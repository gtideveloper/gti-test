<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\WIP;

/**
 * Main UpStream Frontend Process Class.
 *
 * @package     UpStream\Plugins\FrontendEdit
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class UpStream_Frontend_Process
{
    const RESPONSE_SUCCESS_KEY = 'success';

    const RESPONSE_ERROR_KEY = 'error';

    const RESPONSE_DATA_KEY = 'data';

    protected static $_instance = null;
    /**
     * Project ID
     *
     * @var int
     * @since 1.0
     */
    public $ID = 0;

    /**
     * Meta prefix
     *
     * @var string
     * @since 1.0
     */
    public $meta_prefix = '_upstream_project_';

    /**
     * Posted data
     *
     * @var int
     * @since 1.0
     */
    public $posted;

    /**
     * The meta field - task, bug, file, message
     *
     * @var int
     * @since 1.0
     */
    public $type;

    /**
     * Are we editing an item or publishing a new one
     *
     * @var int
     * @since 1.0
     */
    public $edit_or_publish;
    public $message;

    public function __construct()
    {
        $this->init_hooks();
        $this->form_processing();
    }

    /**
     * Hook into actions and filters.
     *
     * @since  1.0.0
     */
    private function init_hooks()
    {
        add_action('upstream_single_project_before_overview', [$this, 'display_message']);

        add_action('wp_ajax_upstream.frontend-edit:delete_item', [$this, 'deleteItem']);
        add_action('wp_ajax_upstream.frontend-edit:fetch_item', [$this, 'fetchItem']);
        add_action('wp_ajax_upstream.frontend-edit:fetch_project', [$this, 'fetchProject']);
        add_action('wp_ajax_upstream.frontend-edit:save_project', [$this, 'saveProject']);
        add_action('wp_ajax_upstream.frontend-edit:fetch_client_users', [$this, 'fetchClientUsers']);
        
        add_action('wp_ajax_upstream.frontend-edit:delete_project', [$this, 'deleteProject']);
        add_action('wp_ajax_upstream.frontend-edit:cancel_edit', [$this, 'cancelEdit']);
    }

    /**
     * Start the form processing
     *
     * @since  1.0.0
     */
    public function form_processing()
    {
        // We skip if the request is not coming from UpStream
        if ( ! isset($_POST['upstream-nonce']) || ! isset($_POST['type']) || self::isDoingAjax()) {
            return;
        }

        $this->setup(esc_html($_POST['type']));

        if ($_POST['type'] !== 'discussion') {
            try {
                $this->add_edit_frontend_item();
            } catch (\Exception $exception) {
                $this->message['status']  = "danger";
                $this->message['message'] = $exception->getMessage();

                $_SESSION['upstream-frontend-edit'] = [
                    'status'  => $this->message['status'],
                    'message' => $this->message['message'],
                ];

            }
        }
    }

    /**
     * @return bool
     */
    protected static function isDoingAjax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * Setup our data after an ajax function is executed
     *
     * @param $field string the meta field (without prefix)
     *
     * @since 1.0.0
     */
    public function setup($type)
    {
        $this->posted = $_POST;
        $this->type   = $type;
        $this->ID     = $this->get_id();

        $this->edit_or_publish = 'publish';
        $this->item_id         = null;

        if (isset($this->posted['editing'])) {
            $itemId = trim($this->posted['editing']);

            if (strlen($itemId) > 0) {
                $this->edit_or_publish = 'edit';
                $this->item_id         = $itemId;
            }

            unset($itemId);
        }
    }

    /**
     * Get the id
     *
     * @return int
     * @since 1.0.0
     */
    public function get_id()
    {
        // go through the varous ways of getting the id
        // may need to check the order more closely to ensure we get it right
        $id = 0;
        if ( ! $id) {
            $id = get_the_ID();
        }
        if ( ! $id) {
            $id = isset($_POST['post_ID']) ? $_POST['post_ID'] : 0;
        }
        if ( ! $id) {
            $id = isset($_POST['post_id']) ? $_POST['post_id'] : 0;
        }
        if ( ! $id) {
            $id = isset($_POST['post-id']) ? $_POST['post-id'] : 0;
        }
        if ( ! $id) {
            $id = isset($this->posted['post_id']) ? $this->posted['post_id'] : 0;
        }

        return absint($id);
    }

    /**
     * Adds or edits a new item such as bug or task
     *
     * @since  1.0.0
     */
    private function add_edit_frontend_item()
    {
        $return   = $this->do_the_checks();
        $messages = $this->messages();
        $data     = [];
        $output   = null;

        // if there are no errors
        if (isset($return['status']) && $return['status'] == self::RESPONSE_SUCCESS_KEY) :

            do_action('frontend_before_item_save', $_POST['post_id'], null);

            $itemColumns = call_user_func('\UpStream\Frontend\get' . ucfirst($this->type) . 'Fields');

            $post = &$this->posted[self::RESPONSE_DATA_KEY];

            foreach ($itemColumns as $columnName => $columnArgs) {
                if (isset($columnArgs['isEditable'])
                    && (bool)$columnArgs['isEditable'] === false
                ) {
                    continue;
                }

                if ( ! isset($post[$columnName])) {
                    $data[$columnName] = '';
                    continue;
                }

                if ($columnArgs['type'] === 'date') {
                    $columnValue = $post[$columnName . '_timestamp'];
                } elseif ($columnArgs['type'] === 'file') {
                    $fileRemovalFlagName = '@' . $columnName . '__remove';
                    $columnIdName        = $columnName . '_id';
                    if (isset($post[$fileRemovalFlagName]) && (bool)$post[$fileRemovalFlagName] === true) {
                        $data[$columnIdName] = '';
                        $columnValue         = '';
                    } else {
                        $columnValue = '';
                        if (isset($post[$columnIdName]) && is_numeric($post[$columnIdName])) {
                            $columnIdValue = (int)$post[$columnIdName];
                            if ($columnIdValue > 0) {
                                $data[$columnIdName] = $columnIdValue;
                                $columnValue         = $post[$columnName];
                            } else {
                                $data[$columnIdName] = '';
                            }

                            unset($columnIdValue);
                        }
                    }
                    unset($columnIdName, $fileRemovalFlagName);
                } elseif ($columnArgs['type'] === 'wysiwyg') {
                    $columnValue = trim(htmlentities($post[$columnName]));
                } elseif (in_array($columnArgs['type'], ['int', 'integer'])) {
                    $columnValue = (int)$post[$columnName];
                } elseif (($columnArgs['type'] === 'user')) {
                    $columnValue = array_map('intval', array_unique(array_filter((array)$post[$columnName])));
                } elseif (in_array($columnArgs['type'], ['float', 'decimal', 'percentage'])) {
                    $columnValue = (float)$post[$columnName];
                } elseif (in_array($columnArgs['type'],
                    ['array', 'radio', 'radio_inline', 'checkbox', 'multicheck', 'multicheck_inline', 'taxonomies'])) {
                    $columnValue = array_filter((array)$post[$columnName]);
                } else {
                    $columnValue = trim(sanitize_text_field($post[$columnName]));
                }

                $data[$columnName] = $columnValue;
            }

            unset($post);

            $project_id       = upstream_post_id();
            $user             = wp_get_current_user();
            $project_owner_id = (int)upstream_project_owner_id($project_id);


            // old permissions begin

            $userCanEditItem  = count(array_intersect($user->roles,
                    ['administrator', 'upstream_manager'])) > 0 || $project_owner_id === (int)$user->ID;

            if ( ! $userCanEditItem) {
                if ($this->edit_or_publish === "publish") {
                    $userCanEditItem = current_user_can('publish_project_' . $this->type);
                } else {

                    $item = null;
                    try {
                        $project = new UpStream_Project($project_id);
                        $item = $project->get_item_by_id($this->item_id, $this->type);
                    } catch (\Exception $e) {}

                    if ($item && (
                        (
                            isset($item['assigned_to'])
                            && in_array((int)$user->ID, (array)$item['assigned_to'])
                        ) ||
                        (isset($item['created_by']) && (int)$item['created_by'] === (int)$user->ID))
                    ) {
                        $userCanEditItem = true;
                    } elseif (
                        (
                            isset($data['assigned_to'])
                            && in_array((int)$user->ID, (array)$data['assigned_to'])
                        ) ||
                        (isset($data['created_by']) && (int)$data['created_by'] === (int)$user->ID)
                    ) {
                        $userCanEditItem = true;
                    }

                }
            }

            // old permissions end

            if ($this->edit_or_publish === 'publish') {
                $userCanEditItem = upstream_override_access_object($userCanEditItem, $this->type, 0, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, UPSTREAM_PERMISSIONS_ACTION_CREATE);
            } else {
                $userCanEditItem = upstream_override_access_object($userCanEditItem, $this->type, $this->posted['editing'], UPSTREAM_ITEM_TYPE_PROJECT, $project_id, UPSTREAM_PERMISSIONS_ACTION_EDIT);
            }

            if ( ! $userCanEditItem) {
                $this->message['status']  = "danger";
                $this->message['message'] = __("You're not allowed to do this.", 'upstream');

                try {
                    if (!session_id()) {
                        session_start();
                    }
                } catch (\Exception $e) {

                }

                $_SESSION['upstream-frontend-edit'] = [
                    'status'  => $this->message['status'],
                    'message' => $this->message['message'],
                ];

                header('Location: ' . get_permalink($this->ID));
                exit();
            }

            /*
             * If we have files uploaded
             */
            if ( ! empty($_FILES)) {
                $this->add_files();
            }

            $dateFields = ['start_date', 'end_date', 'due_date'];
            foreach ($dateFields as $dateFieldName) {
                $dateFieldValue    = isset($data[$dateFieldName]) ? (string)$data[$dateFieldName] : '';
                $dateFieldValueLen = strlen($dateFieldValue);
                if ($dateFieldValueLen > 0) {
                    // Since these timestamps values are generated on JS, we must divide them per 1000 because JS uses
                    // milliseconds as a timestamp, whereas PHP uses seconds.
                    if ($dateFieldValueLen === 13) {
                        $dateFieldValue = (int)$dateFieldValue / 1000;
                    }

                    // Update the values.
                    $data[$dateFieldName]         = $dateFieldValue;
                    $this->posted[$dateFieldName] = $dateFieldValue;
                }
            }

            // unset the values we don't want saved
            // unset( $data['type'], $data['post_id'], $data['_wp_http_referer'], $data['editing'], $data['row'], $data['upstream-nonce'], $data['upstream-files-nonce'] );

            // Add the created_at data if adding the item.
            if ($this->edit_or_publish !== 'edit') {
                if (isset($itemColumns['created_at'])) {
                    $data['created_at'] = time();
                }
            }

            /*
             * insert or edit the meta
             */
            if ($this->edit_or_publish == 'edit') {
                $updated = $this->edit_meta($data);
                $return  = $messages['item_edited'];

                // if theres a problem adding the data
                if ( ! isset($updated) || empty($updated)) {
                    $return = [];
                }

            } else {
                $updated = $this->insert_meta($data);

                // if theres a problem adding the data
                if ( ! isset($updated) || empty($updated)) {
                    $return = $messages['item_not_added'];
                }
            }

            // run this to update all missing meta data such as
            // created date, id, project members, progress etc
            $project = new UpStream_Project($this->ID);
            $project->update_project_meta($this->type, true);

        endif;

        // To display the message
        $this->message['status']  = isset($return['status']) ? $return['status'] : '';
        $this->message['message'] = isset($return['message']) ? $return['message'] : '';

        try {
            if (!session_id()) {
                session_start();
            }
        } catch (\Exception $e) {

        }

        $_SESSION['upstream-frontend-edit'] = [
            'status'  => $this->message['status'],
            'message' => $this->message['message'],
        ];

        do_action('frontend_after_item_save', $_POST['post_id']);

        header('Location: ' . get_permalink($this->ID));
        exit();
    }

    /**
     * Do our checks when adding or editing items
     *
     * @return array    Array of messages
     * @since  1.0
     * @access private
     */
    private function do_the_checks()
    {

        // get messages and set up our default message
        $messages = $this->messages();
        $return   = $messages['item_added'];

        // check nonce
        if ( ! wp_verify_nonce($_POST['upstream-nonce'], 'upstream_security')) {
            $return = $messages['nonce_error'];
        }

        // check user permissions
        if ( ! upstream_permissions("{$this->edit_or_publish}_project_{$this->type}", $this->item_id)) {
            $return = $messages['permission_error'];
        }

        // make sure all required fields are here
        if ( ! isset($this->ID) || empty($this->ID)) {
            $return = $messages['empty_error'];
        }

        if ( ! isset($_POST['type']) || ! in_array($_POST['type'], ['milestones', 'tasks', 'bugs', 'files'])) {
            $return = __('Invalid type.', 'upstream-frontend-edit');
        }

        return $return;
    }

    /**
     * Array of messages.
     * Status is used for both the CSS class on front end
     * and also for some checks such as ['status'] == 'success'.
     *
     * @return array    Array of messages
     * @since  1.0
     * @access private
     */
    private function messages()
    {

        $messages = [
            'item_added'       => [
                'status'  => self::RESPONSE_SUCCESS_KEY,
                'message' => __('Success! Your item has been added.', 'upstream-frontend-edit'),
            ],
            'item_edited'      => [
                'status'  => self::RESPONSE_SUCCESS_KEY,
                'message' => __('Your item has been successfully updated.', 'upstream-frontend-edit'),
            ],
            'item_not_added'   => [
                'status'  => 'danger',
                'message' => __('There was a problem adding this item.', 'upstream-frontend-edit'),
            ],
            'item_deleted'     => [
                'status'  => self::RESPONSE_SUCCESS_KEY,
                'message' => __('Your item has been successfully deleted.', 'upstream-frontend-edit'),
            ],
            'item_not_deleted' => [
                'status'  => 'danger',
                'message' => __('There was a problem deleting this item.', 'upstream-frontend-edit'),
            ],
            'nonce_error'      => [
                'status'  => 'danger',
                'message' => __('There was an error.', 'upstream-frontend-edit'),
            ],
            'permission_error' => [
                'status'  => 'danger',
                'message' => __("You don't have permission for this.", 'upstream-frontend-edit'),
            ],
            'empty_error'      => [
                'status'  => 'danger',
                'message' => __('Looks like there were some empty fields.', 'upstream-frontend-edit'),
            ],
        ];

        return apply_filters('upstream_frontend_messages', $messages);

    }

    /**
     * Adds new files
     *
     * @since  1.0.0
     */
    private function add_files()
    {
        $fileData = [];

        if ( ! empty($_FILES)
             && isset($_FILES['file'])
        ) {
            $fileData = $this->handle_upload($_FILES['file']);
        }

        if (is_array($fileData) && ! empty($fileData)) {
            $this->posted['file']    = sanitize_text_field($fileData['url']);
            $this->posted['file_id'] = absint($fileData['id']);
        }
    }

    /*
     * Handles the WP side of the upload such as ensuring correct
     * file paths, creating the attachment post and adding metadata etc
     */
    private function handle_upload($file = [])
    {

        require_once(ABSPATH . 'wp-admin/includes/admin.php');

        $file_return = wp_handle_upload($file, ['test_form' => false]);

        if (isset($file_return[self::RESPONSE_ERROR_KEY]) || isset($file_return['upload_error_handler'])) {

            return false;

        } else {

            $filename   = $file_return['file'];
            $attachment = [
                'post_mime_type' => $file_return['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content'   => '',
                'post_status'    => 'inherit',
                'guid'           => $file_return['url'],
            ];

            $attachment_id = wp_insert_attachment($attachment, $file_return['url']);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $filename);
            wp_update_attachment_metadata($attachment_id, $attachment_data);

            if (0 < intval($attachment_id)) {
                $file_return['id'] = $attachment_id;

                return $file_return;
            }

        }

        return false;

    }

    /**
     * Edit a single meta entry for an item type
     *
     * @param string $data The sanitized data that needs to be inserted into the database
     *
     * @return string         The index of the item that has been updated
     * @since  1.0
     * @access private
     *
     */
    private function edit_meta($data)
    {
        if (empty($data)) {
            return false;
        }

        // If Milestone, we process the data to the Milestone post, not to the project.
        if ($this->type === 'milestones') {
            $milestone = \UpStream\Factory::getMilestone((int)$this->posted['editing']);

            if (is_object($milestone)) {

                // Create a fake rowset until we are able to refactor all the plugins.
                $fakeRowset = [$this->posted['editing'] => $milestone->convertToLegacyRowset()];
                $fakeRowset = apply_filters('upstream.frontend-edit:project.onBeforeEditMeta',
                    $fakeRowset,
                    (int)$this->ID,
                    $this->type,
                    ''
                );

                do_action('upstream_email_notifications_before_edit_milestone', (int)$this->posted['editing'],
                    $fakeRowset, $data, $this->ID);

                do_action('upstream_email_notifications_detect_changes', $this->type, $data, $this->ID, true);

                if ( ! isset($data['color']) || empty($data['color'])) {
                    $data['color'] = \UpStream\Milestone::DEFAULT_COLOR;
                }

                $mid = (int)$this->posted['editing'];
                if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_MILESTONE, $mid, UPSTREAM_ITEM_TYPE_PROJECT, $this->ID, 'milestone', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                    $milestone->setName($data['milestone']);
                }
                if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_MILESTONE, $mid, UPSTREAM_ITEM_TYPE_PROJECT, $this->ID, 'assigned_to', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                    $milestone->setAssignedTo($data['assigned_to']);
                }
                if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_MILESTONE, $mid, UPSTREAM_ITEM_TYPE_PROJECT, $this->ID, 'start_date', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                    $milestone->setStartDate($data['start_date']);
                }
                if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_MILESTONE, $mid, UPSTREAM_ITEM_TYPE_PROJECT, $this->ID, 'end_date', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                    $milestone->setEndDate($data['end_date']);
                }
                if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_MILESTONE, $mid, UPSTREAM_ITEM_TYPE_PROJECT, $this->ID, 'color', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                    $milestone->setColor($data['color']);
                }
                if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_MILESTONE, $mid, UPSTREAM_ITEM_TYPE_PROJECT, $this->ID, 'notes', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                    $milestone->setNotes($data['notes']);
                }

                if ( ! upstream_disable_milestone_categories()) {
                    if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_MILESTONE, $mid, UPSTREAM_ITEM_TYPE_PROJECT, $this->ID, 'categories', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                        $milestone->setCategories($data['categories']);
                    }
                }
            }

            $updated = 1;
        } else {
            $rowset = $this->get_meta($this->type);
            if ( ! empty($rowset)) {
                foreach ($rowset as $rowIndex => $row) {
                    if ($this->posted['editing'] === $row['id']) {
                        $itemColumns = call_user_func('\UpStream\Frontend\get' . ucfirst($this->type) . 'Fields');

                        foreach ($itemColumns as $columnName => $columnArgs) {
                            if (isset($columnArgs['isEditable']) && (bool)$columnArgs['isEditable'] === false) {
                                continue;
                            }

                            if (upstream_override_access_field(true, $this->type, $row['id'], UPSTREAM_ITEM_TYPE_PROJECT, $this->ID, $columnName, UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                                if (isset($data[$columnName])) {
                                    $row[$columnName] = $data[$columnName];
                                }
                            }

                        }

                        $rowset[$rowIndex] = $row;
                    }
                }
            }

            $metaKey = $this->meta_prefix . $this->type;

            $rowset = apply_filters('upstream.frontend-edit:project.onBeforeEditMeta', $rowset, (int)$this->ID,
                $this->type, $metaKey);

            do_action('upstream_email_notifications_detect_changes', $this->type, $data, $this->ID, true);

            $updated = update_post_meta($this->ID, $metaKey, $rowset);
        }

        return (string)$updated;
    }

    /**
     * Get a meta value
     *
     * @param string $meta_key the meta field
     *
     * @return mixed
     * @since 1.0.0
     *
     */
    public function get_meta($meta_key)
    {
        $result = get_post_meta($this->ID, $this->meta_prefix . $meta_key, true);
        if ( ! $result) {
            $result = null;
        }

        return $result;
    }

    /**
     * Insert a single meta entry for the project
     *
     * @param string $data The sanitized data that needs to be inserted into the database
     *
     * @return string         The index of the item that has been inserted
     * @throws Exception
     * @since  1.0
     * @access private
     *
     */
    private function insert_meta($data)
    {
        if (empty($data)) {
            return false;
        }

        if ($this->type === 'milestones') {
            $milestone = \UpStream\Factory::createMilestone($data['milestone']);
            $milestone->setProjectId($this->ID)
                      ->setAssignedTo($data['assigned_to'])
                      ->setStartDate($data['start_date'])
                      ->setEndDate($data['end_date'])
                      ->setNotes($data['notes']);

            if (isset($data['color'])) {
                $milestone->setColor($data['color']);
            }

            if (isset($data['categories'])) {
                $milestone->setCategories($data['categories']);
            }

            $updated = 1;

            do_action('upstream_save_milestone', $milestone->getId(), $data);


        } else {
            if ($this->type === "files" || $this->type === "bugs") {
                $canProceed = $this->addUploadedFileDataToData($data);
                switch ($canProceed) {
                    case null:
                    case true:
                        $canProceed = true;
                        break;

                    default:
                        return;
                }
                unset($canProceed);
            }

            $existing = $this->get_meta($this->type);
            if (isset($existing) && ! empty($existing)) {
                $existing[] = $data;
                $meta_value = $existing;
            } else {
                $meta_value[] = $data;
            }

            do_action('upstream_email_notifications_for_new_items', $this->type, $meta_value, $this->ID, true);

            $metaKey = $this->meta_prefix . $this->type;

            $meta_value = apply_filters('upstream.frontend-edit:project.onBeforeInsertMeta', $meta_value,
                (int)$this->ID,
                $this->type, $metaKey);

            $updated = update_post_meta($this->ID, $metaKey, $meta_value);
        }


        return (string)$updated;
    }

    /**
     * Attempt to add uploaded file data to $data being used on the add/edit workflow.
     *
     * @return  null    $data is empty
     *          bool    Indicates if $data was updated
     * @since   1.3.9
     * @access  private
     *
     */
    private function addUploadedFileDataToData(&$data)
    {
        if ( ! is_array($data)
             || empty($data)
        ) {
            return null;
        }

        if ( ! $this->didUploadedFile()) {
            return false;
        }

        $data['file_id'] = $this->posted['file_id'];
        $data['file']    = $this->posted['file'];

        return true;
    }

    /**
     * Check if a file was uploaded earlier.
     *
     * @return  bool
     * @since   1.3.9
     * @access  private
     *
     */
    private function didUploadedFile()
    {
        if ( ! isset($this->posted['file'])
             || empty($this->posted['file'])
             || ! isset($this->posted['file_id'])
             || (int)$this->posted['file_id'] <= 0
        ) {
            return false;
        }

        return true;
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Fetch a given item on frontend for editing purposes.
     *
     * @since   1.5.0
     * @static
     */
    public static function fetchItem()
    {
        self::headerJsonContentType();

        $response = [
            self::RESPONSE_SUCCESS_KEY => false,
            self::RESPONSE_DATA_KEY    => [],
            self::RESPONSE_ERROR_KEY   => null,
        ];

        try {
            // Check if the request payload is potentially invalid.
            if (
                ! self::isDoingAjax()
                || empty($_GET)
                || ! isset($_GET['nonce'])
                || ! isset($_GET['project_id'])
                || ! isset($_GET['item_type'])
                || ! in_array($_GET['item_type'], ['milestone', 'task', 'bug', 'file'])
                || ! isset($_GET['item_id'])
                || ! check_ajax_referer('upstream.frontend-edit:fetch_item', 'nonce', false)
            ) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            // Check if the project potentially exists.
            $projectId = (int)$_GET['project_id'];
            if ($projectId <= 0) {
                throw new \Exception(__('Invalid Project.', 'upstream'));
            }

            $wp_lock_check = wp_check_post_lock($projectId);

            if ($wp_lock_check) {
                $user_info = get_userdata($wp_lock_check);
                throw new \Exception(__("This project is being edited by " . $user_info->user_login . ". The other user must cancel or save their work.",'upstream'));
            } else {
                $wp_lock_set = wp_set_post_lock($projectId);
            }

            $itemId = trim((string)$_GET['item_id']);
            if (strlen($itemId) === 0) {
                throw new \Exception(__('Invalid item ID.', 'upstream-frontend-edit'));
            }

            $itemType       = $_GET['item_type'];
            $capabilityName = "publish_project_{$itemType}s";
            if ( ! upstream_can_access_object($capabilityName, $itemType, $itemId, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if ($itemType === 'milestone') {
                $rowset = [0];
            } else {
                $metaName = "_upstream_project_{$itemType}s";
                $rowset   = (array)get_post_meta($projectId, $metaName, true);
            }

            if (count($rowset) > 0) {
                $item = null;

                foreach ($rowset as $row) {
                    if (isset($row['id'])
                        && $row['id'] === $itemId
                    ) {
                        $item = $row;
                        break;
                    }
                }

                if ($itemType === 'milestone') {
                    $milestone = \UpStream\Factory::getMilestone($itemId);
                    $item      = $milestone->convertToLegacyRowset();
                }

                if ($item !== null) {
                    $item['comments'] = [];

                    if ( ! upstream_are_comments_disabled($projectId)) {
                        $usersCache  = [];
                        $usersRowset = get_users([
                            'fields' => [
                                'ID',
                                'display_name',
                            ],
                        ]);

                        foreach ($usersRowset as $userRow) {
                            $userRow->ID = (int)$userRow->ID;

                            $usersCache[$userRow->ID] = (object)[
                                'id'     => $userRow->ID,
                                'name'   => $userRow->display_name,
                                'avatar' => getUserAvatarURL($userRow->ID),
                            ];
                        }
                        unset($userRow, $usersRowset);

                        $dateFormat        = get_option('date_format');
                        $timeFormat        = get_option('time_format');
                        $theDateTimeFormat = $dateFormat . ' ' . $timeFormat;
                        $currentTimestamp  = time();

                        $user                     = wp_get_current_user();
                        $userHasAdminCapabilities = isUserEitherManagerOrAdmin($user);
                        $userCanReply             = ! $userHasAdminCapabilities ? user_can($user,
                            'publish_project_discussion') : true;

                        $userCanReply = upstream_override_access_field($userCanReply, $itemType, $itemId, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'comments', UPSTREAM_PERMISSIONS_ACTION_EDIT);


                        $userCanModerate          = ! $userHasAdminCapabilities ? user_can($user,
                            'moderate_comments') : true;

                        $userCanDelete            = ! $userHasAdminCapabilities ? $userCanModerate || user_can($user,
                                'delete_project_discussion') : true;

                        $userCanDelete = upstream_override_access_field($userCanDelete, $itemType, $itemId, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'comments', UPSTREAM_PERMISSIONS_ACTION_DELETE);

                        $commentsStatuses = ['approve'];
                        if ($userHasAdminCapabilities || $userCanModerate) {
                            $commentsStatuses[] = 'hold';
                        }

                        $comments = get_comments([
                            'post_id'    => $projectId,
                            'status'     => $commentsStatuses,
                            'meta_query' => [
                                'relation' => 'AND',
                                [
                                    'key'   => 'type',
                                    'value' => $itemType,
                                ],
                                [
                                    'key'   => 'id',
                                    'value' => $item['id'],
                                ],
                            ],
                        ]);

                        if (count($comments) > 0) {
                            $commentsCache = [];
                            foreach ($comments as $comment) {
                                $author = $usersCache[(int)$comment->user_id];

                                $date = \DateTime::createFromFormat('Y-m-d H:i:s', $comment->comment_date_gmt);

                                $commentData = json_decode(json_encode([
                                    'id'             => (int)$comment->comment_ID,
                                    'parent_id'      => (int)$comment->comment_parent,
                                    'content'        => $comment->comment_content,
                                    'state'          => $comment->comment_approved,
                                    'created_by'     => $author,
                                    'created_at'     => [
                                        'localized' => "",
                                        'humanized' => sprintf(
                                            _x('%s ago', '%s = human-readable time difference', 'upstream'),
                                            human_time_diff($date->getTimestamp(), $currentTimestamp)
                                        ),
                                    ],
                                    'currentUserCap' => [
                                        'can_reply'    => $userCanReply,
                                        'can_moderate' => $userCanModerate,
                                        'can_delete'   => $userCanDelete || $author->id === $user->ID,
                                    ],
                                    'replies'        => [],
                                ]));

                                $commentData->created_at->localized = $date->format($theDateTimeFormat);

                                $commentsCache[$commentData->id] = $commentData;
                            }

                            foreach ($commentsCache as $comment) {
                                if ($comment->parent_id > 0) {
                                    if (isset($commentsCache[$comment->parent_id])) {
                                        $commentsCache[$comment->parent_id]->replies[] = $comment;
                                    } else {
                                        unset($commentsCache[$comment->id]);
                                    }
                                }
                            }

                            foreach ($commentsCache as $comment) {
                                if ($comment->parent_id === 0) {
                                    ob_start();
                                    upstream_display_message_item($comment, []);
                                    $item['comments'][] = trim(ob_get_contents());
                                    ob_end_clean();
                                }
                            }
                        }
                    }

                    $canViewComments = upstream_override_access_field(true, $itemType, $itemId, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'comments', UPSTREAM_PERMISSIONS_ACTION_VIEW);
                    $canEditComments = upstream_override_access_field(true, $itemType, $itemId, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'comments', UPSTREAM_PERMISSIONS_ACTION_EDIT);

                    $row = [
                        'id'                    => $item['id'],
                        'canViewComments'       => $canViewComments,
                        'canEditComments'       => $canEditComments,
                        'comments'              => $canViewComments ? $item['comments'] : [],
                        self::RESPONSE_DATA_KEY => [],
                    ];

                    $itemColumns = call_user_func('\UpStream\Frontend\get' . ucfirst($itemType) . 'sFields');
                   
                    foreach ($itemColumns as $columnName => $columnArgs) {
                        if (isset($columnArgs['isEditable']) && (bool)$columnArgs['isEditable'] === false) {
                            continue;
                        }

                        $columnType = $columnArgs['type'];
                        $columnValue = isset($item[$columnName]) ? $item[$columnName] : '';

                        $row[self::RESPONSE_DATA_KEY][$columnName] = [
                            'type' => $columnType,
                        ];

                        if ($columnType === 'date') {
                            $columnValue = (int)$columnValue > 0 ? (int)$columnValue : 0;

                            if ($columnValue > 0) {

                                // RSD: this is added to keep the numbers right
                                // TODO: it should be removed at some point
                                $offset = get_option('gmt_offset');
                                $columnValue = $columnValue + ($offset > 0 ? $offset * 60 * 60 : 0);
                                // END RSD

                                $date = new DateTime("@{$columnValue}");
                                $columnValue = (int)$date->format('U');
                            }
                        } elseif ($columnType === 'wysiwyg') {
                            $columnValue = strlen($columnValue) > 0 ? html_entity_decode($columnValue) : '';
                        } elseif (in_array($columnType, ['int', 'integer', 'progress'])) {
                            $columnValue = (int)$columnValue;
                        } elseif ($columnType === 'array' || $columnType === 'taxonomies' || $columnType === 'user') {
                            if ($columnName === 'reminders') {

                            } else {

                                $columnValue = array_map(function ($x) {
                                    if (is_numeric($x) && filter_var($x, FILTER_VALIDATE_INT) !== false) {
                                        return intval($x);
                                    } else {
                                        return $x;
                                    }
                                    }, array_unique(array_filter((array)$columnValue)));
                            }
                        } elseif ($columnType === 'file') {
                            if (strlen((string)$columnValue) > 0) {
                                global $wpdb;

                                $attachment = $wpdb->get_row(sprintf(
                                    'SELECT `ID` AS `id`, `post_title` AS `title`, `post_mime_type` AS `mime_type`
                                       FROM `%s`
                                      WHERE `guid` = "%s"',
                                    $wpdb->prefix . 'posts',
                                    $columnValue
                                ));

                                if (!empty($attachment)) {
                                    $row[self::RESPONSE_DATA_KEY][$columnName]['id'] = $attachment->id;
                                    $row[self::RESPONSE_DATA_KEY][$columnName]['title'] = $attachment->title;
                                    $row[self::RESPONSE_DATA_KEY][$columnName]['meta'] = wp_get_attachment_metadata($attachment->id);
                                    $row[self::RESPONSE_DATA_KEY][$columnName]['mime_type'] = $attachment->mime_type;
                                }

                                unset($attachment);
                            }
                        }

                        $field = $columnName;

                        $row[self::RESPONSE_DATA_KEY][$columnName]['has_edit_permission'] = upstream_override_access_field(true, $itemType, $itemId, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, $field, UPSTREAM_PERMISSIONS_ACTION_EDIT);
                        $row[self::RESPONSE_DATA_KEY][$columnName]['has_view_permission'] = upstream_override_access_field(true, $itemType, $itemId, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, $field, UPSTREAM_PERMISSIONS_ACTION_VIEW);

                        if ($row[self::RESPONSE_DATA_KEY][$columnName]['has_view_permission']) {
                            $row[self::RESPONSE_DATA_KEY][$columnName]['value'] = $columnValue;
                        }
                    }

                    $response[self::RESPONSE_DATA_KEY] = $row;
                } else {
                    throw new \Exception(__('Item not found.', 'upstream-frontend-edit'));
                }
            } else {
                throw new \Exception(__('Item not found.', 'upstream-frontend-edit'));
            }

            $response[self::RESPONSE_SUCCESS_KEY] = true;
        } catch (\Exception $e) {
            $response[self::RESPONSE_ERROR_KEY] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * Add the content-type for json.
     */
    protected static function headerJsonContentType()
    {
        header('Content-Type: application/json');
    }

    /*
     * Handles the WP side of the upload such as ensuring correct
     * file paths, creating the attachment post and adding metadata etc
     */

    /**
     * @param $projectId
     * @param $itemId
     *
     * @return array
     */
    public static function getMilestoneData($projectId, $itemId)
    {
        $data = [];

        $milestone = \UpStream\Factory::getMilestone($itemId);

        if ( ! empty($milestone)) {
            $data[''];
        }

        return $data;
    }

    /**
     * Generates a random string of custom length.
     *
     * @param int    $length    The length of the random string.
     * @param string $charsPool The characters that might compose the string.
     *
     * @return  string
     * @since   1.3.8
     * @static
     *
     */
    public static function randomString(
        $length,
        $charsPool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ) {
        $randomString       = "";
        $maxCharsPoolLength = mb_strlen($charsPool, '8bit') - 1;

        for ($lengthIndex = 0; $lengthIndex < $length; ++$lengthIndex) {
            $randomString .= $charsPool[random_int(0, $maxCharsPoolLength)];
        }

        return $randomString;
    }

    /**
     * @param $project_id   The project ID to delete
     * @return true if the current user can delete the project, false otherwise
     */
    public static function canDeleteProject($project_id)
    {
        if (!$project_id) {
            return false;
        }

        $can_delete = false;
        if (upstream_project_owner_id($project_id) == upstream_current_user_id()) {
            $can_delete = current_user_can('delete_projects');
        }
        else {
            $can_delete = current_user_can('delete_others_projects');
        }

        return upstream_override_access_object($can_delete, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, null, 0, UPSTREAM_PERMISSIONS_ACTION_DELETE);

    }

    /**
     * Frontend AJAX endpoint responsible for deleting a given project.
     *
     * @since 1.0.0
     * @static
     */

    public static function deleteProject()
    {
        $permission = check_ajax_referer('my_delete_post_nonce', 'nonce', false);
        if ($permission == false) {
            echo 'error';
        } else {

            if (!self::canDeleteProject($_REQUEST['id'])) {
                $_SESSION['upstream-frontend-edit'] = [
                    'status'  => 'danger',
                    'message' => __("You're not allowed to do this.", 'upstream'),
                ];

            }
            else {
                wp_delete_post($_REQUEST['id']);

                $_SESSION['upstream-frontend-edit'] = [
                    'status'  => 'success',
                    'message' => __('This project has been deleted.', 'upstream-frontend-edit'),
                ];
            }

            echo site_url("/" . upstream_get_project_base());
        }
        die();
    }

    /**
     * Frontend AJAX endpoint responsible for deleting a given item.
     *
     * @since   1.5.0
     * @static
     */
    public static function deleteItem()
    {
        self::headerJsonContentType();

        $response = [
            self::RESPONSE_SUCCESS_KEY => false,
            self::RESPONSE_ERROR_KEY   => null,
        ];


        try {
            // Check if the request payload is potentially invalid.
            if (
                ! self::isDoingAjax()
                || empty($_POST)
                || ! isset($_POST['nonce'])
                || ! check_ajax_referer('upstream.frontend-edit:delete_item', 'nonce', false)
                || ! isset($_POST['post_id'])
                || ! isset($_POST['item_type'])
                || ! in_array($_POST['item_type'], ['milestone', 'task', 'bug', 'file'])
                || ! isset($_POST['item_id'])
            ) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            // Check if the project potentially exists.
            $projectId = (int)$_POST['post_id'];
            if ($projectId <= 0) {
                throw new \Exception(__('Invalid Project.', 'upstream'));
            }

            $itemId = trim((string)$_POST['item_id']);
            if (strlen($itemId) === 0) {
                throw new \Exception(__('Invalid item ID.', 'upstream-frontend-edit'));
            }

            $itemType       = $_POST['item_type'];
            $capabilityName = "delete_project_{$itemType}s";
            if ( ! upstream_can_access_object($capabilityName, $itemType, $itemId, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, UPSTREAM_PERMISSIONS_ACTION_DELETE)) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if ($itemType === 'milestone') {
                $milestone = \UpStream\Factory::getMilestone($itemId);

                if ( ! empty($milestone)) {
                    $milestone->delete();
                }
            } else {
                $metaName = "_upstream_project_{$itemType}s";
                $rowset   = (array)get_post_meta($projectId, $metaName, true);
                if (count($rowset) > 0) {
                    $item = false;

                    foreach ($rowset as $rowIndex => $row) {
                        if (isset($row['id'])
                            && $row['id'] === $itemId
                        ) {
                            $item = $row;
                            unset($rowset[$rowIndex]);

                            break;
                        }
                    }

                    $rowset = array_values($rowset);

                    if (empty($item)) {
                        throw new \Exception(__('Item not found.', 'upstream-frontend-edit'));
                    } else {
                        update_post_meta($projectId, $metaName, $rowset);

                        // If milestone, remove it from it's tasks
                        if ('milestone' === $itemType) {
                            // Get the tasks
                            $tasks = (array)get_post_meta($projectId, '_upstream_project_tasks', true);
                            if (count($tasks) > 0) {
                                $updated = false;

                                foreach ($tasks as &$task) {
                                    if (isset($task['milestone']) && $task['milestone'] === $itemId) {
                                        $task['milestone'] = '';
                                        $updated           = true;
                                    }
                                }

                                if ($updated) {
                                    update_post_meta($projectId, '_upstream_project_tasks', $tasks);
                                }
                            }
                        }

                        global $wpdb;

                        // Fetch all item comments.
                        $commentsIds = (array)$wpdb->get_col(sprintf('
                        SELECT `comment`.`comment_ID` AS `id`
                          FROM `%s` AS `comment`
                            LEFT JOIN `%s` AS `metaType` ON `metaType`.`comment_ID` = `comment`.`comment_ID`
                            LEFT JOIN `%2$s` AS `metaId` ON `metaId`.`comment_ID` = `comment`.`comment_ID`
                          WHERE `comment`.`comment_post_ID` = "%3$d"
                            AND `metaType`.`meta_key` = "type"
                            AND `metaType`.`meta_value` = "%4$s"
                            AND `metaId`.`meta_key` = "id"
                            AND `metaId`.`meta_value` = "%5$s"',
                            $wpdb->prefix . 'comments',
                            $wpdb->prefix . 'commentmeta',
                            $projectId,
                            $itemType,
                            $itemId
                        ));

                        unset($item['comments']);

                        if (count($commentsIds) > 0) {
                            $commentsIdsImploded = implode(',', $commentsIds);

                            // Delete all item comments metas.
                            $wpdb->query(sprintf(
                                'DELETE FROM %s WHERE `comment_id` IN (%s)',
                                $wpdb->prefix . 'commentmeta',
                                $commentsIdsImploded
                            ));

                            // Delete all item comments.
                            $wpdb->query(sprintf(
                                'DELETE FROM %s WHERE `comment_ID` IN (%s)',
                                $wpdb->prefix . 'comments',
                                $commentsIdsImploded
                            ));
                        }

                        $activity = \UpStream\Factory::getActivity();
                        $activity->add_activity($projectId, $metaName, 'remove', $item);
                    }
                }
            }

            $response[self::RESPONSE_SUCCESS_KEY] = true;
        } catch (\Exception $e) {
            $response[self::RESPONSE_ERROR_KEY] = $e->getMessage();
        }

        $response['message'] = $response[self::RESPONSE_SUCCESS_KEY] ? __('The item was deleted successfully.', 'upstream-frontend-edit') : $response[self::RESPONSE_ERROR_KEY];

        wp_send_json($response);
    }

    /**
     * Fetch a given project on frontend for editing purposes.
     *
     * @since   1.5.0
     * @static
     */
    public static function fetchProject()
    {
        self::headerJsonContentType();

        $response = [
            self::RESPONSE_SUCCESS_KEY => false,
            self::RESPONSE_DATA_KEY    => [],
            self::RESPONSE_ERROR_KEY   => null,
            'action'                   => 'edit',
        ];

        try {
            // Check if the request payload is potentially invalid.
            if (
                ! self::isDoingAjax()
                || empty($_GET)
                || ! isset($_GET['nonce'])
                || ! isset($_GET['project_id'])
                || ! check_ajax_referer('upstream.frontend-edit:fetch_project', 'nonce', false)
            ) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            // Check if the project potentially exists.
            $projectId = (int)$_GET['project_id'];

            if ( ! upstream_can_access_object('edit_projects', UPSTREAM_ITEM_TYPE_PROJECT,
                empty($projectId) ? 0 : $projectId, null, 0,
                empty($projectId) ? UPSTREAM_PERMISSIONS_ACTION_CREATE : UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            // Check if the user is trying to add a new project
            if (empty($projectId)) {
                $post_data = [
                    'post_type'    => 'project',
                    'post_title'   => __('Auto Draft', 'upstream'),
                    'post_content' => '',
                    'post_status'  => 'auto-draft',
                ];

                $projectId = wp_insert_post($post_data);

                $response['action'] = 'add';
            }

            $wp_lock_check = wp_check_post_lock($projectId);

            if ($wp_lock_check) {
                $user_info = get_userdata($wp_lock_check);
                throw new \Exception(__("This project is being edited by " . $user_info->user_login . ". The other user must cancel or save their work.", 'upstream'));
            } else {
                $wp_lock_set = wp_set_post_lock($projectId);
            }

            $project = new UpStream_Project($projectId);

            if (empty($project)) {
                throw new \Exception(__('Project not found.', 'upstream-frontend-edit'));
            }

            $client_id = $project->get_meta('client');

            $all_client_users = [];
            if ( ! empty($client_id)) {
                $all_client_users = (array)upstream_get_all_client_users($client_id);
            }

            // Shows an empty title if we don't have a custom title yet
            $post_title = $project->post_title;
            if ($post_title === __('Auto Draft', 'upstream-frontend-edit')) {
                $post_title = '';
            }
            // Get the project categories and setup the builds
            $categories = (array)wp_get_object_terms($projectId, 'project_category');
            $categories_list = (array) null;
            $terms = get_terms( array('taxonomy' => 'project_category','hide_empty' => false,) );
            $term_ids = [];

            foreach ($categories as $category) {
                $categories_build = null;     
                // Category is assigned
                $categories_build['term_id'] = $category->term_id;
                $categories_build['name'] = $category->name;
                $categories_build['checked'] = true;
                array_push($categories_list, $categories_build);
                array_push($term_ids,$category->term_id);
            }

            if (count($terms) > 0) {
                if (empty($term_ids)) {
                    foreach ($terms as $term) {
                        $categories_build = null;
                        //No cats are assigned to this post add all the terms
                        $categories_build['term_id'] = $term->term_id;
                        $categories_build['name'] = $term->name;
                        $categories_build['checked'] = false;
                        array_push($categories_list, $categories_build);
                        array_push($term_ids,$term->term_id);
                    }
                
                } else {
                    foreach ($terms as $term) {
                        if (!in_array($term->term_id,$term_ids)) {
                            // Now populate the unassigned categories
                            $categories_build['term_id'] = $term->term_id;
                            $categories_build['name'] = $term->name;
                            $categories_build['checked'] = false;
                            array_push($categories_list, $categories_build);
                            array_push($term_ids,$term->term_id);                   
                        }                
                    }
                }
            }

            $data = [
                'id'                    => $projectId,
                self::RESPONSE_DATA_KEY => [
                    'title'            => [
                        'type'  => 'text',
                        'value' => $post_title,
                    ],
                    'status'           => [
                        'type'  => 'select',
                        'value' => $project->get_meta('status'),
                    ],
                    'categories'        => [
                        'type'  => 'project-category',
                        'value' => $categories_list,
                    ],
                    'owner'            => [
                        'type'  => 'select',
                        'value' => $project->get_meta('owner'),
                    ],
                    'client'           => [
                        'type'  => 'select',
                        'value' => $client_id,
                    ],
                    'client_users'     => [
                        'type'  => 'select',
                        'value' => $project->get_meta('client_users'),
                    ],
                    'all_client_users' => [
                        'type'  => 'select',
                        'value' => $all_client_users,
                    ],
                    'description'      => [
                        'type'  => 'wysiwyg',
                        'value' => $project->get_meta('description'),
                    ],
                    'start'            => [
                        'type'  => 'date',
                        'value' => $project->get_meta('start'),
                    ],
                    'end'              => [
                        'type'  => 'date',
                        'value' => $project->get_meta('end'),
                    ],
                ],
            ];


            $data[self::RESPONSE_DATA_KEY] = apply_filters('upstream_frontend_project_data',
                $data[self::RESPONSE_DATA_KEY], $projectId);


            foreach (array_keys($data[self::RESPONSE_DATA_KEY]) as $key) {

                $data[self::RESPONSE_DATA_KEY][$key]['has_edit_permission'] = upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, $key, UPSTREAM_PERMISSIONS_ACTION_EDIT);
                $data[self::RESPONSE_DATA_KEY][$key]['has_view_permission'] = upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, $key, UPSTREAM_PERMISSIONS_ACTION_VIEW);

            }

            $response[self::RESPONSE_DATA_KEY] = $data;

            $response[self::RESPONSE_SUCCESS_KEY] = true;
        } catch (Exception $e) {
            $response[self::RESPONSE_ERROR_KEY] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * Fetch the list of users from a client.
     *
     * @since   1.5.0
     * @static
     */
    public static function fetchClientUsers()
    {
        self::headerJsonContentType();

        $response = [
            self::RESPONSE_SUCCESS_KEY => false,
            self::RESPONSE_DATA_KEY    => [],
            self::RESPONSE_ERROR_KEY   => null,
        ];

        try {
            // Check if the request payload is potentially invalid.
            if (
                ! self::isDoingAjax()
                || empty($_GET)
                || ! isset($_GET['nonce'])
                || ! isset($_GET['client_id'])
                || ! check_ajax_referer('upstream.frontend-edit:fetch_client_users', 'nonce', false)
            ) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            // Check if the project potentially exists.
            $client_id = (int)$_GET['client_id'];

            if ( ! upstream_permissions('edit_projects')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            $all_client_users = [];
            if ( ! empty($client_id)) {
                $all_client_users = (array)upstream_get_all_client_users($client_id);
            }

            $response[self::RESPONSE_DATA_KEY] = [
                'users' => $all_client_users,
            ];

            $response[self::RESPONSE_SUCCESS_KEY] = true;
        } catch (Exception $e) {
            $response[self::RESPONSE_ERROR_KEY] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * Save a given project on frontend for editing purposes.
     *
     * @static
     */
    public static function saveProject()
    {
        self::headerJsonContentType();

        $response = [
            self::RESPONSE_SUCCESS_KEY => false,
            self::RESPONSE_DATA_KEY    => [],
            self::RESPONSE_ERROR_KEY   => null,
        ];

        try {
            // Check if the request payload is potentially invalid.
            if (
                ! self::isDoingAjax()
                || empty($_POST)
                || ! isset($_POST['nonce'])
                || ! isset($_POST['editing'])
                || ! check_ajax_referer('upstream.frontend-edit:save_project', 'nonce', false)
            ) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            // Check if the project potentially exists.
            $projectId = array_key_exists('editing', $_POST) ? (int)$_POST['editing'] : 0;
            if ($projectId <= 0) {
                throw new \Exception(__('Invalid Project.', 'upstream'));
            }

            if ( ! upstream_can_access_object('edit_projects', UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }


            $wp_lock_check = wp_check_post_lock($projectId);

            if ($wp_lock_check) {
                $user_info = get_userdata($wp_lock_check);
                throw new \Exception(__("This project is being edited by " . $user_info->user_login . ". The other user must cancel or save their work.", 'upstream'));
            } else {
                delete_post_meta($projectId, '_edit_lock');
            }

            $project = new UpStream_Project($projectId);

            if (empty($project)) {
                throw new \Exception(__('Project not found.', 'upstream-frontend-edit'));
            }

            $post = [
                'ID'          => $projectId,
                'post_title'  => sanitize_text_field($_POST['title']),
                'post_status' => 'publish',
            ];

            // Update the post into the database
            wp_update_post($post);

            $value = array_key_exists('status', $_POST) ? sanitize_text_field($_POST['status']) : '';
            if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, 'status', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                update_post_meta($projectId, '_upstream_project_status', $value);
            }

            $value = array_key_exists('owner', $_POST) ? sanitize_text_field($_POST['owner']) : '';
            if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, 'owner', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                update_post_meta($projectId, '_upstream_project_owner', $value);
            }

            if ( ! is_clients_disabled()) {
                $value = array_key_exists('client', $_POST) ? sanitize_text_field($_POST['client']) : '';
                if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, 'client', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                    update_post_meta($projectId, '_upstream_project_client', $value);
                }

                $value = array_key_exists('client_users', $_POST) ? $_POST['client_users'] : '';
                if ( ! empty($value)) {
                    // Check if the IDS are coming as CSV in the first item.
                    if (substr_count($value[0], ',')) {
                        $value = explode(',', $value[0]);
                    }

                    $value = array_map('intval', $value);
                }

                if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, 'client_users', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                    update_post_meta($projectId, '_upstream_project_client_users', $value);
                }
            }

            $value = '';
            if ($_POST['start_timestamp']) {
                $value = sanitize_text_field($_POST['start_timestamp']);
            }
            elseif ($_POST['start']) {
                $value = upstream_date_unixtime(sanitize_text_field($_POST['start']));
            }
            if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, 'start', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                update_post_meta($projectId, '_upstream_project_start', $value);
            }

            $value = '';
            if ($_POST['end_timestamp']) {
                $value = sanitize_text_field($_POST['end_timestamp']);
            }
            elseif ($_POST['end']) {
                $value = upstream_date_unixtime(sanitize_text_field($_POST['end']));
            }
            if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, 'end', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                update_post_meta($projectId, '_upstream_project_end', $value);
            }

            $value = '';
            if (array_key_exists('description', $_POST)) {
                $allowed_tags = apply_filters('upstream_allowed_tags_in_comments', []);
                $value        = wp_kses($_POST['description'], wp_kses_allowed_html($allowed_tags));
            }
            if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, 'description', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                update_post_meta($projectId, '_upstream_project_description', $value);
            }

            // Lets see if any category ticks have been passed
            $term_ids = (array) null;
            $value = array_key_exists('categories', $_POST) ? $_POST['categories'] : '';
            if ( ! empty($value)) {
                // Check if the IDS are coming as CSV in the first item.
                if (substr_count($value[0], ',')) {
                    $value = explode(',', $value[0]);
                }

                $term_ids = array_map('intval', $value);
            }

            // Update the project with the categories checked 
            if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, null, 0, 'categories', UPSTREAM_PERMISSIONS_ACTION_EDIT)) {
                wp_set_post_terms($projectId, (array)$term_ids, 'project_category');
            }

            // run this to update all missing meta data such as
            // created date, id, project members, progress etc
            $project = new UpStream_Project($projectId);
            $project->update_project_meta(true);

            do_action('upstream_frontend_save_project', $projectId);

            $response['url'] = get_permalink($projectId);

            $response[self::RESPONSE_SUCCESS_KEY] = true;
        } catch (Exception $e) {
            $response[self::RESPONSE_ERROR_KEY] = $e->getMessage();
        }
        wp_send_json($response);
    }

    public function cancelEdit()
    {
        //Delete the lock
        if ( ! empty($_GET) && isset($_GET['project_id'])) {
            delete_post_meta($_GET['project_id'], '_edit_lock');
        }
    }

    /**
     * The ajax action to delete an item
     *
     * @since  1.0.0
     */
    public function upstream_frontend_delete_item()
    {
        //parse_str( $_POST['formdata'], $posted );
        $this->posted = $_POST;
        $this->ID     = $this->get_id();
        $this->type   = $this->posted['type'];
        $this->delete_item();
    }

    public function display_message()
    {
        $status  = null;
        $message = null;

        if (isset($_SESSION['upstream-frontend-edit'])) {
            if (isset($_SESSION['upstream-frontend-edit']['status']) && ! empty($_SESSION['upstream-frontend-edit']['status'])) {
                $status = $_SESSION['upstream-frontend-edit']['status'];
            }

            if (isset($_SESSION['upstream-frontend-edit']['message']) && ! empty($_SESSION['upstream-frontend-edit']['message'])) {
                $message = $_SESSION['upstream-frontend-edit']['message'];
            }

            unset($_SESSION['upstream-frontend-edit']);
        }

        if (empty($status)) {
            $status = $this->message['status'];
        }

        if (empty($message)) {
            $message = $this->message['message'];
        }

        if (empty($status) || empty($message)) {
            return;
        }

        $output = sprintf('
            <div class="alert alert-%s alert-dismissible fade in" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="%s">
                    <span aria-hidden="true">×</span>
                </button>
                %s
            </div>
        ',
            $status,
            __('Close', 'upstream-frontend-edit'),
            esc_html($message)
        );

        echo $output;
    }

    /**
     * Sanitize an input field before saving
     * Types are: text, name, date, html, id, actions, files, tasks
     * Types are set within up-template-functions.php (in main UpStream plugin)
     *
     * @param string $key   The key (field name) of the field
     * @param string $value The value of the field. Can be null for files as it will use posted data
     *
     * @return array            The sanitized key => value pair
     * @since  1.0
     * @access private
     *
     */
    private function sanitize_input_field($key, $value = null)
    {

        /*
         * Get our settings for this group
         */
        $settings = $this->get_settings();
        $return   = null;

        /*
         * Check for a valid field
         */
        if ( ! isset($settings[$key])) {
            return $value;
        }

        // text, name, date, textarea, select, radio, checkbox, id, actions, files, tasks
        if (in_array($settings[$key]['type'], ['text', 'name', 'date', 'id', 'select', 'radio'])) {
            $return = sanitize_text_field($value);
        }

        if ($settings[$key]['type'] === 'checkbox') {
            if (is_array($value)) {
                if (count($value)) {
                    foreach ($value as &$valueItem) {
                        $valueItem = sanitize_text_field($valueItem);
                    }

                    $return = $value;
                }
            }
        }

        if (in_array($settings[$key]['type'], ['textarea', ''])) {
            $return = wp_kses_post($value);
        }

        // if type is not set or it has not been sanitized yet, run it through wp_kses_post
        if ( ! isset($settings[$key]['type']) || $return == null) {
            $return = wp_kses_post($value);
        }

        return $return;

    }

    /**
     * Get the table settings
     *
     * @return array
     * @since 1.0.0
     */
    public function get_settings()
    {

        $settings = null;

        // get our settings for the table
        switch ($this->type) {
            case 'tasks':
                $settings = upstream_task_table_settings();
                break;
            case 'bugs':
                $settings = upstream_bug_table_settings();
                break;
            case 'files':
                $settings = upstream_file_table_settings();
                break;
            case 'milestones':
                $settings = upstream_milestone_table_settings();
        }

        return $settings;

    }
}
