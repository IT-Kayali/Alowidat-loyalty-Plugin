=== IT-Kayali Loyalty ===
Contributors: it-kayali
Tags: loyalty, points, rewards, stamp-card, woocommerce
Requires at least: 6.4
Requires PHP: 8.1
Stable tag: 0.1.0
License: Proprietary

Modular loyalty, points and digital stamp-card foundation for WordPress with optional WooCommerce integration.

== Description ==

IT-Kayali Loyalty is being developed as the central loyalty system for Alowidat.

Version 0.1.0 is the Phase 1 technical foundation. It creates the plugin lifecycle, namespaced architecture, schema migration basis, dedicated loyalty tables and restricted loyalty roles/capabilities. WooCommerce is intentionally optional.

Customer registration, digital cards, QR scanning, staff workflows and points/reward operations will be implemented phase by phase and are not exposed in this release.

== Installation ==

1. Upload the plugin ZIP using Plugins > Add New > Upload Plugin.
2. Activate IT-Kayali Loyalty.
3. Activation creates the Phase 1 schema and roles/capabilities.

== Frequently Asked Questions ==

= Does version 0.1.0 already award points? =

No. This release establishes the safe technical foundation only.

= Is WooCommerce required? =

No. The loyalty core is designed to work independently. WooCommerce integration is planned as an optional module.

= Are plugin data removed on deactivation? =

No. Deactivation is non-destructive. Uninstall also preserves loyalty data by default.

== Changelog ==

= 0.1.0 =
* Initial Phase 1 foundation.
* Added namespaced plugin bootstrap and autoloader.
* Added schema-versioned database installation.
* Added member, card, user-link, ledger, redemption, reservation, external-reference and branch tables.
* Added loyalty customer/staff roles and custom capabilities.
* Added optional integration contract and non-destructive lifecycle behavior.
