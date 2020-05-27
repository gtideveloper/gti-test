<?php

namespace UpStream\Plugins\CustomFields\Fields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\CustomFields\Struct;

/**
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Fields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */
class Field extends Struct
{
    /**
     * Current field ID.
     *
     * @since   1.0.0
     *
     * @var     int $id
     */
    public $id = 0;

    /**
     * Current field type identifier.
     *
     * @since   1.0.0
     *
     * @var     string $type
     */
    public $type = null;

    /**
     * Current field label.
     *
     * @since   1.0.0
     *
     * @var     string $label
     */
    public $label = "";

    /**
     * Input name.
     *
     * @since   1.0.0
     *
     * @var     string $name
     */
    public $name = "";

    /**
     * Field additional description.
     *
     * @since   1.0.0
     *
     * @var     string $description
     */
    public $description = null;

    /**
     * Args that will be converted into XHTML attributes.
     *
     * @since   1.0.0
     *
     * @var     array $args
     */
    public $args = [];

    /**
     * Whether the field is required.
     *
     * @since   1.0.0
     *
     * @var     bool $required
     */
    public $required = false;

    /**
     * Default value.
     *
     * @since   1.0.0
     *
     * @var     mixed $default
     */
    public $default = null;

    /**
     * Defines whether the field can be filterable on frontend or not.
     *
     * @since   1.2.0
     *
     * @var     bool $filterable
     */
    public $filterable = true;

    /**
     * Defines whether the field will be displayed as column or not.
     *
     * @var     bool $showColumn
     */
    public $showColumn = false;

    /**
     * Class constructor.
     *
     * @since   1.0.0
     *
     * @param   mixed $post Custom Field ID or post object.
     */
    public function __construct($post)
    {
        if (is_numeric($post)) {
            $post = get_post($post);
        }


        if ( ! ($post instanceof \WP_Post) && $post->ID <= 0) {
            throw new \Exception('You must specify either a positive integer ou valid WP_Post object.');
        }

        $this->id    = (int)$post->ID;
        $this->name  = $post->post_name;
        $this->label = $post->post_title;

        $description = (string)$this->getMeta('description', true);
        if (strlen($description) > 0) {
            $this->description = $description;
        }
        unset($description);

        $isRequired = $this->getMeta('is_required', true) === 'yes';
        if ($isRequired) {
            //$this->label .= '<small class="o-required-mark">*</small>';
            $this->args['required'] = 'required';
            $this->required         = true;
        }
        unset($isRequired);

        $isFilterableMeta = $this->getMeta('is_filterable', true);
        $isFilterable     = empty($isFilterableMeta) || $isFilterableMeta === 'yes';
        if ( ! $isFilterable) {
            $this->filterable = false;
        }
        unset($isFilterable);

        $showColumnMeta   = $this->getMeta('show_column', true);
        $this->showColumn = $showColumnMeta === 'yes';
    }

    public function isValid($type, $id, $value)
    {
        return true;
    }

    public function sanitizeBeforeSet($type, $id, $value)
    {
        return $value;
    }

    public function loadFromString($type, $id, $str)
    {
        return null;
    }

    public function storeToObject($type, $id, $inputValue)
    {
        return null;
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
        return self::getMetaForId($metaKey, $this->id, ($ignoreTypeOnKey ? null : $this->type));
    }

    /**
     * Retrieve a custom field meta.
     *
     * @since   1.0.0
     * @static
     *
     * @param   string $metaKey  Meta key.
     * @param   int    $field_id Post ID.
     * @param   string $type     (optional) Custom Field type.
     *
     * @return  mixed
     */
    public static function getMetaForId($metaKey, $field_id, $type = null)
    {
        $metaSelector = UP_CUSTOM_FIELDS_META_PREFIX . (! empty($type) ? $type . ':' : '') . $metaKey;

        $data = get_post_meta($field_id, $metaSelector, true);

        return $data;
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
        $data = [
            'id'         => $this->name,
            'name'       => $this->label,
            'type'       => $this->type,
            'classes'    => 'o-up-custom-field',
            'default'    => $this->default,
            'attributes' => $this->args,
            'desc'       => $this->description,
        ];

        $data = apply_filters('upstream.' . UP_CUSTOM_FIELDS_ALIAS . ':field.cmb2_args', $data, $this);

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
        $meta = get_post_meta($project_id, $this->name, $firstValueOnly);

        return $meta;
    }

    public function render($project_id)
    {

    }

    /**
     * Checks if the field is filterable.
     *
     * @since   1.2.0
     *
     * @return  bool
     */
    public function isFilterable()
    {
        return (bool)$this->filterable;
    }

    /**
     * Checks if the field is a column.
     *
     * @return  bool
     */
    public function isColumn()
    {
        return (bool)$this->showColumn;
    }
}
