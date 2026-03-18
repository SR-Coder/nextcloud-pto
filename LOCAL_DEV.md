# Local Development Environment

## Quick Start

```bash
# Start Nextcloud dev instance (first time takes ~2 min)
docker compose -f docker-compose.dev.yml up -d

# Wait for Nextcloud to initialize
# Watch logs: docker compose -f docker-compose.dev.yml logs -f nextcloud

# Once ready, access at: http://localhost:8080
# Login: admin / admin

# Enable the PTO app
docker compose -f docker-compose.dev.yml exec nextcloud php occ app:enable pto

# Run database migrations
docker compose -f docker-compose.dev.yml exec nextcloud php occ migrations:execute pto Version000100Date20260317000000

# Build frontend
npm install
npm run build

# Restart Nextcloud to pick up changes
docker compose -f docker-compose.dev.yml restart nextcloud
```

## What This Sets Up

- **Nextcloud 27** on http://localhost:8080
- **MariaDB 10.11** database
- **Auto-mounts** this directory as `/var/www/html/custom_apps/pto`
- **Default admin:** username `admin`, password `admin`

## Development Workflow

### Backend Changes (PHP)
```bash
# Edit files in lib/
# Changes are live-mounted, just refresh browser
# For new migrations:
docker compose -f docker-compose.dev.yml exec nextcloud php occ migrations:execute pto <version>
```

### Frontend Changes (Vue/JS)
```bash
# Run in watch mode
npm run watch

# Or rebuild once
npm run build

# Restart Nextcloud to pick up new assets
docker compose -f docker-compose.dev.yml restart nextcloud
```

### View Logs
```bash
# Nextcloud logs
docker compose -f docker-compose.dev.yml logs -f nextcloud

# Database logs
docker compose -f docker-compose.dev.yml logs -f db
```

### Run Tests
```bash
# Backend (from host)
composer test

# Frontend (from host)
npm test
```

### Access Database
```bash
docker compose -f docker-compose.dev.yml exec db mysql -u nextcloud -pnextcloud nextcloud
```

## Stopping/Cleaning Up

```bash
# Stop (keeps data)
docker compose -f docker-compose.dev.yml down

# Stop and wipe all data (fresh start)
docker compose -f docker-compose.dev.yml down -v
```

## Troubleshooting

### App not showing up
```bash
# Verify mount
docker compose -f docker-compose.dev.yml exec nextcloud ls -la /var/www/html/custom_apps/pto

# Check Nextcloud logs
docker compose -f docker-compose.dev.yml logs nextcloud | tail -50
```

### Permission errors
```bash
# Fix permissions
docker compose -f docker-compose.dev.yml exec nextcloud chown -R www-data:www-data /var/www/html/custom_apps/pto
```

### Database connection failed
```bash
# Restart database
docker compose -f docker-compose.dev.yml restart db

# Wait 10 seconds, then restart Nextcloud
docker compose -f docker-compose.dev.yml restart nextcloud
```

### Frontend not updating
```bash
# Clear Nextcloud cache
docker compose -f docker-compose.dev.yml exec nextcloud php occ maintenance:repair

# Rebuild frontend
npm run build

# Hard restart
docker compose -f docker-compose.dev.yml down
docker compose -f docker-compose.dev.yml up -d
```

## URLs

- **Nextcloud:** http://localhost:8080
- **PTO App:** http://localhost:8080/index.php/apps/pto/
- **API Docs:** http://localhost:8080/index.php/apps/pto/api/v1/

## First Time Setup Checklist

1. ✅ Start containers: `docker compose -f docker-compose.dev.yml up -d`
2. ✅ Wait for Nextcloud (watch logs)
3. ✅ Login at http://localhost:8080 (admin/admin)
4. ✅ Enable app: `docker compose -f docker-compose.dev.yml exec nextcloud php occ app:enable pto`
5. ✅ Run migrations: `docker compose -f docker-compose.dev.yml exec nextcloud php occ migrations:execute pto Version000100Date20260317000000`
6. ✅ Install frontend deps: `npm install`
7. ✅ Build frontend: `npm run build`
8. ✅ Access app in browser

## Tips

- Use `npm run watch` during frontend development for auto-rebuild
- Backend changes are instant (no rebuild needed)
- Use Nextcloud's built-in debugger: add `'debug' => true,` to `config/config.php`
- Check PHP errors in Nextcloud logs, not browser console
