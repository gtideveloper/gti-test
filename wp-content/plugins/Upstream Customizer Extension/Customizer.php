<?php
namespace UpStream\Plugins;

// Prevent direct access.
use Alledia\EDD_SL_Plugin_Updater;

if (!defined('ABSPATH')) exit;

/**
 * Main UpStream Customizer Class.
 *
 * @since 1.0.0
 * @final
 */
final class Customizer
{
    /**
     * A singleton UpStream Customizer class instance.
     *
     * @var     null|\UpStream\Plugins\Customizer $_instance
     *
     * @since   1.0.0
     * @access  protected
     * @static
     */
    protected static $_instance = null;

    /**
     * Current plugin version.
     *
     * @const   string VERSION
     *
     * @since   1.0.0
     */
    const VERSION = UPSTREAM_CUSTOMIZER_VERSION;

    /**
     * @var EDD_SL_Plugin_Updater
     */
    public $updater;


    /**
     * Return the singleton instance. If there's none, it instantiates the singleton first.
     *
     * @since   1.0.0
     * @static
     *
     * @return  \UpStream\Plugins\Customizer
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Prevent the object being cloned.
     *
     * @since   1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, 'Cheatin&#8217; huh?', self::VERSION);
    }

    /**
     * Prevent the class being unserialized.
     *
     * @since   1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, 'Cheatin&#8217; huh?', self::VERSION);
    }

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

        do_action('upstream_customizer_loaded');
    }

    /**
     * Hook into actions and filters.
     *
     * @since   1.0.0
     * @access  private
     */
    private function init_hooks()
    {
        add_action('upstream_init', array($this, 'init'), 0);
        add_action('upstream_init', array('\UpStream\Plugins\Customizer\Options', 'instance'));
        add_action('upstream_init', array('\UpStream\Plugins\Customizer\Output', 'instance'));
        add_filter('plugin_action_links_upstream-customizer/upstream-customizer.php', array(get_class($this), 'handleActionLinks'));
        add_action( 'admin_init', [ $this, 'initUpdater' ] );
    }

    /**
     * Define constants.
     *
     * @since   1.0.0
     * @access  private
     */
    private function define_constants()
    {
        $this->define('UP_CUSTOMIZER_PLUGIN_NAME', 'upstream-customizer');
        $this->define('UP_CUSTOMIZER_TITLE', 'Customizer');
        $this->define('UP_CUSTOMIZER_SLUG', 'customizer');
        $this->define('UP_CUSTOMIZER_PLUGIN_FILE', dirname(__FILE__) .'/'. UP_CUSTOMIZER_PLUGIN_NAME .'.php');
        $this->define('UP_CUSTOMIZER_PLUGIN_DIR', plugin_dir_path(UP_CUSTOMIZER_PLUGIN_FILE));
        $this->define('UP_CUSTOMIZER_PLUGIN_URL', plugin_dir_url(UP_CUSTOMIZER_PLUGIN_FILE));
        $this->define('UP_CUSTOMIZER_PLUGIN_BASENAME', plugin_basename(UP_CUSTOMIZER_PLUGIN_FILE));
        $this->define('UP_CUSTOMIZER_VERSION', self::VERSION);
        $this->define('UP_CUSTOMIZER_API_URL', "https://upstreamplugin.com");
        $this->define('UP_CUSTOMIZER_ID', 4051);
    }

    /**
     * Initialize EDD updater.
     */
    public function initUpdater() {
        $this->updater = new EDD_SL_Plugin_Updater(
            UP_CUSTOMIZER_API_URL,
            UP_CUSTOMIZER_PLUGIN_FILE,
            [
                'version' => UPSTREAM_CUSTOMIZER_VERSION,
                'license' => get_option( 'upstream_customizer_license_key' ),
                'item_id' => UP_CUSTOMIZER_ID,
                'author'  => 'UpStream',
                'beta'    => false,
            ]
        );
    }

