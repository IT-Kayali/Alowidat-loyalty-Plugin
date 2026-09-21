<?php
/**
 * Database schema and migration entry point.
 *
 * @package ITKayali\Loyalty\Database
 */

namespace ITKayali\Loyalty\Database;

final class Schema
{
    private const OPTION = 'itk_loyalty_schema_version';

    public static function maybe_upgrade(): void
    {
        $installed = (string) get_option(self::OPTION, '');

        if (ITK_LOYALTY_SCHEMA_VERSION !== $installed) {
            self::install();
        }
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $tables          = TableNames::all();

        $sql = array();

        $sql[] = "CREATE TABLE {$tables['members']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            member_uuid char(36) NOT NULL,
            name varchar(191) NOT NULL,
            email varchar(190) NOT NULL,
            email_normalized varchar(190) NOT NULL,
            email_verified_at datetime NULL,
            status varchar(32) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY member_uuid (member_uuid),
            UNIQUE KEY email_normalized (email_normalized),
            KEY status (status)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$tables['cards']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            member_id bigint(20) unsigned NOT NULL,
            card_number varchar(64) NOT NULL,
            qr_token_hash char(64) NOT NULL,
            status varchar(32) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            renewed_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY member_card (member_id),
            UNIQUE KEY card_number (card_number),
            UNIQUE KEY qr_token_hash (qr_token_hash),
            KEY status (status)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$tables['user_links']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            member_id bigint(20) unsigned NOT NULL,
            wp_user_id bigint(20) unsigned NOT NULL,
            link_type varchar(32) NOT NULL DEFAULT 'loyalty',
            linked_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY member_id (member_id),
            UNIQUE KEY wp_user_id (wp_user_id),
            KEY link_type (link_type)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$tables['branches']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            branch_uuid char(36) NOT NULL,
            code varchar(64) NOT NULL,
            name varchar(191) NOT NULL,
            status varchar(32) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY branch_uuid (branch_uuid),
            UNIQUE KEY code (code),
            KEY status (status)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$tables['ledger']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            transaction_uuid char(36) NOT NULL,
            member_id bigint(20) unsigned NOT NULL,
            points_delta int(11) NOT NULL,
            type varchar(64) NOT NULL,
            source varchar(64) NOT NULL,
            order_id bigint(20) unsigned NULL,
            receipt_reference varchar(191) NULL,
            idempotency_key char(64) NULL,
            branch_id bigint(20) unsigned NULL,
            staff_user_id bigint(20) unsigned NULL,
            reference_transaction_id bigint(20) unsigned NULL,
            metadata longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY transaction_uuid (transaction_uuid),
            UNIQUE KEY idempotency_key (idempotency_key),
            KEY member_created (member_id, created_at),
            KEY order_id (order_id),
            KEY receipt_reference (receipt_reference),
            KEY reference_transaction_id (reference_transaction_id)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$tables['redemptions']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            redemption_uuid char(36) NOT NULL,
            member_id bigint(20) unsigned NOT NULL,
            ledger_transaction_id bigint(20) unsigned NULL,
            reward_type varchar(64) NOT NULL,
            product_id bigint(20) unsigned NULL,
            points_used int(11) NOT NULL,
            status varchar(32) NOT NULL DEFAULT 'completed',
            source varchar(64) NOT NULL,
            branch_id bigint(20) unsigned NULL,
            staff_user_id bigint(20) unsigned NULL,
            metadata longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY redemption_uuid (redemption_uuid),
            KEY member_created (member_id, created_at),
            KEY ledger_transaction_id (ledger_transaction_id),
            KEY product_id (product_id)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$tables['reservations']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reservation_uuid char(36) NOT NULL,
            member_id bigint(20) unsigned NOT NULL,
            points_reserved int(11) NOT NULL,
            status varchar(32) NOT NULL DEFAULT 'active',
            source varchar(64) NOT NULL,
            order_id bigint(20) unsigned NULL,
            expires_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY reservation_uuid (reservation_uuid),
            KEY member_status (member_id, status),
            KEY order_id (order_id),
            KEY expires_at (expires_at)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$tables['external_refs']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            external_key char(64) NOT NULL,
            system varchar(64) NOT NULL,
            external_type varchar(64) NOT NULL,
            external_id varchar(191) NOT NULL,
            local_type varchar(64) NOT NULL,
            local_id bigint(20) unsigned NOT NULL,
            metadata longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY external_key (external_key),
            KEY system_type (system, external_type),
            KEY local_reference (local_type, local_id)
        ) {$charset_collate};";

        foreach ($sql as $statement) {
            dbDelta($statement);
        }

        update_option(self::OPTION, ITK_LOYALTY_SCHEMA_VERSION, false);
    }
}
