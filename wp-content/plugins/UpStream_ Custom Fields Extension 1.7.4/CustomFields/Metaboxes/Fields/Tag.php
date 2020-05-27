<?php

namespace UpStream\Plugins\CustomFields\Metaboxes\Fields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Cmb2Grid\Grid\Cmb2Grid;
use UpStream\Plugins\CustomFields\Fields\Tag as TagField;
use UpStream\Plugins\CustomFields\Metaboxes\Field as FieldArgsMetabox;
use UpStream\Plugins\CustomFields\Traits\Singleton;

/**
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Metaboxes\Fields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.1.0
 * @final
 */
final class Tag extends FieldArgsMetabox
{
    use Singleton;

    /**
     * Field type.
     *
     * @since   1.0.0
     * @const   FIELD_TYPE
     */
    const FIELD_TYPE = 'tag';

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
     * @since   1.1.0
     * @static
     *
     * @param   array       $args  Array of field settings.
     * @param   \CMB2_Field $field Current CMB2 field.
     */
    public static function renderOptions($args, $field)
    {
        $isRequired = (isset($args['attributes'])
                       && isset($args['attributes']['required'])
                       && $args['attributes']['required'] === 'required'
        );

        $attributes    = isset($args['attributes']) && is_array($args['attributes']) ? $args['attributes'] : [];
        $allowMultiple = isset($args['attributes']) && isset($args['attributes']['multiple']) && $args['attributes']['multiple'] === 'multiple';

        $defaultOptions = (array)$args['default'];
        $selectValues   = array_filter((array)$field->value);

        $options = TagField::fetchFieldOptions($field);
        ?>
        <div
            class="cmb-row up-o-select2-wrapper cmb-type-select cmb2-id-<?php echo $args['id']; ?> <?php echo is_array($args['classes']) ? implode(' ',
                $args['classes']) : ''; ?>" data-fieldtype="select">
            <div class="cmb-th">
                <label for="<?php echo $args['id']; ?>"><?php echo $args['name']; ?></label>
            </div>
            <div class="cmb-td">
                <?php
                if ( ! isset($attributes['class'])) {
                    $attributes['class'] = [];
                }

                if ( ! is_array($attributes['class'])) {
                    $attributes['class'] = explode(' ', trim((string)$attributes['class']));
                }

                $attributes['class'][] = 'cmb2_select';
                $attributes['class'][] = 'cmb2_select';
                $attributes['class']   = array_unique(array_filter($attributes['class']));
                $attributes['class']   = implode(' ', $attributes['class']);

                $attributes['name'] = $args['_name'] . ($allowMultiple ? '[]' : '');
                $attributes['id']   = $args['id'];

                if ( ! $allowMultiple) {
                    $attributes['data-allow-clear'] = true;
                }

                $attributes['data-placeholder'] = '';

                $attrs = [];
                foreach ($attributes as $attrName => $attrValue) {
                    $attrs[] = sprintf('%s="%s"', $attrName, $attrValue);
                }
                ?>
                <select <?php echo implode(' ', $attrs); ?>>
                    <option></option>
                    <?php foreach ($options as $optionValue => $optionLabel): ?>
                        <option value="<?php echo $optionValue; ?>"
                            <?php echo in_array($optionValue,
                                (count($selectValues) === 0 ? $defaultOptions : $selectValues)) ? ' selected' : ''; ?>><?php echo $optionLabel; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }

    /**
     * Define all custom args fields for the current Custom Field.
     *
     * @since   1.1.0
     * @static
     *
     * @return  array
     */
    public static function getFields()
    {
        $fields = [];

        $idPrefix = UP_CUSTOM_FIELDS_META_PREFIX . self::FIELD_TYPE . ':';

        $allowMultipleField_id          = 'args:multiple';
        $allowMultipleField             = [
            'before_row' => '<div class="c-field-type-args up-s-hidden" data-type="tag">',
            'after_row'  => '</div>',
            'name'       => __('Allow Multiple Selection', 'upstream-custom-fields'),
            'desc'       => __('Indicates that multiple options can be selected in the list.',
                'upstream-custom-fields'),
            'id'         => $idPrefix . $allowMultipleField_id,
            'type'       => 'radio_inline',
            'default'    => 'no',
            'options'    => [
                'yes' => __('Yes', 'upstream-custom-fields'),
                'no'  => __('No', 'upstream-custom-fields'),
            ],
        ];
        $fields[$allowMultipleField_id] = $allowMultipleField;

        return $fields;
    }

    /**
     * Render all custom args fields into a given metabox object.
     *
     * @since       1.1.0
     * @abstract
     * @static
     *
     * @param       \CMB2 $metabox CMB2 metabox object.
     */
    public static function renderFieldsIntoMetabox($metabox)
    {
        $fieldsGrid = new Cmb2Grid($metabox);

        $fields = self::getFields();

        foreach ($fields as $fieldName => $field) {
            $fields[$fieldName] = $metabox->add_field($field);

            if ($field['type'] === 'group'
                && isset($field['fields'])
                && count($field['fields'])
            ) {
                if (isset($field['all_fields_inline']) && $field['all_fields_inline'] === true) {
                    $cmb2GroupGrid = $fieldsGrid->addCmb2GroupGrid($fields[$fieldName]);

                    $fieldsGridRow = $cmb2GroupGrid->addRow();

                    foreach ($field['fields'] as $groupFieldName => $groupField) {
                        $field['fields'][$groupFieldName] = $metabox->add_group_field($field['id'], $groupField);
                    }

                    $fieldsGridRow->addColumns(array_values($field['fields']));
                } else {
                    foreach ($field['fields'] as $groupFieldName => $groupField) {
                        $field['fields'][$groupFieldName] = $metabox->add_group_field($field['id'], $groupField);
                    }
                }
            }
        }
    }


}
