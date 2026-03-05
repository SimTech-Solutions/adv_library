<?php

declare(strict_types=1);

namespace AdvClientAPI\Templates;

use AdvClientAPI\Exceptions\ResponseParsingException;
use AdvClientAPI\Utilities\DateFormatter;

/**
 * Jinja2-style template renderer for SOAP templates
 * Supports:
 * - Variable substitution: {{ variable }}
 * - Dot notation: {{ data.field }}
 * - For loops: {% for item in array %}...{% endfor %}
 * - Conditionals: {% if condition %}...{% else %}...{% endif %}
 * - Method calls: {{ data.get('field') }}
 */
class TemplateRenderer
{
    private string $templatesPath;

    /**
     * Constructor
     *
     * @param string $templatesPath Path to templates directory
     */
    public function __construct(string $templatesPath)
    {
        $this->templatesPath = rtrim($templatesPath, '/');
    }

    /**
     * Render template with variables
     * Automatically injects security context (username, password, created, expires, timestamp_id)
     *
     * @param string $templateName Template name (e.g., "advancecare/pharma_act.xml.j2")
     * @param array<string, mixed> $variables Variables to inject
     * @return string Rendered template
     * @throws ResponseParsingException
     */
    public function render(string $templateName, array $variables = []): string
    {
        $templatePath = $this->templatesPath . '/' . $templateName;

        if (!file_exists($templatePath)) {
            throw new ResponseParsingException(
                "Template not found: {$templateName}",
                '',
                'Template file'
            );
        }

        $content = file_get_contents($templatePath);
        if ($content === false) {
            throw new ResponseParsingException(
                "Failed to read template: {$templateName}",
                '',
                'Template file'
            );
        }

        // Inject security context
        $this->injectSecurityContext($variables);

        // Process template with support for loops, conditionals, and variables
        try {
            $content = $this->processTemplate($content, $variables);
        } catch (\Exception $e) {
            throw new ResponseParsingException(
                "Template processing error: {$e->getMessage()}",
                $content,
                'Template'
            );
        }

        return $content;
    }

    /**
     * Process template with variable substitution, loops, and conditionals
     *
     * @param string $content
     * @param array<string, mixed> $variables
     * @return string
     */
    private function processTemplate(string $content, array $variables): string
    {
        // 1. Process for loops first (innermost first)
        $content = $this->processForLoops($content, $variables);

        // 2. Process conditionals
        $content = $this->processConditionals($content, $variables);

        // 3. Process variable substitution
        $content = $this->processVariables($content, $variables);

        return $content;
    }

