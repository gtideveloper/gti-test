<?php
namespace UpStream\Plugins\Customizer;

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

/**
 * Main UpStream Customizer Options Class.
 *
 * @since 1.0.0
 * @final
 */
final class Options
{
    /**
     * ID of the metabox.
     *
     * @var     array $id
     *
     * @since   1.0.0
     */
    public $id = 'upstream_customizer';

    /**
     * Page title.
     *
     * @var     string $pageTitle
     *
     * @since   1.0.0
     * @access  protected
     */
    protected $pageTitle = '';

    /**
     * Menu Title.
     *
     * @var     string $menuTitle
     *
     * @since   1.0.0
     * @access  protected
     */
    protected $menuTitle = '';

    /**
     * Page description.
     *
     * @var     string $pageDescription
     *
     * @since   1.0.0
     * @access  protected
     */
    protected $pageDescription = '';

    /**
     * A singleton UpStream Customizer class instance.
     *
     * @var     null|\UpStream\Plugins\Customizer\Options $_instance
     *
     * @since   1.0.0
     * @access  protected
     * @static
     */
    protected static $_instance = null;

    /**
     * Return the singleton instance. If there's none, it instantiates the singleton first.
     *
     * @since   1.0.0
     * @static
     *
     * @return  \UpStream\Plugins\Customizer\Options
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        $title = __('Customizer', 'upstream-customizer');

        // Set page title.
        $this->pageTitle = $title;
        // Set menu title.
        $this->menuTitle = $title;

        // Hook into actions and filters.
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters.
     *
     * @since   1.0.0
     * @access  private
     */
    private function init_hooks()
    {
        add_filter('upstream_option_metaboxes', array($this, 'customizer_options'), 1);
    }

    /**
     * Load UpStream Customizer when WordPress initialises.
     *
     * @since   1.0.0
     */
    public function init()
    {
        register_setting($this->id, $this->id);
    }

