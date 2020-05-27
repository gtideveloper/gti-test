<?php

namespace UpStream\Plugins\CustomFields\Metaboxes;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\CustomFields\Traits\Singleton;

final class Usage
{
    use Singleton;

    public static function render()
    {
        $metabox = new_cmb2_box([
            'id'           => UP_CUSTOM_FIELDS_META_PREFIX . 'usage',
            'title'        => __('Use this field on', 'upstream-custom-fields'),
            'object_types' => ['up_custom_field'],
            'context'      => 'side',
            'priority'     => 'high',
            'show_names'   => true,
        ]);

        $metabox->add_field([
            'name'    => __('Usage', 'upstream-custom-fields'),
            'desc'    => __('Choose where this field should be used.', 'upstream-custom-fields'),
            'id'      => UP_CUSTOM_FIELDS_META_PREFIX . 'usage',
            'type'    => 'multicheck',
            'options' => [
                'project'   => upstream_project_label_plural(),
                'milestone' => upstream_milestone_label_plural(),
                'task'      => upstream_task_label_plural(),
                'bug'       => upstream_bug_label_plural(),
                'file'      => upstream_file_label_plural(),
                'client'    => upstream_client_label_plural(),
            ],
        ]);

        $metabox->add_field([
            'name'    => __('Filterable', 'upstream-custom-fields'),
            'desc'    => __('Whether the field can be filtered on frontend or not.', 'upstream-custom-fields'),
            'id'      => UP_CUSTOM_FIELDS_META_PREFIX . 'is_filterable',
            'type'    => 'radio_inline',
            'options' => [
                'yes' => __('Yes', 'upstream'),
                'no'  => __('No', 'upstream'),
            ],
            'default' => 'yes',
        ]);

        $metabox->add_field([
            'name'    => __('Display as column', 'upstream-custom-fields'),
            'desc'    => __('Whether the field should be added as column to tables.', 'upstream-custom-fields'),
            'id'      => UP_CUSTOM_FIELDS_META_PREFIX . 'show_column',
            'type'    => 'radio_inline',
            'options' => [
                'yes' => __('Yes', 'upstream'),
                'no'  => __('No', 'upstream'),
            ],
            'default' => 'no',
        ]);

        $metabox->add_field([
            'name'    => __('Weight (number)', 'upstream-custom-fields'),
            'desc'    => __('Enter a number from 0 to 1000. This allows you to order custom fields in display. A larger weight number means the item will show up lower on the page. Note: in order for this to work, ALL custom fields must have weights.', 'upstream-custom-fields'),
            'id'      => UP_CUSTOM_FIELDS_META_PREFIX . 'weight',
            'type'    => 'text_small',
            'default' => 0
        ]);


    }
}
