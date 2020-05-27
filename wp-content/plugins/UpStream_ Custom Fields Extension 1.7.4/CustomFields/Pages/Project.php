<?php

namespace UpStream\Plugins\CustomFields\Pages;

use UpStream\Plugins\CustomFields\AutoincrementModel;
use UpStream\Plugins\CustomFields\Model;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Add support for Custom Fields on frontend.
 *
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Pages
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class Project
{
    /**
     * Current namespace.
     *
     * @since   1.0.0
     * @static
     *
     * @var     string $namespace
     */
    public static $namespace = UP_CUSTOM_FIELDS_NAMESPACE . '\\Pages\\Project';

    /**
     * Array of custom fields being used on milestones.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     array $cachedMilestones
     */
    private static $cachedMilestones = [];

    /**
     * Array of custom fields being used on tasks.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     array $cachedTasks
     */
    private static $cachedTasks = [];

    /**
     * Array of custom fields being used on bugs.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     array $cachedBugs
     */
    private static $cachedBugs = [];

    /**
     * Array of custom fields being used on files.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     array $cachedFiles
     */
    private static $cachedFiles = [];

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        throw new \Exception('You cannot instantiate this class.');
    }

    /**
     * Define actions and filters.
     *
     * @since   1.0.0
     * @static
     */
    public static function attachHooks()
    {
        add_action('wp_enqueue_scripts', [self::$namespace, 'enqueueScripts'], 1498);
        add_action('wp_enqueue_scripts', [self::$namespace, 'enqueueStyles'], 1499);
        add_action('admin_enqueue_scripts', [self::$namespace, 'enqueueScriptsOnAdmin'], 998);
        add_action('admin_enqueue_scripts', [self::$namespace, 'enqueueStylesOnAdmin'], 999);
        add_filter('upstream_admin_script_valid_post_types', [self::$namespace, 'filterValidPostTypeAdminScript']);

        add_action('upstream_milestone_table_settings',
            [self::$namespace, 'defineTableColumnsSettingsForMilestones']);
        add_action('upstream_task_table_settings', [self::$namespace, 'defineTableColumnsSettingsForTasks']);
        add_action('upstream_bug_table_settings', [self::$namespace, 'defineTableColumnsSettingsForBugs']);
        add_action('upstream_file_table_settings', [self::$namespace, 'defineTableColumnsSettingsForFiles']);

        add_action('upstream:frontend.project.render_details', [self::$namespace, 'renderFieldsOnProjectDetails'],
            10, 1);

        add_filter('upstream:project.milestones.fields', [self::$namespace, 'appendFieldsOnMilestones'],
            UP_CUSTOM_FIELDS_ID, 1);
        add_filter('upstream:project.tasks.fields', [self::$namespace, 'appendFieldsOnTasks'], UP_CUSTOM_FIELDS_ID,
            1);
        add_filter('upstream:project.bugs.fields', [self::$namespace, 'appendFieldsOnBugs'], UP_CUSTOM_FIELDS_ID,
            1);
        add_filter('upstream:project.files.fields', [self::$namespace, 'appendFieldsOnFiles'], UP_CUSTOM_FIELDS_ID,
            1);
        add_filter('upstream:project.fields', [self::$namespace, 'appendFieldsOnProjects'], UP_CUSTOM_FIELDS_ID,
            1);

        add_action('upstream.frontend-edit:renderAdditionalFields', [self::$namespace, 'renderFields'], 10, 2);
        add_filter('upstream.frontend-edit:project.onBeforeEditMeta', [self::$namespace, 'onBeforeSave'], 10, 4);
        add_filter('upstream.frontend-edit:project.onBeforeInsertMeta', [self::$namespace, 'onBeforeSave'], 10, 4);

        add_filter('ajax_query_attachments_args',
            ['\UpStream\Plugins\CustomFields\Model', 'filterMediaBasedOnUser']);
        add_action('upstream:project.milestones.filters', [self::$namespace, 'renderFrontendTableFilters'], 10, 3);
        add_action('upstream:project.tasks.filters', [self::$namespace, 'renderFrontendTableFilters'], 10, 3);
        add_action('upstream:project.bugs.filters', [self::$namespace, 'renderFrontendTableFilters'], 10, 3);
        add_action('upstream:project.files.filters', [self::$namespace, 'renderFrontendTableFilters'], 10, 3);
        add_action('upstream:project.filters', [self::$namespace, 'renderFrontendTableFilters'], 10, 2);
        add_action('upstream:project.columns.header', [self::$namespace, 'renderFrontendTableColumnHeaders'], 10, 2);
        add_action('upstream:project.columns.data', [self::$namespace, 'renderFrontendTableColumnData'], 10, 5);
        add_action('upstream_table_columns_header', [self::$namespace, 'renderFrontendTableColumnHeaders'], 10, 2);
        add_action('upstream_table_columns_data', [self::$namespace, 'renderFrontendTableColumnData'], 10, 5);

        add_action('upstream_frontend_save_project', [self::$namespace, 'saveProjectFields']);
        add_filter('upstream_frontend_project_data', [self::$namespace, 'filterProjectData'], 10, 2);
        add_action('upstream_save_metabox_field', [self::$namespace, 'saveMetaboxField']);
        add_action('upstream_save_milestone', [self::$namespace, 'saveMilestone'], 10, 2);
        add_filter('upstream_project_milestones', [self::$namespace, 'getMilestoneRowsetAdditionalFields']);
        add_filter('upstream_milestone_converting_legacy_rowset', [self::$namespace, 'getMilestoneAdditionalFields']);

        add_filter('upstream_model_property_exists', [self::$namespace, 'modelPropertyExists'], 20, 4);
        add_filter('upstream_model_set_property_value', [self::$namespace, 'modelSetPropertyValue'], 20, 5);
        add_filter('upstream_model_load_fields', [self::$namespace, 'modelLoadFields'], 20, 5);
        add_filter('upstream_model_store_fields', [self::$namespace, 'modelStoreFields'], 20, 5);

    }


    public static function modelPropertyExists($exists, $type, $id, $property)
    {
        $rowset = Model::fetchRowset($type, false);

        foreach ($rowset as $key => $row) {

            if ($key === $property) {
                return true;
            }
        }

        return $exists;
    }

    public static function modelSetPropertyValue($orig_value, $type, $id, $property, $value)
    {
        $rowset = Model::fetchRowset($type, false);

        foreach ($rowset as $key => $row) {

            if ($key === $property) {
                $res = $row->isValid($type, $id, $value);
                if ($res === true) {
                    return $row->sanitizeBeforeSet($type, $id, $value);
                } else {
                    throw new \UpStream_Model_ArgumentException($res);
                }
            }

        }

        return $orig_value;
    }

    /**
     * @param $outputFields the additional fields to be used by the model
     * @param $inputFields the fields from the database
     * @param $type
     * @param $id
     * @return mixed
     * @throws outputFields
     */
    public static function modelLoadFields($outputFields, $inputFields, $type, $id)
    {
        $rowset = Model::fetchRowset($type, false);

        foreach ($rowset as $key => $row) {

            if (isset($inputFields[$key])) {
                $outputFields[$key] = $row->loadFromString($type, $id, $inputFields[$key]);
            }

        }

        return $outputFields;
    }

    /**
     * @param $outputFields fields to be stored to the database
     * @param $inputFields fields from the model
     * @param $type
     * @param $id
     * @return outputFields
     */
    public static function modelStoreFields($outputFields, $inputFields, $type, $id)
    {
        $rowset = Model::fetchRowset($type, false);

        foreach ($rowset as $key => $row) {

            if (isset($inputFields[$key])) {
                $outputFields[$key] = $row->storeToObject($type, $id, $inputFields[$key]);
            }

        }

        return $outputFields;
    }


    public static function enqueueScriptsOnAdmin()
    {
        global $pagenow;

        $postType = get_post_type();
        $isAdmin  = is_admin();

        if ( ! $isAdmin
            || $postType !== 'project'
            || ! in_array($pagenow, ['post.php', 'post-new.php'])
        ) {
            return;
        }

        global $wp_scripts;

        if ( ! isset($wp_scripts->registered['up-select2'])) {
            wp_enqueue_script('up-select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js',
                [], UP_CUSTOM_FIELDS_VERSION, true);
        }

        wp_enqueue_script(UP_CUSTOM_FIELDS_SLUG . '-admin', UP_CUSTOM_FIELDS_URL . 'assets/js/admin-project.js',
            ['upstream-project'], UP_CUSTOM_FIELDS_VERSION, true);
    }

    public static function filterValidPostTypeAdminScript($postTypes)
    {
        $postTypes[] = 'up_custom_field';

        return $postTypes;
    }

    /**
     * Enqueue required scripts.
     *
     * @since   1.0.0
     * @static
     */
    public static function enqueueScripts()
    {
        $postType = get_post_type();

        if ($postType !== 'project' || is_admin()) {
            return;
        }

        wp_register_script('iris', admin_url('js/iris.min.js'),
            ['jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch'], UP_CUSTOM_FIELDS_VERSION);
        wp_register_script('wp-color-picker', admin_url('js/color-picker.min.js'), ['iris'],
            UP_CUSTOM_FIELDS_VERSION);
        wp_localize_script('wp-color-picker', 'wpColorPickerL10n', [
            'clear'         => esc_html__('Clear', 'cmb2'),
            'defaultString' => esc_html__('Default', 'cmb2'),
            'pick'          => esc_html__('Select Color', 'cmb2'),
            'current'       => esc_html__('Current Color', 'cmb2'),
        ]);

        wp_enqueue_script('wp-color-picker-alpha',
            UPSTREAM_PLUGIN_URL . 'includes/libraries/cmb2/js/wp-color-picker-alpha.min.js', ['wp-color-picker'],
            UP_CUSTOM_FIELDS_VERSION, true);

        wp_enqueue_script(UP_CUSTOM_FIELDS_SLUG, UP_CUSTOM_FIELDS_URL . 'assets/js/frontend-project.js',
            ['jquery', 'wp-color-picker'], UP_CUSTOM_FIELDS_VERSION, true);
        wp_localize_script(UP_CUSTOM_FIELDS_SLUG, '$l', [
            'LB_REMOVE'         => esc_html__('Remove', 'upstream-custom-fields'),
            'LB_UNDO'           => esc_html__('Undo', 'upstream-custom-fields'),
            'MSG_USE_THIS_FILE' => esc_html__('Use this file', 'upstream-custom-fields'),
        ]);
    }

    /**
     * Enqueue required stylesheets.
     *
     * @since   1.0.0
     * @static
     */
    public static function enqueueStyles()
    {
        $postType  = get_post_type();
        $isOnFront = ! is_admin();

        if ($postType !== 'project' || ! $isOnFront) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style(UP_CUSTOM_FIELDS_SLUG, UP_CUSTOM_FIELDS_URL . 'assets/css/upstream-custom-fields.css',
            ['wp-color-picker'], UP_CUSTOM_FIELDS_VERSION, 'all');
    }

    /**
     * Enqueue for admin all required stylesheets.
     *
     * @since   1.1.0
     * @static
     */
    public static function enqueueStylesOnAdmin()
    {
        $postType  = get_post_type();
        $isOnAdmin = is_admin();

        if ($postType !== 'project'
            || ! $isOnAdmin
        ) {
            return;
        }

        global $wp_styles;

        if ( ! isset($wp_styles->registered['up-select2'])) {
            wp_enqueue_style('up-select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css', [],
                UP_CUSTOM_FIELDS_VERSION, 'all');
        }
    }

    /**
     * Render Custom Fields on projects details.
     *
     * @param int $project_id
     *
     * @since   1.0.0
     * @static
     *
     */
    public static function renderFieldsOnProjectDetails($project_id)
    {
        $rowset = Model::fetchRowset('project');
        if (count($rowset) === 0) {
            return;
        }

        foreach ($rowset as $row) {
            if ($row->type === 'text') {
                $value = $row->getValue($project_id);
                if (strlen($value) > 0): ?>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <p class="title"><?php echo $row->label; ?></p>
                        <span><?php echo $value; ?></span>
                    </div>
                <?php endif;
            } elseif ($row->type === 'autoincrement') {
                $value = $row->getValue($project_id);
                if (strlen($value) > 0): ?>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <p class="title"><?php echo $row->label; ?></p>
                        <span><?php echo $value; ?></span>
                    </div>
                <?php endif;
            } elseif (in_array($row->type, ['radio', 'radio_inline'])) {
                $value = $row->getValue($project_id);
                if (strlen($value) > 0): ?>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <p class="title"><?php echo $row->label; ?></p>
                        <span><?php echo $value; ?></span>
                    </div>
                <?php endif;
            } elseif ($row->type === 'colorpicker') {
                $value = $row->getValue($project_id);
                if ( ! empty($value)): ?>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <p class="title"><?php echo $row->label; ?></p>
                        <div class="up-c-color-square has-tooltip" data-toggle="tooltip" title="<?php echo $value; ?>">
                            <div style="background-color: <?php echo $value; ?>"></div>
                        </div>
                    </div>
                <?php endif;
            } elseif (in_array($row->type, ['select', 'checkbox', 'multicheck', 'multicheck_inline'])) {
                $value = $row->getValue($project_id);
                if (count($value) > 0): ?>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <p class="title"><?php echo $row->label; ?></p>
                        <span><?php echo implode(', ', $value); ?></span>
                    </div>
                <?php endif;
            } elseif ($row->type === 'file') {
                $value = $row->getValue($project_id);
                if (strlen($value) > 0): ?>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <p class="title"><?php echo $row->label; ?></p>
                        <?php
                        // Convert to a local url
                        $path      = ABSPATH . str_replace(get_site_url() . '/', '', $value);
                        $imageData = 0;

                        if (file_exists($path)) {
                            $imgData = getimagesize($path);
                        }

                        if (empty($imgData)): ?>
                            <span>
                    <a href="<?php echo $value; ?>" target="_blank"
                       rel="noopener noreferrer"><?php echo basename($value); ?></a>
                  </span>
                        <?php else: ?>
                            <a href="<?php echo $value; ?>" target="_blank" rel="noopener noreferrer">
                                <img
                                    src="<?php echo $value; ?>"
                                    width="32"
                                    height="32"
                                    class="avatar itemfile"
                                    title="<?php echo basename($value); ?>"
                                    data-toggle="tooltip">
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif;
            } elseif ($row->type === 'category') {
                $values = $row->getValue($project_id);
                if (count($values) > 0) {
                    $values = array_map(function ($subject) {
                        $subject = trim(preg_replace('/^\-+/i', '', $subject));

                        return $subject;
                    }, $values);
                    ?>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <p class="title"><?php echo esc_html($row->label); ?></p>
                        <p><?php echo esc_html(implode(', ', $values)); ?></p>
                    </div>
                    <?php
                }
            } elseif (in_array($row->type, ['tag', 'user', 'country'])) {
                $values = array_filter((array)$row->getValue($project_id));

                if (count($values) > 0): ?>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <p class="title"><?php echo esc_html($row->label); ?></p>
                        <p><?php echo esc_html(implode(', ', $values)); ?></p>
                    </div>
                <?php endif;
            }
        }
    }

    /**
     * Append custom fields to milestones fields schema.
     *
     * @param array $schema Fields schema.
     *
     * @return  array
     * @since   1.2.0
     * @static
     *
     */
    public static function appendFieldsOnMilestones($schema)
    {
        $schema = self::appendFieldsOnItemType('milestone', $schema);

        return $schema;
    }

    /**
     * Append custom fields to items's schema where they're assigned to.
     *
     * @param string $itemType The item type.
     * @param array  $schema   The fields schema.
     *
     * @return  array
     * @since   1.2.0
     * @access  private
     * @static
     *
     */
    private static function appendFieldsOnItemType($itemType, $schema)
    {
        $comments = null;
        if (isset($schema['comments'])) {
            $comments = $schema['comments'];
            unset($schema['comments']);
        }

        $rowset = Model::fetchRowset($itemType, false);
        if (count($rowset) > 0) {
            foreach ($rowset as $row) {
                $column = [
                    'label'    => $row->label,
                    'isHidden' => true,
                ];

                if (in_array($row->type, ['text'])) {
                    $column['type'] = 'raw';
                } elseif ($row->type === 'autoincrement') {
                    $column['type'] = 'autoincrement';
                } elseif ($row->type === 'file') {
                    $column['type'] = 'file';
                } elseif ($row->type === 'colorpicker') {
                    $column['type'] = 'colorpicker';
                } elseif (in_array($row->type, ['radio', 'radio_inline'])) {
                    $column['type'] = 'radio';
                } elseif (in_array($row->type, ['checkbox', 'multicheck', 'multicheck_inline'])) {
                    $column['type'] = 'checkbox';
                } else {
                    $column['type']    = 'array';
                    $column['options'] = $row->getOptions();
                }

                $schema[$row->name] = $column;
            }
        }

        if ($comments !== null) {
            $schema['comments'] = $comments;
        }

        return $schema;
    }

    /**
     * Append custom fields to tasks fields schema.
     *
     * @param array $schema Fields schema.
     *
     * @return  array
     * @since   1.2.0
     * @static
     *
     */
    public static function appendFieldsOnTasks($schema)
    {
        $schema = self::appendFieldsOnItemType('task', $schema);

        return $schema;
    }

    /**
     * Append custom fields to bugs fields schema.
     *
     * @param array $schema Fields schema.
     *
     * @return  array
     * @since   1.2.0
     * @static
     *
     */
    public static function appendFieldsOnBugs($schema)
    {
        $schema = self::appendFieldsOnItemType('bug', $schema);

        return $schema;
    }

    /**
     * Append custom fields to files fields schema.
     *
     * @param array $schema Fields schema.
     *
     * @return  array
     * @since   1.2.0
     * @static
     *
     */
    public static function appendFieldsOnFiles($schema)
    {
        $schema = self::appendFieldsOnItemType('file', $schema);

        return $schema;
    }

    /**
     * Append custom fields to files fields schema.
     *
     * @param array $schema Fields schema.
     *
     * @return  array
     * @since   1.2.0
     * @static
     *
     */
    public static function appendFieldsOnProjects($schema)
    {
        $schema = self::appendFieldsOnItemType('project', $schema);

        return $schema;
    }

    /**
     * Define custom fields columns settings for Milestones table.
     *
     * @param array $columnsSchema Table columns settings.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function defineTableColumnsSettingsForMilestones($columnsSchema)
    {
        if (empty(self::$cachedMilestones)) {
            $rowset = Model::fetchRowset('milestone', false);

            self::$cachedMilestones = $rowset;
        } else {
            $rowset = self::$cachedMilestones;
        }

        if (count($rowset) > 0) {
            if (isset($columnsSchema['comments'])) {
                $comments = $columnsSchema['comments'];
                unset($columnsSchema['comments']);
            }

            foreach ($rowset as $row) {
                $args = [
                    'type'          => $row->type,
                    'display'       => true,
                    'heading'       => $row->label,
                    'heading_class' => 'none',
                    'row_class'     => '',
                    'attributes'    => [
                        'data-field-type' => $row->type,
                        'data-field-name' => $row->name,
                        'data-name'       => $row->name,
                    ],
                ];

                if (in_array($row->type, ['checkbox', 'multicheck', 'multicheck_inline'])) {
                    $args['type'] = 'checkbox';
                } elseif (in_array($row->type, ['radio', 'radio_inline'])) {
                    $args['type'] = 'radio';
                }

                $columnsSchema[$row->name] = $args;
            }

            if (isset($comments)) {
                $columnsSchema['comments'] = $comments;
            }
        }

        return $columnsSchema;
    }

    /**
     * Define custom fields columns settings for Tasks table.
     *
     * @param array $columnsSchema Table columns settings.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function defineTableColumnsSettingsForTasks($columnsSchema)
    {
        if (empty(self::$cachedTasks)) {
            $rowset = Model::fetchRowset('task', false);

            self::$cachedTasks = $rowset;
        } else {
            $rowset = self::$cachedTasks;
        }

        if (count($rowset) > 0) {
            if (isset($columnsSchema['comments'])) {
                $comments = $columnsSchema['comments'];
                unset($columnsSchema['comments']);
            }

            foreach ($rowset as $row) {
                $args = [
                    'type'          => $row->type,
                    'display'       => true,
                    'heading'       => $row->label,
                    'heading_class' => 'none',
                    'row_class'     => '',
                    'attributes'    => [
                        'data-field-type' => $row->type,
                        'data-field-name' => $row->name,
                        'data-name'       => $row->name,
                    ],
                ];

                if (in_array($row->type, ['checkbox', 'multicheck', 'multicheck_inline'])) {
                    $args['type'] = 'checkbox';
                } elseif (in_array($row->type, ['radio', 'radio_inline'])) {
                    $args['type'] = 'radio';
                }

                $columnsSchema[$row->name] = $args;
            }

            if (isset($comments)) {
                $columnsSchema['comments'] = $comments;
            }
        }

        return $columnsSchema;
    }

    /**
     * Define custom fields columns settings for Bugs table.
     *
     * @param array $columnsSchema Table columns settings.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function defineTableColumnsSettingsForBugs($columnsSchema)
    {
        if (empty(self::$cachedBugs)) {
            $rowset = Model::fetchRowset('bug', false);

            self::$cachedBugs = $rowset;
        } else {
            $rowset = self::$cachedBugs;
        }

        if (count($rowset) > 0) {
            if (isset($columnsSchema['comments'])) {
                $comments = $columnsSchema['comments'];
                unset($columnsSchema['comments']);
            }

            foreach ($rowset as $row) {
                $args = [
                    'type'          => $row->type,
                    'display'       => true,
                    'heading'       => $row->label,
                    'heading_class' => 'none',
                    'row_class'     => '',
                    'attributes'    => [
                        'data-field-type' => $row->type,
                        'data-field-name' => $row->name,
                        'data-name'       => $row->name,
                    ],
                ];

                if (in_array($row->type, ['checkbox', 'multicheck', 'multicheck_inline'])) {
                    $args['type'] = 'checkbox';
                } elseif (in_array($row->type, ['radio', 'radio_inline'])) {
                    $args['type'] = 'radio';
                }

                $columnsSchema[$row->name] = $args;
            }

            if (isset($comments)) {
                $columnsSchema['comments'] = $comments;
            }
        }

        return $columnsSchema;
    }

    /**
     * Define custom fields columns settings for Files table.
     *
     * @param array $columnsSchema Table columns settings.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function defineTableColumnsSettingsForFiles($columnsSchema)
    {
        if (empty(self::$cachedFiles)) {
            $rowset = Model::fetchRowset('file', false);

            self::$cachedFiles = $rowset;
        } else {
            $rowset = self::$cachedFiles;
        }

        if (count($rowset) > 0) {
            if (isset($columnsSchema['comments'])) {
                $comments = $columnsSchema['comments'];
                unset($columnsSchema['comments']);
            }

            foreach ($rowset as $row) {
                $args = [
                    'type'          => $row->type,
                    'display'       => true,
                    'heading'       => $row->label,
                    'heading_class' => 'none',
                    'row_class'     => '',
                    'attributes'    => [
                        'data-field-type' => $row->type,
                        'data-field-name' => $row->name,
                        'data-name'       => $row->name,
                    ],
                ];

                if (in_array($row->type, ['checkbox', 'multicheck', 'multicheck_inline'])) {
                    $args['type'] = 'checkbox';
                } elseif (in_array($row->type, ['radio', 'radio_inline'])) {
                    $args['type'] = 'radio';
                }

                $columnsSchema[$row->name] = $args;
            }

            if (isset($comments)) {
                $columnsSchema['comments'] = $comments;
            }
        }

        return $columnsSchema;
    }

    /**
     * Render form fields.
     *
     * @param string $section Area where the fields will be rendered.
     * @param array  $data
     *
     * @since   1.0.0
     * @static
     *
     */
    public static function renderFields($section, $data = [])
    {
        if ( ! in_array($section, ['milestone', 'task', 'bug', 'file', 'project'])) {
            return;
        }

        $fieldNamePrefix = 'upstream_' . $section . '_';

        $rowset = Model::fetchRowset($section, false);
        foreach ($rowset as $row) {
            $fieldName = $fieldNamePrefix . $row->name;
            $args      = [];
            if (isset($row->args)
                && ! empty($row->args)
                && is_array($row->args)
            ) {
                foreach ($row->args as $argKey => $argValue) {
                    $args[] = $argKey . '="' . $argValue . '"';
                }
            }

            // Make sure the default property is an array.
            if (empty($row->default) || ! is_array($row->default)) {
                //                $row->default = [];
            }

            $value = $row->default;
            if ( ! empty($data)) {
                if (isset($data[$row->name])) {
                    $value = $data[$row->name];
                } else {
                    // Get the value from meta.
                    $meta = get_post_meta($data['id'], $row->name, true);

                    $value = $meta;
                }
            }

            if ($row->type === 'text') {
                ?>
                <div class="row form_row_<?php print $row->name ?>">
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                        <label for="<?php echo $fieldName; ?>"><?php echo $row->label; ?></label>
                    </div>
                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                        <div
                            class="form-group up-c-custom-field-form-group <?php echo $row->required ? 'is-required' : ''; ?> up-<?php echo $fieldName; ?>"
                            data-field-type="<?php echo $row->type; ?>">
                            <input type="text" id="<?php echo $fieldName; ?>" name="data[<?php echo $row->name; ?>]"
                                   class="form-control col-md-7 col-xs-12<?php echo $row->required ? ' required' : ''; ?>" <?php echo count($args) > 0 ? implode(' ',
                                $args) : ''; ?> value="<?php echo (string)$value; ?>">
                            <?php if (strlen($row->description) > 0): ?>
                                <p class="help-block"><?php echo $row->description; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                </div>
                <?php
            } elseif ($row->type === 'autoincrement') {
                ?>
                <div class="row form_row_<?php print $row->name ?>">
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                        <label><?php echo $row->label; ?></label>
                    </div>
                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                        <div
                            class="form-group up-c-custom-field-form-group up-<?php echo $fieldName; ?>"
                            data-field-type="<?php echo $row->type; ?>" data-field-id="<?php echo $row->id; ?>"
                            data-name="<?php echo $row->name; ?>">

                            <div class="up-autoincrement"><?php echo __('---', 'upstream-custom-fields'); ?></div>

                            <?php if (strlen($row->description) > 0): ?>
                                <p class="help-block"><?php echo $row->description; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                </div>

                <?php
            } elseif ($row->type === 'select') {
                $options = $row->getOptions();
                ?>
                <div class="row form_row_<?php print $row->name ?>">
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                        <label for="<?php echo $fieldName; ?>"><?php echo $row->label; ?></label>
                    </div>
                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                        <div
                            class="form-group up-c-custom-field-form-group <?php echo $row->required ? 'is-required' : ''; ?> up-<?php echo $fieldName; ?>"
                            data-field-type="<?php echo $row->type; ?>">
                            <select class="form-control" name="data[<?php echo $row->name; ?>]"
                                    id="<?php echo $fieldName; ?>" <?php echo count($args) > 0 ? implode(' ',
                                $args) : ''; ?>>
                                <?php if ( ! $row->required): ?>
                                    <option value=""><?php _e('None', 'upstream'); ?></option>
                                <?php endif; ?>

                                <?php foreach ($options as $optionValue => $optionName): ?>
                                    <option value="<?php echo $optionValue; ?>" <?php selected($value,
                                        $optionValue); ?>><?php echo $optionName; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (strlen($row->description) > 0): ?>
                                <p class="help-block"><?php echo $row->description; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                </div>
                <?php
            } elseif ($row->type === 'file') {
                $buttonLabel = strlen($row->buttonText) > 0 ? $row->buttonText : __('Add or Upload File', 'cmb2');
                ?>
                <div class="row form_row_<?php print $row->name ?>">
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                        <label for="<?php echo $fieldName; ?>"><?php echo $row->label; ?></label>
                    </div>
                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                        <div
                            class="form-group up-c-custom-field-form-group <?php echo $row->required ? 'is-required' : ''; ?> up-<?php echo $fieldName; ?>"
                            data-field-type="<?php echo $row->type; ?>" data-name="<?php echo $row->name; ?>">
                            <input type="hidden" name="data[<?php echo $row->name; ?>_id]" value="">
                            <input type="hidden" id="<?php echo $fieldName; ?>_url"
                                   name="data[<?php echo $row->name; ?>]">
                            <button type="button" id="<?php echo $fieldName; ?>"
                                    class="btn btn-default btn-xs o-btn-media"
                                    data-title="<?php echo esc_attr($row->label); ?>"
                                    data-name="data[<?php echo $row->name; ?>]">
                                <i class="fa fa-upload"></i> <?php echo $buttonLabel; ?>
                            </button>
                            <?php if (strlen($row->description) > 0): ?>
                                <p class="help-block"><?php echo $row->description; ?></p>
                            <?php endif; ?>
                            <div class="file-preview"></div>
                        </div>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                </div>
                <?php
            } elseif ($row->type === 'radio' || $row->type === 'radio_inline') {
                if (is_array($row->default) && empty($row->default)) {
                    $defaultValue = '';
                } else {
                    $defaultValue = (string)$row->default;
                }

                $options = $row->getOptions();
                ?>
                <div class="row form_row_<?php print $row->name ?>">
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                        <label for="<?php echo $fieldName; ?>"><?php echo $row->label; ?></label>
                    </div>
                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                        <div
                            class="form-group up-c-custom-field-form-group <?php echo $row->required ? 'is-required' : ''; ?> up-<?php echo $fieldName; ?>"
                            data-field-type="radio">
                            <div
                                class="up-c-input-radio <?php echo $row->type === 'radio_inline' ? 's-inline' : ''; ?>">
                                <?php if ($row->showNoneOption): ?>
                                    <div>
                                        <label>
                                            <input type="radio" id="<?php echo $fieldName . '_none'; ?>"
                                                   name="data[<?php echo $row->name; ?>]"
                                                   value=""<?php echo strlen($defaultValue) === 0 ? ' data-selected-by-default' : ''; ?>>&nbsp;
                                            <?php _e('None', 'upstream'); ?>
                                        </label>
                                    </div>
                                <?php endif; ?>

                                <?php foreach ($options as $optionValue => $optionName): ?>
                                    <div>
                                        <label>
                                            <input type="radio" data-def="<?php echo $defaultValue; ?>"
                                                   data-v="<?php echo $optionValue; ?>"
                                                   data-c="<?php echo (int)$defaultValue === $optionValue; ?>"
                                                   id="<?php echo $fieldName . '_' . $optionValue; ?>"
                                                   name="data[<?php echo $row->name; ?>]"
                                                   value="<?php echo $optionValue; ?>"<?php echo $defaultValue === $optionValue ? ' data-selected-by-default' : ''; ?>
                                                <?php checked($value, $optionValue); ?>>&nbsp;
                                            <?php echo $optionName; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (strlen($row->description) > 0): ?>
                                <p class="help-block"><?php echo $row->description; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                </div>
                <?php
            } elseif ($row->type === 'multicheck' || $row->type === 'multicheck_inline') {
                $options = $row->getOptions();
                if ( ! is_array($row->default)) {
                    $row->default = [];
                }
                ?>
                <div class="row form_row_<?php print $row->name ?>">
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                        <label for="<?php echo $fieldName; ?>"><?php echo $row->label; ?></label>
                    </div>
                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                        <div
                            class="form-group up-c-custom-field-form-group up-<?php echo $fieldName; ?> <?php echo $row->required ? 'is-required' : ''; ?>"
                            data-field-type="checkbox">
                            <?php if ($row->showSelectAllBtn): ?>
                                <button type="button" class="btn btn-default btn-xs"
                                        data-action="toggle-selection"><?php _e('Select / Deselect All',
                                        'cmb2'); ?></button>
                            <?php endif; ?>
                            <div
                                class="up-c-input-checkbox <?php echo $row->type === 'multicheck_inline' ? 's-inline' : ''; ?>">
                                <?php foreach ($options as $optionValue => $optionName): ?>
                                    <div>
                                        <label>
                                            <input type="checkbox" id="<?php echo $fieldName . '_' . $optionValue; ?>"
                                                   name="data[<?php echo $row->name; ?>][]"
                                                   value="<?php echo $optionValue; ?>"<?php echo in_array($optionValue,
                                                $row->default) ? ' data-selected-by-default' : ''; ?>
                                                <?php
                                                if (is_array($value)) {
                                                    if (in_array($optionValue, $value)) {
                                                        echo "checked";
                                                    }
                                                }
                                                else {
                                                    checked($value, $optionValue);
                                                }

                                                ?>>&nbsp;
                                            <?php echo $optionName; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (strlen($row->description) > 0): ?>
                                <p class="help-block"><?php echo $row->description; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                </div>
                <?php
            } elseif ($row->type === 'colorpicker') {
                if ( ! $row->disableAlpha) {
                    $args[] = 'data-alpha="1"';
                }

                $args[] = 'data-palettes="' . ((int) ! $row->disablePalettes) . '"';
                ?>
                <div class="row form_row_<?php print $row->name ?>">
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                        <label for="<?php echo $fieldName; ?>"><?php echo $row->label; ?></label>
                    </div>
                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                        <div
                            class="form-group up-c-custom-field-form-group <?php echo $row->required ? 'is-required' : ''; ?> up-<?php echo $fieldName; ?>"
                            data-field-type="<?php echo $row->type; ?>">
                            <input type="text" id="<?php echo $fieldName; ?>" name="data[<?php echo $row->name; ?>]"
                                   class="form-control col-md-7 col-xs-12<?php echo $row->required ? ' required' : ''; ?>" <?php echo count($args) > 0 ? implode(' ',
                                $args) : ''; ?> value="<?php echo (string)$row->default; ?>"
                                   value="">
                            <?php if (strlen($row->description) > 0): ?>
                                <p class="help-block"><?php echo $row->description; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                </div>
                <?php
            } elseif ($row->type === 'category') {
                $options = $row->getOptions();
                ?>
                <div class="row form_row_<?php print $row->name ?>">
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                        <label for="<?php echo $fieldName; ?>"><?php echo $row->label; ?></label>
                    </div>
                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                        <div
                            class="form-group up-c-custom-field-form-group <?php echo $row->required ? 'is-required' : ''; ?> up-<?php echo $fieldName; ?>"
                            data-field-type="<?php echo $row->type; ?>">
                            <select class="form-control"
                                    name="data[<?php echo $row->name; ?>]<?php echo $row->allowMultipleSelection ? '[]' : ''; ?>"
                                    id="<?php echo $fieldName; ?>" <?php echo count($args) > 0 ? implode(' ',
                                $args) : ''; ?> <?php echo $row->allowMultipleSelection ? 'multiple="multiple"' : ''; ?>>
                                <?php if ( ! $row->required && ! $row->allowMultipleSelection): ?>
                                    <option value=""><?php _e('None', 'upstream'); ?></option>
                                <?php endif; ?>

                                <?php foreach ($options as $optionValue => $optionName): ?>
                                    <option value="<?php echo $optionValue; ?>"
                                        <?php selected($value, $optionValue); ?>><?php echo $optionName; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (strlen($row->description) > 0): ?>
                                <p class="help-block"><?php echo $row->description; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                </div>
                <?php
            } elseif (in_array($row->type, ['tag', 'user', 'country'])) {
                $options = $row->getOptions();
                ?>
                <div class="row form_row_<?php print $row->name ?>">
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                        <label for="<?php echo $fieldName; ?>"><?php echo $row->label; ?></label>
                    </div>
                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                        <div
                            class="form-group up-c-custom-field-form-group <?php echo $row->required ? 'is-required' : ''; ?> up-<?php echo $fieldName; ?>"
                            data-field-type="<?php echo $row->type; ?>">
                            <select class="form-control"
                                    name="data[<?php echo $row->name; ?>]<?php echo $row->allowMultipleSelection ? '[]' : ''; ?>"
                                    id="<?php echo $fieldName; ?>" <?php echo count($args) > 0 ? implode(' ',
                                $args) : ''; ?> <?php echo $row->allowMultipleSelection ? 'multiple="multiple"' : ''; ?>>
                                <?php if ( ! $row->required && ! $row->allowMultipleSelection): ?>
                                    <option value=""><?php _e('None', 'upstream'); ?></option>
                                <?php endif; ?>

                                <?php foreach ($options as $optionValue => $optionName): ?>
                                    <?php
                                    if (is_array($value)) {
                                        $selectedAttribute = in_array($optionValue,
                                            $value) ? 'selected="selected"' : '';
                                    } else {
                                        $selectedAttribute = selected($value, $optionValue);
                                    }
                                    ?>
                                    <option value="<?php echo $optionValue; ?>"
                                        <?php echo $selectedAttribute; ?>><?php echo $optionName; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (strlen($row->description) > 0): ?>
                                <p class="help-block"><?php echo $row->description; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                </div>
                <?php
            }
        }
    }

    /**
     * Called before an item is saved on frontend.
     *
     * @param array  $rowset     Rowset being saved.
     * @param int    $project_id Current project id.
     * @param string $section    Section where the item belongs to.
     * @param string $metaKey    Meta key where the item will be saved at.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function onBeforeSave($rowset, $project_id, $section, $metaKey)
    {
        $fields = Model::fetchRowset(rtrim($section, 's'));

        $item = null;
        if (isset($_POST['editing'])) {
            foreach ($rowset as $rowIndex => $row) {
                if (isset($row['id']) && $row['id'] === (int)$_POST['editing']) {
                    $item = &$rowset[$rowIndex];
                    break;
                }
            }
        } else {
            foreach ($rowset as $rowIndex => $row) {
                if ( ! isset($row['id'])) {
                    $item = &$rowset[$rowIndex];
                    break;
                }
            }
        }

        if ( ! function_exists('wp_read_image_metadata')) {
            include_once ABSPATH . 'wp-admin/includes/image.php';
        }

        if ( ! function_exists('wp_handle_upload')) {
            include_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if ( ! function_exists('media_handle_upload')) {
            include_once ABSPATH . 'wp-admin/includes/media.php';
        }

        foreach ($fields as $customField) {
            if ($customField->type === 'file') {
                $removeFlagName = $customField->name . '_remove';
                if (isset($_POST[$removeFlagName])) {
                    if ((bool)$_POST[$removeFlagName]) {
                        $_POST[$customField->name . '_id'] = 0;
                        $item[$customField->name . '_id']  = 0;
                        $item[$customField->name]          = '';
                    }
                    unset($_POST[$removeFlagName]);
                };
            }

            if ($section === 'milestones') {
                if (isset($_POST['data']) && isset($_POST['data'][$customField->name])) {
                    update_post_meta($item['id'], $customField->name, $_POST['data'][$customField->name]);
                }
            }
        }

        return $rowset;
    }

    /**
     * @param $projectId
     */
    public static function saveProjectFields($projectId)
    {
        $projectId = (int)$projectId;

        if (empty($projectId)) {
            return;
        }

        $fields = Model::fetchRowset('project');
        if (count($_POST['data']) > 0) {
            foreach ($_POST['data'] as $key => $value) {
                $_POST[$key] = $value;
            }
        }

        // Check all fields
        if ( ! empty($fields)) {
            foreach ($fields as $field) {
                if (isset($_POST[$field->name])) {
                    if (is_array($_POST[$field->name])) {
                        // Remove empty values
                        $value = array_filter($_POST[$field->name]);
                        if (!empty($value) && ($field->type == 'category' || $field->type == 'tag' || $field->type == 'user' || $field->type == 'country')) {
                            if (substr_count($value[0], ',')) {
                                $value = explode(',', $value[0]);
                            }
                            if ($field->type != 'country') {
                                $value = array_map('intval', $value);
                            }
                        }
                        update_post_meta($projectId, $field->name, $value);
                    } else {
                        update_post_meta($projectId, $field->name, $_POST[$field->name]);
                    }
                }
            }
        }
    }

    /**
     * @param $data
     * @param $projectId
     */
    public static function filterProjectData($data, $projectId)
    {
        $convertToObject = false;
        if (is_object($data)) {
            $data            = (array)$data;
            $convertToObject = true;
        }

        $projectId = (int)$projectId;

        if (empty($projectId)) {
            return $data;
        }

        $fields = Model::fetchRowset('project');

        // Check all fields
        if ( ! empty($fields)) {
            foreach ($fields as $field) {
                $meta = get_post_meta($projectId, $field->name, true);

                $type = $field->type;

                if (in_array($type, ['checkbox', 'multicheck', 'multicheck_inline'])) {
                    $type = 'checkbox';
                } elseif (in_array($type, ['radio', 'radio_inline'])) {
                    $type = 'radio';
                }

                if ($type === 'autoincrement') {
                    $model = AutoincrementModel::getInstance();
                    if (empty($meta)) {
                        // Get the autoincrement value
                        $meta = $model->getAutoincrementStringForProject($projectId, $field->id);
                    } else {
                        $meta = $model->getAutoincrementFullString($meta, $field->id);
                    }
                }

                $meta = maybe_unserialize($meta);

                if (in_array($type, ['user', 'tag', 'category', 'country']) && is_array($meta) && ! empty($meta)) {
//                    $meta = explode(',', $meta[0]);
                    // RSD: should be separate items in the array, not comma separated -- handle both
                    $arr = [];
                    foreach ($meta as $m) {
                        $arr = array_merge($arr, explode(',', $m));
                    }
                }

                // If an array, reset the indexes.
                if (is_array($meta)) {
                    $meta = array_values($meta);
                }

                $field_data = [
                    'type'  => $type,
                    'value' => $meta,
                ];


                // If file, we need to get the attachment id.
                if ($type === 'file') {
                    $meta = get_post_meta($projectId, $field->name . '_id', true);

                    $field_data['id']    = $meta;
                    $field_data['title'] = basename($field_data['value']);
                }

                $data[$field->name] = $field_data;
            }
        }

        if ($convertToObject) {
            $data = (object)$data;
        }

        return $data;
    }

    /**
     * Render custom fields filters within a data table on frontend.
     *
     * @param array $tableSettings Current data table args.
     * @param array $columnsSchema Data table columns schema.
     * @param int   $projectId     Project ID.
     *
     * @since   1.2.0
     * @static
     *
     */
    public static function renderFrontendTableFilters($tableSettings, $columnsSchema, $projectId = null)
    {
        $rowset = Model::fetchFilterableFieldsForType($tableSettings['type'], false);
        if (count($rowset) > 0) {
            foreach ($rowset as $row) {
                if (in_array($row->type, ['text', 'autoincrement', 'colorpicker'])) {
                    $filterType = 'search';
                    $filterArgs = [
                        'operator' => $row->type === 'colorpicker' ? 'exact' : 'contains',
                        'attrs'    => [
                            'placeholder' => $row->label,
                            'width'       => 200,
                        ],
                    ];
                } else {
                    $filterType = 'select';
                    $filterArgs = [
                        'operator' => 'contains',
                        'options'  => $row->getOptions(),
                        'attrs'    => [
                            'data-placeholder' => $row->label,
                        ],
                    ];
                }

                $filterArgs['icon'] = true;

                \UpStream\Frontend\renderTableFilter($filterType, $row->name, $filterArgs);
            }
        }
    }

    /**
     * Render custom fields columns' header within a data table on frontend.
     *
     * @static
     *
     * @param array $tableSettings Current data table args.
     * @param array $columnsSchema Data table columns schema.
     * @param int   $projectId     Project ID.
     */
    public static function renderFrontendTableColumnHeaders($tableSettings, $columnsSchema, $projectId = null)
    {
        $rowset = Model::fetchColumnFieldsForType($tableSettings['type'], false);

        if (count($rowset) > 0) {
            foreach ($rowset as $row) {
                $data = [
                    'isOrderable' => true,
                    'label'       => $row->label,
                ];

                \UpStream\Frontend\renderTableHeaderColumn($row->name, $data);
            }
        }
    }

    /**
     * Render custom fields columns' cells within a data table on frontend.
     *
     * @static
     *
     * @param array $tableSettings Current data table args.
     * @param array $columnsSchema Data table columns schema.
     * @param int   $projectId     Project ID.
     * @param mixed $row
     */
    public static function renderFrontendTableColumnData($tableSettings, $columnsSchema, $projectId = null, $row)
    {
        $rowset = Model::fetchColumnFieldsForType($tableSettings['type'], false);

        if (count($rowset) > 0) {
            foreach ($rowset as $field) {

                if (is_object($row)) {
                    $row = (array)$row;
                }

                /*
                 * RSD: WHY IS THIS HERE
                if (isset($row[$field->name])) {
                    $value = $row[$field->name];
                } else {
                    // For projects
                    $value = $field->getValue($projectId);
                }
                */

                $value = isset($row[$field->name]) ? $row[$field->name] : '';


                if (is_array($value) && isset($value['value'])) {
                    $value = $value['value'];
                }

                $valueStr = is_array($value) ? implode(', ', $value) : $value;

                echo '<td data-column="' . $field->name . '" data-value="' . $valueStr . '">';

                if (!upstream_override_access_field(true, $tableSettings['type'], $row['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, $field->name, UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                    ?>
                    <span class="label up-o-label"
                          style="background-color:#666;color:#fff">(hidden)</span>
                    <?php
                } elseif ($field->type === 'text') {
                    if (strlen($value) > 0): ?>
                        <div>
                            <span><?php echo $value; ?></span>
                        </div>
                    <?php endif;
                } elseif ($field->type === 'autoincrement') {
                    if (strlen($value) > 0): ?>
                        <div>
                            <span><?php echo $value; ?></span>
                        </div>
                    <?php endif;
                } elseif (in_array($field->type, ['radio', 'radio_inline'])) {
                    if (is_array($value)) {
                        $value = $value[0];
                    }

                    if (isset($field->options[$value])) {
                        $value = $field->options[$value];
                    }

                    if (strlen($value) > 0): ?>
                        <div>
                            <span><?php echo $value; ?></span>
                        </div>
                    <?php endif;
                } elseif ($field->type === 'colorpicker') {
                    if ( ! empty($value)): ?>
                        <div>
                            <div class="up-c-color-square has-tooltip" data-toggle="tooltip"
                                 title="<?php echo $value; ?>">
                                <div style="background-color: <?php echo $value; ?>"></div>
                            </div>
                        </div>
                    <?php endif;
                } elseif (in_array($field->type, ['select', 'checkbox', 'multicheck', 'multicheck_inline'])) {
                    if ( ! is_array($value)) {
                        $value = [$value => $value];
                    }

                    if (is_array($value) && count($value) > 0): ?>
                        <?php
                        $output = [];

                        foreach ($value as $selected => $label) {
                            if (isset($field->options[$label])) {
                                $selected = $field->options[$label];

                                $output[] = $selected;
                            }
                        }

                        $output = implode(', ', $output);
                        ?>
                        <div>
                            <span><?php echo $output ?></span>
                        </div>
                    <?php endif;
                } elseif ($field->type === 'file') {
                    if (strlen($value) > 0): ?>
                        <div>
                            <?php
                            // Convert to a local url
                            $path = ABSPATH . str_replace(get_site_url() . '/', '', $value);

                            $imgData = getimagesize($path);
                            if (empty($imgData)): ?>
                                <span>
                    <a href="<?php echo $value; ?>" target="_blank"
                       rel="noopener noreferrer"><?php echo basename($value); ?></a>
                  </span>
                            <?php else: ?>
                                <a href="<?php echo $value; ?>" target="_blank" rel="noopener noreferrer">
                                    <img
                                        src="<?php echo $value; ?>"
                                        width="32"
                                        height="32"
                                        class="avatar itemfile"
                                        title="<?php echo basename($value); ?>"
                                        data-toggle="tooltip">
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif;
                } elseif ($field->type === 'category') {

                    if (!empty($tableSettings['id']) && $tableSettings['id'] === 'projects') {
                        $value = array_filter((array)$field->getValue($projectId));
                    } else {
                        $options = $field->getOptions();

                        $selectedValues = $value;
                        $selectedValuesLabels = [];
                        foreach ($selectedValues as $selectedValue) {
                            if (isset($options[$selectedValue])) {
                                $selectedValuesLabels[] = $options[$selectedValue];
                            }
                        }

                        $value = $selectedValuesLabels;
                    }

                    if (count($value) > 0) {
                        $value = array_map(function ($subject) {
                            $subject = trim(preg_replace('/^\-+/i', '', $subject));

                            return $subject;
                        }, $value);
                        ?>
                        <div>
                            <p><?php echo esc_html(implode(', ', $value)); ?></p>
                        </div>
                        <?php
                    }
                } elseif ($field->type === 'country') {

                    $options = Model::getCountries();
                    $outVal = [];
                    if (!empty($value)) {
                        if (!is_array($value)) $value = [$value];
                        foreach ($value as $v) {
                            if (isset($options[$v])) {
                                $outVal[] = $options[$v];
                            } else {
                                $outVal[] = $v;
                            }
                        }
                    }

                    if (count($outVal) > 0): ?>
                        <div>
                            <p><?php echo esc_html(implode(', ', $outVal)); ?></p>
                        </div>
                    <?php endif;

                } elseif (in_array($field->type, ['tag', 'user'])) {
                    // RSD: this doesnt work with tasks, etc...
                    //$value = array_filter((array)$field->getValue($projectId));

                    $options = $field->getOptions();
                    $outVal = [];
                    if (!empty($value)) {
                        if (!is_array($value)) $value = [$value];
                        foreach ($value as $v) {
                            $outVal[] = $options[$v];
                        }
                    }

                    if (count($outVal) > 0): ?>
                        <div>
                            <p><?php echo esc_html(implode(', ', $outVal)); ?></p>
                        </div>
                    <?php endif;
                }

                echo '</td>';
            }
        }
    }

    public static function saveMetaboxField($args)
    {
        if ($args['field_id'] === '_upstream_project_milestones') {
            if ( ! empty($args['value'])) {
                $fields = Model::fetchRowset('milestone');

                if ( ! empty($fields)) {
                    foreach ($args['value'] as $data) {
                        foreach ($fields as $field) {
                            if (isset($data[$field->name])) {
                                if (is_array($data[$field->name])) {
                                    // Remove empty values
                                    $value = array_filter($data[$field->name]);
                                    update_post_meta($data['id'], $field->name, $value);
                                } else {
                                    $value = sanitize_text_field($data[$field->name]);
                                    update_post_meta($data['id'], $field->name, $value);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function saveMilestone($milestoneId, $milestoneData)
    {
        if (isset($_POST['data']) && is_array($_POST['data'])) {
            $fields = Model::fetchRowset('milestone');
            $data   = $_POST['data'];

            if ( ! empty($fields)) {
                foreach ($fields as $field) {
                    if (isset($data[$field->name])) {
                        if (is_array($data[$field->name])) {
                            // Remove empty values
                            $value = array_filter($data[$field->name]);
                            update_post_meta($milestoneId, $field->name, $value);
                        } else {
                            $value = sanitize_text_field($data[$field->name]);
                            update_post_meta($milestoneId, $field->name, $value);
                        }
                    }
                }
            }
        }
    }

    public static function getMilestoneRowsetAdditionalFields($data)
    {
        $fields = Model::fetchRowset('milestone');

        if ( ! empty($fields)) {
            foreach ($data as $milestoneId => &$milestone) {
                foreach ($fields as $field) {
                    $meta = get_post_meta($milestoneId, $field->name, true);

                    $milestone[$field->name] = $meta;
                }
            }
        }

        return $data;
    }

    public static function getMilestoneAdditionalFields($data)
    {
        $fields = Model::fetchRowset('milestone');

        if ( ! empty($fields)) {
            foreach ($fields as $field) {
                $meta = upstream_cache_get_post_meta($data['id'], $field->name, true);

                $data[$field->name] = $meta;
            }
        }

        return $data;
    }
}
