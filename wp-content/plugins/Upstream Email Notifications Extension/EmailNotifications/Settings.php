<?php

namespace UpStream\Plugins\EmailNotifications;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\EmailNotifications\Traits\Singleton;

/**
 * Class responsible for handling all plugin options, from rendering
 * the Settings Page to retrieving saved data.
 *
 * @package     UpStream\Plugins\EmailNotifications
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 *
 * @uses        Singleton
 */
final class Settings
{
    use Singleton;

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        $this->attachHooks();
    }

    /**
     * Attach the settings actions with WordPress.
     *
     * @since   1.0.0
     * @access  private
     *
     * @see     https://developer.wordpress.org/reference/functions/add_action
     * @see     https://developer.wordpress.org/reference/functions/add_filter
     */
    private function attachHooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_filter('upstream_option_metaboxes', [$this, 'initialize'], 1);
        add_action('wp_ajax_upstream_email_notifications_send_test_email', [$this, 'sendTestEmail']);

        $namespace = get_class($this);
        add_action('cmb2_render_password_clean', [$namespace, 'render_password_field'], 10, 5);
        add_action('cmb2_sanitize_password_clean', [$namespace, 'sanitize_password_field'], 10, 5);
    }

    /**
     * Enqueue all script dependencies.
     *
     * @since   1.0.0
     * @static
     *
     * @see     https://developer.wordpress.org/reference/functions/wp_enqueue_script
     */
    public static function enqueueScripts()
    {
        $isAdmin = is_admin();
        if ( ! $isAdmin) {
            return;
        }

        global $pagenow;
        if ($pagenow === 'admin.php') {
            if ( ! isset($_GET['page'])
                 || $_GET['page'] !== UPSTREAM_EMAIL_NOTIFICATIONS_IDENTIFIER
            ) {
                return;
            }

            wp_enqueue_style('upstream-email-notifications-settings',
                plugins_url(UPSTREAM_EMAIL_NOTIFICATIONS_NAME . '/assets/css/page-settings.css'));
        } elseif ($pagenow === 'post.php') {
            $postType = get_post_type();
            if ($postType !== 'project') {
                return;
            }
        } else {
            return;
        }

        $scriptURL = plugins_url(UPSTREAM_EMAIL_NOTIFICATIONS_NAME) . '/assets/js/' . UPSTREAM_EMAIL_NOTIFICATIONS_NAME . '.js';

        wp_enqueue_script(UPSTREAM_EMAIL_NOTIFICATIONS_NAME, $scriptURL, ['jquery'],
            UPSTREAM_EMAIL_NOTIFICATIONS_VERSION, true);

        wp_enqueue_style(UPSTREAM_EMAIL_NOTIFICATIONS_NAME,
            plugins_url(UPSTREAM_EMAIL_NOTIFICATIONS_NAME . '/assets/css/' . UPSTREAM_EMAIL_NOTIFICATIONS_NAME . '.css'));
    }

    /**
     * Send an email to the current user's email address to check if all
     * options are working and echoes the response formatted as JSON.
     *
     * @since   1.0.0
     * @static
     *
     * @uses    header()
     * @uses    upstream_user_data()
     * @uses    Plugin::doSendEmail()
     * @uses    wp_die()
     *
     * @throws  \Exception if any unexpected error occurs.
     */
    public static function sendTestEmail()
    {
        header('Content-Type: application/json');

        $user = upstream_user_data();

        $response = [
            'message' => "",
        ];

        $blogName = get_bloginfo('name');
        $blogURL  = get_bloginfo('url');

        $emailFromEmail = get_bloginfo('admin_email');
        $emailToEmail   = trim($user['email']);
        $emailToName    = ! empty(trim($user['full_name'])) ? trim($user['full_name']) : $emailToEmail;
        $emailSubject   = sprintf(
            '%s | %s',
            __('Notification Test', 'upstream-email-notifications'),
            $blogName
        );

        $emailMessage = sprintf('
            <p>%s</p>
            <p>%s</p>
            &#8212; <br />
            <small>%s</small>
        ',
            sprintf(
                __("Looks like you're all set on %s.", 'upstream-email-notifications'),
                '<a target="_blank" rel="noopener noreferrer" href="' . $blogURL . '">' . $blogName . '</a>'
            ),
            sprintf(
                __('Thanks for using %s and the %s plugins.', 'upstream-email-notifications'),
                '<a target="_blank" rel="noopener noreferrer" href="https://upstreamplugin.com">' . __('UpStream',
                    'upstream') . '</a>',
                '<a target="_blank" rel="noopener noreferrer" href="https://upstreamplugin.com/extensions/email-notifications">' . __('Email Notifications',
                    'upstream-email-notifications') . '</a>'
            ),
            __('Please do not reply this message.', 'upstream-email-notifications')
        );

        try {
            if (Plugin::doSendEmail($emailFromEmail, $emailToEmail, $emailToName, $emailSubject, $emailMessage)) {
                $response['message'] = __('Check out your inbox.', 'upstream-email-notifications');
            }
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Method responsible for rendering Password-type fields.
     * It echoes the input's HTML markup.
     *
     * @since   1.0.0
     * @static
     *
     * @param   \CMB2_Field $field             The field object
     * @param   string      $value             The field value
     * @param   integer     $postId            The current post id
     * @param   string      $objectType        The type of object being worked with
     * @param   \CMB2_Types $fieldTypeInstance The field type instance
     */
    public static function render_password_field(
        $field,
        $value = "",
        $postId = 0,
        $objectType = "",
        $fieldTypeInstance = null
    ) {
        // Check if we're in the right place.
        if ($objectType === "options-page") {
            $inputAttrs = [
                'type'  => 'password',
                'desc'  => isset($field->args['desc']) && ! empty($field->args['desc']) ? '<p class="cmb2-metabox-description">' . $field->args['desc'] . '</p>' : "",
                'value' => ! empty($field->value) ? base64_decode($field->value) : "",
            ];

            if (isset($field->args['required']) && $field->args['required'] === 'required') {
                $inputAttrs['required'] = 'required';
            }

            echo $fieldTypeInstance->input($inputAttrs);
        }
    }

    /**
     * Callback responsible for sanitizing Password-type fields.
     *
     * @since   1.0.0
     * @static
     *
     * @param   string         $valueOverride Sanitization override value to return
     * @param   string         $value         The value being passed
     * @param   int            $postId        The post id
     * @param   \CMB2_Sanitize $sanitizer     CMB2 Sanitizer instance
     *
     * @return  string
     */
    public static function sanitize_password_field($valueOverride, $value, $postId = 0, $field, $sanitizer)
    {
        if ( ! empty($value)) {
            $value = base64_encode($value);
        }

        return $value;
    }

    /**
     * Method called by the 'upstream_option_metaboxes' action.
     * It initializes the current class.
     *
     * @since   1.0.0
     *
     * @uses    apply_filters
     *
     * @param   array $options The options array passed by UpStream
     *
     * @return  array
     */
    public function initialize($options)
    {
        $title = __('Email Notifications', 'upstream-email-notifications');

        $pluginOptions = [
            'id'         => UPSTREAM_EMAIL_NOTIFICATIONS_IDENTIFIER,
            'title'      => $title,
            'menu_title' => $title,
            'desc'       => $this->getPageDescription(),
            'show_names' => true,
            'fields'     => self::getOptionsSchema(),
            'show_on'    => [
                'key'   => 'options-page',
                'value' => [UPSTREAM_EMAIL_NOTIFICATIONS_IDENTIFIER],
            ],
        ];

        $pluginOptions = apply_filters(UPSTREAM_EMAIL_NOTIFICATIONS_IDENTIFIER . '_option_fields', $pluginOptions);

        array_push($options, $pluginOptions);

        return $options;
    }

    /**
     * Retrieve the Settings Page description text.
     *
     * @since   1.0.0
     * @access  private
     *
     * @return  string
     */
    private function getPageDescription()
    {
        $description = "";

        return $description;
    }

    /**
     * Retrieve all plugin options schema defined by CMB2 field patterns.
     *
     * @since   1.0.0
     * @static
     *
     * @return  array
     */
    public static function getOptionsSchema()
    {
        $testButtonLabel = __('Send Me a Test Email', 'upstream-email-notifications');

        $fieldsList = [
            [
                'before_row' => sprintf('
                    <div class="metabox-holder plugin-info-wrapper">
                        <div class="postbox pad">
                            <h2><span class="dashicons dashicons-editor-help"></span>&nbsp;%s</h2>
                            <ul>
                                <li>%s</li>
                                <li>%s</li>
                                <li>%s</li>
                            </ul>
                        </div>
                    </div>
                    <br/>
                    <h3>%s</h3>
                    ',
                    __('How does it work?', 'upstream-email-notifications'),
                    sprintf(__('Users will be notified when one or more items (<code>%s</code>, <code>%s</code> or <code>%s</code>) are assigned to them.',
                        'upstream-email-notifications'), __('Milestones', 'upstream'), __('Tasks', 'upstream'),
                        __('Bugs', 'upstream')),
                    __('Any user is able to unsubscribe from a given project if he doesn\'t want to receive notifications related to that project.',
                        'upstream-email-notifications'),
                    __('Administrators are able to enable/disable the notifications feature for individual projects.',
                        'upstream-email-notifications'),
                    __('General Settings', 'upstream-email-notifications')
                ),
                'name'       => __('Email Handler', 'upstream-email-notifications'),
                'id'         => 'handler',
                'type'       => 'select',
                'options'    => [
                    'native' => __('WordPress'),
                    'smtp'   => __('PHPMailer with SMTP', 'upstream-email-notifications'),
                ],
                'default'    => 'native',
                'desc'       => __('You can choose to use either of WordPress\'s native <code>wp_mail()</code> function or <br />the <code>PHPMailer</code> library using your own SMTP options to send out emails.',
                    'upstream-email-notifications'),
                'after_row'  => '<hr />',
            ],
            [
                'before_row' => sprintf('<div class="up-c-collapse is-collapsed" data-handler="native"><h3 role="button">%s <span class="dashicons dashicons-arrow-down-alt2"></span></h3><div class="up-c-collapse__content" style="display: none;">',
                    __('WordPress Handler Settings', 'upstream-email-notifications')),
                'name'       => __("Sender's Email", 'upstream-email-notifications'),
                'id'         => 'sender_email',
                'type'       => 'text',
                'desc'       => __("The notifications sender's email address. The site's admin email is set as default.",
                    'upstream-email-notifications'),
                'default'    => get_bloginfo('admin_email'),
                'after_row'  => '</div></div><hr />',
            ],
            [
                'before_row' => sprintf('<div class="up-c-collapse is-collapsed" data-handler="smtp"><h3 role="button">%s <span class="dashicons dashicons-arrow-down-alt2"></span></h3><div class="up-c-collapse__content" style="display: none;">',
                    __('PHPMailer with SMTP Handler Settings', 'upstream-email-notifications')),
                'name'       => __('SMTP Host', 'upstream-email-notifications'),
                'id'         => 'smtp_host',
                'type'       => 'text',
                'desc'       => __("Your mail server's address.", 'upstream-email-notifications'),
            ],
            [
                'name'    => __('SMTP Port', 'upstream-email-notifications'),
                'id'      => 'smtp_port',
                'type'    => 'text',
                'desc'    => __('The port to your mail server.', 'upstream-email-notifications'),
                'default' => 465,
            ],
            [
                'name' => __('SMTP Username', 'upstream-email-notifications'),
                'id'   => 'smtp_username',
                'type' => 'text',
                'desc' => __('The username to login to your mail server.', 'upstream-email-notifications'),
            ],
            [
                'name' => __('SMTP Password', 'upstream-email-notifications'),
                'id'   => 'smtp_password',
                'type' => 'password_clean',
                'desc' => __('The password to login to your mail server.', 'upstream-email-notifications'),
            ],
            [
                'name'      => __('Encryption Type', 'upstream-email-notifications'),
                'id'        => 'encryption_type',
                'type'      => 'radio_inline',
                'options'   => [
                    'none' => __('None', 'upstream'),
                    'tls'  => __('TLS', 'upstream-email-notifications'),
                    'ssl'  => __('SSL', 'upstream-email-notifications'),
                ],
                'default'   => 'ssl',
                'desc'      => __('The encryption type used for authentication. For most servers SSL is the recommended option.',
                    'upstream-email-notifications'),
                'after_row' => '</div></div><hr />',
            ],
            [
                'before_row' => sprintf('<h3>%s</h3>', __('Reminders Settings', 'upstream-email-notifications')),
                'name'       => __('Enable Reminders', 'upstream-email-notifications'),
                'id'         => 'reminders',
                'type'       => 'radio_inline',
                'options'    => [
                    '1' => __('Enabled'),
                    '0' => __('Disabled'),
                ],
                'default'    => '1',
                'desc'       => __('Reminders are emails sent to assignees informing about upcoming Milestones, Tasks and/or Bugs due dates.',
                    'upstream-email-notifications'),
            ],
            [
                'name'    => __('Default Reminders', 'upstream-email-notifications'),
                'id'      => 'default_reminders',
                'type'    => 'multicheck',
                'options' => [
                    '1' => __('A day before', 'upstream-email-notifications'),
                    '2' => __('Two days before', 'upstream-email-notifications'),
                    '3' => __('Three days before', 'upstream-email-notifications'),
                    '4' => __('A week before', 'upstream-email-notifications'),
                    '5' => __('Two weeks before', 'upstream-email-notifications'),
                ],
                'desc'    => __('Here you can define a set of default reminders for newly added Milestones, Tasks and/or Bugs.',
                    'upstream-email-notifications'),
            ],
            [
                'name'      => __('Check Recurrency', 'upstream-email-notifications'),
                'id'        => 'reminders_check_frequency',
                'type'      => 'select',
                'options'   => [
                    '0' => __('Hourly', 'upstream-email-notifications'),
                    '1' => __('Twice a day', 'upstream-email-notifications'),
                    '2' => __('Daily', 'upstream-email-notifications'),
                ],
                'default'   => '0',
                'desc'      => __('How often should UpStream check for upcoming reminders.'),
                'after_row' => sprintf('
                    <hr />
                    <h3>%s</h3>
                    <p>%s</p>
                    <p>%s</p>
                    <button type="button" class="button" id="upstream-email-notifications-test" title="' . $testButtonLabel . '" data-loading-text="%s">' . $testButtonLabel . '</button>
                    <br />&nbsp;<hr />',
                    __('Testing Settings', 'upstream-email-notifications'),
                    __('By clicking on the button below, UpStream will make an attempt to send a test message to your email address using the saved settings.',
                        'upstream-email-notifications'),
                    __('Make sure you click on the Save button before sending out test emails.',
                        'upstream-email-notifications'),
                    __('Sending...', 'upstream-email-notifications')
                ),
            ],
        ];

        return $fieldsList;
    }
}
