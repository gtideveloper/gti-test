<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/************************* BEGIN UPSTREAM V2 FUNCTIONALITY ******************************/


if (! defined('UPSTREAM_ITEM_TYPE_PROJECT')) {

    define('UPSTREAM_ITEM_TYPE_PROJECT', 'project');
    define('UPSTREAM_ITEM_TYPE_MILESTONE', 'milestone');
    define('UPSTREAM_ITEM_TYPE_CLIENT', 'client');
    define('UPSTREAM_ITEM_TYPE_TASK', 'task');
    define('UPSTREAM_ITEM_TYPE_BUG', 'bug');
    define('UPSTREAM_ITEM_TYPE_FILE', 'file');
    define('UPSTREAM_ITEM_TYPE_DISCUSSION', 'discussion');

}

define('UPSTREAM_PERMISSIONS_UNCHANGED', 0);
define('UPSTREAM_PERMISSIONS_OVERRIDE_ALLOW', 1);
define('UPSTREAM_PERMISSIONS_OVERRIDE_BLOCK', 2);

define('UPSTREAM_PERMISSIONS_ACTION_VIEW', 'view');
define('UPSTREAM_PERMISSIONS_ACTION_EDIT', 'edit');
define('UPSTREAM_PERMISSIONS_ACTION_CREATE', 'create');
define('UPSTREAM_PERMISSIONS_ACTION_DELETE', 'delete');
define('UPSTREAM_PERMISSIONS_ACTION_COPY', 'copy');

define('UPSTREAM_PERMISSIONS_FILTER_OBJECT', 'upstream_permissions_filter_object');
define('UPSTREAM_PERMISSIONS_FILTER_FIELD', 'upstream_permissions_filter_field');
define('UPSTREAM_PERMISSIONS_FILTER_BYPASS', 'upstream_permissions_filter_bypass');
define('UPSTREAM_PERMISSIONS_FILTER_PAGE_ACCESS', 'upstream_permissions_filter_page_access');

function upstream_can_access_object($capability, $object_type, $object_id, $parent_type, $parent_id, $action, $is_admin_page = false)

{
    if ($object_type === 'milestones') $object_type = 'milestone';
    else if ($object_type === 'tasks') $object_type = 'task';
    else if ($object_type === 'bugs') $object_type = 'bug';
    else if ($object_type === 'files') $object_type = 'file';

    $user_id = get_current_user_id();
    $override = apply_filters(UPSTREAM_PERMISSIONS_FILTER_OBJECT, UPSTREAM_PERMISSIONS_UNCHANGED,
        $object_type, $object_id, $parent_type, $parent_id, $user_id, $action);

    if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_BLOCK) {
        return false;
    } else if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_ALLOW) {
        return true;
    }
    else {
        if ($is_admin_page) {
            return upstream_admin_permissions($capability);
        } else {
            return upstream_permissions($capability, $object_id);
        }
    }
}

function upstream_can_access_field($capability, $object_type, $object_id, $parent_type, $parent_id, $field, $action, $is_admin_page = false)

{
    if ($object_type === 'milestones') $object_type = 'milestone';
    else if ($object_type === 'tasks') $object_type = 'task';
    else if ($object_type === 'bugs') $object_type = 'bug';
    else if ($object_type === 'files') $object_type = 'file';

    $user_id = get_current_user_id();
    $override = apply_filters(UPSTREAM_PERMISSIONS_FILTER_FIELD, UPSTREAM_PERMISSIONS_UNCHANGED,
        $object_type, $object_id, $parent_type, $parent_id, $field, $user_id, $action);

    if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_BLOCK) {
        return false;
    } else if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_ALLOW) {
        return true;
    }
    else {
        if ($is_admin_page) {
            return upstream_admin_permissions($capability);
        } else {
            return upstream_permissions($capability, $object_id);
        }
    }
}


/************************* END UPSTREAM  V2  FUNCTIONALITY ******************************/

