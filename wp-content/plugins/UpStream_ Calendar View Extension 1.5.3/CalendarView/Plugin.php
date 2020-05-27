<?php

namespace UpStream\Plugins\CalendarView;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Alledia\EDD_SL_Plugin_Updater;
use UpStream\Plugins\CalendarView\Traits\Singleton;

/**
 * Main plugin class file.
 *
 * @package     UpStream\Plugins\CalendarView
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class Plugin
{
    use Singleton;

    /**
     * Plugin's instance constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        register_activation_hook(UP_CALENDAR_VIEW_PLUGIN, [$this, 'activationCallback']);

        // Load internationalization files.
        $this->loadTextDomain();

        $this->attachHooks();
    }

    /**
     * Load localization files.
     *
     * @since   1.0.0
     */
    public function loadTextDomain()
    {
        $activeLocale = apply_filters('plugin_locale', get_locale(), UPSTREAM_CALENDAR_VIEW_NAME);

        load_textdomain(UPSTREAM_CALENDAR_VIEW_NAME,
            sprintf('%s/%s/%2$s-%3$s.mo', WP_LANG_DIR, UPSTREAM_CALENDAR_VIEW_NAME, $activeLocale));
        load_plugin_textdomain(UPSTREAM_CALENDAR_VIEW_NAME, false, UPSTREAM_CALENDAR_VIEW_NAME . '/languages');
    }

    /**
     * Attach all required filters and actions to its correspondent endpoint methods.
     *
     * @since   1.0.0
     * @access  private
     */
    private function attachHooks()
    {
        $calendarClass = UP_CALENDAR_VIEW_NAMESPACE . '\\Calendar';

        // Make sure requests made to UpStream's API URL are allowed.
        add_filter('http_request_host_is_external', [$this, 'allowApiHost'], 10, 3);

        if (is_admin()) {
            // Add "Settings" link on plugins page.
            add_filter('plugin_action_links_' . UP_CALENDAR_VIEW_PLUGIN, [$this, 'handleActionLinks']);

            add_action('admin_init', [$this, 'initUpdater']);

            // Register the plugin's settings page.
            add_filter('upstream_option_metaboxes', [$this, 'renderOptionsMetabox'], 1);

            // Integrates Calendar View with Customizer extension so its look can be customized.
            // .. Renders a link on Customizer settings page table of contents.
            add_filter('upstream.customizer:settings.table_of_contents',
                [$this, 'filterCustomizerSectionLink'], 10, 1);
            // .. Renders all fields that might be customized.
            add_filter('upstream.customizer:settings.fields', [$this, 'filterCustomizerFields'], 10, 1);
        } else {
            // Enqueue all dependent scripts.
            add_action('wp_enqueue_scripts', [$this, 'enqueueScripts'], 1003);
            // Enqueue all dependent styles.
            add_action('wp_enqueue_scripts', [$this, 'enqueueStyles'], 1003);

            // Render an anchor on the sidebar.
            add_action('upstream_sidebar_before_single_menu', [$this, 'renderSidebarLink'], 11);
            add_action('upstream_sidebar_projects_submenu', [$this, 'renderSidebarProjectsLink']);
            // Render a calendar wrapper inside current project page.
            add_action('upstream_single_project_section_calendar', [$calendarClass, 'renderCalendarPanel'], 11);
            add_filter('upstream_panel_sections', [$calendarClass, 'filterPanelSections'], 20);
            // Render the calendar within current project page.
            add_action('upstream.calendar-view:renderCalendar', [$calendarClass, 'renderCalendar']);
            // Make sure all custom styles for the calendar are loaded.
            add_action('upstream.customizer:render_styles', [$this, 'renderCustomizerStyles']);
        }

        // Make sure calendars can be filtered via AJAX.
        add_action('wp_ajax_upstream.calendar-view:project.get_calendar_data', [$calendarClass, 'reloadCalendar']);
        // Make sure user can change calendar dates via AJAX.
        add_action('wp_ajax_upstream.calendar-view:project.calendar.change_date',
            [$calendarClass, 'changeCalendarDate']);
        // Allow moving items from the calendar.
        add_action('wp_ajax_upstream.calendar-view:calendar.move_item', [$calendarClass, 'moveItem']);
        // Render the Calendar Overview on "/projects" page.
        add_action('upstream:frontend.renderAfterProjectsList', [$calendarClass, 'renderOverviewCalendar']);
    }

    /**
     * Method called once the plugin is activated.
     * If there's a dependency missing, the plugin will be deactivated.
     *
     * @since   1.0.0
     * @static
     */
    public static function activationCallback()
    {
        try {
            self::testMinimumRequirements();
        } catch (\Exception $e) {
            self::dieWithError($e->getMessage());
        }
    }

    /**
     * Initialize EDD updater.
     */
    public function initUpdater()
    {
        $this->updater = new EDD_SL_Plugin_Updater(
            UPSTREAM_CALENDAR_VIEW_API_URL,
            UPSTREAM_CALENDAR_VIEW_PLUGIN_FILE,
            [
                'version' => UPSTREAM_CALENDAR_VIEW_VERSION,
                'license' => get_option('upstream_calendar_view_license_key'),
                'item_id' => UPSTREAM_CALENDAR_VIEW_ID,
                'author'  => 'UpStream',
                'beta'    => false,
            ]
        );
    }

    /**
     * Check if all minimum requirements are satisfied.
     *
     * @since   1.0.0
     * @static
     *
     * @global  $wp_version
     *
     * @throws  \Exception when minimum PHP version required is not satisfied.
     * @throws  \Exception when minimum WordPress version required is not satisfied.
     * @throws  \Exception when UpStream is either not installed or activated.
     */
    public static function testMinimumRequirements()
    {
        if ( ! function_exists('is_plugin_inactive')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $errorMessageTmpl = __('For performance and security reasons this plugin requires <strong>%s</strong> to run.',
            'upstream-calendar-view');

        // Check the PHP version.
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            $errorMessage = sprintf(
                '%s <br/> %s',
                sprintf($errorMessageTmpl, __('PHP version 5.6 or greater')),
                sprintf(_x('Please consider updating your PHP (currently at %s).', '%s = running PHP version',
                    'upstream-calendar-view'), PHP_VERSION)
            );

            throw new \Exception($errorMessage);
        }

        // Check the WordPress version.
        global $wp_version;
        if (version_compare($wp_version, '4.5', '<')) {
            $errorMessage = sprintf(
                '%s <br/> %s',
                sprintf($errorMessageTmpl, __('WordPress version 4.5 or greater')),
                sprintf(_x('Please consider updating your WordPress (currently at %s).',
                    '%s = running WordPress version', 'upstream-calendar-view'), $wp_version)
            );

            throw new \Exception($errorMessage);
        }

        // Check if UpStream is installed and activated.
        $upstream = 'upstream/upstream.php';
        if ( ! file_exists(WP_PLUGIN_DIR . '/' . $upstream) || is_plugin_inactive($upstream)) {
            $errorMessage = sprintf(
                _x('Please make sure %s is <strong>installed</strong> and <strong>active</strong> in order to make this plugin to work.',
                    '%s = "UpStream" localized string', 'upstream-calendar-view'),
                '<a href="https://wordpress.org/plugins/upstream" target="_blank" rel="noreferrer noopener">' . __('UpStream',
                    'upstream') . '</a>'
            );

            throw new \Exception($errorMessage);
        }
    }

    /**
     * Calls wp_die() function with a custom message.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @param   string $message The message to be displayed.
     */
    private static function dieWithError($message = "")
    {
        $message = sprintf('<h1>%s > %s</h1><br/>%s', __('UpStream', 'upstream'),
            __('Calendar View', 'upstream-calendar-view'), $message);

        $message .= sprintf('<br /><br />
            <a class="button" href="javascript:history.back();" title="%s">%s</a>',
            __('Go back to the plugins page.', 'upstream-calendar-view'),
            __('Go Back', 'upstream')
        );

        wp_die($message);
    }

    /**
     * Ensures the licensing API's URL can be used to update the plugin.
     *
     * @since   1.0.0
     * @static
     *
     * @param   bool   $isAllowed Indicates either $host is allowed to communicate or not.
     * @param   string $host      The host being requested.
     * @param   string $url       The URL of the requested host.
     *
     * @return  bool    $isAllowed
     */
    public static function allowApiHost($isAllowed, $host, $url)
    {
        if ($host === preg_replace('/^http[s]?:\/\//i', '', UP_CALENDAR_VIEW_API_URL)) {
            $isAllowed = true;
        }

        return $isAllowed;
    }

    /**
     * Create the plugin's option tab on UpStream's settings page.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $options Array containing all option fields passed by CMB2.
     *
     * @return  array   $options
     */
    public static function renderOptionsMetabox($options)
    {
        $pluginId = str_replace('-', '_', UPSTREAM_CALENDAR_VIEW_NAME);
        $title    = __('Calendar View', 'upstream-calendar-view');

        $pluginOptions = [
            'id'         => $pluginId,
            'title'      => $title,
            'menu_title' => $title,
            'show_names' => true,
            'fields'     => self::getOptionsSchema(),
            'desc'       => "",
            'show_on'    => [
                'key'   => 'options-page',
                'value' => [UPSTREAM_CALENDAR_VIEW_NAME],
            ],
        ];

        $pluginOptions = apply_filters($pluginId . '_option_fields', $pluginOptions);

        array_push($options, $pluginOptions);

        return $options;
    }

    /**
     * Retrieve all plugin options schema defined by CMB2 field patterns.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @return  array
     */
    private static function getOptionsSchema()
    {
        $fieldsList = [
            [
                'before_row' => sprintf(
                    '<h3>%s</h3>',
                    __('General Calendars Settings', 'upstream-calendar-view')
                ),
                'name'       => __('First Day of the week', 'upstream-calendar-view'),
                'id'         => 'first_day',
                'type'       => 'select',
                'desc'       => __('Select the first day of the week on the calendar.', 'upstream-calendar-view'),
                'options'    => [
                    '0' => __('Monday'),
                    '1' => __('Tuesday'),
                    '2' => __('Wednesday'),
                    '3' => __('Thursday'),
                    '4' => __('Friday'),
                    '5' => __('Saturday'),
                    '6' => __('Sunday'),
                ],
                'default'    => '0',
            ],
            [
                'name'    => __('Weeks to be displayed', 'upstream-calendar-view'),
                'id'      => 'weeks_count',
                'type'    => 'select',
                'desc'    => __('Select how many weeks will be displayed by default on the calendar.',
                    'upstream-calendar-view'),
                'options' => [
                    '1'  => sprintf('%d %s', 1, __('week', 'upstream-calendar-view')),
                    '2'  => sprintf('%d %s', 2, __('weeks', 'upstream-calendar-view')),
                    '3'  => sprintf('%d %s', 3, __('weeks', 'upstream-calendar-view')),
                    '4'  => sprintf('%d %s', 4, __('weeks', 'upstream-calendar-view')),
                    '5'  => sprintf('%d %s', 5, __('weeks', 'upstream-calendar-view')),
                    '6'  => sprintf('%d %s', 6, __('weeks', 'upstream-calendar-view')),
                    '7'  => sprintf('%d %s', 7, __('weeks', 'upstream-calendar-view')),
                    '8'  => sprintf('%d %s', 8, __('weeks', 'upstream-calendar-view')),
                    '9'  => sprintf('%d %s', 9, __('weeks', 'upstream-calendar-view')),
                    '10' => sprintf('%d %s', 10, __('weeks', 'upstream-calendar-view')),
                    '11' => sprintf('%d %s', 11, __('weeks', 'upstream-calendar-view')),
                    '12' => sprintf('%d %s', 12, __('weeks', 'upstream-calendar-view')),
                ],
                'default' => '4',
            ],
            [
                'before_row' => sprintf(
                    '<h3>%s</h3>',
                    __('Calendar Overview Settings', 'upstream-calendar-view')
                ),
                'name'       => sprintf(_x('%s Timeframe as Bars', '%s = Custom "Project" label.',
                    'upstream-calendar-view'), upstream_project_label_plural()),
                'id'         => 'overview_projects_timeframes',
                'type'       => 'select',
                'desc'       => sprintf(__('Choose to display all %s dates as bars on the %s.'),
                    upstream_project_label_plural(true), __('Calendar Overview', 'upstream-calendar-view')
                ),
                'options'    => [
                    '0' => __('No, use dates', 'upstream-calendar-view'),
                    '1' => __('Yes, use bars', 'upstream-calendar-view'),
                ],
                'default'    => '0',
            ],
        ];

        return $fieldsList;
    }

    /**
     * Retrieve all plugin options.
     *
     * @since   1.0.0
     * @static
     *
     * @return  array
     */
    public static function getOptions()
    {
        $pluginId   = str_replace('-', '_', UPSTREAM_CALENDAR_VIEW_NAME);
        $optionsMap = (array)get_option($pluginId);

        if (empty($optionsMap) || array_keys($optionsMap) === range(0, count($optionsMap) - 1)) {
            $optionsMap = [];

            $optionsSchema = self::getOptionsSchema();
            foreach ($optionsSchema as $option) {
                $optionsMap[$option['id']] = isset($option['default']) ? $option['default'] : null;
            }
        }

        $optionsMap = json_decode(json_encode($optionsMap));

        return $optionsMap;
    }

    /**
     * Callback called to setup the links to display on the plugins page, besides active/deactivate links.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $links The list of links to be displayed.
     *
     * @return  array
     */
    public static function handleActionLinks($links)
    {
        $links['settings'] = sprintf(
            '<a href="%s" title="%2$s" aria-label="%2$s">%3$s</a>',
            admin_url('admin.php?page=upstream_calendar_view'),
            __('Open Settings Page', 'upstream'),
            __('Settings', 'upstream')
        );

        return $links;
    }

    /**
     * Enqueue all dependent styles.
     *
     * @since   1.0.0
     * @static
     */
    public static function enqueueStyles()
    {
        if ( ! self::canRunOnCurrentPage()) {
            return;
        }

        wp_enqueue_style(UPSTREAM_CALENDAR_VIEW_NAME,
            UP_CALENDAR_VIEW_URL . '/assets/css/' . UPSTREAM_CALENDAR_VIEW_NAME . '.css', [], UP_CALENDAR_VIEW_VERSION,
            "all");
    }

    /**
     * Check if we're not on admin and viewing a project page.
     *
     * @since   1.0.0
     * @static
     *
     * @return  bool
     */
    public static function canRunOnCurrentPage()
    {
        return true;

        // RSD: what was the point of this?

        if ( ! is_admin() && get_post_type() === "project") {
            $user           = wp_get_current_user();
            $userCanProceed = count(array_intersect($user->roles,
                    ['administrator', 'upstream_manager', 'upstream_user', 'upstream_client_user'])) > 0;
            if ( ! $userCanProceed) {
                if ( ! user_can($user, 'edit_published_projects') && ! user_can($user, 'edit_others_projects')) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Enqueue all dependent scripts.
     *
     * @since   1.0.0
     * @static
     */
    public static function enqueueScripts()
    {
        if ( ! self::canRunOnCurrentPage()) {
            return;
        }

        wp_enqueue_script(UPSTREAM_CALENDAR_VIEW_NAME,
            UP_CALENDAR_VIEW_URL . '/assets/js/' . UPSTREAM_CALENDAR_VIEW_NAME . '.js', ['jquery', 'upstream', 'jquery-ui-tooltip'],
            UP_CALENDAR_VIEW_VERSION, "all");
        wp_localize_script(UPSTREAM_CALENDAR_VIEW_NAME, '$data', [
            'ajaxurl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce(UPSTREAM_CALENDAR_VIEW_NAME),
            'project_id' => Calendar::getCurrentProjectId(),
            'isArchive'  => is_archive(),
            'can_add_items' => true,
        ]);
    }

    /**
     * This method concatenates an anchor on the Table of Contents section on the Customizer settings page.
     * Triggered by the "upstream.customizer:settings.table_of_contents" filter.
     *
     * @since   1.0.0
     * @static
     *
     * @param   string $html Table of Contents list inner html.
     *
     * @return  string
     */
    public static function filterCustomizerSectionLink($html)
    {
        $html .= sprintf('
            <li>
              <a href="#upstream-customizer-settings-calendar_view">%s</a>
            </li>',
            __('Calendar View', 'upstream-calendar-view')
        );

        return $html;
    }

    /**
     * This method adds custom fields to the Customizer plugin settings page.
     * Triggered by the "upstream.customizer:settings.table_of_contents" filter.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $fields Array of fields used by Customizer.
     *
     * @return  array
     */
    public static function filterCustomizerFields($fields)
    {
        $descTmplStr = sprintf('%s: <code>%s</code>', __('Default Color', 'upstream-calendar-view'), '%s');

        $fields[] = [
            'before_row' => sprintf('<h3>%s</h3><div id="upstream-customizer-settings-calendar_view">',
                __('Calendar View', 'upstream-calendar-view')),
            'name'       => sprintf('%s > %s', _x('Header', 'Calendar Header', 'upstream-calendar-view'),
                __('Day Name Text Color', 'upstream-calendar-view')),
            'id'         => 'calendar_view__header_text_color',
            'type'       => 'colorpicker',
            'default'    => "#3A4E66",
            'desc'       => sprintf($descTmplStr, '#3A4E66'),
        ];

        $fields[] = [
            'name'    => sprintf('%s > %s', _x('Header', 'Calendar Header', 'upstream-calendar-view'),
                __('Bottom Separator Color', 'upstream-calendar-view')),
            'id'      => 'calendar_view__header_separator_color',
            'type'    => 'colorpicker',
            'default' => "#429EDB",
            'desc'    => sprintf($descTmplStr, '#429EDB'),
        ];

        $fields[] = [
            'name'    => __('Days Border Color', 'upstream-calendar-view'),
            'id'      => 'calendar_view__border_color',
            'type'    => 'colorpicker',
            'default' => "#E1E1E1",
            'desc'    => sprintf($descTmplStr, '#E1E1E1'),
        ];

        $fields[] = [
            'name'    => sprintf('%s > %s', __('Weekdays', 'upstream-calendar-view'),
                __('Background Color', 'upstream-calendar-view')),
            'id'      => 'calendar_view__day_bg_color',
            'type'    => 'colorpicker',
            'default' => "#FFFFFF",
            'desc'    => sprintf($descTmplStr, '#FFFFFF'),
        ];

        $fields[] = [
            'name'    => sprintf('%s > %s', __('Weekdays', 'upstream-calendar-view'),
                __('Text Color', 'upstream-calendar-view')),
            'id'      => 'calendar_view__day_text_color',
            'type'    => 'colorpicker',
            'default' => "#7D828E",
            'desc'    => sprintf($descTmplStr, '#7D828E'),
        ];

        $fields[] = [
            'name'    => sprintf('%s > %s', __('Weekdays', 'upstream-calendar-view'),
                __('Day Number Color', 'upstream-calendar-view')),
            'id'      => 'calendar_view__day_number_color',
            'type'    => 'colorpicker',
            'default' => "#BDC3C7",
            'desc'    => sprintf($descTmplStr, '#BDC3C7'),
        ];

        $fields[] = [
            'name'    => sprintf('%s > %s', __('Weekend', 'upstream-calendar-view'),
                __('Background Color', 'upstream-calendar-view')),
            'id'      => 'calendar_view__wkend_day_bg_color',
            'type'    => 'colorpicker',
            'default' => "#F9F9F9",
            'desc'    => sprintf($descTmplStr, '#F9F9F9'),
        ];

        $fields[] = [
            'name'    => sprintf('%s > %s', __('Weekend', 'upstream-calendar-view'),
                __('Text Color', 'upstream-calendar-view')),
            'id'      => 'calendar_view__wkend_day_text_color',
            'type'    => 'colorpicker',
            'default' => "#BDC3C7",
            'desc'    => sprintf($descTmplStr, '#BDC3C7'),
        ];

        $fields[] = [
            'name'    => sprintf('%s > %s', __('Weekend', 'upstream-calendar-view'),
                __('Day Number Color', 'upstream-calendar-view')),
            'id'      => 'calendar_view__wkend_day_number_color',
            'type'    => 'colorpicker',
            'default' => "#BDC3C7",
            'desc'    => sprintf($descTmplStr, '#BDC3C7'),
        ];

        $fields[] = [
            'name'    => sprintf('%s > %s', __('Current Day', 'upstream-calendar-view'),
                __('Background Color', 'upstream-calendar-view')),
            'id'      => 'calendar_view__today_bg_color',
            'type'    => 'colorpicker',
            'default' => "#F0F6FB",
            'desc'    => sprintf($descTmplStr, '#F0F6FB'),
        ];

        $fields[] = [
            'name'    => sprintf('%s > %s', __('Current Day', 'upstream-calendar-view'),
                __('Text Color', 'upstream-calendar-view')),
            'id'      => 'calendar_view__today_text_color',
            'type'    => 'colorpicker',
            'default' => "#7D828E",
            'desc'    => sprintf($descTmplStr, '#7D828E'),
        ];

        $fields[] = [
            'name'      => sprintf('%s > %s', __('Current Day', 'upstream-calendar-view'),
                __('Day Number Color', 'upstream-calendar-view')),
            'id'        => 'calendar_view__today_number_color',
            'type'      => 'colorpicker',
            'default'   => "#BDC3C7",
            'desc'      => sprintf($descTmplStr, '#BDC3C7'),
            'after_row' => '</div><hr id="upstream-customizer-settings-footer" />',
        ];

        return $fields;
    }

    /**
     * This method renders custom css used by the Customizer plugin.
     * Triggered by the "upstream.customizer:render_styles" filter.
     *
     * @since   1.0.0
     * @static
     *
     * @param   array $fields Array of fields used by Customizer.
     *
     * @return  array
     */
    public static function renderCustomizerStyles()
    {
        $isHexColorValid = function ($hex) {
            preg_match('/#?([a-fA-F0-9]{3}){1,2}\b/', $hex, $matches);

            return is_array($matches) && isset($matches[1]);
        };

        $options = (array)get_option('upstream_customizer');
        if (empty($options)) {
            return;
        }

        $headerTextColor      = isset($options['calendar_view__header_text_color']) ? $options['calendar_view__header_text_color'] : "";
        $headerSeparatorColor = isset($options['calendar_view__header_separator_color']) ? $options['calendar_view__header_separator_color'] : "";
        $dayBgColor           = isset($options['calendar_view__day_bg_color']) ? $options['calendar_view__day_bg_color'] : "";
        $dayTextColor         = isset($options['calendar_view__day_text_color']) ? $options['calendar_view__day_text_color'] : "";
        $daysBorderColor      = isset($options['calendar_view__border_color']) ? $options['calendar_view__border_color'] : "";
        $weekdaysNumberColor  = isset($options['calendar_view__day_number_color']) ? $options['calendar_view__day_number_color'] : "";

        $currentDayBgColor     = isset($options['calendar_view__today_bg_color']) ? $options['calendar_view__today_bg_color'] : "";
        $currentDayTextColor   = isset($options['calendar_view__today_text_color']) ? $options['calendar_view__today_text_color'] : "";
        $currentDayNumberColor = isset($options['calendar_view__today_number_color']) ? $options['calendar_view__today_number_color'] : "";

        $weekdendNumberColor = isset($options['calendar_view__wkend_day_number_color']) ? $options['calendar_view__wkend_day_number_color'] : "";
        $weekdendBgColor     = isset($options['calendar_view__wkend_day_bg_color']) ? $options['calendar_view__wkend_day_bg_color'] : "";
        $weekdendTextColor   = isset($options['calendar_view__wkend_day_text_color']) ? $options['calendar_view__wkend_day_text_color'] : "";
        ?>

        <style>
            .o-calendar .o-calendar-day.is-weekend {
            <?php if ($isHexColorValid($weekdendBgColor)): ?> background-color: <?php echo $weekdendBgColor; ?>;
            <?php endif; ?><?php if ($isHexColorValid($weekdendTextColor)): ?> color: <?php echo $weekdendTextColor; ?>;
            <?php endif; ?>
            }

            <?php if ($isHexColorValid($weekdendNumberColor)): ?>
            .o-calendar .o-calendar-day.is-weekend .o-calendar-day__day {
                color: <?php echo $weekdendNumberColor; ?>;
            }

            <?php endif; ?>

            .o-calendar .o-calendar-week-header {
            <?php if ($isHexColorValid($headerTextColor)): ?> color: <?php echo $headerTextColor; ?>;
            <?php endif; ?><?php if ($isHexColorValid($headerSeparatorColor)): ?> border-bottom-color: <?php echo $headerSeparatorColor; ?>;
            <?php endif; ?>
            }

            .o-calendar .o-calendar-day {
            <?php if ($isHexColorValid($dayBgColor)): ?> background-color: <?php echo $dayBgColor; ?>;
            <?php endif; ?><?php if ($isHexColorValid($dayTextColor)): ?> color: <?php echo $dayTextColor; ?>;
            <?php endif; ?>
            }

            <?php if ($isHexColorValid($weekdaysNumberColor)): ?>
            .o-calendar .o-calendar-day .o-calendar-day__day {
                color: <?php echo $weekdaysNumberColor; ?>;
            }

            <?php endif; ?>

            .o-calendar .o-calendar-day.is-today {
            <?php if ($isHexColorValid($currentDayBgColor)): ?> background-color: <?php echo $currentDayBgColor; ?>;
            <?php endif; ?><?php if ($isHexColorValid($currentDayTextColor)): ?> color: <?php echo $currentDayTextColor; ?>;
            <?php endif; ?>
            }

            <?php if ($isHexColorValid($currentDayNumberColor)): ?>
            .o-calendar .o-calendar-day.is-today .o-calendar-day__day {
                color: <?php echo $currentDayNumberColor; ?>;
            }

            <?php endif; ?>

            <?php if ($isHexColorValid($daysBorderColor)): ?>
            .c-calendar .o-calendar-day + .o-calendar-day {
                border-left-color: <?php echo $daysBorderColor; ?>;
            }

            .o-calendar > tbody > tr:not(.o-calendar-month-separator) + tr {
                border-top-color: <?php echo $daysBorderColor; ?>;
            }

            <?php endif; ?>
        </style>

        <?php
    }

    /**
     * Render an anchor on the sidebar.
     * Callend by "upstream_sidebar_before_single_menu" action.
     *
     * @since   1.0.0
     * @static
     */
    public static function renderSidebarLink()
    {
        if (Plugin::canRunOnCurrentPage()):
            ?>
            <li id="nav-calendar">
                <a href="#calendar">
                    <i class="fa fa-calendar"></i> <?php esc_html_e('Calendar', 'upstream-calendar-view'); ?>
                </a>
            </li>
        <?php
        endif;
    }

    /**
     * Render an anchor on the sidebar in the projects.
     * Callend by "upstream_sidebar_before_single_menu" action.
     *
     * @since   1.0.0
     * @static
     */
    public static function renderSidebarProjectsLink()
    {
        if (Plugin::canRunOnCurrentPage()):
            $link = add_query_arg('view', 'calendar', get_post_type_archive_link('project'));
            $active = isset($_GET['view']) && $_GET['view'] === 'calendar';
            ?>
            <li>
                <a href="<?php echo $link; ?>" <?php echo $active ? 'class="current-page"' : ""; ?>>
                    <i class="fa fa-calendar"></i> <?php esc_html_e('Calendar', 'upstream-calendar-view'); ?>
                </a>
            </li>
        <?php
        endif;
    }
}
