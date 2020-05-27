<?php

namespace UpStream\Plugins\Customizer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main UpStream Customizer Output Class.
 *
 * @since 1.0.0
 * @final
 */
final class Output {
    /**
     * A \UpStream\Plugins\Customizer\Output singleton instance.
     *
     * @var     \UpStream\Plugins\Customizer\Output
     *
     * @since   1.0.0
     * @access  protected
     * @static
     */
    protected static $_instance = null;
    /**
     * Plugin options.
     *
     * @var     array
     *
     * @since   1.0.0
     * @access  private
     */
    private $options = [];

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct() {
        $this->init_hooks();
        $this->options = (array) get_option( 'upstream_customizer' );
    }

    /**
     * Hook into actions and filters.
     *
     * @since   1.0.0
     * @access  private
     */
    private function init_hooks() {
        add_action( 'upstream_head', [ $this, 'custom_styles' ] );
        add_filter( 'upstream_footer_text', [ $this, 'footer_text' ] );
        add_filter( 'upstream_additional_nav_content', [ $this, 'nav_content' ] );
        add_filter( 'upstream_logout_url', [ $this, 'logout_url' ] );
    }

    /**
     * Return the singleton instance. If there's none, it instantiates the singleton first.
     *
     * @since   1.0.0
     * @static
     *
     * @return  \UpStream\Plugins\Customizer\Output
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Return the Footer Text option.
     *
     * @since   1.0.0
     *
     * @return  string
     */
    public function footer_text() {
        $footerText = $this->getOption( 'footer_text' );

        return $footerText;
    }

    public function logout_url($orig_url) {
        if (!empty($this->getOption('logout_url'))) {
            return $this->getOption('logout_url');
        }
        return $orig_url;
    }

    public function nav_content() {
        $nav_content = $this->getOptionNoEscape( 'additional_nav_html' );

        return $nav_content;
    }


    /**
     * Retrieve a given option content by its key.
     * If the key doesn't exist, this method will return $defaultValue.
     *
     * @since   1.0.0
     * @access  private
     *
     * @param   string  The option key to be retrieved.
     * @param   string  Default value in case $key doesn't exist.
     *
     * @return  string
     */
    private function getOption( $key, $defaultValue = '' ) {
        $value = ( isset( $this->options[ $key ] ) && $this->options[ $key ] != '' ) ? (string) $this->options[ $key ] : $defaultValue;

        return esc_html( $value );
    }

    /**
     * Retrieve a given option content by its key.
     * If the key doesn't exist, this method will return $defaultValue.
     *
     * @since   1.0.0
     * @access  private
     *
     * @param   string  The option key to be retrieved.
     * @param   string  Default value in case $key doesn't exist.
     *
     * @return  string
     */
    private function getOptionNoEscape( $key, $defaultValue = '' ) {
        $value = ( isset( $this->options[ $key ] ) && $this->options[ $key ] != '' ) ? (string) $this->options[ $key ] : $defaultValue;

        return $value;
    }


