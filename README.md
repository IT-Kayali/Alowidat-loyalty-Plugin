# IT-Kayali Loyalty

**Current version:** 0.3.0

IT-Kayali Loyalty is a modular WordPress loyalty foundation for Alowidat. The long-term goal is one central loyalty account per customer across the website, optional WooCommerce and the physical shop. WooCommerce remains optional so the loyalty core can operate independently.

## Phase 3 status

Version 0.3.0 adds explicit two-way account linking: existing WooCommerce customers can voluntarily activate loyalty, and loyalty-only customers can upgrade the same account to a WooCommerce shop account without replacing their loyalty identity.

Implemented:

- installable WordPress plugin bootstrap and `ITKayali\Loyalty` namespace
- schema-versioned database migrations
- dedicated tables for members, cards, WordPress links, ledger, redemptions, reservations, external references, branches and one-time account tokens
- independent `member_uuid`; loyalty identity is not based on the WordPress/WooCommerce user ID
- roles `itk_loyalty_customer` and `itk_loyalty_staff`
- frontend-only access protection for loyalty customers/staff; no normal wp-admin access and no admin bar
- WooCommerce **My Account** is the central logged-in account shell when WooCommerce is active; loyalty-only customers see only **Treuekonto** and **Abmelden**
- loyalty-only customers are redirected from all other WooCommerce account endpoints to `/my-account/treuekonto/`
- existing WooCommerce customers receive **Treuekonto** as an additional menu entry and can explicitly activate loyalty there
- loyalty-only customers can explicitly upgrade the same WordPress user to the WooCommerce `customer` role; the loyalty member is not recreated
- upgraded customers keep the normal WooCommerce menu plus **Treuekonto**
- automatically created **Treuekonto** page with `[itk_loyalty_account]`
- customer registration with only name and email
- duplicate protection against an existing loyalty email and against silently enrolling an existing WordPress/WooCommerce account
- email verification with hashed, one-time, expiring tokens
- verification-link resend flow
- shared frontend password login for existing WordPress users
- Magic Link login for active loyalty customers
- generic Magic-Link responses to reduce email-account enumeration
- rate limiting for registration, login-link and email-change requests
- customer account view with current ledger-derived point balance
- profile update for name and email
- email changes remain pending until the new address is verified; the old address remains valid until then
- database-level uniqueness protection for pending email changes and rollback protection when the linked WordPress email cannot be finalized
- optional integration contract so WooCommerce and helloCash can be added later as adapters
- non-destructive deactivation/uninstall behavior by default

Not implemented yet: digital cards, QR generation, stamps, staff scanner, reward redemption, automatic WooCommerce point earning/reversals and helloCash.

## Requirements

- WordPress 6.4 or newer
- PHP 8.1 or newer
- MySQL/MariaDB supported by the installed WordPress version
- HTTPS is strongly recommed and will be mandatory for camera scanning in a later phase
- WordPress email delivery must be configured for verification and Magic Links
- WooCommerce is **not required** for the plugin core

## Installation / upgrade

1. Upload `it-kayali-loyalty.zip` in **WordPress → Plugins → Add New → Upload Plugin**.
2. If version 0.1.0 is already installed, replace the existing plugin with the ZIP when WordPress asks.
3. Keep the plugin active.
4. Version 0.3.0 reuses schema version 2, creates/reuses the standalone **Treuekonto** page, registers the WooCommerce `/my-account/treuekonto/` endpoint, and enables explicit account linking when WooCommerce is active.
5. Open the Treuekonto page and test registration with an email address that is not already used by a WordPress user.

## Shortcodes

### `[itk_loyalty_account]`

Primary unified customer page. Logged-out visitors see:

- password login for existing WordPress users
- Magic Link request for active loyalty customers
- verification-link resend
- loyalty registration with name + email

Logged-in loyalty customers see their loyalty account/profile and current ledger-derived point balance.

### `[itk_loyalty_login]`

Optional standalone rendering of the same login/Magic-Link forms. Normally `[itk_loyalty_account]` is preferred to keep the customer journey unified.

### `[itk_loyalty_register]`

Optional standalone registration form. Normally `[itk_loyalty_account]` is preferred.

## Roles

### Loyalty Customer

Internal role key: `itk_loyalty_customer`

This role is created for loyalty-only customers. The password is generated internally; the intended primary sign-in method is the email Magic Link. Customers have no normal wp-admin access and no admin bar.

### Loyalty Staff

Internal role key: `itk_loyalty_staff`

Phase 1/2 grants only the minimal loyalty capabilities required by the later staff workflow:

- `read`
- `itk_loyalty_scan_members`
- `itk_loyalty_add_points`
- `itk_loyalty_redeem_rewards`

The actual staff frontend is Phase 6.

## Customer flow in 0.3.0