    /**
     * Process {% for item in array %}...{% endfor %} loops
     *
     * @param string $content
     * @param array<string, mixed> $variables
     * @return string
     */
    private function processForLoops(string $content, array $variables): string
    {
        // Match: {% for varname in arrayname %} ... {% endfor %}
        $pattern = '/\{%\s*for\s+(\w+)\s+in\s+(\w+(?:\.\w+)*)\s*%\}(.*?)\{%\s*endfor\s*%\}/s';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $itemName = $matches[1];
            $arrayPath = $matches[2];
            $loopBody = $matches[3];

            // Get the array to iterate
            $array = $this->getValueByPath($variables, $arrayPath);

            if (!is_array($array)) {
                return ''; // Skip loop if not an array
            }

            $result = '';
            foreach ($array as $item) {
                // Create loop context with item and loop variable
                $loopVars = $variables;
                $loopVars[$itemName] = $item;
                $loopVars['loop'] = [
                    'index' => count(explode('|', $result)) % count($array),
                ];

                // Process body with loop variables
                $result .= $this->processVariables($loopBody, $loopVars);
            }

            return $result;
        }, $content);
    }

    /**
     * Process {% if condition %}...{% else %}...{% endif %} conditionals
     *
     * @param string $content
     * @param array<string, mixed> $variables
     * @return string
     */
    private function processConditionals(string $content, array $variables): string
    {
        // Match: {% if condition %}...{% else %}...{% endif %} or {% if condition %}...{% endif %}
        $pattern = '/\{%\s*if\s+(.+?)\s*%\}(.*?)(?:\{%\s*else\s*%\}(.*?))?\{%\s*endif\s*%\}/s';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $condition = trim($matches[1]);
            $trueBody = $matches[2];
            $falseBody = $matches[3] ?? '';

            // Evaluate condition
            if ($this->evaluateCondition($condition, $variables)) {
                return $trueBody;
            } else {
                return $falseBody;
            }
        }, $content);
    }

    /**
     * Process {{ variable }} substitutions
     *
     * @param string $content
     * @param array<string, mixed> $variables
     * @return string
     */
    private function processVariables(string $content, array $variables): string
    {
        // Match: {{ variable }} or {{ variable.field }} or {{ variable.get('field') }}
        $pattern = '/\{\{\s*([\w.]+(?:\.[\'"]?\w+[\'"]?)?)\s*\}\}/';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $path = trim($matches[1]);
            $value = $this->getValueByPath($variables, $path);

            if ($value === null) {
                return '';
            }

            if (is_array($value) || is_object($value)) {
                return '';
            }

            return (string)$value;
        }, $content);
    }

    /**
     * Get value from variables by dot-notation path
     * Supports: field, data.field, array[0].field
     *
     * @param array<string, mixed> $variables
     * @param string $path
     * @return mixed
     */
    private function getValueByPath(array $variables, string $path): mixed
    {
        $parts = explode('.', $path);
        $current = $variables;

        foreach ($parts as $part) {
            // Handle get() method calls: field.get('key')
            if (preg_match('/^(\w+)\([\'"](\w+)[\'"]\)$/', $part, $matches)) {
                $key = $matches[2];
                if (is_array($current) && isset($current[$key])) {
                    $current = $current[$key];
                } else {
                    return null;
                }
                continue;
            }

            // Handle normal field access
            if (is_array($current)) {
                if (!isset($current[$part])) {
                    return null;
                }
                $current = $current[$part];
            } elseif (is_object($current)) {
                if (!property_exists($current, $part)) {
                    return null;
                }
                $current = $current->$part;
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Evaluate template condition
     * Supports: variable, variable.field, variable.get('field')
     *
     * @param string $condition
     * @param array<string, mixed> $variables
     * @return bool
     */
    private function evaluateCondition(string $condition, array $variables): bool
    {
        // Handle .get('field') method calls
        if (preg_match('/^(.+?)\.get\([\'"](.*?)[\'"]\)$/', $condition, $matches)) {
            $value = $this->getValueByPath($variables, $matches[1]);
            if (is_array($value) && isset($value[$matches[2]])) {
                return !empty($value[$matches[2]]);
            }
            return false;
        }

        // Handle simple path checking
        $value = $this->getValueByPath($variables, $condition);
        return !empty($value);
    }

    /**
     * Inject security context variables
     * Adds: username, password, created, expires, timestamp_id (if not already present)
     *
     * @param array<string, mixed> &$variables
     * @return void
     */
    private function injectSecurityContext(array &$variables): void
    {
        // Use provided values or generate defaults
        if (!isset($variables['created'])) {
            $variables['created'] = DateFormatter::getCurrentIso8601();
        }

        if (!isset($variables['expires'])) {
            // Default: 10 minutes from now
            $created = new \DateTime($variables['created']);
            $created->modify('+10 minutes');
            $variables['expires'] = $created->format(\DateTime::ATOM);
        }

        if (!isset($variables['timestamp_id'])) {
            $variables['timestamp_id'] = $this->generateTimestampId();
        }
    }

    /**
     * Generate unique timestamp ID (UUID)
     *
     * @return string
     */
    private function generateTimestampId(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // RFC 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Get template path
     *
     * @return string
     */
    public function getTemplatesPath(): string
    {
        return $this->templatesPath;
    }
}
