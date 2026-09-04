# AGENTS.md

This file provides guidance to AI agents when working with code in this repository.

## Project Overview

A PHP library of PHPUnit traits and base classes that other projects consume via `composer require`. It was created from the Scaffold template.

## PHP Application Architecture

### Class Library

This project ships classes only - there is no CLI entry point:

- **Location:** `src/` directory, autoloaded PSR-4
- **Consumed by:** other projects, via `composer require`
- **Contents:** `src/Traits/` holds the reusable test traits; `src/UnitTestCase.php` is the base test case

Add classes under `src/` and cover each one with a test in `tests/Unit/`.

### Namespace Structure

- Source code: `AlexSkrypnyk\PhpunitHelpers\`
- Tests: `AlexSkrypnyk\PhpunitHelpers\Tests\`
- Autoloading: PSR-4 via Composer

## Commands

### Code Quality

```bash
# Run all linters (PHPCS, PHPStan, Rector)
composer lint

# Auto-fix code style issues
composer lint-fix

# Individual tools
./vendor/bin/phpcs # Check coding standards
./vendor/bin/phpcbf # Fix coding standards
./vendor/bin/phpstan # Static analysis (level 9)
./vendor/bin/rector --dry-run # Check Rector suggestions
```

### Testing

```bash
# Run all PHPUnit tests (fast, no coverage)
composer test

# Run with coverage reports
composer test-coverage
# Coverage reports: .logs/.coverage-html/index.html, .logs/cobertura.xml

# Run specific test file
./vendor/bin/phpunit tests/Unit/ProcessTraitTest.php

# Run specific test method
./vendor/bin/phpunit --filter testMethodName

# Run the tests excluded from the default suite
./vendor/bin/phpunit --group=manual
```

### Building

```bash
# Clean and reinstall dependencies
composer reset # removes vendor/, composer.lock
composer install
```

## Code Quality Standards

### Three-Layer Quality Stack

1. **PHP_CodeSniffer** - Drupal coding standards + strict types requirement
  - Config: `phpcs.xml`
  - Rules: Drupal standard, DrevOps standard, Generic.PHP.RequireStrictTypes
  - Relaxed rules in test files (long arrays, missing function docs)

2. **PHPStan** - Level 9 static analysis
  - Config: `phpstan.neon`
  - Ignores: Untyped iterables in tests/data providers

3. **Rector** - PHP 8.3 modernization + code quality
  - Config: `rector.php`
  - Sets: PHP 8.3, code quality, coding style, dead code, type declarations, naming, early return, PHPUnit code quality

### Coding Conventions

- All PHP files must declare `strict_types=1`
- Use single quotes for strings (double quotes if containing single quote)
- All files must end with a newline character
- Local variables/method arguments: `snake_case`
- Method names/class properties: `camelCase`

### Trait Member Prefixes

Every method and property declared in a trait under `src/Traits/` starts with that trait's prefix, so members remain traceable to their trait once composed into a consuming class.

The prefix is the trait name minus the `Trait` suffix, lower-camel-cased: `ApplicationTrait` uses `application*`, `EnvTrait` uses `env*`, `TuiTrait` uses `tui*`, `LocationsTrait` uses `locations*`, `ProcessTrait` uses `process*`.

Four deliberate exceptions, each of which stays as it is:

- **Assertion methods keep the PHPUnit `assert` prefix first**, with the trait's subject immediately after: `assertProcessSuccessful()`, `assertApplicationOutputContains()`, `assertArrayContainsString()`, `assertStringContainsOrNot()`. This keeps them grouped with PHPUnit's own assertions in editor autocomplete.
- **`LoggerTrait` uses `log` rather than `logger`**, applied consistently to every method and property: `logSetVerbose()`, `logStepStart()`, `logFormatElapsedTime()`, `$logSteps`, `$logIsVerbose`.
- **`SerializableClosureTrait` keeps `cw()` and `cu()`.** The names are deliberately short because they appear inline in data providers, where longer names push lines past the limit. The trait docblock records this.
- **`ReflectionTrait` keeps `callProtectedMethod()`, `setProtectedValue()` and `getProtectedValue()`.** The names read naturally at the call site and are in wide use.

`LocationsTrait`'s `$root`, `$fixtures`, `$workspace`, `$repo`, `$sut` and `$tmp` properties are unprefixed. They are read directly as `self::$fixtures` and `self::$tmp` throughout consuming test suites, so they are left alone; the accessors `locationsRoot()`, `locationsFixtures()` and the rest already follow the rule.

## Testing Patterns

### PHPUnit Structure

- `tests/Unit/` - Unit tests with mocks, no I/O
- `tests/Functional/` - Integration tests, real file system and processes
- `tests/Fixtures/` - Test fixtures, including a sample Symfony Console application

Tests carrying `#[Group('manual')]` are excluded from the default suite and are run on demand to observe behaviour interactively.

