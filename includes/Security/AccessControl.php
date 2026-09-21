<?php
/**
 * Frontend-only restrictions for loyalty customers and staff.
 *
 * @package ITKayali\Loyalty\Security
 */

namespace ITKayali\Loyalty\Security;

use ITKayali\Loyalty\Core\AccountPage;
use ITKayali\Loyalty\Roles\RoleManager;

final class AccessControl
{
    public static function register(): void
    {
        add_action('admin_init', array(self::class, 'blockRestrictedAdmin'));
        add_action('template_redirect', array(self::class, 'blockLoyaltyOnlyWooAccount'), 1);
        add_filter('show_admin_bar', array(self::class, 'filterAdminBar'));
        add_filter('login_redirect', array(self::class, 'filterLoginRedirect'), 10, 3);
    }

    public static function blockRestrictedAdmin(): void
    {
        if (! is_user_logged_in() || wp_doing_ajax() || current_user_can('manage_options')) {
            return;
        }

        $user = wp_get_current_user();
        if (! self::isRestricted($user)) {
            return;
        }

        wp_safe_redirect(self::customerRedirect($user));
        exit;
    }

    public static function blockLoyaltyOnlyWooAccount(): void
    {
        if (
            ! is_user_logged_in()
            || current_user_can('manage_options')
            || ! function_exists('is_account_page')
            || ! is_account_page()
        ) {
            return;
        }

        $user = wp_get_current_user();
        if (! self::isLoyaltyOnlyCustomer($user)) {
            return;
        }

        wp_safe_redirect(AccountPage::url());
        exit;
    }

    public static function filterAdminBar(bool $show): bool
    {
        if (! is_user_logged_in() || current_user_can('manage_options')) {
            return $show;
        }

        return self::isRestricted(wp_get_current_user()) ? false : $show;
    }

    public static function filterLoginRedirect(string $redirect_to, string $requested_redirect_to, $user): string
    {
        if (! $user instanceof \WP_User || user_can($user, 'manage_options')) {
            return $redirect_to;
        }

        return self::isRestricted($user) ? self::customerRedirect($user) : $redirect_to;
    }

    private static function isLoyaltyOnlyCustomer(\WP_User $user): bool
    {
        $roles = (array) $user->roles;

        return in_array(RoleManager::CUSTOMER_ROLE, $roles, true)
            && ! in_array('customer', $roles, true)
            && ! user_can($user, 'manage_woocommerce');
    }

    private static function isRestricted(\WP_User $user): bool
    {
        return in_array(RoleManager::CUSTOMER_ROLE, (array) $user->roles, true)
            || in_array(RoleManager::STAFF_ROLE, (array) $user->roles, true);
    }

    private static function customerRedirect(\WP_User $user): string
    {
        if (in_array(RoleManager::CUSTOMER_ROLE, (array) $user->roles, true)) {
            return AccountPage::url();
        }

        return home_url('/');
    }
}
