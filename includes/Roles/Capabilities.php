<?php
/**
 * Capability identifiers used by the plugin.
 *
 * @package ITKayali\Loyalty\Roles
 */

namespace ITKayali\Loyalty\Roles;

final class Capabilities
{
    public const VIEW_DASHBOARD   = 'itk_loyalty_view_dashboard';
    public const MANAGE_MEMBERS   = 'itk_loyalty_manage_members';
    public const MANAGE_CARDS     = 'itk_loyalty_manage_cards';
    public const VIEW_LEDGER      = 'itk_loyalty_view_ledger';
    public const ADJUST_POINTS    = 'itk_loyalty_adjust_points';
    public const MANAGE_REWARDS   = 'itk_loyalty_manage_rewards';
    public const MANAGE_STAFF     = 'itk_loyalty_manage_staff';
    public const MANAGE_BRANCHES  = 'itk_loyalty_manage_branches';
    public const MANAGE_RULES     = 'itk_loyalty_manage_rules';
    public const MANAGE_SETTINGS  = 'itk_loyalty_manage_settings';
    public const SCAN_MEMBERS     = 'itk_loyalty_scan_members';
    public const ADD_POINTS       = 'itk_loyalty_add_points';
    public const REDEEM_REWARDS   = 'itk_loyalty_redeem_rewards';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return array(
            self::VIEW_DASHBOARD,
            self::MANAGE_MEMBERS,
            self::MANAGE_CARDS,
            self::VIEW_LEDGER,
            self::ADJUST_POINTS,
            self::MANAGE_REWARDS,
            self::MANAGE_STAFF,
            self::MANAGE_BRANCHES,
            self::MANAGE_RULES,
            self::MANAGE_SETTINGS,
            self::SCAN_MEMBERS,
            self::ADD_POINTS,
            self::REDEEM_REWARDS,
        );
    }

    /**
     * @return string[]
     */
    public static function staff(): array
    {
        return array(
            self::SCAN_MEMBERS,
            self::ADD_POINTS,
            self::REDEEM_REWARDS,
        );
    }
}
