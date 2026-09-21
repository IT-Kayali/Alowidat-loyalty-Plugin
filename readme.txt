=== IT-Kayali Loyalty ===
Contributors: it-kayali
Tags: loyalty, points, rewards, stamp-card, woocommerce
Requires at least: 6.4
Requires PHP: 8.1
Stable tag: 0.3.1
License: Proprietary

Modular loyalty and customer-account foundation for WordPress with optional WooCommerce integration.

== Description ==

IT-Kayali Loyalty is the central loyalty system being developed for Alowidat.

Version 0.3.1 keeps both the normal WooCommerce My Account login and the standalone Treuekonto login/registration page, while adding secure password setup for loyalty customers:

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
* existing WooCommerce customers can explicitly activate loyalty from Treuekonto
* loyalty-only customers can explicitly upgrade the same user to a WooCommerce customer account
* member UUID, ledger balance and loyalty history are preserved during the shop-account upgrade
* the normal WooCommerce My Account login remains available and the standalone Treuekonto login/registration page remains available
* linked loyalty customers change their account email only through Treuekonto so email verification cannot be bypassed
* loyalty-only email confirmation requires a personal password before activation
* after confirmation the same loyalty account can sign in with email + password on either login page
* Magic-Link emails also include a separate 60-minute one-time password setup/change link for existing or passwordless loyalty accounts
* German-style membership date display and theme-resistant loyalty buttons

Existing WordPress/WooCommerce users are never silently enrolled into loyalty. Version 0.3.0 provides explicit opt-in from the authenticated WooCommerce account.

Digital cards, QR, stamps, staff workflow, automatic WooCommerce point earning/reversals and reward redemption are later phases and are not exposed yet.

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

= Does version 0.3.1 already award points? =

No. It can display the current ledger-derived balance, but earning/redemption services are not active yet.

= Is WooCommerce required? =

No. The loyalty core is designed to work independently. WooCommerce integration is an optional later module.

= Why can an existing WooCommerce email not register as a new loyalty-only account? =

To prevent duplicate identities and silent enrollment. The customer should log into the existing WooCommerce account, open Treuekonto, and explicitly activate the loyalty program there.

= How does a loyalty-only customer log in? =

During the first email confirmation the customer sets a personal password. Afterwards the same loyalty account can log in with email + password on either the Treuekonto page or the normal WooCommerce My Account page. Magic Link remains available as an additional option, and its email also contains a secure password setup/change link.

= What happens when a customer changes email? =

The new address remains pending until it is verified. The previous email remains valid until confirmation succeeds.

= Are plugin data removed on deactivation? =

No. Deactivation is non-destructive. Uninstall also preserves loyalty data by default.

== Changelog ==

= 0.3.1 =
* Keep the normal WooCommerce My Account login page instead of redirecting logged-out visitors to Treuekonto.
* Keep the standalone Treuekonto page for account creation, password login and Magic Link.
* Require loyalty-only customers to set a personal password while confirming the first verification email.
* Allow the same loyalty account to log in with email + password on both Treuekonto and WooCommerce My Account.
* Add a separate 60-minute one-time password setup/change link to Magic-Link emails.
* Give existing pre-0.3.1 loyalty accounts a secure path to set a known password without recreating the account.

= 0.3.0 =
* Add explicit loyalty opt-in for existing WooCommerce customers.
* Reuse the existing WooCommerce/WordPress user instead of creating a duplicate account.
* Require email confirmation before an existing shop customer's new loyalty member becomes active.
* Add explicit loyalty-only to WooCommerce customer upgrade.
* Preserve the same loyalty member UUID, ledger balance and history during upgrade.
* Show the full WooCommerce My Account menu plus Treuekonto after upgrade.
* Keep existing WooCommerce shop login available while a newly linked loyalty member is still pending verification.

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
