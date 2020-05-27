<?php

namespace UpStream\Plugins\CustomFields\CMB2;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\CustomFields\Traits\Singleton;

/**
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Traits
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */
class OptionValueField
{
    use Singleton;

    /**
     * Store the current namespace so it can be reused on various methods.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     string $namespace
     */
    private static $namespace;

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        self::$namespace = __NAMESPACE__ . '\OptionValueField';

        self::attachHooks();
    }

    /**
     * Define actions and filters.
     *
     * @since   1.0.0
     * @static
     */
    public static function attachHooks()
    {
        add_action('cmb2_render_option_value', [self::$namespace, 'renderOptionValueField'], 10, 5);
    }

    /**
     * Render options value field.
     *
     * @since   1.0.0
     * @static
     *
     * @param   CMB2_Field $field      Field object.
     * @param   mixed      $fieldValue Sanitized value.
     * @param   int        $post_id    Post ID.
     * @param   string     $postType   Post type.
     * @param   CMB2_Type  $fieldType  Field type object.
     */
    public static function renderOptionValueField($field, $fieldValue, $post_id, $postType, $fieldType)
    {
        $fieldDescription = isset($field->args['desc']) ? $field->args['desc'] : (isset($field->args['description']) ? $field->args['description'] : "");

        $attributes = [
            'type'  => 'text',
            'class' => 'regular-text up-o-field-option_value',
            'id'    => $field->args['id'],
            'name'  => $field->args['_name'],
            'value' => $fieldValue,
        ];

        $isRequired = (
            isset($field->args['attributes']['required'])
            && $field->args['attributes']['required'] === 'required'
        );

        if ($isRequired) {
            $attributes['class']    .= ' required up-is-required';
            $attributes['required'] = 'required';
        }

        $attributesChunks = [];
        foreach ($attributes as $attrName => $attrValue) {
            $attributesChunks[] = sprintf('%s="%s"', $attrName, $attrValue);
        }
        ?>

        <input <?php echo implode(' ', $attributesChunks); ?>>

        <?php if (strlen($fieldDescription) > 0): ?>
        <p class="cmb2-metabox-description"><?php echo $fieldDescription; ?></p>
    <?php endif; ?>

        <?php
    }
}
