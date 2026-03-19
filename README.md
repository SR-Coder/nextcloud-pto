# Nextcloud PTO Tracker

A comprehensive PTO (Paid Time Off) and vacation tracking application for Nextcloud with flexible policies, manager approval workflows, and native Nextcloud integration.

## ✨ Features

### ✅ Implemented & Working

- **Flexible PTO Policies**
  - Unlimited time off
  - Accrual-based (daily, weekly, monthly, yearly)
  - Fixed annual allocation
  - Admin-only policy management

- **Request & Approval Workflow**
  - User request submission with date pickers
  - Automatic business day calculation
  - Manager approval queue
  - Request history with status tracking
  - Request cancellation (before approval)

- **Native Nextcloud Integration**
  - Uses Nextcloud's built-in user management
  - Manager relationships (set in Nextcloud Settings → Users)
  - Admin permissions via Nextcloud groups
  - Responsive UI matching Nextcloud design
  - Calendar integration (approved PTO → Nextcloud Calendar)
    - Automatic event creation on approval
    - Admin-configurable calendar selection
    - RFC 5545 compliant all-day events
    - Compatible with Nextcloud 27 and 28+

- **Security**
  - CSRF protection on all endpoints
  - Authorization checks (owner/manager/admin)
  - Input validation on all user inputs
  - PSR-12 compliant code

- **Multiple Leave Types**
  - Vacation
  - Sick leave
  - Personal days
  - Other

### 🚧 Planned Features

- Email/push notifications
- Background jobs for automatic accrual
- Reporting dashboard
- Approval history view
- Multi-language support (i18n)

## 📋 Requirements

- Nextcloud 25, 26, or 27
- PHP 8.0+
- MySQL, PostgreSQL, or SQLite

## 🚀 Installation

### Production (from App Store)
*Coming soon - pending Nextcloud App Store submission*

### Development/Testing

1. **Clone the repository:**
   ```bash
   git clone https://github.com/SR-Coder/nextcloud-pto.git
   cd nextcloud-pto
   ```

2. **Build the frontend:**
   ```bash
   npm install
   npm run build
   ```

3. **Copy to Nextcloud:**
   ```bash
   # Standard installation
   cp -r . /path/to/nextcloud/apps/pto/
   
   # Docker installation
   cp -r . /path/to/nextcloud-data/custom_apps/pto/
   ```

4. **Enable the app:**
   ```bash
   # Via occ command
   sudo -u www-data php occ app:enable pto
   
   # Or via Nextcloud web UI → Apps → PTO Tracker → Enable
   ```

5. **Set up policies:**
   - Go to Settings → Administration → PTO Management
   - Create your organization's PTO policies
   - Assign users to policies

6. **Assign managers:**
   - Go to Settings → Users
   - Edit each user → Set their manager
   - Managers will see approval queue for their team

## 📖 Usage

### For Administrators

1. **Create PTO Policies** (Settings → Administration → PTO Management)
   - Name your policy
   - Choose type: Unlimited, Accrual, or Fixed
   - Configure hours/rates as needed

2. **Assign Users to Policies**
   - Each user gets a balance per policy
   - Initial balances can be set

3. **Set Up Managers** (Settings → Users)
   - Edit user profile → Manager field
   - Managers approve requests from their team

### For Employees

1. **View Balances** (PTO Tracker → Dashboard)
   - See available hours per policy
   - View upcoming time off

2. **Submit Requests** (PTO Tracker → New Request)
   - Select policy and leave type
   - Pick start/end dates
   - Add optional notes
   - Submit for approval

3. **Track Requests** (PTO Tracker → My Requests)
   - View all submitted requests
   - Filter by status
   - Cancel pending requests

### For Managers

1. **Approve Requests** (PTO Tracker → Approvals)
   - See pending requests from team
   - Add approval/denial comments
   - Approve or deny

## 🏗️ Architecture

- **Backend**: PHP 8.0+ with Nextcloud OCP framework
- **Frontend**: Vue 3 + Vite
- **Database**: Nextcloud abstraction layer (supports MySQL, PostgreSQL, SQLite)
- **APIs**: RESTful JSON endpoints
- **Security**: CSRF tokens, authorization middleware

### Database Schema

- `pto_policies` - Policy definitions (unlimited/accrual/fixed)
- `pto_balances` - User balances per policy
- `pto_requests` - Time off requests
- `pto_approvals` - Approval history
- `pto_user_roles` - Reserved for future use

### API Endpoints

**Policies** (Admin only):
- `GET /api/v1/policies` - List all policies
- `POST /api/v1/policies` - Create policy
- `PUT /api/v1/policies/{id}` - Update policy

**Balances**:
- `GET /api/v1/balances` - Get current user's balances

**Requests**:
- `GET /api/v1/requests` - Get current user's requests
- `POST /api/v1/requests` - Create request
- `POST /api/v1/requests/{id}/approve` - Approve (managers only)
- `POST /api/v1/requests/{id}/deny` - Deny (managers only)
- `POST /api/v1/requests/{id}/cancel` - Cancel (owner only)
- `GET /api/v1/requests/pending` - Get pending approvals (managers)

## 🛡️ Security

**Implemented Protections:**
- ✅ CSRF tokens on all write operations
- ✅ Authorization checks (owner/manager/admin)
- ✅ Input validation (dates, hours, types, lengths)
- ✅ SQL injection protection (QueryBuilder)
- ✅ XSS protection (template escaping)

**Authorization Matrix:**

| Action | Owner | Manager | Admin |
|--------|-------|---------|-------|
| View own requests | ✅ | - | ✅ |
| View team requests | - | ✅ | ✅ |
| Create request | ✅ | - | - |
| Approve/deny | - | ✅ | ✅ |
| Cancel request | ✅ | - | - |
| Manage policies | - | - | ✅ |
| Assign balances | - | - | ✅ |

## 🧪 Development

See [DEVELOPMENT.md](DEVELOPMENT.md) for local development setup with Docker.

### Running Tests
```bash
# Unit tests (when implemented)
composer test

# Code style
vendor/bin/php-cs-fixer fix
```

## 📝 Changelog

### v0.1.4 (2026-03-18)
- ✅ CSRF protection implemented
- ✅ Authorization checks on all endpoints
- ✅ Input validation
- ✅ Admin settings page
- ✅ Dashboard with balance cards
- ✅ Request submission form
- ✅ Manager approval queue
- ✅ Request history
- ✅ Native Nextcloud manager integration

### v0.1.0 (2026-03-17)
- Initial database schema
- Basic policy CRUD
- Core entity/service layer

## 🤝 Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Development Workflow
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Test thoroughly
5. Create a Pull Request

### Code Standards
- PSR-12 PHP coding standard
- Vue 3 composition API preferred
- All endpoints must have authorization checks
- All inputs must be validated

## 📄 License

MIT License - see [LICENSE](LICENSE) file

## 🙏 Credits

- **Developed by:** JCR Labs
- **Built for:** Nextcloud community
- **Inspired by:** The need for simple, integrated PTO tracking

## 🔗 Links

- [GitHub Repository](https://github.com/SR-Coder/nextcloud-pto)
- [Issue Tracker](https://github.com/SR-Coder/nextcloud-pto/issues)
- [Nextcloud App Store](https://apps.nextcloud.com/) *(coming soon)*

## ⚠️ Status

**Current Version:** 0.4.3  
**Status:** Beta - Ready for testing in production-like environments  
**App Store:** Pending submission

The core features are working and secure, including calendar integration. Notifications and background jobs are planned for the next release.
