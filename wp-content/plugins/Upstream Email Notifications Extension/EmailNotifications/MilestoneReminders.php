<?php

namespace UpStream\Plugins\EmailNotifications;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\EmailNotifications\Traits\Singleton;

/**
 * @package     UpStream\Plugins\EmailNotifications
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @final
 *
 * @uses        Singleton
 */
final class MilestoneReminders
{
    use Singleton;

    const POST_REMINDERS_META_KEY = 'upst_reminders';

    public static function updateAllMilestoneReminders($data)
    {
        foreach ($data['_upstream_project_milestones'] as $milestone) {

            $editedReminders = [];
            foreach ($milestone['reminders'] as $reminder) {
                if (is_numeric($reminder)) {
                    $editedReminders[] = $reminder;
                } else {
                    $r_data = json_decode(str_replace('\\', '', $reminder));
                    $editedReminders[] = $r_data->id;
                }
            }

            self::getInstance()->updateMilestoneReminders($milestone['id'], $editedReminders);

        }
    }

    /**
     * @param $milestoneId
     * @param $jsonArray an array of JSON encoded strings of reminders
     */
    public function updateMilestoneRemindersFromJson($milestoneId, $jsonArray)
    {
        $currentReminders = $this->getMilestoneReminders($milestoneId);

        // Remove all current reminders.
        $this->deleteMilestoneReminders($milestoneId);

        if (is_array($jsonArray) && ! empty($jsonArray)) {
            foreach ($jsonArray as $line) {
                add_post_meta($milestoneId, self::POST_REMINDERS_META_KEY, json_decode($line));
            }
        }
    }

    /**
     * @param $milestoneId
     * @param $editedReminders
     */
    public function updateMilestoneReminders($milestoneId, $editedReminders)
    {
        $currentReminders = $this->getMilestoneReminders($milestoneId);

        // Remove all current reminders.
        $this->deleteMilestoneReminders($milestoneId);

        if (is_array($editedReminders) && ! empty($editedReminders)) {
            foreach ($editedReminders as $reminderId) {
                // Does the reminder already exists?
                $theReminder = $this->getReminderInArray($reminderId, $currentReminders);

                if ($theReminder === false) {
                    // The $reminderId will be numeric when the reminder was just added. Otherwise, it is the id, alpha numeric.
                    $theReminder = (object)[
                        'reminder'   => (int)$reminderId,
                        'id'         => $this->generateRandomString(),
                        'created_at' => time(),
                        'sent'       => false,
                        'sent_at'    => null,
                    ];
                }

                add_post_meta($milestoneId, self::POST_REMINDERS_META_KEY, $theReminder);
            }
        }
    }

    /**
     * @param $milestoneId
     *
     * @return mixed
     */
    public function getMilestoneReminders($milestoneId)
    {
        $currentReminders = get_post_meta($milestoneId, self::POST_REMINDERS_META_KEY);

        if ( ! empty($currentReminders)) {
            foreach ($currentReminders as &$reminder) {
                $reminder = maybe_unserialize($reminder);
            }
        }

        return $currentReminders;
    }

    /**
     * @param $milestoneId
     */
    public function deleteMilestoneReminders($milestoneId)
    {
        delete_post_meta($milestoneId, self::POST_REMINDERS_META_KEY);
    }

    /**
     * @param $reminderId
     * @param $reminders
     *
     * @return object
     */
    protected function getReminderInArray($reminderId, $reminders)
    {
        if ( ! empty($reminders)) {
            foreach ($reminders as $reminder) {
                if ( ! is_object($reminder)) {
                    continue;
                }

                // It is numeric when the reminder was added, so it has the index of the reminders.
                if (is_numeric($reminderId)) {
                    if (isset($reminder->reminder) && $reminder->reminder == $reminderId) {
                        return $reminder;
                    }
                } else {
                    // It is not numeric, but the id when it is an existent reminder.
                    if (isset($reminder->id) && $reminder->id == $reminderId) {
                        return $reminder;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    protected function generateRandomString($length = 10)
    {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString     = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
