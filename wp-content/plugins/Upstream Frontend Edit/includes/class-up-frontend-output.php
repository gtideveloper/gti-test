<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Main UpStream Frontend Output class.
 *
 * @package     UpStream\Plugins\FrontendEdit
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class UpStream_Frontend_Output
{
    /**
     * @var UpStream The one true UpStream Frontend Output
     * @since 1.0.0
     */
    protected static $_instance = null;
    private static $editNonce = null;
    private static $cachedMilestones = [];

    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters.
     *
     * @since  1.0.0
     */
    private function init_hooks()
    {
        // Register permissions used by the plugin.
        add_filter('upstream:users.permissions', ['\UpStream_Frontend_Output', 'registerPermissions']);

        if (is_admin()) {
            return;
        }

        add_action('wp_enqueue_scripts', [$this, 'enqueues'], 1001);

        // check this plugin directory for teplate files
        add_filter('upstream_check_template_directory', [$this, 'check_directory']);

        // add the template files
        add_action('upstream_after_project_content', [$this, 'modals']);
        add_action('upstream_after_project_list_content', [$this, 'modals']);

        // add the bug button
        add_action('upstream_project_bugs_top_right', [$this, 'bug_button'], 10, 1);

        // add the task button
        add_action('upstream_project_tasks_top_right', [$this, 'task_button'], 10, 1);

        // add the discussion button
        add_action('upstream_project_discussion_top_right', [$this, 'discussion_button'], 10, 1);

        // add the files
        add_action('upstream_project_files_top_right', [$this, 'files_button'], 10, 1);

        // Add "Add Milestone" button.
        add_action('upstream_project_milestones_top_right', [$this, 'renderAddMilestoneButton']);

        add_filter('upstream:frontend:renderGridDataRow', [$this, 'addEditAnchorToGridRowColumn'], 10, 3);
        add_action('upstream:frontend:renderGridDataRow', [$this, 'renderCreatedByDataOnRow'], 10, 3);

        // Register an action to render the "Reply" button after each comment on discussion.
        add_action('upstream:frontend.project.discussion:comment_footer', [$this, 'renderReplyButtonOnComments'], 10,
            2);

        add_filter('upstream:frontend:project.table.body.td_value', [$this, 'defineCustomTdData'], 10, 7);

        add_action('upstream:frontend.project.details.before_title', [$this, 'renderEditProjectButton']);
        add_action('upstream:frontend.project.details.after_title', [$this, 'renderEditProjectButton']);

        if ( current_user_can('administrator') || current_user_can('upstream_manager') || current_user_can('upstream_user')) {
         //   add_action('upstream:frontend.project.details.before_title', [$this, 'renderCopyProjectButton']);
            add_action('upstream:frontend.project.details.after_title', [$this, 'renderCopyProjectButton']);
        }

        add_action('upstream_project_project_top_right', [$this, 'renderAddProjectLink']);
    
    }

    /**
     * @since 1.0.0
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Register additional permissions to UpStream.
     *
     * @param array $permissions A list of permissions being passed through WP filters.
     *
     * @return  array
     * @since   1.3.0
     * @static
     *
     */
    public static function registerPermissions($permissions)
    {
        $projectsLabelPlural = upstream_project_label_plural(true);

        $permissions = array_merge($permissions, [
            [
                'key'         => 'publish_project_milestones',
                'title'       => __('Create Milestones', 'upstream-frontend-edit'),
                'description' => sprintf(
                    __('Allow users to create %s in %s.', 'upstream-frontend-edit'),
                    upstream_milestone_label_plural(true),
                    $projectsLabelPlural
                ),
            ],
            [
                'key'         => 'publish_project_tasks',
                'title'       => __('Create Tasks', 'upstream-frontend-edit'),
                'description' => sprintf(
                    __('Allow users to create %s in %s.', 'upstream-frontend-edit'),
                    upstream_task_label_plural(true),
                    $projectsLabelPlural
                ),
            ],
            [
                'key'         => 'publish_project_bugs',
                'title'       => __('Report Bugs', 'upstream-frontend-edit'),
                'description' => sprintf(
                    __('Allow users to publish new %s in %s.', 'upstream-frontend-edit'),
                    upstream_bug_label_plural(true),
                    $projectsLabelPlural
                ),
            ],
            [
                'key'         => 'publish_project_files',
                'title'       => __('Upload Files', 'upstream-frontend-edit'),
                'description' => sprintf(
                    __('Allow users to upload %s in %s.', 'upstream-frontend-edit'),
                    upstream_file_label_plural(true),
                    $projectsLabelPlural
                ),
            ],
            [
                'key'         => 'publish_project_discussion',
                'title'       => __('Publish Messages', 'upstream-frontend-edit'),
                'description' => sprintf(
                    __('Allow users to participate in %s discussions.', 'upstream-frontend-edit'),
                    upstream_project_label(true)
                ),
            ],
            [
                'key'         => 'delete_project_discussion',
                'title'       => __('Delete Comments', 'upstream-frontend-edit'),
                'description' => __('Allow users to delete discussion messages.', 'upstream-frontend-edit'),
            ],
            [
                'key'         => 'edit_projects',
                'title'       => __('Edit Projects', 'upstream-frontend-edit'),
                'description' => __('Allow users to edit project details.', 'upstream-frontend-edit'),
            ],
        ]);

        return $permissions;
    }

    /**
     * Add code to toggle edit-form modal so users can edit particular items.
     *
     * @param string $tr    The current <tr>'s HTML.
     * @param array  $item  The current row data.
     * @param string $table The type of data being handled.
     *
     * @return  string
     * @since   1.3.6
     * @static
     *
     */
    public static function addEditAnchorToGridRowColumn($tr, $item, $table)
    {
        if ($table === 'milestones') {
            $title       = $item['milestone'];
            $titleQuoted = preg_quote($title);
        } else {
            $title       = $item['title'];
            $titleQuoted = preg_quote($title);
        }

        $project_id = upstream_post_id();

        $user            = wp_get_current_user();
        $userCanEditItem = count(array_intersect($user->roles, ['administrator', 'upstream_manager'])) > 0;
        if ( ! $userCanEditItem) {
            if (count(array_intersect($user->roles, ['upstream_user', 'upstream_client_user'])) > 0) {
                $project_owner_id = (int)upstream_project_owner_id($project_id);
                if (
                    $project_owner_id === (int)$user->ID ||
                    (isset($item['assigned_to']) && (int)$item['assigned_to'] === (int)$user->ID) ||
                    (isset($item['created_by']) && (int)$item['created_by'] === (int)$user->ID)
                ) {
                    $userCanEditItem = true;
                }
            }
        }

        if ($userCanEditItem) {
            if ( ! empty($title)) {
                $title       = esc_html($title);
                $titleQuoted = esc_html($titleQuoted);

                if (preg_match('~<td.+?data\-value="' . $titleQuoted . '".+?>(' . $titleQuoted . ')<\/td>~i', $tr,
                    $matches)) {
                    $td = str_replace('>' . $title . '<',
                        '><a href="#" class="text-info" data-toggle="up-modal" data-target=".modal-add-' . rtrim($table,
                            's') . '" data-id="' . esc_attr($item['id']) . '" data-post="' . $project_id . '"><i class="fa fa-pencil"></i>1 ' . $title . '</a><',
                        $matches[0]);

                    $tr = str_replace($matches[0], $td, $tr);
                }
            } elseif (preg_match('~<td.+?data\-name="title".+?>(.+?)<\/td>~i', $tr, $matches)) {
                $td = str_replace('>' . $matches[1] . '<',
                    '><a href="#" class="text-info" data-toggle="up-modal" data-target=".modal-add-' . rtrim($table,
                        's') . '" data-id="' . esc_attr($item['id']) . '" data-post="' . $project_id . '"><i class="fa fa-pencil"></i>2 ' . $matches[1] . '</a><',
                    $matches[0]);

                $tr = str_replace($matches[0], $td, $tr);
            }
        }

        return $tr;
    }

    /**
     * Action triggered to render controls for a given project comment.
     *
     * @param object $comment Comment data.
     *
     * @since   1.3.8
     * @static
     *
     */
    public static function insertCommentControls($comment)
    {
        if (empty($comment) || ! isset($comment->currentUserCap)) {
            return;
        }

        $canReply    = isset($comment->currentUserCap->can_reply) ? $comment->currentUserCap->can_reply : false;
        $canModerate = isset($comment->currentUserCap->can_moderate) ? $comment->currentUserCap->can_moderate : false;
        $canDelete   = isset($comment->currentUserCap->can_delete) ? $comment->currentUserCap->can_delete || $canModerate : false;

        $comment->state = (int)$comment->state;

        $controls = [];
        if ($canModerate) {
            if ($comment->state === 1) {
                $controls[0] = [
                    'action' => 'unapprove',
                    'nonce'  => "unapprove_comment",
                    'icon'   => "eye-slash",
                    'label'  => __('Unapprove'),
                ];
            } else {
                $controls[2] = [
                    'action' => 'approve',
                    'nonce'  => "approve_comment",
                    'icon'   => "eye",
                    'label'  => __('Approve'),
                ];
            }
        }

        if ($canReply) {
            $controls[1] = [
                'action' => 'reply',
                'nonce'  => "add_comment_reply",
                'icon'   => "reply",
                'label'  => __('Reply'),
            ];
        }

        if ($canDelete) {
            $controls[] = [
                'action' => 'trash',
                'nonce'  => "trash_comment",
                'icon'   => "trash-o",
                'label'  => __('Delete'),
            ];
        }

        if (count($controls) > 0) {
            foreach ($controls as $control) {
                printf(
                    '<a href="#" class="o-comment-control" data-action="comment.%s" data-nonce="%s">
                      <i class="fa fa-%s"></i>&nbsp;
                      %s
                    </a>',
                    $control['action'],
                    wp_create_nonce('upstream:project.' . $control['nonce'] . ':' . $comment->id),
                    $control['icon'],
                    $control['label']
                );
            }
        }
    }

    /**
     * Append the item's created_by column to the row for frontend usage.
     * This method is called by the "upstream:frontend:renderGridDataRow" filter.
     *
     * @param string $tr    The current <tr>'s HTML.
     * @param array  $item  The current row data.
     * @param string $table The type of data being handled.
     *
     * @return  string
     * @since   1.3.8
     * @static
     *
     */
    public static function renderCreatedByDataOnRow($tr, $item, $table)
    {
        if ($table !== "milestones") {
            $itemCreatedBy = isset($item['created_by']) && (int)$item['created_by'] > 0 ? (int)$item['created_by'] : 0;
            if ($itemCreatedBy > 0) {
                $tr = str_replace('<td data-name="title"',
                    '<td data-name="title" data-created_by="' . $itemCreatedBy . '"', $tr);
            }
        }

        return $tr;
    }

    /**
     * Render the "Reply" button after each comment on discussion.
     * Hook called by the "upstream:frontend.project.discussion:comment_footer" action.
     *
     * @param int    $project_id The current project ID.
     * @param object $comment    The comment object.
     *
     * @since   1.4.0
     * @static
     *
     */
    public static function renderReplyButtonOnComments($project_id, $comment)
    {
        ?>
        <a href="#" class="text-info" data-action="comment.reply"
           data-nonce="<?php echo wp_create_nonce('upstream.frontend-edit:project.discussion.reply:' . $comment->id); ?>">
            <small><i class="fa fa-reply"></i> <?php _e('Reply'); ?></small>
        </a>
        <?php if (isset($comment->userCanDelete) && $comment->userCanDelete): ?>
        <a href="#" class="text-danger" data-action="comment.delete"
           data-nonce="<?php echo wp_create_nonce('upstream.frontend-edit:project.discussion:' . $comment->id); ?>">
            <small><i class="fa fa-trash"></i> <?php _e('Delete'); ?></small>
        </a>
    <?php
    endif;
    }

    /**
     * Define custom values for a given <td> within a data table.
     *
     * @param   $html           string  Current <td>'s HTML.
     * @param   $columnName     string  Correspondent column name.
     * @param   $columnValue    mixed   Column value.
     * @param   $column         array   Column args.
     * @param   $row            array   Whole object data.
     * @param   $rowType        string  Item type.
     * @param   $projectId      int     Project ID.
     *
     * @return  string
     * @since   1.5.0
     * @static
     *
     */
    public static function defineCustomTdData($html, $columnName, $columnValue, $column, $row, $rowType, $projectId)
    {

        $isMilestoneMilestone = $rowType === 'milestone' && $columnName === 'milestone';

        if ($isMilestoneMilestone
            || ($rowType === 'task' && $columnName === 'title')
            || ($rowType === 'bug' && $columnName === 'title')
            || ($rowType === 'file' && $columnName === 'title')
        ) {
            if (self::$editNonce === null) {
                self::$editNonce = wp_create_nonce('upstream.frontend-edit:fetch_item');
            }

            if ($isMilestoneMilestone) {
                if (empty(self::$cachedMilestones)) {
                    self::$cachedMilestones = getMilestones();
                }

                if ( ! empty($columnValue)
                     && isset(self::$cachedMilestones[$columnValue])
                ) {
                    $columnValue = self::$cachedMilestones[$columnValue]['title'];
                }
            }

            $canEdit = upstream_permissions('publish_project_' . $rowType . 's');

            if ($canEdit && in_array($rowType, ['task', 'bug', 'milestone'])) {
                $canEdit = self::canEditItemType($rowType);
            }

            $canEdit = upstream_override_access_object($canEdit, $rowType, $row['id'], 'project', $projectId, UPSTREAM_PERMISSIONS_ACTION_EDIT);

            if ($canEdit) {
                $html = sprintf(
                    '<a href="#" class="o-edit-link" data-toggle="up-modal" data-target="#modal-%s" data-nonce="%s" data-id="%3$s" data-value="%3$s">
                      <i class="fa fa-pencil"></i>
                      %s
                    </a>',
                    $rowType,
                    self::$editNonce,
                    $columnValue
                );
            } else {
                $html = $columnValue;
            }
        }

        return $html;
    }

    /**
     * @param $itemType
     *
     * @return bool
     */
    protected static function canEditItemType($itemType)
    {
        $capabilities = [
            'milestone' => [
                'milestone_assigned_to_field',
                'milestone_end_date_field',
                'milestone_milestone_field',
                'milestone_notes_field',
                'milestone_start_date_field',
            ],
            'task'      => [
                'task_assigned_to_field',
                'task_end_date_field',
                'task_milestone_field',
                'task_notes_field',
                'task_progress_field',
                'task_start_date_field',
                'task_status_field',
                'task_title_field',
            ],
            'bug'       => [
                'bug_assigned_to_field',
                'bug_description_field',
                'bug_due_date_field',
                'bug_file_field',
                'bug_severity_field',
                'bug_status_field',
                'bug_title_field',
            ],
            'project'   => [
                'project_client_field',
                'project_description',
                'project_description_field',
                'project_end_date_field',
                'project_owner_field',
                'project_start_date_field',
                'project_status_field',
                'project_title_field',
                'project_users_field',
            ],
        ];

        if ( ! array_key_exists($itemType, $capabilities)) {
            return false;
        }

        foreach ($capabilities[$itemType] as $capability) {
            if (current_user_can($capability)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Enqueues
     *
     * @since  1.0.0
     */
    public function enqueues()
    {
        $postType = get_post_type();

        if (get_post_type() === false) {
            if (upstream_is_project_base_uri($_SERVER['REQUEST_URI'])) {

            }

            else {
                return;
            }
        }

        else if ($postType !== 'project') {
            return;
        }

        ?>
        <script>
            var ajaxurl = "<?php echo admin_url('admin-ajax.php') ?>";
        </script>
        <?php

        wp_enqueue_media();

        wp_enqueue_style('up-frontend-edit', UP_FRONTEND_PLUGIN_URL . 'assets/css/upstream-frontend-edit.css',
            ['admin-bar'],
            UP_FRONTEND_VERSION);

        wp_enqueue_style('up-chosen');

        wp_enqueue_script('up-modal', UP_FRONTEND_PLUGIN_URL . 'assets/js/modal.min.js', [], UP_FRONTEND_VERSION,
            true);

        wp_register_script('iris', admin_url('js/iris.min.js'),
            ['jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch'], UPSTREAM_FRONTEND_EDIT_VERSION);
        wp_register_script('wp-color-picker', admin_url('js/color-picker.min.js'), ['iris'],
            UPSTREAM_FRONTEND_EDIT_VERSION);

        wp_localize_script('wp-color-picker', 'wpColorPickerL10n', [
            'clear'         => esc_html__('Clear', 'upstream-frontend-edit'),
            'defaultString' => esc_html__('Default', 'upstream-frontend-edit'),
            'pick'          => esc_html__('Select Color', 'upstream-frontend-edit'),
            'current'       => esc_html__('Current Color', 'upstream-frontend-edit'),
        ]);

        $dependencies = ['jquery', 'up-chosen', 'upstream', 'up-modal', 'wp-color-picker'];

        // Fix compatibility with Geodir Advanced Search plugin.
        if (is_plugin_active('geodir_advance_search_filters/geodir_advance_search_filters.php')) {
            $dependencies[] = 'advanced-search-js';
        }

        wp_enqueue_script('up-frontend-edit', UP_FRONTEND_PLUGIN_URL . 'assets/js/project.js',
            $dependencies, UP_FRONTEND_VERSION, true);
        
        // Add Copy Project to the frontend.
        wp_enqueue_script('upstream-frontend-copy-project', UP_FRONTEND_PLUGIN_URL . 'assets/js/frontend-copy-project.js',
            ['jquery'], UP_FRONTEND_VERSION);

        wp_localize_script('upstream-frontend-copy-project', 'upstreamCopyProjectLangStrings', [
            'ERR_INVALID_RESPONSE' => __('Invalid response', 'upstream-frontend-copy-project'),
            'ERR_UNABLE_TO_COPY'   => __('It wasn\'t possible to copy this '. upstream_project_label(), 'upstream-frontend-copy-project'),
        ]);

        wp_localize_script('up-frontend-edit', 'upstreamFrontEditLang', [
            'MSG_CONFIRM'                    => __('Are you sure? This action cannot be undone.', 'upstream'),
            'MSG_CONFIRM_COPY'               => __('Are you sure you want to copy this ' . upstream_project_label() .'?', 'upstream'),
            'LB_EXISTING_FILE'               => __('Existing File', 'upstream-frontend-edit'),
            'LB_DELETE'                      => __('Delete', 'upstream'),
            'LB_DELETING'                    => __('Deleting...', 'upstream'),
            'LB_UNAPPROVE'                   => __('Unapprove'),
            'LB_UNAPPROVING'                 => __('Unapproving...', 'upstream'),
            'LB_APPROVE'                     => __('Approve'),
            'LB_APPROVING'                   => __('Approving...', 'upstream'),
            'LB_ADD_COMMENT'                 => __('Add Comment', 'upstream'),
            'LB_ADDING'                      => __('Adding...', 'upstream'),
            'LB_SAVING'                      => __('Saving...', 'upstream'),
            'LB_SAVE'                        => __('Save', 'upstream'),
            'LB_REPLY'                       => __('Reply'),
            'LB_REPLYING'                    => __('Replying...', 'upstream'),
            'LB_REMOVE'                      => __('Remove', 'upstream-frontend-edit'),
            'LB_UNDO'                        => __('Undo', 'upstream-frontend-edit'),
            'MSG_USE_THIS_FILE'              => __('Use this file', 'cmb2'),
            'MSG_INVALID_DATE'               => __('Invalid date.', 'upstream'),
            'option_select_users_by_default' => select_users_by_default(),
        ]);

        $this->localizeDatePickerStrings();

        wp_enqueue_media();
    }

   


    /**
     * Make sure DatePicker strings are localized.
     *
     * @since   1.2.2
     * @access  private
     *
     * @global  $wp_locale
     */
    private function localizeDatePickerStrings()
    {
        global $wp_locale;

        // Convert current date format to its correspondent JS date format.
        $currentDateFormat = get_option('date_format');
        switch ($currentDateFormat) {
            case 'F j, Y':
                $currentDateFormat = 'MM dd, yy';
                break;
            case 'Y/m/d':
                $currentDateFormat = 'yy/mm/dd';
                break;
            case 'm/d/Y':
                $currentDateFormat = 'mm/dd/yy';
                break;
            case 'd/m/Y':
                $currentDateFormat = 'dd/mm/yy';
                break;
        }

        wp_localize_script('up-frontend-edit', 'upstream_frontend_edit_langStrings', [
            'closeText'       => __('Done', 'upstream-frontend-edit'),
            'currentText'     => __('Today', 'upstream-frontend-edit'),
            'monthNames'      => array_values($wp_locale->month_genitive),
            'monthNamesShort' => array_values($wp_locale->month_abbrev),
            'monthStatus'     => __('Show a different month', 'upstream-frontend-edit'),
            'dayNames'        => array_values($wp_locale->weekday),
            'dayNamesShort'   => array_values($wp_locale->weekday_abbrev),
            'dayNamesMin'     => array_values($wp_locale->weekday_initial),
            'dateFormat'      => $currentDateFormat,
            'firstDay'        => get_option('start_of_week'),
            'isRTL'           => $wp_locale->is_rtl(),
            'LB_ADD'          => __('Add', 'upstream-frontend-edit'),
            'LB_EDIT'         => __('Edit', 'upstream-frontend-edit'),
            'LB_MILESTONE'    => upstream_milestone_label(),
            'LB_TASK'         => upstream_task_label(),
            'LB_BUG'          => upstream_bug_label(),
            'LB_FILE'         => upstream_file_label(),
            'LB_POST_MESSAGE' => __('Add Comment', 'upstream'),
        ]);
    }

    /**
     * Check plugin directory for template files
     *
     * @since  1.0.0
     */
    public function check_directory($dirs)
    {
        $dir = trailingslashit(UP_FRONTEND_PLUGIN_DIR) . 'templates/';
        array_push($dirs, $dir);

        return $dirs;
    }

    /**
     * Check plugin directory for template files
     *
     * @since  1.0.0
     */
    public function modals_orig()
    {
        require_once UPSTREAM_PLUGIN_DIR . 'includes/admin/metaboxes/metabox-functions.php';

        if (upstream_permissions('publish_project_tasks')) :
            if (self::canEditItemType('task')) {
                upstream_get_template_part('add-task.php');
            }
        endif;

        if (upstream_permissions('publish_project_bugs')) :
            if (self::canEditItemType('bug')) {
                upstream_get_template_part('add-bug.php');
            }
        endif;

        if (upstream_permissions('publish_project_discussion')) :
            upstream_get_template_part('add-message.php');
        endif;

        if (upstream_permissions('publish_project_files')) :
            upstream_get_template_part('add-files.php');
        endif;

        if (upstream_permissions('publish_project_milestones')) {
            if (self::canEditItemType('milestone')) {
                upstream_get_template_part('add-milestone.php');
            }
        }

        if (upstream_permissions('edit_projects')) :
            upstream_get_template_part('add-project.php');
        endif;

        upstream_get_template_part('delete.php');
    }

    public function modals()
    {
        require_once UPSTREAM_PLUGIN_DIR . 'includes/admin/metaboxes/metabox-functions.php';

        upstream_get_template_part('add-task.php');

        upstream_get_template_part('add-bug.php');

        upstream_get_template_part('add-message.php');
        upstream_get_template_part('add-files.php');

        upstream_get_template_part('add-milestone.php');

        upstream_get_template_part('add-project.php');

        upstream_get_template_part('delete.php');
    }

    /**
     * Adds the 'add new bug' button
     *
     * @since  1.0.0
     */
    public function bug_button($project_id)
    {
        $canAccess = upstream_permissions('publish_project_bugs') && self::canEditItemType('bug');
        $canAccess = upstream_override_access_object($canAccess, UPSTREAM_ITEM_TYPE_BUG, 0, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, UPSTREAM_PERMISSIONS_ACTION_CREATE);

        if ($canAccess):
                $bugLabel = upstream_bug_label(); ?>
                <li>
                    <button type="button" class="btn btn-xs btn-primary" data-toggle="up-modal" data-target="#modal-bug"
                            data-form-type="add"
                            data-modal-title="<?php printf(__('New: %s', 'upstream'), $bugLabel); ?>">
                        <?php echo sprintf(__('Add %s', 'upstream'), $bugLabel); ?>
                    </button>
                </li>
        <?php endif;
    }

    /**
     * Adds the 'add new task' button
     *
     * @since  1.0.0
     */
    public function task_button($project_id)
    {

        $canAccess = upstream_permissions('publish_project_tasks') && self::canEditItemType('task');
        $canAccess = upstream_override_access_object($canAccess, UPSTREAM_ITEM_TYPE_TASK, 0, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, UPSTREAM_PERMISSIONS_ACTION_CREATE);

        if ($canAccess):
                $taskLabel = upstream_task_label(); ?>
                <li>
                    <button type="button" class="btn btn-xs btn-primary" data-toggle="up-modal"
                            data-target="#modal-task"
                            data-form-type="add"
                            data-modal-title="<?php printf(__('New: %s', 'upstream'), $taskLabel); ?>">
                        <?php echo sprintf(__('Add %s', 'upstream'), $taskLabel); ?>
                    </button>
                </li>
        <?php endif;
    }

    /**
     * Adds the 'add new message' button for discussions
     *
     * @since  1.0.0
     */
    public function discussion_button($project_id)
    {
        if (upstream_can_access_field('publish_project_discussion', UPSTREAM_ITEM_TYPE_PROJECT, $project_id, null, 0, 'comments', UPSTREAM_PERMISSIONS_ACTION_EDIT)):
            $addCommentLabel = __('Add Comment', 'upstream'); ?>
            <li>
                <button type="button" class="btn btn-xs btn-primary" data-toggle="up-modal"
                        data-target="#modal-discussion" data-form-type="add"
                        data-modal-title="<?php echo $addCommentLabel; ?>">
                    <?php echo $addCommentLabel; ?>
                </button>
            </li>
        <?php endif;
    }

    /**
     * Adds the 'add new message' button for discussions
     *
     * @since  1.0.0
     */
    public function files_button($project_id)
    {
        if (upstream_can_access_object('publish_project_files', UPSTREAM_ITEM_TYPE_FILE, 0, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, UPSTREAM_PERMISSIONS_ACTION_CREATE)):
            $fileLabel = upstream_file_label(); ?>
            <li>
                <button type="button" class="btn btn-xs btn-primary" data-toggle="up-modal" data-target="#modal-file"
                        data-form-type="add"
                        data-modal-title="<?php printf(__('New: %s', 'upstream'), $fileLabel); ?>">
                    <?php printf(__('Add %s', 'upstream'), $fileLabel); ?>
                </button>
            </li>
        <?php endif;
    }

    /**
     * Adds the "Add New Milestone" button.
     *
     * @since  1.2.0
     */
    public function renderAddMilestoneButton($project_id)
    {
        if (
            (
                function_exists('upstream_disable_milestones') &&
                upstream_disable_milestones()
            ) ||
            (
                function_exists('upstream_are_milestones_disabled') &&
                upstream_are_milestones_disabled()
            ) ||
            ! upstream_can_access_object('publish_project_milestones', UPSTREAM_ITEM_TYPE_MILESTONE, 0, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, UPSTREAM_PERMISSIONS_ACTION_CREATE)
        ) {
            return;
        }
        $milestoneLabel = upstream_milestone_label();
        ?>

        <li>
            <button type="button" class="btn btn-xs btn-primary" data-toggle="up-modal" data-target="#modal-milestone"
                    data-form-type="add"
                    data-modal-title="<?php printf(__('New: %s', 'upstream'), $milestoneLabel); ?>">
                <?php echo sprintf(__('Add %s', 'upstream'), $milestoneLabel); ?>
            </button>
        </li>

        <?php
    }

    public function renderEditProjectButton($project)
    {
        $project_label = upstream_project_label();
        $title         = sprintf(__('Edit %s', 'upstream'), $project_label);

        if (upstream_can_access_object('edit_projects', UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, UPSTREAM_PERMISSIONS_ACTION_EDIT)) :
            ?>
            <a href="#"
               class="text-info edit-project"
               data-target="#modal-project"
               data-value="<?php echo $title; ?>"
               data-id="<?php echo esc_attr($project->id); ?>"
               data-nonce="<?php echo wp_create_nonce('upstream.frontend-edit:fetch_project'); ?>"
               title="<?php echo $title; ?>">

                <i class="fa fa-pencil"></i>
            </a>
        <?php endif;
    }

    public function renderCopyProjectButton($project)
    {
        $project_label = upstream_project_label();
        $title         = sprintf(__('Copy %s', 'upstream'), $project_label);

        if (upstream_can_access_object('edit_projects', UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, UPSTREAM_PERMISSIONS_ACTION_COPY)) :
            ?>
            <a href="#"
               class="upstream-frontend-copy-project-anchor"
               data-post-id="<?php echo esc_attr($project->id); ?>"
               data-text="Copy"
               data-disabled-text="Copying..."
               data-nonce="<?php echo wp_create_nonce('upstream-copy-project:clone'); ?>"
               title="<?php echo $title; ?>">

                <i class="fa fa-clone"></i>
            </a>
        <?php endif;
    }

    public function renderAddProjectLink()
    {
        $project_label = upstream_project_label();
        $title         = sprintf(__('New %s', 'upstream'), $project_label);

        if (upstream_can_access_object('publish_projects', UPSTREAM_ITEM_TYPE_PROJECT, 0, null, 0, UPSTREAM_PERMISSIONS_ACTION_CREATE)) :
            ?>
            <li>
                <button
                        id="upstream_new_project"
                        class="btn btn-xs btn-primary edit-project"
                        data-target="#modal-project"
                        data-value="<?php echo $title; ?>"
                        data-id="0"
                        data-form-type="add"
                        data-nonce="<?php echo wp_create_nonce('upstream.frontend-edit:fetch_project'); ?>"
                        data-modal-title="<?php printf(__('Add %s', 'upstream'), upstream_project_label(true)); ?>">
                    <?php printf(__('Add %s', 'upstream'), upstream_project_label(true)); ?>
                </button>
            </li>
        <?php endif;
    }
}
