<?php
/**
 * Centralized table-name resolver.
 *
 * @package ITKayali\Loyalty\Database
 */

namespace ITKayali\Loyalty\Database;

final class TableNames
{
    public static function members(): string
    {
        return self::withPrefix('itk_loyalty_members');
    }

    public static function cards(): string
    {
        return self::withPrefix('itk_loyalty_cards');
    }

    public static function userLinks(): string
    {
        return self::withPrefix('itk_loyalty_user_links');
    }

    public static function ledger(): string
    {
        return self::withPrefix('itk_loyalty_ledger');
    }

    public static function redemptions(): string
    {
        return self::withPrefix('itk_loyalty_redemptions');
    }

    public static function reservations(): string
    {
        return self::withPrefix('itk_loyalty_reservations');
    }

    public static function externalRefs(): string
    {
        return self::withPrefix('itk_loyalty_external_refs');
    }

    public static function branches(): string
    {
        return self::withPrefix('itk_loyalty_branches');
    }

    public static function tokens(): string
    {
        return self::withPrefix('itk_loyalty_tokens');
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return array(
            'members'       => self::members(),
            'cards'         => self::cards(),
            'user_links'    => self::userLinks(),
            'ledger'        => self::ledger(),
            'redemptions'   => self::redemptions(),
            'reservations'  => self::reservations(),
            'external_refs' => self::externalRefs(),
            'branches'      => self::branches(),
            'tokens'        => self::tokens(),
        );
    }

    private static function withPrefix(string $suffix): string
    {
        global $wpdb;

        return $wpdb->prefix . $suffix;
    }
}
