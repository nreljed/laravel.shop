<?php

namespace Database\Factories;

use App\Models\User;
use Nreljed\Platform\Database\Factories\UserFactory as BaseUserFactory;

// Note: no @extends tag — the platform factory is not generic; the model is
// pinned via the $model property below.
class UserFactory extends BaseUserFactory
{
    protected $model = User::class;
}
