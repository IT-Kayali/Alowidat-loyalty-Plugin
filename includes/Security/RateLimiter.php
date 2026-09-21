<?php
/**
 * Lightweight rate limiter for public account actions.
 *
 * @package ITKayali\Loyalty\Security
 */

namespace ITKayali\Loyalty\Security;

final class RateLimiter
{
    public function allow(string $action, string $identity, int $limit = 5, int $window = 900): bool
    {
        $ip  = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $key = 'itk_lr_' . hash_hmac('sha256', $action . '|' . strtolower(trim($identity)) . '|' . $ip, wp_salt('nonce'));

        $state = get_transient($key);
        if (! is_array($state) || ! isset($state['count'])) {
            set_transient($key, array('count' => 1), $window);
            return true;
        }

        $count = (int) $state['count'];
        if ($count >= $limit) {
            return false;
        }

        $state['count'] = $count + 1;
        set_transient($key, $state, $window);

        return true;
    }
}
