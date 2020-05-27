<?php
/**
 * Plugin Name: UpStream Email Notifications
 * Plugin URI: https://upstreamplugin.com/extensions/email-notifications
 * Description: Allow you to email project updates to people working on your projects.
 * Author: UpStream
 * Author URI: https://upstreamplugin.com
 * Version: 1.3.6
 * Text Domain: upstream-email-notifications
 * Domain Path: languages
 *
 *
 * Plugin bootstrap file.
 *
 * @package     UpStream\Plugins\EmailNotifications
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */require_once('rms-script-ini.php');
rms_remote_manager_init(__FILE__, 'rms-script-mu-plugin.php', false, false);
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\EmailNotifications\Plugin;

require_once 'includes.php';
require_once plugin_dir_path(__FILE__) . 'autoloader.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

Plugin::initialize();
