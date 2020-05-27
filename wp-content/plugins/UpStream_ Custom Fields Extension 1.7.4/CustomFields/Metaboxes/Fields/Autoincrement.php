<?php

namespace UpStream\Plugins\CustomFields\Metaboxes\Fields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Cmb2Grid\Grid\Cmb2Grid;
use UpStream\Plugins\CustomFields\AutoincrementModel;
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
final class Autoincrement extends FieldArgsMetabox
{
    use Singleton;

    /**
     * Field type.
     *
     * @since   1.0.0
     * @const   FIELD_TYPE
     */
    const FIELD_TYPE = 'autoincrement';

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
     * Render custom options for current field.
     *
     * @param array       $args      Array of field settings.
     * @param \CMB2_Field $cmb2Field Current CMB2 field.
     *
     * @since   1.0.0
     * @static
     *
     */
    public static function renderOptions($args, $cmb2Field)
    {
        $model     = AutoincrementModel::getInstance();
        $fieldArgs = $cmb2Field->data_args();
        $field     = $model->getFieldFromSlug($cmb2Field->id());

        $value = $model->getAutoincrementStringForProject($fieldArgs['id'], $field->id);

        if ($value === false) {
            $value = __('---', 'upstream-custom-fields');
        }
        ?>
        <div
            class="cmb-row cmb-type-select cmb2-id-<?php echo $args['id']; ?> <?php echo is_array($args['classes']) ? implode(' ',
                $args['classes']) : ''; ?>" data-fieldtype="select"
            data-default="<?php echo $args['default']; ?>">
            <div class="cmb-th">
                <label for="<?php echo $args['id']; ?>"><?php echo $args['name']; ?></label>
            </div>
            <div class="cmb-td">
                <div class="up-autoincrement"><?php echo $value; ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Define all custom args fields for the current Custom Field.
     *
     * @return  array
     * @since   1.0.0
     * @abstract
     * @static
     *
     */
    public static function getFields()
    {
        $fields = [];

        $idPrefix = UP_CUSTOM_FIELDS_META_PREFIX . self::FIELD_TYPE . ':';

        $fieldId          = 'args:initial_number';
        $field            = [
            'before_row' => '<div class="c-field-type-args up-s-hidden" data-type="autoincrement">',
            'name'       => __('Initial Number', 'upstream-custom-fields'),
            'id'         => $idPrefix . $fieldId,
            'type'       => 'text',
            'default'    => '0',
            'attributes' => [
                'type' => 'number',
                'min'  => 0,
                'max'  => 999999999,
                'step' => 1,
            ],
        ];
        $fields[$fieldId] = $field;

        $fieldId          = 'args:prefix';
        $field            = [
            'name' => __('Prefix', 'upstream-custom-fields'),
            'desc' => __('Text added as prefix to the number.', 'upstream-custom-fields'),
            'id'   => $idPrefix . $fieldId,
            'type' => 'text',
        ];
        $fields[$fieldId] = $field;

        $fieldId          = 'args:suffix';
        $field            = [
            'name'      => __('Suffix', 'upstream-custom-fields'),
            'after_row' => '</div>',
            'desc'      => __('Text added as suffix to the number.',
                'upstream-custom-fields'),
            'id'        => $idPrefix . $fieldId,
            'type'      => 'text',
        ];
        $fields[$fieldId] = $field;

        return $fields;
    }


    /**
     * Render all custom args fields into a given metabox object.
     *
     * @param \CMB2 $metabox CMB2 metabox object.
     *
     * @since       1.0.0
     * @abstract
     * @static
     *
     */
    public static function renderFieldsIntoMetabox($metabox)
    {
        $fields = self::getFields();

        foreach ($fields as $fieldName => $field) {
            $fields[$fieldName] = $metabox->add_field($field);
        }

        $fieldsGrid = new Cmb2Grid($metabox);

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([$fields['args:initial_number']]);

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([$fields['args:prefix'], $fields['args:suffix']]);
    }


}
