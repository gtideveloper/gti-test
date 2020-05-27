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
 * @since       1.1.0
 */
class Tag extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.1.0
     *
     * @var     string $type
     */
    public $type = 'tag';

    /**
     * Whether to allow multiple selection.
     *
     * @since   1.1.0
     *
     * @var     bool $allowMultipleSelection
     */
    public $allowMultipleSelection = false;

    /**
     * Class constructor.
     *
     * @since   1.1.0
     *
     * @param   mixed $post Custom Field ID or post object.
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

        foreach ($value as $tid) {
            $id = get_term_by('id', $tid, 'upstream_tags');
            if ($tid === false)
                return sprintf(__('Term ID %s is invalid.', 'upstream'), $tid);
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
     * Retrieve a given field meta.
     *
     * @since   1.1.0
     *
     * @param   string $metaKey         Meta to be retrieved.
     * @param   bool   $ignoreTypeOnKey Whether to ignore the field type in the key.
     *
     * @return  mixed
     */
    public function getMeta($metaKey, $ignoreTypeOnKey = false)
    {
        return self::getMetaForId($metaKey, $this->id, ! $ignoreTypeOnKey ? 'tag' : false);
    }

    /**
     * Retrieve current custom field value for a given project.
     *
     * @since   1.1.0
     *
     * @param   int  $project_id     Project ID.
     * @param   bool $firstValueOnly Whether to return a single value.
     *
     * @return  array
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
     * @since   1.1.0
     *
     * @return  array
     */
    public function getOptions()
    {
        $options = self::fetchTags();

        return $options;
    }

    /**
     * Retrieve a list of terms as associative array.
     *
     * @since   1.1.0
     * @access  protected
     * @static
     *
     * @return  array
     */
    protected static function fetchTags()
    {
        $queryArgs = [
            'taxonomy'   => 'upstream_tag',
            'hide_empty' => false,
        ];

        $rowset     = (array)get_terms($queryArgs);
        $termsCount = count($rowset);

        $terms = [];
        foreach ($rowset as $row) {
            $terms[(int)$row->term_id] = $row->name;
        }
        unset($row, $rowset);

        return $terms;
    }

    /**
     * Retrieve an array of settings based on CMB2 patterns.
     *
     * @since   1.1.0
     *
     * @return  array
     */
    public function toCmb2()
    {
        $data = parent::toCmb2();

        $data['type']          = 'select';
        $data['options']       = [];
        $data['options_cb']    = [$this, 'fetchFieldOptions'];
        $data['render_row_cb'] = [UP_CUSTOM_FIELDS_NAMESPACE . '\\Metaboxes\\Fields\\Tag', 'renderOptions'];

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
     * @since   1.1.0
     * @static
     *
     * @param   \CMB2_Field $field The CMB2 field.
     *
     * @return  array
     */
    public static function fetchFieldOptions($field)
    {
        $options = self::fetchTags();

        return $options;
    }
}
