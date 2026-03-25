# Testing Guide

## Overview

This project uses a comprehensive testing strategy to ensure quality and reliability:

- **Backend (PHP)**: PHPUnit for unit and integration tests
- **Frontend (Vue.js)**: Vitest for component and unit tests
- **Coverage**: Track test coverage for both backend and frontend

## Quick Start

### Backend Tests (PHP)

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run only integration tests
composer test:integration

# Run specific test file
vendor/bin/phpunit tests/Unit/Service/PolicyServiceTest.php
```

### Frontend Tests (JavaScript)

```bash
# Install dependencies
npm install

# Run all tests
npm test

# Run tests in watch mode (re-runs on file changes)
npm run test:watch

# Run tests with UI
npm run test:ui

# Generate coverage report
npm run test:coverage
```

## Test Structure

### Backend (PHP)

```
tests/
├── bootstrap.php           # Test bootstrap
├── Unit/                   # Unit tests (isolated, no DB/external dependencies)
│   ├── Service/           # Service layer tests
│   ├── Db/                # Entity/Mapper tests
│   └── Controller/        # Controller tests (mocked dependencies)
└── Integration/            # Integration tests (real DB, Nextcloud APIs)
    ├── Service/
    ├── Db/
    └── Controller/
```

### Frontend (JavaScript)

```
src/
├── components/
│   ├── __tests__/         # Component tests
│   │   └── *.spec.js
│   └── ComponentName.vue
└── views/
    ├── __tests__/         # View/page tests
    │   └── *.spec.js
    └── ViewName.vue
```

## Writing Tests

### Backend Unit Test Example

```php
<?php

namespace OCA\PTO\Tests\Unit\Service;

use OCA\PTO\Service\PolicyService;
use PHPUnit\Framework\TestCase;

class PolicyServiceTest extends TestCase {
    private PolicyService $service;

    protected function setUp(): void {
        $mapper = $this->createMock(PolicyMapper::class);
        $this->service = new PolicyService($mapper);
    }

    public function testCalculateAccrual(): void {
        $policy = new Policy();
        $policy->setType('accrual');
        $policy->setAccrualRate(8.0);
        $policy->setAccrualPeriod('daily');

        $lastAccrual = new DateTime('2026-01-01');
        $currentDate = new DateTime('2026-01-06');

        $accrued = $this->service->calculateAccrual($policy, $lastAccrual, $currentDate);

        $this->assertEquals(40.0, $accrued);
    }
}
```

### Frontend Component Test Example

```javascript
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BalanceDisplay from '../BalanceDisplay.vue'

describe('BalanceDisplay', () => {
    it('renders policy name and balance', () => {
        const wrapper = mount(BalanceDisplay, {
            props: {
                policyName: 'Vacation',
                balance: 80,
                policyType: 'fixed',
            },
        })

        expect(wrapper.text()).toContain('Vacation')
        expect(wrapper.text()).toContain('80')
    })
})
```

## Testing Best Practices

### General

- **Write tests first** (TDD) or alongside feature development
- **One assertion per test** when possible (or related assertions)
- **Descriptive test names** - name should explain what's being tested
- **Arrange-Act-Assert** pattern for clarity
- **Mock external dependencies** in unit tests
- **Don't test implementation details** - test behavior

### Backend (PHP)

- Use PHPUnit's data providers for testing multiple scenarios
- Mock database interactions in unit tests
- Use real database (test instance) for integration tests
- Test both success and failure paths
- Validate error handling and edge cases

### Frontend (Vue)

- Mount components with realistic props
- Test user interactions (clicks, form input, etc.)
- Test component output, not internal state
- Use `findComponent` to test child components
- Mock API calls with `vi.mock()`

## What to Test

### ✅ DO Test

- **Business logic** - Calculations, validations, workflows
- **Edge cases** - Empty inputs, boundary values, null/undefined
- **Error handling** - Invalid input, failed API calls
- **User interactions** - Button clicks, form submissions
- **Component rendering** - Correct output for given props
- **API endpoints** - Request/response handling

### ❌ DON'T Test

- **Third-party libraries** - Assume they're tested
- **Framework internals** - Vue/Nextcloud are tested
- **Trivial getters/setters** - Unless they contain logic
- **Implementation details** - Test behavior, not internal structure

## Coverage Goals

- **Minimum**: 70% overall coverage
- **Target**: 80%+ for core business logic
- **Critical paths**: 100% (approval workflow, balance calculations, accrual)

### Checking Coverage

```bash
# Backend
vendor/bin/phpunit --coverage-html coverage/

# Frontend
npm run test:coverage

# View HTML reports
open coverage/index.html
```

## Continuous Integration

Tests run automatically on every Pull Request via GitHub Actions.

**CI will fail if:**
- Any test fails
- Code coverage drops below threshold
- Linting fails

See `.github/workflows/test.yml` for CI configuration.

## Integration Testing

Integration tests require a Nextcloud test instance. See [NEXTCLOUD_TEST_SETUP.md](docs/NEXTCLOUD_TEST_SETUP.md) for setup instructions.

## Common Issues

### PHPUnit not found
```bash
composer install
```

### Vitest not found
```bash
npm install
```

### Tests fail in CI but pass locally
- Check Node/PHP versions match CI
- Ensure all dependencies are in package.json/composer.json
- Check for hardcoded paths or environment-specific code

### Mock not working
```php
// PHP
$mock = $this->createMock(ClassName::class);
$mock->expects($this->once())
     ->method('methodName')
     ->willReturn('value');
```

```javascript
// JavaScript
import { vi } from 'vitest'
const mock = vi.fn(() => 'value')
```

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Vitest Documentation](https://vitest.dev/)
- [Vue Test Utils](https://test-utils.vuejs.org/)
- [Nextcloud Testing Guide](https://docs.nextcloud.com/server/latest/developer_manual/testing/)
