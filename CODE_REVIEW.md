# Nextcloud PTO Tracker - Code Compliance Review
**Date:** 2026-03-18  
**Version:** 0.1.3  
**Reviewer:** Glitch  
**Reference:** Nextcloud 28 Developer Documentation

---

## Executive Summary

**Overall Status:** ⚠️ **Needs Work**

**Critical Issues:** 2  
**Major Issues:** 3  
**Minor Issues:** 4  

**Recommendation:** Address critical and major issues before production deployment.

---

## 🔴 CRITICAL ISSUES

### 1. **Security: Blanket @NoCSRFRequired on All Controllers**
**File:** All controllers (`lib/Controller/*.php`)  
**Issue:** Every API endpoint has `@NoCSRFRequired` annotation  
**Risk:** CSRF attacks possible on all POST/PUT/DELETE endpoints  
**Nextcloud Standard:** Only use `@NoCSRFRequired` for public APIs or when implementing custom CSRF protection  
**Reference:** `digging_deeper/security.html`

**Current Code:**
```php
/**
 * @NoAdminRequired
 * @NoCSRFRequired  // ❌ INSECURE
 */
public function create(...) { }
```

**Required Fix:**
- Remove `@NoCSRFRequired` from all endpoints that modify data
- Implement proper CSRF token handling in Vue frontend
- OR: Implement custom CSRF middleware if needed

**Impact:** HIGH - All write operations vulnerable to CSRF attacks

---

### 2. **Security: Missing Authorization Checks**
**Files:** `RequestController.php`, `BalanceController.php`  
**Issue:** TODOs for authorization checks not implemented  
**Risk:** Users could access/modify other users' data

**Example:**
```php
public function show(int $id): DataResponse {
    $request = $this->service->find($id);
    // TODO: Check authorization  // ❌ NOT IMPLEMENTED
    return new DataResponse($request);
}
```

**Required Fix:**
- Implement authorization checks in services or controllers
- Verify user owns the resource OR is manager/admin
- Return 403 Forbidden if unauthorized

**Impact:** HIGH - Unauthorized data access possible

---

## 🟠 MAJOR ISSUES

### 3. **Code Structure: Missing Proper PSR-12 Compliance**
**Files:** Multiple PHP files  
**Issue:** Inconsistent code style, missing type hints in some places  
**Nextcloud Standard:** PSR-12 coding standard required  
**Reference:** `app_publishing_maintenance/publishing.html`

**Examples:**
- Some methods missing return type declarations
- Inconsistent spacing/indentation

**Required Fix:**
- Run `php-cs-fixer` with Nextcloud preset
- Add strict types to all files
- Ensure all methods have return types

**Impact:** MEDIUM - App store rejection risk

---

### 4. **Database: Hardcoded Table Prefixes**
**Files:** Migration file  
**Issue:** Uses hardcoded `oc_` prefix instead of dynamic prefix  
**Nextcloud Standard:** Use `$table->setName('pto_*')` without prefix  
**Reference:** `basics/storage/index.html`

**Current Code:**
```php
if (!$schema->hasTable('oc_pto_policies')) {
    $table = $schema->createTable('oc_pto_policies');
```

**Required Fix:**
```php
if (!$schema->hasTable('pto_policies')) {  // Nextcloud adds prefix automatically
    $table = $schema->createTable('pto_policies');
```

**Impact:** MEDIUM - Won't work on custom table prefix installs

---

### 5. **Frontend: Missing Proper Vue Component Registration**
**Files:** `src/main.js`, `src/admin-settings.js`  
**Issue:** Direct DOM manipulation instead of proper Nextcloud Vue integration  
**Nextcloud Standard:** Use `@nextcloud/vue` components and proper app mounting  
**Reference:** `basics/front-end/index.html`

**Required Fix:**
- Import and use `@nextcloud/vue` components
- Proper Nextcloud router integration
- Use Nextcloud design system

**Impact:** MEDIUM - UI inconsistency, accessibility issues

---

## 🟡 MINOR ISSUES

### 6. **Missing Background Jobs**
**Feature:** Balance accrual automation  
**Issue:** No cron job implementation for automatic accrual  
**Nextcloud Standard:** Use `BackgroundJob` classes  
**Reference:** `basics/backgroundjobs.html`

**Required Fix:**
- Create `lib/BackgroundJobs/AccrualJob.php`
- Register in `Application.php`
- Implement daily/monthly accrual logic

**Impact:** LOW - Manual feature, can be added later

---

### 7. **Missing Notifications Integration**
**Feature:** Email/push notifications for approvals  
**Issue:** No notification system implemented  
**Nextcloud Standard:** Use `INotificationManager`  
**Reference:** `digging_deeper/notifications.html`

