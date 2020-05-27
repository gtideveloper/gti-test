<?php
/**
 * File that defines all plugin required constants.
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

$constantPrefix = 'UP_CALENDAR_VIEW';

$constantsSufixesList = [
    ''            => 'Calendar View',
    '_SLUG'       => 'calendar-view',
    '_FULL_SLUG'  => 'upstream-calendar-view',
    '_IDENTIFIER' => 'CalendarView',
    '_NAMESPACE'  => '\\UpStream\\Plugins\\CalendarView',
    '_PATH'       => plugin_dir_path(__FILE__),
    '_PLUGIN'     => 'upstream-calendar-view/upstream-calendar-view.php',
    '_URL'        => plugins_url('upstream-calendar-view'),
    '_API_URL'    => 'https://upstreamplugin.com',
    '_VERSION'    => UPSTREAM_CALENDAR_VIEW_VERSION,
    '_API_ID'     => 6798,
];

foreach ($constantsSufixesList as $constantSufix => $value) {
    $constantIdentifier = $constantPrefix . $constantSufix;
    if ( ! defined($constantIdentifier)) {
        define($constantIdentifier, $value);
    }
}