function upstream_override_access_object($orig_value, $object_type, $object_id, $parent_type, $parent_id, $action)

{
    if ($object_type === 'milestones') $object_type = 'milestone';
    else if ($object_type === 'tasks') $object_type = 'task';
    else if ($object_type === 'bugs') $object_type = 'bug';
    else if ($object_type === 'files') $object_type = 'file';

    $user_id = get_current_user_id();
    $override = apply_filters(UPSTREAM_PERMISSIONS_FILTER_OBJECT, UPSTREAM_PERMISSIONS_UNCHANGED,
        $object_type, $object_id, $parent_type, $parent_id, $user_id, $action);

    if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_BLOCK) {
        return false;
    } else if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_ALLOW) {
        return true;
    }
    else {
        return $orig_value;
    }
}

function upstream_override_access_field($orig_value, $object_type, $object_id, $parent_type, $parent_id, $field, $action)

{
    if ($object_type === 'milestones') $object_type = 'milestone';
    else if ($object_type === 'tasks') $object_type = 'task';
    else if ($object_type === 'bugs') $object_type = 'bug';
    else if ($object_type === 'files') $object_type = 'file';

    $user_id = get_current_user_id();
    $override = apply_filters(UPSTREAM_PERMISSIONS_FILTER_FIELD, UPSTREAM_PERMISSIONS_UNCHANGED,
        $object_type, $object_id, $parent_type, $parent_id, $field, $user_id, $action);

    if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_BLOCK) {
        return false;
    } else if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_ALLOW) {
        return true;
    }
    else {
        return $orig_value;
    }
}

/**
 * Permission checks for the frontend are always run through here.
 * Return true if they are allowed.
 *
 * @deprecated Use current_user_can
 */
function upstream_permissions($capability = null, $item_id = null)
{

    // allow bypass of standard permissions by capability
    if (apply_filters(UPSTREAM_PERMISSIONS_FILTER_BYPASS, false, $capability)) {
        return true;
    }

    // set the return variable that can be overwritten after all checks
    $return       = false;
    $current_user = upstream_current_user_id();

    // these guys can do whatever they want
    if (upstream_project_owner_id() == upstream_current_user_id() ||
        current_user_can('upstream_manager') ||
        current_user_can('administrator')) {
        $return = true;
    }

    // if they are simply a project member or a client user, this will give them at least READ access to the project
    // and stops them being redirected to the projects archive page
    if ($capability == 'view_project') {
        $members = upstream_project_members_ids();
        if (is_array($members) && in_array($current_user, $members)) {
            $return = true;
        }

        // client users
        $client_users = upstream_project_client_users();
        if (is_array($client_users) && in_array($current_user, $client_users)) {
            $return = true;
        }
    }


    // if capability is set and they have the capability
    if (isset($capability) && ! empty($capability)) {

        // for WP user - get standard capabilities
        if (is_int($current_user) && current_user_can($capability)) {
            $return = true;
        }

        // for client users
        // get their capabilities, stored within the meta of the Client post type
        if ( ! is_int($current_user)) {
            $client_users = get_post_meta(
                upstream_project_client_id(upstream_post_id()),
                '_upstream_client_users',
                true
            );
            if (is_array($client_users) && ! empty($client_users)) {
                foreach ($client_users as $index => $user) {
                    if ($user['id'] == $current_user) {
                        if (isset($user['capability']) && in_array($capability, $user['capability'])) {
                            $return = true;
                        }
                    }
                }
            }
        }
    }

    // if we have an individual item and they are the creator or have been assigned this item
    // used for the 'Actions' column to allow editing/deleting buttons
    if (isset($item_id)) {
        $item        = upstream_project_item_by_id(upstream_post_id(), $item_id);
        $assigned_to = isset($item['assigned_to']) ? ( array )$item['assigned_to'] : [];
        $created_by  = isset($item['created_by']) ? $item['created_by'] : null;
        if (in_array($current_user, $assigned_to) || $created_by == $current_user) {
            $return = true;
        }
    }


    // blocks all fields (except for status) from being edited/deleted
    // used if the project status is closed
    if ($capability != 'project_status_field' && upstream_project_status_type() == 'closed') {
        $return = false;
    }


    return apply_filters('upstream_permissions', $return);
}


