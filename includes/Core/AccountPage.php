<?php
/**
 * Frontend account page lifecycle.
 *
 * @package ITKayali\Loyalty\Core
 */

namespace ITKayali\Loyalty\Core;

final class AccountPage
{
    private const OPTION = 'itk_loyalty_account_page_id';

    public static function ensure(): void
    {
        if (wp_installing() || wp_doing_cron()) {
            return;
        }

        $existing_id = (int) get_option(self::OPTION, 0);

        if ($existing_id > 0 && 'trash' !== get_post_status($existing_id) && null !== get_post($existing_id)) {
            return;
        }

        $page = get_page_by_path('treuekonto');
        if ($page instanceof \WP_Post && has_shortcode((string) $page->post_content, 'itk_loyalty_account')) {
            update_option(self::OPTION, (int) $page->ID, false);
            return;
        }

        $page_id = wp_insert_post(
            array(
                'post_title'   => __('Treuekonto', 'it-kayali-loyalty'),
                'post_name'    => 'treuekonto',
                'post_content' => '[itk_loyalty_account]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'comment_status' => 'closed',
            ),
            true
        );

        if (! is_wp_error($page_id) && $page_id > 0) {
            update_option(self::OPTION, (int) $page_id, false);
        }
    }

    public static function id(): int
    {
        return (int) get_option(self::OPTION, 0);
    }

    public static function url(): string
    {
        $page_id = self::id();
        if ($page_id > 0) {
            $url = get_permalink($page_id);
            if (is_string($url) && '' !== $url) {
                return $url;
            }
        }

        return home_url('/treuekonto/');
    }

    public static function customerUrl(): string
    {
        if (
            class_exists('WooCommerce')
            && function_exists('wc_get_page_permalink')
            && function_exists('wc_get_endpoint_url')
        ) {
            $base = wc_get_page_permalink('myaccount');
            if (is_string($base) && '' !== $base) {
                return wc_get_endpoint_url('treuekonto', '', $base);
            }
        }

        return self::url();
    }
}
