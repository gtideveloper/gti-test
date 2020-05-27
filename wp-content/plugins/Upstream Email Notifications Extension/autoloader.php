<?php
/**
 * Load all plugin files and its dependencies.
 *
 * @package     UpStream\Plugins\EmailNotifications
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\EmailNotifications\Psr4Autoloader;

require_once 'constants.php';

if ( ! class_exists(UPSTREAM_EMAIL_NOTIFICATIONS_NAMESPACE . '\\Psr4Autoloader')) {
    require_once UPSTREAM_EMAIL_NOTIFICATIONS_PATH . UPSTREAM_EMAIL_NOTIFICATIONS . '/Psr4Autoloader.php';

    Psr4Autoloader::register(
        UPSTREAM_EMAIL_NOTIFICATIONS_NAMESPACE,
        UPSTREAM_EMAIL_NOTIFICATIONS_PATH . UPSTREAM_EMAIL_NOTIFICATIONS
    );
}