    /**
     * Define a given constant if not already set.
     *
     * @since   1.0.0
     * @access  private
     *
     * @param   string  $identifier    The constant identifier.
     * @param   mixed   $value         The constant value.
     */
    private function define($identifier, $value)
    {
        if (is_string($identifier) && !defined($identifier)) {
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
        $filesList = array(
            'includes/class-up-customizer-options.php',
            'includes/class-up-customizer-output.php',
            'includes/trait-up-singleton.php',
        );

        foreach ($filesList as $file) {
            include_once $file;
        }
    }


    /**
     * Load UpStream Customizer when WordPress initialises.
     *
     * @since   1.0.0
     */
    public function init()
    {
        // Before init action.
        do_action('before_upstream_customizer_init');

        $this->loadTextDomain();

        // Init action.
        do_action('upstream_customizer_init');
    }

    /**
     * Callback called to setup the links to display on the plugins page, besides active/deactivate links.
     *
     * @since   1.1.1
     * @static
     *
     * @param   array   $links  The list of links to be displayed.
     *
     * @return  array
     */
    public static function handleActionLinks($links)
    {
        $links['settings'] = sprintf(
            '<a href="%s" title="%2$s" aria-label="%2$s">%3$s</a>',
            admin_url('admin.php?page=upstream_customizer'),
            __('Open Settings Page', 'upstream'),
            __('Settings', 'upstream')
        );

        return $links;
    }

    /**
     * Load Localisation files.
     *
     * @since   1.1.2
     */
    public function loadTextDomain()
    {
        $alias = 'upstream-customizer';
        $locale = apply_filters('plugin_locale', get_locale(), $alias);

        load_textdomain($alias, WP_LANG_DIR .'/'. $alias .'/'. $alias .'-'. $locale .'.mo');

        load_plugin_textdomain($alias, false, plugin_basename(dirname(__FILE__)) .'/languages');
    }

    /**
     * Method called once the plugin is activated.
     * If there's a dependency missing, the plugin is deactivated.
     *
     * @since   1.1.5
     * @static
     *
     * @uses    wp_die()
     */
    public static function activationCallback()
    {
        global $wp_version;
        if (version_compare($wp_version, '4.5', '<')) {
            self::forcePluginDeactivation();

            $errorMessage = '<p>' . sprintf(
                _x('It seems you are using an outdated version of WordPress (%s).', '%s: Current WordPress running version', 'upstream-customizer') . '<br>' .
                __('For security reasons please update your installation.', 'upstream-customizer') . '<br>' .
                _x('The <i>%s</i> requires WordPress version 4.5 or later.', '%s: Plugin name', 'upstream-customizer'),
                $wp_version,
                sprintf('%s - %s', __('UpStream', 'upstream'), __('UpStream Customizer', 'upstream-customizer'))
            ) . '</p>';

            self::dieWithError($errorMessage);
        }

        // Check the PHP version.
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            $errorMessage = sprintf(
                '<p>%s</p><p>%s</p>',
                __('For security reasons, this plugin requires <strong>PHP version 5.6</strong> or <strong>greater</strong> to run.', 'upstream-customizer'),
                __('Please, update your PHP (currently at', 'upstream-customizer') . ' ' . PHP_VERSION .').'
            );

            self::dieWithError($errorMessage);
        }
    }

    /**
     * Calls wp_die() function with a custom message.
     *
     * @since   1.1.5
     * @access  private
     * @static
     *
     * @param   string  $message    The message to be displayed.
     */
    private static function dieWithError($message = "")
    {
        $message = '<h1>' . __('UpStream Customizer', 'upstream-customizer') . '</h1><br/>' . $message;

        $message .= sprintf('<br /><br />
            <a class="button" href="javascript:history.back();" title="%s">%s</a>',
            __('Go back to the plugins page.', 'upstream-customizer'),
            __('Go back', 'upstream-customizer')
        );

        wp_die($message);
    }
}
