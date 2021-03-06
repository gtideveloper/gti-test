<?php

namespace UpStream\Plugins\CustomFields\Metaboxes\Fields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Cmb2Grid\Grid\Cmb2Grid;
use UpStream\Plugins\CustomFields\Fields\User as UserField;
use UpStream\Plugins\CustomFields\Metaboxes\Field as FieldArgsMetabox;
use UpStream\Plugins\CustomFields\Traits\Singleton;

/**
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Metaboxes\Fields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.4.2
 * @final
 */
final class User extends FieldArgsMetabox
{
    use Singleton;

    /**
     * Field type.
     *
     * @since   1.4.2
     * @const   FIELD_TYPE
     */
    const FIELD_TYPE = 'user';

    /**
     * Class constructor.
     *
     * @since   1.4.2
     */
    public function __construct()
    {
        add_action('upstream.custom-fields:' . self::FIELD_TYPE . '.render_args_metabox',
            [$this, 'renderFieldsIntoMetabox'], 10, 1);
    }

    /**
     * Render custom options for current field.
     *
     * @param array       $args  Array of field settings.
     * @param \CMB2_Field $field Current CMB2 field.
     *
     * @since   1.4.2
     * @static
     *
     */
    public static function renderOptions($args, $field)
    {
        $attributes    = isset($args['attributes']) && is_array($args['attributes']) ? $args['attributes'] : [];
        $allowMultiple = isset($args['attributes']) && isset($args['attributes']['multiple']) && $args['attributes']['multiple'] === 'multiple';

        $defaultOptions = (array)$args['default'];
        $selectValues   = [];

        if (!empty($field->value)) {

            if (!is_array($field->value)) {
                $field->value = [$field->value];
            }

            foreach ($field->value as $v) {
                $selectValues = array_merge($selectValues, explode(',', $v));
            }
        }

        $options = null;

        $posts = \Upstream_Cache::get_instance()->get('CustomFields_user_ro_'.$args['_id']);

        if ($posts === false) {
            $query = new \WP_Query([
                'name' => $args['_id'],
                'post_type' => 'up_custom_field',
                'post_status' => 'publish'
            ]);

            $posts = $query->posts;
            \Upstream_Cache::get_instance()->set('CustomFields_user_ro_'.$args['_id'], $posts);
        }

        if (empty($posts) || ! is_array($posts) || count($posts) < 1) {

        } else {
            $uf = new UserField($posts[0]);
            $options = UserField::fetchFieldOptions($uf);
        }

        if (!$options) {
            $options = [];
        }

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
                    <?php if (!$allowMultiple): ?>
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
     * @return  array
     * @since   1.4.2
     * @static
     *
     */
    public static function getFields()
    {
        $fields = [];

        $idPrefix = UP_CUSTOM_FIELDS_META_PREFIX . self::FIELD_TYPE . ':';

        $allowMultipleField_id          = 'args:multiple';
        $allowMultipleField             = [
            'before_row' => '<div class="c-field-type-args up-s-hidden" data-type="user">',
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

        global $wp_roles;

        $roles = $wp_roles->roles;

        $options = [];
        foreach ($roles as $role => $data) {
            $options[$role] = $data['name'];
        }

        $rolesField_id          = 'args:roles';
        $roleField              = [
            'after_row' => '</div>',
            'name'      => __('User Roles', 'upstream-custom-fields'),
            'desc'      => __('Select which user roles are allowed for this field.',
                'upstream-custom-fields'),
            'id'        => $idPrefix . $rolesField_id,
            'type'      => 'multicheck',
            'options'   => $options,
        ];
        $fields[$rolesField_id] = $roleField;

        return $fields;
    }

    /**
     * Render all custom args fields into a given metabox object.
     *
     * @param \CMB2 $metabox CMB2 metabox object.
     *
     * @since       1.4.2
     * @abstract
     * @static
     *
     */
    public static function renderFieldsIntoMetabox($metabox)
    {
        $fieldsGrid = new Cmb2Grid($metabox);

        $fields = self::getFields();

        foreach ($fields as $fieldName => $field) {
            $fields[$fieldName] = $metabox->add_field($field);
        }

        $fieldsGridRow = $fieldsGrid->addRow();
        $fieldsGridRow->addColumns([
            [
                $fields['args:multiple'],
            ],
        ]);
    }
}
