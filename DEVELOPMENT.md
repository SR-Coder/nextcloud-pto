# Development Guide

## Quick Start

### Prerequisites

- Nextcloud 25, 26, or 27
- PHP 8.0+
- Node.js 16+ and npm
- Docker (for local development)

### Local Development Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/SR-Coder/nextcloud-pto.git
   cd nextcloud-pto
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Start development environment:**
   ```bash
   # See LOCAL_DEV.md for Docker setup
   docker compose up -d
   ```

4. **Build frontend (development):**
   ```bash
   npm run dev
   ```

5. **Access the app:**
   - Nextcloud: http://localhost:8080
   - Login: admin / admin
   - Enable app: Settings → Apps → PTO Tracker

### Frontend Development

**Build Scripts:**
```bash
# Development build (with source maps)
npm run dev

# Production build
npm run build

# Watch mode (rebuilds on change)
npm run watch
```

**Build System:**
- **Bundler:** Vite 5.x
- **Framework:** Vue 3 (Composition API)
- **Output:** IIFE format for Nextcloud compatibility
- **Entry Points:**
  - `src/main.js` → `js/pto-main.js` (main app)
  - `src/admin-settings.js` → `js/pto-admin-settings.js` (admin panel)

**Adding a New Component:**
1. Create component in `src/components/` or `src/views/`
2. Import in relevant entry point
3. Add route if needed (in `src/main.js`)
4. Run `npm run build`
5. Refresh Nextcloud (hard refresh: Cmd+Shift+R)

### Backend Development

**Architecture:**

```
lib/
├── AppInfo/
│   └── Application.php      # App bootstrap
├── Controller/
│   ├── RequestController.php
│   ├── PolicyController.php
│   └── BalanceController.php
├── Db/
│   ├── Request.php           # Entity
│   └── RequestMapper.php     # Data access
├── Service/
│   ├── RequestService.php    # Business logic
│   ├── AuthorizationService.php
│   └── PolicyService.php
├── Migration/
│   └── Version*.php          # Database migrations
└── Settings/
    ├── AdminSection.php
    └── AdminSettings.php
```

**Adding a New Endpoint:**

1. **Add route** in `appinfo/routes.php`:
   ```php
   ['name' => 'request#myAction', 'url' => '/api/v1/requests/custom', 'verb' => 'POST'],
   ```

2. **Implement controller method:**
   ```php
   /**
    * @NoAdminRequired
    */
   public function myAction(string $param): DataResponse {
       // Validate input
       if (empty($param)) {
           return new DataResponse(['error' => 'Param required'], Http::STATUS_BAD_REQUEST);
       }
       
       // Check authorization
       if (!$this->authService->canDoThing($this->getUserId())) {
           return new DataResponse(['error' => 'Unauthorized'], Http::STATUS_FORBIDDEN);
       }
       
       // Business logic
       $result = $this->service->doThing($param);
       
       return new DataResponse($result);
   }
   ```

3. **Add service method** if needed:
   ```php
   public function doThing(string $param): array {
       // Implement business logic
       // Use mapper for database access
       return $result;
   }
   ```

4. **Test the endpoint:**
   ```bash
   curl -X POST http://localhost:8080/index.php/apps/pto/api/v1/requests/custom \
     -H "requesttoken: YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"param": "value"}'
   ```

### Database Changes

**Creating a Migration:**

1. **Create migration file:**
   ```bash
   # Name: Version{YYYYMMDD}Date{HHMMSS}.php
   # Example: Version20260318Date143000.php
   touch lib/Migration/Version20260318Date143000.php
   ```

2. **Implement schema changes:**
   ```php
   public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
       $schema = $schemaClosure();
       
       if (!$schema->hasTable('pto_new_table')) {
           $table = $schema->createTable('pto_new_table');
           $table->addColumn('id', Types::BIGINT, [
               'autoincrement' => true,
               'notnull' => true,
           ]);
           $table->setPrimaryKey(['id']);
       }
       
       return $schema;
   }
   ```

3. **Run migration:**
   ```bash
   php occ app:disable pto
   php occ app:enable pto
   ```

**Current Schema:**

- `pto_policies` - Policy definitions
- `pto_balances` - User balance tracking
- `pto_requests` - Time off requests
- `pto_approvals` - Approval history
- `pto_user_roles` - Reserved for future use

### Security Checklist

**For Every New Endpoint:**

