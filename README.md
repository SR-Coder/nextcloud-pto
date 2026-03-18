# Nextcloud PTO Tracker

A comprehensive PTO (Paid Time Off) and vacation tracking application for Nextcloud.

## Features

- **Flexible PTO Policies**: Support for unlimited, accrual-based, and fixed annual leave
- **Request/Approval Workflow**: Manager approval system with notifications
- **Multiple Leave Types**: Vacation, sick leave, personal days, etc.
- **Calendar Integration**: Approved time off automatically syncs to Nextcloud Calendar
- **Reporting**: Track team usage, balances, and history
- **Supervisor Delegation**: Managers can submit requests on behalf of team members
- **Talk Integration** (optional): Auto-set status in Nextcloud Talk during leave

## Development Status

🚧 **Early Development** - Not ready for production use

## Requirements

- Nextcloud 25, 26, or 27
- PHP 8.0+
- MySQL, PostgreSQL, or SQLite

## Installation

*Installation instructions coming soon*

## Architecture

- **Backend**: PHP with Nextcloud OCP framework
- **Frontend**: Vue.js
- **Database**: Nextcloud database abstraction layer
- **APIs**: RESTful endpoints for all operations

## License

MIT License - see LICENSE file

## Contributing

Contributions welcome! This is an open-source project.

## Roadmap

- [ ] Core database schema
- [ ] User role management (admin, manager, employee)
- [ ] Request submission and approval workflow
- [ ] PTO policy configuration UI
- [ ] Calendar integration (CalDAV)
- [ ] Email notifications
- [ ] Reporting dashboard
- [ ] Nextcloud Talk status integration
- [ ] Accrual calculation background jobs
- [ ] Multi-language support (i18n)
- [ ] Nextcloud App Store submission

## Credits

Developed by JCR Labs
