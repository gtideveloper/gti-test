<?php
/**
 * Defines all plugin constants.
 *
 * @package     UpStream\Plugins\EmailNotifications
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */

// Prevent direct access.
if (!defined('ABSPATH')) exit;

$constantsPrefix = 'UPSTREAM_EMAIL_NOTIFICATIONS';

$constantsSchema = array(
    ''            => 'EmailNotifications',
    '_NAME'       => 'upstream-email-notifications',
    '_IDENTIFIER' => 'upstream_email_notifications',
    '_SLUG'       => 'email-notifications',
    '_TITLE'      => 'UpStream - Email Notifications',
    '_PLUGIN'     => 'upstream-email-notifications/upstream-email-notifications.php',
    '_PATH'       => plugin_dir_path(__FILE__),
    '_NAMESPACE'  => '\\UpStream\\Plugins\\EmailNotifications',
    '_VERSION'    => UPSTREAM_EMAIL_NOTIFICATIONS_VERSION,
    '_ID'         => 4996,
    '_API_URL'    => 'https://upstreamplugin.com/',
);

foreach ($constantsSchema as $key => $value) {
    $fullKey = $constantsPrefix . $key;
    if (!defined($fullKey)) {
        define($fullKey, $value);
    }
}
