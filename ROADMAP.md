# Nextcloud PTO Tracker - Roadmap

## v1.2.0 (Future Release)

### Manager Override for Balance Requests
**Status:** Proposed

**Problem:** Users cannot submit PTO requests that exceed their available balance, even for legitimate cases (unpaid leave, advance on next year PTO, special circumstances).

**Solution:** Manager override on approval
- Users can submit requests over balance
- Warning shown to user: "This exceeds your available balance"
- Manager sees warning when approving with required override checkbox
- Audit trail logs who overrode and reason

**Implementation tasks:**
1. Remove balance check from request submission (move to warning only)
2. Add override checkbox to approval UI
3. Update approval endpoint to accept override flag
4. Log overrides in request history
5. Add tests for override scenarios

**Estimate:** 2-3 hours

---

## v1.1.0 (Current Release)

**Features:**
- NC32 compatibility (min-version: 32, max-version: 33)
- Better error messages (show actual validation errors)
- Fixed permission check (no more 403 errors for regular users)

**Release date:** 2026-03-26
