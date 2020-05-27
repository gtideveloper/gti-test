<?php

namespace UpStream\Plugins\CopyProject;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Alledia\EDD_SL_Plugin_Updater;
use UpStream\Factory;
use UpStream\Milestones;
use UpStream\Plugins\CopyProject\Traits\Singleton;

/**
 * Main plugin class file.
 *
 * @package     UpStream\Plugins\CopyProject
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
     * @var EDD_SL_Plugin_Updater
     */
    public static $updater;

    /**
     * Store the current namespace so it can be reused on various methods.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     string $namespace
     */
    private static $namespace;

    /**
     * Plugin's instance constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        // Store the current namespace so it can be reused.
        self::$namespace = __NAMESPACE__ . '\Plugin';

        register_activation_hook(UP_COPY_PROJECT_PLUGIN, [self::$namespace, 'activationCallback']);

        $this->attachHooks();
    }

    /**
     * Attach all required filters and actions to its correspondent endpoint methods.
     *
     * @since   1.0.0
     * @access  private
     */
    private function attachHooks()
    {
        // Define all filters.
        add_filter('plugin_action_links_' . UP_COPY_PROJECT_PLUGIN, [self::$namespace, 'handleActionLinks']);
        add_filter('post_row_actions', [self::$namespace, 'renderAdditionalPostRowActionLinks'], 10, 2);
        add_filter('upstream_option_metaboxes', [self::$namespace, 'renderOptionsMetabox'], 1);
        // Define all actions.
        add_action('admin_enqueue_scripts', [self::$namespace, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [self::$namespace, 'enqueueStyles']);
        add_action('wp_ajax_upstream-copy-project:clone', [self::$namespace, 'cloneFromAjax']);
        add_action('wp_ajax_upstream.frontend-edit:copy_project', [self::$namespace, 'copyProject']);
        add_action('admin_init', [self::$namespace, 'initUpdater']);
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
     * Check if all minimum requirements are satisfied.
     *
     * @throws  \Exception when minimum PHP version required is not satisfied.
     * @throws  \Exception when minimum WordPress version required is not satisfied.
     * @throws  \Exception when UpStream is either not installed or activated.
     * @global  $wp_version
     *
     * @since   1.0.0
     * @static
     *
     */
    public static function testMinimumRequirements()
    {
        if ( ! function_exists('is_plugin_inactive')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Check the PHP version.
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            $errorMessage = sprintf(
                '%s <br/> %s',
                __('Due security reasons this plugin requires <strong>PHP version 5.6 or greater</strong> to run.',
                    'upstream-copy-project'),
                __('Please, update your PHP (currently at', 'upstream-copy-project') . ' ' . PHP_VERSION . ').'
            );

            throw new \Exception($errorMessage);
        }

        // Check the WordPress version.
        global $wp_version;
        if (version_compare($wp_version, '4.5', '<')) {
            $errorMessage = sprintf(
                '%s <br/> %s',
                __('Due security reasons this plugin requires <strong>WordPress version 4.5 or greater</strong> to run.',
                    'upstream-copy-project'),
                __('Please, update your WordPress (currently at', 'upstream-copy-project') . ' ' . $wp_version . ').'
            );

            throw new \Exception($errorMessage);
        }

        // Check if UpStream is installed and activated.
        $upstream = 'upstream/upstream.php';
        if ( ! file_exists(WP_PLUGIN_DIR . '/' . $upstream) || is_plugin_inactive($upstream)) {
            $errorMessage = sprintf(
                __('Please, make sure %s is <strong>installed</strong> and <strong>active</strong> in order to make this plugin to work.',
                    'upstream-copy-project'),
                '<a href="https://wordpress.org/plugins/upstream" target="_blank" rel="noreferrer noopener">' . __('UpStream',
                    'upstream') . '</a>'
            );

            throw new \Exception($errorMessage);
        }
    }

    /**
     * Calls wp_die() function with a custom message.
     *
     * @param string $message The message to be displayed.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function dieWithError($message = "")
    {
        $message = '<h1>' . __('Copy Project', 'upstream-copy-project') . '</h1><br/>' . $message;

        $message .= sprintf('<br /><br />
            <a class="button" href="javascript:history.back();" title="%s">%s</a>',
            __('Go back to the plugins page.', 'upstream-copy-project'),
            __('Go back', 'upstream-copy-project')
        );

        wp_die($message);
    }

    /**
     * AJAX endpoint responsible for handling 'cloning' requests.
     *
     * @since   1.0.0
     * @static
     */
    public static function cloneFromAjax()
    {
        // Check nonce value.
        check_ajax_referer(UP_COPY_PROJECT_NAME . '-nonce', 'security');

        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'err'     => null,
        ];

        if ( ! current_user_can('administrator') && ! current_user_can('upstream_manager')) {
            $response['err'] = __('Only Aministrators or Managers are allowed to copy projects.', UP_COPY_PROJECT_NAME);
        }

        if ( ! empty($_POST) && isset($_POST['project_id'])) {
            $project_id = (int)$_POST['project_id'];
            if ($project_id > 0) {
                try {
                    $response['success'] = self::doClone($project_id);
                } catch (\Exception $e) {
                    $response['err'] = $e->getMessage();
                }
            }
        }

        echo wp_json_encode($response);

        die();
    }

    public static function copyProject()
    {
        check_ajax_referer('upstream-copy-project:clone', 'security', true);

        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'err'     => null,
        ];

        if ( ! current_user_can('administrator') && ! current_user_can('upstream_manager') && ! current_user_can('upstream_user')) {
            $response['err'] = __('Only Administrators or Managers are allowed to copy projects.',
                UP_COPY_PROJECT_NAME);
            echo wp_json_encode($response);
            die();
        }

        if ( ! empty($_POST) && isset($_POST['project_id'])) {
            $project_id = (int)$_POST['project_id'];
            if ($project_id > 0) {
                try {
                    $response['success'] = self::doClone($project_id);
                } catch (\Exception $e) {
                    $response['err'] = $e->getMessage();
                }
            }
        }

        echo wp_json_encode($response);

        die();
    }

    /**
     * Clone a given project based on its ID.
     *
     * The cloned project title will be sufixed with " (x)" where "x" is an incremental integer.
     * Example: Let's say we have a project called "Foo" and we cloned it. The new title will be
     * "Foo (1)". If latter is cloned, "Foo (2)" and so on.
     *
     * @param integer $matrixProjectId The post_id to be cloned.
     *
     * @return  boolean     Returns true in case of success.
     * @throws  \Exception if wp_insert_post() fails.
     * @throws  \Exception if any other error occurs.
     *
     * @since   1.0.0
     * @access  public
     * @static
     *
     * @global        $wpdb            for querying db in search of projects having a similar title.
     *
     */
    public static function doClone($matrixProjectId)
    {
        global $wpdb;

        try {
            $theClone = get_post($matrixProjectId, 'ARRAY_A');

            // Regex to identify potential numerical-incremental-indexes sufixes.
            $sufixRegex = '/\(([0-9]*)\)$/';

            // Retrieve all posts potentially having the same/similar title.
            $postsHavingSimilarTitleRowset = $wpdb->get_results(sprintf('
                SELECT `ID`, `post_title`
                FROM `%sposts`
                WHERE `post_type` = "%s" AND
                      `post_status` != "trash" AND
                      `post_title` LIKE "%s"',
                $wpdb->prefix,
                $theClone['post_type'],
                preg_replace($sufixRegex, '', $theClone['post_title']) . '%'
            ));

            // Check if there's any post having a similar title with/without potential numerical-incremental sufix.
            if (count($postsHavingSimilarTitleRowset)) {
                $sufixHigherIndex = null;
                // Tries to identify the higher numerical-incremental index.
                foreach ($postsHavingSimilarTitleRowset as $postSimilarTitleRow) {
                    if (preg_match($sufixRegex, $postSimilarTitleRow->post_title, $matches)) {
                        $sufixIndex = count($matches) > 1 ? (int)$matches[1] : 0;
                    } else {
                        $sufixIndex = 0;
                    }

                    if ($sufixHigherIndex === null || $sufixIndex > $sufixHigherIndex) {
                        $sufixHigherIndex = $sufixIndex;
                    }
                }
                unset($postSimilarTitleRow);
            } else {
                $sufixHigherIndex = 0;
            }

            unset($postsHavingSimilarTitleRowset);

            // Clean up any potential numerical-index sufix.
            if (preg_match($sufixRegex, $theClone['post_title'], $matches)) {
                $theClone['post_title'] = preg_replace($sufixRegex, '', $theClone['post_title']);
            }

            // Generate the new title having a numerical-index sufix.
            $theClone['post_title'] = sprintf('%s (%d)', trim($theClone['post_title']), ++$sufixHigherIndex);
            $theClone['post_name']  = sanitize_title($theClone['post_title']);

            // Get the current timestamp.
            $currentTimestamp          = current_time('timestamp', 0);
            $theClone['post_modified'] = date('Y-m-d H:i:s', $currentTimestamp);

            // Get the current timestamp-gmt.
            $currentTimestampGMT           = current_time('timestamp', 1);
            $theClone['post_modified_gmt'] = date('Y-m-d H:i:s', $currentTimestampGMT);

            // Get all plugin options.
            $pluginOptions = self::getOptions();
            // Check if we have to change the `post_status` column.
            if ($pluginOptions->post_status !== 'original') {
                $theClone['post_status'] = $pluginOptions->post_status;
            }

            // Check if we have to change the `post_date` column.
            if ($pluginOptions->post_date === 'current') {
                $theClone['post_date']     = $theClone['post_modified'];
                $theClone['post_date_gmt'] = $theClone['post_modified_gmt'];
            }

            // Unset unecessary keys for a new post object.
            unset($theClone['ID'], $theClone['guid'], $theClone['comment_count']);

            // Insert the cloned post.
            $theClone['ID'] = (int)wp_insert_post($theClone);
            if ( ! empty($theClone['ID'])) {
                // Clone all related taxonomies.
                $theCloneTaxonomies = get_object_taxonomies($theClone['post_type']);
                foreach ($theCloneTaxonomies as $taxonomy) {
                    $taxonomyTerms = wp_get_post_terms($matrixProjectId, $taxonomy, ['fields' => 'names']);

                    wp_set_object_terms($theClone['ID'], $taxonomyTerms, $taxonomy);
                }
                unset($taxonomyTerms, $taxonomy, $theCloneTaxonomies);

                $metasBlacklist = [
                    '_edit_lock',
                    '_edit_last',
                    '_wp_old_slug',
                    '_wp_trash_meta_status',
                    '_wp_trash_meta_time',
                    '_wp_desired_post_slug',
                ];

                $optionalMetasList = ['activity', 'tasks', 'bugs', 'files', 'discussion'];
                foreach ($optionalMetasList as $optionalMeta) {
                    if (isset($pluginOptions->{$optionalMeta}) && (bool)$pluginOptions->{$optionalMeta} === false) {
                        array_push($metasBlacklist, '_upstream_project_' . $optionalMeta);
                    }
                }

                $newMilestonesIdList = [];

                // Check if the milestone is not disabled to copy them
                if ( ! isset($pluginOptions->milestones) || (bool)$pluginOptions->milestones === true) {
                    $newMilestonesIdList = self::copyMilestones($matrixProjectId, $theClone['ID']);
                }

                // Clone all related metas.
                $theCloneMetas = get_post_custom($matrixProjectId);
                foreach ($theCloneMetas as $metaKey => $meta) {
                    // We don't need those metas on the cloned project.
                    if (in_array($metaKey, $metasBlacklist)) {
                        continue;
                    }

                    if (is_array($meta) && count($meta) > 0) {
                        // Insert cloned project meta.
                        foreach ($meta as $metaValue) {
                            if ($metaKey == '_upstream_project_tasks') {
                                if (count($metaValue) > 0) {
                                    $tasks = unserialize($metaValue);
                                    foreach ($tasks as &$task) {
                                        if (isset($task['milestone']) && isset($newMilestonesIdList[$task['milestone']])) {
                                            $task['milestone'] = $newMilestonesIdList[$task['milestone']];
                                        }
                                    }
                                }
                                $metaValue = serialize($tasks);
                            }
                            $wpdb->insert($wpdb->prefix . 'postmeta', [
                                'post_id'    => $theClone['ID'],
                                'meta_key'   => $metaKey,
                                'meta_value' => $metaValue,
                            ]);
                        }
                    }
                }
                unset($meta, $metaKey, $theCloneMetas);

                update_post_meta($theClone['ID'], '_upstream_project_progress',
                    upstream_project_progress($matrixProjectId));

                return true;
            } else {
                throw new \Exception(__('It wasn\'t possible to copy the project.', 'upstream-copy-project'));
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Retrieve all plugin options.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function getOptions()
    {
        $pluginId   = str_replace('-', '_', UP_COPY_PROJECT_NAME);
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
     * Retrieve all plugin options schema defined by CMB2 field patterns.
     *
     * @return  array
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function getOptionsSchema()
    {
        $boolOptionsList = [
            '1' => __('Yes', 'upstream-copy-project'),
            '0' => __('No', 'upstream-copy-project'),
        ];

        $fieldsList = [
            [
                'before_row' => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    __('Copied Project Settings', 'upstream-copy-project'),
                    __('Here you can change a few options for copied projects.', 'upstream-copy-project')
                ),
                'name'       => __('Publish Status', 'upstream-copy-project'),
                'id'         => 'post_status',
                'type'       => 'select',
                'desc'       => __('The copied project publish status option.', 'upstream-copy-project'),
                'options'    => [
                    'original' => __('Same as Original', 'upstream-copy-project'),
                    'publish'  => __('Published', 'upstream-copy-project'),
                    'draft'    => __('Draft', 'upstream-copy-project'),
                ],
                'default'    => 'original',
            ],
            [
                'name'    => __('Published On', 'upstream-copy-project'),
                'id'      => 'post_date',
                'type'    => 'select',
                'desc'    => __('The copied project publish date.', 'upstream-copy-project'),
                'options' => [
                    'original' => __('Same as Original', 'upstream-copy-project'),
                    'current'  => __('Current Time'),
                ],
                'default' => 'original',
            ],
            [
                'name'    => __('Activity', 'upstream-copy-project'),
                'id'      => 'activity',
                'type'    => 'radio_inline',
                'desc'    => __('Either to copy or not all project activity.', 'upstream-copy-project'),
                'options' => $boolOptionsList,
                'default' => '0',
            ],
            [
                'name'    => upstream_milestone_label_plural(),
                'id'      => 'milestones',
                'type'    => 'radio_inline',
                'desc'    => sprintf(__('Either to copy or not all project %s.', 'upstream-copy-project'),
                    upstream_milestone_label_plural(true)),
                'options' => $boolOptionsList,
                'default' => '1',
            ],
            [
                'name'    => upstream_task_label_plural(),
                'id'      => 'tasks',
                'type'    => 'radio_inline',
                'desc'    => sprintf(__('Either to copy or not all project %s.', 'upstream-copy-project'),
                    upstream_task_label_plural(true)),
                'options' => $boolOptionsList,
                'default' => '1',
            ],
            [
                'name'    => upstream_bug_label_plural(),
                'id'      => 'bugs',
                'type'    => 'radio_inline',
                'desc'    => sprintf(__('Either to copy or not all project %s.', 'upstream-copy-project'),
                    upstream_bug_label_plural(true)),
                'options' => $boolOptionsList,
                'default' => '1',
            ],
            [
                'name'    => upstream_file_label_plural(),
                'id'      => 'files',
                'type'    => 'radio_inline',
                'desc'    => sprintf(__('Either to copy or not all project %s.', 'upstream-copy-project'),
                    upstream_file_label_plural(true)),
                'options' => $boolOptionsList,
                'default' => '1',
            ],
            [
                'name'    => __('Discussions', 'upstream-copy-project'),
                'id'      => 'discussion',
                'type'    => 'radio_inline',
                'desc'    => __('Either to copy or not all project discussions.', 'upstream-copy-project'),
                'options' => $boolOptionsList,
                'default' => '1',
            ],
        ];

        return $fieldsList;
    }

    /**
     * @param $projectId
     * @param $projectCloneId
     */
    protected static function copyMilestones($projectId, $projectCloneId)
    {
        global $wpdb;

        $milestones = Milestones::getMilestonesFromProjectUncached($projectId);
        // Store a list mapping the old milestone ID to the new one, to update the ID in the linked tasks.
        $newMilestonesIdList = [];

        foreach ($milestones as $milestone) {
            // Convert it to a Milestone class
            $milestone = Factory::getMilestone($milestone);

            $milestoneClone = Factory::createMilestone($milestone->getName());
            $milestoneClone->setAssignedTo($milestone->getAssignedTo())
                ->setColor($milestone->getColor())
                ->setCreatedTimeInUtc($milestone->getCreatedTimeInUtc())
                ->setStartDate($milestone->getStartDate())
                ->setEndDate($milestone->getEndDate())
                ->setNotes($milestone->getNotes())
                ->setOrder($milestone->getOrder())
                ->setProgress($milestone->getProgress())
                ->setProjectId($projectCloneId)
                ->setTaskCount($milestone->getTaskCount())
                ->setCategories($milestone->getCategories())
                ->setTaskOpen($milestone->getTaskOpen());

            $newMilestonesIdList[$milestone->getId()] = $milestoneClone->getId();

            $orig_metas = get_post_custom($milestone->getId());
            $new_metas = get_post_custom($milestoneClone->getId());

            $skips = ['upst_project_id','upst_start_date','upst_end_date','upst_color','upst_progress','upst_task_count','upst_task_open','_edit_lock'];

            $metas =  $wpdb->get_results('SELECT * FROM '.$wpdb->postmeta.' WHERE post_id='.$milestone->getId());
            foreach ($metas as $meta) {
                if (!in_array($meta->meta_key, $skips)) {
                    $wpdb->insert($wpdb->postmeta, [
                        'post_id'    => $milestoneClone->getId(),
                        'meta_key'   => $meta->meta_key,
                        'meta_value' => $meta->meta_value,
                    ]);
                }
            }


        }
        return $newMilestonesIdList;
    }

    /**
     * Enqueue all dependent scripts.
     *
     * @since   1.0.0
     * @static
     */
    public static function enqueueScripts()
    {
        if ( ! self::canRunOnThisPage()) {
            return;
        }

        wp_enqueue_script('upstream-copy-project', UP_COPY_PROJECT_URL . '/assets/js/upstream-copy-project.js',
            ['jquery'], UP_COPY_PROJECT_VERSION);
        wp_localize_script('upstream-copy-project', 'upstreamCopyProjectLangStrings', [
            'ERR_INVALID_RESPONSE' => __('Invalid response', 'upstream-copy-project'),
            'ERR_UNABLE_TO_COPY'   => __('It wasn\'t possible to copy this project.', 'upstream-copy-project'),
        ]);
    }

    /**
     * Check if current page is potentially allowed to load assets.
     *
     * @return  bool
     * @since   1.0.4
     * @static
     *
     */
    public static function canRunOnThisPage()
    {
        global $pagenow;

        $isAdmin  = is_admin();
        $postType = get_post_type();

        if (
            ! $isAdmin
            || $pagenow !== 'edit.php'
            || $postType !== 'project'
        ) {
            return false;
        }

        return true;
    }

    /**
     * Enqueue all dependent styles.
     *
     * @since   1.0.0
     * @static
     */
    public static function enqueueStyles()
    {
        if ( ! self::canRunOnThisPage()) {
            return;
        }

        wp_enqueue_style('upstream-copy-project', UP_COPY_PROJECT_URL . '/assets/css/upstream-copy-project.css', [],
            UP_COPY_PROJECT_VERSION);
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
            admin_url('admin.php?page=upstream_copy_project'),
            __('Open Settings Page', 'upstream-copy-project'),
            __('Settings', 'upstream-copy-project')
        );

        return $links;
    }

    /**
     * Render additional post actions links in the Projects page for Admins/UpStream Managers only.
     * Those links are displayed below posts/projects.
     *
     * @param array    $actionsList Associative array containing all action links.
     * @param \WP_Post $post        The current project object.
     *
     * @return  array       $actionsList
     * @since   1.0.0
     * @static
     *
     */
    public static function renderAdditionalPostRowActionLinks($actionsList, $post)
    {
        if (
            $post->post_type === 'project' &&
            $post->post_status !== 'trash' &&
            (
                current_user_can('administrator') ||
                current_user_can('upstream_manager')
            )
        ) {
            $nonce = wp_create_nonce(UP_COPY_PROJECT_NAME . '-nonce');

            $actionsList['clone'] = sprintf(
                '<a href="#" class="upstream-copy-project-anchor" data-post-id="%s" data-nonce="%s" data-text="%s" data-disabled-text="%s">%3$s</a>',
                $post->ID,
                $nonce,
                __('Copy', 'upstream-copy-project'),
                __('Copying...', 'upstream-copy-project')
            );
        }

        return $actionsList;
    }

    /**
     * Create the plugin's option tab on UpStream's settings page.
     *
     * @param array $options Array containing all option fields passed by CMB2.
     *
     * @return  array   $options
     * @since   1.0.0
     * @static
     *
     */
    public static function renderOptionsMetabox($options)
    {
        $pluginId = str_replace('-', '_', UP_COPY_PROJECT_NAME);
        $title    = __('Copy Project', 'upstream-copy-project');

        $pluginOptions = [
            'id'         => $pluginId,
            'title'      => $title,
            'menu_title' => $title,
            'show_names' => true,
            'fields'     => self::getOptionsSchema(),
            'desc'       => "",
            'show_on'    => [
                'key'   => 'options-page',
                'value' => [UP_COPY_PROJECT_NAME],
            ],
        ];

        $pluginOptions = apply_filters($pluginId . '_option_fields', $pluginOptions);

        array_push($options, $pluginOptions);

        return $options;
    }

    /**
     * Initialize EDD updater.
     */
    public static function initUpdater()
    {
        self::$updater = new EDD_SL_Plugin_Updater(
            UP_COPY_PROJECT_API_URL,
            UP_COPY_PROJECT_PLUGIN,
            [
                'version' => UPSTREAM_COPY_PROJECT_VERSION,
                'license' => get_option('upstream_copy_project_license_key'),
                'item_id' => UP_COPY_PROJECT_API_ID,
                'author'  => 'UpStream',
                'beta'    => false,
            ]
        );
    }
}
