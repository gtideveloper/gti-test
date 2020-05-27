<?php

namespace UpStream\Plugins;

// Prevent direct access.
use Alledia\EDD_SL_Plugin_Updater;

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Main UpStream Frontend-Edit class.
 *
 * @package     UpStream\Plugins
 * @subpackage  FrontendEdit
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class FrontendEdit
{
    /**
     * Current plugin version.
     *
     * @const   string VERSION
     *
     * @since   1.0.0
     */
    const VERSION = UPSTREAM_FRONTEND_EDIT_VERSION;

    /**
     * Plugin name alias.
     *
     * @const   string ALIAS
     *
     * @since   1.1.0
     */
    const ALIAS = 'upstream-frontend-edit';

    /**
     * A singleton UpStream FrontendEdit class instance.
     *
     * @var     null|Upstream\Plugins\FrontendEdit $_instance
     *
     * @since   1.0.0
     * @access  protected
     * @static
     */
    protected static $_instance = null;

    /**
     * @var EDD_SL_Plugin_Updater
     */
    protected $updater;

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();

        if (version_compare(\UPSTREAM_VERSION, '1.8.0', '<=')) {
            add_action('upstream_frontend_projects_messages',
                ['\UpStream\Plugins\FrontendEdit', 'displayUpStreamUpdateWarning']);
        }

        do_action('upstream_frontend_edit_loaded');
    }

    /**
     * Define constants.
     *
     * @since   1.0.0
     * @access  private
     */
    private function define_constants()
    {
        $this->define('UP_FRONTEND_NAME', self::ALIAS);
        $this->define('UP_FRONTEND_EDIT_API_URL', 'https://upstreamplugin.com/');
        $this->define('UP_FRONTEND_SLUG', 'frontend-edit');
        $this->define('UP_FRONTEND_TITLE', 'Frontend Edit');
        $this->define('UP_FRONTEND_PLUGIN_FILE', dirname(__FILE__) . '/' . UP_FRONTEND_NAME . '.php');
        $this->define('UP_FRONTEND_PLUGIN_DIR', plugin_dir_path(UP_FRONTEND_PLUGIN_FILE));
        $this->define('UP_FRONTEND_PLUGIN_URL', plugin_dir_url(UP_FRONTEND_PLUGIN_FILE));
        $this->define('UP_FRONTEND_PLUGIN_BASENAME', plugin_basename(UP_FRONTEND_PLUGIN_FILE));
        $this->define('UP_FRONTEND_VERSION', self::VERSION);
        $this->define('UP_FRONTEND_ID', 3925);
    }

    /**
     * Define a given constant if not already set.
     *
     * @param string $identifier The constant identifier.
     * @param mixed  $value      The constant value.
     *
     * @since   1.0.0
     * @access  private
     *
     */
    private function define($identifier, $value)
    {
        if (is_string($identifier) && ! defined($identifier)) {
            define($identifier, $value);
        }
    }

    /**
     * Include required files.
     *
     * @since   1.0.0
     * @access  private
     */
    private function includes()
    {
        $filesList = [
            'includes/class-up-frontend-output.php',
            'includes/class-up-frontend-process.php',
            'includes/up-frontend-edit-functions.php',
            'includes/trait-up-singleton.php',
        ];

        foreach ($filesList as $file) {
            include_once $file;
        }
    }

    /**
     * Hook into actions and filters.
     *
     * @since   1.0.0
     * @access  private
     */
    private function init_hooks()
    {
        add_action('upstream_init', [$this, 'init'], 0);
        add_action('admin_init', [$this, 'initUpdater']);

        if ( ! is_admin() || defined('DOING_AJAX')) {
            add_action('upstream_init', ['UpStream_Frontend_Output', 'instance']);
            add_action('upstream_init', ['UpStream_Frontend_Process', 'instance']);
        }

        // Register an action to render controls links for project comments.
        add_action('upstream:project.comments.comment_controls',
            ['UpStream_Frontend_Output', 'insertCommentControls'], 10, 1);
    }

    /**
     * Return the singleton instance. If there's none, it instantiates the singleton first.
     *
     * @return  Upstream\Plugins\FrontendEdit
     * @since   1.0.0
     * @static
     *
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Display an update available warning.
     *
     * @since   1.1.2
     * @static
     */
    public static function displayUpStreamUpdateWarning()
    {
        ?>
        <div class="alert alert-warning" role="alert">
            <strong><?php _e('Warning!', 'upstream-frontend-edit'); ?></strong> <br/>
            <?php echo sprintf(
                __('There is a new version of %s available.', 'upstream-frontend-edit'),
                '<a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/plugins/upstream/#developers"><strong>' . __('UpStream',
                    'upstream') . '</strong></a>'
            ); ?> <br/>
            <?php printf(__("Please, update now in order to make sure <strong>%s</strong> plugin is working correctly and you're using the latest released features.",
                'upstream-frontend-edit'), 'Frontend Edit'); ?>
        </div>
        <?php
    }

    /**
     * Method called once the plugin is deactivated.
     *
     * @since   1.3.0
     * @static
     */
    public static function onPluginDeactivation()
    {

    }

    /**
     * Initialize EDD updater.
     */
    public function initUpdater()
    {
        $this->updater = new EDD_SL_Plugin_Updater(
            UP_FRONTEND_EDIT_API_URL,
            UP_FRONTEND_PLUGIN_FILE,
            [
                'version' => UPSTREAM_FRONTEND_EDIT_VERSION,
                'license' => get_option('upstream_frontend_edit_license_key'),
                'item_id' => UP_FRONTEND_ID,
                'author'  => 'UpStream',
                'beta'    => false,
            ]
        );
    }

    /**
     * Prevent the object being cloned.
     *
     * @since   1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', self::ALIAS), self::VERSION);
    }

    /**
     * Prevent the class being unserialized.
     *
     * @since   1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', self::ALIAS), self::VERSION);
    }

    /**
     * Load UpStream FrontendEdit when WordPress initialises.
     *
     * @since   1.0.0
     */
    public function init()
    {
        // Before init action.
        do_action('before_upstream_frontend_edit_init');

        // Set up localisation.
        $this->load_plugin_textdomain();

        // Init action.
        do_action('upstream_frontend_edit_init');
    }

    /**
     * Load Localisation files.
     *
     * @since   1.0.0
     */
    public function load_plugin_textdomain()
    {
        $locale = apply_filters('plugin_locale', get_locale(), self::ALIAS);

        load_textdomain(self::ALIAS, WP_LANG_DIR . '/' . self::ALIAS . '/' . self::ALIAS . '-' . $locale . '.mo');

        load_plugin_textdomain(self::ALIAS, false, plugin_basename(dirname(__FILE__)) . '/languages');
    }
}
