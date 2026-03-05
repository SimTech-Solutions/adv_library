# Contributing to ADV Insurance PHP Library

Thank you for your interest in contributing to this project!

## Code of Conduct

- Be respectful in all interactions
- Help others with a friendly attitude
- Report issues clearly without assumptions

## How to Contribute

### 1. Report Issues

Found a bug? Have a question? [Open an issue] with:

- Clear description of the problem
- Steps to reproduce (for bugs)
- Your environment (PHP version, OS, etc.)
- Expected vs. actual behavior

### 2. Submit Code Changes

**Before you start:**

- Fork the repository
- Create a feature branch: `git checkout -b feature/your-feature`

**Code standards:**

- Follow PSR-12 coding standards
- Use type hints for all function parameters and returns
- Write clear, self-documenting code
- Add doc comments for public methods

**Testing:**

```bash
# Run all tests
composer test

# Code quality checks
composer phpstan
composer phpcs

# Fix style issues
composer phpcs-fix
```

**Commit guidelines:**

- Use descriptive commit messages
- Keep commits atomic (one change per commit)
- Reference issues in commits: `Fixes #123`

### 3. Documentation

- Update README.md for major features
- Add examples for new API methods
- Document breaking changes clearly

## Development Setup

```bash
cd /path/to/library
composer install
composer test
```

## Submitting Pull Requests

1. Update documentation
2. Add/update tests
3. Ensure all tests pass
4. Provide clear PR description
5. Reference related issues

## Questions?

Contact: <simtek2022@gmail.com>

**Thank you for contributing! 🎉**