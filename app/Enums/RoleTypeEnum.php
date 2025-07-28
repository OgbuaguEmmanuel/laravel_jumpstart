<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Admin()
 * @method static static SuperAdmin()
 * @method static static User()
 */
final class RoleTypeEnum extends Enum
{
    const SuperAdmin = 'super admin';
    const Admin = 'admin';
    const User = 'user';
}
