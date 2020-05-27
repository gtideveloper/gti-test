<?php

namespace UpStream\Plugins\CustomFields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\CustomFields\Fields\Autoincrement;
use UpStream\Plugins\CustomFields\Traits\Singleton;

/**
 *
 *
 * @package     UpStream\Plugins\CustomFields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class AutoincrementModel
{
    use Singleton;

    public function attachHooks()
    {
        add_action('upstream:project.updateProjectMeta', [$this, 'updateProjectMeta'], 10, 2);
        add_action('upstream:customField.processAutoincrement.project', [$this, 'processAutoincrementForProject'], 10,
            2);
        add_filter('cmb2_override_meta_value', [$this, 'cmb2OverrideMetaValue'], 10, 4);
    }

    /**
     * Called when the project metadata is updated by UpStream. This method checks if
     * there is any autoincrement for the item type: project, tasks, etc. If none, just
     * ignore. If found any, it will call an action which should process the number and
     * auto increment if needed.
     *
     *
     * @param $projectId
     * @param $itemType
     */
    public function updateProjectMeta($projectId, $itemType)
    {
        if (empty($itemType)) {
            $itemType = 'project';
        }

        $itemTypes = [
            'project',
            'milestone',
            'task',
            'bug',
            'file',
            'client',
        ];

        // Is the itemType a valid type?
        if ( ! in_array($itemType, $itemTypes)) {
            return;
        }

        // Do we have autoincrement enabled for the item type?
        $query = new \WP_Query([
            'post_type'  => 'up_custom_field',
            'meta_query' => [
                [
                    'key'     => '_upstream__custom-fields:type',
                    'value'   => 'autoincrement',
                    'compare' => '=',
                ],
            ],
        ]);

        // We don't have any autoincrement custom field.
        if (empty($query->posts) || ! is_array($query->posts)) {
            return;
        }

        // Is there any custom field for the current item type?
        foreach ($query->posts as $customField) {
            $usage = get_post_meta($customField->ID, '_upstream__custom-fields:usage', true);

            if ( ! empty($usage) && is_array($usage)) {
                $usage = array_search($itemType, $usage);

                // Don't match, so continue to another.
                if ($usage === false) {
                    continue;
                }

                do_action('upstream:customField.processAutoincrement.' . $itemType, $projectId, $customField);
            }
        }
    }

    /**
     * Filter whether to override getting of meta value.
     * Returning a non 'cmb2_field_no_override_val' value
     * will effectively short-circuit the value retrieval.
     *
     * @since 2.0.0
     *
     * @param mixed $value     The value get_metadata() should
     *                         return - a single metadata value,
     *                         or an array of values.
     *
     * @param int   $object_id Object ID.
     *
     * @param array $args      {
     *                         An array of arguments for retrieving data
     *
     * @type string $type      The current object type
     * @type int    $id        The current object ID
     * @type string $field_id  The ID of the field being requested
     * @type bool   $repeat    Whether current field is repeatable
     * @type bool   $single    Whether current field is a single database row
     * }
     *
     * @param CMB2_Field object $field This field object
     *
     * @param mixed
     */
    public function cmb2OverrideMetaValue($value, $object_id, $args, $field)
    {
        if ($field->type() !== 'autoincrement') {
            return $value;
        }

        // Is the field being loaded for projects or items?
        $itemsIds = [
            '_upstream_project_tasks',
            '_upstream_project_milestones',
            '_upstream_project_bugs',
            '_upstream_project_files',
            '_upstream_project_client',
        ];
        if (in_array($args['field_id'], $itemsIds)) {
            return $value;
        }

        // Projects
        $fieldPost = $this->getFieldPostFromSlug($args['field_id']);

        $value = $this->processAutoincrementForProject($object_id, $fieldPost);

        return $value;
    }

    /**
     * @param $slug
     *
     * @return Autoincrement
     */
    public function getFieldPostFromSlug($slug)
    {
        $args      = [
            'name'        => $slug,
            'post_type'   => 'up_custom_field',
            'numberposts' => 1,
        ];
        $fieldPost = get_posts($args);

        if (empty($fieldPost)) {
            return false;
        }

        return $fieldPost[0];
    }

    /**
     * @param int      $projectId
     * @param \WP_Post $customField
     *
     * @return int
     */
    public function processAutoincrementForProject($projectId, $customField)
    {
        $number = $this->getAutoincrementNumberForProject($projectId, $customField);

        // Check if the post is not a auto-draft post... we only update when it was saved at least once.
        $post = get_post($projectId);
        if ($number === false && $post->post_status !== 'auto-draft') {
            $number = $this->getNextNumber($customField);

            $this->updateAutoincrementInPost($projectId, $customField, $number);
            $this->updateCurrentNumber($customField, $number);
        }

        return $number;
    }

    /**
     * Returns the current number for the autoincrement field in the project.
     * If none, we return the default value.
     *
     * @param int   $projectId
     * @param mixed $field
     *
     * @return int|bool
     */
    public function getAutoincrementNumberForProject($projectId, $field)
    {
        if (is_numeric($field)) {
            $field = get_post($field);
        }

        $fieldInstance = new Autoincrement($field);
        $metaKey       = $fieldInstance->name;

        // Do we have an autoincrement field for the project?
        $number = get_post_meta($projectId, $metaKey, true);

        return $number === '' ? false : $number;
    }

    /**
     * Get the next number for the specific autoincrement field.
     *
     * @param mixed $field
     *
     * @return int
     */
    public function getNextNumber($field)
    {
        if (is_numeric($field)) {
            $field = get_post($field);
        }

        $metaKey = "_upstream__custom-fields:autoincrement:current";

        // Do we any value for the field?
        $number = get_post_meta($field->ID, $metaKey, true);
        // We don't have a number, so let's create one based on the default value of the field.
        if ($number === '') {
            $fieldInstance = new Autoincrement($field);

            $number = (int)$fieldInstance->default;
        }

        $number++;

        return $number;
    }

    /**
     * @param $projectId
     * @param $field
     * @param $number
     */
    public function updateAutoincrementInPost($projectId, $field, $number)
    {
        if (is_numeric($field)) {
            $field = get_post($field);
        }

        $fieldInstance = new Autoincrement($field);
        $metaKey       = $fieldInstance->name;

        update_post_meta($projectId, $metaKey, $number);
    }

    /**
     * Update the field's current number.
     *
     * @param mixed $field
     * @param int   $number
     */
    public function updateCurrentNumber($field, $number)
    {
        $metaKey = "_upstream__custom-fields:autoincrement:current";

        if (is_a($field, 'WP_Post')) {
            $field = $field->ID;
        }

        update_post_meta($field, $metaKey, (int)$number);
    }

    /**
     * @param $slug
     *
     * @return Autoincrement
     */
    public function getFieldFromSlug($slug)
    {
        $post = $this->getFieldPostFromSlug($slug);

        $field = $this->getFieldFromFieldPost($post);

        return $field;
    }

    /**
     * @param $post
     *
     * @return Autoincrement
     */
    public function getFieldFromFieldPost($post)
    {
        $field = new Autoincrement($post);

        return $field;
    }

    /**
     * @param $number
     * @param $field
     *
     * @return string|bool
     */
    public function getAutoincrementFullString($number, $field)
    {
        if (false === $number) {
            return false;
        }

        $number = (int)$number;

        if (is_numeric($field)) {
            $field = get_post($field);
        }

        $field = $this->getFieldFromFieldPost($field);

        if (isset($field->args['prefix'])) {
            $prefix = (string)$field->args['prefix'];
            if ($prefix !== '') {
                $number = $prefix . $number;
            }
        }

        if (isset($field->args['suffix'])) {
            $suffix = (string)$field->args['suffix'];
            if ($suffix !== '') {
                $number = $number . $suffix;
            }
        }

        return $number === '' ?  __('---', 'upstream-custom-fields') : $number;
    }

    /**
     * Returns the string with prefix and suffix for the autoincrement field in the project.
     *
     * @param int   $projectId
     * @param mixed $field
     *
     * @return string|bool
     */
    public function getAutoincrementStringForProject($projectId, $field)
    {
        $value = $this->processAutoincrementForProject($projectId, $field);

        if ($value === false) {
            return __('---', 'upstream-custom-fields');
        }

        return $this->getAutoincrementFullString($value, $field);
    }
}
