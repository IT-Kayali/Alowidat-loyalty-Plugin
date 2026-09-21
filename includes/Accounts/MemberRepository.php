<?php
/**
 * Persistence for loyalty members and WordPress user links.
 *
 * @package ITKayali\Loyalty\Accounts
 */

namespace ITKayali\Loyalty\Accounts;

use ITKayali\Loyalty\Database\TableNames;

final class MemberRepository
{
    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim(sanitize_email($email)));
    }

    public function findByEmail(string $email): ?array
    {
        global $wpdb;

        $normalized = self::normalizeEmail($email);
        if ('' === $normalized) {
            return null;
        }

        $sql = $wpdb->prepare(
            'SELECT * FROM ' . TableNames::members() . ' WHERE email_normalized = %s LIMIT 1',
            $normalized
        );

        $row = $wpdb->get_row($sql, ARRAY_A);

        return is_array($row) ? $row : null;
    }

    public function findByPendingEmail(string $email): ?array
    {
        global $wpdb;

        $normalized = self::normalizeEmail($email);
        if ('' === $normalized) {
            return null;
        }

        $sql = $wpdb->prepare(
            'SELECT * FROM ' . TableNames::members() . ' WHERE pending_email_normalized = %s LIMIT 1',
            $normalized
        );

        $row = $wpdb->get_row($sql, ARRAY_A);

        return is_array($row) ? $row : null;
    }

    public function findById(int $member_id): ?array
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            'SELECT * FROM ' . TableNames::members() . ' WHERE id = %d LIMIT 1',
            $member_id
        );

        $row = $wpdb->get_row($sql, ARRAY_A);

        return is_array($row) ? $row : null;
    }

    public function findByWpUserId(int $user_id): ?array
    {
        global $wpdb;

        $members = TableNames::members();
        $links   = TableNames::userLinks();

        $sql = $wpdb->prepare(
            "SELECT m.* FROM {$members} m INNER JOIN {$links} l ON l.member_id = m.id WHERE l.wp_user_id = %d LIMIT 1",
            $user_id
        );

        $row = $wpdb->get_row($sql, ARRAY_A);

        return is_array($row) ? $row : null;
    }

    public function getWpUserId(int $member_id): int
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            'SELECT wp_user_id FROM ' . TableNames::userLinks() . ' WHERE member_id = %d LIMIT 1',
            $member_id
        );

        return (int) $wpdb->get_var($sql);
    }

    public function create(string $name, string $email, int $wp_user_id): int|\WP_Error
    {
        global $wpdb;

        $now        = current_time('mysql', true);
        $normalized = self::normalizeEmail($email);
        $member_uuid = wp_generate_uuid4();

        $inserted = $wpdb->insert(
            TableNames::members(),
            array(
                'member_uuid'     => $member_uuid,
                'name'            => $name,
                'email'           => $email,
                'email_normalized'=> $normalized,
                'status'          => 'pending',
                'created_at'      => $now,
                'updated_at'      => $now,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            return new \WP_Error('member_insert_failed', __('Das Treuekonto konnte nicht angelegt werden.', 'it-kayali-loyalty'));
        }

        $member_id = (int) $wpdb->insert_id;
        $linked = $wpdb->insert(
            TableNames::userLinks(),
            array(
                'member_id'  => $member_id,
                'wp_user_id' => $wp_user_id,
                'link_type'  => 'loyalty',
                'linked_at'  => $now,
            ),
            array('%d', '%d', '%s', '%s')
        );

        if (false === $linked) {
            $wpdb->delete(TableNames::members(), array('id' => $member_id), array('%d'));
            return new \WP_Error('member_link_failed', __('Das Treuekonto konnte nicht mit dem Benutzerkonto verbunden werden.', 'it-kayali-loyalty'));
        }

        return $member_id;
    }

    public function markVerified(int $member_id): bool
    {
        global $wpdb;

        $now = current_time('mysql', true);
        $updated = $wpdb->update(
            TableNames::members(),
            array(
                'email_verified_at' => $now,
                'status'            => 'active',
                'updated_at'        => $now,
            ),
            array('id' => $member_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        return false !== $updated;
    }

    public function updateName(int $member_id, string $name): bool
    {
        global $wpdb;

        $updated = $wpdb->update(
            TableNames::members(),
            array(
                'name'       => $name,
                'updated_at' => current_time('mysql', true),
            ),
            array('id' => $member_id),
            array('%s', '%s'),
            array('%d')
        );

        return false !== $updated;
    }

    public function setPendingEmail(int $member_id, string $email): bool
    {
        global $wpdb;

        $updated = $wpdb->update(
            TableNames::members(),
            array(
                'pending_email'              => $email,
                'pending_email_normalized'   => self::normalizeEmail($email),
                'pending_email_requested_at' => current_time('mysql', true),
                'updated_at'                 => current_time('mysql', true),
            ),
            array('id' => $member_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );

        return false !== $updated;
    }

    public function confirmPendingEmail(int $member_id, string $expected_email): bool
    {
        global $wpdb;

        $member = $this->findById($member_id);
        if (! $member || self::normalizeEmail($expected_email) !== (string) $member['pending_email_normalized']) {
            return false;
        }

        $updated = $wpdb->update(
            TableNames::members(),
            array(
                'email'                      => $expected_email,
                'email_normalized'           => self::normalizeEmail($expected_email),
                'pending_email'              => null,
                'pending_email_normalized'   => null,
                'pending_email_requested_at' => null,
                'email_verified_at'          => current_time('mysql', true),
                'updated_at'                 => current_time('mysql', true),
            ),
            array('id' => $member_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        return false !== $updated;
    }

    public function balance(int $member_id): int
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            'SELECT COALESCE(SUM(points_delta), 0) FROM ' . TableNames::ledger() . ' WHERE member_id = %d',
            $member_id
        );

        return (int) $wpdb->get_var($sql);
    }
}
