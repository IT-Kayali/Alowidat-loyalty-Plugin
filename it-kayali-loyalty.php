<?php
/**
 * Plugin Name:       IT-Kayali Loyalty
 * Plugin URI:        https://github.com/IT-Kayali/Alowidat-loyalty-Plugin
 * Description:       Modular loyalty, points and digital stamp-card system for WordPress with optional WooCommerce integration.
 * Version:           0.2.1
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            IT-Kayali
 * Author URI:        https://it-kayali.de
 * Text Domain:       it-kayali-loyalty
 * Domain Path:       /languages
 * License:           Proprietary
 */

defined('ABSPATH') || exit;

if (! defined('ITK_LOYALTY_VERSION')) {
    define('ITK_LOYALTY_VERSION', '0.2.1');
}

if (! defined('ITK_LOYALTY_SCHEMA_VERSION')) {
    define('ITK_LOYALTY_SCHEMA_VERSION', '2');
}

if (! defined('ITK_LOYALTY_FILE')) {
    define('ITK_LOYALTY_FILE', __FILE__);
}

if (! defined('ITK_LOYALTY_DIR')) {
    define('ITK_LOYALTY_DIR', plugin_dir_path(__FILE__));
}

if (! defined('ITK_LOYALTY_URL')) {
    define('ITK_LOYALTY_URL', plugin_dir_url(__FILE__));
}

require_once ITK_LOYALTY_DIR . 'includes/autoload.php';

register_activation_hook(
    ITK_LOYALTY_FILE,
    array(\ITKayali\Loyalty\Core\Activator::class, 'activate')
);

register_deactivation_hook(
    ITK_LOYALTY_FILE,
    array(\ITKayali\Loyalty\Core\Deactivator::class, 'deactivate')
);

add_action(
    'plugins_loaded',
    static function (): void {
        \ITKayali\Loyalty\Core\Plugin::instance()->boot();
    }
);
