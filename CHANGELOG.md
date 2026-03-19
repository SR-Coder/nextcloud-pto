# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## 0.3.4 - 2026-03-18

### Added
- Complete PTO request submission and tracking system
- Manager approval workflow with approve/deny actions
- Balance display by policy type (unlimited, accrual, fixed)
- My Requests view with request history and filtering
- Approval Queue for managers to review team requests
- Admin policy management (create, enable/disable policies)
- Native Nextcloud manager integration (uses built-in user management)
- Request cancellation for pending requests
- Status badges and visual indicators

### Changed
- Rebuilt app structure to properly use Nextcloud framework
- Removed all custom CSS overrides in favor of Nextcloud's built-in styles
- Updated to use proper Nextcloud DOM structure (#app-navigation + #app-content)

### Fixed
- Vue 3 compatibility (removed deprecated $set() calls)
- Self-approval prevention (users cannot approve their own PTO requests)
- Form button visibility (proper scrolling behavior)
- Dropdown option styling
- Side-by-side layout matching Nextcloud design patterns
- Error message sanitization (no SQL exposure)
- CSRF protection on all endpoints
- Authorization checks for all manager actions

### Security
- Sanitized all error messages to prevent SQL query exposure
- Added authorization checks to prevent users approving own requests
- CSRF token validation on all state-changing operations
- Proper permission checks for manager and admin actions