    /**
     * Echoes the custom styles.
     *
     * @since  1.0.0
     */
    public function custom_styles() {
        $logo = $this->getOption( 'logo' );
        if ( ! empty( $logo ) ) {
            $logoWidth  = $this->getOption( 'logo_width', '230px' );
            $logoHeight = $this->getOption( 'logo_height', '50px' );
            $marginLeft = round( (int) $logoWidth / 2 ) . 'px';

            $logoCss = '.upstream-front-end .nav_title {position: relative; height: ' . $logoHeight . '; margin-bottom: 10px;}' .
                       '.site_title {' .
                       'position: absolute;' .
                       'left: 50%;' .
                       'margin-left: -' . $marginLeft . ' !important;' .
                       'background-image: url(' . $logo . ');' .
                       'background-size: ' . $logoWidth . ' ' . $logoHeight . ';' .
                       'background-repeat: no-repeat;' .
                       'width: ' . $logoWidth . ';' .
                       'height: ' . $logoHeight . ';' .
                       '} .site_title span { display: none; } body.nav-sm .navbar.nav_title > .site_title { display: none; }';
        } else {
            $logoCss = "";
        }
        ?>

        <style>
            <?php echo $logoCss; ?>

            /* text color */
            body, table.dataTable.dtr-inline.collapsed > tbody > tr > td:first-child::before, table.dataTable.dtr-inline.collapsed > tbody > tr > th:first-child::before, a:focus, a:hover, .nav.child_menu li li a:hover, .nav.side-menu > li > a:hover, .pagination > .active > a, .pagination > .active > a:focus, .pagination > .active > a:hover, .pagination > .active > span, .pagination > .active > span:focus, .pagination > .active > span:hover, .profile_info p, a.btn {
                color: <?php echo $this->getOption('text_color'); ?>;
            }

            /* heading color */
            table thead, .x_title h2, .profile_info h2, .details-panel p.title, .fn-label, a, .sidebar-footer a, .nav.side-menu > li > a, .menu_section h3, .navbar-brand, .navbar-nav > li > a, .site_title, .pagination > li > a, .pagination > li > span, .btn-default, .panel_toolbox > li > a:hover, .text-info, .dropdown-menu > li > a {
                color: <?php echo $this->getOption('heading_color'); ?>;
            }

            /* lines & borders color */
            .table-bordered > tbody > tr > td, .table-bordered > tbody > tr > th, .table-bordered > tfoot > tr > td, .table-bordered > tfoot > tr > th, .table-bordered > thead > tr > td, .table-bordered > thead > tr > th, .table-bordered, div.dataTables_wrapper div.dataTables_filter input, .form-control, .btn-default, .pagination > .disabled > a, .pagination > .disabled > a:focus, .pagination > .disabled > a:hover, .pagination > .disabled > span, .pagination > .disabled > span:focus, .pagination > .disabled > span:hover, .x_title {
                border-color: <?php echo $this->getOption('lines_borders_color'); ?>;
            }

            <?php $highlightColor = $this->getOption('highlight_color'); ?>
            /* highlight color  */
            .nav.side-menu > li.active, .nav.side-menu > li.current-page {
                border-color: <?php echo $highlightColor; ?>;
            }

            .nav li li.current-page a, .nav.child_menu li li a.active {
                color: <?php echo $highlightColor; ?>;
            }

            <?php $buttonBackground = $this->getOption('button_background'); ?>
            /* button colors  */
            .btn-primary {
                border-color: <?php echo $buttonBackground; ?>;
                background-color: <?php echo $buttonBackground; ?>;
                color: <?php echo $this->getOption('button_font_color'); ?>;
            }

            .btn-primary:hover,
            .btn-primary:active,
            .btn-primary:focus {
                background-color: <?php echo $this->getOption('button_background_hover'); ?>;
                color: <?php echo $this->getOption('button_font_color_hover'); ?>;
            }

            <?php $secondaryButtonBackgroundColor = $this->getOption('secondary_button_background_color'); ?>
            <?php $secondaryButtonFontColor = $this->getOption('secondary_button_font_color'); ?>
            .btn-default {
                border-color: <?php echo $secondaryButtonBackgroundColor; ?>;
                background-color: <?php echo $secondaryButtonBackgroundColor; ?>;
                color: <?php echo $secondaryButtonFontColor; ?>;
            }

            .btn-default:hover,
            .btn-default:active,
            .btn-default:focus {
                background-color: <?php echo $this->getOption('secondary_button_hover_background_color'); ?>;
                color: <?php echo $this->getOption('secondary_button_hover_font_color'); ?>;
            }

            .pagination > .paginate_button > a,
            .pagination > .paginate_button > a:active,
            .pagination > .paginate_button > a:hover,
            .pagination > .paginate_button > a:focus {
                border-color: <?php echo $secondaryButtonBackgroundColor; ?>;
                background-color: <?php echo $secondaryButtonBackgroundColor; ?>;
                color: <?php echo $secondaryButtonFontColor; ?>;
            }

            .pagination > .paginate_button.disabled {
                opacity: 0.65;
            }

            /* sidebar background */
            body, .left_col, .nav_title, .sidebar-footer a:hover {
                background-color: <?php echo $this->getOption('sidebar_background'); ?>;
            }

            /* page background */
            body .container.body .right_col {
                background-color: <?php echo $this->getOption('page_background'); ?>;
            }

            /* sidebar link */
            .nav.side-menu > li > a, .nav.child_menu > li > a {
                color: <?php echo $this->getOption('sidebar_link_color'); ?>;
            }

            .nav.side-menu > li > a:hover, .nav.child_menu > li > a:hover {
                color: <?php echo $this->getOption('sidebar_link_hover_color'); ?>;
            }

            /* sidebar bottom icons `*/
            .sidebar-footer a {
                color: <?php echo $this->getOption('sidebar_bottom_icons_color'); ?>;
            }

            /* panel colors `*/
            .x_panel {
                background-color: <?php echo $this->getOption('panel_background'); ?>;
            }

            .x_title h2 {
                color: <?php echo $this->getOption('panel_heading_color'); ?>;
            }

            <?php echo $this->getOptionNoEscape('custom_css'); ?>

            <?php $headerBgColor = $this->getOption('header_color'); ?>
            body .main_container .top_nav .nav_menu,
            body .main_container .top_nav .nav_menu ul.navbar-nav li.open a.dropdown-toggle,
            body .main_container .top_nav .nav_menu ul.navbar-nav li a.dropdown-toggle:hover,
            body .main_container .top_nav .nav_menu ul.navbar-nav li a.dropdown-toggle:active,
            body .main_container .top_nav .nav_menu ul.navbar-nav li a.dropdown-toggle:focus,
            body .main_container .top_nav .nav_menu ul.navbar-nav li.open ul.dropdown-menu > li > a:hover,
            body .main_container .top_nav .nav_menu ul.navbar-nav li.open ul.dropdown-menu > li > a:active,
            body .main_container .top_nav .nav_menu ul.navbar-nav li.open ul.dropdown-menu > li > a:focus {
                background-color: <?php echo $headerBgColor; ?>;
            }

            <?php $headerMenuItemBgColor = $this->getOption('header_menu_item_color'); ?>
            body .main_container .top_nav .nav_menu ul.navbar-nav li.open ul.dropdown-menu {
                background-color: <?php echo $headerMenuItemBgColor; ?>;
            }

            <?php $panelItemsOddBgColor = $this->getOption('panel_items_odd_bg_color'); ?>
            .o-data-table > tbody > tr.t-row-odd,
            .c-comments > .o-comment:nth-child(odd) {
                background-color: <?php echo $panelItemsOddBgColor; ?>;
            }

            <?php $panelItemsEvenBgColor = $this->getOption('panel_items_even_bg_color'); ?>
            .o-data-table > tbody > tr.t-row-even,
            .c-comments > .o-comment:nth-child(even) {
                background-color: <?php echo $panelItemsEvenBgColor; ?>;
            }
        </style>

        <script>
            <?php echo $this->getOptionNoEscape('custom_js'); ?>
        </script>

        <?php
        do_action( 'upstream.customizer:render_styles' );
    }
}
