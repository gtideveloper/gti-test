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
final class Radio extends FieldArgsMetabox
{
    use Singleton;

    /**
     * Field type.
     *
     * @since   1.0.0
     * @const   FIELD_TYPE
     */
    const FIELD_TYPE = 'radio';

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

        if ($args['type'] === 'radio_inline') {
            $args['classes'] .= ' cmb-inline';
        }

        // If it is an array, make sure to convert the value to a string correctly.
        if (is_array($field->value)) {
            if (empty($field->value)) {
                $field->value = '';
            } else {
                $field->value = $field->value[0];
            }
        }

        $showNoneOption   = isset($args['do_show_option_none']) ? (bool)$args['do_show_option_none'] : false;
        $fieldValue       = strlen((string)$field->value) > 0 ? (string)$field->value : "";
        $fieldDescription = isset($args['desc']) ? $args['desc'] : (isset($args['description']) ? $args['description'] : "");
        $defaultOptions   = isset($args['default']) ? (string)$args['default'] : "";
        $options          = isset($args['options']) ? $args['options'] : [];

        ?>
        <div
            class="cmb-row cmb-type-radio cmb2-id-<?php echo $args['id']; ?> cmb-repeat-group-field o-up-custom-field <?php echo $args['classes']; ?>"
            data-field-type="radio" data-default="<?php echo $defaultOptions; ?>">
            <div class="cmb-th">
                <label for="<?php echo $args['id']; ?>"><?php echo $args['name']; ?></label>
            </div>
            <div class="cmb-td">
                <ul class="cmb2-radio-list cmb2-list<?php echo $isRequired ? ' is-required' : ''; ?>">
                    <?php if ($showNoneOption): ?>
                        <li>
                            <label>
                                <input
                                    type="radio"
                                    class="cmb2-option"
                                    name="<?php echo $args['_name']; ?>"
                                    id="<?php echo $args['id']; ?>"
                                    value="__none__"
                                    <?php echo strlen($defaultOptions) === 0 ? ' data-selected-by-default' : ''; ?>
                                    <?php echo strlen($fieldValue) === 0 || $fieldValue === '__none__' ? ' checked="checked"' : ''; ?>>
                                <?php echo _e('None', 'upstream'); ?>
                            </label>
                        </li>
                    <?php endif; ?>
                    <?php foreach ($options as $optionValue => $optionLabel): ?>
                        <li>
                            <label>
                                <input
                                    type="radio"
                                    class="cmb2-option"
                                    name="<?php echo $args['_name']; ?>"
                                    id="<?php echo $args['id']; ?>"
                                    value="<?php echo $optionValue; ?>"
                                    <?php echo $optionValue === $defaultOptions ? ' data-selected-by-default' : ''; ?>
                                    <?php echo $optionValue === $fieldValue || (strlen($fieldValue) === 0 && $optionValue === $defaultOptions) ? ' checked="checked"' : ''; ?>>
                                <?php echo $optionLabel; ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (strlen($fieldDescription) > 0): ?>
                    <p class="cmb2-metabox-description"><?php echo $fieldDescription; ?></p>
                <?php endif; ?>
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
            'before_group'      => '<div class="c-field-type-args up-s-hidden" data-type="radio"><p><span class="dashicons dashicons-info"></span> ' . __('Only one option can be selected per field.',
                    'upstream-custom-fields') . '</p>',
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

        $layoutField_id          = 'args:layout';
        $layoutField             = [
            'name'    => __('Options list display layout', 'upstream-custom-fields'),
            'id'      => $idPrefix . $layoutField_id,
            'type'    => 'radio_inline',
            'default' => 'default',
            'options' => [
                'default' => __('Vertical', 'upstream-custom-fields'),
                'inline'  => __('Horizontal', 'upstream-custom-fields'),
            ],
        ];
        $fields[$layoutField_id] = $layoutField;

        $showNoneOption_id          = 'args:show_none_option';
        $showNoneOption             = [
            'name'      => __('Show the "None" option?', 'upstream-custom-fields'),
            'id'        => $idPrefix . $showNoneOption_id,
            'type'      => 'radio_inline',
            'options'   => [
                'yes' => __('Yes', 'upstream-custom-fields'),
                'no'  => __('No', 'upstream-custom-fields'),
            ],
            'default'   => 'no',
            'after_row' => '</div>',
        ];
        $fields[$showNoneOption_id] = $showNoneOption;

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

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([$fields['args:options']]);

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([$fields['args:layout'], $fields['args:show_none_option']]);
    }


}
