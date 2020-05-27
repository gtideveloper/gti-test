<?php

namespace UpStream\Plugins\CustomFields\Pages;

// Prevent direct access.
use UpStream\Plugins\CustomFields\Model;

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Handle actions on the Custom Fields list on admin.
 *
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Pages
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class Collection
{
    /**
     * Current namespace.
     *
     * @since   1.0.0
     * @static
     *
     * @var     string $namespace
     */
    public static $namespace = UP_CUSTOM_FIELDS_NAMESPACE . '\\Pages\\Collection';

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
        add_action('admin_enqueue_scripts', [self::$namespace, 'enqueueStyles']);
        add_filter('manage_up_custom_field_posts_columns', [self::$namespace, 'renderAdditionalColumnsHeaders'], 10,
            1);
        add_action('manage_up_custom_field_posts_custom_column', [self::$namespace, 'renderAdditionalColumnsValues'],
            10, 2);
        add_action('restrict_manage_posts', [self::$namespace, 'renderTableFilters']);
        add_action('parse_query', [self::$namespace, 'filterTableQuery'], 10, 1);
    }

    /**
     * Enqueue required stylesheets.
     *
     * @since   1.0.0
     * @static
     */
    public static function enqueueStyles()
    {
        $postType = get_post_type();
        $isAdmin  = is_admin();

        if ( ! $isAdmin
             || $postType !== UP_CUSTOM_FIELDS_POST_TYPE
        ) {
            return;
        }

        global $pagenow;
        if ($pagenow === 'edit.php') {
            wp_enqueue_style(UP_CUSTOM_FIELDS_SLUG . ':admin-list', UP_CUSTOM_FIELDS_URL . 'assets/css/collection.css',
                [], UP_CUSTOM_FIELDS_VERSION, 'all');
        }
    }

    /**
     * Define additional column headers for Custom Fields table list.
     *
     * @param array $columns Additional column headers.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function renderAdditionalColumnsHeaders($columns)
    {
        $columns['name']     = __('Slug', 'upstream-custom-fields');
        $columns['type']     = __('Type', 'upstream-custom-fields');
        $columns['required'] = __('Required', 'upstream-custom-fields');
        $columns['usage']    = __('Used on', 'upstream-custom-fields');

        return $columns;
    }

    /**
     * Render additional columns values from a given Custom Field.
     *
     * @param array $columns Current column name.
     * @param int   $post_id Current custom field.
     *
     * @since   1.0.0
     * @static
     *
     */
    public static function renderAdditionalColumnsValues($columnName, $post_id)
    {
        $itemTypes = [];

        if ($columnName === 'name') {
            $post = get_post($post_id);
            $columnValue = $post->post_name;
            echo $columnValue;
        } elseif ($columnName === 'type') {
            $meta = get_post_meta($post_id, '_upstream__custom-fields:type', true);

            if ($meta === 'text') {
                $columnValue = __('Text', 'upstream-custom-fields');
            } elseif ($meta === 'autoincrement') {
                $columnValue = __('Autoincrement', 'upstream-custom-fields');
            } elseif ($meta === 'select') {
                $columnValue = __('Dropdown', 'upstream-custom-fields');
            } elseif ($meta === 'file') {
                $columnValue = __('File', 'upstream-custom-fields');
            } elseif ($meta === 'colorpicker') {
                $columnValue = __('Color Picker', 'upstream-custom-fields');
            } elseif ($meta === 'radio' || $meta === 'radio_inline') {
                $columnValue = __('Radio Buttons', 'upstream-custom-fields');
            } elseif (in_array($meta, ['checkbox', 'multicheck', 'multicheck_inline'])) {
                $columnValue = __('Checkbox', 'upstream-custom-fields');
            } elseif ($meta === 'category') {
                $columnValue = __('Category', 'upstream');
            } elseif ($meta === 'tag') {
                $columnValue = __('Tag', 'upstream');
            } elseif ($meta === 'user') {
                $columnValue = __('User', 'upstream-custom-fields');
            } elseif ($meta === 'country') {
                $columnValue = __('Country', 'upstream-custom-fields');
            } else {
                $columnValue = '<i class="u-color-gray">' . __('none', 'upstream') . '</i>';
            }

            echo $columnValue;
        } elseif ($columnName === 'required') {
            $meta = strtoupper(get_post_meta($post_id, '_upstream__custom-fields:is_required', true));

            if ($meta === 'YES') {
                $columnValue = '<span class="dashicons dashicons-yes" style="color: rgba(39, 174, 96, 1);"></span>';
            } else {
                $columnValue = '<span class="dashicons dashicons-minus u-color-gray"></span>';
            }

            echo $columnValue;
        } elseif ($columnName === 'usage') {
            $meta = (array)get_post_meta($post_id, '_upstream__custom-fields:usage', true);
            $meta = array_filter($meta);

            if (count($meta) > 0) {
                $sectionsLabels = [
                    'project'   => upstream_project_label_plural(),
                    'milestone' => upstream_milestone_label_plural(),
                    'task'      => upstream_task_label_plural(),
                    'bug'       => upstream_bug_label_plural(),
                    'file'      => upstream_file_label_plural(),
                    'client'    => upstream_client_label_plural(),
                ];

                $columnValue = [];
                foreach ($meta as $section) {
                    if (isset($sectionsLabels[$section])) {
                        $columnValue[] = $sectionsLabels[$section];
                    }
                }

                $columnValue = implode(', ', $columnValue);
            } else {
                $columnValue = '<i class="u-color-gray">' . __('none', 'upstream') . '</i>';
            }

            echo $columnValue;
        }
    }

    /**
     * Render custom filters.
     *
     * @since   1.0.0
     * @static
     */
    public static function renderTableFilters()
    {
        global $pagenow;

        $isAdmin  = is_admin();
        $postType = get_post_type();

        if ( ! $isAdmin
             || $pagenow !== 'edit.php'
             || $postType !== UP_CUSTOM_FIELDS_POST_TYPE
        ) {
            return;
        }

        $fieldTypes = Model::getFieldTypes();

        $fieldTypeSelected = isset($_GET['type']) && isset($fieldTypes[$_GET['type']]) ? $_GET['type'] : '';
        ?>
        <select name="type" class="postform">
            <option value=""><?php printf(__('All %s', 'upstream'),
                    __('Types', 'upstream-custom-fields')); ?></option>
            <?php foreach ($fieldTypes as $fieldTypeId => $fieldTypeValue): ?>
                <option
                    value="<?php echo $fieldTypeId; ?>"<?php echo $fieldTypeSelected === $fieldTypeId ? ' selected' : ''; ?>><?php echo $fieldTypeValue; ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Filter table query based on custom filters.
     *
     * @param   &\WP_Query   Query to be executed.
     *
     * @since   1.0.0
     * @static
     *
     */
    public static function filterTableQuery($query)
    {
        global $pagenow;

        $isAdmin  = is_admin();
        $postType = get_post_type();
        if (empty($postType)) {
            $postType = isset($_GET['post_type']) ? $_GET['post_type'] : '';
        }

        if ( ! $isAdmin
             || $pagenow !== 'edit.php'
             || $postType !== UP_CUSTOM_FIELDS_POST_TYPE
        ) {
            return;
        }

        $fieldTypes = array_keys(Model::getFieldTypes());

        $fieldType = isset($_GET['type']) && in_array($_GET['type'], $fieldTypes) ? $_GET['type'] : null;
        if (empty($fieldType)) {
            return;
        }

        $query->query_vars['meta_key']   = '_upstream__custom-fields:type';
        $query->query_vars['meta_value'] = $fieldType;
    }
}
