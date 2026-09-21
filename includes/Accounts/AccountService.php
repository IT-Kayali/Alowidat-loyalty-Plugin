<?php
/**
 * Customer account application service.
 *
 * @package ITKayali\Loyalty\Accounts
 */

namespace ITKayali\Loyalty\Accounts;

use ITKayali\Loyalty\Roles\RoleManager;
use ITKayali\Loyalty\Security\RateLimiter;

final class AccountService
{
    public function __construct(
        private MemberRepository $members,
        private TokenRepository $tokens,
        private RateLimiter $rateLimiter,
        private ?Mailer $mailer = null
    ) {
        $this->mailer ??= new Mailer();
    }

    public function register(string $name, string $email): bool|\WP_Error
    {
        $name  = trim(sanitize_text_field($name));
        $email = MemberRepository::normalizeEmail($email);

        if ($this->stringLength($name) < 2 || $this->stringLength($name) > 191) {
            return new \WP_Error('invalid_name', __('Bitte gib einen gültigen Namen ein.', 'it-kayali-loyalty'));
        }

        if (! is_email($email)) {
            return new \WP_Error('invalid_email', __('Bitte gib eine gültige E-Mail-Adresse ein.', 'it-kayali-loyalty'));
        }

        if (! $this->rateLimiter->allow('register', $email, 5, HOUR_IN_SECONDS)) {
            return new \WP_Error('rate_limited', __('Zu viele Anfragen. Bitte versuche es später erneut.', 'it-kayali-loyalty'));
        }

        if ($this->members->findByEmail($email) || $this->members->findByPendingEmail($email)) {
            return new \WP_Error('loyalty_exists', __('Für diese E-Mail-Adresse besteht bereits ein Treuekonto.', 'it-kayali-loyalty'));
        }

        if (email_exists($email)) {
            return new \WP_Error('wp_user_exists', __('Für diese E-Mail-Adresse besteht bereits ein Benutzerkonto. Bitte melde dich zuerst an.', 'it-kayali-loyalty'));
        }

        $username = $this->uniqueUsername($email);
        $password = wp_generate_password(40, true, true);
        $user_id  = wp_insert_user(
            array(
                'user_login'   => $username,
                'user_pass'    => $password,
                'user_email'   => $email,
                'display_name' => $name,
                'role'         => RoleManager::CUSTOMER_ROLE,
            )
        );

        if (is_wp_error($user_id)) {
            return new \WP_Error('user_create_failed', __('Das Benutzerkonto konnte nicht erstellt werden.', 'it-kayali-loyalty'));
        }

        $member_id = $this->members->create($name, $email, (int) $user_id);
        if (is_wp_error($member_id)) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user((int) $user_id);
            return $member_id;
        }

        $token = $this->tokens->create((int) $member_id, (int) $user_id, TokenRepository::TYPE_VERIFY_EMAIL, DAY_IN_SECONDS);
        if (is_wp_error($token)) {
            return $token;
        }

        if (! $this->mailer->sendVerification($email, $name, $token, true)) {
            return new \WP_Error('mail_failed', __('Das Konto wurde angelegt, aber die Bestätigungs-E-Mail konnte nicht versendet werden. Bitte fordere den Bestätigungslink erneut an.', 'it-kayali-loyalty'));
        }

