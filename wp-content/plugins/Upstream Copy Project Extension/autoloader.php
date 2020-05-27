<?php
/**
 * File that loads the plugin autoloader file.
 *
 * @package     UpStream\Plugins\CopyProject
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\CopyProject\PSR4\Autoloader;

// Load the constants file.
require_once __DIR__ . '/defines.php';

if ( ! class_exists(UP_COPY_PROJECT_NAMESPACE . '\\Autoloader')) {
    require_once UP_COPY_PROJECT_LIBRARY_PATH . '/PSR4/Autoloader.php';
}

Autoloader::register(
    UP_COPY_PROJECT_NAMESPACE,
    UP_COPY_PROJECT_LIBRARY_PATH
);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
