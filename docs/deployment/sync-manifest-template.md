# Sync Manifest Template

Folosește acest template pentru fiecare sincronizare importantă între:

- `HOME-LOCAL`
- `OFFICE-LOCAL`
- `STAGING`

```md
# Sync Manifest

- Sync ID:
- Date:
- Source Environment:
- Target Environment:
- Trigger:

## Code

- Git Branch:
- Git Commit SHA:
- Git Commit Message:
- Working Tree Clean: yes/no

## Database

- DB Export Required: yes/no
- DB Export File:
- DB Export Source:
- DB Export Timestamp:
- DB Import Required: yes/no
- DB Import Target:

## Deploy

- Code Deploy Required: yes/no
- Deploy Target:
- Deploy Method:
- Cache Clear Required: yes/no
- Permalink Flush Required: yes/no

## Test Context

- Checkout URL:
- QA Index URL:
- Test Users:
- Products in Cart:
- Guest / Logged-in scenarios:

## Validated Flows

- [ ] guest checkout
- [ ] logged-in no address
- [ ] logged-in one address
- [ ] logged-in multiple addresses
- [ ] shipping method
- [ ] billing step
- [ ] payment step

## Notes

- 

## Result

- Sync Status: pending/pass/fail/partial
- Verified By:
- Verified On:
```
