<?php
/**
 * Main plugin runtime.
 *
 * @package ITKayali\Loyalty\Core
 */

namespace ITKayali\Loyalty\Core;

use ITKayali\Loyalty\Accounts\AccountService;
use ITKayali\Loyalty\Accounts\FrontendController;
use ITKayali\Loyalty\Accounts\MemberRepository;
use ITKayali\Loyalty\Accounts\TokenRepository;
use ITKayali\Loyalty\Database\Schema;
use ITKayali\Loyalty\Roles\RoleManager;
use ITKayali\Loyalty\Security\AccessControl;
use ITKayali\Loyalty\Security\RateLimiter;
use ITKayali\Loyalty\Integrations\WooCommerce\AccountEndpoint;

final class Plugin
{
    private static ?self $instance = null;

    private bool $booted = false;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        load_plugin_textdomain(
            'it-kayali-loyalty',
            false,
            dirname(plugin_basename(ITK_LOYALTY_FILE)) . '/languages'
        );

        Schema::maybe_upgrade();
        RoleManager::install();
        AccessControl::register();

        add_action('init', array(AccountPage::class, 'ensure'), 5);

        $members = new MemberRepository();
        $tokens  = new TokenRepository();
        $service = new AccountService($members, $tokens, new RateLimiter());

        (new FrontendController($service, $members))->register();

        if (AccountEndpoint::isAvailable()) {
            AccountEndpoint::register();
        }
    }
}