/* ======================================================================================
                    ADMIN
   ====================================================================================== */


/**
 * Permission checks are always run through here.
 * Return true if they are allowed.
 *
 * @param  string $capability
 *
 * @return bool
 */
function upstream_admin_permissions($capability = null)
{

    /*
     * Set the return variable that can be overwritten after all checks
     */
    $return = false;

    /*
     * These guys can do whatever they want
     */
    if (upstream_project_owner_id() == upstream_current_user_id() ||
        current_user_can('upstream_manager') ||
        current_user_can('administrator')) {
        $return = true;
    }

    /*
     * If the user has the capability
     */
    if (isset($capability) && ! empty($capability)) {
        if (current_user_can($capability)) {
            $return = true;
        }
    }

    /*
     * If project status is closed, block all fields (except for status) from being edited/deleted
     */
    if ((isset($capability) && $capability != 'project_status_field') &&
        upstream_project_status_type() == 'closed') {
        $return = false;
    }

    return apply_filters('upstream_admin_permissions', $return);
}

/**
 * Retrieve all Client Users permissions available.
 *
 * @since   1.11.0
 *
 * @return  array
 */
function upstream_get_client_users_permissions()
{
    $permissions = [];

    $permissionsList = (array)apply_filters('upstream:users.permissions', []);
    foreach ($permissionsList as $permission) {
        $permissions[$permission['key']] = $permission;
    }

    return $permissions;
}

/**
 * Check if a given user can access a given project.
 *
 * @since   1.12.2
 *
 * @param   numeric/WP_User     $user_id    The user to be checked against the project.
 * @param   numeric $project_id The project to be checked.
 *
 * @return  bool
 */
function upstream_user_can_access_project($user_id, $project_id)
{
    $project_id = is_numeric($project_id) ? (int)$project_id : 0;
    if ($project_id <= 0) {
        return false;
    }

    $user = $user_id instanceof \WP_User ? $user_id : new \WP_User($user_id);
    if ($user->ID === 0 && !apply_filters('upstream_permissions_filter_page_access', false)) {
        return false;
    }

    $override = apply_filters(UPSTREAM_PERMISSIONS_FILTER_OBJECT, UPSTREAM_PERMISSIONS_UNCHANGED,
        'project', $project_id, null, 0, $user->ID, UPSTREAM_PERMISSIONS_ACTION_VIEW);

    if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_BLOCK) {
        return false;
    } else if ($override == UPSTREAM_PERMISSIONS_OVERRIDE_ALLOW) {
        return true;
    }

    $userIsAdmin = count(array_intersect((array)$user->roles, ['administrator', 'upstream_manager'])) > 0;
    if ($userIsAdmin) {
        return true;
    }

    $userCanAccessProject = false;

    if (in_array('upstream_client_user', $user->roles)) {
        // Check if client user is allowed on this project.
        $meta = (array)get_post_meta($project_id, '_upstream_project_client_users');
        $meta = ! empty($meta) ? (array)$meta[0] : [];
        $meta = empty($meta) ? [] : $meta;

        if (in_array($user->ID, $meta)) {
            $userCanAccessProject = true;
        }
    }

    if ( ! $userCanAccessProject && user_can($user, 'edit_published_projects')) {
        // Check if user is a member of the project.
        $projectMembers = upstream_project_members_ids($project_id);
        if (is_array($projectMembers) && in_array($user->ID, $projectMembers)) {
            $userCanAccessProject = true;
        } elseif ($user->ID == (int)$projectMembers) {
            $userCanAccessProject = true;
        }
    }

    return $userCanAccessProject;
}
