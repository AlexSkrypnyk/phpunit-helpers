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

## Pushing to Remote
- Do NOT push to remote repositories unless explicitly asked to do so
- When pushing feature branches for the first time, use: `git push -u origin branch-name`
- For subsequent pushes on the same branch, use: `git push`
- Only push when explicitly requested by the user with commands like "push" or "push this"

## Pull Requests
- Use GitHub CLI to create pull requests: `gh pr create`
- Always include a meaningful title and description
- Format pull request body with:
  - ## Summary section with bullet points describing changes
  - ## Test plan section with checklist of verification steps
- Do NOT include "Generated with Claude Code" footer in pull request descriptions
- Only create pull requests when explicitly asked by the user

## AI Code Review Tools (CodeRabbit, etc.)
- Monitor PR reviews from AI tools like CodeRabbit for feedback and nitpicks
- Use `gh api repos/OWNER/REPO/pulls/PR_NUMBER/reviews` to check review comments
- Address all nitpicks and actionable feedback iteratively:
  1. Make code changes based on feedback
  2. Run tests and linting: `composer test && composer lint`
  3. Fix any coding standard violations: `composer lint-fix`
  4. Commit changes with descriptive message
  5. Push changes to remote branch
  6. Trigger new review: `gh pr comment PR_NUMBER --body "@coderabbitai full review"`
  7. Wait for new review and repeat until no nitpicks remain
- Continue this process until AI reviewer has no more nitpicks or actionable comments
- Common improvement areas: test coverage, edge cases, error handling, code organization

### Rate Limits and Best Practices
- CodeRabbit enforces hourly rate limits per developer/organization
- Space out commits to avoid hitting rate limits (wait ~15 minutes between reviews)
- Rate limit messages will appear in PR comments when exceeded
- After rate limit expires, can trigger new reviews with `@coderabbitai review` or by pushing new commits
- Incremental reviews only review new commits, use `@coderabbitai full review` for comprehensive review

## Commit Message Standards
- Start with a verb in past tense (added, updated, deleted, removed, fixed)
- End with a period
- Use backticks around code references (e.g., `ClassName::methodName()`)
- Do not include "Generated with Claude Code" footer
- Focus on what was accomplished, not technical implementation details
- Make sure that `composer lint` passes before committing
