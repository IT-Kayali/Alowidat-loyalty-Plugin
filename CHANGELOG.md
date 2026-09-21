# Changelog

All notable changes to **IT-Kayali Loyalty** are documented here.

## [0.2.1] - 2026-09-21

### Fixed

- Loyalty-only customers are redirected from WooCommerce **My Account** and its account endpoints back to **Treuekonto**.
- The WooCommerce account area remains available for future explicitly upgraded customers that receive the WooCommerce `customer` role.
- Membership dates now use the unambiguous German-style `dd.mm.yyyy` display.
- Loyalty buttons use stronger scoped styles so active theme button rules cannot make the primary action appear disabled or unreadable.

### Verified

- PHP syntax validation passes for all plugin PHP files.
- Plugin header/runtime/readme versions are aligned at `0.2.1`.
- Installable ZIP is built from the same GitHub commit by CI.

## [0.2.0] - 2026-09-21

### Added

- Loyalty-only registration with name and email.
- Restricted WordPress user creation using `itk_loyalty_customer` while retaining an independent loyalty `member_uuid`.
- Email verification with SHA-256-hashed, expiring, one-time tokens.
- Verification-email resend flow with generic responses.
- Unified frontend account page and automatic **Treuekonto** page creation.
- `[itk_loyalty_account]`, `[itk_loyalty_login]` and `[itk_loyalty_register]` shortcodes.
- Existing WordPress password login on the shared customer page.
- 15-minute one-time Magic Link login for verified loyalty customers.
- Rate limiting for registration, verification resend, Magic Link and email-change requests.
- Customer profile with ledger-derived point balance.
- Name editing.
- Pending-email workflow: the old email remains valid until the new email is confirmed.
- wp-admin blocking and admin-bar hiding for restricted loyalty customer/staff roles.
- Pending/unverified loyalty customers are kept in verification-only state even if a WordPress session exists.
- Email-change confirmation updates the linked WordPress account first and rolls it back if the loyalty record cannot be finalized.
- Pending email addresses are uniqueness-protected at database level.
- Schema version 2 with pending-email fields and `itk_loyalty_tokens` table.
- Responsive frontend account styling.

### Security

- Raw verification and Magic Link tokens are never stored in the database.
- One-time token consumption uses an atomic used-state update.
- Public email-triggering endpoints use generic messages to reduce account enumeration.
- Existing WordPress/WooCommerce emails are not automatically converted into loyalty members.

### Not yet implemented

- WooCommerce loyalty opt-in and loyalty-to-shop upgrade (Phase 3).
- Digital card, stamps and QR identity (Phase 4).
- Admin dashboard (Phase 5).
- Staff frontend/scanner (Phase 6).
- Point earning/redemption and WooCommerce automation (Phase 7).
- helloCash adapter (Phase 9).

## [0.1.0] - 2026-09-21

### Added

- Initial production-oriented WordPress plugin scaffold.
- `ITKayali\Loyalty` namespace with PSR-4-style autoloading.
- Activation/deactivation lifecycle.
- Schema-versioned database migration entry point using WordPress `dbDelta()`.
- Dedicated tables for members, cards, WordPress user links, ledger entries, redemptions, reservations, external references and branches.
- Independent `member_uuid` foundation decoupled from WordPress/WooCommerce user IDs.
- Ledger fields for immutable transaction references, correction links and idempotency support.
- Loyalty customer and staff roles with least-privilege capability foundations.
- Administrator loyalty capabilities.
- Minimal integration contract for future optional adapters such as WooCommerce and helloCash.
- Safe uninstall policy that preserves loyalty data unless destructive cleanup is explicitly enabled.
