# IT-Kayali Loyalty

**Current version:** 0.1.0

IT-Kayali Loyalty is a modular WordPress loyalty foundation for Alowidat. The long-term goal is one central loyalty account per customer across the website, WooCommerce and the physical shop. WooCommerce remains optional so the loyalty core can operate independently.

## Phase 1 status

Version 0.1.0 establishes the technical foundation only. Customer registration, digital cards, QR scanning, points booking, reward redemption and WooCommerce automation are deliberately not exposed yet.

Implemented in Phase 1:

- installable WordPress plugin bootstrap
- `ITKayali\\Loyalty` namespace and PSR-4-style autoloading
- activation and non-destructive deactivation lifecycle
- schema-versioned database migration entry point
- dedicated database tables for members, cards, WordPress links, ledger, redemptions, reservations, external references and branches
- independent loyalty `member_uuid` foundation; no dependency on a WordPress/WooCommerce user ID
- append-oriented ledger schema with transaction UUID, reference transaction and idempotency key support
- customer role `itk_loyalty_customer`
- staff role `itk_loyalty_staff` with minimal loyalty capabilities
- explicit plugin capabilities for future admin and staff functions
- administrator capability grants
- optional integration contract so WooCommerce and helloCash can be added later as adapters
- safe uninstall behavior: loyalty data is retained unless explicit destructive cleanup is enabled in a future setting

## Requirements

- WordPress 6.4 or newer
- PHP 8.1 or newer
- MySQL/MariaDB supported by the installed WordPress version
- WooCommerce is **not required** for the plugin core

## Installation

1. Upload `it-kayali-loyalty.zip` in **WordPress → Plugins → Add New → Upload Plugin**.
2. Activate **IT-Kayali Loyalty**.
3. Version 0.1.0 creates the schema and base roles/capabilities.

There is intentionally no customer-facing or staff-facing UI in Phase 1.

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

The point balance will later be derived from ledger movements rather than maintained as an independently editable number.

## Roles

### Loyalty Customer

Internal role key: `itk_loyalty_customer`

Phase 1 grants only the basic WordPress `read` capability. Frontend-only access restrictions are implemented with the customer account work in Phase 2.

### Loyalty Staff

Internal role key: `itk_loyalty_staff`

Phase 1 grants only:

- `read`
- `itk_loyalty_scan_members`
- `itk_loyalty_add_points`
- `itk_loyalty_redeem_rewards`

Staff does not receive Shop Manager or Administrator permissions.

## Shortcodes

No shortcodes are registered in Phase 1. Planned later phases include a unified customer account, customer registration/card views and the frontend staff area. Shortcodes/endpoints will be documented when they become functional.

## Planned customer flow

Phase 2 will add a loyalty-only registration using name and email, email verification, a common customer login/Magic Link flow and a restricted loyalty account area. WooCommerce linking/upgrade is Phase 3.

## Planned staff flow

Phase 6 will add a frontend staff area for QR/card lookup, controlled point earning and reward redemption. A QR scan alone will never alter points.

## Points and rewards rules

These business rules are planned but are **not active in version 0.1.0**:

- eligible 50 ml perfume: 1 point per purchased unit
- eligible 100 ml perfume: 1 point per purchased unit
- other sizes: no points
- 10 points: one eligible 50 ml designer-perfume reward
- points remain in the ledger until an actual redemption subtracts 10 points
- no automatic expiry in the first functional release

## Security foundation

Phase 1 establishes capability separation, dedicated tables, unique identifiers and idempotency fields. Later mutation endpoints will additionally require nonces, capability checks, server-side validation, prepared SQL, rate limits and transaction/locking controls.

## Known limitations

- no frontend registration/login yet
- no digital card or QR generation yet
- no admin dashboard yet
- no staff frontend yet
- no points booking/redemption service yet
- no WooCommerce automation yet
- no helloCash integration yet
- database foreign-key constraints are intentionally not used; WordPress-compatible application-level integrity will be implemented in domain services

## Roadmap

1. **0.1.x / Phase 1:** project foundation and stabilization
2. **Phase 2:** loyalty customer accounts, email verification, unified login and Magic Link
3. **Phase 3:** WooCommerce opt-in/linking and loyalty-to-shop upgrade
4. **Phase 4:** digital card, stamp progress and QR identity
5. **Phase 5:** WordPress administration
6. **Phase 6:** frontend staff workflow
7. **Phase 7:** WooCommerce earning, reversals and online rewards/reservations
8. **Phase 8:** security, concurrency, duplicate protection, performance and acceptance tests
9. **Phase 9:** helloCash adapter after API capability review

## Versioning

The project follows SemVer-oriented releases. Plugin header, runtime constant, documentation and packaged ZIP must represent the same version.

See [CHANGELOG.md](CHANGELOG.md) for release history.