1. Customer opens the shared Treuekonto page.
2. New loyalty-only customer enters **name + email**.
3. The system creates an independent loyalty member, a restricted WordPress user link and a one-time verification token.
4. Customer confirms the email through the emailed link.
5. Verification activates the loyalty member and signs the customer in.
6. Later logins can use a 15-minute one-time Magic Link.
7. When WooCommerce is active, the logged-in customer lands in `/my-account/treuekonto/`.
8. Loyalty-only customers see only **Treuekonto** and **Abmelden** in the account menu.
9. Name can be changed immediately.
10. A new email address is stored only as pending until the new mailbox confirms it. The old email remains authoritative meanwhile.

Existing WordPress/WooCommerce users are deliberately **not** silently enrolled during loyalty registration.

### Existing WooCommerce customer → Loyalty

1. The customer logs into the existing WooCommerce account.
2. **Treuekonto** is available in My Account.
3. The customer explicitly chooses **Treueprogramm aktivieren**.
4. The plugin reuses the same WordPress/WooCommerce user and creates only the independent loyalty member/link.
5. A verification email is sent to the shop-account email.
6. After confirmation, loyalty becomes active and the customer keeps the full WooCommerce menu plus **Treuekonto**.

### Loyalty-only customer → WooCommerce shop account

1. The verified loyalty customer opens **Treuekonto**.
2. The customer explicitly chooses **Auf Shop-Konto upgraden**.
3. The existing WordPress user receives the WooCommerce `customer` role.
4. No new loyalty member is created: `member_uuid`, ledger, balance and history remain unchanged.
5. The normal WooCommerce account areas become visible in addition to **Treuekonto**.

## Database foundation

The plugin uses the active WordPress table prefix and creates:

- `{prefix}itk_loyalty_members`
- `{prefix}itk_loyalty_cards`
- `{prefix}itk_loyalty_user_links`
- `{prefix}itk_loyalty_ledger`
- `{prefix}itk_loyalty_redemptions`
- `{prefix}itk_loyalty_reservations`
- `{prefix}itk_loyalty_external_refs`
- `{prefix}itk_loyalty_branches`
- `{prefix}itk_loyalty_tokens`

Version 0.2.0 adds pending-email fields to the member schema and the token table. Only SHA-256 hashes of verification/Magic tokens are persisted; raw login tokens are never stored in the database.

The point balance is derived from ledger movements rather than maintained as a silently editable independent number.

## Security foundation

Current protections include:

- WordPress nonces for state-changing frontend forms
- server-side input validation and sanitization
- output escaping
- WordPress prepared queries for lookups
- dedicated capabilities and restricted roles
- wp-admin blocking for loyalty customer/staff roles
- verification-only account state for pending loyalty customers; unverified accounts cannot use the active loyalty dashboard
- admin-bar hiding for restricted loyalty roles
- random one-time verification/Magic tokens stored only as hashes
- token expiry and one-time consumption
- rate limiting for public email-triggering actions
- generic Magic-Link/resend responses to reduce account enumeration
- duplicate loyalty-email protection
- no customer data in any QR because QR functionality is not implemented yet

Later points/redemption endpoints will additionally require transactional locking, idempotency and concurrency controls.

## Points and rewards rules

These business rules remain planned but are **not active in version 0.2.0**:

- eligible 50 ml perfume: 1 point per purchased unit
- eligible 100 ml perfume: 1 point per purchased unit
- other sizes: no points
- 10 points: one eligible 50 ml designer-perfume reward
- points remain in the ledger until an actual redemption subtracts 10 points
- no automatic expiry in the first functional release

## Known limitations

- email delivery depends on the WordPress mail configuration; production should use a reliable SMTP/provider setup
- WooCommerce opt-in/account upgrade is implemented, but automatic point earning from WooCommerce orders is not yet active
- no digital card, stamps or QR code yet
- no admin dashboard yet
- no staff frontend/scanner yet
- no points earning/redemption service yet
- no helloCash integration yet
- the automatically created Treuekonto page intentionally uses the shortcode and the active theme's surrounding layout

## Roadmap

1. **0.1.x / Phase 1:** project foundation
2. **0.2.0–0.2.2 / Phase 2:** loyalty-only customer registration, verification, shared login/Magic Link, account data and WooCommerce My Account integration
3. **0.3.0 / Phase 3:** WooCommerce explicit opt-in/linking and loyalty-to-shop upgrade without changing `member_uuid`, points or history
4. **Phase 4:** digital card, 10-stamp progress, full-card count and private QR identity
5. **Phase 5:** WordPress administration
6. **Phase 6:** frontend staff workflow and scanner
7. **Phase 7:** WooCommerce earning, reversals and online reward reservations
8. **Phase 8:** security/concurrency/duplicate/performance hardening and acceptance tests
9. **Phase 9:** helloCash adapter after API capability review

## Versioning

GitHub Actions validates PHP syntax and version consistency on every push/PR, builds the installable ZIP, and on a successful push to `main` creates the matching `vX.Y.Z` Git tag/GitHub Release when it does not already exist. The release asset is built from the same commit and excludes repository-only files such as `.github`, `.gitignore` and `.gitkeep`.

The project follows SemVer-oriented releases. Plugin header, runtime constant, README/readme and packaged ZIP must represent the same version.

See [CHANGELOG.md](CHANGELOG.md) for release history.