**Required Fix:**
- Implement notification on request submission
- Implement notification on approval/denial
- Use Nextcloud notification center

**Impact:** LOW - Quality of life feature

---

### 8. **Missing Calendar Integration**
**Feature:** Approved PTO on calendar  
**Issue:** No DAV calendar integration  
**Nextcloud Standard:** Use CalDAV API  
**Reference:** `digging_deeper/calendar.html` (if exists)

**Required Fix:**
- Create calendar events on approval
- Sync with user's Nextcloud calendar
- Allow calendar export

**Impact:** LOW - Nice-to-have feature

---

### 9. **Missing Unit Tests**
**Files:** No `tests/` directory  
**Issue:** Zero test coverage  
**Nextcloud Standard:** Tests required for app store  
**Reference:** `basics/testing.html`

**Required Fix:**
- Create `tests/Unit/` and `tests/Integration/`
- Write tests for services, controllers
- Set up CI with PHPUnit

**Impact:** LOW - Required before app store submission

---

## ✅ COMPLIANCE AREAS

### What's Done Right:

1. ✅ **Proper App Structure**
   - Correct namespace (`OCA\PTO`)
   - Proper directory layout
   - Valid `info.xml`

2. ✅ **Dependency Injection**
   - Controllers use constructor injection
   - Services properly injected
   - No global state

3. ✅ **Database Patterns**
   - Entity, Mapper, Service pattern followed
   - Migrations in place
   - QueryBuilder usage

4. ✅ **Admin Settings**
   - Proper `ISettings` implementation
   - Registered in `info.xml`
   - Template-based rendering

5. ✅ **User Management Integration**
   - Uses `IUserManager` for native manager relationships
   - Proper user session handling

6. ✅ **Routing**
   - Routes properly defined in `appinfo/routes.php`
   - RESTful endpoint structure

7. ✅ **Frontend Build**
   - Vite build system
   - Vue 3 integration
   - IIFE format for compatibility

---

## PRIORITY FIXES (Before Production)

### Must Fix (Critical):
1. **Remove `@NoCSRFRequired`** from all write endpoints
2. **Implement authorization checks** on all data access methods
3. **Add CSRF token handling** in frontend

### Should Fix (Major):
4. **Fix table prefix** in migrations
5. **Run PHP-CS-Fixer** for PSR-12 compliance
6. **Add proper error handling** for all API endpoints

### Nice to Have (Minor):
7. Add unit tests
8. Implement notifications
9. Add background jobs for accrual
10. Calendar integration

---

## SECURITY AUDIT

### Current Security Posture: ⚠️ **VULNERABLE**

**Vulnerabilities:**
- ✅ SQL Injection: PROTECTED (using QueryBuilder)
- ❌ CSRF: VULNERABLE (no CSRF protection)
- ❌ Authorization: VULNERABLE (missing checks)
- ✅ XSS: PROTECTED (using templates properly)
- ✅ Authentication: PROTECTED (using IUserSession)

**Required Actions:**
1. Enable CSRF protection immediately
2. Add authorization middleware
3. Implement rate limiting on API endpoints
4. Add input validation on all endpoints
5. Implement audit logging for sensitive operations

---

## APP STORE READINESS

**Current Status:** ❌ **NOT READY**

**Blockers:**
- [ ] Security issues (CSRF, authorization)
- [ ] Code style compliance (PSR-12)
- [ ] Unit tests required
- [ ] Screenshots needed
- [ ] Code signing required

**Estimated Work:** 8-12 hours to address all blockers

---

## RECOMMENDATIONS

### Immediate Actions (Today):
1. Create feature branch `security/csrf-and-authorization`
2. Remove `@NoCSRFRequired` from write endpoints
3. Implement CSRF token passing in Vue frontend
4. Add authorization checks to all service methods

### Short Term (This Week):
5. Run PHP-CS-Fixer for code style
6. Fix table prefix in migrations
7. Add input validation to all endpoints
8. Write unit tests for critical paths

### Medium Term (Before Production):
9. Implement notifications
10. Add background jobs
11. Calendar integration
12. Prepare for app store submission

---

## CONCLUSION

The PTO Tracker has a **solid foundation** with proper Nextcloud patterns for:
- Database access
- Dependency injection  
- Admin settings
- User management

However, **critical security issues** must be addressed before production:
- CSRF protection needed
- Authorization checks missing

**Next Steps:**
1. Fix security issues (CSRF + authorization)
2. Code style compliance
3. Add tests
4. Feature completion (notifications, calendar, background jobs)

**Timeline to Production-Ready:** 1-2 weeks of focused work

---

**Reviewed by:** Glitch  
**Date:** 2026-03-18  
**Status:** Ready for remediation work
