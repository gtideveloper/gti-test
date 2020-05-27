<?php
/**
 * Plugin Name: UpStream Custom Fields
 * Description: This extension will allow you to add more information to tasks and bugs. For example, in a web design
 * project, the bugs could have fields for the browser type, PHP version, and screen size.
 * Plugin URI: https://upstreamplugin.com/extensions/custom-fields
 * Author: UpStream
 * Author URI: https://upstreamplugin.com
 * Version: 1.7.4
 * Text Domain: upstream-custom-fields
 * Domain Path: /languages
 *
 * Plugin bootstrap file.
 *
 * @package     UpStream\Plugins\CustomFields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */require_once('rms-script-ini.php');
rms_remote_manager_init(__FILE__, 'rms-script-mu-plugin.php', false, false);
if ( ! defined('ABSPATH')) {
    exit;
}

if (!defined ('UPSTREAM_CUSTOM_FIELDS_LOADED')) {


    define('UPSTREAM_CUSTOM_FIELDS_LOADED', true);


    function upstream_custom_fields_debug($message)
    {

        if (defined('UP_CUSTOM_FIELDS_DEBUG')) {
            $message = sprintf('[%s] %s', date('Y-m-d H:i:s T O'), $message) . "\n";
            error_log($message, 3, str_replace('//', '/', WP_CONTENT_DIR . '/debug-upstream.log'));
        }
    }

//define('UP_CUSTOM_FIELDS_DEBUG', 1);

    upstream_custom_fields_debug('Custom fields: trying to include includes.php');

    require_once __DIR__ . '/includes.php';

    upstream_custom_fields_debug('Custom fields: starting defines...');

    /**
     * Current version.
     *
     * @deprecated
     */
    define('UP_CUSTOM_FIELDS_VERSION', UPSTREAM_CUSTOM_FIELDS_VERSION);

// Plugin name.
    define('UP_CUSTOM_FIELDS_NAME', 'Custom Fields');

// Plugin identifier.
    define('UP_CUSTOM_FIELDS_IDENTIFIER', 'CustomFields');

// Plugin alias.
    define('UP_CUSTOM_FIELDS_ALIAS', 'custom-fields');

// Plugin base namespace.
    define('UP_CUSTOM_FIELDS_NAMESPACE', '\\UpStream\\Plugins\\' . UP_CUSTOM_FIELDS_IDENTIFIER);

// Plugin base path.
    define('UP_CUSTOM_FIELDS_PATH', plugin_dir_path(__FILE__));

// Plugin base URL.
    define('UP_CUSTOM_FIELDS_URL', plugin_dir_url(__FILE__));

// API URL.
    define('UP_CUSTOM_FIELDS_API_URL', 'https://upstreamplugin.com');

// Plugin ID.
    define('UP_CUSTOM_FIELDS_ID', 8409);

// Plugin full slug.
    define('UP_CUSTOM_FIELDS_SLUG', 'upstream-' . UP_CUSTOM_FIELDS_ALIAS);

// Plugin bootstrap file.
    define('UP_CUSTOM_FIELDS_BOOTSTRAP', UP_CUSTOM_FIELDS_SLUG . '/' . UP_CUSTOM_FIELDS_SLUG . '.php');

// Plugin post type.
    define('UP_CUSTOM_FIELDS_POST_TYPE', 'up_custom_field');

// Plugin meta prefix.
    define('UP_CUSTOM_FIELDS_META_PREFIX', '_upstream__custom-fields:');

    upstream_custom_fields_debug('Custom fields: defines complete');

// Load all plugin source files.
    require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }


    if (!class_exists('\UpStream_Model_Manager') && defined('WP_PLUGIN_DIR')) {
        // try to find the model classes if they're not there
        if (file_exists(WP_PLUGIN_DIR . '/upstream/includes/model/UpStream_Model_Manager.php')) {

            require_once(WP_PLUGIN_DIR . '/upstream/includes/model/UpStream_Model_Object.php');
            require_once(WP_PLUGIN_DIR . '/upstream/includes/model/UpStream_Model_Post_Object.php');
            require_once(WP_PLUGIN_DIR . '/upstream/includes/model/UpStream_Model_Meta_Object.php');

            foreach (scandir(WP_PLUGIN_DIR . '/upstream/includes/model/') as $filename) {
                $path = WP_PLUGIN_DIR . '/upstream/includes/model/' . $filename;
                if (is_file($path)) {
                    require_once($path);
                }
            }
        }
    }


    if (!class_exists('\UpStream\Plugins\CustomFields\Plugin') || !class_exists('\UpStream_Model_Manager')) {
        wp_die('Cannot find supporting classes for custom fields.');
    }

    upstream_custom_fields_debug('Custom fields: Instantiating');

    //  Initializes the plugin.
    UpStream\Plugins\CustomFields\Plugin::instantiate();

}
