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
final class Text extends FieldArgsMetabox
{
    use Singleton;

    /**
     * Field type.
     *
     * @since   1.0.0
     * @const   FIELD_TYPE
     */
    const FIELD_TYPE = 'text';

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
            'before_row' => '<div class="c-field-type-args" data-type="text">',
            'name'       => __('Default Value', 'upstream-custom-fields'),
            'id'         => $idPrefix . $defaultValueField_id,
            'type'       => 'text',
        ];
        $fields[$defaultValueField_id] = $defaultValueField;

        $placeholderField_id          = 'args:placeholder';
        $placeholderField             = [
            'name' => __('Placeholder', 'upstream-custom-fields'),
            'desc' => __('Short hint that describes the expected value.', 'upstream-custom-fields'),
            'id'   => $idPrefix . $placeholderField_id,
            'type' => 'text',
        ];
        $fields[$placeholderField_id] = $placeholderField;

        $minLengthField_id          = 'args:minlength';
        $minLengthField             = [
            'name'       => __('Minimum Length', 'upstream-custom-fields'),
            'desc'       => __('Specifies the minimum number of characters the field requires. Max: 254.',
                'upstream-custom-fields'),
            'id'         => $idPrefix . $minLengthField_id,
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => 1,
                'max'  => 254,
                'step' => 1,
            ],
        ];
        $fields[$minLengthField_id] = $minLengthField;

        $maxLengthField_id          = 'args:maxlength';
        $maxLengthField             = [
            'name'       => __('Maximum Length', 'upstream-custom-fields'),
            'desc'       => __('Specifies the maximum number of characters the field requires. Max: 255.',
                'upstream-custom-fields'),
            'id'         => $idPrefix . $maxLengthField_id,
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => 1,
                'max'  => 255,
                'step' => 1,
            ],
        ];
        $fields[$maxLengthField_id] = $maxLengthField;

        $patternField_id          = 'args:pattern';
        $patternField             = [
            'name'      => __('Pattern', 'upstream-custom-fields'),
            'desc'      => __("Regular expression that the input value is checked against. This pattern must match the entire value, not just a subset.<br>Should not have a leading or trailing slash. Use the <strong>Description</strong> field to describe the pattern to help the user.",
                'upstream-custom-fields'),
            'id'        => $idPrefix . $patternField_id,
            'type'      => 'text',
            'after_row' => '</div>',
        ];
        $fields[$patternField_id] = $patternField;

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
        $fieldsGridRow->addColumns([$fields['default_value'], $fields['args:placeholder']]);

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([$fields['args:minlength'], $fields['args:maxlength']]);

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([
            [
                $fields['args:pattern'],
                'class' => 'col-sm-6 col-md-6 col-lg-6',
            ],
        ]);
    }
}
