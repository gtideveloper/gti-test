<?php

namespace UpStream\Plugins\CustomFields\Metaboxes\Fields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Cmb2Grid\Grid\Cmb2Grid;
use UpStream\Plugins\CustomFields\Metaboxes\Field as FieldArgsMetabox;
use UpStream\Plugins\CustomFields\Traits\Singleton;

/**
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Metaboxes\Fields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class Colorpicker extends FieldArgsMetabox
{
    use Singleton;

    /**
     * Field type.
     *
     * @since   1.0.0
     * @const   FIELD_TYPE
     */
    const FIELD_TYPE = 'colorpicker';

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        add_action('upstream.custom-fields:' . self::FIELD_TYPE . '.render_args_metabox',
            [$this, 'renderFieldsIntoMetabox'], 10, 1);
    }

    /**
     * Define all custom args fields for the current Custom Field.
     *
     * @since   1.0.0
     * @abstract
     * @static
     *
     * @return  array
     */
    public static function getFields()
    {
        $fields = [];

        $idPrefix = UP_CUSTOM_FIELDS_META_PREFIX . self::FIELD_TYPE . ':';

        $defaultValueField_id          = 'default_value';
        $defaultValueField             = [
            'before_row' => '<div class="c-field-type-args up-s-hidden" data-type="colorpicker">',
            'name'       => __('Default Value', 'upstream-custom-fields'),
            'id'         => $idPrefix . $defaultValueField_id,
            'type'       => 'colorpicker',
            'options'    => [
                'alpha' => true,
            ],
        ];
        $fields[$defaultValueField_id] = $defaultValueField;

        $useAlphaOption_id          = 'args:disable_alpha';
        $useAlphaOption             = [
            'name' => __('Disable Transparency', 'upstream-custom-fields'),
            'desc' => __('Either to allow transparency or not.', 'upstream-custom-fields'),
            'id'   => $idPrefix . $useAlphaOption_id,
            'type' => 'checkbox',
        ];
        $fields[$useAlphaOption_id] = $useAlphaOption;

        $palettes_id          = 'args:disable_palettes';
        $palettes             = [
            'name'      => __('Disable Palettes', 'upstream-custom-fields'),
            'desc'      => __('Disable palette of basic colors beneath the color picker.', 'upstream-custom-fields'),
            'id'        => $idPrefix . $palettes_id,
            'type'      => 'checkbox',
            'after_row' => '</div>',
        ];
        $fields[$palettes_id] = $palettes;

        return $fields;
    }

    /**
     * Render all custom args fields into a given metabox object.
     *
     * @since       1.0.0
     * @abstract
     * @static
     *
     * @param       \CMB2 $metabox CMB2 metabox object.
     */
    public static function renderFieldsIntoMetabox($metabox)
    {
        $fields = self::getFields();

        foreach ($fields as $fieldName => $field) {
            $fields[$fieldName] = $metabox->add_field($field);
        }

        $fieldsGrid = new Cmb2Grid($metabox);

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([$fields['default_value']]);

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([$fields['args:disable_alpha'], $fields['args:disable_palettes']]);
    }
}
