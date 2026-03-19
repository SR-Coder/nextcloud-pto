# PTO Tracker Security & Compliance Fix Plan

**Created:** 2026-03-18  
**Status:** Ready to Execute  
**Total Estimated Time:** 8-10 hours

---

## Phase 1: Critical Security Fixes (MUST DO FIRST)

### PR #1: Implement CSRF Protection
**Branch:** `security/csrf-protection`  
**Priority:** 🔴 CRITICAL  
**Time:** 2-3 hours  
**Blockers:** None

**Changes:**
1. Remove `@NoCSRFRequired` from all write endpoints:
   - `RequestController`: create, approve, deny, cancel
   - `BalanceController`: processAccrual, assignPolicy
   - `PolicyController`: create, update, delete, toggleEnabled
   - `UserController`: assignManager (keep for GET endpoints)

2. Update Vue frontend to send CSRF tokens:
   - Install `@nextcloud/axios` or use native fetch with token
   - Get token from `OC.requestToken`
   - Add to all POST/PUT/DELETE requests

3. Test each endpoint:
   - Verify CSRF token validation works
   - Verify legitimate requests succeed
   - Verify missing token requests fail with 412

**Testing Checklist:**
- [ ] Create policy with valid token → SUCCESS
- [ ] Create policy without token → 412 FAIL
- [ ] Submit PTO request with valid token → SUCCESS
- [ ] Approve request with valid token → SUCCESS
- [ ] All frontend forms still work

**Success Criteria:** All write operations require valid CSRF tokens

---

