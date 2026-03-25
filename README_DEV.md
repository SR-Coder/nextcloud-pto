# Developer Setup

## Prerequisites

- Nextcloud 25, 26, or 27 development instance
- PHP 8.0+
- Node.js 18+ and npm
- Composer (for PHP dependencies)

## Installation

1. **Clone into Nextcloud apps directory**
   ```bash
   cd /path/to/nextcloud/apps
   git clone https://github.com/SR-Coder/nextcloud-pto.git pto
   cd pto
   ```

2. **Install frontend dependencies**
   ```bash
   npm install
   ```

3. **Build frontend assets**
   ```bash
   # Development build with watch
   npm run watch

   # Or one-time production build
   npm run build
   ```

4. **Enable the app in Nextcloud**
   ```bash
   # Via CLI
   php occ app:enable pto

   # Or via Nextcloud web UI: Apps > PTO Tracker > Enable
   ```

5. **Run database migrations**
   ```bash
   php occ migrations:execute pto Version000100Date20260317000000
   ```

## Development Workflow

### Backend (PHP)

- **Controllers**: `lib/Controller/` - Handle HTTP requests
- **Services**: `lib/Service/` - Business logic
- **Database**: `lib/Db/` - Entities and Mappers
- **Migrations**: `lib/Migration/` - Database schema changes

Run Nextcloud in debug mode:
```bash
php -S localhost:8080 -t /path/to/nextcloud
```

### Frontend (Vue.js)

- **Entry point**: `src/main.js`
- **Views**: `src/views/` - Page components
- **Components**: `src/components/` - Reusable UI elements

Development with hot reload:
```bash
npm run dev
```

Build for production:
```bash
npm run build
```

### Testing Endpoints

```bash
# Get policies
curl -u admin:password http://localhost:8080/index.php/apps/pto/api/v1/policies

# Create request
curl -X POST -u admin:password \
  -H "Content-Type: application/json" \
  -d '{"policyId":1,"leaveType":"vacation","startDate":"2026-04-01","endDate":"2026-04-05","hours":40}' \
  http://localhost:8080/index.php/apps/pto/api/v1/requests
```

## Project Structure

```
pto/
├── appinfo/
│   ├── info.xml              # App metadata
│   └── routes.php            # API routes
├── lib/
│   ├── AppInfo/
│   │   └── Application.php   # App bootstrap
│   ├── Controller/           # HTTP controllers
│   ├── Db/                   # Entities & Mappers
│   ├── Migration/            # Database migrations
│   └── Service/              # Business logic
├── src/
│   ├── components/           # Vue components
│   ├── views/                # Page components
│   ├── App.vue               # Root component
│   └── main.js               # Entry point
├── templates/
│   └── main.php              # Main HTML template
├── package.json              # Frontend dependencies
├── vite.config.js            # Build configuration
└── DEVELOPMENT.md            # Architecture notes
```

## Next Steps for Development

1. Implement Calendar integration (CalDAV)
2. Add email notifications
3. Build out Vue components with real API calls
4. Add background job for accrual processing
5. Implement admin panel for policy management
6. Add Nextcloud Talk status integration
7. Write unit tests (PHPUnit)
8. Add i18n support
9. Create screenshots for App Store
10. Submit to Nextcloud App Store

## Useful Commands

```bash
# Check Nextcloud logs
tail -f /path/to/nextcloud/data/nextcloud.log

# Clear Nextcloud cache
php occ maintenance:repair

# Run database migrations
php occ migrations:status pto
php occ migrations:execute pto <version>

# Disable/enable app
php occ app:disable pto
php occ app:enable pto

# Frontend linting
npm run lint
npm run lint:fix
```

## Resources

- [Nextcloud Developer Docs](https://docs.nextcloud.com/server/latest/developer_manual/)
- [Nextcloud Vue Components](https://nextcloud-vue-components.netlify.app/)
- [Vue.js Guide](https://vuejs.org/guide/)
