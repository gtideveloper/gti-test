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
class Category extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.1.0
     *
     * @var     string $type
     */
    public $type = 'category';

    /**
     * Whether to show the "Select All" button.
     *
     * @since   1.1.0
     *
     * @var     bool $showSelectAllBtn
     */
    public $showSelectAllBtn = false;

    /**
     * Whether to allow multiple selection.
     *
     * @since   1.1.0
     *
     * @var     bool $allowMultipleSelection
     */
    public $allowMultipleSelection = false;

    /**
     * The category ID where all data will be children of.
     *
     * @since   1.1.0
     *
     * @var     int $treeRootId
     */
    public $treeRootId = 0;

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

        $treeRootId = (int)$this->getMeta('args:root_id');
        if ($treeRootId > 0) {
            $this->treeRootId = $treeRootId;
        }

        $this->allowMultipleSelection = $this->getMeta('args:multiple') !== 'no';
    }

    public function isValid($type, $id, $value)
    {
        if ($this->allowMultipleSelection) {
            if (!is_array($value))
                return __('Passed value must be an array.', 'upstream');
        } else {
            $value = [$value];
        }

        foreach ($value as $tid) {
            $id = get_term_by('id', $tid, $type . '_category');
            if ($tid === false)
                return sprintf(__('Term ID %s is invalid.', 'upstream'), $tid);
        }

        return true;
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
        return self::getMetaForId($metaKey, $this->id, ! $ignoreTypeOnKey ? 'category' : false);
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
        $options = self::fetchCategories($this->treeRootId);

        return $options;
    }

    /**
     * Retrieve a list of terms as associative array.
     *
     * @since   1.1.0
     * @access  protected
     * @static
     *
     * @param   int $parent_id The category parent ID.
     *
     * @return  array
     */
    protected static function fetchCategories($parent_id = 0)
    {
        $options = [];

        $queryArgs = [
            'taxonomy'   => 'project_category',
            'hide_empty' => false,
        ];

        if ($parent_id > 0) {
            $queryArgs['child_of'] = $parent_id;
        }

        $rowset     = (array)get_terms($queryArgs);
        $termsCount = count($rowset);

        $terms = [];
        foreach ($rowset as $row) {
            $terms[] = (object)[
                'id'        => (int)$row->term_id,
                'title'     => $row->name,
                'parent_id' => (int)$row->parent,
                'children'  => [],
            ];
        }
        unset($row, $rowset);

        $map = [];
        if ($termsCount > 0) {
            foreach ($terms as $termIndex => $term) {
                if ($parent_id > 0) {
                    $firstLevel = $parent_id === $term->parent_id;
                } else {
                    $firstLevel = $term->parent_id === 0;
                }

                if ($firstLevel) {
                    $map[$term->id] = &$terms[$termIndex];
                }
            }

            for ($i = 0; $i < $termsCount; $i++) {
                foreach ($terms as $termIndex => $term) {
                    if ($term->parent_id > 0) {
                        self::nestChildren($term, $map);
                    }
                }
            }

            $options    = [];
            $lastParent = $parent_id;

            foreach ($map as $term) {
                self::applyDepthIndicator($term, 0, $options, $lastParent);
            }
        }

        return $options;
    }

    /**
     * Nest all children recursively.
     *
     * @since   1.1.0
     * @access  protected
     * @static
     *
     * @param   stdClass $needle    The term.
     * @param   array    &$haystack Terms list.
     */
    protected static function nestChildren($needle, &$haystack = [])
    {
        if ($needle->parent_id > 0) {
            if (isset($haystack[$needle->parent_id])) {
                if ( ! isset($haystack[$needle->parent_id]->children)) {
                    $haystack[$needle->parent_id]->children = [];
                }

                $haystack[$needle->parent_id]->children[$needle->id] = $needle;
            } elseif ( ! empty($haystack)) {
                foreach ($haystack as &$row) {
                    self::nestChildren($needle, $row->children);
                }
            }
        }
    }

    /**
     * Prepend "-" as needed indicating depth levels to a list items.
     *
     * @since   1.1.0
     * @access  protected
     * @static
     *
     * @param   stdClass $subject     The term.
     * @param   int      $depth       Depth level.
     * @param   array    &$options    The resultant options list.
     * @param   int      &$lastParent The last parent ID found.
     */
    protected static function applyDepthIndicator($subject, $depth, &$options, &$lastParent)
    {
        if ($lastParent !== $subject->parent_id) {
            $depth++;
        }

        $value                 = trim(str_repeat('-', $depth) . ' ' . $subject->title);
        $options[$subject->id] = $value;

        if ( ! isset($subject->children)) {
            return;
        }

        foreach ($subject->children as $row) {
            self::applyDepthIndicator($row, $depth, $options, $lastParent);
        }
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

        $data['type']              = 'select';
        $data['select_all_button'] = $this->showSelectAllBtn;
        $data['parent_id']         = $this->treeRootId;
        $data['options']           = [];
        $data['options_cb']        = [$this, 'fetchFieldOptions'];
        $data['render_row_cb']     = [UP_CUSTOM_FIELDS_NAMESPACE . '\\Metaboxes\\Fields\\Category', 'renderOptions'];

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
        $parent_id = isset($field->args['parent_id']) && (int)$field->args['parent_id'] > 0 ? (int)$field->args['parent_id'] : 0;


        $options = \Upstream_Cache::get_instance()->get('CustomFields_categ_'.$parent_id);
        if ($options === false) {
            $options = self::fetchCategories($parent_id);

            \Upstream_Cache::get_instance()->set('CustomFields_categ_'.$parent_id, $options);
        }

        return $options;
    }
}
