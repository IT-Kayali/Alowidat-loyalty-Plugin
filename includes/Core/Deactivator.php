<?php
/**
 * Plugin deactivation routine.
 *
 * Data and roles are intentionally retained on deactivation.
 *
 * @package ITKayali\Loyalty\Core
 */

namespace ITKayali\Loyalty\Core;

final class Deactivator
{
    public static function deactivate(): void
    {
        // Intentionally non-destructive. Uninstall behavior is handled separately.
    }
}
