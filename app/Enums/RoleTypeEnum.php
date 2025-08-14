<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static Admin()
 * @method static SuperAdmin()
 * @method static User()
 */
final class RoleTypeEnum extends Enum
{
    const SuperAdmin = 'super admin';

    const Admin = 'admin';

    const User = 'user';
}
