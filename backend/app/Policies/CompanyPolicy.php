<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return true; // Companies are publicly viewable
    }

    public function update(User $user, Company $company): bool
    {
        // User must belong to the company
        if ($user->company_id === $company->id) {
            return true;
        }

        return $user->isAdmin();
    }
}
