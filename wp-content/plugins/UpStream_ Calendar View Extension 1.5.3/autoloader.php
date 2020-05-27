<?php
/**
 * File that loads the plugin autoloader file.
 *
 * @package     UpStream\Plugins\CalendarView
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\CalendarView\PSR4\Autoloader;

// Load the constants file.
require_once 'constants.php';

if ( ! class_exists(UP_CALENDAR_VIEW_NAMESPACE . '\\Autoloader')) {
    require_once UP_CALENDAR_VIEW_PATH . UP_CALENDAR_VIEW_IDENTIFIER . '/PSR4/Autoloader.php';
}

Autoloader::register(
    UP_CALENDAR_VIEW_NAMESPACE,
    UP_CALENDAR_VIEW_PATH . UP_CALENDAR_VIEW_IDENTIFIER
);
