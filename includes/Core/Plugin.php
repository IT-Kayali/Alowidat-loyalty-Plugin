<?php
/**
 * Main plugin runtime.
 *
 * @package ITKayali\Loyalty\Core
 */

namespace ITKayali\Loyalty\Core;

use ITKayali\Loyalty\Database\Schema;

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
    }
}
