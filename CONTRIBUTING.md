# Contributing to Nextcloud PTO Tracker

## 🚨 Critical Rules

### 1. **NO DIRECT COMMITS TO MAIN**
- **ALWAYS** work in feature branches
- **ALWAYS** create a Pull Request
- **NEVER** push directly to `main`

### 2. **NO SECRETS IN CODE**
- Never commit API keys, passwords, tokens, or credentials
- Use environment variables or Nextcloud's config system
- GitHub Actions runs secret scanning on every PR

### 3. **EVERYTHING IN GIT**
- All changes must be tracked in git with clear commit messages
- No "oops I forgot to commit" - commit early, commit often
- Use `.gitignore` for local dev files only

## Branch Workflow

### Feature Development
```bash
# Start new feature
git checkout main
git pull
git checkout -b feature/your-feature-name

# Make changes and commit
git add .
git commit -m "Add feature: clear description"

# Push to GitHub
git push -u origin feature/your-feature-name

# Create Pull Request on GitHub
# Wait for CI checks to pass
# Merge via GitHub UI (not CLI)
```

### Bug Fixes
```bash
git checkout -b fix/bug-description
# ... make changes ...
git commit -m "Fix: clear description of what was fixed"
git push -u origin fix/bug-description
# Create PR
```

### Branch Naming
- `feature/` - New features
- `fix/` - Bug fixes
- `docs/` - Documentation updates
- `refactor/` - Code refactoring
- `test/` - Test additions/changes

## Pull Request Guidelines

### Before Creating PR
- ✅ Code follows PSR-12 (PHP) and ESLint (JS) standards
- ✅ No secrets committed (check with `git log -p`)
- ✅ Tests pass (when we add them)
- ✅ Migrations are reversible
- ✅ No `console.log()` or `var_dump()` left in code

### PR Description Template
```markdown
## What
Brief description of changes

## Why
Why this change is needed

## How
Technical approach taken

## Testing
How to test this change

## Screenshots (if UI changes)
Before/after screenshots
```

### PR Checklist
- [ ] Branch is up to date with `main`
- [ ] Commit messages are clear
- [ ] No merge conflicts
- [ ] CI checks pass
- [ ] Code has been self-reviewed
- [ ] Related issue linked (if applicable)

## Commit Message Format

```
<type>: <short description>

<optional detailed explanation>

<optional issue reference>
```

### Types
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation
- `style:` - Formatting, semicolons, etc (no code change)
- `refactor:` - Code restructuring
- `test:` - Adding tests
- `chore:` - Build process, dependencies

### Examples
```
feat: Add calendar integration for approved requests

Implements CalDAV event creation when a PTO request is approved.
Events are created in the user's default calendar with proper
start/end times and descriptions.

Closes #42
```

```
fix: Prevent negative balance on request cancellation

Balance restoration was double-counting hours in some cases.
Added check to ensure balance never goes negative.
```

## Code Standards

### PHP (Backend)
- Follow PSR-12
- Type hints on all parameters and return types
- DocBlocks for public methods
- Use Nextcloud's QueryBuilder for all DB queries
- Never use raw SQL

### JavaScript (Frontend)
- Use ES6+ features
- Vue 3 Composition API preferred
- PropTypes for all component props
- No `var`, only `const` and `let`
- eslint must pass

### Security
- **Validate all user input**
- **Escape all output**
- **Use prepared statements** (QueryBuilder handles this)
- **Check permissions** before sensitive operations
- **Never trust client-side validation alone**

## Testing (Coming Soon)

### PHP Tests
```bash
./vendor/bin/phpunit
```

### JavaScript Tests
```bash
npm test
```

## Local Development Setup

See [README_DEV.md](README_DEV.md) for complete setup instructions.

## Questions?

- Check existing issues and PRs first
- Open a GitHub Discussion for questions
- Open an Issue for bugs or feature requests

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
