<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static viewActivity()
 * @method static static viewAllActivities()
 * @method static static OptionThree()
 */
final class PermissionTypeEnum extends Enum
{
    const viewActivity = 'view activity';
    const viewAllActivities = 'view all activities';
    const OptionThree = 2;
}