    /**
     * Return all options fields schema.
     *
     * @since   1.0.0
     *
     * @return  array
     */
    public function customizer_fields()
    {
        $fields = array();

        // Table of Contents + Logo > URL
        $fields[] = array(
            'before_row' => sprintf('
                <h3>%s</h3>
                <div class="cmb-inline">
                    <ul>%s</ul>
                </div>
                <hr id="upstream-customizer-settings-logo" />
                <h3>%s</h3>',
                __('Table of Contents', 'upstream-customizer'),
                apply_filters('upstream.customizer:settings.table_of_contents', sprintf(
                    '<li><a href="#upstream-customizer-settings-logo">%s</a></li>
                    <li><a href="#upstream-customizer-settings-general">%s</a></li>
                    <li><a href="#upstream-customizer-settings-panels">%s</a></li>
                    <li><a href="#upstream-customizer-settings-sidebar">%s</a></li>
                    <li><a href="#upstream-customizer-settings-primary-buttons">%s</a></li>
                    <li><a href="#upstream-customizer-settings-secondary-buttons">%s</a></li>
                    <li><a href="#upstream-customizer-settings-headers">%s</a></li>
                    <li><a href="#upstream-customizer-settings-footer">%s</a></li>
                    <li><a href="#upstream-customizer-settings-custom">%s</a></li>',
                    __('Logo', 'upstream-customizer'),
                    __('General Styles', 'upstream-customizer'),
                    __('Panels', 'upstream-customizer'),
                    __('Sidebar', 'upstream-customizer'),
                    __('Primary Buttons', 'upstream-customizer'),
                    __('Secondary Buttons', 'upstream-customizer'),
                    __('Headers & Heading', 'upstream-customizer'),
                    __('Footer', 'upstream-customizer'),
                    __('Advanced (admin only)', 'upstream-customizer')
                )),
                __('Logo', 'upstream-customizer')
            ),
            'name' => 'URL',
            'id' => 'logo',
            'type' => 'file'
        );

        // Logo > Width
        $fields[] = array(
            'name' => __('Width', 'upstream-customizer'),
            'id' => 'logo_width',
            'type' => 'text',
            'desc' => __('Logo width in pixels, up to a maximum of 230px. Include px on the end of number.', 'upstream-customizer'),
            'attributes' => array(
                'placeholder' => '230px'
            )
        );

        // Logo > Height
        $fields[] = array(
            'name' => __('Height', 'upstream-customizer'),
            'id' => 'logo_height',
            'type' => 'text',
            'desc' => __('Logo height in pixels. Include px on the end of number.', 'upstream-customizer'),
            'attributes' => array(
                'placeholder' => '50px',
            ),
            'after_row' => '<hr id="upstream-customizer-settings-general" />'
        );

        // General Styles > Default Text Color
        $fields[] = array(
            'before_row' => sprintf('<h3>%s</h3>', __('General Styles', 'upstream-customizer')),
            'name' => __('Default Text Color', 'upstream-customizer'),
            'id' => 'text_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Lines & Borders Color', 'upstream-customizer'),
            'id' => 'lines_borders_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Page Background Color', 'upstream-customizer'),
            'id' => 'page_background',
            'type' => 'colorpicker',
            'after_row' => '<hr id="upstream-customizer-settings-panels" />'
        );

        $fields[] = array(
            'before_row' => sprintf('<h3>%s</h3>', __('Panels', 'upstream-customizer')),
            'name' => __('Background Color', 'upstream-customizer'),
            'id' => 'panel_background',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Heading Text Color', 'upstream-customizer'),
            'id' => 'panel_heading_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Odd Items Background Color', 'upstream-customizer'),
            'id' => 'panel_items_odd_bg_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Even Items Background Color', 'upstream-customizer'),
            'id' => 'panel_items_even_bg_color',
            'type' => 'colorpicker',
            'after_row' => '<hr id="upstream-customizer-settings-sidebar" />'
        );

        $fields[] = array(
            'before_row' => sprintf('<h3>%s</h3>', __('Sidebar', 'upstream-customizer')),
            'name' => __('Background Color', 'upstream-customizer'),
            'id' => 'sidebar_background',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Links Color', 'upstream-customizer'),
            'id' => 'sidebar_link_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Links Hover Color', 'upstream-customizer'),
            'id' => 'sidebar_link_hover_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Highlighted Link Color', 'upstream-customizer'),
            'id' => 'highlight_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Bottom Links Icon Color', 'upstream-customizer'),
            'id' => 'sidebar_bottom_icons_color',
            'type' => 'colorpicker',
            'after_row' => '<hr id="upstream-customizer-settings-primary-buttons" />'
        );

        $fields[] = array(
            'before_row' => sprintf('<h3>%s</h3>', __('Primary Buttons', 'upstream-customizer')),
            'name' => __('Background Color', 'upstream-customizer'),
            'id' => 'button_background',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Font Color', 'upstream-customizer'),
            'id' => 'button_font_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Background Hover', 'upstream-customizer'),
            'id' => 'button_background_hover',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Font Color Hover', 'upstream-customizer'),
            'id' => 'button_font_color_hover',
            'type' => 'colorpicker',
            'after_row' => '<hr id="upstream-customizer-settings-secondary-buttons" />'
        );

        $fields[] = array(
            'before_row' => sprintf('<h3>%s</h3>', __('Secondary Buttons', 'upstream-customizer')),
            'name' => __('Background Color', 'upstream-customizer'),
            'id' => 'secondary_button_background_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Font Color', 'upstream-customizer'),
            'id' => 'secondary_button_font_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Hover Background Color', 'upstream-customizer'),
            'id' => 'secondary_button_hover_background_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Hover Font Color', 'upstream-customizer'),
            'id' => 'secondary_button_hover_font_color',
            'type' => 'colorpicker',
            'after_row' => '<hr id="upstream-customizer-settings-headers" />'
        );

        $fields[] = array(
            'before_row' => sprintf('<h3>%s</h3>', __('Headers & Heading', 'upstream-customizer')),
            'name' => __('Header Background Color', 'upstream-customizer'),
            'id' => 'header_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Header Menu Item Background Color', 'upstream-customizer'),
            'id' => 'header_menu_item_color',
            'type' => 'colorpicker'
        );

        $fields[] = array(
            'name' => __('Text Color', 'upstream-customizer'),
            'id' => 'heading_color',
            'type' => 'colorpicker',
            'after_row' => '<hr id="upstream-customizer-settings-footer" />'
        );

        $fields[] = array(
            'before_row' => sprintf('<h3>%s</h3>', __('Footer', 'upstream-customizer')),
            'name' => __('Footer Text', 'upstream-customizer'),
            'id' => 'footer_text',
            'type' => 'text',
            'after_row' => '<hr id="upstream-customizer-settings-custom" />'
        );

        $fields = apply_filters('upstream.customizer:settings.fields', $fields);

        if (current_user_can('administrator')) {

            $fields[] = array(
                'before_row' => sprintf('<h3>%s</h3><h4>%s</h4>', __('Advanced (admin only)', 'upstream-customizer'), __('Only Administrators can edit these fields. NOTE: These can be dangerous! Use at your own risk!', 'upstream-customizer')),
                'name' => __('Custom CSS', 'upstream-customizer'),
                'id' => 'custom_css',
                'type' => 'textarea_code'
            );

            $fields[] = array(
                'name' => __('Custom JS', 'upstream-customizer'),
                'id' => 'custom_js',
                'type' => 'textarea_code'
            );

            $fields[] = array(
                'name' => __('Additional Nav Content (HTML)', 'upstream-customizer'),
                'id' => 'additional_nav_html',
                'type' => 'textarea_code'
            );

            $fields[] = array(
                'name' => __('Logout URL', 'upstream-customizer'),
                'id' => 'logout_url',
                'type' => 'text'
            );

        }

        return $fields;
    }

    /**
     * Callback called by the `upstream_option_metaboxes` event.
     * It sets up all new custom options.
     *
     * @since   1.0.0
     *
     * @return  array
     */
    public function customizer_options($options)
    {
        $options[] = apply_filters($this->id . '_option_fields', array(
            'id'         => $this->id,
            'title'      => $this->pageTitle,
            'menu_title' => $this->menuTitle,
            'desc'       => $this->pageDescription,
            'show_on'    => array(
                'key'   => 'options-page',
                'value' => array($this->id)
            ),
            'show_names' => true,
            'fields'     => $this->customizer_fields()
            )
        );

        return $options;
    }
}
