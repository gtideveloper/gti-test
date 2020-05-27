<?php

namespace UpStream\Plugins\CustomFields\Fields;

// Prevent direct access.
use UpStream\Plugins\CustomFields\AutoincrementModel;

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Fields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */
class Autoincrement extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.0.0
     *
     * @var     string $type
     */
    public $type = 'autoincrement';

    /**
     * Class constructor.
     *
     * @since   1.0.0
     *
     * @param   mixed $post Custom Field ID or post object.
     */
    public function __construct($post)
    {
        parent::__construct($post);

        $defaultValue = (int)$this->getMeta('args:initial_number');
        if (strlen($defaultValue) > 0) {
            $this->default = $defaultValue;
        }

        $prefix = $this->getMeta('args:prefix');
        if ($prefix !== '' && ! is_null($prefix)) {
            $this->args['prefix'] = $prefix;
        }

        $suffix = $this->getMeta('args:suffix');
        if ($suffix !== '' && ! is_null($suffix)) {
            $this->args['suffix'] = $suffix;
        }

        // Add the default value to the element.
        $this->args['data-default'] = $this->default;
    }

    public function isValid($type, $id, $value)
    {
        return __('Cannot set autoincrement value.', 'upstream');
    }

    public function loadFromString($type, $id, $str)
    {
        return $str;
    }

    public function storeToObject($type, $id, $inputValue)
    {
        return $inputValue;
    }


    /**
     * Retrieve current custom field value for a given project.
     *
     * @since   1.0.0
     *
     * @param   int  $project_id     Project ID.
     * @param   bool $firstValueOnly Whether to return a single value.
     *
     * @return string
     */
    public function getValue($project_id, $firstValueOnly = true)
    {
        $model = AutoincrementModel::getInstance();

        if (is_numeric($this->id)) {
            $fieldPost = get_post($this->id);
        } else {
            $fieldPost = $model->getFieldPostFromSlug($this->id);
        }

        $value = $model->getAutoincrementStringForProject($project_id, $fieldPost);

        return $value;
    }

    /**
     * Retrieve an array of settings based on CMB2 patterns.
     *
     * @since   1.0.0
     *
     * @return  array
     */
    public function toCmb2()
    {
        $data = parent::toCmb2();

        $data['render_row_cb'] = [UP_CUSTOM_FIELDS_NAMESPACE . '\\Metaboxes\\Fields\\Autoincrement', 'renderOptions'];

        $data = apply_filters('upstream.' . UP_CUSTOM_FIELDS_ALIAS . ':field_' . $this->type . '.cmb2_args', $data,
            $this);

        return $data;
    }
}
