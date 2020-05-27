<?php

use UpStream\Factory;
use UpStream\Milestones;

// Exit if accessed directly.
if ( ! defined('ABSPATH')) {
    exit;
}

// Ignore this class if it is already defined.
if (class_exists('UpStream_Gantt_Chart')) {
    return;
}

/**
 * Main UpStream Gantt Chart Class.
 *
 * @since 1.0.0
 */
final class UpStream_Gantt_Chart
{
    const FILTER_MILESTONE_STATUS_IN_PROGRESS = 'in_progress';

    const FILTER_MILESTONE_STATUS_COMPLETED = 'completed';

    const FILTER_MILESTONE_STATUS_UPCOMING = 'upcoming';

    /**
     * @var UpStream The one true UpStream Gantt Chart
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * @var array
     */
    protected $projectList = [];

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
        add_action('wp_enqueue_scripts', [$this, 'enqueues'], 1001);
        add_action('wp_ajax_upstream_project_timeline_update_item', [$this, 'updateTimelineItem']);
    }

    /**
     * Main UpStream Gantt Chart Instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Hook into actions and filters.
     *
     * @since  1.0.0
     */
    public function enqueues()
    {
        if ( ! UpStream_Gantt_Utils::isProjectPage() && ! UpStream_Gantt_Utils::isTimelineOverviewPage()) {
            return;
        }

        wp_enqueue_style('up-timeline', UP_TIMELINE_PLUGIN_URL . 'assets/css/style.css', [], UP_TIMELINE_VERSION,
            'all');
        wp_enqueue_script('up-timeline-cookie', UP_TIMELINE_PLUGIN_URL . 'assets/js/jquery.cookie.js', [
            'jquery',
            'up-bootstrap',
        ], UP_TIMELINE_VERSION, true);
        wp_enqueue_script('up-timeline', UP_TIMELINE_PLUGIN_URL . 'assets/js/jquery.fn.gantt.js', [
            'jquery',
            'up-bootstrap',
        ], UP_TIMELINE_VERSION, false);
        wp_enqueue_script('up-project-timeline', UP_TIMELINE_PLUGIN_URL . 'assets/js/project-timeline.js',
            ['up-timeline'], UP_TIMELINE_VERSION, false);
    }

    /**
     * Echo the timeline in the overview page.
     */
    public function echoOverviewTimeline()
    {
        $this->echoTimeline();
    }

    public static function enqueuesExternal()
    {

        if( ! wp_script_is( 'up-project-timeline', 'enqueued' ) ) {

            wp_enqueue_style('up-timeline', UP_TIMELINE_PLUGIN_URL . 'assets/css/style.css', [], UP_TIMELINE_VERSION,
                'all');
            wp_enqueue_script('up-timeline-cookie', UP_TIMELINE_PLUGIN_URL . 'assets/js/jquery.cookie.js', [
                'jquery',
            ], UP_TIMELINE_VERSION, true);
            wp_enqueue_script('up-timeline', UP_TIMELINE_PLUGIN_URL . 'assets/js/jquery.fn.gantt.js', [
                'jquery'
            ], UP_TIMELINE_VERSION, false);
            wp_enqueue_script('up-project-timeline', UP_TIMELINE_PLUGIN_URL . 'assets/js/project-timeline.js',
                ['up-timeline'], UP_TIMELINE_VERSION, false);

        }
    }

    public static function getTimelineExternal($projectId)
    {
        if (!$projectId) {
            return;
        }

        $me = UpStream_Gantt_Chart::instance();
        $data = [];
        $data = $me->fetchMilestoneAndTasks($projectId);

        // reset array keys to numerical order
        $data = array_values($data);

        $array['gantt_data'] = json_encode($data);
        $array['gantt_url']  = UP_TIMELINE_PLUGIN_URL . 'assets/';

        $data = $array;

        $startDate = (int)get_post_meta($projectId, '_upstream_project_start', true);
        $endDate   = (int)get_post_meta($projectId, '_upstream_project_end', true);

        ob_start();
        ?>
        <div class="row" id="project-section-timeline">
            <div id="timeline" class="col-md-12 col-sm-12 col-xs-12 gantt-chart">

                <div class="x_panel" data-section="timeline">
                    <div class="x_content" style="display:block;">
                        <div class="gantt"></div>
                        <script>
                            (function (window, document, $, undefined) {
                                'use strict';

                                // Since this is not added to detached script, it will load even if the gantt library is not loaded. So we add a delay for now, until we are able to move the script to a file.
                                window.setTimeout(function () {
                                    var onUpstreamLoad = function () {
                                        $('.gantt').gantt({
                                            source: JSON.parse('<?php echo str_replace('"', '\\"',
                                                str_replace("'", "\\'", $data['gantt_data'])); ?>'),
                                            navigate: 'scroll',
                                            scale: 'days',
                                            maxScale: 'months',
                                            minScale: 'days',
                                            itemsPerPage: 30,
                                            months: [
                                                '<?php _e('January', 'upstream-project-timeline'); ?>',
                                                '<?php _e('February', 'upstream-project-timeline'); ?>',
                                                '<?php _e('March', 'upstream-project-timeline'); ?>',
                                                '<?php _e('April', 'upstream-project-timeline'); ?>',
                                                '<?php _e('May', 'upstream-project-timeline'); ?>',
                                                '<?php _e('June', 'upstream-project-timeline'); ?>',
                                                '<?php _e('July', 'upstream-project-timeline'); ?>',
                                                '<?php _e('August', 'upstream-project-timeline'); ?>',
                                                '<?php _e('September', 'upstream-project-timeline'); ?>',
                                                '<?php _e('October', 'upstream-project-timeline'); ?>',
                                                '<?php _e('November', 'upstream-project-timeline'); ?>',
                                                '<?php _e('December', 'upstream-project-timeline'); ?>'
                                            ],
                                            dow: [
                                                '<?php echo _x('S', 'Sunday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('M', 'Monday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('T', 'Tuesday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('W', 'Wednesday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('T', 'Thursday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('F', 'Friday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('S', 'Saturday', 'upstream-project-timeline'); ?>'
                                            ],
                                            startDate: '<?php echo $startDate -  (3*24*60*60); ?>',
                                            endDate: '<?php echo $endDate + (3*24*60*60); ?>'
                                        });
/*
                                        $('.gantt').popover({
                                            selector: '.bar',
                                            trigger: 'hover',
                                            html: true,
                                            container: 'body',
                                            placement: 'bottom'
                                        });

                                        $('.gantt').tooltip({
                                            selector: '.o-assignees',
                                            placement: 'left auto'
                                        });

 */
                                    };

                                    onUpstreamLoad();
                                }, 1000);

                            })(window, window.document, jQuery);
                        </script>


                    </div>
                </div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }



    /**
     * Add the chart
     *
     * @since  1.0.0
     */
    public function echoTimeline()
    {
        if (UpStream_Gantt_Utils::areMilestonesDisabled() || ( ! UpStream_Gantt_Utils::isProjectPage() && ! UpStream_Gantt_Utils::isTimelineOverviewPage())) {
            return;
        }

        $data = $this->getGanttData();

        $title = UpStream_Gantt_Utils::isTimelineOverviewPage() ? __('Projects Timeline',
            'upstream-project-timeline') : __('Project Timeline', 'upstream-project-timeline');

        $collapseBox = \UpStream\Frontend\getSectionCollapseState('timeline') === 'closed';

        if ( ! function_exists('upstream_admin_get_all_project_users')) {
            require_once UPSTREAM_PLUGIN_DIR . 'includes/admin/metaboxes/metabox-functions.php';
        }

        $sortBy  = 'milestones';
        $filters = [
            'projects'         => '',
            'milestone_status' => '',
            'users'            => '',
        ];

        if (UpStream_Gantt_Utils::isTimelineOverviewPage()) {
            $sortBy                      = isset($_GET['uptl_sort_by']) ? $_GET['uptl_sort_by'] : 'projects';
            $filters['projects']         = isset($_GET['uptl_filter_projects']) ? $_GET['uptl_filter_projects'] : '';
            $filters['milestone_status'] = isset($_GET['uptl_filter_milestone_status']) ? $_GET['uptl_filter_milestone_status'] : '';
            $filters['users']            = isset($_GET['uptl_filter_user']) ? $_GET['uptl_filter_user'] : '';
        }

        $projects          = $this->fetchUserProjects();
        $users             = $this->fetchUsers();
        $startDate         = 0;
        $endDate           = 0;
        $filteredStartDate = false;
        $filteredEndDate   = false;

        if (UpStream_Gantt_Utils::isTimelineOverviewPage()) {
            // Check if we have a filter
            if (isset($_GET['uptl_filter_start_date'])) {
                $startDate = (int)$_GET['uptl_filter_start_date'];
                $filteredStartDate = true;
            }

            if (isset($_GET['uptl_filter_end_date'])) {
                $endDate = (int)$_GET['uptl_filter_end_date'];
                $filteredEndDate = true;
            }

            foreach ($projects as $project) {

                if (!$filteredStartDate) {
                    $projectStartDate = (int)get_post_meta($project->id, '_upstream_project_start', true);
                    if ($startDate == 0 || $projectStartDate < $startDate) {
                        $startDate = $projectStartDate;
                    }
                }

                if (!$filteredEndDate) {
                    $projectEndDate = (int)get_post_meta($project->id, '_upstream_project_end', true);
                    if ($endDate == 0 || $projectEndDate < $endDate) {
                        $endDate = $projectEndDate;
                    }
                }
            }
        } else {
            $projectId = get_the_ID();

            $startDate = (int)get_post_meta($projectId, '_upstream_project_start', true);
            $endDate   = (int)get_post_meta($projectId, '_upstream_project_end', true);
        }

        ?>
        <div class="row" id="project-section-timeline">
            <div id="timeline" class="col-md-12 col-sm-12 col-xs-12 gantt-chart">

                <div class="x_panel" data-section="timeline">
                    <div class="x_title">
                        <h2>
                            <?php if (UpStream_Gantt_Utils::isProjectPage()) : ?>
                                <i class="fa fa-bars sortable_handler"></i>
                            <?php endif; ?>
                            <i class="fa fa-align-left"></i>
                            <?php echo esc_html($title); ?>
                        </h2>
                        <ul class="nav navbar-right panel_toolbox">
                            <li>
                                <a class="collapse-link">
                                    <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
                                </a>
                            </li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">

                        <?php if (UpStream_Gantt_Utils::isTimelineOverviewPage()): ?>
                            <form method="GET" id="up_gantt_form" action="<?php echo esc_url(home_url('projects')); ?>"
                                  class="form-inline">
                                <div id="gantt_filters" class="form-group">
                                    <?php //*********** SORT BY ************* ?>
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <i class="fa fa-search"></i>
                                        </div>
                                        <select name="uptl_sort_by" class="form-control input-sm">
                                            <option
                                                value="projects" <?php echo selected($sortBy,
                                                'projects'); ?>>
                                                <?php echo esc_html__('Sort by', 'upstream-project-timeline'); ?>
                                                <?php echo esc_html(upstream_project_label_plural()); ?></option>

                                            <option value="assigned_to" <?php echo selected($sortBy,
                                                'assigned_to'); ?>>
                                                <?php echo esc_html__('Sort by', 'upstream-project-timeline'); ?>
                                                <?php echo esc_html__('Assigned To', 'upstream'); ?>
                                            </option>

                                            <?php do_action('upstream_project_timeline_sort_by_options', $sortBy); ?>
                                        </select>
                                    </div>

                                    <?php //*********** FILTER BY PROJECTS ************* ?>
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <i class="fa fa-flag"></i>
                                        </div>
                                        <select name="uptl_filter_projects" class="form-control input-sm">
                                            <option
                                                value="" <?php echo selected($filters['projects'], ''); ?>>
                                                <?php echo esc_html(sprintf(__('Show all %s', 'upstream-project-timeline'),
                                                    upstream_project_label_plural())); ?></option>

                                            <?php foreach ($projects as $project) : ?>
                                                <option
                                                    value="<?php echo $project->id; ?>" <?php echo selected($filters['projects'],
                                                    $project->id); ?>><?php echo esc_html($project->title); ?>
                                                </option>
                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <?php //*********** FILTER BY MILESTONE STATUSES ************* ?>
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <i class="fa fa-bookmark"></i>
                                        </div>
                                        <select name="uptl_filter_milestone_status" class="form-control input-sm">
                                            <option
                                                value="" <?php echo selected($filters['milestone_status'], ''); ?>>
                                                <?php echo esc_html(sprintf(__('Show all %s Statuses',
                                                    'upstream-project-timeline'),
                                                    upstream_milestone_label())); ?></option>

                                            <option
                                                value="in_progress" <?php echo selected($filters['milestone_status'],
                                                'in_progress'); ?>>
                                                <?php echo esc_html(sprintf(__('Only %s in progress',
                                                    'upstream-project-timeline'),
                                                    upstream_milestone_label_plural())); ?></option>

                                            <option
                                                value="completed" <?php echo selected($filters['milestone_status'],
                                                'completed'); ?>>
                                                <?php echo esc_html(sprintf(__('Only completed %s', 'upstream-project-timeline'),
                                                    upstream_milestone_label_plural())); ?></option>

                                            <option
                                                value="upcoming" <?php echo selected($filters['milestone_status'],
                                                'upcoming'); ?>>
                                                <?php echo esc_html(sprintf(__('Only upcoming %s', 'upstream-project-timeline'),
                                                    upstream_milestone_label_plural())); ?></option>
                                        </select>
                                    </div>

                                    <?php //*********** FILTER BY ASSIGNED USERS ************* ?>
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </div>
                                        <select name="uptl_filter_user" class="form-control input-sm">
                                            <option
                                                value="" <?php echo selected($filters['users'], ''); ?>>
                                                <?php echo esc_html__('Show all users', 'upstream-project-timeline'); ?>
                                            </option>

                                            <?php if ( ! empty($users)) : ?>
                                                <?php foreach ($users as $userId => $userName) : ?>
                                                    <option
                                                        value="<?php echo $userId; ?>"
                                                        <?php echo selected($filters['users'], $userId); ?>>
                                                        <?php echo esc_html($userName); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                        </select>
                                    </div>

                                    <?php do_action('upstream_project_timeline_overview_timeline_filters', $sortBy,
                                        $filters); ?>

                                    <?php //*********** FILTER BY START/END DATES ************* ?>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input type="text" class="form-control o-datepicker"
                                                   placeholder="<?php esc_html_e('Start Date', 'upstream-project-timeline'); ?>"
                                                   id="uptl-filter-start_date" autocomplete="off">
                                        </div>
                                        <input type="hidden" id="uptl-filter-start_date_timestamp"
                                               name="uptl_filter_start_date"
                                               data-column="start_date" data-compare-operator=">=">
                                    </div>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input type="text" class="form-control o-datepicker"
                                                   placeholder="<?php esc_html_e('End Date', 'upstream-project-timeline'); ?>"
                                                   id="uptl-filter-end_date" autocomplete="off">
                                        </div>
                                        <input type="hidden" id="uptl-filter-end_date_timestamp"
                                               name="uptl_filter_end_date"
                                               data-column="end_date" data-compare-operator="<=">
                                    </div>

                                    <div class="form-group">
                                        <div class="input-group">
                                            <input type="submit" class="form-control" id="uptl-submit-filters"
                                                   value="<?php esc_html_e('Submit', 'upstream-project-timeline'); ?>">
                                        </div>
                                        <div class="input-group">
                                            <input type="reset" class="form-control" id="uptl-clear-filters"
                                                   value="<?php esc_html_e('Reset', 'upstream-project-timeline'); ?>">
                                        </div>
                                    </div>

                                </div>

                                <input type="hidden" name="view" value="timeline"/>
                            </form>

                            <script>
                                jQuery(function ($) {
                                    $('#up_gantt_form #gantt_filters input.o-datepicker').change(function () {
                                        var $this = $(this);

                                        var date = $this.datepicker('getDate');

                                        if (date === null) {
                                            return;
                                        }

                                        date = (date.getTime() / 1000).toFixed(0);

                                        $('input#' + $this.attr('id') + '_timestamp').val(date);
                                    });

                                    <?php if (isset( $_GET['uptl_filter_start_date'] ) && ! empty( $_GET['uptl_filter_start_date'] )) : ?>
                                    // Set start date filter, if set.
                                    var startDate = parseInt('<?php echo (int) $_GET['uptl_filter_start_date'] * 1000; ?>');
                                    if (startDate > 0) {
                                        $('#uptl-filter-start_date').datepicker('setDate', new Date(startDate));
                                    }
                                    <?php endif; ?>

                                    <?php if (isset( $_GET['uptl_filter_end_date'] ) && ! empty( $_GET['uptl_filter_end_date'] )) : ?>
                                    // Set end date filter, if set.
                                    var endDate = parseInt('<?php echo (int) $_GET['uptl_filter_end_date'] * 1000; ?>');

                                    if (endDate > 0) {
                                        $('#uptl-filter-end_date').datepicker('setDate', new Date(endDate));
                                    }
                                    <?php endif; ?>


                                    $('#uptl-clear-filters').click(function () {
                                        var form = this.form;
                                        window.setTimeout(function () {
                                            $('#uptl-filter-start_date_timestamp, #uptl-filter-end_date_timestamp').val('');
                                            $('select', form).val('');
                                            form.submit();
                                        }, 500);
                                    });
                                });
                            </script>
                        <?php endif; ?>

                        <div class="gantt"></div>
                        <script>
                            (function (window, document, $, undefined) {
                                'use strict';

                                // Since this is not added to detached script, it will load even if the gantt library is not loaded. So we add a delay for now, until we are able to move the script to a file.
                                window.setTimeout(function () {
                                    var onUpstreamLoad = function () {
                                        $('.gantt').gantt({
                                            source: JSON.parse('<?php echo str_replace('"', '\\"',
                                                str_replace("'", "\\'", $data['gantt_data'])); ?>'),
                                            navigate: 'scroll',
                                            scale: 'days',
                                            maxScale: 'months',
                                            minScale: 'days',
                                            itemsPerPage: 30,
                                            useCookie: true,
                                            cookieKey: 'upstream.gantt',
                                            months: [
                                                '<?php _e('January', 'upstream-project-timeline'); ?>',
                                                '<?php _e('February', 'upstream-project-timeline'); ?>',
                                                '<?php _e('March', 'upstream-project-timeline'); ?>',
                                                '<?php _e('April', 'upstream-project-timeline'); ?>',
                                                '<?php _e('May', 'upstream-project-timeline'); ?>',
                                                '<?php _e('June', 'upstream-project-timeline'); ?>',
                                                '<?php _e('July', 'upstream-project-timeline'); ?>',
                                                '<?php _e('August', 'upstream-project-timeline'); ?>',
                                                '<?php _e('September', 'upstream-project-timeline'); ?>',
                                                '<?php _e('October', 'upstream-project-timeline'); ?>',
                                                '<?php _e('November', 'upstream-project-timeline'); ?>',
                                                '<?php _e('December', 'upstream-project-timeline'); ?>'
                                            ],
                                            dow: [
                                                '<?php echo _x('S', 'Sunday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('M', 'Monday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('T', 'Tuesday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('W', 'Wednesday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('T', 'Thursday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('F', 'Friday', 'upstream-project-timeline'); ?>',
                                                '<?php echo _x('S', 'Saturday', 'upstream-project-timeline'); ?>'
                                            ],
                                            waitText: '<?php _e('Please wait...', 'upstream-project-timeline'); ?>',
                                            onItemClick: function (bar) {
                                                <?php if ( ! apply_filters('upstream_project_timeline_bar_onclick_override',
                                                false)) : ?>
                                                var type = bar.data('type');
                                                var id = bar.data('id');
                                                var $tr = $('table#' + type + 's').find('tr[data-id="' + id + '"]');
                                                var $pencil = $tr.find('.fa-pencil');

                                                $pencil.trigger('click');
                                                <?php endif; ?>
                                                <?php do_action('upstream_project_timeline_bar_onclick'); ?>
                                            },
                                            onResizeOrMove: function (data, direction, startDate, endDate) {
                                                <?php
                                                $pluginId = 'upstream_project_timeline';
                                                $optionsMap = (array)get_option($pluginId);
                                                if (!isset($optionsMap['drag_drop']) || $optionsMap['drag_drop'] == 1) {
                                                ?>

                                                if (!startDate || !endDate) {
                                                    return;
                                                }

                                                startDate = new Date(startDate / 1000) - (60 * (new Date()).getTimezoneOffset());
                                                endDate = new Date(endDate / 1000) - (60 * (new Date()).getTimezoneOffset());

                                                // Store the new dates for resized items.
                                                $.ajax({
                                                    url: upstream.ajaxurl,
                                                    type: 'post',
                                                    async: false,
                                                    data: {
                                                        action: 'upstream_project_timeline_update_item',
                                                        nonce: upstream.security,
                                                        projectId: $('#project_id').val(),
                                                        itemId: data.data('id'),
                                                        itemType: data.data('type'),
                                                        direction: direction,
                                                        startDate: startDate,
                                                        endDate: endDate
                                                    }
                                                });

                                                <?php
                                                } else {
                                                ?>
                                                alert('<?php _e('You are not allowed to modify the project timeline by drag and drop.', 'upstream-project-timeline'); ?>');
                                                <?php } ?>

                                            },
                                            startDate: '<?php echo $startDate -  (3*24*60*60); ?>',
                                            endDate: '<?php echo $endDate +  (3*24*60*60); ?>'
                                        });

                                        $('.gantt').popover({
                                            selector: '.bar',
                                            trigger: 'hover',
                                            html: true,
                                            container: 'body',
                                            placement: 'bottom'
                                        });

                                        $('.gantt').tooltip({
                                            selector: '.o-assignees',
                                            placement: 'left auto'
                                        });
                                    };

                                    onUpstreamLoad();
                                }, 300);

                            })(window, window.document, jQuery);
                        </script>

                        <?php do_action('upstream_project_timeline_after_gantt'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }


    protected function overrideAccessField($orig_value, $object_type, $object_id, $parent_type, $parent_id, $field, $action)
    {
       return apply_filters('upstream.project-timeline:gantt_override_access_field', upstream_override_access_field($orig_value, $object_type, $object_id, $parent_type, $parent_id, $field, $action));
    }

    protected function overrideAccessObject($orig_value, $object_type, $object_id, $parent_type, $parent_id, $action)
    {
        return apply_filters('upstream.project-timeline:gantt_override_access_object', upstream_override_access_object($orig_value, $object_type, $object_id, $parent_type, $parent_id, $action));
    }


    /**
     * @param array $array
     *
     * @return array|void
     * @throws Exception
     *
     * @since 1.0.0
     * @since 1.3.0 Renamed from gantt_data to getGanttData
     */
    public function getGanttData($array = [])
    {
        if ( ! UpStream_Gantt_Utils::isProjectPage() && ! UpStream_Gantt_Utils::isTimelineOverviewPage()) {
            return;
        }

        $sortBy = 'milestones';
        if (UpStream_Gantt_Utils::isTimelineOverviewPage()) {
            if ( ! isset($_GET['uptl_sort_by']) || empty($_GET['uptl_sort_by'])) {
                $sortBy = 'projects';
            } else {
                $sortBy = sanitize_text_field($_GET['uptl_sort_by']);
            }
        }

        $data = [];
        if ($sortBy === 'milestones') {
            $data = $this->fetchMilestoneAndTasks();
        } elseif ($sortBy === 'projects') {
            $data = $this->fetchMilestonesByProject();
        } elseif ($sortBy === 'assigned_to') {
            $data = $this->fetchMilestonesByAssigners();
        }

        $data = apply_filters('upstream_project_timeline_gantt_data', $data, $sortBy);

        // reset array keys to numerical order
        $data = array_values($data);

        $array['gantt_data'] = json_encode($data);
        $array['gantt_url']  = UP_TIMELINE_PLUGIN_URL . 'assets/';

        return $array;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function fetchMilestoneAndTasks($pid = 0)
    {
        $data = [];

        $xitems    = upstream_project_milestones($pid);
        $xsubItems = upstream_project_tasks($pid);

        if (!$xitems) {
            $xitems = [];
        }

        if (!$xsubItems) {
            $xsubItems = [];
        }

        // RSD: for perms
        $projectId = get_the_ID();
        $items = [];
        foreach ($xitems as $key => $value) {
            if (
                $this->overrideAccessObject(true, UPSTREAM_ITEM_TYPE_MILESTONE, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                $this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_MILESTONE, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'start_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                $this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_MILESTONE, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'end_date', UPSTREAM_PERMISSIONS_ACTION_VIEW)
            ) {
                $items[$key] = $xitems[$key];
            }
        }

        $subItems = [];
        foreach ($xsubItems as $value) {
            if (
                $this->overrideAccessObject(true, UPSTREAM_ITEM_TYPE_TASK, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                $this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_TASK, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'start_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                $this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_TASK, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'end_date', UPSTREAM_PERMISSIONS_ACTION_VIEW)
            ) {
                $subItems[] = $value;
            }
        }

        if (empty($items)) {
            $items = [];
        }

        $items['0'] = [
            'id' => 0,
            'start_date' => 0
        ];

        $items    = UpStream_Gantt_Utils::quickSortByStartDate($items);
        $subItems = UpStream_Gantt_Utils::quickSortByStartDate($subItems);

        /**
         * Add the milestone data
         */
        $i = 0;
        if ($items) {
            $users = upstreamGetUsersMap();

            $useTzBackwardsComp = version_compare(UPSTREAM_VERSION, '1.15.1', '<');

            $tasksStatuses = getTasksStatuses();

            foreach ($items as $index => $milestone) {

                $mi = 0;
                if (isset($milestone['id'])) {
                    $mi = $milestone['id'];
                }

                if ($mi != 0) {

                    // if no start or end
                    if (
                        !isset($milestone['start_date'])
                        || empty($milestone['start_date'])
                        || !isset($milestone['end_date'])
                        || empty($milestone['end_date'])
                    ) {
                        continue;
                    }

                    // gather and format some of our data
                    $milestoneAssignees = [];
                    if (isset($milestone['assigned_to'])) {
                        if (!is_array($milestone['assigned_to'])) {
                            $milestoneAssignees = [$milestone['assigned_to']];
                        } else {
                            $milestoneAssignees = $milestone['assigned_to'];
                        }

                        $milestoneAssignees = array_filter(array_map('intval', $milestoneAssignees));

                        $milestoneAssigneesNames = [];
                        foreach ($milestoneAssignees as $milestoneAssignee) {
                            if (isset($users[$milestoneAssignee])) {
                                $milestoneAssigneesNames[] = $users[$milestoneAssignee];
                            }
                        }
                    }

                    $color = isset($milestone['color']) ? $milestone['color'] : '#aaaaaa';
                    $open = isset($milestone['task_open']) ? $milestone['task_open'] : '0';
                    $progress = $milestone['progress'] ? $milestone['progress'] : '0';

                    $label = sprintf(
                        '%s - %d%s',
                        $milestone['milestone'],
                        $progress,
                        __('% Complete', 'upstream')
                    );

                    // create the description for the popover
                    $desc = '<strong>' . esc_html($milestone['milestone']) . '</strong><br>';
                    $desc .= esc_html(upstream_format_date($milestone['start_date']) . ' - ' . upstream_format_date($milestone['end_date'])) . '<br>';

                    if ($this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_MILESTONE, $milestone['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'assigned_to', UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                        if (isset($milestoneAssigneesNames)
                            && count($milestoneAssigneesNames) > 0
                        ) {
                            $desc .= sprintf('%s %s<br>', esc_html__('Assigned To: ', 'upstream'),
                                esc_html(implode(', ', $milestoneAssigneesNames)));
                        }
                    }

                    if ($this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_MILESTONE, $milestone['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'progress', UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                        $desc .= esc_html__('Progress', 'upstream') . ': ' . esc_html($progress) . '%<br>';
                    }

                    if ($this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_MILESTONE, $milestone['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'tasks', UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                        $desc .= sprintf('%d %s / %d %s', $milestone['task_count'], esc_html(upstream_task_label_plural()), $open,
                            esc_html__('Open', 'upstream'));
                    }

                    if ($useTzBackwardsComp) {
                        $startDateTime = new DateTime('@' . $milestone['start_date']);
                        $startDateTimestamp = (int)$startDateTime->format('U');

                        $endDateTime = new DateTime('@' . $milestone['end_date']);
                        $endDateTimestamp = (int)$endDateTime->format('U');

                        unset($startDateTime, $endDateTime);
                    } else {
                        $startDateTimestamp = (int)$milestone['start_date'];
                        $endDateTimestamp = (int)$milestone['end_date'];
                    }

                    $startDate = new DateTime("@{$startDateTimestamp}");
                    $startDateTimestamp = (int)$startDate->format('U');

                    $endDate = new DateTime("@{$endDateTimestamp}");
                    $endDateTimestamp = (int)$endDate->format('U');

                    /*
                     * Create the array for the milestone
                     */
                    $data[$i]['id'] = $milestone['id'];
                    $data[$i]['type'] = 'item';
                    $data[$i]['label'] = "<i class='fa fa-flag'></i> " . esc_html($milestone['milestone']);
                    $data[$i]['values'][] = [
                        'from' => $startDateTimestamp * 1000,
                        'to' => $endDateTimestamp * 1000,
                        'label' => $label,
                        'color' => $color,
                        'progress' => $progress,
                        'desc' => $desc,
                        'dataObj' => ['id' => $milestone['id'], 'type' => 'milestone'],
                    ];


                } else {

                }

                $i++;

                /**
                 * Add the task data
                 */
                if ($subItems) {
                    foreach ($subItems as $ti => $task) {

                        if (isset($task['milestone']) && $task['milestone'] == '') {
                            unset($task['milestone']);
                        }

                        // if the task belongs to the current milestone
                        if ((isset($task['milestone']) && $task['milestone'] == $milestone['id']) || ($milestone['id'] == 0 && !isset($task['milestone']))) {

                            // if no start or end
                            if ( empty( $task['start_date']) || empty( $task['end_date'])) {
                                continue;
                            }

                            if ($useTzBackwardsComp) {
                                $startDateTime      = new DateTime(date('Y-m-d H:i:s', $task['start_date']));
                                $startDateTimestamp = (int)$startDateTime->format('U');

                                $endDateTime      = new DateTime(date('Y-m-d H:i:s', $task['end_date']));
                                $endDateTimestamp = (int)$endDateTime->format('U');
                            } else {
                                $startDateTimestamp = (int)$task['start_date'];
                                $endDateTimestamp   = (int)$task['end_date'];
                            }

                            //                $startDate          = new DateTime("@{$startDateTimestamp}");
                            //               $startDateTimestamp = (int)$startDate->format('U');

                            //             $endDate          = new DateTime("@{$endDateTimestamp}");
                            //            $endDateTimestamp = (int)$endDate->format('U');

                            // RSD: edded this for compatibility with the rest of the code
                            // TODO: remove this
                            $offset  = get_option( 'gmt_offset' );
                            $startDateTimestamp = $startDateTimestamp + ($offset>0 ? $offset*60*60 : 0);
                            $endDateTimestamp = $endDateTimestamp + ($offset>0 ? $offset*60*60 : 0);
                            $startDate = upstream_format_date($startDateTimestamp /*+ ($offset>0 ? $offset*60*60 : 0)*/);
                            $endDate = upstream_format_date($endDateTimestamp /*+ ($offset>0 ? $offset*60*60 : 0)*/);
                            // RSD: end added

                            // gather and format some of our data
                            $taskAssignees = [];
                            if (isset($task['assigned_to'])) {
                                if ( ! is_array($task['assigned_to'])) {
                                    $taskAssignees = [$task['assigned_to']];
                                } else {
                                    $taskAssignees = $task['assigned_to'];
                                }

                                $taskAssignees = array_filter(array_map('intval', $taskAssignees));
                            }

                            $taskAssigneesCount = count($taskAssignees);

                            $taskStatus = isset($task['status']) ? $task['status'] : '';
                            $color      = isset($tasksStatuses[$taskStatus])
                                ? $tasksStatuses[$task['status']]['color']
                                : '#aaaaaa';

                            $progress = isset($task['progress']) && ! empty($task['progress']) ? $task['progress'] : '0';
                            $label    = esc_html($task['title'] . ' - ' . $progress . __('% Complete', 'upstream'));

                            // create the description for the popover
                            $desc = '<strong>' . esc_html($task['title']) . '</strong><br>';
                            $desc .= $startDate . ' - ' . $endDate . '<br>';

                            if ($taskAssigneesCount > 0) {
                                $taskAssigneesNames = [];
                                foreach ($taskAssignees as $assignee) {
                                    if (isset($users[$assignee])) {
                                        $taskAssigneesNames[] = $users[$assignee];
                                    }
                                }

                                if (count($taskAssigneesNames) > 0) {
                                    $taskAssigneesNames = implode(', ', $taskAssigneesNames);
                                    if ($this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_TASK, $task['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'assigned_to', UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                                        $desc .= sprintf('%s %s<br>', esc_html__('Assigned To: ', 'upstream'),
                                            esc_html($taskAssigneesNames));
                                    }
                                } else {
                                    $taskAssigneesNames = "";
                                }
                            }

                            if ($this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_TASK, $task['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'progress', UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                                $desc .= esc_html__('Progress', 'upstream') . ': ' . esc_html($progress) . '%<br>';
                            }

                            $taskMilestoneName = isset($milestone['milestone']) ? $milestone['milestone'] : '(None)';

                            $desc .= esc_html(sprintf('%1s: %2s', upstream_milestone_label(), $taskMilestoneName));

                            /*
                             * Create the array for the task
                             */
                            $data[$i]['id']     = $task['id'];
                            $data[$i]['type']   = 'subitem';
                            $data[$i]['source'] = 'task';
                            $data[$i]['label']  = $task['title'];


                            if ($taskAssigneesCount > 0) {
                                $taskAssigneesHtml = [
                                    sprintf(
                                        '<div class="o-assignees" title="%s %s">',
                                        esc_html__('Assigned To: ', 'upstream'),
                                        esc_html($taskAssigneesNames)
                                    ),
                                    '<img src="' . getUserAvatarURL($taskAssignees[0]) . '" height="20" alt="' . esc_attr($users[$taskAssignees[0]]) . '">',
                                ];

                                if ($taskAssigneesCount > 1) {
                                    $taskAssigneesHtml[] = '<span class="badge">+' . ($taskAssigneesCount - 1) . '</span>';
                                }

                                $taskAssigneesHtml[] = '</div>';

                                $data[$i]['assigned_to'] = implode('', $taskAssigneesHtml);
                            }

                            $data[$i]['values'][] = [
                                'from'     => $startDateTimestamp * 1000,
                                'to'       => $endDateTimestamp * 1000,
                                'label'    => $label,
                                'color'    => $color,
                                'progress' => $progress,
                                'desc'     => $desc,
                                'dataObj'  => ['id' => $task['id'], 'type' => 'task'],
                            ];
                        }

                        $i++;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function fetchMilestonesByProject()
    {
        $data = [];

        $projects = $this->fetchUserProjects();

        $filterProject         = $this->getFilterProjects();
        $filterMilestoneStatus = $this->getFilterMilestoneStatus();
        $filterUsers           = $this->getFilterUsers();
        $filterStartDate       = $this->getFilterStartDate();
        $filterEndDate         = $this->getFilterEndDate();

        if ( ! empty($projects)) {
            foreach ($projects as $project) {
                // Filter the project, if a filter is set.
                if ( ! empty($filterProject)) {
                    if ((int)$project->id !== $filterProject) {
                        continue;
                    }
                }

                $xmilestones = \UpStream\Milestones::getInstance()->getMilestonesFromProject($project->id, true);

                // RSD: for perms
                $milestones = [];
                foreach ($xmilestones as $key => $value) {
                    if (
                        $this->overrideAccessObject(true, UPSTREAM_ITEM_TYPE_MILESTONE, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $project->id, UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                        $this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_MILESTONE, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'start_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                        $this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_MILESTONE, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'end_date', UPSTREAM_PERMISSIONS_ACTION_VIEW)
                    ) {
                        $milestones[$key] = $xmilestones[$key];
                    }
                }



                $milestones = UpStream_Gantt_Utils::quickSortByStartDate($milestones);

                if ( ! empty($milestones)) {
                    $projectMilestones = [];

                    $firstStartDate = 0;
                    $lastEndDate    = 0;

                    foreach ($milestones as $milestone) {
                        if (
                            ! isset($milestone['start_date'])
                            || empty($milestone['start_date'])
                            || ! isset($milestone['end_date'])
                            || empty($milestone['end_date'])
                        ) {
                            continue;
                        }

                        $milestone = \UpStream\Factory::getMilestone($milestone['id']);

                        /**
                         * Filter the milestone for gantt data. If returns true, it is kept in the list.
                         * If false, it is ignored and not included in the data.
                         *
                         * @param bool               $include
                         * @param UpStream\Milestone $milestone
                         */
                        if ( ! apply_filters('upstream_project_timeline_gantt_filter_milestone', true, $milestone)) {
                            continue;
                        }

                        $startDateTimestamp = $milestone->getStartDate('unix');
                        $endDateTimestamp   = $milestone->getEndDate('unix');

                        // Filter by Milestone Status
                        if ($filterMilestoneStatus === static::FILTER_MILESTONE_STATUS_IN_PROGRESS && ! $milestone->isInProgress()) {
                            continue;
                        }

                        if ($filterMilestoneStatus === static::FILTER_MILESTONE_STATUS_COMPLETED && ! $milestone->isCompleted()) {
                            continue;
                        }

                        if ($filterMilestoneStatus === static::FILTER_MILESTONE_STATUS_UPCOMING && ! $milestone->isUpcoming()) {
                            continue;
                        }

                        // Filter by Users
                        if ( ! empty($filterUsers) && ! in_array($filterUsers, $milestone->getAssignedTo())) {
                            continue;
                        }

                        // Filter by Start and End Dates
                        if ( ! empty($filterStartDate) && $startDateTimestamp < $filterStartDate) {
                            continue;
                        }
                        if ( ! empty($filterEndDate) && $endDateTimestamp > $filterEndDate) {
                            continue;
                        }

                        if ($firstStartDate == 0 || $startDateTimestamp < $firstStartDate) {
                            $firstStartDate = $startDateTimestamp;
                        }

                        if ($lastEndDate == 0 || $endDateTimestamp > $lastEndDate) {
                            $lastEndDate = $endDateTimestamp;
                        }

                        /*
                         * Create the array for the task
                         */
                        $milestoneData           = [];
                        $milestoneData['id']     = $milestone->getId();
                        $milestoneData['type']   = 'subitem';
                        $milestoneData['source'] = 'milestone';
                        $milestoneData['label']  = $milestone->getName();

                        /**
                         * Filter the label of the milestone bar in the timeline.
                         *
                         * @param string             $label
                         * @param UpStream\Milestone $milestone
                         *
                         * @return string
                         */
                        $itemLabel = apply_filters('upstream_project_timeline_bar_milestone_label',
                            $milestone->getName(), $milestone);

                        $milestoneData['values'][] = [
                            'from'     => $startDateTimestamp * 1000,
                            'to'       => $endDateTimestamp * 1000,
                            'label'    => $itemLabel,
                            'color'    => $milestone->getColor(),
                            'progress' => $milestone->getProgress(),
                            'desc'     => '',
                            'dataObj'  => [
                                'id'   => $milestone->getId(),
                                'type' => 'milestone',
                            ],
                        ];

                        $projectMilestones[] = $milestoneData;
                    }

                    if ( ! empty($projectMilestones)) {
                        $projectData = [];

                        if (empty($project->startDateTimestamp)) {
                            $project->startDateTimestamp = $firstStartDate;
                        }

                        if (empty($project->endDateTimestamp)) {
                            $project->endDateTimestamp = $lastEndDate;
                        }

                        /*
                         * Create the array for the project
                         */
                        $projectData['id']       = $project->id;
                        $projectData['type']     = 'item';
                        $projectData['source']   = 'project';
                        $projectData['label']    = "<i class='fa fa-flag'></i> " . esc_html($project->title);
                        $projectData['values'][] = [
                            'from'     => $project->startDateTimestamp * 1000,
                            'to'       => $project->endDateTimestamp * 1000,
                            'label'    => esc_html($project->title),
                            'color'    => '#535192',
                            'progress' => 0,
                            'desc'     => '',
                            'dataObj'  => [
                                'id'   => $project->id,
                                'type' => 'hidden',
                            ],
                        ];

                        $data[] = $projectData;
                        $data   = array_merge($data, $projectMilestones);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function fetchUserProjects()
    {
        if (empty($this->projectList)) {
            if ( ! function_exists('upstream_user_projects')) {
                require_once UPSTREAM_PLUGIN_DIR . '/includes/up-project-functions.php';
            }

            $this->projectsList = upstream_user_projects();
        }

        return $this->projectsList;
    }

    /**
     * @return int
     */
    public function getFilterProjects()
    {
        return isset($_GET['uptl_filter_projects']) ? (int)$_GET['uptl_filter_projects'] : 0;
    }

    public function getFilterMilestoneStatus()
    {
        return isset($_GET['uptl_filter_milestone_status']) ? sanitize_text_field($_GET['uptl_filter_milestone_status']) : '';
    }

    public function getFilterUsers()
    {
        return isset($_GET['uptl_filter_user']) ? (int)$_GET['uptl_filter_user'] : 0;
    }

    public function getFilterStartDate()
    {
        return isset($_GET['uptl_filter_start_date']) ? (int)$_GET['uptl_filter_start_date'] : 0;
    }

    public function getFilterEndDate()
    {
        return isset($_GET['uptl_filter_end_date']) ? (int)$_GET['uptl_filter_end_date'] : 0;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function fetchMilestonesByAssigners()
    {
        $data = [];

        $projects = $this->fetchUserProjects();

        if ( ! empty($projects)) {
            // Multidimensional list of milestones per user ID.
            $userMilestones = [];

            $firstStartDate = PHP_INT_MAX;
            $lastEndDate    = PHP_INT_MIN;

            $filterProject         = $this->getFilterProjects();
            $filterMilestoneStatus = $this->getFilterMilestoneStatus();
            $filterUsers           = $this->getFilterUsers();
            $filterStartDate       = $this->getFilterStartDate();
            $filterEndDate         = $this->getFilterEndDate();

            foreach ($projects as $project) {
                // Filter the project, if a filter is set.
                if ( ! empty($filterProject)) {
                    if ((int)$project->id !== $filterProject) {
                        continue;
                    }
                }

                $xmilestones = \UpStream\Milestones::getInstance()->getMilestonesFromProject($project->id, true);

                // RSD: for perms
                $projectId = $project->id;
                $milestones = [];
                foreach ($xmilestones as $key => $value) {
                    if (
                        $this->overrideAccessObject(true, UPSTREAM_ITEM_TYPE_MILESTONE, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                        $this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_MILESTONE, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'start_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                        $this->overrideAccessField(true, UPSTREAM_ITEM_TYPE_MILESTONE, $value['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'end_date', UPSTREAM_PERMISSIONS_ACTION_VIEW)
                    ) {
                        $milestones[$key] = $xmilestones[$key];
                    }
                }


                $milestones = UpStream_Gantt_Utils::quickSortByStartDate($milestones);

                if ( ! empty($milestones)) {
                    foreach ($milestones as $milestone) {
                        if (
                            ! isset($milestone['start_date'])
                            || empty($milestone['start_date'])
                            || ! isset($milestone['end_date'])
                            || empty($milestone['end_date'])
                        ) {
                            continue;
                        }

                        $milestone = \UpStream\Factory::getMilestone($milestone['id']);

                        /**
                         * Filter the milestone for gantt data. If returns true, it is kept in the list.
                         * If false, it is ignored and not included in the data.
                         *
                         * @param bool               $include
                         * @param UpStream\Milestone $milestone
                         */
                        if ( ! apply_filters('upstream_project_timeline_gantt_filter_milestone', true, $milestone)) {
                            continue;
                        }

                        $startDateTimestamp = $milestone->getStartDate('unix');
                        $endDateTimestamp   = $milestone->getEndDate('unix');

                        // Filter by Milestone Status
                        if ($filterMilestoneStatus === static::FILTER_MILESTONE_STATUS_IN_PROGRESS && ! $milestone->isInProgress()) {
                            continue;
                        }

                        if ($filterMilestoneStatus === static::FILTER_MILESTONE_STATUS_COMPLETED && ! $milestone->isCompleted()) {
                            continue;
                        }

                        if ($filterMilestoneStatus === static::FILTER_MILESTONE_STATUS_UPCOMING && ! $milestone->isUpcoming()) {
                            continue;
                        }

                        // Filter by Start and End Dates
                        if ( ! empty($filterStartDate) && $startDateTimestamp < $filterStartDate) {
                            continue;
                        }
                        if ( ! empty($filterEndDate) && $endDateTimestamp > $filterEndDate) {
                            continue;
                        }

                        if ($startDateTimestamp < $firstStartDate) {
                            $firstStartDate = $startDateTimestamp;
                        }

                        if ($endDateTimestamp > $lastEndDate) {
                            $lastEndDate = $endDateTimestamp;
                        }

                        /*
                         * Create the array for the task
                         */
                        $milestoneData           = [];
                        $milestoneData['id']     = $milestone->getId();
                        $milestoneData['type']   = 'subitem';
                        $milestoneData['source'] = 'milestone';
                        $milestoneData['label']  = esc_html(sprintf('%s (%s)', $milestone->getName(), $project->title));

                        /**
                         * Documented above.
                         */
                        $itemLabel = apply_filters('upstream_project_timeline_bar_milestone_label',
                            $milestone->getName(), $milestone);

                        $milestoneData['values'][] = [
                            'from'     => $startDateTimestamp * 1000,
                            'to'       => $endDateTimestamp * 1000,
                            'label'    => $itemLabel,
                            'color'    => $milestone->getColor(),
                            'progress' => $milestone->getProgress(),
                            'desc'     => '',
                            'dataObj'  => [
                                'id'   => $milestone->getId(),
                                'type' => 'milestone',
                            ],
                        ];

                        if ( ! empty($filterUsers) && ! in_array($filterUsers, $milestone->getAssignedTo())) {
                            continue;
                        }

                        if (empty($milestone->getAssignedTo())) {
                            $userId = 0;

                            if ( ! isset($userMilestones[$userId])) {
                                $userMilestones[$userId] = [];
                            }

                            $userMilestones[$userId][] = $milestoneData;
                        } else {
                            // We duplicate the milestone data in case of multiple users.
                            foreach ($milestone->getAssignedTo() as $userId) {
                                if ( ! isset($userMilestones[$userId])) {
                                    $userMilestones[$userId] = [];
                                }

                                $userMilestones[$userId][] = $milestoneData;
                            }
                        }
                    }
                }
            }

            if ( ! empty($userMilestones)) {
                foreach ($userMilestones as $userId => $milestones) {
                    $userData = [];

                    if (empty((int)$userId)) {
                        $user               = new stdClass();
                        $user->display_name = __('(no assigned user)', 'upstream-project-timeline');
                    } else {
                        $user = get_user_by('id', (int)$userId);

                        if (is_wp_error($user)) {
                            continue;
                        }
                    }

                    /*
                     * Create the array for the project
                     */
                    $userData['id']       = $userId;
                    $userData['type']     = 'item';
                    $userData['source']   = 'user';
                    $userData['label']    = "<i class='fa fa-user'></i> " . esc_html($user->display_name);
                    $userData['values'][] = [
                        'from'     => $firstStartDate * 1000,
                        'to'       => $lastEndDate * 1000,
                        'label'    => esc_html($user->display_name),
                        'color'    => '#535192',
                        'progress' => 0,
                        'desc'     => '',
                        'dataObj'  => [
                            'id'   => $userId,
                            'type' => 'hidden',
                        ],
                    ];

                    $data[] = $userData;
                    $data   = array_merge($data, $milestones);
                }
            }
        }

        return $data;
    }

    private function timestamp_from_date($value, $date_format)
    {

        // if blank, return empty string
        if ( ! $value || empty($value)) {
            return '';
        }

        $timestamp = null;

        // if already a timestamp, return the timestamp
        if (is_numeric($value) && (int)$value == $value) {
            $timestamp = $value;
        }

        if ( ! $timestamp) {
            if (empty($value)) {
                return 0;
            }

            $date        = DateTime::createFromFormat($date_format, trim($value));

            if ($date) {
                $timestamp = $date->getTimestamp();
            } else {
                $date_object = date_create_from_format($date_format, $value);
                $timestamp   = $date_object ? $date_object->setTime(0, 0, 0)->getTimeStamp() : strtotime($value);
            }
        }

        // returns the timestamp and sets it to the start of the day
        return strtotime('today', $timestamp);
    }


    /**
     * @return mixed
     */
    protected function fetchUsers()
    {
        return upstream_admin_get_all_project_users();
    }

    /**
     *
     */
    public function updateTimelineItem()
    {
        $this->verifyNonce();

        if ( ! isset($_POST['projectId'])) {
            $this->output('projectId_not_found');

            return;
        }

        if ( ! isset($_POST['itemId'])) {
            $this->output('itemId_not_found');

            return;
        }

        if ( ! isset($_POST['itemType'])) {
            $this->output('itemType_not_found');

            return;
        }

        if ( ! isset($_POST['startDate'])) {
            $this->output('startDate_not_found');

            return;
        }

        if ( ! isset($_POST['endDate'])) {
            $this->output('endDate_not_found');

            return;
        }

        // Sanitize data.
        $projectId = (int)$_POST['projectId'];
        $itemId    = sanitize_text_field($_POST['itemId']);
        $itemType  = sanitize_text_field($_POST['itemType']);
        $startDate = preg_replace('/[^0-9\-]/', '', sanitize_text_field($_POST['startDate']));
        $endDate   = preg_replace('/[^0-9\-]/', '', sanitize_text_field($_POST['endDate']));

        $startDate = $this->timestamp_from_date($startDate, 'Y-m-d');
        $endDate   = $this->timestamp_from_date($endDate, 'Y-m-d');

        if (empty($projectId) || empty($itemId) || empty($itemType) || empty($startDate) || empty($endDate)) {
            $this->output('error');

            return;
        }

        switch ($itemType) {
            case 'milestone':
                $milestone = Factory::getMilestone($itemId);

                $milestone->setStartDate($startDate)
                          ->setEndDate($endDate);
                break;

            case 'task':
                // Get all project's tasks.
                $tasks = get_post_meta($projectId, '_upstream_project_tasks', true);

                if ( ! empty($tasks)) {
                    foreach ($tasks as &$task) {
                        if ($task['id'] === $itemId) {
                            $task['start_date'] = $startDate;
                            $task['end_date']   = $endDate;
                        }
                    }

                    update_post_meta($projectId, '_upstream_project_tasks', $tasks);
                }

                break;
            default:
                $this->output('error');

                return;
                break;
        }

        $this->output('success');
    }

    protected function verifyNonce()
    {
        if ( ! isset($_POST['nonce'])) {
            $this->output('security_error');

            return;
        }

        if ( ! wp_verify_nonce($_POST['nonce'], 'upstream-nonce')) {
            $this->output('security_error');

            return;
        }
    }

    /**
     * @param $return
     */
    protected function output($return)
    {
        echo wp_json_encode($return);
        wp_die();
    }
}
