<?php
/**
 * WooCommerce My Account integration for loyalty customers.
 *
 * @package ITKayali\Loyalty\Integrations\WooCommerce
 */

namespace ITKayali\Loyalty\Integrations\WooCommerce;

use ITKayali\Loyalty\Accounts\MemberRepository;
use ITKayali\Loyalty\Core\AccountPage;
use ITKayali\Loyalty\Roles\RoleManager;

final class AccountEndpoint
{
    private const ENDPOINT = 'treuekonto';
    private const REWRITE_OPTION = 'itk_loyalty_wc_endpoint_rewrite_version';

    public static function isAvailable(): bool
    {
        return class_exists('WooCommerce')
            && function_exists('wc_get_page_permalink')
            && function_exists('wc_get_endpoint_url');
    }

    public static function register(): void
    {
        add_action('init', array(self::class, 'registerEndpoint'), 9);
        add_action('init', array(self::class, 'maybeFlushRewriteRules'), 99);
        add_filter('woocommerce_get_query_vars', array(self::class, 'addQueryVar'));
        add_filter('woocommerce_account_menu_items', array(self::class, 'filterMenuItems'), 20);
        add_action('woocommerce_account_' . self::ENDPOINT . '_endpoint', array(self::class, 'renderEndpoint'));
        add_action('template_redirect', array(self::class, 'redirectLoggedOutAccountToSharedLogin'), 1);
        add_action('template_redirect', array(self::class, 'redirectAccountRoutes'), 2);
        add_action('template_redirect', array(self::class, 'redirectLegacyTreuekonto'), 3);
        add_action('wp_enqueue_scripts', array(self::class, 'enqueueAssets'));
        add_action('woocommerce_save_account_details_errors', array(self::class, 'protectLinkedAccountEmail'), 10, 2);
    }

    public static function registerEndpoint(): void
    {
        add_rewrite_endpoint(self::ENDPOINT, EP_ROOT | EP_PAGES);
    }

    public static function maybeFlushRewriteRules(): void
    {
        $installed = (string) get_option(self::REWRITE_OPTION, '');

        if (ITK_LOYALTY_VERSION === $installed) {
            return;
        }

        flush_rewrite_rules(false);
        update_option(self::REWRITE_OPTION, ITK_LOYALTY_VERSION, false);
    }

    public static function addQueryVar(array $vars): array
    {
        $vars[self::ENDPOINT] = self::ENDPOINT;

        return $vars;
    }

    public static function filterMenuItems(array $items): array
    {
        if (! is_user_logged_in()) {
            return $items;
        }

        $user = wp_get_current_user();
        if (! self::hasLoyaltyRole($user) && ! self::isShopCustomer($user)) {
            return $items;
        }

        $label = __('Treuekonto', 'it-kayali-loyalty');

        if (self::isLoyaltyOnlyCustomer($user)) {
            $logout = $items['customer-logout'] ?? __('Abmelden', 'it-kayali-loyalty');

            return array(
                self::ENDPOINT => $label,
                'customer-logout' => $logout,
            );
        }

        $result = array();
        $inserted = false;

        foreach ($items as $key => $item_label) {
            $result[$key] = $item_label;

            if ('dashboard' === $key) {
                $result[self::ENDPOINT] = $label;
                $inserted = true;
            }
        }

        if (! $inserted) {
            $logout = $result['customer-logout'] ?? null;
            unset($result['customer-logout']);
            $result[self::ENDPOINT] = $label;

            if (null !== $logout) {
                $result['customer-logout'] = $logout;
            }
        }

        return $result;
    }

    public static function renderEndpoint(): void
    {
        echo do_shortcode('[itk_loyalty_account]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public static function redirectLoggedOutAccountToSharedLogin(): void
    {
        if (
            is_user_logged_in()
            || ! function_exists('is_account_page')
            || ! is_account_page()
        ) {
            return;
        }

        if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('lost-password')) {
            return;
        }

        wp_safe_redirect(AccountPage::url());
        exit;
    }

    public static function redirectAccountRoutes(): void
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

        if (
            function_exists('is_wc_endpoint_url')
            && (
                is_wc_endpoint_url(self::ENDPOINT)
                || is_wc_endpoint_url('customer-logout')
            )
        ) {
            return;
        }

        wp_safe_redirect(AccountPage::customerUrl());
        exit;
    }

    public static function redirectLegacyTreuekonto(): void
    {
        if (! is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        if (! self::hasLoyaltyRole($user) && ! self::isShopCustomer($user)) {
            return;
        }

        $page_id = AccountPage::id();
        if ($page_id <= 0 || ! is_page($page_id)) {
            return;
        }

        $url = AccountPage::customerUrl();
        if (isset($_GET['itk-loyalty-message'])) {
            $message = sanitize_key(wp_unslash($_GET['itk-loyalty-message']));
            if ('' !== $message) {
                $url = add_query_arg('itk-loyalty-message', $message, $url);
            }
        }

        wp_safe_redirect($url);
        exit;
    }

    public static function protectLinkedAccountEmail(\WP_Error $errors, $user): void
    {
        $user_id = is_object($user) && isset($user->ID) ? (int) $user->ID : 0;
        if ($user_id <= 0) {
            return;
        }

        $member = (new MemberRepository())->findByWpUserId($user_id);
        if (! $member) {
            return;
        }

        $submitted_email = is_object($user) && isset($user->user_email)
            ? MemberRepository::normalizeEmail((string) $user->user_email)
            : '';
        $loyalty_email = MemberRepository::normalizeEmail((string) $member['email']);

        if ('' !== $submitted_email && $submitted_email !== $loyalty_email) {
            $errors->add(
                'itk_loyalty_email_managed',
                __('Bitte ändere deine E-Mail-Adresse im Bereich Treuekonto. Dort wird die neue Adresse sicher bestätigt und anschließend für dein gesamtes Konto übernommen.', 'it-kayali-loyalty')
            );
        }
    }

    public static function enqueueAssets(): void
    {
        if (
            ! function_exists('is_account_page')
            || ! is_account_page()
            || ! function_exists('is_wc_endpoint_url')
            || ! is_wc_endpoint_url(self::ENDPOINT)
        ) {
            return;
        }

        wp_enqueue_style(
            'itk-loyalty-account',
            ITK_LOYALTY_URL . 'public/css/account.css',
            array(),
            ITK_LOYALTY_VERSION
        );
    }

    private static function hasLoyaltyRole(\WP_User $user): bool
    {
        return in_array(RoleManager::CUSTOMER_ROLE, (array) $user->roles, true);
    }

    private static function isShopCustomer(\WP_User $user): bool
    {
        return in_array('customer', (array) $user->roles, true);
    }

    private static function isLoyaltyOnlyCustomer(\WP_User $user): bool
    {
        $roles = (array) $user->roles;

        return in_array(RoleManager::CUSTOMER_ROLE, $roles, true)
            && ! in_array('customer', $roles, true)
            && ! user_can($user, 'manage_woocommerce');
    }
}
