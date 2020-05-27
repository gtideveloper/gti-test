<?php

namespace UpStream\Plugins\CalendarView;

// Prevent direct access.
use UpStream\Factory;
use UpStream\Milestones;

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for generating and rendering UpStream Calendars.
 *
 * @package     UpStream\Plugins\CalendarView
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class Calendar
{

    final static function __fix_timestamp($type, $ts) {
        // RSD: added this to fix timestamp bugs from old versions
        // TODO: will need to be removed at some point

        if ("task" == $type || "bug" === $type) {
            $offset = get_option( 'gmt_offset' );
            return $ts + ( $offset > 0 ? $offset * 60 * 60 : 0 );
        }

        return $ts;
    }

    /**
     * Amount of days in a week.
     *
     * @since   1.0.0
     *
     * @const   DAYS_IN_A_WEEK_COUNT
     */
    const DAYS_IN_A_WEEK_COUNT = 7;

    /**
     * Months names cache.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     array $monthsNames
     */
    private static $monthsNames = [];

    /**
     * Week days names cache.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     array $weekDaysNames
     */
    private static $weekDaysNames = [];

    /**
     * Cache of all project members.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     array $cachedProjectMembers
     */
    private static $cachedProjectMembers = [];
    /**
     * Indicates whether FrontEnd-Edit extension is installed.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     bool $isFrontendEditPresent
     */
    private static $isFrontendEditPresent = false;
    /**
     * Indicates whether current user has edit milestones capabilities.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     bool $userCanEditMilestones
     */
    private static $userCanEditMilestones = false;
    /**
     * Indicates whether current user has edit tasks capabilities.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     bool $userCanEditTasks
     */
    private static $userCanEditTasks = false;
    /**
     * Indicates whether current user has edit bugs capabilities.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     bool $userCanEditBugs
     */
    private static $userCanEditBugs = false;

    /**
     * Stores an associative array mapping all Bugs and their statuses colors.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     array $bugsColors
     */
    private static $bugsColors = [];
    /**
     * Indicates if current user has admin capabilities.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     bool $userIsAdmin
     */
    private static $userIsAdmin = false;
    /**
     * Milestones cache from all projects within this calendar.
     *
     * @since   1.0.8
     * @access  private
     * @static
     *
     * @var     array $projectsMilestones
     */
    private static $projectsMilestones = [];
    /**
     * Tasks statuses cache.
     *
     * @since   1.0.8
     * @access  private
     * @static
     *
     * @var     array $tasksStatuses
     */
    private static $tasksStatuses = [];
    /**
     * Milestones statuses cache.
     *
     * @since   1.0.8
     * @access  private
     * @static
     *
     * @var     array $milestonesCache
     */
    private static $milestonesCache = [];
    /**
     * Amount of weeks to be displayed on the calendar.
     *
     * @since   1.0.0
     *
     * @var     int $weeksCount
     */
    public $weeksCount = 2;
    /**
     * Indicates whether the project name should be displayed on item's popups.
     *
     * @since   1.0.0
     *
     * @var     bool $showProject
     */
    public $showProject = false;
    /**
     * Indicates whether the project will display time frames.
     *
     * @since   1.0.0
     *
     * @var     bool $projectTimeframesOnly
     */
    public $projectTimeframesOnly = false;
    /**
     * Numeric representation of the current month, without leading zeros (1-12).
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     int $currentMonth
     */
    private $currentMonth = null;
    /**
     * The project id to use when filtering data.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     int $project_id
     */
    private $project_id = 0;
    /**
     * The date used as reference to calculate the very first week of the calendar.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     string $referenceDate
     */
    private $referenceDate = null;
    /**
     * Index (zero-based) of the first day of the week.
     * 0-6, Mon-Sun by default.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     int $firstDayOfTheWeek
     */
    private $firstDayOfTheWeek = 0;
    /**
     * Stores the current day item index.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     int $lastItemDayIndex
     */
    private $lastItemDayIndex = 0;
    /**
     * Stores all projects index displayed on a week. Used only on Calendar Overview.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     array $dayProjectsIndexes
     */
    private $dayProjectsIndexes = [];

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct($project_id = 0)
    {
        $options = Plugin::getOptions();

        $this->weeksCount        = (int)$options->weeks_count;
        $this->firstDayOfTheWeek = (int)$options->first_day;

        $currentDate = new \DateTime('now');
        $currentDate = $currentDate->format('Y-m-d');

        $this->referenceDate = self::getFirstDateOfTheWeek($currentDate, 'Y-m-d', 1, $this->firstDayOfTheWeek);
        $this->project_id    = (int)$project_id > 0 ? $project_id : self::getCurrentProjectId();
        self::cacheProjectMembers($this->project_id);
        $this->showProject  = false;
        $this->currentMonth = (int)date('n');

        if ( ! function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        self::$isFrontendEditPresent = is_plugin_active('UpStream-Frontend-Edit/upstream-frontend-edit.php');

        $projectOwnerId    = (int)upstream_project_owner_id($this->project_id);
        $user              = wp_get_current_user();
        self::$userIsAdmin = count(array_intersect($user->roles,
                ['administrator', 'upstream_manager'])) > 0 || $projectOwnerId === $user->ID;
        if (self::$userIsAdmin) {
            self::$userCanEditMilestones = true;
            self::$userCanEditTasks      = true;
            self::$userCanEditBugs       = true;
        } else {
            self::$userCanEditMilestones = current_user_can('publish_project_milestones');
            self::$userCanEditTasks      = current_user_can('publish_project_tasks');
            self::$userCanEditBugs       = current_user_can('publish_project_bugs');
        }

        self::$bugsColors = upstream_project_bug_statuses_colors();

        self::$tasksStatuses = getTasksStatuses();

        self::$milestonesCache = getMilestones();
    }

    /**
     * Calculates the first date of a week based on a reference date.
     *
     * @param string $date              Date used as reference.
     * @param string $dateFormat        Date format used on output.
     * @param int    $week              Current week index on calendar.
     * @param int    $firstWeekDayIndex Index of the first day of the week.
     *
     * @return  string
     * @since   1.0.0
     * @static
     *
     */
    public static function getFirstDateOfTheWeek($date, $dateFormat = 'Y-m-d', $week = 1, $firstWeekDayIndex = 0)
    {
        $firstWeekDayIndex += 1;

        $date                    = strtotime($date);
        $day_of_week             = date('w', $date);
        $date                    += (($firstWeekDayIndex - $day_of_week - 7) % 7) * 60 * 60 * 24 * $week;
        $additional              = 3600 * 24 * 7 * ($week - 1);
        $formatted_start_of_week = date($dateFormat, $date + $additional);

        return $formatted_start_of_week;
    }

    public static function getCurrentProjectId()
    {
        if (self::isCalendarOverviewPage()) {
            return 0;
        }

        return (int)upstream_post_id();
    }

    public static function isCalendarOverviewPage()
    {
        return ! is_admin() && isset($_GET['view']) && 'calendar' === $_GET['view'];
    }

    /**
     * Cache all project members from a given project.
     *
     * @param int $project_id Project ID.
     *
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function cacheProjectMembers($project_id)
    {
        if ($project_id <= 0 || isset(self::$cachedProjectMembers[$project_id])) {
            return;
        }

        $meta = (array)get_post_meta($project_id, '_upstream_project_members');
        $meta = ! empty($meta) ? (array)$meta[0] : [];

        $users = self::fetchUsers();

        $members = [];
        if (count($meta) > 0) {
            foreach ($meta as $user_id) {
                $user_id = (int)$user_id;

                if (isset($users[$user_id])) {
                    array_push($members, $users[$user_id]);
                }
            }
        }

        self::$cachedProjectMembers[$project_id] = $members;
    }

    /**
     * Retrieve all users that have the roles: "administrators", "upstream_manager" and "upstream_user".
     *
     * @return  array
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function fetchUsers()
    {
        $users  = [];
        $rowset = get_users([
            'role__in' => ['administrator', 'upstream_manager', 'upstream_user'],
        ]);

        foreach ($rowset as $user) {
            $users[(int)$user->ID] = (object)[
                'id'   => (int)$user->ID,
                'name' => $user->display_name,
            ];
        }

        return $users;
    }

    /**
     * Render a calendar that show all items assigned to the current user.
     *
     * @since   1.0.0
     * @static
     */
    public static function renderOverviewCalendar()
    {
        if ( ! Plugin::canRunOnCurrentPage()) {
            return;
        }

        // Only display the calendar if on the calendar page.
        if ( ! isset($_GET['view']) || $_GET['view'] !== 'calendar') {
            return;
        }

        $templatesDir = dirname(dirname(__FILE__)) . '/templates/';

        $options = Plugin::getOptions();

        $weeksCount = (int)$options->weeks_count;

        $calendar              = new Calendar();
        $calendar->showProject = true;
        $calendar->project_id  = 0;

        if ( ! isset($options->overview_projects_timeframes) || (isset($options->overview_projects_timeframes) && (bool)$options->overview_projects_timeframes)) {
            $calendar->projectTimeframesOnly = true;
            $projectTimeframesOnly           = true;
        } else {
            $projectTimeframesOnly = false;
        }

        $currentUserId  = (int)get_current_user_id();
        $projectMembers = self::fetchUsers();

        $collapseBox = \UpStream\Frontend\getSectionCollapseState('calendar') === 'closed';
        ?>
        <div class="row" id="project-section-calendar">
            <div id="calendar" class="col-md-12">
                <div class="x_panel" data-section="calendar">
                    <div class="x_title">
                        <h2>
                            <i class="fa fa-bars sortable_handler"></i>
                            <i class="fa fa-calendar"></i> <?php esc_html_e('Calendar Overview', 'upstream-calendar-view'); ?>
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
                        <div id="calendar" class="c-calendar">
                            <?php include $templatesDir . 'calendar-header.php'; ?>
                            <div class="c-calendar__body">
                                <?php self::renderCalendar($calendar); ?>
                                <div class="c-curtain">
                                    <div class="c-curtain__overlay"></div>
                                    <div class="c-curtain__msg">
                                        <i class="fa fa-spinner fa-spin"></i> <?php esc_html_e('Loading...',
                                            'upstream-calendar-view'); ?>
                                    </div>
                                </div>
                            </div>
                            <?php include $templatesDir . 'calendar-footer.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render a calendar.
     *
     * @param Calendar $calendar An optional Calendar instance.
     * @param mixed    $usersIds Can be either a numeric value or array of users ids.
     *
     * @since   1.0.0
     * @static
     *
     */
    public static function renderCalendar($calendar = null, $usersIds = [])
    {
        $user           = wp_get_current_user();

        /*
        $userCanProceed = count(array_intersect($user->roles,
                ['administrator', 'upstream_manager', 'upstream_user', 'upstream_client_user'])) > 0;
        if ( ! $userCanProceed) {
            if ( ! user_can($user, 'edit_published_projects') && ! user_can($user, 'edit_others_projects')) {
                return;
            }
        }
        */

        if ( ! ($calendar instanceof Calendar)) {
            $calendar = new Calendar();
        }

        if ( ! empty($usersIds)) {
            if (is_numeric($usersIds)) {
                $usersIds = [$usersIds];
            }
            if ( ! is_array($usersIds)) {
                $usersIds = [];
            }
        }

        $currentDate = new \DateTime('now');
        $currentDate = $currentDate->format('Y-m-d');

        $dateFormat = get_option('date_format');

        $daysOfTheWeek = self::generateWeekDaysNames($calendar->firstDayOfTheWeek);
        $users         = self::fetchUsers();
        ?>

        <table class="o-calendar" data-date="<?php echo $calendar->referenceDate; ?>"
               data-weeks="<?php echo $calendar->weeksCount; ?>"
               data-first_day="<?php echo $calendar->firstDayOfTheWeek; ?>"
               data-is-single-project="<?php echo empty($calendar->project_id) ? '0' : '1'; ?>">
            <thead>
            <tr>
                <?php foreach ($daysOfTheWeek as $dayOfTheWeek): ?>
                    <th class="o-calendar-week-header"><?php echo $dayOfTheWeek; ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php
            for ($weekIndex = 1; $weekIndex <= $calendar->weeksCount; $weekIndex++):
                $weekSingleDate = self::getFirstDateOfTheWeek($calendar->referenceDate, 'Y-m-d', $weekIndex,
                    $calendar->firstDayOfTheWeek);
                $weekDates = [];
                $splitMonth = false;

                for ($weekDayIndex = 0; $weekDayIndex < 7; $weekDayIndex++) {
                    $weekDates[$weekDayIndex] = $weekSingleDate;
                    $weekSingleDateMonth      = (int)date('n', strtotime($weekSingleDate));

                    if ($weekSingleDateMonth !== $calendar->currentMonth) {
                        $splitMonth             = $weekSingleDateMonth;
                        $calendar->currentMonth = $weekSingleDateMonth;
                    }

                    $weekSingleDate = date('Y-m-d', strtotime('+1 day', strtotime($weekSingleDate)));
                }
                ?>

                <?php if ( ! empty($splitMonth)): ?>
                <tr class="o-calendar-month-separator">
                    <?php foreach ($weekDates as $weekDate):
                        $weekDateTimestamp = strtotime($weekDate);
                        $weekDateMonth = (int)date('n', $weekDateTimestamp);

                        if ($weekDateMonth !== $splitMonth && (int)date('n',
                                strtotime('+1 day', $weekDateTimestamp)) === $splitMonth):
                            $previousMonth = $weekDateMonth;
                            ?>
                            <td class="month-marker-previous"><?php echo self::getMonthName($previousMonth - 1); ?></td>
                        <?php elseif ($weekDateMonth === $splitMonth && (int)date('n',
                                strtotime('-1 day', $weekDateTimestamp)) !== $splitMonth): ?>
                            <td class="month-marker-current"><?php echo self::getMonthName($splitMonth - 1); ?></td>
                        <?php else: ?>
                            <td class="month-marker-empty"></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>

                <?php
                $weekDates = $calendar->fetchWeekData($weekDates, $usersIds);

                if ($calendar->projectTimeframesOnly) {
                    $m = [];
                    $n = -1;
                    foreach ($weekDates as $weekDate => $weekDateData) {
                        foreach ($weekDateData['projects'] as $project) {
                            if ( ! isset($m[$weekDate][$project->pos])) {
                                $m[$weekDate][$project->pos] = $project->id;

                                if ($project->pos > $n) {
                                    $n = $project->pos;
                                }
                            }
                        }
                    }

                    foreach ($weekDates as $weekDate => $weekDateData) {
                        for ($i = 0; $i <= $n; $i++) {
                            if ( ! isset($m[$weekDate][$i])) {
                                $m[$weekDate][$i] = -1;
                            }
                        }

                        if (isset($m[$weekDate])) {
                            ksort($m[$weekDate]);
                        }
                    }
                }
                ?>

                <tr class="o-calendar-week">
                    <?php
                    $weekDateIndex = 0;
                    foreach ($weekDates as $weekDate => $weekData): ?>
                        <td class="o-calendar-day<?php echo (int)date('N',
                            strtotime($weekDate)) >= 6 ? ' is-weekend' : ''; ?><?php echo $weekDate === $currentDate ? ' is-today' : ''; ?>"
                            data-date="<?php echo $weekDate; ?>">
                            <div class="o-calendar-day-cell">
                                <div class='o-calendar-new-item-label'>
                                    <?php echo esc_html__('Click to create', 'upstream-calendar-view'); ?>
                                </div>
                                <div class="o-calendar-day__day"><?php echo date('d', strtotime($weekDate)); ?></div>
                                <div class="o-calendar-day__items">
                                    <?php if ($calendar->projectTimeframesOnly): ?>
                                        <?php
                                        if ( ! isset($m[$weekDate])) {
                                            continue;
                                        }

                                        for ($i = 0; $i < count($m[$weekDate]); $i++) {
                                            $project_id = $m[$weekDate][$i];
                                            if ($project_id > 0) {
                                                echo self::renderCalendarDayItemHtml($weekData['projects'][$project_id],
                                                    'project', $users, $dateFormat, $weekDate, $weekDateIndex, [],
                                                    $calendar->project_id);
                                            } else {
                                                echo '<div class="o-calendar-day__item s-pill s-filler">&nbsp;</div>';
                                            }
                                        }
                                        ?>
                                    <?php else: ?>
                                        <?php foreach ($weekData as $itemType => $items): ?>
                                            <?php if (count($items) > 0): ?>
                                                <?php foreach ($items as $item): ?>
                                                    <?php if ( ! isset($calendar->showProject) || ! (bool)$calendar->showProject):
                                                        unset($item->project);
                                                    endif; ?>
                                                    <?php echo self::renderCalendarDayItemHtml($item,
                                                        rtrim($itemType, 's'), $users, $dateFormat, $weekDate,
                                                        $weekDateIndex, [], $calendar->project_id); ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <?php
                        $weekDateIndex++;
                    endforeach; ?>
                </tr>
            <?php endfor; ?>
            </tbody>
            <tfoot></tfoot>
        </table>
        <?php
    }

    /**
     * Retrieve an array containing all week days names starting on a given index (0-6, Mon-Sun).
     *
     * @param int $firstDayIndex The index (0-6, Mon-Sun) of what will be the first day of the week.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function generateWeekDaysNames($firstDayIndex = 0)
    {
        $firstDayIndex = (int)$firstDayIndex;

        if ($firstDayIndex < 0 || $firstDayIndex > 6) {
            $firstDayIndex = 0;
        }

        $theWeek = [];

        $weekDayIndex = $firstDayIndex - 1;
        for ($dayIndex = 0; $dayIndex < self::DAYS_IN_A_WEEK_COUNT; $dayIndex++) {
            if ($weekDayIndex === self::DAYS_IN_A_WEEK_COUNT - 1) {
                $weekDayIndex = 0;
            } else {
                $weekDayIndex++;
            }

            $theWeek[] = self::getWeekDayName($weekDayIndex);
        }

        return $theWeek;
    }

    /**
     * Retrieve the name of a given day of the week (zero-indexed).
     *
     * @param int $dayIndex The day index.
     *
     * @return  string
     * @since   1.0.0
     * @static
     *
     */
    public static function getWeekDayName($dayIndex)
    {
        if (empty(self::$weekDaysNames)) {
            self::$weekDaysNames = self::getWeekDaysNames();
        }

        if ( ! is_numeric($dayIndex) || ! isset(self::$weekDaysNames[$dayIndex])) {
            return (string)$dayIndex;
        }

        return self::$weekDaysNames[$dayIndex];
    }

    /**
     * Retrieve an array containing all week days names.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function getWeekDaysNames()
    {
        if (empty(self::$weekDaysNames)) {
            self::$weekDaysNames = [
                __('Monday'),
                __('Tuesday'),
                __('Wednesday'),
                __('Thursday'),
                __('Friday'),
                __('Saturday'),
                __('Sunday'),
            ];
        }

        return self::$weekDaysNames;
    }

    /**
     * Retrieve a given month (zero-indexed) name.
     *
     * @param int $monthIndex Month index.
     *
     * @return  string
     * @since   1.0.0
     * @static
     *
     */
    private static function getMonthName($monthIndex)
    {
        if (empty(self::$monthsNames)) {
            self::$monthsNames = self::getMonthsNames();
        }

        if ( ! is_numeric($monthIndex) || ! isset(self::$monthsNames[$monthIndex])) {
            return (string)$monthIndex;
        }

        return self::$monthsNames[$monthIndex];
    }

    /**
     * Retrieve an array containing all months names.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function getMonthsNames()
    {
        if (empty(self::$monthsNames)) {
            self::$monthsNames = [
                __('January'),
                __('February'),
                __('March'),
                __('April'),
                __('May'),
                __('June'),
                __('July'),
                __('August'),
                __('September'),
                __('October'),
                __('November'),
                __('December'),
            ];
        }

        return self::$monthsNames;
    }

    /**
     * Given an array of dates, this method retrieves all milestones, tasks, bugs starting/ending at those dates.
     *
     * @param array $weekDays An array of dates.
     *
     * @return  array
     * @since   1.0.0
     *
     */
    public function fetchWeekData($weekDays, $usersIds = [])
    {
        $data = [];
        foreach ($weekDays as $weekDate) {
            $data[$weekDate] = [
                'projects'   => [],
                'milestones' => [],
                'tasks'      => [],
                'bugs'       => [],
            ];
        }

        $options = Plugin::getOptions();
        if (
            $this->projectTimeframesOnly &&
            (
                ! isset($options->overview_projects_timeframes) ||
                (isset($options->overview_projects_timeframes) && (bool)$options->overview_projects_timeframes)
            )
        ) {

            if (isUserEitherManagerOrAdmin()) {
                $rowset = get_posts([
                    'post_type'      => "project",
                    'post_status'    => "publish",
                    'posts_per_page' => -1,
                ]);
            } else {
                $rowset = upstream_get_users_projects(get_current_user_id());
            }

            if (count($rowset) > 0) {
                $clientRowset = get_posts([
                    'post_type'      => "client",
                    'post_status'    => "publish",
                    'posts_per_page' => -1,
                ]);

                $clients = [];
                foreach ($clientRowset as $row) {
                    $clients[(int)$row->ID] = $row->post_title;
                }

                $projectsPosMap = [];
                $nextProjectPos = 0;

                $getProjectPosOnDay = function ($project_id) use (&$projectsPosMap, &$nextProjectPos) {
                    if ( ! isset($projectsPosMap[$project_id])) {
                        $projectsPosMap[$project_id] = $nextProjectPos;
                        $nextProjectPos++;
                    }

                    return $projectsPosMap[$project_id];
                };

                $statusesColors = \upstream_project_statuses_colors();
                $projects_ids   = [];
                foreach ($rowset as $row) {
                    $project = (object)[
                        'id'                 => (int)$row->ID,
                        'title'              => $row->post_title,
                        'startDateTimestamp' => 0,
                        'start_date'         => null,
                        'endDateTimestamp'   => 0,
                        'endDate'            => null,
                        'end_date'           => null,
                        'pos'                => null,
                        'status'             => null,
                        'statusColor'        => null,
                        'client'             => "",
                    ];

                    $metas = (array)get_post_meta($row->ID);

                    if (isset($metas['_upstream_project_status']) && ! empty($metas['_upstream_project_status'])) {
                        $project->status = $metas['_upstream_project_status'][0];

                        if (isset($statusesColors[$project->status])) {
                            $project->statusColor = $statusesColors[$project->status];
                        }
                    }

                    if (isset($metas['_upstream_project_client']) && ! empty($metas['_upstream_project_client'])) {
                        if (is_array($metas['_upstream_project_client'])) {
                            $client_id = (int)$metas['_upstream_project_client'][0];
                        } else {
                            $client_id = (int)$metas['_upstream_project_client'];
                        }

                        if (isset($clients[$client_id])) {
                            $project->client = $clients[$client_id];
                        }
                    }

                    if (isset($metas['_upstream_project_start']) && ! empty($metas['_upstream_project_start'])) {
                        $project->startDateTimestamp = (int)$metas['_upstream_project_start'][0];

                        $project->startDate  = new \DateTime("@{$project->startDateTimestamp}");
                        $project->startDate  = $project->startDate->format('Y-m-d');
                        $project->start_date = $project->startDateTimestamp;
                    }

                    if (isset($metas['_upstream_project_end']) && ! empty($metas['_upstream_project_end'])) {
                        $project->endDateTimestamp = (int)$metas['_upstream_project_end'][0];

                        $project->endDate  = new \DateTime("@{$project->endDateTimestamp}");
                        $project->endDate  = $project->endDate->format('Y-m-d');
                        $project->end_date = $project->endDateTimestamp;
                    }

                    $project->startDateTimestamp = upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $row->ID, null, 0, 'start_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) ? $project->startDateTimestamp : 0;
                    $project->endDateTimestamp = upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $row->ID, null, 0, 'end_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) ? $project->endDateTimestamp : 0;

                    if ($project->startDateTimestamp > 0 || $project->endDateTimestamp > 0) {
                        foreach ($weekDays as $weekDate) {
                            $weekDateTimestamp = strtotime($weekDate);

                            if (
                                $project->startDateTimestamp > 0 &&
                                $project->endDateTimestamp > 0
                            ) {
                                if (
                                    $project->startDateTimestamp <= $weekDateTimestamp &&
                                    $project->endDateTimestamp >= $weekDateTimestamp
                                ) {
                                    $data[$weekDate]['projects'][$project->id] = $project;
                                    $project->pos                              = $getProjectPosOnDay($project->id);
                                }
                            } elseif ($project->endDateTimestamp > 0) {
                                if ($project->endDateTimestamp >= $weekDateTimestamp) {
                                    $data[$weekDate]['projects'][$project->id] = $project;
                                    $project->pos                              = $getProjectPosOnDay($project->id);
                                }
                            } elseif ($project->startDateTimestamp > 0) {
                                if ($project->startDateTimestamp <= $weekDateTimestamp) {
                                    $data[$weekDate]['projects'][$project->id] = $project;
                                    $project->pos                              = $getProjectPosOnDay($project->id);
                                }
                            }
                        }
                    }
                }
            }

            return apply_filters('upstream.calendar-view:fetch_week_data', $data, $usersIds);
        }

        $hideMilestones = (bool)upstream_disable_milestones();
        if ( ! $hideMilestones) {
            $milestones = self::filterMilestones($weekDays, $this->project_id, $usersIds);
            if (count($milestones) > 0) {
                foreach ($milestones as $weekDate => $weekDateData) {
                    $data[$weekDate]['milestones'] = $weekDateData;
                }
                unset($weekDateData, $weekDate);
            }
            unset($milestones);
        }

        $hideTasks = (bool)upstream_disable_tasks();
        if ( ! $hideTasks) {
            $tasks = self::filterTasks($weekDays, $this->project_id, $usersIds);
            if (count($tasks) > 0) {
                foreach ($tasks as $weekDate => $weekDateData) {
                    $data[$weekDate]['tasks'] = $weekDateData;
                }
                unset($weekDateData, $weekDate);
            }
            unset($tasks);
        }

        $hideBugs = (bool)upstream_disable_bugs();
        if ( ! $hideBugs) {
            $bugs = self::filterBugs($weekDays, $this->project_id, $usersIds);
            if (count($bugs) > 0) {
                foreach ($bugs as $weekDate => $weekDateData) {
                    $data[$weekDate]['bugs'] = $weekDateData;
                }
                unset($weekDateData, $weekDate);
            }
            unset($bugs);
        }

        return apply_filters('upstream.calendar-view:fetch_week_data', $data, $usersIds);
    }

    /**
     * Filter milestones based on an array of dates, array of projects ids and/or users ids.
     *
     * @param array $week        Array of dates.
     * @param mixed $projectsIds Can be either a numeric value or array of projects ids.
     * @param mixed $usersIds    Can be either a numeric value or array of users ids.
     *
     * @return  array
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function filterMilestones($week, $projectsIds = [], $usersIds = [])
    {
        if (count($week) === 0) {
            return [];
        }

        if ( ! empty($projectsIds)) {
            if (is_numeric($projectsIds)) {
                $projectsIds = [$projectsIds];
            } elseif ( ! is_array($projectsIds)) {
                $projectsIds = [];
            }

            $projectsIds = array_unique(array_filter($projectsIds));
        }  elseif ($projectsIds === 0) {
            $projectsIds = [];
        }

        if ( ! is_numeric($usersIds) && ! is_array($usersIds)) {
            $usersIds = [];
        } elseif (is_numeric($usersIds)) {
            $usersIds = [$usersIds];
        }

        $data = [];

        $projects = self::filterProjectsItemTypeEnabled('milestones', $projectsIds);

        if (count($projects) > 0) {
            foreach ($projects as $project) {
                $milestones = Milestones::getInstance()->getMilestonesFromProject($project->id);

                if (count($milestones) > 0) {
                    foreach ($milestones as $milestone) {
                        $milestone = Factory::getMilestone($milestone);

                        $startDate = $milestone->getStartDate('mysql');
                        $endDate   = $milestone->getEndDate('mysql');

                        $startDate = upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_MILESTONE, $milestone->getId(), UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'start_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) ? $startDate : null;
                        $endDate = upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_MILESTONE, $milestone->getId(), UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'end_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) ? $endDate : null;

                        if ( ! empty($startDate) || ! empty($endDate)) {
                            $row = (object)[
                                'id'          => $milestone->getId(),
                                'title'       => $milestone->getName(),
                                'assigned_to' => $milestone->getAssignedTo(),
                                'start_date'  => $milestone->getStartDate('unix'),
                                'end_date'    => $milestone->getEndDate('unix'),
                                'type'        => "milestone",
                                'created_by'  => $milestone->getCreatedBy(),
                                'project'     => $project,
                                'color'       => $milestone->getColor(),
                            ];

                            self::$projectsMilestones[$project->id][$row->id] = $row;

                            foreach ($week as $weekDate) {
                                if ($startDate === $weekDate || $endDate === $weekDate) {
                                    if ( ! isset($data[$weekDate])) {
                                        $data[$weekDate] = [];
                                    }

                                    // Add a flag to know if it is the start, or end date.
                                    if ($startDate === $weekDate) {
                                        $row->start_or_end = 'start';
                                    } else {
                                        $row->start_or_end = 'end';
                                    }

                                    $data[$weekDate][] = $row;
                                }
                            }
                        }
                    }
                }
            }
        }

        return apply_filters('upstream.calendar-view:filter_milestones', $data);
    }

    /**
     * Retrieve a list of projects which current user can access and have $itemType feature enabled.
     *
     * @param string $itemType    Item type to be checked.
     * @param array  $projectsIds Set of project ids to be checked.
     *
     * @return  array
     * @since   1.0.7
     * @access  private
     * @static
     *
     */
    private static function filterProjectsItemTypeEnabled($itemType, $projectsIds = [])
    {
        if ( ! in_array($itemType, ['milestones', 'tasks', 'bugs'])) {
            return;
        }

        if (!is_array($projectsIds)) {
            if ($projectsIds != null && is_numeric($projectsIds)) $projectsIds = [$projectsIds];
            else $projectsIds = [];
        }

        $currentUserId = get_current_user_id();
        $transientKey  = 'upstream.calendar-view-user:' . (empty($currentUserId) ? 0 : $currentUserId) . '-pids:' . (is_array($projectsIds) && count($projectsIds) > 0 ? implode('-', $projectsIds): '');

        $rowset = array_filter((array)get_transient($transientKey));
        if (empty($rowset)) {
            $projects    = upstream_get_users_projects($currentUserId);
            $checkFnName = 'upstream_are_' . $itemType . '_disabled';
            foreach ($projects as $projectIndex => $project) {
                $rowset[(int)$project->ID] = (object)[
                    'id'            => (int)$project->ID,
                    'title'         => $project->post_title,
                    'alias'         => $project->post_name,
                    'hasMilestones' => ! call_user_func('upstream_are_milestones_disabled', $project->ID),
                    'hasTasks'      => ! call_user_func('upstream_are_tasks_disabled', $project->ID),
                    'hasBugs'       => ! call_user_func('upstream_are_bugs_disabled', $project->ID),
                ];
            }

            set_transient($transientKey, $rowset, 60);
        }

        if (count($rowset) > 0) {
            if ( ! empty($projectsIds)) {
                foreach ($rowset as $projectId => $project) {
                    if ( ! in_array($projectId, $projectsIds)) {
                        unset($rowset[$projectId]);
                    }
                }
            }

            $checkKey = 'has' . ucfirst($itemType);
            foreach ($rowset as $projectId => $project) {
                if ( ! $project->{$checkKey}) {
                    unset($rowset[$projectId]);
                }
            }
        }

        return $rowset;
    }

    /**
     * Filter tasks based on an array of dates, array of projects ids and/or users ids.
     *
     * @param array $week        Array of dates.
     * @param mixed $projectsIds Can be either a numeric value or array of projects ids.
     * @param mixed $usersIds    Can be either a numeric value or array of users ids.
     *
     * @return  array
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function filterTasks($week, $projectsIds = [], $usersIds = [])
    {
        if (count($week) === 0) {
            return [];
        }

        if ( ! empty($projectsIds)) {
            if (is_numeric($projectsIds)) {
                $projectsIds = [$projectsIds];
            } elseif ( ! is_array($projectsIds)) {
                $projectsIds = [];
            }

            $projectsIds = array_unique(array_filter($projectsIds));
        } elseif ($projectsIds === 0) {
            $projectsIds = [];
        }

        if ( ! is_numeric($usersIds) && ! is_array($usersIds)) {
            $usersIds = [];
        } elseif (is_numeric($usersIds)) {
            $usersIds = [$usersIds];
        }

        $data = [];

        $projects = self::filterProjectsItemTypeEnabled('tasks', $projectsIds);

        if (count($projects) > 0) {
            if (count(self::$tasksStatuses) === 0) {
                self::$tasksStatuses = getTasksStatuses();
            }

            foreach ($projects as $project) {
                $meta = (array)get_post_meta($project->id, '_upstream_project_tasks', true);

                if ( ! isset(self::$projectsMilestones[$project->id])) {
                    $milestones = \UpStream\Milestones::getInstance()->getMilestonesFromProject($project->id, true);

                    foreach ($milestones as $milestone) {
                        if ( ! isset($milestone['id']) || empty($milestone['id'])) {
                            continue;
                        }

                        self::$projectsMilestones[$project->id][$milestone['id']] = $milestone['milestone'];
                    }
                    unset($milestone, $milestones);
                }

                if (count($meta) > 0) {
                    $usersIdsCount = count($usersIds);

                    foreach ($meta as $row) {
                        $startDateTimestamp = isset($row['start_date']) ? (int)$row['start_date'] : 0;
                        $startDateTimestamp = self::__fix_timestamp('task', $startDateTimestamp);

                        $startDateTimestamp = upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_TASK, $row['id'], UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'start_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) ? $startDateTimestamp : 0;

                        if ($startDateTimestamp > 0) {
                            $startDate = new \DateTime("@{$startDateTimestamp}");
                            $startDate = $startDate->format('Y-m-d');
                        } else {
                            $startDate = null;
                        }

                        $endDateTimestamp = isset($row['end_date']) ? (int)$row['end_date'] : 0;
                        $endDateTimestamp = self::__fix_timestamp('task', $endDateTimestamp);

                        $endDateTimestamp = upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_TASK, $row['id'], UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'end_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) ? $endDateTimestamp : 0;

                        if ($endDateTimestamp > 0) {
                            $endDate = new \DateTime("@{$endDateTimestamp}");
                            $endDate = $endDate->format('Y-m-d');
                        } else {
                            $endDate = null;
                        }


                        if ($startDateTimestamp > 0 || $endDateTimestamp > 0) {
                            $assignees = [];
                            if (isset($row['assigned_to'])) {
                                if ( ! is_array($row['assigned_to'])) {
                                    $assignees = [$row['assigned_to']];
                                } else {
                                    $assignees = $row['assigned_to'];
                                }

                                $assignees = array_filter(array_map('intval', $assignees));
                            }

                            if ($usersIdsCount > 0) {
                                if (count(array_intersect($usersIds, $assignees)) === 0) {
                                    continue;
                                }
                            }

                            if (!isset($row['title'])) {
                                $row['title'] = "(Untitled)";
                            }

                            $task = (object)[
                                'id'           => $row['id'],
                                'title'        => $row['title'],
                                'assigned_to'  => $assignees,
                                'status'       => '',
                                'progress'     => isset($row['progress']) ? (int)$row['progress'] : 0,
                                'milestone'    => '',
                                'start_date'   => $startDateTimestamp,
                                'end_date'     => $endDateTimestamp,
                                'created_by'   => isset($row['created_by']) && (int)$row['created_by'] > 0 ? (int)$row['created_by'] : 0,
                                'type'         => "task",
                                'project'      => $project,
                                'start_or_end' => 'start',
                            ];

                            if (isset($row['status'])) {
                                $task->status = isset(self::$tasksStatuses[$row['status']])
                                    ? self::$tasksStatuses[$row['status']]
                                    : $row['status'];
                            }

                            if (isset($row['milestone'])) {
                                $task->milestone = $row['milestone'];
                            }

                            foreach ($week as $weekDate) {
                                if ($startDate === $weekDate || $endDate === $weekDate) {
                                    if ( ! isset($data[$weekDate])) {
                                        $data[$weekDate] = [];
                                    }

                                    // Add a flag to know if it is the start, or end date.
                                    if ($startDate === $weekDate) {
                                        $task->start_or_end = 'start';
                                    } else {
                                        $task->start_or_end = 'end';
                                    }

                                    $data[$weekDate][] = $task;
                                }
                            }
                        }
                    }
                }
            }
        }

        return apply_filters('upstream.calendar-view:filter_tasks', $data);
    }

    /**
     * Filter bugs based on an array of dates, array of projects ids and/or users ids.
     *
     * @param array $week        Array of dates.
     * @param mixed $projectsIds Can be either a numeric value or array of projects ids.
     * @param mixed $usersIds    Can be either a numeric value or array of users ids.
     *
     * @return  array
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function filterBugs($week, $projectsIds = [], $usersIds = [])
    {
        if (count($week) === 0) {
            return [];
        }

        if ( ! empty($projectsIds)) {
            if (is_numeric($projectsIds)) {
                $projectsIds = [$projectsIds];
            } elseif ( ! is_array($projectsIds)) {
                $projectsIds = [];
            }

            $projectsIds = array_unique(array_filter($projectsIds));
        } elseif ($projectsIds === 0) {
            $projectsIds = [];
        }


        if ( ! is_numeric($usersIds) && ! is_array($usersIds)) {
            $usersIds = [];
        } elseif (is_numeric($usersIds)) {
            $usersIds = [$usersIds];
        }

        $data = [];

        $projects = self::filterProjectsItemTypeEnabled('bugs', $projectsIds);

        if (count($projects) > 0) {
            foreach ($projects as $project) {
                $meta = (array)get_post_meta($project->id, '_upstream_project_bugs');
                $meta = ! empty($meta) ? $meta[0] : [];
                // Avoid NULL values when there is no bugs
                $meta = ! empty($meta) ? $meta : [];

                if (count($meta) > 0) {
                    $usersIdsCount = count($usersIds);

                    foreach ($meta as $row) {
                        $dueDateTimestamp = isset($row['due_date']) ? (int)$row['due_date'] : 0;
                        $dueDateTimestamp = self::__fix_timestamp('bug', $dueDateTimestamp);

                        $dueDateTimestamp = upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_BUG, $row['id'], UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'due_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) ? $dueDateTimestamp : 0;

                        if ($dueDateTimestamp > 0) {
                            $dueDate = new \DateTime("@{$dueDateTimestamp}");
                            $dueDate = $dueDate->format('Y-m-d');
                        } else {
                            $dueDate = null;
                        }

                        if ($dueDateTimestamp > 0) {
                            $assignees = [];
                            if (isset($row['assigned_to'])) {
                                if ( ! is_array($row['assigned_to'])) {
                                    $assignees = [$row['assigned_to']];
                                } else {
                                    $assignees = $row['assigned_to'];
                                }

                                $assignees = array_filter(array_map('intval', $assignees));
                            }

                            if ($usersIdsCount > 0) {
                                if (count(array_intersect($usersIds, $assignees)) === 0) {
                                    continue;
                                }
                            }

                            if ( ! empty($usersIds)) {
                                if ( ! in_array($assignees, $usersIds)) {
                                    continue;
                                }
                            }

                            if (!isset($row['title'])) {
                                $row['title'] = "(Untitled)";
                            }

                            
                            $bug = (object)[
                                'id'          => $row['id'],
                                'title'       => $row['title'],
                                'assigned_to' => $assignees,
                                'status'      => isset($row['status']) ? (string)$row['status'] : "",
                                'severity'    => isset($row['severity']) ? (string)$row['severity'] : "",
                                'due_date'    => $dueDateTimestamp,
                                'created_by'  => isset($row['created_by']) && (int)$row['created_by'] > 0 ? (int)$row['created_by'] : 0,
                                'type'        => "bug",
                                'project'     => $project,
                            ];

                            foreach ($week as $weekDate) {
                                if ($dueDate === $weekDate) {
                                    if ( ! isset($data[$weekDate])) {
                                        $data[$weekDate] = [];
                                    }

                                    $data[$weekDate][] = $bug;
                                }
                            }
                        }
                    }
                }
            }
        }

        return apply_filters('upstream.calendar-view:filter_bugs', $data);
    }

    /**
     * Generate the html that wraps a given calendar item.
     *
     * @param object $item            The item data.
     * @param string $itemType        Whether "milestone", "task" or "bug".
     * @param array  $usersCache      Array of cached users.
     * @param string $dateFormat      Preferred date format for date fields.
     * @param string $weekDate
     * @param int    $weekDateIndex
     * @param array  $milestonesCache @deprecated
     * @param int    $projectId
     *
     * @return  string
     * @throws \UpStream\Exception
     * @since   1.0.0
     * @access  private
     * @static
     *
     */
    private static function renderCalendarDayItemHtml(
        $item,
        $itemType,
        $usersCache,
        $dateFormat,
        $weekDate,
        $weekDateIndex,
        $milestonesCache = [],
        $projectId = 0
    ) {
        if ( ! in_array($itemType, ['milestone', 'task', 'bug', 'project'])) {
            return '';
        }

        $projectTimeframesMode = $itemType === "project";

        if ( ! $projectTimeframesMode) {
            if ( ! is_array($usersCache) || ! count($usersCache)) {
                $usersCache = self::fetchUsers();
            }
        }

        if ( ! is_string($dateFormat) || $dateFormat === '') {
            $dateFormat = null;
        }

        if (empty($projectId)) {

            // RSD: this was giving a warning because project->project->id didn't exist
            if ($itemType != "project")
                $projectId = $item->project->id;
        }

        $wrapperAttrs = [
            'class'           => "o-calendar-day__item",
            'data-type'       => $itemType,
            'data-toggle'     => "popover",
            'role'            => "button",
            'tabindex'        => 0,
            'data-trigger'    => "manual",
            'data-project-id' => $projectId,
            'data-id'         => $item->id,
        ];

        if ( ! $projectTimeframesMode) {
            $itemTypeIcon = ($itemType === "milestone" ? 'flag' : ($itemType === "task" ? 'wrench' : 'bug'));

            if (is_array($item->assigned_to)) {
                $wrapperAttrs['data-assigned_to'] = implode(',', $item->assigned_to);
            } else {
                $wrapperAttrs['data-assigned_to'] = (int)$item->assigned_to;
            }

            $wrapperAttrs['data-assigned_to'] = upstream_override_access_field(true, $itemType, $item->id, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, 'assigned_to', UPSTREAM_PERMISSIONS_ACTION_VIEW) ? $wrapperAttrs['data-assigned_to'] : 0;

        }

        $noneLabel   = "<i class='s-none-indicator'>(" . esc_html__('none', 'upstream') . ')</i>';
        $popoverHtml = "<table class='o-calendar-day-popover-items'>";

        if ($projectTimeframesMode) {
            $calendarItemHtml = "";
            $itemTypeIcon     = 'suitcase';

            $wrapperAttrs['class'] .= ' s-pill';

            if (upstream_override_access_field(true, $itemType, $item->id, null, 0, 'start_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                $item->startDate === $weekDate) {
                $wrapperAttrs['class']         .= ' s-start';
                $wrapperAttrs['data-date-ref'] = 'start';
            }

            if (upstream_override_access_field(true, $itemType, $item->id, null, 0, 'end_date', UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                $item->endDate === $weekDate) {
                $wrapperAttrs['class']         .= ' s-end';
                $wrapperAttrs['data-date-ref'] = 'end';
            }

            if ( ! empty($item->statusColor)) {
                $wrapperAttrs['style'] = 'background-color: ' . $item->statusColor . ';';
            }

            $columnsLabels = [
                'start_date' => esc_html__('Start Date', 'upstream'),
                'end_date'   => esc_html__('End Date', 'upstream'),
                'status'     => esc_html__('Status', 'upstream'),
                'client'     => esc_html(upstream_client_label()),
            ];
        } else {
            $columnsLabels = [
                'assigned_to' => esc_html__('Assigned To', 'upstream'),
                'start_date'  => esc_html__('Start Date', 'upstream'),
                'end_date'    => esc_html__('End Date', 'upstream'),
                'due_date'    => esc_html__('Due Date', 'upstream'),
                'status'      => esc_html__('Status', 'upstream'),
                'progress'    => esc_html__('Progress', 'upstream'),
                'milestone'   => esc_html(upstream_milestone_label()),
                'severity'    => esc_html__('Severity', 'upstream'),
            ];

            if (isset($item->project) && ! empty($item->project)) {
                $popoverHtml .= "<tr class='s-project-separator'>";
                $popoverHtml .= '<td>' . esc_html(upstream_project_label()) . '</td>';
                $popoverHtml .= "<td><a href='" . esc_url(get_permalink($item->project->id)) . "#" . $itemType . "s'>" . esc_html($item->project->title) . '</a></td>';
                $popoverHtml .= '</tr>';
            }
        }

        $wrapperAttrs['data-popover-icon']  = "<i class='fa fa-" . $itemTypeIcon . "'></i> ";

        if ($itemType == 'project') {
            $wrapperAttrs['data-popover-title'] = "<a href='" .get_permalink($item->id)."'>" . esc_attr( $item->title ) . "</a>";
        } else {
            $wrapperAttrs['data-popover-title'] = esc_attr( $item->title );
        }

        foreach ($columnsLabels as $column_id => $columnLabel) {

            $visible = upstream_override_access_field(true, $itemType, $item->id, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, $column_id, UPSTREAM_PERMISSIONS_ACTION_VIEW);

            if (isset($item->{$column_id})) {
                $columnValue = $item->{$column_id};
                if ($column_id === 'assigned_to') {
                    if ( ! is_array($columnValue)) {
                        $columnValue = [$columnValue];
                    }

                    $assignees = array_filter(array_map('intval', $columnValue));
                    if (count($assignees) > 0) {
                        $columnValue = [];
                        foreach ($assignees as $assignee) {
                            if (isset($usersCache[$assignee])) {
                                $columnValue[] = $usersCache[$assignee]->name;
                            }
                        }

                        $columnValue = implode(', ', $columnValue);
                    } else {
                        $columnValue = '';
                    }
                } elseif ($column_id === "progress") {
                    $columnValue .= '%';
                } elseif ($itemType === 'project' && $column_id === 'status') {
                    $s = upstream_get_all_project_statuses();
                    if (isset($s[$columnValue])) {
                        $columnValue = $s[$columnValue]['name'];
                    }
                } elseif ($column_id === "milestone") {
                    if ( ! empty($columnValue)) {
                        $milestone = Factory::getMilestone($columnValue);

                        if ($milestone !== null) {
                            $columnValue = $milestone->getName();
                        }
                    }
                } elseif (strpos($column_id,
                        "_date") !== false && is_numeric($columnValue) && (int)$columnValue > 0) {
                    $columnValue = upstream_format_date($columnValue, $dateFormat);
                }

                if (empty($columnValue)) {
                    $columnValue = $noneLabel;
                }

                if (is_array($columnValue)) {
                    $columnValue = $columnValue['name'];
                }

                if (!$visible) {
                    $columnValue = __('(hidden)', 'upstream');
                }

                $popoverHtml .= '<tr>';
                $popoverHtml .= '<td>' . esc_html($columnLabel) . '</td>';
                $popoverHtml .= '<td>' . esc_attr($columnValue) . '</td>';
                $popoverHtml .= '</tr>';
            }
        }

        $popoverHtml .= '</table>';

        if (
            ( ! isset($item->project) || empty($item->project)) &&
            self::$isFrontendEditPresent &&
            $itemType !== "project"
        ) {
            $user_id = get_current_user_id();

            $userCanEditItem = self::$userIsAdmin;
            if ( ! $userCanEditItem
                 && self::${'userCanEdit' . ucfirst($itemType) . 's'}
                 && ((isset($item->assigned_to) && ((is_array($item->assigned_to) && in_array($user_id,
                                    $item->assigned_to)) || ! is_array($item->assigned_to) && $item->assigned_to === $user_id))
                     || (isset($item->created_by) && $item->created_by === $user_id)
                 )
            ) {
                $userCanEditItem = true;
            }

            $userCanEditItem = upstream_override_access_object($userCanEditItem, $itemType, $item->id, UPSTREAM_ITEM_TYPE_PROJECT, $projectId, UPSTREAM_PERMISSIONS_ACTION_EDIT);

            if ($userCanEditItem) {
                $popoverHtml .= "<hr style='margin: 10px 0;'/>";
                $popoverHtml .= "<div class='text-right'>";
                $popoverHtml .= "<button type='button' class='btn btn-xs btn-default' data-action='calendar.edit' data-id='" . $item->id . "'><i class='fa fa-pencil'></i> " . esc_html__('Edit',
                        'upstream-calendar-view') . "</button>";
                $popoverHtml .= "</div>";
            }
        }

        $wrapperAttrs['data-content']   = $popoverHtml;
        $wrapperAttrs['data-html']      = 'true';
        $wrapperAttrs['data-container'] = 'body';

        $position = null;
        if (isset($item->start_date) && isset($item->end_date) && ! empty($item->title)) {
            if (isset($item->start_date) && date('Y-m-d', $item->start_date) === $weekDate) {
                $position                      = 'start';
                $wrapperAttrs['data-date-ref'] = 'start';
            }

            if (isset($item->end_date) && date('Y-m-d', $item->end_date) === $weekDate) {
                $position                      = 'end';
                $wrapperAttrs['data-date-ref'] = 'end';
            }
        }

        $wrapperAttrsHtmlArray = [];
        foreach ($wrapperAttrs as $attrName => $attrValue) {
            $wrapperAttrsHtmlArray[] = $attrName . '="' . $attrValue . '"';
        }

        $theTitle = '<i class="fa fa-' . $itemTypeIcon . '"></i> ';

        $outText = $item->title;

        $theTitle .= $outText;
        if ($itemType !== "project") {
            $colorColumnRef = $itemType === "milestone" ? "title" : "status";
            if (isset($item->{$colorColumnRef}) && ! empty($item->{$colorColumnRef})) {
                if (is_array($item->{$colorColumnRef})) {
                    $theTitle = '<span style="color: ' . $item->{$colorColumnRef}['color'] . '">';
                    $theTitle .= '<i class="fa fa-' . $itemTypeIcon . '"></i> ' . $outText;
                    if ( !is_null($position) ) {
                        $direction = $position === 'start' ? 'right' : 'left';
                        $theTitle  .= '<i class="o-calendar-interval-icon fa fa-long-arrow-' . $direction . '"></i>';
                    }
                    $theTitle .= '</span>';
                } else {
                    $theTitle = '<span style="color: ' . (isset($item->color) ? $item->color : '') . '">';
                    $theTitle .= '<i class="fa fa-' . $itemTypeIcon . '"></i> ' . $outText;
                    if ( !is_null($position) ) {
                        $direction = $position === 'start' ? 'right' : 'left';
                        $theTitle  .= '<i class="o-calendar-interval-icon fa fa-long-arrow-' . $direction . '"></i>';
                    }
                    $theTitle .= '</span>';
                }
            }
        }

        $calendarItemHtml = sprintf(
            '<div %s>%s</div>',
            implode(' ', $wrapperAttrsHtmlArray),
            $projectTimeframesMode ? ($weekDateIndex === 0 || $item->startDate === $weekDate ? $theTitle : "&nbsp;") : $theTitle
        );

        return $calendarItemHtml;
    }

    /**
     * AJAX endpoint that generates a new calendar based on custom parameters.
     *
     * @since   1.0.0
     * @static
     */
    public static function reloadCalendar()
    {
        header('Content-Type: application/json');

        $response = [
            'success'       => false,
            'data'          => null,
            'error_message' => null,
        ];

        try {
            // Check if the request is AJAX.
            if ( ! defined('DOING_AJAX') || ! DOING_AJAX) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check the correspondent nonce.
            if ( ! wp_verify_nonce($_GET['nonce'], UPSTREAM_CALENDAR_VIEW_NAME)) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check if the project exists.
            $project_id = 0;
            if (isset($_GET['project_id']) && $_GET['project_id'] > 0) {
                $project = get_post((int)$_GET['project_id']);
                if ($project === false) {
                    throw new \Exception(__("Invalid Project.", 'upstream'));
                }

                $project_id = (int)$project->ID;
            }

            $isArchive = isset($_GET['is_archive']) && (bool)$_GET['is_archive'];

            ob_start();
            $calendar             = new Calendar();
            $calendar->weeksCount = (int)$_GET['weeks'];

            if ($isArchive) {
                $calendar->project_id  = null;
                $calendar->showProject = true;

                $options = Plugin::getOptions();
                if ( ! isset($options->overview_projects_timeframes) || (isset($options->overview_projects_timeframes) && (bool)$options->overview_projects_timeframes)) {
                    $calendar->projectTimeframesOnly = true;
                }
            } elseif ($project_id > 0) {
                $calendar->project_id = $project_id;
            }

            $jumpToMonth = null;
            if (isset($_GET['month'])) {
                $jumpToMonth = is_numeric($_GET['month']) ? (int)$_GET['month'] : 0;
                if ($jumpToMonth >= 1 && $jumpToMonth <= 12) {
                    $referenceDate           = self::getFirstDateOfTheWeek(sprintf('%s-%s-01', date('Y'),
                        ($jumpToMonth < 10 ? '0' . $jumpToMonth : $jumpToMonth)), 'Y-m-d', 1,
                        $calendar->firstDayOfTheWeek);
                    $calendar->referenceDate = $referenceDate;
                }
            }

            if (empty($jumpToMonth) && isset($_GET['date']) && ! empty($_GET['date'])) {
                $referenceDate           = self::getFirstDateOfTheWeek($_GET['date'], 'Y-m-d', 1,
                    $calendar->firstDayOfTheWeek);
                $calendar->referenceDate = $referenceDate;
            }

            self::renderCalendar($calendar);

            $response['data'] = ob_get_contents();
            ob_end_clean();

            $response['success'] = true;
        } catch (Exception $e) {
            $response['error_message'] = $e->getMessage();
        }

        echo json_encode($response);

        wp_die();
    }

    /**
     * AJAX endpoint that changes the date from a calendar.
     *
     * @since   1.0.0
     * @static
     */
    public static function changeCalendarDate()
    {
        header('Content-Type: application/json');

        $response = [
            'success'       => false,
            'data'          => null,
            'error_message' => null,
        ];

        try {
            // Check if the request is AJAX.
            if ( ! defined('DOING_AJAX') || ! DOING_AJAX) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check the correspondent nonce.
            if ( ! wp_verify_nonce($_GET['nonce'], UPSTREAM_CALENDAR_VIEW_NAME)) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check if the project exists.
            $project_id = (int)$_GET['project_id'];
            if ($project_id > 0) {
                $project = get_post($project_id);
                if (empty($project)) {
                    throw new \Exception(__("Invalid Project.", 'upstream'));
                }
            }

            $previousReferenceDate = $_GET['start_date'];
            if (empty($previousReferenceDate)) {
                throw new \Exception(sprintf(__('Invalid "%s" parameter.', 'upstream-calendar-view'),
                    'start_date'));
            }

            $direction = strtolower($_GET['direction']);
            if ($direction === "today") {
                $referenceDate = date('Y-m-d');
            } elseif ($direction !== "past" && $direction !== "future") {
                throw new \Exception(sprintf(__('Invalid "%s" parameter.', 'upstream-calendar-view'),
                    'direction'));
            } else {
                $amount = strtolower($_GET['amount']);
                if ($amount !== "week" && $amount !== "month") {
                    throw new \Exception(sprintf(__('Invalid "%s" parameter.', 'upstream-calendar-view'),
                        'amount'));
                }

                if ($direction === "future") {
                    $referenceDate = date('Y-m-d', strtotime('+1 ' . $amount, strtotime($previousReferenceDate)));
                } else {
                    $referenceDate = date('Y-m-d', strtotime('-1 ' . $amount, strtotime($previousReferenceDate)));
                }
            }

            $weeksCount = (int)$_GET['weeks'];
            if ($weeksCount <= 0 || $weeksCount > 12) {
                throw new \Exception(sprintf(__('Invalid "%s" parameter.', 'upstream-calendar-view'), 'weeks'));
            }

            $firstDayOfTheWeek = (int)$_GET['first_day'];
            if ($firstDayOfTheWeek < 0 || $firstDayOfTheWeek > 6) {
                throw new \Exception(sprintf(__('Invalid "%s" parameter.', 'upstream-calendar-view'),
                    'first_day'));
            }

            $isArchive = isset($_GET['is_archive']) && (int)$_GET['is_archive'];

            $referenceDate = self::getFirstDateOfTheWeek($referenceDate, 'Y-m-d', 1, $firstDayOfTheWeek);

            ob_start();
            $calendar                    = new Calendar($project_id);
            $calendar->weeksCount        = $weeksCount;
            $calendar->referenceDate     = $referenceDate;
            $calendar->firstDayOfTheWeek = $firstDayOfTheWeek;

            if ($isArchive) {
                $calendar->project_id  = null;
                $calendar->showProject = true;

                $options = Plugin::getOptions();
                if ( ! isset($options->overview_projects_timeframes) || (isset($options->overview_projects_timeframes) && (bool)$options->overview_projects_timeframes)) {
                    $calendar->projectTimeframesOnly = true;
                }
            } elseif ($project_id > 0) {
                $calendar->project_id = $project_id;
            }

            self::renderCalendar($calendar);

            $response['data'] = ob_get_contents();
            ob_end_clean();

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
        }

        echo json_encode($response);

        wp_die();
    }

    /**
     * AJAX endpoint that move an item to another date
     *
     * @throws \Exception
     * @since   1.0.0
     * @static
     *
     */
    public static function moveItem()
    {
        header('Content-Type: application/json');

        $response = [
            'success'       => false,
            'data'          => [],
            'error_message' => null,
        ];

        try {
            // Check if the request is AJAX.
            if ( ! defined('DOING_AJAX') || ! DOING_AJAX) {
                throw new \Exception(__("Invalid request.", 'upstream-calendar-view'));
            }

            if ( ! upstream_permissions('edit_projects')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            // Check the correspondent nonce.
            if ( ! wp_verify_nonce($_POST['nonce'], UPSTREAM_CALENDAR_VIEW_NAME)) {
                throw new \Exception(__("Invalid request.", 'upstream-calendar-view'));
            }

            // Check if the project exists.
            $projectId = 0;
            if (isset($_POST['project_id'])) {
                $projectId = (int)$_POST['project_id'];
                $project   = get_post($projectId);
                if (empty($project)) {
                    throw new \Exception(__("Invalid Project.", 'upstream'));
                }
            }

            $updated = false;

            $newDate = upstream_date_unixtime(sanitize_text_field($_POST['new_date']), 'Y-m-d');

            $response['data']['new_date']     = $newDate;
            $response['data']['new_date_str'] = upstream_format_date($newDate);

            // Get the item and change its date.
            switch ($_POST['item_type']) {
                case 'task':
                    $items = (array)get_post_meta($projectId, '_upstream_project_tasks', true);
                    if (count($items) > 0) {
                        foreach ($items as &$item) {
                            if (isset($item['id']) && $item['id'] === $_POST['item_id']) {
                                if (isset($_POST['item_date_ref'])) {
                                    if ($_POST['item_date_ref'] === 'start') {
                                        $item['start_date'] = $newDate;
                                        $updated            = true;
                                    } elseif ($_POST['item_date_ref'] === 'end') {
                                        $item['end_date'] = $newDate;
                                        $updated          = true;
                                    }

                                    // Correct the date order, if required. The user can move the start date to after the end date.
                                    if ($updated) {
                                        $dates = self::reorderDates($item['start_date'], $item['end_date']);

                                        $item['start_date'] = $dates[0];
                                        $item['end_date']   = $dates[1];
                                    }
                                }
                            }
                        }

                        if ($updated) {
                            update_post_meta($projectId, '_upstream_project_tasks', $items);
                        }
                    }
                    break;

                case 'milestone':
                    // Get the milestone

                    $milestone = Factory::getMilestone((int)$_POST['item_id']);

                    if (isset($_POST['item_date_ref'])) {
                        $startDate = $milestone->getStartDate('unix');
                        $endDate   = $milestone->getEndDate('unix');

                        if ($_POST['item_date_ref'] === 'start') {
                            $startDate = $newDate;
                            $updated   = true;
                        } elseif ($_POST['item_date_ref'] === 'end') {
                            $endDate = $newDate;
                            $updated = true;
                        }

                        // Correct the date order, if required. The user can move the start date to after the end date.
                        $dates = self::reorderDates($startDate, $endDate);

                        $milestone->setStartDate($dates[0]);
                        $milestone->setEndDate($dates[1]);
                    }
                    break;

                case 'bug':
                    $items = (array)get_post_meta($projectId, '_upstream_project_bugs', true);
                    if (count($items) > 0) {
                        foreach ($items as &$item) {
                            if (isset($item['id']) && $item['id'] === $_POST['item_id']) {
                                $item['due_date'] = $newDate;
                                $updated          = true;
                            }
                        }

                        if ($updated) {
                            update_post_meta($projectId, '_upstream_project_bugs', $items);
                        }
                    }
                    break;
            }

            $response['success'] = $updated;

            if ( ! $updated) {
                $response['error_message'] = esc_html__('Nothing updated', 'upstream-calendar-view');
            }
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
        }

        echo json_encode($response);

        wp_die();
    }

    /**
     * @param $date1
     * @param $date2
     */
    protected static function reorderDates($date1, $date2)
    {
        if ($date1 > $date2) {
            $tmp   = $date1;
            $date1 = $date2;
            $date2 = $tmp;
        }

        if (empty($date1)) {
            $date1 = $date2;
            $date2 = '';
        }

        return [$date1, $date2];
    }

    /**
     * Render the calendar panel.
     *
     * @since   1.0.0
     * @static
     */
    public static function renderCalendarPanel()
    {
        if ( ! Plugin::canRunOnCurrentPage()) {
            return;
        }

        $options = Plugin::getOptions();

        $weeksCount = (int)$options->weeks_count;
        $project_id = self::getCurrentProjectId();

        self::cacheProjectMembers($project_id);

        $currentUserId  = (int)get_current_user_id();
        $projectMembers = self::$cachedProjectMembers[$project_id];

        include UP_CALENDAR_VIEW_PATH . '/templates/panel.php';
    }

    /**
     * @param $panels
     *
     * @return array
     */
    public static function filterPanelSections($panels)
    {
        $newList = [];

        foreach ($panels as $i => $panel) {
            $newList[] = $panel;

            if (0 == $i) {
                $newList[] = 'calendar';
            }
        }

        return $newList;
    }

    /**
     * Fetch all project milestones or all milestones from all projects if $project_id is empty.
     *
     * @param int $project_id The project ID.
     *
     * @return  array
     * @since   1.0.1
     * @access  private
     * @static
     *
     */
    private static function fetchMilestones($project_id = 0)
    {
        $project_id = is_numeric($project_id) ? ($project_id > 0 ? (int)$project_id : 0) : 0;

        $cache = [];

        if ($project_id > 0) {
            $cache = \UpStream\Milestones::getInstance()->getMilestonesFromProject($project_id, true);
        } else {
            $projects = get_posts([
                'post_type'      => "project",
                'post_status'    => "publish",
                'posts_per_page' => -1,
            ]);

            foreach ($projects as $project) {
                $milestones = \UpStream\Milestones::getInstance()->getMilestonesFromProject($project->ID, true);

                $cache = array_merge($cache, $milestones);
            }
        }

        return $cache;
    }
}
