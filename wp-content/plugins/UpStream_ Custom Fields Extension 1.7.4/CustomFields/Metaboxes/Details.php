<?php

namespace UpStream\Plugins\CustomFields\Metaboxes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Cmb2Grid\Grid\Cmb2Grid;
use UpStream\Plugins\CustomFields\Fields\Field as DefaultField;
use UpStream\Plugins\CustomFields\Model;
use UpStream\Plugins\CustomFields\Traits\Singleton;

final class Details {
    use Singleton;

    public static function render() {
        $metabox = new_cmb2_box( [
            'id'           => UP_CUSTOM_FIELDS_META_PREFIX . 'details',
            'title'        => __( 'Details', 'upstream-custom-fields' ),
            'object_types' => [ 'up_custom_field' ],
            'context'      => 'normal',
            'priority'     => 'high',
            'show_names'   => true,
        ] );

        $fieldTypes = Model::getFieldTypes();

        $typeFieldArgs = [
            'name'    => __( 'Field Type', 'upstream-custom-fields' ),
            'id'      => UP_CUSTOM_FIELDS_META_PREFIX . 'type',
            'type'    => 'select',
            'options' => $fieldTypes,
        ];

        $field_id = upstream_post_id();
        if ( $field_id === 0
             || ( isset( $_REQUEST['_wp_http_referer'] )
                  && strpos( $_REQUEST['_wp_http_referer'], '/post-new.php?' ) !== false
             )
        ) {
            // Do nothing.
        } else {
            $fieldType = DefaultField::getMetaForId( 'type', $field_id );
            if ( empty( $fieldType ) ) {
                return;
            }

            $metabox->add_field( [
                'type'       => 'hidden',
                'id'         => $typeFieldArgs['id'],
                'attributes' => [
                    'value' => $fieldType,
                ],
            ] );

            $typeFieldArgs['id'] .= '_disabled';

            $typeFieldArgs['options'] = [
                $fieldType => $fieldTypes[ $fieldType ],
            ];

            $typeFieldArgs['attributes'] = [
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ];
        }

        $typeField = $metabox->add_field( $typeFieldArgs );

        $requiredField = $metabox->add_field( [
            'name'    => __( 'Required', 'upstream-custom-fields' ),
            'id'      => UP_CUSTOM_FIELDS_META_PREFIX . 'is_required',
            'type'    => 'radio_inline',
            'default' => "no",
            'options' => [
                'yes' => __( 'Yes', 'upstream-custom-fields' ),
                'no'  => __( 'No', 'upstream-custom-fields' ),
            ],
        ] );

        $fieldsGrid = new Cmb2Grid( $metabox );

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns( [ $typeField, $requiredField ] );

        $descriptionField = $metabox->add_field( [
            'name' => __( 'Description', 'upstream-custom-fields' ),
            'desc' => __( 'This description appears under the fields and helps users understand their choice.',
                'upstream-custom-fields' ),
            'id'   => UP_CUSTOM_FIELDS_META_PREFIX . 'description',
            'type' => 'textarea_small',
        ] );

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns( [ $descriptionField ] );
    }
}
