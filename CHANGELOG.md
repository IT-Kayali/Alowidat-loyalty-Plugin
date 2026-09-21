# Changelog

All notable changes to **IT-Kayali Loyalty** are documented here.

## [0.1.0] - 2026-09-21

### Added

- Initial production-oriented WordPress plugin scaffold.
- `ITKayali\\Loyalty` namespace with PSR-4-style autoloading.
- Activation/deactivation lifecycle.
- Schema-versioned database migration entry point using WordPress `dbDelta()`.
- Dedicated tables for members, cards, WordPress user links, ledger entries, redemptions, reservations, external references and branches.
- Independent `member_uuid` foundation decoupled from WordPress/WooCommerce user IDs.
- Ledger fields for immutable transaction references, correction links and idempotency support.
- Loyalty customer and staff roles with least-privilege capability foundations.
- Administrator loyalty capabilities.
- Minimal integration contract for future optional adapters such as WooCommerce and helloCash.
- Safe uninstall policy that preserves loyalty data unless destructive cleanup is explicitly enabled.

### Not yet implemented

- Customer registration/login and Magic Link.
- WooCommerce account linking.
- Digital card/stamps/QR generation.
- Admin UI.
- Frontend staff workflow.
- Point earning, reward redemption and reservation domain services.
- WooCommerce automation.
- helloCash adapter.
