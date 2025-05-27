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

## Git Workflow
- When creating features, create feature branches using format: `feature/branch-name`
- Convert human-readable feature names to machine-readable branch names:
  - Use lowercase letters and hyphens only
  - Maximum 20 characters for the branch name part
  - Remove articles (a, an, the) and common words
  - Abbreviate when necessary
- Examples:
  - "Add user authentication" → `feature/add-user-auth`
  - "Fix email validation bug" → `feature/fix-email-valid`
  - "Update database schema" → `feature/update-db-schema`
- Do NOT push to remote repositories unless explicitly asked to do so

## Commit Message Standards
- Start with a verb in past tense (added, updated, deleted, removed, fixed)
- End with a period
- Use backticks around code references (e.g., `ClassName::methodName()`)
- Do not include "Generated with Claude Code" footer
- Focus on what was accomplished, not technical implementation details
- Make sure that `composer lint` passes before committing
