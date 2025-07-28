<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static Login()
 * @method static Register()
 * @method static ResetPassword()
 * @method static ChangePassword()
 * @method static VerifyEmail()
 * @method static Logout()
 * @method static TwoFactorAuth()
 * @method static SocialAuth()
 * @method static Role()
 */
final class ActivityLogType extends Enum
{
    const Login = 'Login';
    const Register = 'Register';
    const ResetPassword = 'ResetPassword';
    const ChangePassword = 'ChangePassword';
    const VerifyEmail = 'VerifyEmail';
    const Logout = 'Logout';
    const TwoFactorAuth = 'TwoFactorAuth';
    const SocialAuth = 'SocialAuth';
    const Role = 'Role';
}
