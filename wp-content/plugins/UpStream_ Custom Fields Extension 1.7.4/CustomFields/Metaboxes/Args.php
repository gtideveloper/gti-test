<?php

namespace UpStream\Plugins\CustomFields\Metaboxes;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Cmb2Grid\Grid\Cmb2Grid;
use UpStream\Plugins\CustomFields\Fields\Field as FieldSingleton;
use UpStream\Plugins\CustomFields\Model;
use UpStream\Plugins\CustomFields\Traits\Singleton;

final class Args
{
    use Singleton;

    public static function render()
    {
        $metabox = new_cmb2_box([
            'id'           => UP_CUSTOM_FIELDS_META_PREFIX . 'args',
            'title'        => __('Options', 'upstream-custom-fields'),
            'object_types' => [UP_CUSTOM_FIELDS_POST_TYPE],
            'context'      => 'normal',
            'priority'     => 'high',
        ]);

        $field_id = upstream_post_id();
        if ($field_id === 0
            || (isset($_REQUEST['_wp_http_referer'])
                && strpos($_REQUEST['_wp_http_referer'], '/post-new.php?') !== false
            )
        ) {
            $fieldTypes = Model::getFieldTypes();
            foreach ($fieldTypes as $fieldType => $fieldTypeName) {
                do_action('upstream.custom-fields:' . $fieldType . '.render_args_metabox', $metabox);
            }
        } else {
            $fieldType = FieldSingleton::getMetaForId('type', $field_id);
            do_action('upstream.custom-fields:' . $fieldType . '.render_args_metabox', $metabox);
        }
    }
}
