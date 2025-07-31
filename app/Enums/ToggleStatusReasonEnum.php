<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static SUSPICIOUS_ACTIVITY()
 * @method static TOS_VIOLATION()
 * @method static USER_REQUESTED_DEACTIVATION()
 * @method static EXTENDED_INACTIVITY()
 * @method static PAYMENT_LAPSE()
 * @method static FRAUDULENT_ACTIVITY()
 * @method static SECURITY_COMPROMISE()
 * @method static LEGAL_ORDER()
 * @method static REGISTRATION_COMPLETE()
 * @method static EMAIL_VERIFIED()
 * @method static USER_REQUESTED_REACTIVATION()
 * @method static ADMIN_ACTIVATION()
 * @method static ISSUE_RESOLVED()
 */
final class ToggleStatusReasonEnum extends Enum
{
    // --- Deactivation Reasons ---
    const SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    const TOS_VIOLATION = 'tos_violation';
    const USER_REQUESTED_DEACTIVATION = 'user_requested_deactivation';
    const EXTENDED_INACTIVITY = 'extended_inactivity';
    const PAYMENT_LAPSE = 'payment_lapse';
    const FRAUDULENT_ACTIVITY = 'fraudulent_activity';
    const SECURITY_COMPROMISE = 'security_compromise';
    const LEGAL_ORDER = 'legal_order';

    // --- Activation Reasons ---
    const REGISTRATION_COMPLETE = 'registration_complete';
    const EMAIL_VERIFIED = 'email_verified';
    const USER_REQUESTED_REACTIVATION = 'user_requested_reactivation';
    const PAYMENT_RENEWED = 'payment_renewed';
    const ADMIN_ACTIVATION = 'admin_activation';
    const ISSUE_RESOLVED = 'issue_resolved';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            // Deactivation Reasons
            self::SUSPICIOUS_ACTIVITY => 'Account deactivated due to suspicious activity.',
            self::TOS_VIOLATION => 'Account deactivated due to Terms of Service violation.',
            self::USER_REQUESTED_DEACTIVATION => 'Account deactivated at user\'s request.',
            self::EXTENDED_INACTIVITY => 'Account deactivated due to extended inactivity.',
            self::PAYMENT_LAPSE => 'Account deactivated due to payment lapse.',
            self::FRAUDULENT_ACTIVITY => 'Account deactivated due to detected fraudulent activity.',
            self::SECURITY_COMPROMISE => 'Account deactivated due to security compromise.',
            self::LEGAL_ORDER => 'Account deactivated by legal/regulatory order.',

            // Activation Reasons
            self::REGISTRATION_COMPLETE => 'Account activated upon successful registration.',
            self::EMAIL_VERIFIED => 'Account activated after email verification.',
            self::USER_REQUESTED_REACTIVATION => 'Account reactivated at user\'s request.',
            self::PAYMENT_RENEWED => 'Account activated after payment renewal.',
            self::ADMIN_ACTIVATION => 'Account manually activated by administrator.',
            self::ISSUE_RESOLVED => 'Account reactivated after an underlying issue was resolved.',

            default => parent::getDescription($value),
        };
    }

    public static function getDeactivationReasons(): array
    {
        return [
            self::SUSPICIOUS_ACTIVITY,
            self::TOS_VIOLATION,
            self::USER_REQUESTED_DEACTIVATION,
            self::EXTENDED_INACTIVITY,
            self::PAYMENT_LAPSE,
            self::FRAUDULENT_ACTIVITY,
            self::SECURITY_COMPROMISE,
            self::LEGAL_ORDER,
        ];
    }

    /**
     * Helper method to get all activation reason values.
     * @return array<string> // Array of strings
     */
    public static function getActivationReasons(): array
    {
        return [
            self::REGISTRATION_COMPLETE,
            self::EMAIL_VERIFIED,
            self::USER_REQUESTED_REACTIVATION,
            self::PAYMENT_RENEWED,
            self::ADMIN_ACTIVATION,
            self::ISSUE_RESOLVED,
        ];
    }
}
