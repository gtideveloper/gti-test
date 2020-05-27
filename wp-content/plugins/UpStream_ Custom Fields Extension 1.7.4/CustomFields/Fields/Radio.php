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
class Radio extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.0.0
     *
     * @var     string $type
     */
    public $type = 'radio';

    /**
     * All options available to be selected.
     *
     * @since   1.0.0
     *
     * @var     array $options
     */
    public $options = [];

    /**
     * Whether to show the "None" option.
     *
     * @since   1.0.0
     *
     * @var     bool $showNoneOption
     */
    public $showNoneOption = false;

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

        $options      = (array)$this->getMeta('args:options');
        $optionsCount = count($options);

        $this->default = null;

        if ($optionsCount > 0) {
            $useDefaultLayout = $this->getMeta('args:layout') !== 'inline';
            if ( ! $useDefaultLayout) {
                $this->type = 'radio_inline';
            }

            $showNoneOption = $this->getMeta('args:show_none_option') === 'yes';
            if ($showNoneOption) {
                $this->showNoneOption = true;
            }

            foreach ($options as $option) {
                $this->options[$option['value']] = $option['label'];

                if ($this->default === null && isset($option['selected']) && $option['selected'] === 'on') {
                    $this->default = $option['value'];
                }
            }
        }

        // Add the default value to the element.
        $this->args['data-default'] = $this->default;
    }

    public function isValid($type, $id, $value)
    {
        if (is_array($value)) {
            return __('Passed value must not be an array.', 'upstream');
        }

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


    public function loadFromString($type, $id, $str)
    {
        return $str;
    }

    public function storeToObject($type, $id, $inputValue)
    {
        return $inputValue;
    }

    /**
     * Retrieve a given meta to current field.
     *
     * @since   1.0.0
     *
     * @param   string $metaKey         The meta key.
     * @param   bool   $ignoreTypeOnKey Either to ignore the field type on $metaKey.
     *
     * @return  mixed
     */
    public function getMeta($metaKey, $ignoreTypeOnKey = false)
    {
        return self::getMetaForId($metaKey, $this->id, ! $ignoreTypeOnKey ? 'radio' : false);
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

        if (strlen($value) > 0) {
            $options = $this->options;

            if (isset($options[$value])) {
                $value = $options[$value];
            }
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

        $data['options']             = $this->options;
        $data['do_show_option_none'] = $this->showNoneOption;
        $data['render_row_cb']       = [UP_CUSTOM_FIELDS_NAMESPACE . '\\Metaboxes\\Fields\\Radio', 'renderOptions'];

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
