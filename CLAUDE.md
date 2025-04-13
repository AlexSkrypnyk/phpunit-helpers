# Claude Code Configuration

## Project Structure
- PHP 8.2+ library
- PHPUnit for testing

## Testing Commands
```bash
# Run all tests
composer test

# Run specific test file
composer test

# Run tests with coverage
composer test-coverage
```

## Code Standards
- Use snake_case for variable names and function arguments
- Use camelCase for class properties
- Follow Drupal coding standards
- Check coding standards with `composer lint`
- Fix coding standards with `composer lint-fix`

## Test Coverage Checking
Coverage reports are stored in:
- `.logs/cobertura.xml`
- `.logs/.coverage-html/`