### PR #2: Implement Authorization Checks
**Branch:** `security/authorization-checks`  
**Priority:** 🔴 CRITICAL  
**Time:** 2-3 hours  
**Blockers:** None (can work in parallel with PR #1)

**Changes:**

1. **Create Authorization Middleware/Service Layer:**
   ```php
   // lib/Middleware/AuthorizationMiddleware.php
   class AuthorizationMiddleware extends Middleware {
       public function beforeController($controller, $methodName) {
           // Check if user owns resource
           // Check if user is manager/admin
       }
   }
   ```

2. **Add checks to Service layer:**
   - `RequestService::find()` - verify user owns request OR is approver
   - `BalanceService::getBalance()` - verify user owns balance OR is admin
   - `RequestService::approveRequest()` - verify user is manager of requester
   - `RequestService::cancelRequest()` - verify user owns request

3. **Implement permission helpers:**
   ```php
   private function canViewRequest(Request $request, string $userId): bool {
       return $request->getUserId() === $userId 
           || $this->authService->canApproveFor($userId, $request->getUserId())
           || $this->authService->isAdmin($userId);
   }
   ```

**Testing Checklist:**
- [ ] User can view own requests → SUCCESS
- [ ] User cannot view others' requests → 403
- [ ] Manager can view team requests → SUCCESS
- [ ] Admin can view all requests → SUCCESS
- [ ] User can only cancel own requests → 403 for others

**Success Criteria:** All data access properly authorized

---

## Phase 2: Code Quality & Standards (SHOULD DO)

### PR #3: PSR-12 Code Style Compliance
**Branch:** `quality/psr12-compliance`  
**Priority:** 🟠 MAJOR  
**Time:** 1 hour  
**Blockers:** None

**Changes:**
1. Install PHP-CS-Fixer:
   ```bash
   composer require --dev friendsofphp/php-cs-fixer
   ```

2. Create `.php-cs-fixer.php` config:
   ```php
   return (new PhpCsFixer\Config())
       ->setRules([
           '@PSR12' => true,
           'strict_param' => true,
           'array_syntax' => ['syntax' => 'short'],
       ]);
   ```

3. Run fixer:
   ```bash
   vendor/bin/php-cs-fixer fix lib/
   vendor/bin/php-cs-fixer fix tests/
   ```

4. Add to CI pipeline

**Testing Checklist:**
- [ ] All PHP files pass PSR-12 validation
- [ ] No functionality broken by style changes
- [ ] CI passes

**Success Criteria:** Clean PSR-12 compliance report

---

### PR #4: Fix Table Prefix Handling
**Branch:** `fix/table-prefix`  
**Priority:** 🟠 MAJOR  
**Time:** 30 minutes  
**Blockers:** None

**Changes:**
1. Update migration file:
   ```php
   // BEFORE:
   if (!$schema->hasTable('oc_pto_policies')) {
       $table = $schema->createTable('oc_pto_policies');
   
   // AFTER:
   if (!$schema->hasTable('pto_policies')) {
       $table = $schema->createTable('pto_policies');
   ```

2. Update all 5 tables in migration

3. Test on fresh install with custom prefix

**Testing Checklist:**
- [ ] Fresh install works
- [ ] Custom table prefix install works
- [ ] All queries still work

**Success Criteria:** Works with any table prefix

---

### PR #5: Input Validation & Error Handling
**Branch:** `quality/input-validation`  
**Priority:** 🟠 MAJOR  
**Time:** 1-2 hours  
**Blockers:** None

**Changes:**
1. Add validation to all controller methods:
   ```php
   public function create(int $policyId, string $leaveType, ...): DataResponse {
       // Validate inputs
       if (empty($leaveType) || !in_array($leaveType, ['vacation', 'sick', 'personal', 'other'])) {
           return new DataResponse(['error' => 'Invalid leave type'], Http::STATUS_BAD_REQUEST);
       }
       
       if ($hours <= 0 || $hours > 2000) {
           return new DataResponse(['error' => 'Invalid hours'], Http::STATUS_BAD_REQUEST);
       }
       
       // ... existing logic
   }
   ```

2. Add try-catch blocks with proper error responses
3. Add logging for errors
4. Validate date formats, ranges, etc.

**Testing Checklist:**
- [ ] Invalid leave type → 400 with error message
- [ ] Negative hours → 400 with error message
- [ ] Invalid date range → 400 with error message
- [ ] Valid inputs → 200 SUCCESS

**Success Criteria:** All endpoints validate input properly

---

## Phase 3: Testing Infrastructure (SHOULD DO)

### PR #6: Unit Test Framework
**Branch:** `tests/unit-framework`  
**Priority:** 🟡 MINOR  
**Time:** 2 hours  
**Blockers:** None

**Changes:**
1. Create `tests/` structure:
   ```
   tests/
     Unit/
       Service/
         RequestServiceTest.php
         BalanceServiceTest.php
         PolicyServiceTest.php
       Controller/
         RequestControllerTest.php
     Integration/
       DatabaseTest.php
   ```

2. Install PHPUnit:
   ```bash
   composer require --dev phpunit/phpunit
   ```

3. Create `phpunit.xml`:
   ```xml
   <phpunit bootstrap="tests/bootstrap.php">
       <testsuites>
           <testsuite name="Unit">
               <directory>tests/Unit</directory>
           </testsuite>
       </testsuites>
   </phpunit>
   ```

4. Write initial tests:
   - Service layer tests (business logic)
   - Controller tests (API endpoints)
   - Database mapper tests

**Testing Checklist:**
- [ ] PHPUnit runs successfully
- [ ] At least 50% code coverage
- [ ] All critical paths tested

**Success Criteria:** Green test suite with >50% coverage

---

## Phase 4: Feature Completion (NICE TO HAVE)

### PR #7: Notification System
**Branch:** `feature/notifications`  
**Priority:** 🟡 MINOR  
**Time:** 2-3 hours  
**Blockers:** None

**Changes:**
1. Create notification provider:
   ```php
   // lib/Notification/Notifier.php
   class Notifier implements INotifier {
       public function prepare(INotification $notification, string $languageCode): INotification {
           // Format notification for display
       }
   }
   ```

2. Send notifications on:
   - Request submission → notify managers
   - Request approval → notify requester
   - Request denial → notify requester

3. Register in `Application.php`

**Testing Checklist:**
- [ ] Submit request → manager receives notification
- [ ] Approve request → user receives notification
- [ ] Notification appears in Nextcloud notification center

**Success Criteria:** Working notification system

---

### PR #8: Background Jobs (Accrual)
**Branch:** `feature/background-jobs`  
**Priority:** 🟡 MINOR  
**Time:** 2 hours  
**Blockers:** None

**Changes:**
1. Create accrual job:
   ```php
   // lib/BackgroundJob/AccrualJob.php
   class AccrualJob extends TimedJob {
       public function run($argument) {
           // Process accrual for all active policies
       }
   }
   ```

2. Register in `Application.php`:
   ```php
   $context->registerService('AccrualJob', function($c) {
       return $c->query(AccrualJob::class);
   });
   ```

3. Implement accrual logic in `BalanceService`

**Testing Checklist:**
- [ ] Cron runs successfully
- [ ] Accrual policies get proper hours added
- [ ] Logs show successful processing

**Success Criteria:** Automated accrual working

---

## Execution Order

### Week 1 (Critical):
**Day 1-2:**
1. ✅ PR #1: CSRF Protection (MUST)
2. ✅ PR #2: Authorization Checks (MUST)

**Day 3:**
3. ✅ PR #3: PSR-12 Compliance (SHOULD)
4. ✅ PR #4: Table Prefix Fix (SHOULD)

**Day 4:**
5. ✅ PR #5: Input Validation (SHOULD)

### Week 2 (Quality):
**Day 5-6:**
6. ✅ PR #6: Unit Tests (SHOULD)

**Day 7 (Optional):**
7. 🔄 PR #7: Notifications (NICE)
8. 🔄 PR #8: Background Jobs (NICE)

---

## Testing Strategy

### After Each PR:
1. **Automated:** Run PHPUnit tests
2. **Manual:** Test affected features in browser
3. **Integration:** Full workflow test (create policy → request → approve)

### Before Merge:
1. Code review checklist
2. Security audit of changes
3. Breaking change assessment
4. Documentation updates

### Full Test Matrix (After All PRs):
- [ ] Create PTO policy as admin → SUCCESS
- [ ] Assign user to policy → SUCCESS
- [ ] User submits PTO request → SUCCESS
- [ ] Manager receives notification → SUCCESS
- [ ] Manager approves request → SUCCESS
- [ ] User receives approval notification → SUCCESS
- [ ] Request appears on user dashboard → SUCCESS
- [ ] Unauthorized access attempts → 403 FAIL
- [ ] CSRF attacks → 412 FAIL
- [ ] SQL injection attempts → PROTECTED

---

## Success Metrics

### Phase 1 (Critical):
- ✅ Zero CSRF vulnerabilities
- ✅ Zero authorization bypasses
- ✅ Security audit passes

### Phase 2 (Quality):
- ✅ PSR-12 compliant
- ✅ Works with any table prefix
- ✅ Input validation on all endpoints

### Phase 3 (Testing):
- ✅ >50% test coverage
- ✅ All critical paths tested
- ✅ CI pipeline green

### Phase 4 (Features):
- ✅ Notifications working
- ✅ Background jobs running
- ✅ Calendar integration (future)

---

## Rollback Plan

If any PR causes issues:
1. Revert PR immediately
2. Document failure reason
3. Fix in new branch
4. Re-test before merge

---

**Ready to Execute:** YES  
**First PR:** security/csrf-protection  
**Start Date:** 2026-03-18  
**Target Completion:** 2026-03-25
