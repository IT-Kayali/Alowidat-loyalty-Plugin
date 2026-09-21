<?php
/**
 * Plugin activation routine.
 *
 * @package ITKayali\Loyalty\Core
 */

namespace ITKayali\Loyalty\Core;

use ITKayali\Loyalty\Database\Schema;
use ITKayali\Loyalty\Roles\RoleManager;

final class Activator
{
    public static function activate(): void
    {
        if (version_compare(PHP_VERSION, '8.1', '<')) {
            wp_die(
                esc_html__('IT-Kayali Loyalty requires PHP 8.1 or newer.', 'it-kayali-loyalty'),
                esc_html__('Plugin activation failed', 'it-kayali-loyalty'),
                array('back_link' => true)
            );
        }

        Schema::install();
        RoleManager::install();

        update_option('itk_loyalty_version', ITK_LOYALTY_VERSION, false);
        update_option('itk_loyalty_activated_at', current_time('mysql', true), false);
    }
}
