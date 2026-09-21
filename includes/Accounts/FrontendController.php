<?php
/**
 * Shortcodes and public customer account actions.
 *
 * @package ITKayali\Loyalty\Accounts
 */

namespace ITKayali\Loyalty\Accounts;

use ITKayali\Loyalty\Core\AccountPage;

final class FrontendController
{
    public function __construct(
        private AccountService $service,
        private MemberRepository $members
    ) {
    }

    public function register(): void
    {
        add_action('init', array($this, 'handleRequest'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueueAssets'));

        add_shortcode('itk_loyalty_account', array($this, 'renderAccount'));
        add_shortcode('itk_loyalty_login', array($this, 'renderLogin'));
        add_shortcode('itk_loyalty_register', array($this, 'renderRegister'));
    }

    public function enqueueAssets(): void
    {
        $page_id = AccountPage::id();
        $should_enqueue = $page_id > 0 && is_page($page_id);

        if (! $should_enqueue) {
            global $post;
            if ($post instanceof \WP_Post) {
                $content = (string) $post->post_content;
                $should_enqueue = has_shortcode($content, 'itk_loyalty_account')
                    || has_shortcode($content, 'itk_loyalty_login')
                    || has_shortcode($content, 'itk_loyalty_register');
            }
        }

        if (! $should_enqueue) {
            return;
        }

        wp_enqueue_style(
            'itk-loyalty-account',
            ITK_LOYALTY_URL . 'public/css/account.css',
            array(),
            ITK_LOYALTY_VERSION
        );
    }

    public function handleRequest(): void
    {
        $this->handleLinkAction();

        if ('POST' !== strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''))) {
            return;
        }

        $action = isset($_POST['itk_loyalty_action']) ? sanitize_key(wp_unslash($_POST['itk_loyalty_action'])) : '';
        if ('' === $action) {
            return;
        }

        switch ($action) {
            case 'register':
                $this->handleRegistration();
                break;
            case 'password_login':
                $this->handlePasswordLogin();
                break;
            case 'magic_link':
                $this->handleMagicLinkRequest();
                break;
            case 'resend_verification':
                $this->handleResendVerification();
                break;
            case 'update_profile':
                $this->handleProfileUpdate();
                break;
        }
    }

    public function renderAccount(): string
    {
        if (! is_user_logged_in()) {
            return $this->wrap(
                $this->renderMessages()
                . '<div class="itk-loyalty-grid">'
                . '<section class="itk-loyalty-panel">' . $this->renderLoginForms() . '</section>'
                . '<section class="itk-loyalty-panel">' . $this->renderRegisterForm() . '</section>'
                . '</div>'
            );
        }

        $member = $this->members->findByWpUserId(get_current_user_id());
        if (! $member) {
            return $this->wrap(
                $this->renderMessages()
                . '<section class="itk-loyalty-panel">'
                . '<h2>' . esc_html__('Treueprogramm', 'it-kayali-loyalty') . '</h2>'
                . '<p>' . esc_html__('Dieses Benutzerkonto ist noch nicht mit dem Treueprogramm verbunden. Die freiwillige Aktivierung für bestehende Shop-Konten folgt in der WooCommerce-Phase.', 'it-kayali-loyalty') . '</p>'
                . '<p><a class="itk-loyalty-button itk-loyalty-button-secondary" href="' . esc_url(wp_logout_url(AccountPage::url())) . '">' . esc_html__('Abmelden', 'it-kayali-loyalty') . '</a></p>'
                . '</section>'
            );
        }

        if ('active' !== (string) $member['status'] || empty($member['email_verified_at'])) {
            return $this->wrap($this->renderMessages() . $this->renderVerificationPending($member));
        }

        return $this->wrap($this->renderMessages() . $this->renderDashboard($member));
    }

    public function renderLogin(): string
    {
        if (is_user_logged_in()) {
            return '<p><a href="' . esc_url(AccountPage::url()) . '">' . esc_html__('Zum Treuekonto', 'it-kayali-loyalty') . '</a></p>';
        }

        return $this->wrap($this->renderMessages() . '<section class="itk-loyalty-panel">' . $this->renderLoginForms() . '</section>');
    }

