# Changelog

All notable changes to **IT-Kayali Loyalty** are documented here.

## [0.3.1] - 2026-09-22

### Changed

- The native WooCommerce **My Account** login page remains available for logged-out visitors and is no longer redirected to the standalone Treuekonto page.
- The standalone `/treuekonto/` page remains available for loyalty account creation, password login and Magic Link.
- Loyalty-only customers now set a personal password as part of the first email-confirmation flow; the account is activated only after the password is saved.
- After confirmation, the same loyalty account can sign in with email + password through either login page.
- Magic-Link emails now contain two independent one-time links: a 15-minute login link and a 60-minute password setup/change link.
- Existing loyalty customers created before 0.3.1 can use the password link in a Magic-Link email to establish a known password without creating a new account.

### Security

- Password setup uses a dedicated hashed one-time token type and never stores raw setup tokens in the database.
- Password validation occurs before consuming a verification/password token so correctable input errors do not invalidate the link.
- Verification still uses the original one-time email token; WooCommerce customers that already possess a password keep the direct verification flow.

### Verified

- PHP syntax validation passes for all plugin PHP files.
- Plugin header/runtime/readme versions are aligned at `0.3.1`.
- No database schema migration is required; schema version 2 remains current.
- Installable ZIP is built from the same GitHub commit by CI.

## [0.3.0] - 2026-09-21

### Added

- Phase 3 explicit WooCommerce loyalty opt-in for existing WooCommerce customers.
- Existing shop customers now receive a **Treuekonto** entry in WooCommerce My Account even before loyalty is activated.
- Shop customers can explicitly activate loyalty from `/my-account/treuekonto/`; the existing WordPress/WooCommerce user is reused instead of creating a duplicate user.
- WooCommerce opt-in creates one independent loyalty member linked to the existing user and requires email confirmation before the loyalty member becomes active.
- Loyalty-only customers can explicitly upgrade the same WordPress user to the WooCommerce `customer` role.
- Loyalty-to-shop upgrades preserve the existing loyalty `member_uuid`, ledger balance, reservations, redemptions and history because no new loyalty member is created.
- After a loyalty-to-shop upgrade, the normal WooCommerce My Account menu becomes available while **Treuekonto** remains an additional entry.
- Rate limiting and nonces protect both WooCommerce opt-in and loyalty-to-shop upgrade actions.
- Integration hooks `itk_loyalty_woocommerce_optin_created` and `itk_loyalty_customer_upgraded_to_woocommerce` are available for future modules.

### Changed

- Pending loyalty verification no longer blocks a pre-existing WooCommerce customer from using the normal shop login; only loyalty-only accounts remain verification-gated.
- Authenticated loyalty redirects now target the central `/my-account/treuekonto/` endpoint when WooCommerce is available.
- The standalone `/treuekonto/` page remains the logged-out registration/login fallback.
- Logged-out WooCommerce My Account visits are redirected to the shared loyalty login/registration page so all customer account types use the same sign-in form; WooCommerce lost-password remains available.
- WooCommerce account-email changes are blocked for linked loyalty customers and must be performed through **Treuekonto**, preserving the verified email-change workflow.

### Verified

- PHP syntax validation passes for all plugin PHP files.
- Plugin header/runtime/readme versions are aligned at `0.3.0`.
- No database schema migration is required; schema version 2 remains current.
- Installable ZIP is built from the same GitHub commit by CI.

## [0.2.2] - 2026-09-21

### Changed

- The logged-in loyalty customer area now lives inside WooCommerce **My Account** at `/my-account/treuekonto/` when WooCommerce is active.
- Loyalty-only customers see only **Treuekonto** and **Abmelden** in the WooCommerce account navigation.
- Direct visits to the WooCommerce dashboard, orders, addresses, payment methods and other account endpoints are redirected to **Treuekonto** for loyalty-only customers.
- Logged-in loyalty customers visiting the legacy standalone `/treuekonto/` page are redirected to the WooCommerce **Treuekonto** endpoint.
- The standalone `/treuekonto/` page remains available as the registration/login fallback and for installations without WooCommerce.
- Future explicitly upgraded shop customers can keep the normal WooCommerce account menu while also receiving a **Treuekonto** entry.
- Rewrite rules are refreshed once per plugin version when the WooCommerce endpoint is available.

### Verified

- PHP syntax validation passes for all plugin PHP files.
- Plugin header/runtime/readme versions are aligned at `0.2.2`.
- Installable ZIP is built from the same GitHub commit by CI.

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
