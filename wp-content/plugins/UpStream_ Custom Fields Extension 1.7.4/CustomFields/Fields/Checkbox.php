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
class Checkbox extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.0.0
     *
     * @var     string $type
     */
    public $type = 'multicheck';

    /**
     * All options available to be selected.
     *
     * @since   1.0.0
     *
     * @var     array $options
     */
    public $options = [];

    /**
     * Whether to show the "Select All" button.
     *
     * @since   1.0.0
     *
     * @var     bool $showSelectAllBtn
     */
    public $showSelectAllBtn = false;

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

        $defaultValue = null;
        $options      = (array)$this->getMeta('args:options');
        $optionsCount = count($options);
        if ($optionsCount > 0) {
            $useDefaultLayout = $this->getMeta('args:layout') !== 'inline';
            if ( ! $useDefaultLayout) {
                $this->type = 'multicheck_inline';
            }

            $this->showSelectAllBtn = $this->getMeta('args:show_select_all_btn') === 'yes';

            foreach ($options as $option) {
                $this->options[$option['value']] = $option['label'];

                if (isset($option['selected']) && $option['selected'] === 'on') {
                    if ($defaultValue === null) {
                        $defaultValue = [];
                    }

                    $defaultValue[] = $option['value'];
                }
            }
        }

        $this->default = $defaultValue;

        // Add the default value to the element.
        if ( ! isset($this->args)) {
            $this->args = [];
        }

        if (is_array($this->default)) {
            $this->args['data-default'] = implode(',', $this->default);
        } else {
            $this->args['data-default'] = $this->default;
        }
    }

    public function isValid($type, $id, $value)
    {
        if (!is_array($value))
            $value = [$value];

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
        if (!is_array($value))
            $value = [$value];

        return $value;
    }

    public function loadFromString($type, $id, $str)
    {
        $val = maybe_unserialize($str);

        if (is_array($val)) {
            return $val;
        } else {
            return [$val];
        }
    }

    public function storeToObject($type, $id, $inputValue)
    {
        if (is_array($inputValue)) {
            return $inputValue;
        } else {
            return [$inputValue];
        }
    }


    public function getMeta($metaKey, $ignoreTypeOnKey = false)
    {
        return self::getMetaForId($metaKey, $this->id, ! $ignoreTypeOnKey ? 'multicheck' : false);
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
        $selectedValues       = (array)parent::getValue($project_id, true);

        if (count($selectedValues) > 0) {
            $options = $this->options;

            foreach ($selectedValues as $selectedValue) {
                if (isset($options[$selectedValue])) {
                    $selectedValuesLabels[$selectedValue] = $options[$selectedValue];
                }
            }
        }

        return $selectedValuesLabels;
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

        $data['select_all_button'] = $this->showSelectAllBtn;
        $data['options']           = $this->options;
        $data['render_row_cb']     = [UP_CUSTOM_FIELDS_NAMESPACE . '\\Metaboxes\\Fields\\Checkbox', 'renderOptions'];

        $data = apply_filters('upstream.' . UP_CUSTOM_FIELDS_ALIAS . ':field_' . $this->type . '.cmb2_args', $data,
            $this);

        return $data;
    }

    /**
     * Retrieve all field options.
     *
     * @since   1.1.0
     *
     * @return  array
     */
    public function getOptions()
    {
        $options = $this->options;

        return $options;
    }
}
