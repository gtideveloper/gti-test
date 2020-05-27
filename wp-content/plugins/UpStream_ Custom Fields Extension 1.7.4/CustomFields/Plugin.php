<?php

namespace UpStream\Plugins\CustomFields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Alledia\EDD_SL_Plugin_Updater;
use UpStream\Plugins\CustomFields\Pages\Collection as CollectionPage;
use UpStream\Plugins\CustomFields\Pages\Project as ProjectPage;
use UpStream\Plugins\CustomFields\Traits\Singleton;

/**
 * Plugin main class file.
 *
 * @package     UpStream\Plugins\CustomFields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class Plugin
{
    use Singleton;

    /**
     * @var EDD_SL_Plugin_Updater
     */
    public static $updater;
    /**
     * Store the current namespace so it can be reused on various methods.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     string $namespace
     */
    private static $namespace;

    /**
     * Plugin's instance constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        upstream_custom_fields_debug('Custom fields: Starting constructor');

        // Store the current namespace so it can be reused.
        self::$namespace = __NAMESPACE__ . '\Plugin';

        if ( ! function_exists('is_plugin_inactive')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        upstream_custom_fields_debug('Custom fields: Checking plugin active');

        $upstream = 'upstream/upstream.php';
        if ( ! file_exists(WP_PLUGIN_DIR . '/' . $upstream)
             || is_plugin_inactive($upstream)
        ) {
            return;
        }

        upstream_custom_fields_debug('Custom fields: Attaching hooks');

        $this->attachHooks();

        Metabox::instantiate();
        AutoincrementModel::instantiate();

        upstream_custom_fields_debug('Custom fields: Ending constructor');

        do_action('upstream.' . UP_CUSTOM_FIELDS_ALIAS . ':onAfterInit');
    }

    /**
     * Define actions and filters.
     *
     * @since   1.0.0
     * @access  private
     */
    private function attachHooks()
    {
        upstream_custom_fields_debug('Custom fields: Activation hook begin');

        register_activation_hook(UP_CUSTOM_FIELDS_BOOTSTRAP, [self::$namespace, 'activationCallback']);
        register_deactivation_hook(UP_CUSTOM_FIELDS_BOOTSTRAP, [self::$namespace, 'deactivationCallback']);

        add_action('admin_menu', [self::$namespace, 'registerAdminMenu']);
        add_action('admin_init', [self::$namespace, 'initUpdater']);
        add_filter('upstream:custom_menu_order', [self::$namespace, 'fixAdminMenuOrder'], UP_CUSTOM_FIELDS_ID, 1);
        add_action('init', [self::$namespace, 'registerPostType']);
        add_filter('post_updated_messages', [self::$namespace, 'setPostUpdateMessages'], UP_CUSTOM_FIELDS_ID, 1);
        add_filter('bulk_post_updated_messages', [self::$namespace, 'setPostBulkUpdateMessages'],
            UP_CUSTOM_FIELDS_ID, 2);

        CollectionPage::attachHooks();
        ProjectPage::attachHooks();
        AutoincrementModel::getInstance()->attachHooks();

        upstream_custom_fields_debug('Custom fields: Hooks attached');

    }

    /**
     * Called once the plugin is deactivated.
     *
     * @since   1.0.0
     * @static
     */
    public static function activationCallback()
    {
        self::registerPostType();
    }

    /**
     * Register the custom post type.
     *
     * @since   1.0.0
     * @static
     */
    public static function registerPostType()
    {
        upstream_custom_fields_debug('Custom fields: Registering post type');

        $labelSingular = __('Custom Field', 'upstream-custom-fields');
        $labelPlural   = __('Custom Fields', 'upstream-custom-fields');

        $postTypeLabels = [
            'name'                  => _x('%2$s', 'Custom Field post type name', 'upstream'),
            'singular_name'         => _x('%1$s', 'singular custom field post type name', 'upstream'),
            'add_new'               => __('New %1s', 'upstream'),
            'add_new_item'          => __('Add New %1$s', 'upstream'),
            'edit_item'             => __('Edit %1$s', 'upstream'),
            'new_item'              => __('New %1$s', 'upstream'),
            'all_items'             => __('%2$s', 'upstream'),
            'view_item'             => __('View %1$s', 'upstream'),
            'search_items'          => __('Search %2$s', 'upstream'),
            'not_found'             => __('No %2$s found', 'upstream'),
            'not_found_in_trash'    => __('No %2$s found in Trash', 'upstream'),
            'parent_item_colon'     => '',
            'menu_name'             => _x('%2$s', 'custom field post type menu name', 'upstream'),
            'featured_image'        => __('%1$s Image', 'upstream'),
            'set_featured_image'    => __('Set %1$s Image', 'upstream'),
            'remove_featured_image' => __('Remove %1$s Image', 'upstream'),
            'use_featured_image'    => __('Use as %1$s Image', 'upstream'),
            'filter_items_list'     => __('Filter %2$s list', 'upstream'),
            'items_list_navigation' => __('%2$s list navigation', 'upstream'),
            'items_list'            => __('%2$s list', 'upstream'),
        ];

        foreach ($postTypeLabels as $labelKey => $labelValue) {
            $postTypeLabels[$labelKey] = sprintf($labelValue, $labelSingular, $labelPlural);
        }

        $postTypeArgs = [
            'labels'             => $postTypeLabels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'map_meta_cap'       => true,
            'has_archive'        => 'up_custom_fields',
            'hierarchical'       => false,
            'supports'           => ['title', 'revisions'],
        ];
        $p = register_post_type('up_custom_field', $postTypeArgs);

        upstream_custom_fields_debug('Custom fields: Checking plugin active' . print_r($p, true));
    }

    /**
     * Initialize EDD updater.
     */
    public static function initUpdater()
    {
        if (class_exists('EDD_SL_Plugin_Updater')) {
            self::$updater = new EDD_SL_Plugin_Updater(
                UP_CUSTOM_FIELDS_API_URL,
                UP_CUSTOM_FIELDS_BOOTSTRAP,
                [
                    'version' => UPSTREAM_CUSTOM_FIELDS_VERSION,
                    'license' => get_option('upstream_custom_fields_license_key'),
                    'item_id' => UP_CUSTOM_FIELDS_ID,
                    'author' => 'UpStream',
                    'beta' => false,
                ]
            );
        }
    }

    /**
     * Called once the plugin is activated.
     *
     * @since   1.0.0
     * @static
     */
    public static function deactivationCallback()
    {
        // Do nothing.
    }

    public static function isUserEitherManagerOrAdmin($user = null)
    {
        if (empty($user) || ! ($user instanceof \WP_User)) {
            $user = wp_get_current_user();
        }

        if ($user->ID > 0 && isset($user->roles)) {
            return count(array_intersect((array)$user->roles, ['administrator', 'upstream_manager'])) > 0;
        }

        return false;
    }

    /**
     * Register a menu link on admin sidebar.
     *
     * @since   1.0.0
     * @static
     */
    public static function registerAdminMenu()
    {
        upstream_custom_fields_debug('Custom fields: Registering admin menu');

        if (self::isUserEitherManagerOrAdmin()) {

            upstream_custom_fields_debug('Custom fields: Trying to add page');

            $c = add_submenu_page(
                'edit.php?post_type=project',
                'Custom Fields',
                'Custom Fields',
                'edit_posts',
                'edit.php?post_type=' . UP_CUSTOM_FIELDS_POST_TYPE
            );

            upstream_custom_fields_debug('Custom fields: After adding submenu page ' . print_r($c, true));
        }
    }

    /**
     * Ensure Custom Fields submenu item is at the bottomest of UpStream's menu.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $menu Array of UpStream submenu items.
     *
     * @return  array   $menu
     */
    public static function fixAdminMenuOrder($menu)
    {
        global $submenu;
        $identifier = 'edit.php?post_type=project';
        if (isset($submenu[$identifier])) {
            foreach ($submenu[$identifier] as $upSubmenu) {
                if (isset($upSubmenu[2])
                    && $upSubmenu[2] === 'edit.php?post_type=' . UP_CUSTOM_FIELDS_POST_TYPE
                ) {
                    $upSubmenu[2] = 'edit.php?post_type=' . UP_CUSTOM_FIELDS_POST_TYPE;
                    $menu[]       = $upSubmenu;

                    break;
                }
            }
        }

        return $menu;
    }

    /**
     * Add custom update messages to the post_updated_messages filter flow.
     *
     * @since   1.0.2
     * @static
     *
     * @see     https://developer.wordpress.org/reference/hooks/post_updated_messages
     *
     * @param   array $messages Post updated messages.
     *
     * @return  array   $messages
     */
    public static function setPostUpdateMessages($messages)
    {
        $messages[UP_CUSTOM_FIELDS_POST_TYPE] = [
            1 => __('Custom Field updated.', 'upstream-custom-fields'),
            4 => __('Custom Field updated.', 'upstream-custom-fields'),
            6 => __('Custom Field published.', 'upstream-custom-fields'),
            7 => __('Custom Field saved.', 'upstream-custom-fields'),
            8 => __('Custom Field submitted.', 'upstream-custom-fields'),
        ];

        return $messages;
    }

    /**
     * Add custom update messages to the bulk_post_updated_messages filter flow.
     *
     * @since   1.0.2
     * @static
     *
     * @see     https://developer.wordpress.org/reference/hooks/bulk_post_updated_messages
     *
     * @param   array $messages Array of messages.
     * @param   array $counts   Array of item counts for each message.
     *
     * @return  array   $messages
     */
    public static function setPostBulkUpdateMessages($messages, $counts)
    {
        $countsUpdated   = (int)$counts['updated'];
        $countsLocked    = (int)$counts['locked'];
        $countsDeleted   = (int)$counts['deleted'];
        $countsTrashed   = (int)$counts['trashed'];
        $countsUntrashed = (int)$counts['untrashed'];

        $postTypeNameSingular = __('Custom Field', 'upstream-custom-fields');
        $postTypeNamePlural   = __('Custom Fields', 'upstream-custom-fields');

        $messages[UP_CUSTOM_FIELDS_POST_TYPE] = [
            'updated'   => sprintf(
                _n('%1$s %2$s updated.', '%1$s %3$s updated.', $countsUpdated),
                $countsUpdated,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'locked'    => sprintf(
                _n('%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s updated, somebody is editing them.',
                    $countsLocked),
                $countsLocked,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'deleted'   => sprintf(
                _n('%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $countsDeleted),
                $countsDeleted,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'trashed'   => sprintf(
                _n('%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $countsTrashed),
                $countsTrashed,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'untrashed' => sprintf(
                _n('%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $countsUntrashed),
                $countsUntrashed,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
        ];

        return $messages;
    }
}
