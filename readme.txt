=== IT-Kayali Loyalty ===
Contributors: it-kayali
Tags: loyalty, points, rewards, stamp-card, woocommerce
Requires at least: 6.4
Requires PHP: 8.1
Stable tag: 0.2.2
License: Proprietary

Modular loyalty and customer-account foundation for WordPress with optional WooCommerce integration.

== Description ==

IT-Kayali Loyalty is the central loyalty system being developed for Alowidat.

Version 0.2.2 provides the completed Phase 2 loyalty-only customer account flow:

* registration with name and email only
* email verification
* restricted loyalty customer role
* common frontend login page
* one-time Magic Link login for verified loyalty customers
* resend-verification flow
* customer profile with ledger-derived point balance
* name update
* safe pending email change with verification of the new address
* automatic Treuekonto page using [itk_loyalty_account]
* rate limiting and hashed one-time security tokens
* WooCommerce My Account integration at /my-account/treuekonto/
* loyalty-only customers see only Treuekonto and Abmelden in the account menu
* all other WooCommerce account endpoints redirect to Treuekonto for loyalty-only customers
* German-style membership date display and theme-resistant loyalty buttons

Existing WordPress/WooCommerce users are not silently enrolled into loyalty. Explicit WooCommerce opt-in/linking is planned for Phase 3.

Digital cards, QR, stamps, staff workflow and point earning/redemption are later phases and are not exposed yet.

== Installation ==

1. Upload the plugin ZIP using Plugins > Add New > Upload Plugin.
2. Activate IT-Kayali Loyalty or replace the existing older version if already installed.
3. Keep the plugin active; schema version 2 is applied automatically.
4. Open the automatically created Treuekonto page.
5. Verify that WordPress can send email before production use.

== Shortcodes ==

= [itk_loyalty_account] =
Primary unified account/login/registration page.

= [itk_loyalty_login] =
Optional standalone login and Magic-Link forms.

= [itk_loyalty_register] =
Optional standalone loyalty registration form.

== Frequently Asked Questions ==

= Does version 0.2.0 already award points? =

No. It can display the current ledger-derived balance, but earning/redemption services are not active yet.

= Is WooCommerce required? =

No. The loyalty core is designed to work independently. WooCommerce integration is an optional later module.

= Why can an existing WooCommerce email not register as a new loyalty-only account? =

To prevent duplicate identities and silent enrollment. Existing shop users will explicitly activate/link loyalty in Phase 3.

= How does a loyalty-only customer log in? =

After email verification, the intended method is a one-time Magic Link sent to the verified email address. The common page also contains the normal WordPress password login for existing accounts.

= What happens when a customer changes email? =

The new address remains pending until it is verified. The previous email remains valid until confirmation succeeds.

= Are plugin data removed on deactivation? =

No. Deactivation is non-destructive. Uninstall also preserves loyalty data by default.

== Changelog ==

= 0.2.2 =
* Move the logged-in loyalty customer area into WooCommerce My Account at /my-account/treuekonto/.
* Show only Treuekonto and Abmelden for loyalty-only customers.
* Redirect other WooCommerce account endpoints to Treuekonto for loyalty-only customers.
* Preserve the standalone Treuekonto page for registration/login fallback and non-WooCommerce installations.
* Keep future upgraded WooCommerce customers compatible with the full account menu plus Treuekonto.

= 0.2.1 =
* Redirect loyalty-only customers away from WooCommerce My Account to Treuekonto.
* Keep the guard compatible with future explicit WooCommerce upgrades.
* Display membership dates as dd.mm.yyyy.
* Strengthen scoped button styles against theme overrides.

= 0.2.0 =
* Added loyalty-only registration with name and email.
* Added email verification and resend flow.
* Added hashed expiring one-time token table.
* Added Magic Link login for verified loyalty customers.
* Added common frontend password login.
* Added automatic Treuekonto page and account/login/register shortcodes.
* Added customer profile and ledger-derived balance display.
* Added verified pending-email change workflow.
* Added rate limiting and frontend-only role restrictions.
* Added schema migration from version 1 to version 2.

= 0.1.0 =
* Initial Phase 1 foundation.
* Added namespaced plugin bootstrap and autoloader.
* Added schema-versioned database installation.
* Added member, card, user-link, ledger, redemption, reservation, external-reference and branch tables.
* Added loyalty customer/staff roles and custom capabilities.
* Added optional integration contract and non-destructive lifecycle behavior.
