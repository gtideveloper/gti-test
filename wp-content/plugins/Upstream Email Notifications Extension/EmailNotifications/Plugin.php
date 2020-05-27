<?php

namespace UpStream\Plugins\EmailNotifications;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Alledia\EDD_SL_Plugin_Updater;
use UpStream\Plugins\EmailNotifications\Traits\Singleton;

/**
 * The plugin main class. It is responsible for looking for changes
 * in Projects data and sending email notifications to some users.
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
final class Plugin
{
    use Singleton;

    protected static $updater;

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        // remove PHP SESSION NONE becuase it's breaking the health check

        static::loadTextDomain();
    }

    /**
     * Load localisation files.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @uses    https://codex.wordpress.org/Function_Reference/load_textdomain
     * @uses    https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
     */
    private static function loadTextDomain()
    {
        $currentLocale = apply_filters('plugin_locale', get_locale(), UPSTREAM_EMAIL_NOTIFICATIONS_NAME);

        $moFilePath = sprintf('%s/%2$s/%2$s-%3$s.mo', WP_LANG_DIR, UPSTREAM_EMAIL_NOTIFICATIONS_NAME, $currentLocale);

        load_textdomain(UPSTREAM_EMAIL_NOTIFICATIONS_NAME, $moFilePath);
        load_plugin_textdomain(UPSTREAM_EMAIL_NOTIFICATIONS_NAME, false,
            UPSTREAM_EMAIL_NOTIFICATIONS_NAME . '/languages');
    }

    /**
     * Initialize the plugin by testing its dependencies and attaching related WP hooks.
     *
     * @since   1.0.0
     * @static
     */
    public static function initialize()
    {
        if ( ! self::testDependencies()) {
            return false;
        }

        self::attachHooks();

        Reminders::instantiate();
    }

    /**
     * Check if all plugin dependencies are loaded.
     * In case the check fails, the user will be redirected to an error page.
     *
     * @since   1.0.0
     * @static
     */
    public static function testDependencies()
    {
        $loaded = self::areDependenciesLoaded();

        if ( ! $loaded) {
            self::displayDependencyErrorMessage();
        }

        return $loaded;
    }

    /**
     * Check if all plugin dependencies are loaded.
     *
     * @return  bool
     * @since   1.0.0
     * @static
     *
     */
    public static function areDependenciesLoaded()
    {
        if ( ! function_exists('is_plugin_inactive')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $upstreamPluginSignature = 'upstream/upstream.php';

        $dependenciesAreLoaded = (
            file_exists(WP_PLUGIN_DIR . '/' . $upstreamPluginSignature) &&
            ! is_plugin_inactive($upstreamPluginSignature)
        );

        return $dependenciesAreLoaded;
    }

    /**
     * Add an 'admin_notices' action so it renders an error message.
     *
     * @since   1.0.0
     * @static
     *
     * @see     https://developer.wordpress.org/reference/functions/add_action
     */
    public static function displayDependencyErrorMessage()
    {
        add_action('admin_notices',
            [UPSTREAM_EMAIL_NOTIFICATIONS_NAMESPACE . '\\Plugin', 'renderDependencyErrorMessage']);
    }

    /**
     * Attach the plugin actions with WordPress.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @see     https://developer.wordpress.org/reference/functions/register_activation_hook
     * @see     https://developer.wordpress.org/reference/functions/register_deactivation_hook
     * @see     https://developer.wordpress.org/reference/functions/add_action
     * @see     https://developer.wordpress.org/reference/functions/add_filter
     */
    private static function attachHooks()
    {
        $namespace = __NAMESPACE__ . '\Plugin';

        register_activation_hook(UPSTREAM_EMAIL_NOTIFICATIONS_PLUGIN, [$namespace, 'activationCallback']);
        register_deactivation_hook(UPSTREAM_EMAIL_NOTIFICATIONS_PLUGIN, [$namespace, 'deactivationCallback']);

        add_filter('plugin_action_links_' . UPSTREAM_EMAIL_NOTIFICATIONS_PLUGIN, [$namespace, 'handleActionLinks']);

        add_action('upstream_loaded', [$namespace, 'run']);
        add_action('upstream_details_metaboxes', [$namespace, 'renderMetabox']);

        add_action('save_post', [$namespace, 'onAfterSavePost'], 10, 3);
        add_action('pre_post_update', [$namespace, 'cacheProjectIntoSession'], 10, 2);

        add_action('frontend_after_item_save', [$namespace, 'onAfterFrontendSaveItem'], 10, 3);
        add_action('frontend_before_item_save', [$namespace, 'cacheProjectIntoSession'], 10, 2);

        add_action('upstream_email_notifications_detect_changes', [$namespace, 'handleItemsComingFromHook'], 10, 4);
        add_action('upstream_email_notifications_for_new_items', [$namespace, 'handleItemOnPost'], 10, 3);

        add_action('admin_init', [$namespace, 'initUpdater']);

        Reminders::attachHooks();
    }

    /**
     * Run the plugin and create the plugin's settings page.
     *
     * @since   1.0.0
     * @static
     */
    public static function run()
    {
        if ( ! self::testDependencies()) {
            return false;
        }

        $instance = static::getInstance();
        Settings::getInstance();

        add_action('admin_enqueue_scripts', [self::$instance, 'enqueueScripts'], 101);
        add_action('wp_enqueue_scripts', [self::$instance, 'enqueueScripts'], 1002);

        Reminders::run();
    }

    /**
     * Render a dependency error message.
     *
     * @since   1.0.0
     * @static
     */
    public static function renderDependencyErrorMessage()
    {
        $message = sprintf(
            __('Please, make sure %s plugin is installed and active in order to make %s plugin to work.',
                'upstream-email-notifications'),
            '<a href="https://upstreamplugin.com" target="_blank" rel="noreferrer noopener"><strong>' . __('UpStream',
                'upstream') . '</strong></a>',
            '<strong>' . __('Email Notifications', 'upstream-email-notifications') . '</strong>'
        );
        ?>

        <div class="notice notice-error is-dismissible">
            <p><?php echo $message; ?></p>
        </div>

        <?php
    }

    /**
     * Render a notification error message.
     *
     * @since   1.0.0
     * @static
     */
    public static function renderSenderErrorMessage()
    {
        ?>

        <div class="notice notice-warning is-dismissible">
            <h4><?php echo sprintf('%s - %s', __('UpStream', 'upstream'),
                    __('Email Notifications', 'upstream-email-notifications')); ?></h4>
            <p><?php echo __('There was an error sending the notifications. Please review your SMTP settings.',
                    'upstream-email-notifications'); ?></p>
        </div>

        <?php
    }

    /**
     * Initialize EDD updater.
     */
    public static function initUpdater()
    {
        static::$updater = new EDD_SL_Plugin_Updater(
            UPSTREAM_EMAIL_NOTIFICATIONS_API_URL,
            UPSTREAM_EMAIL_NOTIFICATIONS_PLUGIN,
            [
                'version' => UPSTREAM_EMAIL_NOTIFICATIONS_VERSION,
                'license' => get_option('upstream_email_notifications_license_key'),
                'item_id' => UPSTREAM_EMAIL_NOTIFICATIONS_ID,
                'author'  => 'UpStream',
                'beta'    => false,
            ]
        );
    }

    /**
     * Method called after a post's form is being submitted and before it gets saved into DB.
     * It saves the project into $_SESSION so we can deal with changed data after saving.
     *
     * @param integer $post_ID The post id.
     * @param array   $post    The post data.
     *
     * @since   1.0.0
     * @static
     *
     */
    public static function cacheProjectIntoSession($post_ID, $post)
    {
        // Make sure we're handling only "project"-type posts.
        if ($post['post_type'] === "project") {
            \Upstream_Cache::get_instance()->set('upstream_email_notifications', self::getProjectData($post_ID));
        }
    }

    /**
     * Retrieve project data in an unified way.
     *
     * @param integer $project_id The project id to be retrieved.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     * @uses    \UpStream_Project()
     *
     */
    public static function getProjectData($project_id)
    {
        $project = new \UpStream_Project($project_id);

        $row = [
            'id'           => (int)$project_id,
            'created_at'   => $project->post_date_gmt,
            'modified_at'  => $project->post_modified_gmt,
            'title'        => $project->post_title,
            'alias'        => $project->post_name,
            'url'          => $project->guid,
            'status'       => $project->get_meta('status'),
            'client_id'    => (int)$project->get_meta('client'),
            'client_users' => (array)$project->get_meta('client_users'),
            'members'      => (array)$project->get_meta('members'),
            'description'  => (string)$project->get_meta('description'),
            'milestones'   => (array)$project->get_meta('milestones'),
            'tasks'        => (array)$project->get_meta('tasks'),
            'bugs'         => (array)$project->get_meta('bugs'),
            'files'        => (array)$project->get_meta('files'),
            'discussion'   => (array)$project->get_meta('discussion'),
        ];

        return $row;
    }

    /**
     * Method called whenever a post/page is created or updated.
     *
     * @param integer  $post_id The post id.
     * @param \WP_Post $post    The post object being saved.
     * @param bool     $update  Whether this is an existing post being updated or not.
     *
     * @uses    self::getCachedProjectFromSession()
     * @uses    self::getNotifiableChangesByUserOnData()
     *
     * @since   1.0.0
     * @static
     *
     * @see     https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
     *
     * @uses    self::getProjectDataFromPost()
     */
    public static function onAfterSavePost($post_id, $post, $update)
    {
        // Make sure we're dealing only with "project"-type posts.
        if ($post->post_type === 'project') {
            if (empty($_POST) || self::areNotificationsDisabledForProject($post_id)) {
                return;
            }

            $dataAfterSave = self::getProjectDataFromPost();

            if ((bool)$update) {
                $dataBeforeSave = self::getCachedProjectFromSession();

                self::getNotifiableChangesByUserOnData($dataBeforeSave, $dataAfterSave, $post_id);
            }
        }
    }

    public static function onAfterFrontendSaveItem($project_id)
    {
        if (self::areNotificationsDisabledForProject($project_id)) {
            return;
        }

        $dataAfterSave = self::getProjectDataFromPost();
        $dataBeforeSave = self::getCachedProjectFromSession();

        self::getNotifiableChangesByUserOnData($dataBeforeSave, $dataAfterSave, $project_id);
    }

    /**
     * Check if Email Notifications are disabled for a given project.
     * If no ID is passed, this function will try to figure it out the ID by using
     * the upstream_post_id() function.
     *
     * @param integer $project_id The post/project ID to be checked.
     *
     * @return  bool
     * @uses    upstream_post_id()
     * @uses    get_post_meta()
     *
     * @since   1.0.0
     * @static
     *
     * @see     upstream_post_id() in upstream/includes/up-general-functions.php
     *
     */
    public static function areNotificationsDisabledForProject($project_id = 0)
    {
        $areNotificationsDisabled = false;
        $project_id               = (int)$project_id;

        // Try to figure it out the ID if the given $project_id appears to be invalid.
        if ($project_id <= 0) {
            $project_id = (int)upstream_post_id();
        }

        if ($project_id > 0) {
            $key = "_upstream_project_disable_all_notifications";

            // Check if the request is comming from a POST send by someone editing a project which has the permission to disable all project notifications.
            // This prevent toggling the option to take effect only after saving the project's form.
            if (
                isset($_POST['action']) &&
                $_POST['action'] === 'editpost' &&
                isset($_POST['post_type']) &&
                $_POST['post_type'] === 'project' &&
                upstream_admin_permissions('disable_project_notifications')
            ) {
                $areNotificationsDisabled = isset($_POST[$key]) && $_POST[$key] === 'on';
            } else {
                $projectMeta              = get_post_meta($project_id, $key, false);
                $areNotificationsDisabled = ! empty($projectMeta) && $projectMeta[0] === 'on';
            }
        }

        return $areNotificationsDisabled;
    }

    /**
     * Retrieve all project data from $_POST request.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function getProjectDataFromPost()
    {
        $data = [];

        if (isset($_POST['post_type']) && $_POST['post_type'] === 'project') {
            $data['id'] = (int)$_POST['post_ID'];

            $project = new \UpStream_Project($data['id']);

            $data['created_at']   = $project->post_date_gmt;
            $data['modified_at']  = $project->post_modified_gmt;
            $data['title']        = isset($_POST['post_title']) ? $_POST['post_title'] : $project->post_title;
            $data['alias']        = isset($_POST['post_name']) ? $_POST['post_name'] : $project->post_name;
            $data['url']          = $project->guid;
            $data['status']       = $project->get_meta('status');
            $data['client_id']    = isset($_POST['_upstream_project_client']) ? (int)$_POST['_upstream_project_client'] : $project->get_meta('client');
            $data['client_users'] = isset($_POST['_upstream_project_client_users']) ? (array)$_POST['_upstream_project_client_users'] : $project->get_meta('client_users');
            $data['members']      = (array)$project->get_meta('members');
            $data['description']  = (string)$_POST['_upstream_project_description'];
            $data['milestones']   = isset($_POST['_upstream_project_milestones']) ? (array)@$_POST['_upstream_project_milestones'] : $project->get_meta('milestones');
            $data['tasks']        = isset($_POST['_upstream_project_tasks']) ? (array)@$_POST['_upstream_project_tasks'] : $project->get_meta('tasks');
            $data['bugs']         = isset($_POST['_upstream_project_bugs']) ? (array)@$_POST['_upstream_project_bugs'] : $project->get_meta('bugs');
            $data['files']        = isset($_POST['_upstream_project_files']) ? (array)@$_POST['_upstream_project_files'] : $project->get_meta('files');
            $data['discussion']   = isset($_POST['_upstream_project_discussion']) ? (array)@$_POST['_upstream_project_discussion'] : $project->get_meta('comments');
        }

        return $data;
    }

    /**
     * Retrieve post data from $_SESSION which was saved by self::cacheProjectIntoSession().
     * The session key gets erased after being retrieved.
     *
     * @return  array
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function getCachedProjectFromSession()
    {
        $sessionKey = 'upstream_email_notifications';

        $data = \Upstream_Cache::get_instance()->get($sessionKey);

        return $data;
    }

    /**
     * Compares project data after being saved against before it was editted.
     * It basically detect changes in 'Assigned To' columns and attempt to notify those
     * newly assigned users of that changes via email.
     *
     * @param array $dataBefore Project data before it was editted.
     * @param array $dataAfter  Project data after it was saved.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @see     self::turnChangesIntoNotifications()
     *
     */
    private static function getNotifiableChangesByUserOnData($dataBefore, $dataAfter, $project_id = 0)
    {
        $isNew = $dataBefore['created_at'] === '0000-00-00 00:00:00';

        $changes = [];

        $cache = [
            'users'              => [],
            'unsubscribbedUsers' => [],
        ];

        $keysToBeMapped = [
            'milestones',
            'tasks',
            'bugs',
            'files',
        ];

        $project = new \UpStream_Project($project_id);

        /**
         * Check if a given user is eligible to receive notifications for a given project.
         *
         * @param integer $user_id The user's id to be checked.
         *
         * @return  boolean
         * @uses    \UpStream_Project   $project    The project being run.
         *
         * @since   1.0.0
         *
         * @uses    array               $cache      A temporary cache that stores users ids and if they prefer not to receive notifications for the project.
         */
        $canUserReceiveNotifications = function ($user_id) use (&$cache, $project) {
            $user_id = (int)$user_id;

            if ($user_id <= 0 || isset($cache['unsubscribbedUsers'][$user_id])) {
                return false;
            }

            if ( ! isset($cache['users'][$user_id])) {
                $user = get_userdata($user_id);

                if (
                    empty($user)
                    || empty($user->user_email)
                    || $project->get_meta('disable_notifications_' . $user_id) === "on"
                ) {
                    $cache['unsubscribbedUsers'][$user_id] = 1;

                    return false;
                }

                $cache['users'][$user_id] = $user;
            }

            return true;
        };

        /**
         * Associate a given $data of type $dataType with a $user_id.
         * It assumes a couple of things:
         *   - the current project is whitelisted to send notifications
         *   - $dataType is either one of those: "milestones", "tasks" or "bugs"
         *   - $data is a new item or one that has changed (in terms of the `assigned_to` column)
         *   - $user_id has a valid email address and is subscribed to the current project
         *
         * @param int    $user_id  The user id in which $data was assigned to.
         * @param string $dataType Represents the type of $data: "milestones", "tasks" or "bugs".
         * @param object $data     Object containing the changed data.
         *
         * @uses                        array   $changes    Array containing all data (changed) assigned to the user separated by type.
         *                              array(user_id => array('milestones' => array(), 'tasks' => array(), 'bugs' => array())).
         *
         * @since                       1.0.0The following item(
         *
         */
        $registerChange = function ($user_id, $dataType, $data) use (&$changes) {
            if ( ! isset($changes[$user_id])) {
                $changes[$user_id] = [
                    $dataType => [],
                ];
            }

            if ( ! isset($changes[$user_id][$dataType])) {
                $changes[$user_id][$dataType] = [];
            }

            array_push($changes[$user_id][$dataType], $data);
        };

        if ($isNew) {
            if (self::areNotificationsDisabledForProject($project->ID)) {
                return;
            }

            foreach ($keysToBeMapped as $key) {
                if (isset($dataAfter[$key])) {
                    foreach ($dataAfter[$key] as $itemAfter) {
                        $itemAfter = (object)$itemAfter;

                        if (isset($itemAfter->assigned_to)) {
                            if ( ! is_array($itemAfter->assigned_to)) {
                                $itemAfter->assigned_to = [$itemAfter->assigned_to];
                            }

                            $itemAfter->assigned_to = array_filter(array_map('intval', $itemAfter->assigned_to));
                        } else {
                            $itemAfter->assigned_to = [];
                        }

                        foreach ($itemAfter->assigned_to as $assignee) {
                            if ($canUserReceiveNotifications($assignee)) {
                                $registerChange($assignee, $key, $itemAfter);
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($keysToBeMapped as $key) {
                if (isset($dataAfter[$key]) && isset($dataBefore[$key])) {
                    foreach ($dataAfter[$key] as $itemAfter) {
                        $itemAfter = (object)$itemAfter;

                        if (isset($itemAfter->assigned_to)) {
                            if ( ! is_array($itemAfter->assigned_to)) {
                                $itemAfter->assigned_to = [$itemAfter->assigned_to];
                            }

                            $assignees = array_filter(array_map('intval', $itemAfter->assigned_to));

                            if (empty($itemAfter->id)) {
                                foreach ($assignees as $assignee) {
                                    if ($canUserReceiveNotifications($assignee)) {
                                        $registerChange($assignee, $key, $itemAfter);
                                    }
                                }
                            } else {
                                foreach ($dataBefore[$key] as $itemBefore) {
                                    $itemBefore = (object)$itemBefore;

                                    if ($itemAfter->id === $itemBefore->id) {
                                        if ( ! isset($itemBefore->assigned_to)) {
                                            $itemBefore->assigned_to = [];
                                        } else {
                                            $itemBefore->assigned_to = is_array($itemBefore->assigned_to) ? $itemBefore->assigned_to : (array)$itemBefore->assigned_to;
                                        }

                                        $itemBeforeAssignees = array_filter(array_map('intval',
                                            $itemBefore->assigned_to));

                                        foreach ($assignees as $assignee) {
                                            if ( ! in_array($assignee, $itemBeforeAssignees)
                                                 && $canUserReceiveNotifications($assignee)
                                            ) {
                                                $registerChange($assignee, $key, $itemAfter);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        self::turnChangesIntoNotifications($changes);
    }

    /**
     * Method that receives an associative array containing user_ids as key and a list of milestones, tasks and/or bugs
     * that might have been assigned to that user as values. An email message is sent to that user's email address
     * detailing those items.
     *
     * @param array $changesByUserId Array containing all changes organized by user.
     *
     * @uses    self::sendEmailForProject()
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function turnChangesIntoNotifications($changesByUserId)
    {
        if ( ! empty($changesByUserId)) {
            $itemTypes = [
                'milestones' => upstream_milestone_label_plural(),
                'tasks'      => upstream_task_label_plural(),
                'bugs'       => upstream_bug_label_plural(),
                'files'      => upstream_file_label_plural(),
            ];

            $post_id = upstream_post_id();

            $milestones = getMilestonesTitles();

            foreach ($changesByUserId as $userId => $changes) {
                $messageHtmlList = [];

                foreach ($changes as $itemType => $items) {
                    $messageHtmlList[] = '<div>';
                    $messageHtmlList[] = '<span><strong>' . $itemTypes[$itemType] . '</strong></span><br />';

                    $messageHtmlList[] = '<ul>';

                    foreach ($items as $item) {
                        $messageHtmlList[] = '<li>';

                        if ($itemType === 'milestones') {
                            $messageHtmlList[] = isset($item->milestone) ? $milestones[$item->milestone] : $milestones[$item->title];
                        } else {
                            $messageHtmlList[] = $item->title;
                        }

                        $messageHtmlList[] = '</li>';
                    }

                    $messageHtmlList[] = '</ul>';

                    $messageHtmlList[] = '</div>';
                }

                $html = implode('', $messageHtmlList);

                /**
                 * @param string $html
                 * @param array  $changesByUserId
                 */
                $html = apply_filters('upstream_email_notifications_changes_notification_message', $html,
                    $changesByUserId);

                self::sendEmailForProject($post_id, $html, $userId);
            }
        }
    }

    /**
     * Send email notifications to a given user.
     *
     * @param integer $project_id The project ID.
     * @param string  $message    The email message.
     * @param integer $user_id    The user ID.
     *
     * @return  bool
     * @uses    self::<h1>{{ label.project }}: {{ project.title }}</h1>()
     * @uses    self::setTransientError()
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function sendEmailForProject($project_id, $message, $user_id = 0)
    {
        $title = get_the_title($project_id);

        $siteName = get_bloginfo('name');

        $emailSubject = sprintf(_x('%s Notifications | %s', '1st %s: Site name, 2nd %s: Project name',
            'upstream-email-notifications'), $siteName, $title);

        /**
         * @param string $emailSubject
         * @param string $siteName
         * @param string $title
         *
         * @return string
         */
        $emailSubject = apply_filters('upstream_email_notifications_assigned_email_subject', $emailSubject, $siteName,
            $title);

        $emailFromEmail = get_bloginfo('admin_email');

        $user         = upstream_user_data($user_id);
        $emailToEmail = $user['email'];
        $emailToName  = ! empty($user['full_name']) ? $user['full_name'] : $emailToEmail;

        $emailMessage = sprintf('
            <p>%s</p>
            %s
            &#8212; <br />
            %s <br />
            <small>%s</small>
        ',
            __('The following item(s) was/were assigned to you:', 'upstream-email-notifications'),
            $message,
            sprintf(
                __('You are receiving this because you are a member of the %s, from %s.',
                    'upstream-email-notifications'),
                '<a href="' . get_post_permalink($project_id) . '" target="_blank" rel="noopener noreferrer">' . $title . '</a>&nbsp;project',
                '<a href="' . get_bloginfo('url') . '" target="_blank" rel="noopener noreferrer">' . $siteName . '</a>'
            ),
            __('Please do not reply this message.', 'upstream-email-notifications')
        );

        /**
         * @param string $emailMessage
         * @param int    $project_id
         * @param string $message
         * @param int    $user_id
         * @param string $title
         * @param string $siteName
         *
         * @return string
         */
        $emailMessage = apply_filters('upstream_email_notifications_assigned_email_message', $emailMessage, $project_id,
            $message, $user_id, $title, $siteName);

        try {
            $emailHasBeenSent = self::doSendEmail($emailFromEmail, $emailToEmail, $emailToName, $emailSubject,
                $emailMessage);

            return $emailHasBeenSent;
        } catch (\Exception $e) {
            self::setTransientError($e->getMessage());

            return false;
        }
    }

    /**
     * Method responsible for sending out email messages for UpStream.
     *
     * @param string $fromEmail The sender's email address.
     * @param string $toEmail   The recipient's email address.
     * @param string $toName    The recipient's name.
     * @param string $subject   The email subject.
     * @param string $message   The email body.
     *
     * @return  bool
     * @throws  \Exception if any error occurred during the send process.
     *
     * @since   1.0.0
     * @static
     *
     * @uses    self::getOptions()
     * @uses    \PHPMailer
     */
    public static function doSendEmail($fromEmail, $toEmail, $toName, $subject, $message = "")
    {
        try {
            $pluginOptions = self::getOptions(true);

            $fromName = sprintf(_x('%s Notifications BOT', '%s: Site name', 'upstream-email-notifications'),
                get_bloginfo('name'));

            if (isset($pluginOptions->handler) && $pluginOptions->handler === "smtp") {
                if ( ! isset($pluginOptions->smtp_host)) {
                    throw new \Exception(__("SMTP Host not set.", 'upstream-email-notifications'));
                }

                if ( ! isset($pluginOptions->smtp_port)) {
                    throw new \Exception(__("SMTP Port not set.", 'upstream-email-notifications'));
                }

                if ( ! isset($pluginOptions->smtp_username)) {
                    throw new \Exception(__("SMTP Username not set.", 'upstream-email-notifications'));
                }

                if ( ! isset($pluginOptions->smtp_password)) {
                    throw new \Exception(__("SMTP Password not set.", 'upstream-email-notifications'));
                }
            }

            if (empty($fromEmail)) {
                throw new \Exception(__("Sender's email address cannot be empty.", 'upstream-email-notifications'));
            }

            if (empty($toEmail)) {
                throw new \Exception(__("Recipient's email address cannot be empty.", 'upstream-email-notifications'));
            }

            if (empty($toName)) {
                $toName = $toEmail;
            }

            if (empty($subject)) {
                $subject = __("No Subject", 'upstream-email-notifications');
            }

            if (isset($pluginOptions->handler) && $pluginOptions->handler === "smtp") {
                if ( ! class_exists('PHPMailer')) {
                    require_once ABSPATH . 'wp-includes/class-phpmailer.php';
                }

                $mailer = new \PHPMailer(true);

                $mailer->IsSMTP();
                $mailer->SMTPAuth = true;
                $mailer->Host     = $pluginOptions->smtp_host;
                $mailer->Port     = $pluginOptions->smtp_port;
                $mailer->Username = $pluginOptions->smtp_username;
                $mailer->Password = base64_decode($pluginOptions->smtp_password);

                if ($pluginOptions->encryption_type === "tls" || $pluginOptions->encryption_type === "ssl") {
                    $mailer->SMTPSecure = $pluginOptions->encryption_type;
                }

                $mailer->CharSet = 'utf-8';
                // SMTP will only allow messages if "from"="smtp-username".
                $mailer->setFrom($pluginOptions->smtp_username, $fromName);
                $mailer->addAddress($toEmail, $toName);
                $mailer->Subject = $subject;
                $mailer->isHTML(true);
                $mailer->Body = (string)$message;

                if ($mailer->send()) {
                    return true;
                } else {
                    $errorMessage = __("UpStream was unable to send the notifications.",
                        'upstream-email-notifications');
                }
            } else {
                if ( ! function_exists('wp_mail')) {
                    require_once ABSPATH . 'wp-includes/pluggable.php';
                }

                $emailHeaders = [
                    'Content-Type: text/html; charset=UTF-8',
                    sprintf('From: %s <%s>', $fromName, $pluginOptions->sender_email),
                ];

                $recipient = sprintf('%s <%s>', $toName, $toEmail);

                if (wp_mail($recipient, $subject, $message, $emailHeaders)) {
                    return true;
                } else {
                    $errorMessage = __("UpStream was unable to send the notifications. Please make sure the server is allowed to send emails.",
                        'upstream-email-notifications');
                }
            }
        } catch (\phpmailerException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        throw new \Exception($errorMessage);
    }

    /**
     * Retrieve all plugin options.
     *
     * @param bool $returnAsObject Whether to return the data as array/object.
     *
     * @return  array | object
     * @since   1.0.0
     * @static
     *
     */
    public static function getOptions($returnAsObject = false)
    {
        $optionsList = (array)get_option('upstream_email_notifications');

        if (empty($optionsList) || array_keys($optionsList) === range(0, count($optionsList) - 1)) {
            $optionsList = [];

            $optionsSchema = Settings::getOptionsSchema();
            foreach ($optionsSchema as $option) {
                $optionsList[$option['id']] = isset($option['default']) ? $option['default'] : null;
            }
        }

        if ((bool)$returnAsObject) {
            $optionsList = json_decode(json_encode($optionsList));
        }

        return $optionsList;
    }

    /**
     * Save temporarily an error message into a transient item.
     *
     * @param string  $message        The error message.
     * @param integer $expirationTime The transient expiration time in seconds.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @see     https://codex.wordpress.org/Transients_API
     *
     */
    private static function setTransientError($message, $expirationTime = 120)
    {
        $expirationTime = (int)$expirationTime > 0 ? (int)$expirationTime : 60;
        if (empty($message)) {
            return false;
        }

        $message = '<strong>' . sprintf('%s - %s', __('UpStream', 'upstream'),
                __('Email Notifications', 'upstream-email-notifications')) . '</strong><br />' . $message;

        set_transient('upstream_errors', $message, $expirationTime);
    }

    /**
     * Method called once the plugin is activated.
     * If there's a dependency missing, the plugin is deactivated.
     *
     * @since   1.0.0
     * @static
     *
     * @uses    self::areDependenciesLoaded()
     * @uses    self::forcePluginDeactivation()
     * @uses    wp_die()
     */
    public static function activationCallback()
    {
        // Check if UpStream is installed e active.
        if ( ! self::areDependenciesLoaded()) {
            self::forcePluginDeactivation();

            $errorMessage = '<p>' . sprintf(
                    __('Please, make sure %s is <strong>installed</strong> and <strong>active</strong> in order to make this plugin to work.',
                        'upstream-email-notifications'),
                    '<a href="//wordpress.org/plugins/upstream" target="_blank" rel="noopener noreferrer">' . __('UpStream',
                        'upstream') . '</a>'
                ) . '</p>';

            self::dieWithError($errorMessage);
        }

        // Check the WordPress version.
        global $wp_version;
        if (version_compare($wp_version, '4.5', '<')) {
            self::forcePluginDeactivation();

            $errorMessage = '<p>' . sprintf(
                    _x('It seems you are using an outdated version of WordPress (%s).',
                        '%s: Current WordPress running version', 'upstream-email-notifications') . '<br>' .
                    __('For security reasons please update your installation.',
                        'upstream-email-notifications') . '<br>' .
                    _x('The <i>%s</i> requires WordPress version 4.5 or later.', '%s: Plugin name',
                        'upstream-email-notifications'),
                    $wp_version,
                    sprintf('%s - %s', __('UpStream', 'upstream'),
                        __('Email Notifications', 'upstream-email-notifications'))
                ) . '</p>';

            self::dieWithError($errorMessage);
        }

        // Check the PHP version.
        if (version_compare(PHP_VERSION, '5.6.20', '<')) {
            $errorMessage = sprintf(
                '<p>%s</p><p>%s</p>',
                __('For security reasons, this plugin requires <strong>PHP version 5.6.20</strong> or <strong>greater</strong> to run.',
                    'upstream-email-notifications'),
                __('Please, update your PHP (currently at', 'upstream-email-notifications') . ' ' . PHP_VERSION . ').'
            );

            self::dieWithError($errorMessage);
        }
    }

    /**
     * Forcelly uninstall the current plugin.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @see     https://developer.wordpress.org/reference/functions/deactivate_plugins
     */
    private static function forcePluginDeactivation()
    {
        deactivate_plugins(UPSTREAM_EMAIL_NOTIFICATIONS_PLUGIN);
    }

    /**
     * Calls wp_die() function with a custom message.
     *
     * @param string $message The message to be displayed.
     *
     * @since   1.1.0
     * @access  private
     * @static
     *
     */
    private static function dieWithError($message = "")
    {
        $message = '<h1>' . __('Email Notifications', 'upstream-email-notifications') . '</h1><br/>' . $message;

        $message .= sprintf('<br /><br />
            <a class="button" href="javascript:history.back();" title="%s">%s</a>',
            __('Go back to the plugins page.', 'upstream-email-notifications'),
            __('Go back', 'upstream-email-notifications')
        );

        wp_die($message);
    }

    /**
     * Method called once the plugin is deactivated.
     *
     * @since   1.0.0
     * @static
     */
    public static function deactivationCallback()
    {
        // Do nothing.
    }

    /**
     * Callback called to setup the links to display on the plugins page, besides active/deactivate links.
     *
     * @param array $links The list of links to be displayed.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function handleActionLinks($links)
    {
        $links['settings'] = sprintf(
            '<a href="%s" title="%2$s" aria-label="%2$s">%3$s</a>',
            admin_url('admin.php?page=upstream_email_notifications'),
            __('Open Settings Page', 'upstream-email-notifications'),
            __('Settings', 'upstream-email-notifications')
        );

        return $links;
    }

    /**
     * Configure the plugin's project metabox.
     *
     * @since   1.0.0
     * @static
     *
     * @uses    new_cmb2_box()
     * @uses    \Cmb2Grid\Grid\Cmb2Grid()
     */
    public static function renderMetabox()
    {
        $userCanDisableProjectNotifications = upstream_admin_permissions('disable_project_notifications');
        $areNotificationsDisabled           = self::areNotificationsDisabledForProject();

        if ($areNotificationsDisabled && ! $userCanDisableProjectNotifications) {
            return;
        }

        $metabox = new_cmb2_box([
            'id'           => '_upstream_project_notifications',
            'title'        => '<span class="dashicons dashicons-email"></span> ' . __('Notifications',
                    'upstream-email-notifications'),
            'object_types' => ['project'],
            'context'      => 'side',
            'priority'     => 'default',
        ]);

        $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

        $metaboxFieldsList = [
/*
            [
                'id'          => '_upstream_project_disable_notifications_' . upstream_current_user_id(),
                'type'        => 'checkbox',
                'description' => '</span><span>' . __("I don't want to receive email notifications for this project",
                        'upstream-email-notifications'),
            ],
*/
        ];

        if ($userCanDisableProjectNotifications) {
            array_push($metaboxFieldsList, [
                'id'           => '_upstream_project_disable_all_notifications',
                'type'         => 'checkbox',
                'description'  => '</span><span>' . __('Disable all email notifications for this project',
                        'upstream-email-notifications'),
                'before_field' => '<hr />',
            ]);
        }

        foreach ($metaboxFieldsList as $fieldIndex => $field) {
            $metaboxFieldsList[$fieldIndex] = $metabox->add_field($field);
        }
    }

    /**
     * Check if there's an item on POST that should be eligible to be sent a notification to the assigned user.
     *
     * @param string $dataType   Either "milestones", "tasks" or "bugs".
     * @param int    $project_id The project's ID which is being saved/edited.
     *
     * @uses    self::turnChangesIntoNotifications()
     *
     * @since   1.0.0
     * @static
     *
     * @uses    \UpStream_Project()
     */
    public static function handleItemOnPost($dataType, $data, $project_id)
    {
        if (
            (int)$project_id <= 0
            || ! in_array($dataType, ['milestones', 'tasks', 'bugs', 'files'])
            || empty($_POST)
            || ! isset($_POST['data'])
            || ! isset($_POST['data']['assigned_to'])
            || self::areNotificationsDisabledForProject($project_id)
            || ( ! isset($_POST['data']['title']) && ! isset($_POST['data']['milestone']))
        ) {
            return;
        }

        $item = (object)[
            'title'       => isset($_POST['data']['title']) ? $_POST['data']['title'] : $_POST['data']['milestone'],
            'assigned_to' => is_array($_POST['data']['assigned_to'])
                ? $_POST['data']['assigned_to']
                : [(int)$_POST['data']['assigned_to']],
        ];

        $item->assigned_to = array_filter(array_map('intval', $item->assigned_to));
        if (count($item->assigned_to) === 0) {
            return;
        }

        $project = new \UpStream_Project($project_id);

        $data = [];

        foreach ($item->assigned_to as $assignee) {
            $user = get_userdata($assignee);
            if ( ! empty($user->user_email)) {
                $disabledNotifications = (string)$project->get_meta('disable_notifications_' . $assignee);
                if ($disabledNotifications !== 'on') {
                    $data[$assignee] = [
                        $dataType => [$item],
                    ];
                }
            }
        }

        if (count($data) > 0) {
            self::turnChangesIntoNotifications($data);
        }
    }

    /**
     * Notifies users if needed to from incoming changes coming from hook "upstream_email_notifications_detect_changes".
     *
     * @param string $dataType   Either "milestones", "tasks" or "bugs".
     * @param array  $data       The incoming data.
     * @param int    $project_id The project's ID which is being saved/edited.
     * @param bool   $sendEmails Whether the plugin should try to notify users about possible assignment changes.
     *
     * @return  array   $changedData
     * @uses    self::areNotificationsDisabledForProject()
     * @uses    \UpStream_Project()
     * @uses    self::turnChangesIntoNotifications()
     *
     * @since   1.0.0
     * @static
     *
     */
    public static function handleItemsComingFromHook($dataType, $data, $project_id, $sendEmails = false)
    {
        $data = (array)$data;
        if ((int)$project_id <= 0 || ! in_array($dataType, ['milestones', 'tasks', 'bugs', 'files']) || empty($data)) {
            return;
        }

        if (self::areNotificationsDisabledForProject($project_id)) {
            return;
        }

        $project = new \UpStream_Project($project_id);

        $originalData = (array)$project->get_meta($dataType);

        if (empty($originalData)) {
            return;
        }

        $cache = [
            'users'              => [],
            'unsubscribbedUsers' => [],
        ];

        /**
         * Check if a given user is eligible to receive notifications for a given project.
         *
         * @param integer $user_id The user's id to be checked.
         *
         * @return  boolean
         * @uses    \UpStream_Project   $project    The project being run.
         *
         * @since   1.0.0
         *
         * @uses    array               $cache      A temporary cache that stores users ids and if they prefer not to receive notifications for the project.
         */
        $canUserReceiveNotifications = function ($user_id) use (&$cache, $project) {
            $user_id = (int)$user_id;

            if ($user_id <= 0 || isset($cache['unsubscribbedUsers'][$user_id])) {
                return false;
            }

            if ( ! isset($cache['users'][$user_id])) {
                $user = get_userdata($user_id);

                if (
                    empty($user)
                    || empty($user->user_email)
                    || $project->get_meta('disable_notifications_' . $user_id) === "on"
                ) {
                    $cache['unsubscribbedUsers'][$user_id] = 1;

                    return false;
                }

                $cache['users'][$user_id] = $user;
            }

            return true;
        };

        $changedData = [];

        if ( ! isset($data[0])) {
            $data['id'] = $_POST['editing'];
            $data       = [$data];
        }

        foreach ($data as $item) {
            $item = json_decode(json_encode($item));
            if ( ! isset($item->assigned_to)) {
                continue;
            }

            $assignees = is_array($item->assigned_to) ? $item->assigned_to : (array)$item->assigned_to;
            $assignees = array_filter(array_map('intval', $assignees));

            if (count($assignees) === 0) {
                continue;
            }

            foreach ($assignees as $assignee) {
                if ($canUserReceiveNotifications($assignee)) {
                    foreach ($originalData as $originalDataItem) {
                        $originalDataItem = json_decode(json_encode($originalDataItem));
                        if ($originalDataItem->id === $item->id) {
                            if (isset($originalDataItem->assigned_to)) {
                                $originalDataItemAssignees = is_array($originalDataItem->assigned_to) ? $originalDataItem->assigned_to : (array)$originalDataItem->assigned_to;
                                $originalDataItemAssignees = array_filter(array_map('intval',
                                    $originalDataItemAssignees));

                                if ( ! in_array($assignee, $originalDataItemAssignees)) {
                                    if ( ! isset($changedData[$assignee])) {
                                        $changedData[$assignee] = [
                                            $dataType => [],
                                        ];
                                    }

                                    array_push($changedData[$assignee][$dataType], $item);
                                }
                            }
                        }
                    }
                }
            }
        }

        if ((bool)$sendEmails) {
            self::turnChangesIntoNotifications($changedData);
        }

        return $changedData;
    }

    /**
     * Enqueue script dependencies.
     *
     * @since   1.1.0
     * @static
     */
    public static function enqueueScripts()
    {
        $postType = get_post_type();

        if ($postType === 'project') {
            if (is_admin()) {
                add_thickbox();

                $jsIdentifier   = 'upstream-email-notifications:admin-metabox.project';
                $jsFilename     = 'admin-metabox-project.js';
                $jsRequirements = ['upstream-project'];
            } else {
                $jsIdentifier     = 'upstream-email-notifications:frontend-metabox.project';
                $jsFilename       = 'frontend-metabox-project.js';
                $jsRequirements[] = 'up-frontend-edit';
                $jsRequirements   = ['upstream'];
            }

            wp_enqueue_script($jsIdentifier,
                plugin_dir_url(UPSTREAM_EMAIL_NOTIFICATIONS_PLUGIN) . 'assets/js/' . $jsFilename, $jsRequirements,
                UPSTREAM_EMAIL_NOTIFICATIONS_VERSION, true);
            wp_localize_script($jsIdentifier, 'l', [
                'LB_ONE_DAY_BEFORE'     => __('A day before', 'upstream-email-notifications'),
                'LB_TWO_DAYS_BEFORE'    => __('Two days before', 'upstream-email-notifications'),
                'LB_THREE_DAYS_BEFORE'  => __('Three days before', 'upstream-email-notifications'),
                'LB_ONE_WEEK_BEFORE'    => __('A week before', 'upstream-email-notifications'),
                'LB_TWO_WEEKS_BEFORE'   => __('Two weeks before', 'upstream-email-notifications'),
                'MSG_NO_DATA_FOUND'     => __('No data found.', 'upstream-email-notifications'),
                'MSG_LOADING_REMINDERS' => __('Loading reminders...', 'upstream-email-notifications'),
                'MSG_RELOAD_PAGE'       => __('Invalid type. Please, reload the page and try again.',
                    'upstream-email-notifications'),
                'MSG_NOT_SAVED_YET'     => __('Not saved yet', 'upstream-email-notifications'),
            ]);
        }
    }
}
