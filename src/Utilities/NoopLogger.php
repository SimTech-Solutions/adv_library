<?php

declare(strict_types=1);

namespace AdvClientAPI\Utilities;

use AdvClientAPI\Contracts\LoggerInterface;

/**
 * No-op logger implementation (silent)
 */
class NoopLogger implements LoggerInterface
{
    public function debug(string $message, array $context = []): void
    {
        // Silent
        print("DebugLog: $message");
        var_dump($context);  
    }

    public function info(string $message, array $context = []): void
    {
        
           print('InfoLog: '. $message);
           var_dump($context);
      
    }

    public function warning(string $message, array $context = []): void
    {
        print('WaringnLog: '. $message);
        var_dump($context);
    }

    public function error(string $message, array $context = []): void
    {
       
        print('ErrorLog: '. $message);
        var_dump($context);
    }
}
