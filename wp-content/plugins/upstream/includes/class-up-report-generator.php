<?php
/**
 * Setup message asking for review.
 *
 * @author   UpStream
 * @category Admin
 * @package  UpStream/Admin
 * @version  1.0.0
 */

// Exit if accessed directly or already defined.
if ( ! defined('ABSPATH') || class_exists('UpStream_Report_Generator')) {
    return;
}

/**
 * Class UpStream_Report
 */
class UpStream_Report_Generator
{

    public static function getBuiltinReports()

    {
        if (!class_exists('UpStream_Report')) {
            return [];
        }

        $r = new UpStream_Report_Projects();

        return [$r];
    }

    public static function getReports()
    {
        $reports = self::getBuiltinReports();
        $reports = apply_filters('upstream_list_reports', $reports);
        return $reports;
    }

}
