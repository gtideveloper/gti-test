<?php

namespace UpStream\Plugins\CustomFields\Fields;

// Prevent direct access.
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
class Text extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.0.0
     *
     * @var     string $type
     */
    public $type = 'text';

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

        $defaultValue = (string)$this->getMeta('default_value');
        if (strlen($defaultValue) > 0) {
            $this->default = $defaultValue;
        }

        $minLength = (int)$this->getMeta('args:minlength');
        if ($minLength > 0) {
            $this->args['minlength'] = $minLength;
        }

        $maxLength = (int)$this->getMeta('args:maxlength');
        if ($maxLength > 0) {
            $this->args['maxlength'] = $maxLength;
        }

        $placeholder = trim((string)$this->getMeta('args:placeholder'));
        if (strlen($placeholder) > 0) {
            $this->args['placeholder'] = $placeholder;
        }

        $pattern = trim((string)$this->getMeta('args:pattern'));
        if (strlen($pattern) > 0) {
            $this->args['pattern'] = $pattern;
        }

        // Add the default value to the element.
        $this->args['data-default'] = $this->default;
    }

    public function isValid($type, $id, $value)
    {
        if (!is_string($value)) {
            return sprintf(__('Input %s must be a string.'), $value);
        } else if (isset($this->args['minlength']) && strlen($value) < $this->args['minlength']) {
            return sprintf(__('Input %s is shorter than the min length.'), $value);
        } else if (isset($this->args['maxlength']) && strlen($value) > $this->args['maxlength']) {
            return sprintf(__('Input %s is longer than the max length.'), $value);
        } else if (isset($this->args['pattern']) && !preg_match('/' . $this->args['pattern'] . '/', $value)) {
            return sprintf(__('Input %s does not match the required pattern.'), $value);
        }

        return true;

    }

    public function sanitizeBeforeSet($type, $id, $value)
    {
        return wp_kses_post($value);
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
     */
    public function getValue($project_id, $firstValueOnly = true)
    {
        $value = parent::getValue($project_id, $firstValueOnly);

        if (strlen($value) === 0
            && strlen((string)$this->default) > 0
        ) {
            $value = $this->default;
        }

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

        $data = apply_filters('upstream.' . UP_CUSTOM_FIELDS_ALIAS . ':field_' . $this->type . '.cmb2_args', $data,
            $this);

        return $data;
    }
}
