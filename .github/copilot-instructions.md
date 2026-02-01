# OpenSourceBikeShare AI Development Guide

## Project Overview
A Symfony 6+ PHP bike-sharing system with multi-modal rental support (web, SMS, QR codes), credit system, and Docker deployment. Uses PDO with MariaDB and follows domain-driven design with tagged services.

## Architecture Patterns

### Core Domain Services
- **Rent Systems**: Multiple implementations (`RentSystemWeb`, `RentSystemSms`, `RentSystemQR`) implementing `RentSystemInterface`
- **SMS Connectors**: Pluggable SMS providers (`EuroSmsConnector`, `TextmagicSmsConnector`, `DebugConnector`) via factory pattern
- **Credit Systems**: Optional paid rental system with configurable fees and violations
- **Repository Pattern**: Raw SQL repositories (not Doctrine) - see `src/Repository/BikeRepository.php` for typical patterns

### Service Registration & DI
Services are auto-tagged via interfaces in `config/services.php`:
```php
$services->instanceof(RentSystemInterface::class)->tag('rentSystem');
$services->instanceof(SmsConnectorInterface::class)->tag('smsConnector');
```

Compiler passes in `src/App/Kernel.php` collect tagged services into factories.

### Database Layer
- Custom `DbInterface` abstraction over PDO (not Doctrine ORM)
- Repositories use raw SQL with parameter binding
- Database schema includes: `bikes`, `stands`, `users`, `history`, `notes`
- Use `BikeShare\Db\PdoDb` for database operations

## Development Workflow

### Docker Environment
```bash
# Start services (nginx, PHP-FPM, MariaDB, phpMyAdmin)
docker-compose up -d

# Access application: http://localhost:80
# phpMyAdmin: http://localhost:81
```

### Testing
```bash
# Run full test suite (includes fixture loading)
composer test

# Manual test commands
php bin/console cache:clear
php bin/console load:fixtures
vendor/bin/phpunit
```

### Code Quality
- **PHP CS Fixer**: `phpcs.xml` with Slevomat standards
- **Rector**: `rector.php` for automated refactoring  
- **PHPUnit**: Unit/Integration/Application tests with fixtures

## Key Conventions

### Controller Patterns
Controllers extend `AbstractController` and use route attributes:
```php
#[Route('/', name: 'index')]
public function index(Request $request, CreditSystemInterface $creditSystem): Response
```

### Factory Pattern Usage
- `RentSystemFactory`: Selects rent system based on request context
- `SmsConnectorFactory`: Creates SMS connector from env config (`SMS_CONNECTOR`)
- `CreditSystemFactory`: Enables/disables credit system based on configuration

### Configuration Binding
Environment variables bound in services.php:
```php
->bind('$isSmsSystemEnabled', expr("container.getEnv('SMS_CONNECTOR') ? true : false"))
->bind('$systemRules', env('SYSTEM_RULES'))
```

### SMS Command Processing
SMS commands implement `SmsCommandInterface` and are auto-registered. Commands are parsed and routed via `SmsTextNormalizer` implementations.

## File Organization
- `src/Controller/`: Web controllers grouped by functionality
- `src/Repository/`: Database access layer with raw SQL
- `src/Rent/`: Bike rental system implementations  
- `src/SmsConnector/`: SMS provider integrations
- `src/Credit/`: Optional payment/credit system
- `templates/`: Twig templates (mobile-responsive)
- `tests/`: PHPUnit tests with fixture support

## Critical Integration Points
- **Multi-modal rental**: Web forms, SMS parsing, QR code scanning all use same `RentSystemInterface`
- **Notifications**: Email and SMS alerts for violations/system events
- **Geographic**: Multi-city support via `CityRepository`
- **Security**: Custom authentication with privilege levels (0-7)

When implementing features, consider which rental modes need support and whether SMS/credit system integration is required.