<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static  viewActivity()
 * @method static  viewAllActivities()
 * @method static  createAdmin()
 * @method static  createRole()
 * @method static  createPermission()
 * @method static  assignRole()
 * @method static  removeRole()
 * @method static  grantPermission()
 * @method static  revokePermission()
 * @method static  viewPermissions()
 * @method static  viewRoles()
 * @method static  updatePermission()
 * @method static  deleteRole()
 * @method static  toggleUserStatus()
 * @method static  deleteUsers()
 * @method static  unlockUsers()
 * @method static createUser()
 * @method static viewLock()
 * @method static viewSupportTicket()
 * @method static treatSupportTicket()
 */
final class PermissionTypeEnum extends Enum
{
    const viewActivity = 'view_activity';
    const viewAllActivities = 'view_all_activities';
    const createAdmin = 'create_admin';
    const createRole = 'create_role';
    const createPermission = 'create_permission';
    const assignRole = 'assign_role';
    const removeRole = 'remove_role';
    const grantPermission = 'grant_permission';
    const revokePermission = 'revoke_permission';
    const viewRoles = 'view_roles';
    const viewPermissions = 'view_permissions';
    const updateRole = 'update_role';
    const updatePermission = 'update_permission';
    const deletePermission = 'delete_permission';
    const deleteRole = 'delete_role';
    const toggleUserStatus = 'toggle_user_status';
    const deleteUsers = 'delete_users';
    const unlockUsers = 'unlock_users';
    const viewUsers = 'view_users';
    const createUser = 'create_user';
    const viewLock = 'view_lock';
    const viewSupportTicket = 'view_support_ticket';
    const treatSupportTicket = 'treat_support_ticket';
}
