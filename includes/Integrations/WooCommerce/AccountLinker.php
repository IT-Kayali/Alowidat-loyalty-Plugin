<?php
/**
 * Explicit linking between WooCommerce accounts and loyalty membership.
 *
 * @package ITKayali\Loyalty\Integrations\WooCommerce
 */

namespace ITKayali\Loyalty\Integrations\WooCommerce;

use ITKayali\Loyalty\Accounts\Mailer;
use ITKayali\Loyalty\Accounts\MemberRepository;
use ITKayali\Loyalty\Accounts\TokenRepository;
use ITKayali\Loyalty\Roles\RoleManager;
use ITKayali\Loyalty\Security\RateLimiter;

final class AccountLinker
{
    public function __construct(
        private MemberRepository $members,
        private TokenRepository $tokens,
        private RateLimiter $rateLimiter,
        private ?Mailer $mailer = null
    ) {
        $this->mailer ??= new Mailer();
    }

    public function activateForExistingCustomer(int $user_id): bool|\WP_Error
    {
        if (! AccountEndpoint::isAvailable()) {
            return new \WP_Error('woocommerce_unavailable', __('WooCommerce ist derzeit nicht verfügbar.', 'it-kayali-loyalty'));
        }

        $user = get_user_by('id', $user_id);
        if (! $user instanceof \WP_User || ! $this->isShopCustomer($user)) {
            return new \WP_Error('not_shop_customer', __('Dieses Benutzerkonto ist kein WooCommerce-Kundenkonto.', 'it-kayali-loyalty'));
        }

        $linked = $this->members->findByWpUserId($user_id);
        if ($linked) {
            $user->add_role(RoleManager::CUSTOMER_ROLE);
            return true;
        }

        $email = MemberRepository::normalizeEmail((string) $user->user_email);
        if (! is_email($email)) {
            return new \WP_Error('invalid_email', __('Das Shop-Konto besitzt keine gültige E-Mail-Adresse.', 'it-kayali-loyalty'));
        }

        if ($this->members->findByEmail($email) || $this->members->findByPendingEmail($email)) {
            return new \WP_Error('loyalty_identity_conflict', __('Für diese E-Mail-Adresse existiert bereits ein anderes Treuekonto.', 'it-kayali-loyalty'));
        }

        if (! $this->rateLimiter->allow('woocommerce_loyalty_optin', (string) $user_id, 4, HOUR_IN_SECONDS)) {
            return new \WP_Error('rate_limited', __('Zu viele Aktivierungsversuche. Bitte versuche es später erneut.', 'it-kayali-loyalty'));
        }

        $name = $this->customerName($user);
        $member_id = $this->members->create($name, $email, $user_id);
        if (is_wp_error($member_id)) {
            return $member_id;
        }

        $user->add_role(RoleManager::CUSTOMER_ROLE);
        clean_user_cache($user_id);

        $token = $this->tokens->create(
            (int) $member_id,
            $user_id,
            TokenRepository::TYPE_VERIFY_EMAIL,
            DAY_IN_SECONDS
        );

        if (is_wp_error($token)) {
            return $token;
        }

        if (! $this->mailer->sendVerification($email, $name, $token)) {
            return new \WP_Error(
                'mail_failed',
                __('Das Treuekonto wurde verknüpft, aber die Bestätigungs-E-Mail konnte nicht versendet werden.', 'it-kayali-loyalty')
            );
        }

        do_action('itk_loyalty_woocommerce_optin_created', $user_id, (int) $member_id);

        return true;
    }

    public function upgradeLoyaltyToShop(int $user_id): bool|\WP_Error
    {
        if (! AccountEndpoint::isAvailable() || ! get_role('customer')) {
            return new \WP_Error('woocommerce_unavailable', __('WooCommerce ist derzeit nicht verfügbar.', 'it-kayali-loyalty'));
        }

        $user = get_user_by('id', $user_id);
        if (! $user instanceof \WP_User) {
            return new \WP_Error('user_missing', __('Das Benutzerkonto wurde nicht gefunden.', 'it-kayali-loyalty'));
        }

        $member = $this->members->findByWpUserId($user_id);
        if (! $member) {
            return new \WP_Error('member_missing', __('Dieses Benutzerkonto ist nicht mit einem Treuekonto verbunden.', 'it-kayali-loyalty'));
        }

        if ('active' !== (string) $member['status'] || empty($member['email_verified_at'])) {
            return new \WP_Error('member_inactive', __('Bitte bestätige zuerst dein Treuekonto.', 'it-kayali-loyalty'));
        }

        if ($this->isShopCustomer($user)) {
            return true;
        }

        if (! $this->rateLimiter->allow('woocommerce_account_upgrade', (string) $user_id, 4, HOUR_IN_SECONDS)) {
            return new \WP_Error('rate_limited', __('Zu viele Upgrade-Versuche. Bitte versuche es später erneut.', 'it-kayali-loyalty'));
        }

        $user->add_role('customer');
        clean_user_cache($user_id);

        $updated = get_user_by('id', $user_id);
        if (! $updated instanceof \WP_User || ! in_array('customer', (array) $updated->roles, true)) {
            return new \WP_Error('upgrade_failed', __('Das Shop-Konto konnte nicht freigeschaltet werden.', 'it-kayali-loyalty'));
        }

        do_action('itk_loyalty_customer_upgraded_to_woocommerce', $user_id, (int) $member['id']);

        return true;
    }

    public function isShopCustomer(\WP_User $user): bool
    {
        return in_array('customer', (array) $user->roles, true);
    }

    public function isLoyaltyOnlyCustomer(\WP_User $user): bool
    {
        $roles = (array) $user->roles;

        return in_array(RoleManager::CUSTOMER_ROLE, $roles, true)
            && ! in_array('customer', $roles, true)
            && ! user_can($user, 'manage_woocommerce');
    }

    private function customerName(\WP_User $user): string
    {
        $billing_name = trim(
            (string) get_user_meta($user->ID, 'billing_first_name', true)
            . ' '
            . (string) get_user_meta($user->ID, 'billing_last_name', true)
        );

        if ('' !== $billing_name) {
            return sanitize_text_field($billing_name);
        }

        $display_name = trim((string) $user->display_name);
        if ('' !== $display_name) {
            return sanitize_text_field($display_name);
        }

        return sanitize_text_field((string) $user->user_login);
    }
}
