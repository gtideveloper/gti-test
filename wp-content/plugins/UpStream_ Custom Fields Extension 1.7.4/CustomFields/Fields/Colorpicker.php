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
class Colorpicker extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.0.0
     *
     * @var     string $type
     */
    public $type = 'colorpicker';

    /**
     * Whether to disable transparency controls.
     *
     * @since   1.0.0
     *
     * @var     bool $disableAlpha
     */
    public $disableAlpha = false;

    /**
     * Whether to disable palettes.
     *
     * @since   1.0.0
     *
     * @var     bool $disablePalettes
     */
    public $disablePalettes = false;

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

        $disableAlpha = $this->getMeta('args:disable_alpha') === 'on';
        if ($disableAlpha) {
            $this->disableAlpha = true;
        }

        $disablePalettes = $this->getMeta('args:disable_palettes') === 'on';
        if ($disablePalettes) {
            $this->disablePalettes = true;
        }

        // Add the default value to the element.
        if (is_array($this->default)) {
            $this->args['data-default'] = implode(',', $this->default);
        } else {
            $this->args['data-default'] = $this->default;
        }
    }

    public function isValid($type, $id, $value)
    {
        if (!preg_match('/\#[a-zA-Z0-9]{6}/', $value))
            return sprintf(__('%s is not a valid hex string.', 'upstream'), $value);

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
     * Retrieve an array of settings based on CMB2 patterns.
     *
     * @since   1.0.0
     *
     * @return  array
     */
    public function toCmb2()
    {
        $data = parent::toCmb2();

        $data['options'] = [
            'alpha' => ! $this->disableAlpha,
        ];

        $data['attributes'] = [
            'data-colorpicker' => [],
        ];

        if ($this->disablePalettes) {
            $data['attributes']['data-colorpicker']['palettes'] = false;
        }

        if ( ! empty($data['attributes']['data-colorpicker'])) {
            $data['attributes']['data-colorpicker'] = json_encode($data['attributes']['data-colorpicker']);
        } else {
            unset($data['attributes']['data-colorpicker']);
        }

        $data = apply_filters('upstream.' . UP_CUSTOM_FIELDS_ALIAS . ':field_' . $this->type . '.cmb2_args', $data,
            $this);

        return $data;
    }
}