- [ ] Input validation (type, length, format, range)
- [ ] Authorization check (owner/manager/admin)
- [ ] CSRF protection (remove `@NoCSRFRequired` for writes)
- [ ] SQL injection protection (use QueryBuilder, never raw SQL)
- [ ] XSS protection (use template escaping)
- [ ] Error handling (don't leak sensitive info)

**Example Validation Pattern:**
```php
// Validate required field
if (empty($data['field'])) {
    return new DataResponse(['error' => 'Field required'], 400);
}

// Validate type/enum
if (!in_array($data['type'], ['a', 'b', 'c'], true)) {
    return new DataResponse(['error' => 'Invalid type'], 400);
}

// Validate length
if (strlen($data['text']) > 1000) {
    return new DataResponse(['error' => 'Text too long'], 400);
}

// Validate date
try {
    $date = new \DateTime($data['date']);
} catch (\Exception $e) {
    return new DataResponse(['error' => 'Invalid date'], 400);
}
```

### Testing

**Manual Testing Workflow:**

1. Create a test policy (Settings → Admin → PTO Management)
2. Assign policy to test user
3. Submit a request (Dashboard → New Request)
4. Approve as manager (Approvals tab)
5. Verify request appears in history (My Requests)

**Frontend Testing:**
- Open browser console (F12)
- Check for JavaScript errors
- Verify API calls return expected data
- Test form validation

**Backend Testing:**
- Check `nextcloud.log` for PHP errors
- Monitor database queries
- Test authorization (try as different users)

### Deployment

**Building for Production:**

1. **Clean build:**
   ```bash
   rm -rf js/ css/
   npm run build
   ```

2. **Verify build:**
   ```bash
   ls -lh js/
   # Should see pto-main.js and pto-admin-settings.js
   ```

3. **Deploy to server:**
   ```bash
   # Example: rsync to production server
   rsync -av --exclude 'node_modules' --exclude '.git' \
     . user@server:/path/to/nextcloud/apps/pto/
   ```

4. **Update on server:**
   ```bash
   ssh user@server
   cd /path/to/nextcloud
   sudo -u www-data php occ app:disable pto
   sudo -u www-data php occ app:enable pto
   sudo -u www-data php occ maintenance:mode --off
   ```

### Code Style

**PHP (PSR-12):**
- `declare(strict_types=1);` at top of every file
- Type declarations on all properties and returns
- 4 spaces indentation
- Opening braces on same line

**Vue/JavaScript:**
- Composition API preferred
- 2 spaces indentation
- Single quotes for strings
- Semicolons required

**Naming Conventions:**
- Classes: `PascalCase`
- Methods: `camelCase`
- Constants: `UPPER_SNAKE_CASE`
- Database tables: `snake_case` (without `oc_` prefix)

### Common Issues

**1. "412 Precondition Failed"**
- **Cause:** Missing CSRF token
- **Fix:** Ensure `src/api.js` is sending `requesttoken` header

**2. "404 Not Found" on API**
- **Cause:** Route not registered or app not enabled
- **Fix:** Check `appinfo/routes.php`, run `occ app:enable pto`

**3. "Blank page / white screen"**
- **Cause:** JavaScript error or cache issue
- **Fix:** Hard refresh (Cmd+Shift+R), check browser console

**4. "Changes not appearing"**
- **Cause:** Nextcloud asset caching
- **Fix:** Bump version in `appinfo/info.xml`, rebuild frontend

**5. "Table not found"**
- **Cause:** Migration not run
- **Fix:** `occ app:disable pto && occ app:enable pto`

### Useful Commands

```bash
# Enable/disable app
php occ app:enable pto
php occ app:disable pto

# Check app status
php occ app:list | grep pto

# View logs
tail -f /path/to/nextcloud/data/nextcloud.log

# Clear cache
php occ maintenance:repair --include-expensive

# List routes
php occ route:list | grep pto
```

### Resources

- [Nextcloud Developer Docs](https://docs.nextcloud.com/server/latest/developer_manual/)
- [Vue 3 Documentation](https://vuejs.org/)
- [Vite Documentation](https://vitejs.dev/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)

### Getting Help

- **Issues:** https://github.com/SR-Coder/nextcloud-pto/issues
- **Nextcloud Community:** https://help.nextcloud.com/
- **Developer Chat:** https://cloud.nextcloud.com/call/nvcuafrx

---

**Happy coding!** 🚀
