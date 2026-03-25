# Nextcloud App Store Submission Checklist

## Status: Ready for Submission Prep

### ✅ Already Complete
- [x] App is functional
- [x] info.xml with required fields (id, name, description, version, license, author, category, bugs, dependencies)
- [x] MIT license
- [x] Code follows Nextcloud framework patterns
- [x] No CSRF vulnerabilities
- [x] Proper authorization checks

### 📋 Still Needed for Submission

#### 1. Screenshots (REQUIRED)
**Need 3-5 screenshots hosted on HTTPS**

Recommended shots:
- [ ] Dashboard view (balance cards)
- [ ] New Request form
- [ ] My Requests list
- [ ] Approval Queue (manager view)
- [ ] Admin Settings (policy management)

**Action:** Take screenshots, upload to GitHub repo or image host, get HTTPS URLs

#### 2. Update info.xml
- [ ] Add `<repository>` element with GitHub URL
- [ ] Add `<screenshot>` elements (3-5 HTTPS URLs)
- [ ] Optional: Add `<website>` (if you have a landing page)

#### 3. Create CHANGELOG.md
Format: [Keep a CHANGELOG](http://keepachangelog.com)

```markdown
## 0.3.4 - 2026-03-18
### Added
- PTO request submission and tracking
- Manager approval workflow
- Balance display by policy
- Admin policy management
- Native Nextcloud manager integration
- Request history with filtering

### Fixed
- Proper Nextcloud framework integration
- Vue 3 compatibility (removed $set)
- Self-approval prevention
```

#### 4. Generate Certificate

```bash
# Create certificates directory
mkdir -p ~/.nextcloud/certificates/

# Generate private key and CSR
cd ~/.nextcloud/certificates/
openssl req -nodes -newkey rsa:4096 -keyout pto.key -out pto.csr -subj "/CN=pto"

# Submit CSR via PR to:
# https://github.com/nextcloud/app-certificate-requests

# After approval, save signed certificate as pto.crt
```

#### 5. Register App on App Store

After getting signed certificate:

```bash
# Generate signature for app registration
echo -n "pto" | openssl dgst -sha512 -sign ~/.nextcloud/certificates/pto.key | openssl base64
```

Register at: https://apps.nextcloud.com/developer/apps/new
- Paste certificate contents (pto.crt)
- Paste signature from above command

#### 6. Create Release Archive

```bash
# From project root
cd /Users/reederje/.openclaw/workspace/projects/nextcloud-pto

# Create archive (must contain only 'pto' as top-level folder)
tar -czf pto-0.3.4.tar.gz \
  --exclude='.git*' \
  --exclude='node_modules' \
  --exclude='src' \
  --exclude='package*.json' \
  --exclude='vite.config.js' \
  --exclude='.DS_Store' \
  --transform 's,^,pto/,' \
  appinfo lib templates js css img COPYING README.md CHANGELOG.md

# Sign the archive
openssl dgst -sha512 -sign ~/.nextcloud/certificates/pto.key pto-0.3.4.tar.gz | openssl base64
```

#### 7. Upload Release to App Store

Upload at: https://apps.nextcloud.com/developer/apps/releases/new
- Download URL (host archive on GitHub releases or other HTTPS location)
- Signature (from step 6)

## Timeline Estimate

- **Screenshots:** 15 minutes
- **Update info.xml:** 10 minutes  
- **Create CHANGELOG.md:** 5 minutes
- **Certificate process:** 1-3 days (waiting for approval)
- **Create & upload release:** 15 minutes
- **App Store review:** 1-2 weeks

**Total:** ~2-3 weeks to published

## Next Steps (In Order)

1. Take screenshots of the 5 key views
2. Host screenshots (GitHub repo or imgur/similar)
3. Create CHANGELOG.md
4. Update info.xml with screenshots and repository
5. Generate certificate CSR
6. Submit CSR PR to Nextcloud
7. Wait for certificate approval
8. Register app
9. Create release archive
10. Host archive (GitHub Releases recommended)
11. Upload release to App Store

## Resources

- App Store Docs: https://nextcloudappstore.readthedocs.io/en/latest/developer.html
- Certificate Requests: https://github.com/nextcloud/app-certificate-requests
- Register App: https://apps.nextcloud.com/developer/apps/new
- Upload Release: https://apps.nextcloud.com/developer/apps/releases/new
