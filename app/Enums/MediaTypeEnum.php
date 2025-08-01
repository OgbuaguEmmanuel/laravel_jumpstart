<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static ProfilePicture()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class MediaTypeEnum extends Enum
{
    const ProfilePicture = 'profile_picture';
    const OptionTwo = 1;
    const OptionThree = 2;
}