### Writing Tests

- Coverage attributes: `#[CoversClass(ClassName::class)]`
- Data providers: `#[DataProvider('providerMethodName')]`, declared after the test method they serve
- Prefer data providers over repeated near-identical test methods

## CI/CD

GitHub Actions workflows test across:

- PHP versions: 8.2, 8.3, 8.4, 8.5
- Dependency preferences: `normal` and `lowest`, which between them cover the supported PHPUnit range

Key workflows:

- `.github/workflows/test-php.yml` - PHP testing, linting and coverage upload (Codecov)
- `.github/workflows/draft-release-notes.yml` - release notes drafting
- `.github/workflows/assign-author.yml` - PR author auto-assign

## Git Workflow

- Create feature branches as `feature/branch-name`: lowercase and hyphens only, at most 20 characters for the name part, articles and common words removed, abbreviated where necessary
- Do not push to remote unless explicitly asked
- Push a new branch with `git push -u origin branch-name`, and subsequent pushes with `git push`

## Commit Message Standards

- Start with a verb in past tense (added, updated, deleted, removed, fixed)
- End with a period
- Use backticks around code references, for example `ClassName::methodName()`
- Focus on what was accomplished, not on implementation detail
- Make sure `composer lint` passes before committing

## Pull Requests

- Create pull requests with `gh pr create`, only when explicitly asked
- Always include a meaningful title and description
- Structure the body as a `## Summary` section of bullet points and a `## Test plan` checklist
- Do not include a "Generated with Claude Code" footer in pull request descriptions or commit messages

## AI Code Review Tools (CodeRabbit, etc.)

Monitor PR reviews from AI tools such as CodeRabbit for feedback and nitpicks. Read them with `gh api repos/OWNER/REPO/pulls/PR_NUMBER/reviews`.

Address all nitpicks and actionable feedback iteratively:

1. Make code changes based on feedback
2. Run tests and linting: `composer test && composer lint`
3. Fix any coding standard violations: `composer lint-fix`
4. Commit changes with a descriptive message
5. Push changes to the remote branch
6. Trigger a new review: `gh pr comment PR_NUMBER --body "@coderabbitai full review"`
7. Wait for the new review and repeat until no nitpicks remain

Common improvement areas: test coverage, edge cases, error handling, code organization.

### Rate Limits

CodeRabbit enforces hourly rate limits per developer and organization. Space out commits to avoid hitting them, waiting roughly 15 minutes between reviews. Rate limit messages appear in PR comments when exceeded. Once the limit expires, trigger a new review with `@coderabbitai review` or by pushing new commits. Incremental reviews cover only new commits, so use `@coderabbitai full review` for a comprehensive pass.

## Updating from the template

This project was generated from a template and can pull the template's latest
infrastructure (CI workflows, linting and test configuration, Docker, and docs)
at any time. When you are asked to "update scaffold", fetch the updater skill
from the template repository into the local `.claude/skills/` directory, then
invoke it:

1. Create the target directory if it does not exist:

   ```bash
   mkdir -p .claude/skills/update-consumer-scaffold
   ```

2. Download the skill:

   ```bash
   curl -sSL https://raw.githubusercontent.com/AlexSkrypnyk/scaffold/main/.scaffold/skills/update-consumer-scaffold/SKILL.md -o .claude/skills/update-consumer-scaffold/SKILL.md
   ```

3. Invoke the `update-consumer-scaffold` skill and follow its steps.

The skill directory is fetched on demand and is git-ignored - it is not
committed to the project.
