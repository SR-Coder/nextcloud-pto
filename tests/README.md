# PTO Tracker Tests

## Running Tests

### Prerequisites

Tests require PHPUnit and a Nextcloud instance:

```bash
# Install dependencies (if using composer)
composer install --dev

# Or use Nextcloud's PHPUnit
cd /path/to/nextcloud
```

### Run All Tests

```bash
# From Nextcloud root
./lib/composer/bin/phpunit -c apps/pto/phpunit.xml

# Or from app directory
phpunit
```

### Run Specific Test Suite

```bash
# Unit tests only
phpunit tests/Unit

# Specific test file
phpunit tests/Unit/Service/AuthorizationServiceTest.php
```

## Test Coverage

### Current Coverage

- ✅ **AuthorizationService** - Core security authorization logic
  - Admin role checking
  - Manager relationship validation
  - Manager list retrieval

### Planned Coverage

- PolicyService - Policy CRUD and validation
- RequestService - Request workflow
- BalanceService - Balance calculations
- Controllers - API endpoint validation

## Writing Tests

### Test Structure

```
tests/
├── bootstrap.php           # Test setup
├── Unit/                   # Unit tests (no database)
│   ├── Service/           # Service layer tests
│   └── Controller/        # Controller tests (mocked dependencies)
└── Integration/           # Integration tests (with database)
    └── Api/              # Full API endpoint tests
```

### Example Test

```php
<?php
namespace OCA\PTO\Tests\Unit\Service;

use OCA\PTO\Service\MyService;
use PHPUnit\Framework\TestCase;

class MyServiceTest extends TestCase {
    private MyService $service;

    protected function setUp(): void {
        parent::setUp();
        // Create mocks and initialize service
        $this->service = new MyService(/* mocked dependencies */);
    }

    public function testSomething(): void {
        // Arrange
        $input = 'test';
        
        // Act
        $result = $this->service->doSomething($input);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Best Practices

1. **Use descriptive test names** - `testIsAdminReturnsTrueForAdminUser()`
2. **Mock external dependencies** - Don't hit real database in unit tests
3. **Follow AAA pattern** - Arrange, Act, Assert
4. **Test edge cases** - null values, empty arrays, invalid input
5. **Keep tests focused** - One assertion per test when possible

## Continuous Integration

Tests should be run:
- Before committing changes
- In CI/CD pipeline
- Before releasing new versions

## Coverage Goals

- **Minimum**: 50% code coverage (for App Store submission)
- **Target**: 70% code coverage
- **Ideal**: 80%+ code coverage

Focus coverage on:
1. Service layer (business logic)
2. Authorization logic (security-critical)
3. Data validation
4. API controllers

## Debugging Tests

```bash
# Run with verbose output
phpunit --verbose

# Run with debug info
phpunit --debug

# Stop on first failure
phpunit --stop-on-failure

# Generate coverage report
phpunit --coverage-html coverage/
```

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Nextcloud Testing](https://docs.nextcloud.com/server/latest/developer_manual/basics/testing.html)
- [Test Doubles](https://phpunit.de/manual/current/en/test-doubles.html)
