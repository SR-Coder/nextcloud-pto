# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## 0.4.3 - 2026-03-19

### Added
- **Calendar Integration** - Approved PTO requests automatically create calendar events
  - Admin settings UI for selecting target calendar
  - RFC 5545 compliant iCalendar event generation
  - All-day events with proper date formatting (DTSTART;VALUE=DATE)
  - Version-adaptive implementation (supports both NC27 and NC28+)
  - Graceful fallback when calendar integration is disabled
  - Events formatted as `[PTO] Username - Leave Type` with duration and notes

### Changed
- Calendar event creation uses runtime feature detection instead of version checking
- Updated admin settings panel to include calendar selection dropdown
- Improved text escaping for iCalendar SUMMARY and DESCRIPTION fields per RFC 5545

### Fixed
- Calendar lookup now searches across all users (calendar owner may differ from PTO requester)
- Nextcloud template caching issues resolved
- JavaScript build outputs IIFE format (not ES modules) for browser compatibility
- CSS deployment and asset management

### Technical
- Implemented `CalendarService` with dual-path event creation:
  - NC28+: Modern `createEventBuilder()` API
  - NC27: Manual RFC 5545 iCal generation
- Added `CalendarSettingsController` for calendar management API
- Created `CalendarSettings.vue` component for admin UI
- Followed official Nextcloud Calendar Integration documentation

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
