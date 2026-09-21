<?php
/**
 * Contract for optional external integrations.
 *
 * This is intentionally small in Phase 1. WooCommerce and helloCash adapters
 * are added in later phases without coupling the loyalty core to them.
 *
 * @package ITKayali\Loyalty\Contracts
 */

namespace ITKayali\Loyalty\Contracts;

interface Integration
{
    public function isAvailable(): bool;

    public function register(): void;
}
