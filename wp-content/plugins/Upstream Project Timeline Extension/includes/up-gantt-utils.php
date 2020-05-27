<?php

// Exit if accessed directly.
if ( ! defined('ABSPATH')) {
    exit;
}

// Ignore this class if it is already defined.
if (class_exists('UpStream_Gantt_Utils')) {
    return;
}

/**
 * UpStream Gantt Chart Utils.
 *
 * @since 1.3.0
 */
final class UpStream_Gantt_Utils
{
    public static function quickSortByStartDate($subject)
    {
        if ( ! is_array($subject)
             || count($subject) < 2
        ) {
            return $subject;
        }

        $leftPortion = $rightPortion = [];
        reset($subject);

        $pivotKey = key($subject);
        $pivot    = array_shift($subject);

        if ( ! isset($pivot['start_date'])) {
            $pivot['start_date'] = 0;
        }

        foreach ($subject as $key => $value) {
            if ( ! isset($value['start_date'])) {
                $value['start_date'] = 0;
            }

            if ($value['start_date'] < $pivot['start_date']) {
                $leftPortion[$key] = $value;
            } else {
                $rightPortion[$key] = $value;
            }
        }

        return array_merge(
            self::quickSortByStartDate($leftPortion),
            [$pivotKey => $pivot],
            self::quickSortByStartDate($rightPortion)
        );
    }

    /**
     * Check either milestones are disabled or not.
     *
     * @return  bool
     *
     * @since   1.3.0 Moved from the UpStream_Gantt_Chart class to this class
     * @since   1.0.7
     *
     * @static
     */
    public static function areMilestonesDisabled()
    {
        $areMilestonesSectionDisabled = (
            ! function_exists('upstream_disable_milestones') ||
            (
                function_exists('upstream_disable_milestones') &&
                upstream_disable_milestones()
            )
        );

        $areAllMilestonesDisabled = (
            ! function_exists('upstream_are_milestones_disabled') ||
            (
                function_exists('upstream_are_milestones_disabled') &&
                upstream_are_milestones_disabled()
            )
        );

        return $areMilestonesSectionDisabled || $areAllMilestonesDisabled;
    }

    /**
     * Check if we're not on admin and viewing a project page.
     *
     * @return  bool
     * @since   1.3.0
     *
     */
    public static function canRunOnCurrentPage()
    {
        if ( ! is_admin() && get_post_type() === "project") {

            /* RSD: why is this here?
            $user           = wp_get_current_user();
            $userCanProceed = count(array_intersect($user->roles,
                    ['administrator', 'upstream_manager', 'upstream_user', 'upstream_client_user'])) > 0;
            if ( ! $userCanProceed) {
                if ( ! user_can($user, 'edit_published_projects') && ! user_can($user, 'edit_others_projects')) {
                    return false;
                }
            }
            */
            return true;
        }

        return false;
    }

    /**
     * Checks if we're on a post post_type="project" page which is not archived.
     *
     * @return  bool
     * @since   1.0.7
     * @access  private
     *
     */
    public static function isProjectPage()
    {
        $postType  = get_post_type();
        $isArchive = is_post_type_archive();

        return $postType === 'project' && ! $isArchive;
    }

    /**
     * @return bool
     */
    public static function isTimelineOverviewPage()
    {
        return ! is_admin() && isset($_GET['view']) && 'timeline' === $_GET['view'];
    }
}
