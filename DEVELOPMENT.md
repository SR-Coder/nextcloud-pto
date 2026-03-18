# Development Notes

## Architecture Overview

Based on Nextcloud app development best practices and requirements analysis.

### Tech Stack

- **Backend**: PHP 8.0+ with Nextcloud OCP (OpenCloudPlatform) framework
- **Frontend**: Vue.js 3 (Nextcloud standard)
- **Database**: Nextcloud DB abstraction layer (supports MySQL, PostgreSQL, SQLite)
- **APIs**: RESTful endpoints using Nextcloud routing

### Database Schema (Planned)

#### Tables

1. **pto_policies**
   - id, name, type (unlimited|accrual|fixed), accrual_rate, max_balance, reset_date, created_at, updated_at

2. **pto_balances**
   - id, user_id, policy_id, balance, accrued_this_period, used_this_period, last_accrual_date

3. **pto_requests**
   - id, user_id, policy_id, start_date, end_date, hours, status (pending|approved|denied), notes, created_at

4. **pto_approvals**
   - id, request_id, manager_id, status, comments, acted_at

5. **pto_user_roles**
   - id, user_id, role (admin|manager|employee), manager_id (for employee reporting structure)

### Key Components

#### Backend (lib/)

- **Controllers**: Handle HTTP requests/responses
  - `RequestController.php` - PTO request CRUD
  - `PolicyController.php` - Policy management
  - `ApprovalController.php` - Approval workflow
  - `ReportController.php` - Analytics and reports

- **Services**: Business logic layer
  - `PolicyService.php` - Policy calculations (accrual, balances)
  - `RequestService.php` - Request validation and workflow
  - `NotificationService.php` - Email notifications
  - `CalendarService.php` - CalDAV integration

- **Db**: Database entities and mappers
  - Entity classes for each table
  - Mapper classes for CRUD operations

#### Frontend (src/)

- **Components**: Reusable UI elements
  - `RequestForm.vue` - Submit PTO request
  - `RequestList.vue` - View requests
  - `ApprovalQueue.vue` - Manager approval interface
  - `BalanceDisplay.vue` - Show PTO balances
  - `PolicyConfig.vue` - Admin policy setup

- **Views**: Page-level components
  - `Dashboard.vue` - Main view
  - `MyRequests.vue` - Employee view
  - `TeamRequests.vue` - Manager view
  - `AdminSettings.vue` - System admin view

### Integrations

#### Nextcloud Calendar (CalDAV)

- Use `OCP\Calendar\ICalendar` and `CalDavBackend`
- Create calendar events on approval
- Allow users to select target calendar
- Handle event deletion on request cancellation

#### Nextcloud Talk (Optional)

- Use `OCA\Talk` API to set user status
- Auto-status: "On Vacation" during approved leave
- Toggleable in user settings for privacy

#### Email Notifications

- Use `OCP\Mail\IMailer`
- Notify on: request submission, approval, denial, upcoming leave

### Background Jobs

Use Nextcloud's background job system for:
- Accrual calculations (daily/monthly)
- Balance resets (annual)
- Reminder notifications

### Development Workflow

1. **Local Development**
   - Clone repo to `nextcloud/apps/pto/`
   - Enable dev mode in Nextcloud
   - Use `npm run dev` for frontend hot reload

2. **Testing**
   - Unit tests: PHPUnit for backend
   - Integration tests: Test against Nextcloud test instance
   - Frontend tests: Jest + Vue Test Utils

3. **Deployment**
   - Build: `npm run build`
   - Package: Create tarball for App Store
   - Submit: Via Nextcloud App Store developer portal

### Security Considerations

- Always use prepared statements (Nextcloud's QueryBuilder)
- Validate all user input
- Check permissions before sensitive operations
- Use Nextcloud's CSRF protection
- Follow PSR-12 coding standards

### Gotchas to Avoid

- Don't hardcode paths, URLs, or roles
- Test across Nextcloud versions (25, 26, 27)
- Use background jobs for heavy operations
- Support i18n from the start (l10n folder)
- Document all public APIs
- Provide migration scripts for schema changes

### Resources

- [Nextcloud Developer Manual](https://docs.nextcloud.com/server/latest/developer_manual/)
- [Nextcloud App Tutorial](https://docs.nextcloud.com/server/latest/developer_manual/app_development/tutorial.html)
- [OCP API Reference](https://docs.nextcloud.com/server/latest/developer_manual/basics/classloader.html)
- [App Store Guidelines](https://nextcloudappstore.readthedocs.io/en/latest/)
