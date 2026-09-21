<?php
/**
 * Minimal PSR-4-style autoloader for the plugin namespace.
 *
 * @package ITKayali\Loyalty
 */

defined('ABSPATH') || exit;

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'ITKayali\\Loyalty\\';

        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative_class = substr($class, strlen($prefix));
        $relative_path  = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
        $file           = ITK_LOYALTY_DIR . 'includes' . DIRECTORY_SEPARATOR . $relative_path;

        if (is_readable($file)) {
            require_once $file;
        }
    }
);
