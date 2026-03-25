# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## 1.0.0 - 2026-03-24

### Release Notes
**First stable release!** Fully tested and working on Nextcloud 33.

### Added
- Complete Nextcloud 33 compatibility
- Flexible PTO policies (unlimited, accrual-based, fixed annual)
- Manager approval workflow
- Native Nextcloud notifications
- Calendar integration
- Admin policy management UI
- Access control per user
- Dark mode support
- Multiple leave types

### Changed
- Migrated to Nextcloud 33 APIs
- Updated Calendar integration for NC33
- Removed deprecated API calls
- Minimum requirements: NC33+, PHP 8.2+

### Fixed
- Calendar event creation on NC33
- Logger calls updated for NC33 compatibility
- Template script/style loading
- All features fully functional

# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).
>>>>>>> 765692d (Release v1.0.0 - First Stable Release)

## 0.5.6 - 2026-03-19

### Fixed
- **Policy Assignment Fully Working**
  - Routes file properly deployed (removePolicy route was missing)
  - Controller method deployed (removePolicy() was missing)
  - Service method deployed (deleteBalance() was missing)
  - Removed debug console.log statements

### Changed
- Admins can now assign/remove policies from users
- Save button persists changes correctly
- Success messages display after save
- All backend methods properly deployed

### Technical
- Added POST /api/v1/balances/remove endpoint
- BalanceController::removePolicy() - admin-only
- BalanceService::deleteBalance() - deletes balance record
- PolicyAssignment component processes pending removals

## 0.5.5 - 2026-03-19

### Added
- **Access Control** - Users without assigned policies cannot access PTO app
  - Shows "PTO Tracker Not Available" message for unauthorized users
  - Admins always have access (even without policies)
  - Prevents contractors/external users from accessing the system

### Fixed
- PolicyAssignment user dropdown showing "()"
  - API returns { users: [...] } but component expected array
  - Now handles both data.users and direct array format

### Changed
- App.vue checks if user has any balances OR is admin
- If no access → shows message to contact administrator
- If has access → shows normal PTO interface

## 0.5.4 - 2026-03-19

### Added
- **Policy Assignment UI for Admins**
  - New component: PolicyAssignment.vue
  - Per-user policy assignment interface
  - Select user from dropdown
  - Shows all available policies with checkboxes
  - Assign/unassign policies
  - Set initial balance when assigning
  - Shows current balance for assigned policies

### Changed
- Added PolicyAssignment component to AdminSettings
- Updated package.json with build:main and build:admin scripts
- Build now compiles both main app and admin panel

### Technical
- Updated balances endpoint to accept optional userId parameter
- Admins can query any user's balances via GET /api/v1/balances?userId=xxx
- Non-admins still only see their own balances

## 0.5.3 - 2026-03-19

### Added
- **Permission-Based Navigation** - Approvals tab only visible to managers/admins
  - Navigation component checks user permissions
  - Uses GET /api/v1/users/managers endpoint for canApprove flag
  - Non-managers see: Dashboard, My Requests, New Request
  - Managers see: Dashboard, My Requests, New Request, Approvals

### Fixed
- Removed 2 debug console.log statements from ApprovalQueue.vue
- Navigation now uses correct API endpoint for canApprove check

## 0.5.2 - 2026-03-19

### Added
- **Cancel Notification** - Managers receive notification when employee cancels a PTO request
  - `notifyRequestCancelled()` in NotificationService
  - `prepareRequestCancelled()` in Notifier
  - Integrated into RequestService::cancelRequest()
  - Shows employee name, hours, and date range

### Technical
- Notification sent to all managers when request is cancelled
- Does not immediately remove notification (allows managers to see it)

## 0.5.1 - 2026-03-19

### Fixed
- **Nextcloud Notification System Now Working!**
  - Added `\OC_App::loadApp('notifications')` before calling notify()
  - Root cause: notifications app wasn't auto-loaded, so IApp implementation wasn't registered
  - This caused notify() to silently loop through zero apps and do nothing

### Changed
- Removed excessive debug logging from NotificationService
- Cleaned up Notifier (removed action buttons that need OCS routes)
- All notification types now working:
  - Request submitted → Managers notified
  - Request approved → Employee notified
  - Request denied → Employee notified

### Technical
- Works on Nextcloud 27.1.11 and all versions with notifications app
- Follows official Nextcloud notification API documentation
- Notifications appear in bell icon and are stored in oc_notifications table

## 0.5.0 - 2026-03-19

### Added
- **Approval History View** - Collapsible history section in Approval Queue
  - Shows last 50 approved/denied requests for current manager
  - Displays user name, leave type, dates, hours, and decision date
  - Sorted by decision date (most recent first)
  - New API endpoint: `GET /api/v1/requests/history`
  - `RequestService::findHistoryForManager()` method
  - Updated `RequestMapper` methods to accept limit parameter

### Changed
- History section always visible (shows "No approval history yet" when empty)
- Sort by `updated_at DESC` instead of `created_at ASC`

### Fixed
- `formatDate()` error changed to `formatDateTime()`
- History section visibility logic

## 0.4.4 - 2026-03-19

### Fixed
- **Dark Mode Compatibility** - Replaced all hard-coded colors with Nextcloud CSS variables
  - Changed `#333`, `#555` → `var(--color-main-text)`
  - Changed `#666` → `var(--color-text-lighter)`
  - Changed `#0082c9` → `var(--color-primary-element)`
  - Changed `white` → `var(--color-main-background)`
  - Manager assignment info box now readable in dark mode

### Changed
- All Vue components and views use theme-aware colors
- Text is readable in both light and dark themes

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
