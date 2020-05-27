<?php
/**
 * Edit Project Functions
 *
 * @package     UpStream
 * @subpackage  Functions/Templates
 * @copyright   Copyright (c) 2016, UpStream
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Outputs a field for use on the frontend
 *
 * @param  $field_type text, textarea, select etc
 * @param  $name       field name and id
 * @param  $label      the field label
 * @param  $required   is the field required
 */
function upstream_frontend_field($field_type, $name, $label, $required = false)
{

    // required fields
    if (empty($field_type) || empty($name)) {
        return;
    }

    // if no label, use the name
    if (empty($label)) {
        $label = ucwords($name);
    }

    $class = null;
    $reqd  = null;

    $fieldId   = esc_attr($name);
    $fieldName = preg_replace('/upstream_[a-z0-9]+_/i', '', $fieldId);

    $output = '<div class="form-group up-' . $fieldId . '">';
    $output .= '<label class="control-label col-md-3 col-sm-3 col-xs-12" for="' . $fieldId . '">' . esc_html($label) . '';

    if ($required) {
        $output .= '<span class="required">*</span>';
        $reqd   = 'required="required"';
    }

    $output .= '</label>';
    $output .= '<div class="col-md-6 col-sm-6 col-xs-12">';

    switch ($field_type) {
        case 'date':
        case 'text':
            $attrs = [
                'type'  => 'text',
                'id'    => $fieldId,
                'name'  => $field_type === 'text' ? $fieldName : '',
                'class' => 'form-control col-md-7 col-xs-12' . ($field_type === 'date' ? ' datepicker' : ''),
            ];

            if ($required) {
                $attrs['required'] = 'required';
            }

            foreach ($attrs as $attr => $attrValue) {
                $attrs[$attr] = $attr . '="' . $attrValue . '"';
            }

            $output .= sprintf('<input %s />', implode(' ', $attrs));

            if ($field_type === 'date') {
                $output .= '<input type="hidden" id="' . $fieldId . '_timestamp" name="' . $fieldName . '" data-picker-id="#' . $fieldId . '" />';
            }
            break;

        case 'textarea':

            $output .= '<textarea rows="3" id="' . $fieldId . '" name="' . $fieldName . '" ' . esc_attr($reqd) . ' class="form-control"></textarea>';

            break;

        case 'select':

            $output .= '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '">';

            switch ($fieldName) {
                case 'assigned_to':
                    $data = upstream_project_users_dropdown();
                    break;
                case 'progress':
                    $data = upstream_get_percentages_for_dropdown();
                    break;
                case 'milestone':
                    $milestones = upstream_project_milestones();
                    if ($milestones) {
                        $data[''] = __('None', 'upstream');
                        foreach ($milestones as $key => $value) {
                            $data[$value['id']] = $value['milestone'];
                        }
                    }
                    break;
                case 'milestones':
                    $milestones = upstream_admin_get_options_milestones();
                    if ($milestones) {
                        foreach ($milestones as $key => $value) {
                            $data[$key] = $value;
                        }
                    }
                    break;

                default:
                    # code...
                    break;
            }

            if (isset($data) && $data) :
                foreach ($data as $value => $label) {
                    $output .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                }
            endif;

            $output .= '</select>';

            break;


        case 'file':

            $output .= '<input type="' . esc_attr($field_type) . '" id="' . $fieldId . '" name="' . $fieldName . '" ' . esc_attr($reqd) . ' class="form-control col-md-7 col-xs-12 ' . esc_attr($class) . '" />';
            $output .= '<div class="file-preview" style="display:none"><div></div></div>';

            break;

        case 'wysiwyg':
            ob_start();

            wp_editor("", $fieldId, [
                'media_buttons' => true,
                'textarea_rows' => 5,
                'textarea_name' => $fieldName,
            ]);

            $output .= ob_get_contents();

            @ob_end_clean();

            break;

        default:
            $output .= '<input type="' . esc_attr($field_type) . '" id="' . $fieldId . '" name="' . $fieldName . '" ' . esc_attr($reqd) . ' class="form-control col-md-7 col-xs-12 ' . esc_attr($class) . '" />';
            break;
    }
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Returns all users with select roles.
 * For use in dropdowns.
 */
function upstream_project_users_dropdown()
{
    $options = [
        '' => __('None', 'upstream'),
    ];

    $projectUsers = upstream_admin_get_all_project_users();

    $options += $projectUsers;

    return $options;
}

/**
 * Outputs a dropdown field for use on the frontend.
 * Populated with options from the um, options
 *
 * @param  $option   upstream_milestones, upstream_tasks or upstream_bugs
 * @param  $type     status, severities
 * @param  $name     field name and id
 * @param  $label    the field label
 * @param  $required is the field required
 */
function upstream_dropdown_from_options($option, $type, $name, $label, $required = false)
{

    // required fields
    if (empty($option) || empty($type) || empty($name)) {
        return;
    }

    // if no label, use the name
    if (empty($label)) {
        $label = ucwords($name);
    }

    $option = get_option($option);
    $data   = isset($option[$type]) ? $option[$type] : '';

    $class = null;
    $reqd  = null;

    $fieldId   = esc_attr($name);
    $fieldName = preg_replace('/upstream_[a-z0-9]+_/i', '', $fieldId);

    $output = '<div class="form-group">';
    $output .= '<label class="control-label col-md-3 col-sm-3 col-xs-12" for="' . $fieldId . '">' . esc_html($label) . '';

    if ($required) {
        $output .= '<span class="required">*</span>';
        $reqd   = 'required="required"';
    }

    $output .= '</label>';
    $output .= '<div class="col-md-6 col-sm-6 col-xs-12">';
    $output .= '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '">';

    if ($type !== 'milestones') {
        $output .= '<option value="">' . __('None', 'upstream') . '</option>';
    }

    if ($data) :
        foreach ($data as $item) {
            $itemName = esc_html(isset($item['name']) ? $item['name'] : $item['title']);

            $output .= '<option value="' . $itemName . '">' . $itemName . '</option>';
        }
    endif;

    $output .= '</select>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;

}
