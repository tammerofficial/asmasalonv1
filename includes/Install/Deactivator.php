<?php

namespace AsmaaSalon\Install;

use AsmaaSalon\Services\MembershipExpiryChecker;

if (!defined('ABSPATH')) {
    exit;
}

class Deactivator
{
    public static function deactivate(): void
    {
        // Keep data on deactivate by default.
        // We can add cleanup logic here later if needed.

        // ✅ Clear scheduled membership expiry check
        MembershipExpiryChecker::clear_schedule();
    }
}

