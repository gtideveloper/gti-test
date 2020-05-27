<?php

namespace UpStream\Plugins\CustomFields\Fields;

// Prevent direct access.
use UpStream\Plugins\CustomFields\Model;

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Fields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.4.2
 */
class Country extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.4.2
     *
     * @var     string $type
     */
    public $type = 'country';

    /**
     * Whether to allow multiple selection.
     *
     * @since   1.4.2
     *
     * @var     bool $allowMultipleSelection
     */
    public $allowMultipleSelection = false;

    /**
     * Class constructor.
     *
     * @param mixed $post Custom Field ID or post object.
     *
     * @since   1.4.2
     *
     */
    public function __construct($post)
    {
        parent::__construct($post);

        $this->allowMultipleSelection = $this->getMeta('args:multiple') !== 'no';
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

        // must be passed a 3 letter country code

        $countries = self::fetchCountries();
        foreach ($value as $country) {
            if (!isset($countries[$country]))
                return sprintf(__('Country %s is invalid.', 'upstream'), $country);
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
                $res = [];

                foreach ($val as $item) {
                    $parts = explode(',', $item);
                    foreach ($parts as $p) {
                        $res[] = $p;
                    }
                }

                return $res;
            } else {
                $res = [];

                $parts = explode(',', $val);
                foreach ($parts as $p) {
                    $res[] = $p;
                }

                return $res;
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
     * Retrieve a given field meta.
     *
     * @param string $metaKey         Meta to be retrieved.
     * @param bool   $ignoreTypeOnKey Whether to ignore the field type in the key.
     *
     * @return  mixed
     * @since   1.4.2
     *
     */
    public function getMeta($metaKey, $ignoreTypeOnKey = false)
    {
        return self::getMetaForId($metaKey, $this->id, ! $ignoreTypeOnKey ? $this->type : false);
    }

    /**
     * Retrieve current custom field value for a given project.
     *
     * @param int  $project_id     Project ID.
     * @param bool $firstValueOnly Whether to return a single value.
     *
     * @return  array
     * @since   1.4.2
     *
     */
    public function getValue($project_id, $firstValueOnly = true)
    {
        $selectedValuesLabels = [];
        $selectedValues       = (array)parent::getValue($project_id, true);

        if (count($selectedValues) > 0) {
            $options = self::getOptions();

            foreach ($selectedValues as $selectedValue) {
                if (isset($options[$selectedValue])) {
                    $selectedValuesLabels[$selectedValue] = $options[$selectedValue];
                }
            }
        }

        return $selectedValuesLabels;
    }

    /**
     * Retrieve all field options based on the field params.
     *
     * @return  array
     * @since   1.4.2
     *
     */
    public static function getOptions()
    {
        return self::fetchCountries();
    }

    /**
     * Retrieve a list of terms as associative array.
     *
     * @return  array
     * @since   1.4.2
     * @access  protected
     * @static
     *
     */
    protected static function fetchCountries()
    {
        return Model::getCountries();
    }

    /**
     * Retrieve an array of settings based on CMB2 patterns.
     *
     * @return  array
     * @since   1.4.2
     *
     */
    public function toCmb2()
    {
        $data = parent::toCmb2();

        $data['type']          = 'select';
        $data['options']       = [];
        $data['options_cb']    = [$this, 'fetchFieldOptions'];
        $data['render_row_cb'] = [UP_CUSTOM_FIELDS_NAMESPACE . '\\Metaboxes\\Fields\\Country', 'renderOptions'];

        if ($this->allowMultipleSelection) {
            if (isset($data['attributes'])) {
                $data['attributes'] = [];
            }

            $data['attributes']['multiple'] = 'multiple';
        }

        $data = apply_filters('upstream.' . UP_CUSTOM_FIELDS_ALIAS . ':field_' . $this->type . '.cmb2_args', $data,
            $this);

        return $data;
    }

    /**
     * Retrieve all field options based on the field params statically.
     *
     * @param \CMB2_Field $field The CMB2 field.
     *
     * @return  array
     * @since   1.4.2
     * @static
     *
     */
    public static function fetchFieldOptions($field)
    {
        return self::getOptions();
    }
}