        return true;
    }

    public function resendVerification(string $email): void
    {
        $email = MemberRepository::normalizeEmail($email);

        if (! is_email($email) || ! $this->rateLimiter->allow('resend_verification', $email, 4, HOUR_IN_SECONDS)) {
            return;
        }

        $member = $this->members->findByEmail($email);
        if (! $member || ! empty($member['email_verified_at']) || 'pending' !== (string) $member['status']) {
            return;
        }

        $user_id = $this->members->getWpUserId((int) $member['id']);
        if ($user_id <= 0) {
            return;
        }

        $token = $this->tokens->create((int) $member['id'], $user_id, TokenRepository::TYPE_VERIFY_EMAIL, DAY_IN_SECONDS);
        if (is_wp_error($token)) {
            return;
        }

        $this->mailer->sendVerification((string) $member['email'], (string) $member['name'], $token, true);
    }

    public function verificationRequiresPassword(string $token): bool|\WP_Error
    {
        $row = $this->tokens->findValid($token, TokenRepository::TYPE_VERIFY_EMAIL);
        if (! $row) {
            return new \WP_Error('invalid_token', __('Der Bestätigungslink ist ungültig oder abgelaufen.', 'it-kayali-loyalty'));
        }

        $user = get_user_by('id', (int) $row['wp_user_id']);
        if (! $user instanceof \WP_User) {
            return new \WP_Error('user_missing', __('Das Benutzerkonto wurde nicht gefunden.', 'it-kayali-loyalty'));
        }

        return $this->isLoyaltyOnlyUser($user);
    }

    public function completeVerificationWithPassword(string $token, string $password, string $password_confirm): int|\WP_Error
    {
        $password_result = $this->validateNewPassword($password, $password_confirm);
        if (is_wp_error($password_result)) {
            return $password_result;
        }

        $row = $this->tokens->findValid($token, TokenRepository::TYPE_VERIFY_EMAIL);
        if (! $row) {
            return new \WP_Error('invalid_token', __('Der Bestätigungslink ist ungültig oder abgelaufen.', 'it-kayali-loyalty'));
        }

        $user_id = (int) $row['wp_user_id'];
        $user    = get_user_by('id', $user_id);
        if (! $user instanceof \WP_User || ! $this->isLoyaltyOnlyUser($user)) {
            return new \WP_Error('password_setup_not_required', __('Für dieses Konto ist diese Passwort-Einrichtung nicht vorgesehen.', 'it-kayali-loyalty'));
        }

        $consumed = $this->tokens->consume($token, TokenRepository::TYPE_VERIFY_EMAIL);
        if (! $consumed) {
            return new \WP_Error('invalid_token', __('Der Bestätigungslink ist ungültig oder wurde bereits verwendet.', 'it-kayali-loyalty'));
        }

        $member_id = (int) $consumed['member_id'];
        if (! $this->members->markVerified($member_id)) {
            return new \WP_Error('verification_failed', __('Die E-Mail-Adresse konnte nicht bestätigt werden.', 'it-kayali-loyalty'));
        }

        wp_set_password($password, $user_id);
        clean_user_cache($user_id);

        if (! $this->loginUser($user_id)) {
            return new \WP_Error('login_failed', __('Die Anmeldung konnte nicht abgeschlossen werden.', 'it-kayali-loyalty'));
        }

        return $user_id;
    }

    public function isPasswordSetupTokenValid(string $token): bool
    {
        return null !== $this->tokens->findValid($token, TokenRepository::TYPE_PASSWORD_SETUP);
    }

    public function setPasswordFromToken(string $token, string $password, string $password_confirm): int|\WP_Error
    {
        $password_result = $this->validateNewPassword($password, $password_confirm);
        if (is_wp_error($password_result)) {
            return $password_result;
        }

        $row = $this->tokens->consume($token, TokenRepository::TYPE_PASSWORD_SETUP);
        if (! $row) {
            return new \WP_Error('invalid_token', __('Der Passwort-Link ist ungültig oder abgelaufen.', 'it-kayali-loyalty'));
        }

        $member = $this->members->findById((int) $row['member_id']);
        if (! $member || 'active' !== (string) $member['status'] || empty($member['email_verified_at'])) {
            return new \WP_Error('inactive_member', __('Das Treuekonto ist nicht aktiv.', 'it-kayali-loyalty'));
        }

        $user_id = (int) $row['wp_user_id'];
        if (! get_user_by('id', $user_id)) {
            return new \WP_Error('user_missing', __('Das Benutzerkonto wurde nicht gefunden.', 'it-kayali-loyalty'));
        }

        wp_set_password($password, $user_id);
        clean_user_cache($user_id);

        if (! $this->loginUser($user_id)) {
            return new \WP_Error('login_failed', __('Die Anmeldung konnte nicht abgeschlossen werden.', 'it-kayali-loyalty'));
        }

        return $user_id;
    }

    public function verifyEmail(string $token): int|\WP_Error
    {
        $row = $this->tokens->consume($token, TokenRepository::TYPE_VERIFY_EMAIL);
        if (! $row) {
            return new \WP_Error('invalid_token', __('Der Bestätigungslink ist ungültig oder abgelaufen.', 'it-kayali-loyalty'));
        }

        $member_id = (int) $row['member_id'];
        if (! $this->members->markVerified($member_id)) {
            return new \WP_Error('verification_failed', __('Die E-Mail-Adresse konnte nicht bestätigt werden.', 'it-kayali-loyalty'));
        }

        $user_id = (int) $row['wp_user_id'];
        $this->loginUser($user_id);

        return $user_id;
    }


    public function passwordLogin(string $identifier, string $password): \WP_User|\WP_Error
    {
        $identifier = trim(sanitize_text_field($identifier));

        if ('' === $identifier || '' === $password) {
            return new \WP_Error('login_failed', __('Anmeldung fehlgeschlagen.', 'it-kayali-loyalty'));
        }

        if (! $this->rateLimiter->allow('password_login', $identifier, 10, 15 * MINUTE_IN_SECONDS)) {
            return new \WP_Error('rate_limited', __('Zu viele Anmeldeversuche. Bitte versuche es später erneut.', 'it-kayali-loyalty'));
        }

        $user = wp_signon(
            array(
                'user_login'    => $identifier,
                'user_password' => $password,
                'remember'      => true,
            ),
            is_ssl()
        );

        if (is_wp_error($user)) {
            return $user;
        }

        $member = $this->members->findByWpUserId((int) $user->ID);
        if (
            $member
            && ('active' !== (string) $member['status'] || empty($member['email_verified_at']))
            && $this->isLoyaltyOnlyUser($user)
        ) {
            wp_logout();
            return new \WP_Error('email_unverified', __('Bitte bestätige zuerst deine E-Mail-Adresse.', 'it-kayali-loyalty'));
        }

        return $user;
    }

    public function requestMagicLink(string $email): void
    {
        $email = MemberRepository::normalizeEmail($email);

        if (! is_email($email) || ! $this->rateLimiter->allow('magic_link', $email, 5, 15 * MINUTE_IN_SECONDS)) {
            return;
        }

        $member = $this->members->findByEmail($email);
        if (! $member || empty($member['email_verified_at']) || 'active' !== (string) $member['status']) {
            return;
        }

        $user_id = $this->members->getWpUserId((int) $member['id']);
        if ($user_id <= 0 || ! get_user_by('id', $user_id)) {
            return;
        }

        $token = $this->tokens->create((int) $member['id'], $user_id, TokenRepository::TYPE_MAGIC_LOGIN, 15 * MINUTE_IN_SECONDS);
        if (is_wp_error($token)) {
            return;
        }

        $password_token = $this->tokens->create(
            (int) $member['id'],
            $user_id,
            TokenRepository::TYPE_PASSWORD_SETUP,
            HOUR_IN_SECONDS
        );

        $this->mailer->sendMagicLink(
            (string) $member['email'],
            (string) $member['name'],
            $token,
            is_wp_error($password_token) ? '' : $password_token
        );
    }

    public function magicLogin(string $token): int|\WP_Error
    {
        $row = $this->tokens->consume($token, TokenRepository::TYPE_MAGIC_LOGIN);
        if (! $row) {
            return new \WP_Error('invalid_token', __('Der Login-Link ist ungültig oder abgelaufen.', 'it-kayali-loyalty'));
        }

        $member = $this->members->findById((int) $row['member_id']);
        if (! $member || 'active' !== (string) $member['status'] || empty($member['email_verified_at'])) {
            return new \WP_Error('inactive_member', __('Das Treuekonto ist nicht aktiv.', 'it-kayali-loyalty'));
        }

        $user_id = (int) $row['wp_user_id'];
        if (! $this->loginUser($user_id)) {
            return new \WP_Error('login_failed', __('Die Anmeldung konnte nicht abgeschlossen werden.', 'it-kayali-loyalty'));
        }

        return $user_id;
    }

    public function updateProfile(int $user_id, string $name, string $new_email): bool|\WP_Error
    {
        $member = $this->members->findByWpUserId($user_id);
        if (! $member) {
            return new \WP_Error('member_missing', __('Dieses Benutzerkonto ist nicht mit einem Treuekonto verbunden.', 'it-kayali-loyalty'));
        }

        $name = trim(sanitize_text_field($name));
        if ($this->stringLength($name) < 2 || $this->stringLength($name) > 191) {
            return new \WP_Error('invalid_name', __('Bitte gib einen gültigen Namen ein.', 'it-kayali-loyalty'));
        }

        if (! $this->members->updateName((int) $member['id'], $name)) {
            return new \WP_Error('name_update_failed', __('Der Name konnte nicht gespeichert werden.', 'it-kayali-loyalty'));
        }

        wp_update_user(array('ID' => $user_id, 'display_name' => $name));

        $new_email = MemberRepository::normalizeEmail($new_email);
        $old_email = MemberRepository::normalizeEmail((string) $member['email']);
        if ($new_email === $old_email) {
            return true;
        }

        if (! is_email($new_email)) {
            return new \WP_Error('invalid_email', __('Bitte gib eine gültige E-Mail-Adresse ein.', 'it-kayali-loyalty'));
        }

        $other_member = $this->members->findByEmail($new_email);
        if ($other_member && (int) $other_member['id'] !== (int) $member['id']) {
            return new \WP_Error('email_in_use', __('Diese E-Mail-Adresse wird bereits verwendet.', 'it-kayali-loyalty'));
        }

        $pending_member = $this->members->findByPendingEmail($new_email);
        if ($pending_member && (int) $pending_member['id'] !== (int) $member['id']) {
            return new \WP_Error('email_in_use', __('Diese E-Mail-Adresse wird bereits verwendet.', 'it-kayali-loyalty'));
        }

        $existing_user = email_exists($new_email);
        if ($existing_user && (int) $existing_user !== $user_id) {
            return new \WP_Error('email_in_use', __('Diese E-Mail-Adresse wird bereits verwendet.', 'it-kayali-loyalty'));
        }

        if (! $this->rateLimiter->allow('email_change', (string) $member['id'], 4, HOUR_IN_SECONDS)) {
            return new \WP_Error('rate_limited', __('Zu viele E-Mail-Änderungen. Bitte versuche es später erneut.', 'it-kayali-loyalty'));
        }

        if (! $this->members->setPendingEmail((int) $member['id'], $new_email)) {
            return new \WP_Error('email_change_failed', __('Die neue E-Mail-Adresse konnte nicht vorgemerkt werden.', 'it-kayali-loyalty'));
        }

        $token = $this->tokens->create(
            (int) $member['id'],
            $user_id,
            TokenRepository::TYPE_EMAIL_CHANGE,
            DAY_IN_SECONDS,
            array('new_email' => $new_email)
        );

        if (is_wp_error($token)) {
            return $token;
        }

        if (! $this->mailer->sendEmailChangeVerification($new_email, $name, $token)) {
            return new \WP_Error('mail_failed', __('Die Änderung wurde vorgemerkt, aber die Bestätigungs-E-Mail konnte nicht versendet werden.', 'it-kayali-loyalty'));
        }

        return true;
    }

    public function confirmEmailChange(string $token): bool|\WP_Error
    {
        $row = $this->tokens->consume($token, TokenRepository::TYPE_EMAIL_CHANGE);
        if (! $row) {
            return new \WP_Error('invalid_token', __('Der Bestätigungslink ist ungültig oder abgelaufen.', 'it-kayali-loyalty'));
        }

        $metadata = json_decode((string) ($row['metadata'] ?? ''), true);
        $new_email = is_array($metadata) && isset($metadata['new_email']) ? MemberRepository::normalizeEmail((string) $metadata['new_email']) : '';
        if (! is_email($new_email)) {
            return new \WP_Error('invalid_email', __('Die vorgemerkte E-Mail-Adresse ist ungültig.', 'it-kayali-loyalty'));
        }

        $member_id = (int) $row['member_id'];
        $member = $this->members->findById($member_id);
        if (! $member || MemberRepository::normalizeEmail((string) ($member['pending_email'] ?? '')) !== $new_email) {
            return new \WP_Error('email_change_stale', __('Diese E-Mail-Änderung ist nicht mehr aktuell.', 'it-kayali-loyalty'));
        }

        $other_member = $this->members->findByEmail($new_email);
        if ($other_member && (int) $other_member['id'] !== $member_id) {
            return new \WP_Error('email_in_use', __('Diese E-Mail-Adresse wird bereits verwendet.', 'it-kayali-loyalty'));
        }

        $user_id = (int) $row['wp_user_id'];
        $existing_user = email_exists($new_email);
        if ($existing_user && (int) $existing_user !== $user_id) {
            return new \WP_Error('email_in_use', __('Diese E-Mail-Adresse wird bereits verwendet.', 'it-kayali-loyalty'));
        }

        $old_email = MemberRepository::normalizeEmail((string) $member['email']);
        $result = wp_update_user(array('ID' => $user_id, 'user_email' => $new_email));
        if (is_wp_error($result)) {
            return new \WP_Error('wp_email_change_failed', __('Die E-Mail-Adresse des Benutzerkontos konnte nicht aktualisiert werden.', 'it-kayali-loyalty'));
        }

        if (! $this->members->confirmPendingEmail($member_id, $new_email)) {
            wp_update_user(array('ID' => $user_id, 'user_email' => $old_email));
            clean_user_cache($user_id);
            return new \WP_Error('email_change_failed', __('Die neue E-Mail-Adresse konnte nicht übernommen werden.', 'it-kayali-loyalty'));
        }

        clean_user_cache($user_id);
        return true;
    }

    private function validateNewPassword(string $password, string $password_confirm): bool|\WP_Error
    {
        if ($password !== $password_confirm) {
            return new \WP_Error('password_mismatch', __('Die beiden Passwörter stimmen nicht überein.', 'it-kayali-loyalty'));
        }

        if (strlen($password) < 8) {
            return new \WP_Error('password_too_short', __('Das Passwort muss mindestens 8 Zeichen lang sein.', 'it-kayali-loyalty'));
        }

        return true;
    }

    private function isLoyaltyOnlyUser(\WP_User $user): bool
    {
        $roles = (array) $user->roles;

        return in_array(RoleManager::CUSTOMER_ROLE, $roles, true)
            && ! in_array('customer', $roles, true)
            && ! user_can($user, 'manage_woocommerce');
    }

    private function loginUser(int $user_id): bool
    {
        $user = get_user_by('id', $user_id);
        if (! $user instanceof \WP_User) {
            return false;
        }

        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true, is_ssl());
        do_action('wp_login', $user->user_login, $user);

        return true;
    }


    private function stringLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }

    private function uniqueUsername(string $email): string
    {
        $parts = explode('@', $email, 2);
        $base  = sanitize_user($parts[0] ?? 'loyalty', true);
        if ('' === $base) {
            $base = 'loyalty';
        }

        $candidate = $base;
        $attempt   = 0;
        while (username_exists($candidate)) {
            ++$attempt;
            $candidate = $base . '-' . strtolower(wp_generate_password(6, false, false));
            if ($attempt > 20) {
                $candidate = 'loyalty-' . wp_generate_uuid4();
                break;
            }
        }

        return substr($candidate, 0, 60);
    }
}
