# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-03-05

### Added

- Initial release of ADV Insurance PHP Library
- **Oracle CloudGate REST API** with OAuth2 authentication
  - PharmaAct eligibility submission (`oraclePerformPharmaAct`)
  - Eligibility creation (`oracleCreateEligibility`)
  - Invoice processing (`oracleAddInvoice`)
  - Eligibility cancellation (`oracleCancelEligibility`)
- **AdvanceCare SOAP API** for legacy systems
  - PharmaAct submission (`performPharmaAct`)
  - Invoice processing (`addInvoice`)
  - Eligibility cancellation (`cancelEligibility`)
- OAuth2 client credentials flow with automatic token management
- Token caching with configurable TTL (default 1 hour)
- Automatic redirect handling for Oracle CloudGate authentication
- Comprehensive response mapping for all API endpoints
- Exception hierarchy with specific exception types:
  - `AuthException` - Authentication and token failures
  - `SoapException` - SOAP-specific errors
  - `OracleException` - Oracle REST API failures
  - `ResponseParsingException` - Response mapping failures
  - `ConfigException` - Configuration errors
  - `InsuranceApiException` - Base exception for all library errors
- SOAP template rendering system
- Pluggable logging interface (`LoggerInterface`)
- DateTime formatting utilities with ISO 8601 support
- XML/JSON parsing utilities
- Retry policy framework with exponential backoff
- Composer autoloading (PSR-4)
- PHPUnit test suite with unit tests
- Static analysis with PHPStan
- Code standards with PHPCS

### Features

- **Automatic Redirect Handling**: Seamlessly follows OAuth2 and API redirects with proper header preservation
- **Token Management**: Automatic OAuth2 token acquisition with Bearer token prefix and caching
- **Cookie Management**: Automatic cookie preservation across redirects for CloudGate authentication
- **Multiple Services**: Support for both modern REST (Oracle) and legacy SOAP (AdvanceCare) APIs
- **Response Mapping**: Automatic mapping of API responses to standardized array structures
- **Configurable Scope**: Per-request or global OAuth2 scope configuration
- **Environment Support**: Test and production environment configurations
- **Logger Interface**: Pluggable logging for debugging and monitoring

### Security

- Bearer token authentication for Oracle REST API calls
- Basic authentication for OAuth2 token endpoint
- Proper cookie-based session management
- Support for secure production and test environment separation
- Token caching without exposing tokens in logs

### Documentation

- Comprehensive installation and quick start guide
- Complete API reference with exception documentation
- Configuration examples for test and production
- Usage examples for all supported operations (REST and SOAP)
- Error handling patterns with exception catching
- Troubleshooting guide with common issues
- Request and response structure documentation
