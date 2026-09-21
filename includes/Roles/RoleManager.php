<?php
/**
 * WordPress roles and capabilities.
 *
 * @package ITKayali\Loyalty\Roles
 */

namespace ITKayali\Loyalty\Roles;

final class RoleManager
{
    public const CUSTOMER_ROLE = 'itk_loyalty_customer';
    public const STAFF_ROLE    = 'itk_loyalty_staff';

    public static function install(): void
    {
        self::installCustomerRole();
        self::installStaffRole();
        self::grantAdministratorCapabilities();
    }

    private static function installCustomerRole(): void
    {
        $role = get_role(self::CUSTOMER_ROLE);

        if (! $role) {
            $role = add_role(
                self::CUSTOMER_ROLE,
                __('Loyalty Customer', 'it-kayali-loyalty'),
                array('read' => true)
            );
        }

        if ($role) {
            $role->add_cap('read', true);
        }
    }

    private static function installStaffRole(): void
    {
        $role = get_role(self::STAFF_ROLE);

        if (! $role) {
            $role = add_role(
                self::STAFF_ROLE,
                __('Loyalty Staff', 'it-kayali-loyalty'),
                array('read' => true)
            );
        }

        if (! $role) {
            return;
        }

        $role->add_cap('read', true);

        foreach (Capabilities::staff() as $capability) {
            $role->add_cap($capability, true);
        }
    }

    private static function grantAdministratorCapabilities(): void
    {
        $administrator = get_role('administrator');

        if (! $administrator) {
            return;
        }

        foreach (Capabilities::all() as $capability) {
            $administrator->add_cap($capability, true);
        }
    }
}
