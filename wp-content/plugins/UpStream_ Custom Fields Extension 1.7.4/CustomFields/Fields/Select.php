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
class Select extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.0.0
     *
     * @var     string $type
     */
    public $type = 'select';

    /**
     * All options available to be selected.
     *
     * @since   1.0.0
     *
     * @var     array $options
     */
    public $options = [];

    public $allowMultipleSelection = false;

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

        $allowMultiple = $this->getMeta('args:multiple') === 'yes';
        if ($allowMultiple) {
            $this->args['multiple'] = 'multiple';
        }

        $this->allowMultipleSelection = $allowMultiple;

        $defaultValue = null;
        $options      = (array)$this->getMeta('args:options');
        if (count($options) > 0) {
            foreach ($options as $option) {
                $this->options[$option['value']] = $option['label'];

                if (isset($option['selected']) && $option['selected'] === 'on') {
                    if ($allowMultiple) {
                        if ($defaultValue === null) {
                            $defaultValue = [];
                        }

                        $defaultValue[] = $option['value'];
                    } elseif ($defaultValue === null) {
                        $defaultValue = $option['value'];
                    }
                }
            }
        }

        $this->default = $defaultValue;

        // Add the default value to the element.
        if (is_array($this->default)) {
            $this->args['data-default'] = implode(',', $this->default);
        } else {
            $this->args['data-default'] = $this->default;
        }
    }

    public function isValid($type, $id, $value)
    {
        if ($this->allowMultipleSelection) {
            if (!is_array($value))
                $value = [$value];
        } else {
            if (is_array($value))
                return __('Passed value cannot be an array.', 'upstream');

            $value = [$value];
        }

        foreach ($value as $item) {
            $found = false;
            foreach ($this->options as $val => $label) {
                if ($val === $item) {
                    $found = true;
                }
            }
            if (!$found) {
                return sprintf(__('%s is not a valid option.', 'upstream'), $item);
            }
        }

        return true;
    }

    public function sanitizeBeforeSet($type, $id, $value)
    {
        if ($this->allowMultipleSelection) {
            if (!is_array($value))
                $value = [$value];
        }

        return $value;
    }


    public function loadFromString($type, $id, $str)
    {
        $val = maybe_unserialize($str);

        if ($this->allowMultipleSelection) {
            if (is_array($val)) {
                return $val;
            } else {
                return [$val];
            }
        } else {
            if (is_array($val)) {
                return count($val) > 0 ? $val[0] : 0;
            } else {
                return $val;
            }
        }
    }

    public function storeToObject($type, $id, $inputValue)
    {
        if ($this->allowMultipleSelection) {
            if (is_array($inputValue)) {
                return $inputValue;
            } else {
                return [$inputValue];
            }
        } else {
            if (is_array($inputValue)) {
                return count($inputValue) > 0 ? $inputValue[0] : 0;
            } else {
                return $inputValue;
            }
        }
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

        $data['options'] = $this->options;

        if (isset($this->args['multiple']) && $this->args['multiple'] === 'multiple') {
            $data['multiple'] = true;
        }

        if ( ! $this->required) {
            $data['show_option_none'] = true;
        }

        $data['render_row_cb'] = [UP_CUSTOM_FIELDS_NAMESPACE . '\\Metaboxes\\Fields\\Select', 'renderOptions'];

        $data = apply_filters('upstream.' . UP_CUSTOM_FIELDS_ALIAS . ':field_' . $this->type . '.cmb2_args', $data,
            $this);

        return $data;
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
        $selectedValuesLabels = [];
        $selectedValues       = (array)parent::getValue($project_id, false);

        if (count($selectedValues) > 0) {
            $allowMultiple = $this->doesAllowMultipleSelection();

            $options = $this->options;

            foreach ($selectedValues as $selectedValue) {
                if (isset($options[$selectedValue])) {
                    $selectedValuesLabels[$selectedValue] = $options[$selectedValue];

                    if ( ! $allowMultiple) {
                        break;
                    }
                }
            }
        }

        return $selectedValuesLabels;
    }

    /**
     * Check whether current field allows multiple selection.
     *
     * @since   1.0.0
     *
     * @return  bool
     */
    public function doesAllowMultipleSelection()
    {
        return isset($this->args['multiple']) && $this->args['multiple'] === 'multiple';
    }

    /**
     * Retrieve all field options.
     *
     * @since   1.2.1
     *
     * @return  array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
