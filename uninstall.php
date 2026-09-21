<?php
/**
 * Uninstall routine for IT-Kayali Loyalty.
 *
 * Loyalty data is preserved by default. A future admin setting may set the
 * option `itk_loyalty_delete_data_on_uninstall` to `1` to explicitly request
 * destructive cleanup.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

if ('1' !== (string) get_option('itk_loyalty_delete_data_on_uninstall', '0')) {
    return;
}

global $wpdb;

$tables = array(
    $wpdb->prefix . 'itk_loyalty_tokens',
    $wpdb->prefix . 'itk_loyalty_external_refs',
    $wpdb->prefix . 'itk_loyalty_reservations',
    $wpdb->prefix . 'itk_loyalty_redemptions',
    $wpdb->prefix . 'itk_loyalty_ledger',
    $wpdb->prefix . 'itk_loyalty_user_links',
    $wpdb->prefix . 'itk_loyalty_cards',
    $wpdb->prefix . 'itk_loyalty_branches',
    $wpdb->prefix . 'itk_loyalty_members',
);

foreach ($tables as $table) {
    $wpdb->query('DROP TABLE IF EXISTS ' . esc_sql($table)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

$custom_capabilities = array(
    'itk_loyalty_view_dashboard',
    'itk_loyalty_manage_members',
    'itk_loyalty_manage_cards',
    'itk_loyalty_view_ledger',
    'itk_loyalty_adjust_points',
    'itk_loyalty_manage_rewards',
    'itk_loyalty_manage_staff',
    'itk_loyalty_manage_branches',
    'itk_loyalty_manage_rules',
    'itk_loyalty_manage_settings',
    'itk_loyalty_scan_members',
    'itk_loyalty_add_points',
    'itk_loyalty_redeem_rewards',
);

$administrator = get_role('administrator');
if ($administrator) {
    foreach ($custom_capabilities as $capability) {
        $administrator->remove_cap($capability);
    }
}

remove_role('itk_loyalty_customer');
remove_role('itk_loyalty_staff');

$page_id = (int) get_option('itk_loyalty_account_page_id', 0);
if ($page_id > 0) {
    wp_delete_post($page_id, true);
}

delete_option('itk_loyalty_version');
delete_option('itk_loyalty_schema_version');
delete_option('itk_loyalty_activated_at');
delete_option('itk_loyalty_account_page_id');
delete_option('itk_loyalty_delete_data_on_uninstall');
