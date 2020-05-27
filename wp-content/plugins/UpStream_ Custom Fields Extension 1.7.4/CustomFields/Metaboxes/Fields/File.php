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
final class File extends FieldArgsMetabox
{
    use Singleton;

    /**
     * Field type.
     *
     * @since   1.0.0
     * @const   FIELD_TYPE
     */
    const FIELD_TYPE = 'file';

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

        $buttonText_id          = 'args:btn_text';
        $buttonText             = [
            'before_row' => '<div class="c-field-type-args up-s-hidden" data-type="file">',
            'after_row'  => '</div>',
            'name'       => __('Button Text', 'upstream-custom-fields'),
            'desc'       => __('Represents the upload button text. Supported only on admin.',
                'upstream-custom-fields'),
            'attributes' => [
                'placeholder' => __('Add or Upload File', 'cmb2'),
            ],
            'id'         => $idPrefix . $buttonText_id,
            'type'       => 'text',
        ];
        $fields[$buttonText_id] = $buttonText;

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

            if ($field['type'] === 'group'
                && isset($field['fields'])
                && count($field['fields'])
            ) {
                foreach ($field['fields'] as $groupFieldName => $groupField) {
                    $field['fields'][$groupFieldName] = $metabox->add_group_field($field['id'], $groupField);
                }
            }
        }

        $fieldsGrid = new Cmb2Grid($metabox);

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([
            [
                $fields['args:btn_text'],
                'class' => 'col-sm-6 col-md-6 col-lg-6',
            ],
        ]);
    }
}
