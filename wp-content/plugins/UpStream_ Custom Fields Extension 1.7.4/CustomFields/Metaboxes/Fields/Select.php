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
final class Select extends FieldArgsMetabox
{
    use Singleton;

    /**
     * Field type.
     *
     * @since   1.0.0
     * @const   FIELD_TYPE
     */
    const FIELD_TYPE = 'select';

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
     * @since   1.0.0
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

        $attributes = isset($args['attributes']) && is_array($args['attributes']) ? $args['attributes'] : [];

        $defaultOptions = (array)$args['default'];
        $selectValues   = array_filter((array)$field->value);

        $options = isset($args['options']) ? $args['options'] : [];
        ?>
        <div
            class="cmb-row cmb-type-select cmb2-id-<?php echo $args['id']; ?> <?php echo is_array($args['classes']) ? implode(' ',
                $args['classes']) : ''; ?>" data-fieldtype="select"
            data-default="<?php echo implode(',', $defaultOptions); ?>">
            <div class="cmb-th">
                <label for="<?php echo $args['id']; ?>"><?php echo $args['name']; ?></label>
            </div>
            <div class="cmb-td">
                <?php
                if ( ! isset($attributes['class'])) {
                    $attributes['class'] = '';
                }

                if (is_array($attributes['class'])) {
                    $attributes['class'][] = 'cmb2_select';
                } else {
                    $attributes['class'] .= ' cmb2_select';
                }

                $attributes['name'] = $args['_name'] . ((bool)$args['multiple'] ? '[]' : '');
                $attributes['id']   = $args['id'];

                if ((bool)$args['multiple']) {
                    $attributes['multiple'] = 'multiple';
                }

                $attrs = [];
                foreach ($attributes as $attrName => $attrValue) {
                    $attrs[] = sprintf('%s="%s"', $attrName, $attrValue);
                }
                ?>
                <select <?php echo implode(' ', $attrs); ?>>
                    <?php if (empty($selectValues)): ?>
                        <option></option>
                    <?php endif; ?>

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

        $optionsField_id          = 'args:options';
        $optionsField             = [
            'id'                => $idPrefix . $optionsField_id,
            'type'              => 'group',
            'repeatable'        => true,
            'name'              => __('Options', 'upstream-custom-fields'),
            'before_group'      => '<div class="c-field-type-args up-s-hidden" data-type="select">',
            'after_group'       => '</div>',
            'options'           => [
                'group_title'   => _x('Option {#}', '{#} stands for the option index', 'upstream-custom-fields'),
                'add_button'    => __('Add Another Option', 'upstream-custom-fields'),
                'remove_button' => __('Remove Option', 'upstream-custom-fields'),
                'sortable'      => true,
                'closed'        => true,
            ],
            'all_fields_inline' => true,
            'fields'            => [
                'label'    => [
                    'name'       => __('Label', 'upstream-custom-fields'),
                    'id'         => 'label',
                    'type'       => 'text',
                    'desc'       => __('Indicates the meaning of the option. This is displayed to users.',
                        'upstream-custom-fields'),
                    'attributes' => [
                        'required' => 'required',
                    ],
                ],
                'value'    => [
                    'name'       => __('Value', 'upstream-custom-fields'),
                    'id'         => 'value',
                    'type'       => 'option_value',
                    'desc'       => __('This is the value that will be saved in the database.',
                        'upstream-custom-fields'),
                    'attributes' => [
                        'required' => 'required',
                    ],
                ],
                'selected' => [
                    'name'    => __('Selected by default', 'upstream-custom-fields'),
                    'id'      => 'selected',
                    'type'    => 'checkbox',
                    'desc'    => __('The option should be initially selected.', 'upstream-custom-fields'),
                    'options' => [
                        'yes' => __('Yes', 'upstream-custom-fields'),
                    ],
                ],
            ],
        ];
        $fields[$optionsField_id] = $optionsField;

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