    public function renderRegister(): string
    {
        if (is_user_logged_in()) {
            return '<p><a href="' . esc_url(AccountPage::url()) . '">' . esc_html__('Zum Treuekonto', 'it-kayali-loyalty') . '</a></p>';
        }

        return $this->wrap($this->renderMessages() . '<section class="itk-loyalty-panel">' . $this->renderRegisterForm() . '</section>');
    }

    private function handleLinkAction(): void
    {
        $action = isset($_GET['itk-loyalty-action']) ? sanitize_key(wp_unslash($_GET['itk-loyalty-action'])) : '';
        $token  = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
        if ('' === $action || '' === $token) {
            return;
        }

        if ('verify-email' === $action) {
            $result = $this->service->verifyEmail($token);
            $this->redirect(is_wp_error($result) ? 'invalid_link' : 'email_verified');
        }

        if ('magic-login' === $action) {
            $result = $this->service->magicLogin($token);
            $this->redirect(is_wp_error($result) ? 'invalid_link' : 'logged_in');
        }

        if ('verify-email-change' === $action) {
            $result = $this->service->confirmEmailChange($token);
            $this->redirect(is_wp_error($result) ? 'invalid_link' : 'email_changed');
        }
    }

    private function handleRegistration(): void
    {
        if (! isset($_POST['_itk_loyalty_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_itk_loyalty_nonce'])), 'itk_loyalty_register')) {
            $this->redirect('security_error');
        }

        $name  = isset($_POST['name']) ? wp_unslash($_POST['name']) : '';
        $email = isset($_POST['email']) ? wp_unslash($_POST['email']) : '';
        $result = $this->service->register((string) $name, (string) $email);

        if (is_wp_error($result)) {
            $code = 'mail_failed' === $result->get_error_code() ? 'registration_mail_failed' : 'registration_error';
            $this->redirect($code);
        }

        $this->redirect('registered');
    }

    private function handlePasswordLogin(): void
    {
        if (! isset($_POST['_itk_loyalty_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_itk_loyalty_nonce'])), 'itk_loyalty_login')) {
            $this->redirect('security_error');
        }

        $identifier = isset($_POST['identifier']) ? sanitize_text_field(wp_unslash($_POST['identifier'])) : '';
        $password   = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';

        $user = $this->service->passwordLogin($identifier, $password);
        $this->redirect(is_wp_error($user) ? 'login_error' : 'logged_in');
    }

    private function handleMagicLinkRequest(): void
    {
        if (! isset($_POST['_itk_loyalty_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_itk_loyalty_nonce'])), 'itk_loyalty_magic')) {
            $this->redirect('security_error');
        }

        $email = isset($_POST['email']) ? wp_unslash($_POST['email']) : '';
        $this->service->requestMagicLink((string) $email);
        $this->redirect('magic_requested');
    }

    private function handleResendVerification(): void
    {
        if (! isset($_POST['_itk_loyalty_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_itk_loyalty_nonce'])), 'itk_loyalty_resend')) {
            $this->redirect('security_error');
        }

        $email = isset($_POST['email']) ? wp_unslash($_POST['email']) : '';
        $this->service->resendVerification((string) $email);
        $this->redirect('verification_requested');
    }

    private function handleProfileUpdate(): void
    {
        if (! is_user_logged_in()) {
            $this->redirect('login_required');
        }

        if (! isset($_POST['_itk_loyalty_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_itk_loyalty_nonce'])), 'itk_loyalty_profile')) {
            $this->redirect('security_error');
        }

        $name  = isset($_POST['name']) ? wp_unslash($_POST['name']) : '';
        $email = isset($_POST['email']) ? wp_unslash($_POST['email']) : '';
        $result = $this->service->updateProfile(get_current_user_id(), (string) $name, (string) $email);

        if (is_wp_error($result)) {
            $code = 'mail_failed' === $result->get_error_code() ? 'profile_mail_failed' : 'profile_error';
            $this->redirect($code);
        }

        $member = $this->members->findByWpUserId(get_current_user_id());
        $pending = $member && ! empty($member['pending_email']);
        $this->redirect($pending ? 'email_change_pending' : 'profile_saved');
    }

    private function renderLoginForms(): string
    {
        ob_start();
        ?>
        <h2><?php echo esc_html__('Anmelden', 'it-kayali-loyalty'); ?></h2>
        <p class="itk-loyalty-muted"><?php echo esc_html__('Alle Kunden verwenden diesen gemeinsamen Login.', 'it-kayali-loyalty'); ?></p>

        <form method="post" class="itk-loyalty-form">
            <?php wp_nonce_field('itk_loyalty_login', '_itk_loyalty_nonce'); ?>
            <input type="hidden" name="itk_loyalty_action" value="password_login">
            <label>
                <span><?php echo esc_html__('E-Mail oder Benutzername', 'it-kayali-loyalty'); ?></span>
                <input type="text" name="identifier" autocomplete="username" required>
            </label>
            <label>
                <span><?php echo esc_html__('Passwort', 'it-kayali-loyalty'); ?></span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button type="submit" class="itk-loyalty-button"><?php echo esc_html__('Anmelden', 'it-kayali-loyalty'); ?></button>
        </form>

        <div class="itk-loyalty-divider"><span><?php echo esc_html__('oder', 'it-kayali-loyalty'); ?></span></div>

        <h3><?php echo esc_html__('Login-Link per E-Mail', 'it-kayali-loyalty'); ?></h3>
        <form method="post" class="itk-loyalty-form">
            <?php wp_nonce_field('itk_loyalty_magic', '_itk_loyalty_nonce'); ?>
            <input type="hidden" name="itk_loyalty_action" value="magic_link">
            <label>
                <span><?php echo esc_html__('E-Mail-Adresse', 'it-kayali-loyalty'); ?></span>
                <input type="email" name="email" autocomplete="email" required>
            </label>
            <button type="submit" class="itk-loyalty-button itk-loyalty-button-secondary"><?php echo esc_html__('Sicheren Login-Link senden', 'it-kayali-loyalty'); ?></button>
        </form>

        <details class="itk-loyalty-details">
            <summary><?php echo esc_html__('Bestätigungs-E-Mail nicht erhalten?', 'it-kayali-loyalty'); ?></summary>
            <form method="post" class="itk-loyalty-form">
                <?php wp_nonce_field('itk_loyalty_resend', '_itk_loyalty_nonce'); ?>
                <input type="hidden" name="itk_loyalty_action" value="resend_verification">
                <label>
                    <span><?php echo esc_html__('E-Mail-Adresse', 'it-kayali-loyalty'); ?></span>
                    <input type="email" name="email" autocomplete="email" required>
                </label>
                <button type="submit" class="itk-loyalty-button itk-loyalty-button-secondary"><?php echo esc_html__('Bestätigungslink erneut senden', 'it-kayali-loyalty'); ?></button>
            </form>
        </details>
        <?php
        return (string) ob_get_clean();
    }

    private function renderRegisterForm(): string
    {
        ob_start();
        ?>
        <h2><?php echo esc_html__('Treuekonto erstellen', 'it-kayali-loyalty'); ?></h2>
        <p class="itk-loyalty-muted"><?php echo esc_html__('Für ein reines Treuekonto benötigen wir nur deinen Namen und deine E-Mail-Adresse.', 'it-kayali-loyalty'); ?></p>
        <form method="post" class="itk-loyalty-form">
            <?php wp_nonce_field('itk_loyalty_register', '_itk_loyalty_nonce'); ?>
            <input type="hidden" name="itk_loyalty_action" value="register">
            <label>
                <span><?php echo esc_html__('Name', 'it-kayali-loyalty'); ?></span>
                <input type="text" name="name" autocomplete="name" maxlength="191" required>
            </label>
            <label>
                <span><?php echo esc_html__('E-Mail-Adresse', 'it-kayali-loyalty'); ?></span>
                <input type="email" name="email" autocomplete="email" maxlength="190" required>
            </label>
            <button type="submit" class="itk-loyalty-button"><?php echo esc_html__('Treuekonto erstellen', 'it-kayali-loyalty'); ?></button>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    private function renderVerificationPending(array $member): string
    {
        ob_start();
        ?>
        <section class="itk-loyalty-panel">
            <h2><?php echo esc_html__('E-Mail bestätigen', 'it-kayali-loyalty'); ?></h2>
            <p><?php echo esc_html__('Dein Treuekonto wurde angelegt, ist aber noch nicht freigeschaltet.', 'it-kayali-loyalty'); ?></p>
            <p class="itk-loyalty-muted"><?php echo esc_html((string) $member['email']); ?></p>
            <form method="post" class="itk-loyalty-form">
                <?php wp_nonce_field('itk_loyalty_resend', '_itk_loyalty_nonce'); ?>
                <input type="hidden" name="itk_loyalty_action" value="resend_verification">
                <input type="hidden" name="email" value="<?php echo esc_attr((string) $member['email']); ?>">
                <button type="submit" class="itk-loyalty-button"><?php echo esc_html__('Bestätigungslink erneut senden', 'it-kayali-loyalty'); ?></button>
            </form>
            <p><a class="itk-loyalty-button itk-loyalty-button-secondary" href="<?php echo esc_url(wp_logout_url(AccountPage::url())); ?>"><?php echo esc_html__('Abmelden', 'it-kayali-loyalty'); ?></a></p>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    private function renderDashboard(array $member): string
    {
        $balance = $this->members->balance((int) $member['id']);
        $pending = (string) ($member['pending_email'] ?? '');

        ob_start();
        ?>
        <section class="itk-loyalty-panel itk-loyalty-account-head">
            <div>
                <p class="itk-loyalty-eyebrow"><?php echo esc_html__('Treuekonto', 'it-kayali-loyalty'); ?></p>
                <h2><?php echo esc_html((string) $member['name']); ?></h2>
                <p class="itk-loyalty-muted"><?php echo esc_html((string) $member['email']); ?></p>
            </div>
            <div class="itk-loyalty-balance">
                <span><?php echo esc_html__('Punktestand', 'it-kayali-loyalty'); ?></span>
                <strong><?php echo esc_html((string) $balance); ?></strong>
            </div>
        </section>

        <section class="itk-loyalty-panel">
            <h3><?php echo esc_html__('Persönliche Daten', 'it-kayali-loyalty'); ?></h3>
            <form method="post" class="itk-loyalty-form">
                <?php wp_nonce_field('itk_loyalty_profile', '_itk_loyalty_nonce'); ?>
                <input type="hidden" name="itk_loyalty_action" value="update_profile">
                <label>
                    <span><?php echo esc_html__('Name', 'it-kayali-loyalty'); ?></span>
                    <input type="text" name="name" value="<?php echo esc_attr((string) $member['name']); ?>" maxlength="191" required>
                </label>
                <label>
                    <span><?php echo esc_html__('E-Mail-Adresse', 'it-kayali-loyalty'); ?></span>
                    <input type="email" name="email" value="<?php echo esc_attr((string) $member['email']); ?>" maxlength="190" required>
                </label>
                <?php if ('' !== $pending) : ?>
                    <p class="itk-loyalty-notice itk-loyalty-notice-info">
                        <?php
                        echo esc_html(
                            sprintf(
                                __('Neue E-Mail-Adresse wartet auf Bestätigung: %s. Bis dahin bleibt die bisherige Adresse gültig.', 'it-kayali-loyalty'),
                                $pending
                            )
                        );
                        ?>
                    </p>
                <?php endif; ?>
                <button type="submit" class="itk-loyalty-button"><?php echo esc_html__('Änderungen speichern', 'it-kayali-loyalty'); ?></button>
            </form>
        </section>

        <section class="itk-loyalty-panel itk-loyalty-meta">
            <div><span><?php echo esc_html__('Status', 'it-kayali-loyalty'); ?></span><strong><?php echo esc_html('active' === (string) $member['status'] ? __('Aktiv', 'it-kayali-loyalty') : ucfirst((string) $member['status'])); ?></strong></div>
            <div><span><?php echo esc_html__('E-Mail bestätigt', 'it-kayali-loyalty'); ?></span><strong><?php echo ! empty($member['email_verified_at']) ? esc_html__('Ja', 'it-kayali-loyalty') : esc_html__('Nein', 'it-kayali-loyalty'); ?></strong></div>
            <div><span><?php echo esc_html__('Mitglied seit', 'it-kayali-loyalty'); ?></span><strong><?php echo esc_html(mysql2date('d.m.Y', (string) $member['created_at'], true)); ?></strong></div>
        </section>

        <p><a class="itk-loyalty-button itk-loyalty-button-secondary" href="<?php echo esc_url(wp_logout_url(AccountPage::url())); ?>"><?php echo esc_html__('Abmelden', 'it-kayali-loyalty'); ?></a></p>
        <?php
        return (string) ob_get_clean();
    }

    private function renderMessages(): string
    {
        $code = isset($_GET['itk-loyalty-message']) ? sanitize_key(wp_unslash($_GET['itk-loyalty-message'])) : '';
        $messages = array(
            'registered'               => array('success', __('Treuekonto angelegt. Bitte prüfe deine E-Mails und bestätige deine E-Mail-Adresse.', 'it-kayali-loyalty')),
            'registration_mail_failed' => array('error', __('Das Konto wurde angelegt, aber die E-Mail konnte nicht versendet werden. Bitte fordere den Bestätigungslink erneut an.', 'it-kayali-loyalty')),
            'registration_error'       => array('error', __('Die Registrierung konnte nicht abgeschlossen werden. Prüfe deine Angaben oder melde dich an, falls bereits ein Konto besteht.', 'it-kayali-loyalty')),
            'email_verified'           => array('success', __('E-Mail-Adresse bestätigt. Du bist jetzt angemeldet.', 'it-kayali-loyalty')),
            'magic_requested'          => array('info', __('Falls ein aktives Treuekonto zu dieser E-Mail-Adresse existiert, wurde ein Login-Link versendet.', 'it-kayali-loyalty')),
            'verification_requested'   => array('info', __('Falls ein unbestätigtes Treuekonto zu dieser E-Mail-Adresse existiert, wurde ein neuer Bestätigungslink versendet.', 'it-kayali-loyalty')),
            'logged_in'                => array('success', __('Erfolgreich angemeldet.', 'it-kayali-loyalty')),
            'login_error'              => array('error', __('Anmeldung fehlgeschlagen. Bitte prüfe deine Zugangsdaten.', 'it-kayali-loyalty')),
            'invalid_link'             => array('error', __('Der Link ist ungültig, abgelaufen oder wurde bereits verwendet.', 'it-kayali-loyalty')),
            'profile_saved'            => array('success', __('Deine Daten wurden gespeichert.', 'it-kayali-loyalty')),
            'email_change_pending'     => array('info', __('Name gespeichert. Bitte bestätige die neue E-Mail-Adresse über den Link in der neuen Mailbox. Bis dahin bleibt die bisherige Adresse gültig.', 'it-kayali-loyalty')),
            'email_changed'            => array('success', __('Die neue E-Mail-Adresse wurde bestätigt und übernommen.', 'it-kayali-loyalty')),
            'profile_mail_failed'      => array('error', __('Die Änderung wurde vorgemerkt, aber die Bestätigungs-E-Mail konnte nicht versendet werden. Die bisherige Adresse bleibt gültig.', 'it-kayali-loyalty')),
            'profile_error'            => array('error', __('Die Änderungen konnten nicht vollständig gespeichert werden. Bitte prüfe deine Angaben.', 'it-kayali-loyalty')),
            'security_error'           => array('error', __('Die Sicherheitsprüfung ist fehlgeschlagen. Bitte lade die Seite neu und versuche es erneut.', 'it-kayali-loyalty')),
            'login_required'           => array('error', __('Bitte melde dich zuerst an.', 'it-kayali-loyalty')),
        );

        if (! isset($messages[$code])) {
            return '';
        }

        [$type, $text] = $messages[$code];
        return '<div class="itk-loyalty-notice itk-loyalty-notice-' . esc_attr($type) . '">' . esc_html($text) . '</div>';
    }

    private function wrap(string $content): string
    {
        return '<div class="itk-loyalty-account">' . $content . '</div>';
    }

    private function redirect(string $message): never
    {
        $url = add_query_arg('itk-loyalty-message', $message, AccountPage::url());
        wp_safe_redirect($url);
        exit;
    }
}
