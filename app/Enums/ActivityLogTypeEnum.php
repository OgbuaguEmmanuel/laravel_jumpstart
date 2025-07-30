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
 * @method static RolesAndPermissions()
 * @method static User()
 */
final class ActivityLogTypeEnum extends Enum
{
    const Login = 'Login';
    const Register = 'Register';
    const ResetPassword = 'ResetPassword';
    const ChangePassword = 'ChangePassword';
    const VerifyEmail = 'VerifyEmail';
    const Logout = 'Logout';
    const TwoFactorAuth = 'TwoFactorAuth';
    const SocialAuth = 'SocialAuth';
    const RolesAndPermissions = 'RolesAndPermissions';
    const UserModel = 'User';
}
