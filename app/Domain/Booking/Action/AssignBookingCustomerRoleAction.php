<?php

namespace App\Domain\Booking\Action;

use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignBookingCustomerRoleAction
{
    private const CUSTOMER_ROLE = 'customer';

    private const GUARD_NAME = 'sanctum';

    public function __invoke(User $user): User
    {
        $user->assignRole(Role::findOrCreate(self::CUSTOMER_ROLE, self::GUARD_NAME));

        return $user;
    }
}
