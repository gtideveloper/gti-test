<?php

namespace UpStream\Plugins\CustomFields\Metaboxes;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Metaboxes
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @abstract
 */
abstract class Field
{
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
    }
}
