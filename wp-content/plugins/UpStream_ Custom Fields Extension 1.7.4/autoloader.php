<?php
/**
 * Plugin autoloader.
 *
 * @package     UpStream\Plugins\CustomFields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\CustomFields\PSR4\Autoloader;

if ( ! class_exists(UP_CUSTOM_FIELDS_NAMESPACE . '\\Autoloader')) {
    require_once UP_CUSTOM_FIELDS_PATH . UP_CUSTOM_FIELDS_IDENTIFIER . '/PSR4/Autoloader.php';
}

Autoloader::register(
    UP_CUSTOM_FIELDS_NAMESPACE,
    UP_CUSTOM_FIELDS_PATH . UP_CUSTOM_FIELDS_IDENTIFIER
);
