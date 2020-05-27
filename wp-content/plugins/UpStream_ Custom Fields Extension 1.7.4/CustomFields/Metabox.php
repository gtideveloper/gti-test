<?php

namespace UpStream\Plugins\CustomFields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Cmb2Grid\Grid\Cmb2Grid;
use UpStream\Plugins\CustomFields\CMB2\OptionValueField;
use UpStream\Plugins\CustomFields\Metaboxes\Args as ArgsMetabox;
use UpStream\Plugins\CustomFields\Metaboxes\Details as DetailsMetabox;
use UpStream\Plugins\CustomFields\Metaboxes\Usage as UsageMetabox;
use UpStream\Plugins\CustomFields\Traits\Singleton;

/**
 * Class responsible for handling Custom Fields on admin.
 *
 * @package     UpStream\Plugins\CustomFields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class Metabox
{
    use Singleton;

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
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        // Store the current namespace so it can be reused.
        self::$namespace = __NAMESPACE__ . '\Metabox';

        $this->attachHooks();

        $fieldTypes = Model::getFieldTypes();
        foreach ($fieldTypes as $fieldType => $fieldTypeName) {
            $fieldTypeMetaboxNamespace = '\\UpStream\\Plugins\\CustomFields\\Metaboxes\\Fields\\' . ucfirst($fieldType);

            if ( class_exists( $fieldTypeMetaboxNamespace ) ) {
                call_user_func([$fieldTypeMetaboxNamespace, 'instantiate']);
            }
        }
    }

    /**
     * Define actions and filters.
     *
     * @since   1.0.0
     * @access  private
     */
    private function attachHooks()
    {
        include_once WP_PLUGIN_DIR . '/upstream/includes/libraries/cmb2/init.php';
        include_once WP_PLUGIN_DIR . '/upstream/includes/libraries/cmb2-grid/Cmb2GridPlugin.php';

        add_action('cmb2_admin_init', [self::$namespace, 'renderMetaboxes']);
        add_action('admin_enqueue_scripts', [self::$namespace, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [self::$namespace, 'enqueueStyles']);

        add_filter('upstream_details_metabox_fields', [self::$namespace, 'renderProjectsFields']);
        add_filter('upstream_milestone_metabox_fields', [self::$namespace, 'renderMilestonesFields']);
        add_filter('upstream_task_metabox_fields', [self::$namespace, 'renderTasksFields']);
        add_filter('upstream_bug_metabox_fields', [self::$namespace, 'renderBugsFields']);
        add_filter('upstream_file_metabox_fields', [self::$namespace, 'renderFilesFields']);

        add_filter('upstream_client_metabox_fields', [self::$namespace, 'renderClientsFields']);
    }

    /**
     * Enqueue required scripts.
     *
     * @since   1.0.0
     * @static
     */
    public static function enqueueScripts()
    {
        if ( ! self::canRunOnCurrentPage()) {
            return;
        }

        wp_register_script(UP_CUSTOM_FIELDS_SLUG, UP_CUSTOM_FIELDS_URL . 'assets/js/admin-custom_field.js', [],
            UP_CUSTOM_FIELDS_VERSION, true);
        wp_enqueue_script(UP_CUSTOM_FIELDS_SLUG);
        wp_localize_script(UP_CUSTOM_FIELDS_SLUG, '$upstreamCustomFields', []);
    }

    /**
     * Check if scripts can run on current page.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @return  bool
     */
    private static function canRunOnCurrentPage()
    {
        return true; // @todo

        /*
        $postType = get_post_type();
        if (empty($postType)) {
            $postId = upstream_post_id();
            if ($postId > 0) {
                $postType = get_post_type($postId);
            } else {
                $postType = isset($_GET['post_type']) ? $_GET['post_type'] : '';
            }
        }

        return $postType === UP_CUSTOM_FIELDS_POST_TYPE;
        */
    }

    /**
     * Enqueue required stylesheets.
     *
     * @since   1.0.0
     * @static
     */
    public static function enqueueStyles()
    {
        if ( ! self::canRunOnCurrentPage()) {
            return;
        }

        wp_register_style(UP_CUSTOM_FIELDS_SLUG, UP_CUSTOM_FIELDS_URL . 'assets/css/plugin.css', [],
            UPSTREAM_CUSTOM_FIELDS_VERSION);
        wp_enqueue_style(UP_CUSTOM_FIELDS_SLUG);
    }

    /**
     * Define all metaboxes for editing/creating Custom Fields.
     *
     * @since   1.0.0
     * @static
     */
    public static function renderMetaboxes()
    {
        if ( ! self::canRunOnCurrentPage()) {
            return;
        }

        OptionValueField::instantiate();

        DetailsMetabox::render();
        ArgsMetabox::render();
        UsageMetabox::render();
    }

    /**
     * Append Custom Fields to project details form on admin.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $fields Array of CMB2 fields.
     *
     * @return  array
     */
    public static function renderProjectsFields($fields)
    {
        $fields = self::appendCustomFieldsForItemType($fields, 'project');

        return $fields;
    }

    /**
     * Append Custom Fields of a certain type to an array of CMB2 fields.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @param   array  $fields   Array of CMB2 fields.
     * @param   string $itemType Item type where the fields belongs to.
     *
     * @return  array
     */
    private static function appendCustomFieldsForItemType($fields, $itemType)
    {
        //  RSD: some times fields is empty
        if (count($fields) == 0) {
            return $fields;
        }

        if ( ! in_array($itemType, ['project', 'milestone', 'task', 'bug', 'client', 'file'])) {
            return $fields;
        }

        $rowset = Model::fetchRowset($itemType);

        if (count($rowset) > 0) {
            $fieldsIndexes = array_keys($fields);
            $lastIndex     = $fieldsIndexes[count($fieldsIndexes) - 1];

            $newIndex = 0;
            if ($lastIndex % 10 === 0) {
                $newIndex = $lastIndex + 10;
            } else {
                $newIndex = ((int)($lastIndex / 10) + 1) * 10;
            }

            foreach ($rowset as $row) {

                if (in_array($row->type, [
                    'text',
                    'autoincrement',
                    'select',
                    'checkbox',
                    'multicheck',
                    'multicheck_inline',
                    'radio',
                    'radio_inline',
                    'file',
                    'colorpicker',
                    'category',
                    'tag',
                    'user',
                    'country',
                ])) {
                    $data                 = $row->toCmb2();
                    $data['before_field'] = '<div class="up-c-custom-field">';
                    $data['after_field']  = '</div>';

                    $fields[$newIndex] = $data;

                    // RSD: +10 in order to fix bug in not closing row
                    $newIndex+=10;
                }
            }
        }

        return $fields;
    }

    /**
     * Append Custom Fields to project milestones form on admin.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $fields Array of CMB2 fields.
     *
     * @return  array
     */
    public static function renderMilestonesFields($fields)
    {
        $fields = self::appendCustomFieldsForItemType($fields, 'milestone');

        return $fields;
    }

    /**
     * Append Custom Fields to project tasks form on admin.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $fields Array of CMB2 fields.
     *
     * @return  array
     */
    public static function renderTasksFields($fields)
    {
        $fields = self::appendCustomFieldsForItemType($fields, 'task');

        return $fields;
    }

    /**
     * Append Custom Fields to client tasks form on admin.
     *
     * @since   1.24.5
     * @static
     *
     * @param   array $fields Array of CMB2 fields.
     *
     * @return  array
     */
    public static function renderClientsFields($fields)
    {
        $fields = self::appendCustomFieldsForItemType($fields, 'client');

        return $fields;
    }

    /**
     * Append Custom Fields to project bugs form on admin.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $fields Array of CMB2 fields.
     *
     * @return  array
     */
    public static function renderBugsFields($fields)
    {
        $fields = self::appendCustomFieldsForItemType($fields, 'bug');

        return $fields;
    }

    /**
     * Append Custom Fields to project files form on admin.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $fields Array of CMB2 fields.
     *
     * @return  array
     */
    public static function renderFilesFields($fields)
    {
        $fields = self::appendCustomFieldsForItemType($fields, 'file');

        return $fields;
    }
}
