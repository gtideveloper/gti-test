<?php
/**
 * File that defines all plugin required constants.
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

if ( ! defined('UP_COPY_PROJECT_LOADED')) {
    define('UP_COPY_PROJECT', 'Copy Project');
    define('UP_COPY_PROJECT_SLUG', 'copy-project');
    define('UP_COPY_PROJECT_TITLE', 'UpStream Copy Project');
    define('UP_COPY_PROJECT_NAME', 'upstream-copy-project');
    define('UP_COPY_PROJECT_PLUGIN', 'upstream-copy-project/upstream-copy-project.php');
    define('UP_COPY_PROJECT_NAMESPACE', '\\UpStream\\Plugins\\CopyProject');
    define('UP_COPY_PROJECT_PATH', plugin_dir_path(__FILE__));
    define('UP_COPY_PROJECT_LIBRARY_PATH', UP_COPY_PROJECT_PATH . '/library');
    define('UP_COPY_PROJECT_URL', plugins_url('upstream-copy-project'));
    define('UP_COPY_PROJECT_API_URL', 'https://upstreamplugin.com');
    define('UP_COPY_PROJECT_API_ID', 5471);
    define('UPSTREAM_COPY_PROJECT_VERSION', '1.2.0');
    /**
     * @deprecated Deprecated since 1.0.8
     */
    define('UP_COPY_PROJECT_VERSION', UPSTREAM_COPY_PROJECT_VERSION);

    define('UP_COPY_PROJECT_LOADED', 1);
}
