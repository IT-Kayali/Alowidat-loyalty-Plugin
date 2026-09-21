<?php
/**
 * One-time token persistence for verification and Magic Links.
 *
 * @package ITKayali\Loyalty\Accounts
 */

namespace ITKayali\Loyalty\Accounts;

use ITKayali\Loyalty\Database\TableNames;

final class TokenRepository
{
    public const TYPE_VERIFY_EMAIL = 'verify_email';
    public const TYPE_MAGIC_LOGIN = 'magic_login';
    public const TYPE_EMAIL_CHANGE = 'email_change';
    public const TYPE_PASSWORD_SETUP = 'password_setup';

    public function create(int $member_id, int $wp_user_id, string $type, int $ttl_seconds, array $metadata = array()): string|\WP_Error
    {
        global $wpdb;

        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Throwable $exception) {
            $token = wp_generate_password(64, false, false);
        }

        $token_hash = hash('sha256', $token);
        $now_ts     = time();
        $now        = gmdate('Y-m-d H:i:s', $now_ts);
        $expires    = gmdate('Y-m-d H:i:s', $now_ts + max(60, $ttl_seconds));

        $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . TableNames::tokens() . ' WHERE member_id = %d AND type = %s AND used_at IS NULL',
                $member_id,
                $type
            )
        );

        $inserted = $wpdb->insert(
            TableNames::tokens(),
            array(
                'token_hash' => $token_hash,
                'member_id'  => $member_id,
                'wp_user_id' => $wp_user_id,
                'type'       => $type,
                'metadata'   => empty($metadata) ? null : wp_json_encode($metadata),
                'expires_at' => $expires,
                'used_at'    => null,
                'created_at' => $now,
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            return new \WP_Error('token_insert_failed', __('Der Sicherheitslink konnte nicht erstellt werden.', 'it-kayali-loyalty'));
        }

        return $token;
    }

    public function findValid(string $token, string $type): ?array
    {
        global $wpdb;

        if (strlen($token) < 32 || strlen($token) > 128) {
            return null;
        }

        $hash = hash('sha256', $token);
        $now  = gmdate('Y-m-d H:i:s');

        $sql = $wpdb->prepare(
            'SELECT * FROM ' . TableNames::tokens() . ' WHERE token_hash = %s AND type = %s AND used_at IS NULL AND expires_at >= %s LIMIT 1',
            $hash,
            $type,
            $now
        );

        $row = $wpdb->get_row($sql, ARRAY_A);

        return is_array($row) ? $row : null;
    }

    public function consume(string $token, string $type): ?array
    {
        global $wpdb;

        if (strlen($token) < 32 || strlen($token) > 128) {
            return null;
        }

        $hash = hash('sha256', $token);
        $now  = gmdate('Y-m-d H:i:s');

        $sql = $wpdb->prepare(
            'SELECT * FROM ' . TableNames::tokens() . ' WHERE token_hash = %s AND type = %s AND used_at IS NULL AND expires_at >= %s LIMIT 1',
            $hash,
            $type,
            $now
        );

        $row = $wpdb->get_row($sql, ARRAY_A);
        if (! is_array($row)) {
            return null;
        }

        $updated = $wpdb->query(
            $wpdb->prepare(
                'UPDATE ' . TableNames::tokens() . ' SET used_at = %s WHERE id = %d AND used_at IS NULL',
                $now,
                (int) $row['id']
            )
        );

        if (1 !== $updated) {
            return null;
        }

        return $row;
    }
}
