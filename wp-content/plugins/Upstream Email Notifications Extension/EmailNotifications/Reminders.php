<?php

namespace UpStream\Plugins\EmailNotifications;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Milestone;
use UpStream\Plugins\EmailNotifications\Traits\Singleton;

/**
 * @package     UpStream\Plugins\EmailNotifications
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.1.0
 * @final
 *
 * @uses        Singleton
 */
final class Reminders
{
    use Singleton;



    final static function __fix_timestamp($type, $ts) {
        // RSD: added this to fix timestamp bugs from old versions
        // TODO: will need to be removed at some point

        if ("tasks" == $type || "bugs" === $type) {
            $offset = get_option( 'gmt_offset' );
            return $ts + ( $offset > 0 ? $offset * 60 * 60 : 0 );
        }

        return $ts;
    }

    /**
     * Indicates either the reminders field modal has been rendered or not.
     *
     * @since   1.1.0
     * @access  private
     * @static
     *
     * @var     bool $remindersFieldModalHasBeenAdded
     */
    private static $remindersFieldModalHasBeenAdded = false;

    /**
     * Indicates either all default reminders were rendered to HTML already.
     *
     * @since   1.1.3
     * @access  private
     * @static
     *
     * @var     bool $defaultRemindersWereLoaded
     */
    private static $defaultRemindersWereLoaded = false;

    public static function attachHooks()
    {
        if ( ! self::areRemindersEnabled()) {
            return;
        }

        $class = __NAMESPACE__ . '\\Reminders';


        add_action('wp_insert_post', [$class, 'addMilestoneReminder'], 10, 3);

        // Attach filter to deal with save action coming via wp-admin.
        add_filter('upstream:project.onBeforeUpdateMissingMeta', [$class, 'savePotentialReminders'], 10, 3);

        // Attach hooks to deal with insert and update actions coming via frontend.
        add_filter('upstream.frontend-edit:project.onBeforeInsertMeta', [$class, 'onBeforeInsertMetaOnFrontEnd'],
            10, 4);
        add_filter('upstream.frontend-edit:project.onBeforeEditMeta', [$class, 'onBeforeEditMetaOnFrontEnd'], 10,
            4);

        add_action('upstream_email_notifications_before_edit_milestone', [$class, 'onBeforeEditMilestone'], 10, 4);

        // Render all item reminders data on frontend data-tables.
        add_filter('upstream:frontend:project.table.body.td_value', [$class, 'renderRemindersForItemsOnFrontEnd'],
            10, 7);
        // Render reminders UI on frontend item's forms.
        add_action('upstream.frontend-edit:renderAfter.project.items.end_dates',
            [$class, 'renderAfterProjectItemsEndDates'], 10, 1);

        add_filter('upstream_localized_javascript', [$class, 'loadDefaultRemindersOnJS'], 10, 1);

        add_filter('upstream:project.milestones.fields', [$class, 'defineRemindersField']);
        add_filter('upstream:project.tasks.fields', [$class, 'defineRemindersField']);
        add_filter('upstream:project.bugs.fields', [$class, 'defineRemindersField']);

        // Add reminders to milestones when they are converted into legacy rowset, to introduce them in the Frontend UI.
        add_filter('upstream_milestone_converting_legacy_rowset', [$class, 'addRemindersToMilestone']);
    }

    /**
     * Check if the reminders feature are enabled.
     *
     * @return  bool
     * @since   1.1.0
     * @static
     *
     */
    public static function areRemindersEnabled()
    {
        $options = Plugin::getOptions();

        if (isset($options['reminders']) && (bool)$options['reminders'] === false) {
            return false;
        }

        return true;
    }

    public static function run()
    {
        if ( ! self::areRemindersEnabled()) {
            return;
        }

        $namespace = __NAMESPACE__ . '\Reminders';

        add_filter('upstream_milestone_metabox_fields', [$namespace, 'addRemindersFieldForMilestones']);
        add_filter('upstream_task_metabox_fields', [$namespace, 'addRemindersFieldForTasks']);
        add_filter('upstream_bug_metabox_fields', [$namespace, 'addRemindersFieldForBugs']);

        // Clear any cron attached to the old hook.
        if (wp_next_scheduled('upstream:email-notifications:checkUpcomingReminders')) {
            wp_clear_scheduled_hook('upstream:email-notifications:checkUpcomingReminders');
        }

//self::checkUpcomingReminders();
        // Check for upcoming reminders hourly.
        $remindersHookName = 'upstream.email-notifications:checkUpcomingReminders';
        add_action($remindersHookName, [$namespace, 'checkUpcomingReminders']);

        if ( ! wp_next_scheduled($remindersHookName)) {
            $options = Plugin::getOptions();

            $checkFrequency = isset($options['reminders_check_frequency']) ? (int)$options['reminders_check_frequency'] : '0';
            switch ($checkFrequency) {
                case 1:
                    $checkFrequency = 'twicedaily';
                    break;
                case 2:
                    $checkFrequency = 'daily';
                    break;
                default:
                    $checkFrequency = 'hourly';
                    break;
            }

            wp_schedule_event(time(), $checkFrequency, $remindersHookName);
        }
    }

    public static function addMilestoneReminder($post_id, $post, $update)
    {
        if (! $update && isset($_POST['type']) && $_POST['type'] == 'milestones' && isset($_POST['data']) && isset($_POST['data']['reminders'])) {
            $m = MilestoneReminders::getInstance();
            $m->updateMilestoneReminders($post_id, $_POST['data']['reminders']);
        }
    }

    /**
     * This method is called latter after the post is saved.
     * It is responsible for saving reminders on admin area.
     *
     * @param array  $data       Data being saved.
     * @param int    $project_id Post ID.
     * @param string $metaKey    Meta key being saved.
     *
     * @return  array   $data
     * @since   1.1.0
     * @static
     *
     */
    public static function savePotentialReminders($data, $project_id, $metaKey)
    {

        if ($metaKey == "_upstream_project_milestones" && isset($_POST['_upstream_project_milestones'])) {
            MilestoneReminders::updateAllMilestoneReminders($_POST);
            return;
        }

        if (empty($_POST) || empty($data)) {
            return $data;
        }

        $dataMap = [];
        foreach ($data as $rowIndex => &$row) {
            $dataMap[$row['id']] = &$row;
        }

        if (isset($_POST[$metaKey]) && ! empty($_POST[$metaKey])) {
            $currentTimestamp = time();
            $reminders        = [];
            $items            = $_POST[$metaKey];

            foreach ($items as $itemIndex => $item) {
                if (isset($item['reminders'])) {
                    $item_id = (string)$item['id'];
                    if (empty($item_id)) {
                        if ( ! isset($data[$itemIndex])) {
                            continue;
                        }

                        $item_id = $data[$itemIndex]['id'];
                    }

                    foreach ($item['reminders'] as $reminder) {
                        if (is_numeric($reminder)) {
                            if ( ! isset($reminders[$item_id])) {
                                $reminders[$item_id] = [];
                            }

                            $reminders[$item_id][] = json_encode([
                                'reminder'   => (int)$reminder,
                                'id'         => substr(md5(rand()), 0, 14),
                                'sent'       => false,
                                'created_at' => $currentTimestamp,
                                'sent_at'    => null,
                            ]);
                        } else {
                            $reminder = json_decode(str_replace('\\', '', $reminder));
                            if ( ! empty($reminder)) {
                                $reminders[$item_id][] = json_encode($reminder);
                            }
                        }
                    }

                    if (count($reminders[$item_id]) > 0 && isset($dataMap[$item_id])) {
                        $dataMap[$item_id]['reminders'] = $reminders[$item_id];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Filter called by 'UpStream > Frontend Edit' plugin before inserting metas on frontend.
     * It will handle new reminders for the newly added meta-items.
     *
     * @param array  $metaValue  The meta value.
     * @param int    $project_id The project ID where the meta belongs to.
     * @param string $itemType   The type of the item. I.e.: "milestones", "tasks" or "bugs".
     * @param string $metaKey    The meta key that will be inserted.
     *
     * @return  array   $metaValue
     * @since   1.1.3
     * @static
     *
     */
    public static function onBeforeInsertMetaOnFrontEnd($metaValue, $project_id, $itemType, $metaKey)
    {
        if (in_array($itemType, ['milestones', 'tasks', 'bugs'])) {
            if (count($metaValue) > 0) {
                $currentTimestamp = time();

                foreach ($metaValue as &$item) {
                    if (isset($item['reminders']) && is_array($item['reminders']) && count($item['reminders']) > 0) {
                        $reminders = [];
                        foreach ($item['reminders'] as $reminderType) {
                            if (is_numeric($reminderType)) {
                                $reminderType = (int)$reminderType;
                                // Prevent dups.
                                if (isset($reminders[$reminderType])) {
                                    continue;
                                }

                                $reminders[$reminderType] = json_encode([
                                    'reminder'   => $reminderType,
                                    'id'         => substr(md5(rand()), 0, 14),
                                    'sent'       => false,
                                    'created_at' => $currentTimestamp,
                                    'sent_at'    => null,
                                ]);
                            }
                        }

                        if (count($reminders) > 0) {
                            $item['reminders'] = array_values($reminders);
                        }
                    }
                }
            }
        }

        return $metaValue;
    }

    /**
     * Filter called by 'UpStream > Frontend Edit' plugin before updating metas on frontend.
     * It will handle incoming new reminders as well the ones being removed from a particular item.
     *
     * @param array  $metaValue  The meta value.
     * @param int    $project_id The project ID where the meta belongs to.
     * @param string $itemType   The type of the item. I.e.: "milestones", "tasks" or "bugs".
     * @param string $metaKey    The meta key that will be updated.
     *
     * @return  array   $metaValue
     * @since   1.1.3
     * @static
     *
     */
    public static function onBeforeEditMetaOnFrontEnd($metaValue, $project_id, $itemType, $metaKey)
    {
        if (
            count($metaValue) > 0
            && in_array($itemType, ['milestones', 'tasks', 'bugs'])
            && isset($_POST['editing'])
            && ! empty($_POST['editing'])
        ) {
            $currentTimestamp = time();
            $item_id          = $_POST['editing'];

            $incomingReminders = isset($_POST['data']['reminders']) ? (array)$_POST['data']['reminders'] : [];

            foreach ($metaValue as &$item) {
                if ( ! isset($item['id'])
                     || $item['id'] != $item_id
                ) {
                    continue;
                }

                if (count($incomingReminders) === 0) {
                    unset($item['reminders']);
                } else {
                    $existentRemindersById   = [];
                    $existentRemindersByType = [];

                    $potentialNewReminders = [];
                    $potentialExistentIds  = [];
                    if (isset($item['reminders']) && count($item['reminders']) > 0) {
                        $item['reminders'] = array_filter($item['reminders']);
                        foreach ($item['reminders'] as $reminderIdentifier) {
                            if (is_numeric($reminderIdentifier)) {
                                $potentialNewReminders[] = (int)$reminderIdentifier;
                            } elseif ( ! empty($reminderIdentifier)) {
                                $potentialExistentIds[] = $reminderIdentifier;
                            }
                        }

                        if (count($potentialExistentIds) > 0) {
                            $meta = (array)get_post_meta($project_id, '_upstream_project_' . $itemType, true);
                            foreach ($meta as $metaItem) {
                                if ( ! isset($metaItem['id'])
                                     || $metaItem['id'] !== $item['id']
                                ) {
                                    continue;
                                }

                                if (isset($metaItem['reminders']) && is_array($metaItem['reminders'])) {
                                    foreach ($metaItem['reminders'] as $itemReminder) {
                                        $itemReminder = json_decode($itemReminder);
                                        if (is_object($itemReminder)
                                            && isset($itemReminder->id)
                                            && in_array($itemReminder->id, $potentialExistentIds)
                                        ) {
                                            $existentRemindersById[$itemReminder->id]              = $itemReminder;
                                            $existentRemindersByType[(int)$itemReminder->reminder] = &$existentRemindersById[$itemReminder->id];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Register added reminders.
                    foreach ($potentialNewReminders as $incomingReminder) {

                        if (is_numeric($incomingReminder)) {
                            if ($incomingReminder > 1000 || !isset($existentRemindersByType[$incomingReminder])) {

                                do {
                                    $newReminderId = substr(md5(rand()), 0, 14);
                                } while (isset($existentRemindersById[$newReminderId]));

                                $existentRemindersById[$incomingReminder] = (object)[
                                    'reminder' => (int)$incomingReminder,
                                    'id' => $newReminderId,
                                    'sent' => false,
                                    'created_at' => $currentTimestamp,
                                    'sent_at' => null,
                                ];

                            }
                        }

                    }

                    if (count($existentRemindersById) === 0) {
                        unset($item['reminders']);
                    } else {
                        $item['reminders'] = array_map('json_encode', array_values($existentRemindersById));
                    }
                }

                break;
            }
        }

        return $metaValue;
    }

    public static function onBeforeEditMilestone($milestoneId, $rowset, $data, $projectId)
    {
        $model = MilestoneReminders::getInstance();

        // Get current milestone's reminders
        $currentReminders = $model->getMilestoneReminders($milestoneId);

        // Get the edited reminders.
        $newReminders = $data['reminders'];

        // Do we need to delete the current reminders?
        if (empty($newReminders) && ! empty($currentReminders)) {
            // Remove current reminders.
            $model->deleteMilestoneReminders($milestoneId);
        }

        // Update the reminders
        $model->updateMilestoneReminders($milestoneId, $data['reminders']);
    }

    /**
     * Hook endpoint called by UpStream on every data row being rendered on frontend.
     */
    public static function renderRemindersForItemsOnFrontEnd(
        $html,
        $columnName,
        $columnValue,
        $column,
        $row,
        $rowType,
        $projectId
    ) {
        if ($columnName === 'reminders'
            && is_array($columnValue)
            && count($columnValue) > 0
        ) {
            $remindersAvailable = self::getRemindersAvailable();

            $remindersList = [];
            foreach ($columnValue as $reminderJson) {
                try {
                    $reminder = json_decode($reminderJson);
                    if ( ! empty($reminder) && isset($reminder->reminder) && isset($reminder->id) && isset($reminder->created_at)
                    ) {
                        if (isset($remindersAvailable[(int)$reminder->reminder])) {
                            $remindersList[] = '<span>' . $remindersAvailable[(int)$reminder->reminder] . '</span><input type="hidden" data-reminder="' . $reminder->reminder . '" data-sent="' . (int)$reminder->sent . '" value="' . $reminder->id . '">';
                        }
                        else {
                            $remindersList[] = '<span>' . upstream_format_date($reminder->reminder) . '</span><input type="hidden" data-reminder="' . $reminder->reminder . '" data-sent="' . (int)$reminder->sent . '" value="' . $reminder->id . '">';
                        }
                    }
                } catch (\Exception $e) {
                    // Just keep going.
                }
            }

            if (count($remindersList) > 0) {
                $html = implode(', ', $remindersList);
            }
        }

        return $html;
    }

    public static function getRemindersAvailable()
    {
        $rowset = [
            1 => __('A day before', 'upstream-email-notifications'),
            2 => __('Two days before', 'upstream-email-notifications'),
            3 => __('Three days before', 'upstream-email-notifications'),
            4 => __('A week before', 'upstream-email-notifications'),
            5 => __('Two weeks before', 'upstream-email-notifications'),
        ];

        return $rowset;
    }

    /**
     * Hook called by 'UpStream > FrontEnd Edit' plugin.
     * It renders the reminders UI on frontend.
     *
     * @param string $itemType The item type being rendered. Allowed values are: "milestones", "tasks" or "bugs".
     *
     * @since   1.1.0
     * @static
     *
     */
    public static function renderAfterProjectItemsEndDates($itemType)
    {
        if (in_array($itemType, ['milestones', 'tasks', 'bugs'])):
            $pluginOptions = Plugin::getOptions();
            $defaultReminders = isset($pluginOptions['default_reminders']) ? (array)$pluginOptions['default_reminders'] : [];

            $remindersLabels = [
                1 => __('A day before', 'upstream-email-notifications'),
                2 => __('Two days before', 'upstream-email-notifications'),
                3 => __('Three days before', 'upstream-email-notifications'),
                4 => __('A week before', 'upstream-email-notifications'),
                5 => __('Two weeks before', 'upstream-email-notifications'),
            ];

            $id_ext = rand();
            ?>

            <div class="row">
                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                    <label for=""><?php _e('Reminders', 'upstream-email-notifications'); ?></label>
                </div>
                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                    <aside class="reminders-wrapper" style="border: 1px solid #e5e5e5;">
                        <div style="border-bottom: 1px solid #ddd; background: #F5F5F5; padding: 7px 2px 7px 7px;">
                            <select name="reminder">
                                <option value="" disabled selected><?php _e('Select when...',
                                        'upstream-email-notifications'); ?></option>
                                <?php foreach ($remindersLabels as $reminderIndex => $reminderLabel): ?>
                                    <option value="<?php echo $reminderIndex; ?>"<?php echo in_array($reminderIndex,
                                        $defaultReminders) ? ' disabled' : ''; ?>><?php echo $reminderLabel; ?></option>
                                <?php endforeach; ?>
                            </select>
                            &nbsp;
                            <?php echo $itemType === 'bugs' ? __('Due Date', 'upstream') : __('End Date',
                                'upstream'); ?>

                            &nbsp;&nbsp;

                            <input type="text" name="chooseadate" readonly id="upstream_chooseadate<?php print $id_ext ?>"  value="" placeholder="<?php echo __('or Select a Date', 'upstream-email-notifications'); ?>" class="o-datepicker">
                            <input type="hidden" name="chooseadate_timestamp" id="upstream_chooseadate<?php print $id_ext ?>_timestamp"  value="">

                            <button type="button" class="btn btn-default btn-xs pull-right" data-action="reminder:add"
                                    style="position: relative;">
                                <span class="dashicons dashicons-plus"></span>
                            </button>
                        </div>
                        <table class="reminders" style="width: 100%; margin: 10px 0;">
                            <thead>
                            <tr>
                                <th style="padding-left: 10px;"><?php _e('When',
                                        'upstream-email-notifications'); ?></th>
                                <th class="text-center"><?php _e('Sent', 'upstream-email-notifications'); ?></th>
                                <th style="width: 76px; text-align: center; padding-right: 0;"><?php _e('Discard?',
                                        'upstream'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($defaultReminders) > 0): ?>
                                <?php foreach ($defaultReminders as $defaultReminder): ?>
                                    <tr data-reminder="<?php echo $defaultReminder; ?>">
                                        <td style="padding-left: 10px;"><?php echo $remindersLabels[(int)$defaultReminder]; ?></td>
                                        <td class="text-center">
                                            <span class="dashicons dashicons-minus" title="<?php _e('Not saved yet.',
                                                'upstream-email-notifications'); ?>"></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="#" data-action="reminder:remove">
                                                <span class="dashicons dashicons-trash"></span>
                                            </a>
                                            <input type="hidden" name="data[reminders][]" data-default="1" value="<?php echo $defaultReminder; ?>"/>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr data-no-items>
                                    <td colspan="3" class="text-center"><?php _e('No reminders.',
                                            'upstream-email-notifications'); ?></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </aside>
                </div>
                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
            </div>

        <?php
        endif;
    }

    /**
     * Add reminders field into Milestones section.
     *
     * @param array $fields All fields in a project Milestones section.
     *
     * @return  array
     * @since   1.1.0
     * @static
     *
     */
    public static function addRemindersFieldForMilestones($fields)
    {
        $endDateFieldPriority = 0;

        // Find the End Date field.
        foreach ($fields as $priority => $field) {
            if (isset($field['id']) && $field['id'] === 'end_date') {
                $endDateFieldPriority = $priority;
            }
        }

        if ( ! empty($endDateFieldPriority)) {
            $endDateField = &$fields[$endDateFieldPriority];

            $endDateField['name'] = sprintf(self::getRemindersFieldSettings(),
                __('End Date', 'upstream'),
                'milestones',
                __('End Date Reminders', 'upstream-email-notifications')
            );

            $endDateField['after_field'] = [self::$instance, 'renderRemindersField'];
        }

        return $fields;
    }

    /**
     * Return default options for a Reminder field.
     *
     * @return  array
     * @since   1.1.0
     * @access  private
     * @static
     *
     */
    private static function getRemindersFieldSettings()
    {
        $fieldNameHtml = '
            <div class="lb-has-reminders">
              <span>%s</span>
              <a class="thickbox"
                 href="#TB_inline?width=350&height=200&inlineId=modal-reminders"
                 data-action="reminders:browse"
                 data-type="%s"
                 title="%s"
                 data-is-new="1"
              >' . __('Manage Reminders', 'upstream-email-notifications') . '</a>
            </div>';

        return trim($fieldNameHtml);
    }

    /**
     * Add reminders field into Tasks section.
     *
     * @param array $fields All fields in a project Tasks section.
     *
     * @return  array
     * @since   1.1.0
     * @static
     *
     */
    public static function addRemindersFieldForTasks($fields)
    {
        $endDateField = &$fields[31];

        $endDateField['name'] = sprintf(self::getRemindersFieldSettings(),
            __('End Date', 'upstream'),
            'tasks',
            __('End Date Reminders', 'upstream-email-notifications')
        );

        $endDateField['after_field'] = [self::$instance, 'renderRemindersField'];

        return $fields;
    }

    /**
     * Add reminders field into Bugs section.
     *
     * @param array $fields All fields in a project Bugs section.
     *
     * @return  array
     * @since   1.1.0
     * @static
     *
     */
    public static function addRemindersFieldForBugs($fields)
    {
        $dueDateField = &$fields[41];

        $dueDateField['name'] = sprintf(self::getRemindersFieldSettings(),
            __('Due Date', 'upstream'),
            'bugs',
            __('Due Date Reminders', 'upstream-email-notifications')
        );

        $dueDateField['after_field'] = [self::$instance, 'renderRemindersField'];

        return $fields;
    }

    /**
     * Render all reminders-inputs from a given item.
     *
     * @param array       $fieldOptions All field options.
     * @param \CMB2_Field $field        The field object.
     *
     * @since   1.1.0
     * @static
     *
     */
    public static function renderRemindersField($fieldOptions, $field)
    {
        $group_value = $field->group->value;

        if (empty($group_value)) {
            return;
        }

        $item = (object)$group_value[$field->group->index];
        if ( ! isset($item->id)) {
            return;
        }

        preg_match('/^_upstream_project_([a-z]+)_([0-9]+)/i', $field->args['id'], $matches);
        $itemType = $matches[1];

        // RSD: this wasn't working at all before, now it should pull properly
        if ($itemType === 'milestones') {
            // the reminder comes in already in the item
        }
        else if (in_array($itemType, ['tasks', 'bugs'])) {

            $metaItems = get_post_meta(upstream_post_id(), '_upstream_project_' . $itemType);
            $metaItems = ! empty($metaItems) ? $metaItems[0] : [];
            if ( ! empty($metaItems)) {
                foreach ($metaItems as $metaItem) {
                    if ($metaItem['id'] === $item->id) {
                        $item->reminders = isset($metaItem['reminders']) ? $metaItem['reminders'] : [];
                    }
                }
            } else {
                $item->reminders = [];
            }
            unset($metaItems, $metaItem);

        }
        else {
            return;
        }

        if ( ! self::$defaultRemindersWereLoaded) {
            $pluginOptions    = Plugin::getOptions();
            $defaultReminders = isset($pluginOptions['default_reminders']) ? (array)$pluginOptions['default_reminders'] : [];
            if ( ! empty($defaultReminders)):
                foreach ($defaultReminders as $defaultReminder):
                    if ((int)$defaultReminder >= 1 && (int)$defaultReminder <= 5): ?>
                        <input
                            type="hidden"
                            data-reminder="<?php echo $defaultReminder; ?>"
                            data-reminder-default
                        />
                    <?php endif;
                endforeach;
            endif;
            self::$defaultRemindersWereLoaded = true;
        }

        if (isset($item->reminders) && is_array($item->reminders) && count($item->reminders) > 0) {
            foreach ($item->reminders as $reminderJSON) {
                $reminder = json_decode($reminderJSON);
                if ( ! empty($reminder)) {
                    ?>
                    <input
                        type="hidden"
                        class="upstream-reminder"
                        data-type="<?php echo $itemType; ?>"
                        data-sent="<?php echo $reminder->sent; ?>"
                        data-created_at="<?php echo $reminder->created_at; ?>"
                        data-sent_at="<?php echo $reminder->sent_at; ?>"
                        data-reminder="<?php echo $reminder->reminder; ?>"
                        name="_upstream_project_<?php echo $itemType; ?>[<?php echo $field->group->index; ?>][reminders][]"
                        value="<?php echo htmlentities($reminderJSON); ?>"
                    />
                    <?php
                }
            }
        }

        $html = '';

        if ( ! self::$remindersFieldModalHasBeenAdded):

            $id_ext = rand();
            ?>
            <div id="modal-reminders" style="display: none;">
                <div id="modal-reminders-wrapper">
                    <div style="margin-top: 15px;">
                        <label>
                            <?php _e('Remind Assigned User', 'upstream-email-notifications'); ?>
                            <select name="reminder">
                                <option value="" disabled selected><?php _e('Select when...',
                                        'upstream-email-notifications'); ?></option>
                                <option value="1"><?php _e('A day before', 'upstream-email-notifications'); ?></option>
                                <option value="2"><?php _e('Two days before',
                                        'upstream-email-notifications'); ?></option>
                                <option value="3"><?php _e('Three days before',
                                        'upstream-email-notifications'); ?></option>
                                <option value="4"><?php _e('A week before', 'upstream-email-notifications'); ?></option>
                                <option value="5"><?php _e('Two weeks before',
                                        'upstream-email-notifications'); ?></option>
                            </select>
                            <?php _e('End Date', 'upstream'); ?>
                        </label>

                        <input type="text" name="chooseadate2" value="" readonly id="upstream_chooseadate2<?php print $id_ext ?>" placeholder="<?php echo __('or Select a Date', 'upstream-email-notifications'); ?>" class="o-datepicker">
                        <input type="hidden" name="chooseadate2_timestamp" id="upstream_chooseadate2<?php print $id_ext ?>_timestamp"  value="">

                        <button type="button" class="button" data-action="reminder:add"><?php _e('Add Reminder',
                                'upstream-email-notifications'); ?></button>
                    </div>
                    <table class="wp-list-table widefat fixed striped posts upstream-table" style="margin-top: 15px;">
                        <thead>
                        <tr>
                            <th style="padding-left: 10px;"><?php _e('When', 'upstream-email-notifications'); ?></th>
                            <th class="text-center"><?php _e('Sent', 'upstream-email-notifications'); ?></th>
                            <th style="width: 76px; text-align: center; padding-right: 0;"><?php _e('Discard?',
                                    'upstream'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr data-no-items>
                            <td colspan="3" style="text-align: center;">
                                <?php _e('Loading reminders...', 'upstream-email-notifications'); ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
            self::$remindersFieldModalHasBeenAdded = true;
        endif;
    }

    /**
     * Checks for upcoming reminders and send out notifications as needed.
     *
     * @since   1.1.0
     * @static
     */
    public static function checkUpcomingReminders()
    {
        // Cache all projects.
        $projects = call_user_func(function () {
            $projects = [];
            $config   = [
                'post_type'      => "project",
                'posts_per_page' => -1,
                'post_status'    => "publish",
            ];

            $rowset = get_posts($config);

            if (count($rowset) > 0) {
                $siteURL = get_site_url('', 'projects');

                foreach ($rowset as $row) {
                    $meta = (array)get_post_meta($row->ID, '_upstream_project_disable_all_notifications');
                    if (count($meta) > 0 && $meta[0] === 'on') {
                        continue;
                    }

                    $projects[$row->ID] = (object)[
                        'id'         => $row->ID,
                        'title'      => $row->post_title,
                        'url'        => $siteURL . '/' . $row->post_name,
                        'milestones' => [],
                        'tasks'      => [],
                        'bugs'       => [],
                    ];
                }

                if (count($projects) > 0) {
                    global $wpdb;

                    $rowset = $wpdb->get_results(sprintf('
                        SELECT `post_id`, `meta_key`, `meta_value`
                        FROM `%s`
                        WHERE `post_id` IN (%s)
                          AND `meta_key` IN ("_upstream_project_milestones", "_upstream_project_tasks", "_upstream_project_bugs")',
                        $wpdb->prefix . 'postmeta',
                        implode(', ', array_keys($projects))
                    ));

                    foreach ($rowset as $row) {
                        $itemsList = maybe_unserialize($row->meta_value);
                        if ( ! empty($itemsList)) {
                            $itemType = str_replace('_upstream_project_', '', $row->meta_key);

                            if (isset($itemsList[0]) && is_array($itemsList[0]) && isset($itemsList[0][0])) {
                                $itemsList = $itemsList[0];
                            }

                            $projects[(int)$row->post_id]->{$itemType} = [];
                            foreach ($itemsList as $item) {
                                $item                                                   = (array)$item;
                                $projects[(int)$row->post_id]->{$itemType}[$item['id']] = $item;
                            }
                        }
                    }
                }
            }

            return $projects;
        });

        if (count($projects) === 0) {
            return;
        }

        // Cache all users that might be notified.
        $users = call_user_func(function () {
            $users = [];

            $rowset = get_users([
                'role__in' => ['administrator', 'upstream_manager', 'upstream_user', 'upstream_client_user'],
            ]);

            foreach ($rowset as $user) {
                $users[$user->ID] = (object)[
                    'name'      => $user->display_name,
                    'email'     => $user->user_email,
                    'reminders' => [],
                ];
            }

            return $users;
        });

        $adminEmail       = get_bloginfo('admin_email');
        $currentTimestamp = time();
        $secondsInADay    = 1 * 60 * 60 * 24;
        $itemsType        = ['milestones', 'tasks', 'bugs'];
        $emailSubject     = sprintf(_x('%s Notifications About Upcoming Due Dates', '%s: Site name',
            'upstream-email-notifications'), get_bloginfo('name'));

        $itemsIdsCache = [];
        $projectsCache = [];
        $milestones    = [];

        $itemsLabelsMap = [
            'milestones' => upstream_milestone_label_plural(),
            'tasks'      => upstream_task_label_plural(),
            'bugs'       => upstream_bug_label_plural(),
        ];

        foreach ($projects as &$project) {
            $projectsCache[$project->id] = $project;

            // TODOPERM: this breaks with perms
            $project->milestones = \UpStream\Milestones::getInstance()->getAllMilestonesFromProject($project->id, true);

            foreach ($itemsType as $itemType) {
                if ( ! isset($project->{$itemType}) || count($project->{$itemType}) === 0) {
                    continue;
                }

                foreach ($project->{$itemType} as $item) {
                    $item = (object)$item;

                    if (
                        // Check if the item has an end/due date.
                        (empty($item->due_date) && empty($item->end_date))
                        ||
                        // Check if the assigned user is valid.
                        (
                            ! isset($item->assigned_to)
                            || empty($item->assigned_to)
                        )
                        ||
                        // Check if there's any reminder set.
                        ( ! isset($item->reminders) || empty($item->reminders))
                    ) {
                        continue;
                    }

                    $endDate = self::__fix_timestamp($itemType, (int)(isset($item->due_date) ? $item->due_date : $item->end_date));

                    $assignees         = [];
                    $item->assigned_to = is_array($item->assigned_to) ? $item->assigned_to : [$item->assigned_to];
                    $item->assigned_to = array_filter(array_map('intval', $item->assigned_to));

                    foreach ($item->assigned_to as $assignee) {
                        if (isset($users[$assignee])) {
                            $assignees[$assignee] = &$users[$assignee];
                        }
                    }

                    $reminders = array_filter(array_map('json_decode', (array)$item->reminders));

                    for ($i = 0; $reminders && $i < count($reminders); $i++) {

                        $reminder = $reminders[$i];

                        // Check if the notification was already sent.
                        if ((bool)$reminder->sent) {
                            continue;
                        }

                        $forcedDate = 0;

                        switch ((int)$reminder->reminder) {
                            case 1:
                                $daysInterval = 1;
                                break;
                            case 2:
                                $daysInterval = 2;
                                break;
                            case 3:
                                $daysInterval = 3;
                                break;
                            case 4:
                                $daysInterval = 7;
                                break;
                            case 5:
                                $daysInterval = 14;
                                break;
                            default:
                                $forcedDate = (int)$reminder->reminder;
                                $daysInterval = 0;
                                break;
                        }

                        // Check if the notification is past due already.
                        if ($currentTimestamp >= $endDate) {
                            foreach ($assignees as $assigneeId => $assignee) {
                                if ( ! isset($assignee->reminders[$project->id])) {
                                    $assignee->reminders[$project->id] = [
                                        'milestones' => [],
                                        'tasks'      => [],
                                        'bugs'       => [],
                                        'past_due'   => [
                                            'milestones' => [],
                                            'tasks'      => [],
                                            'bugs'       => [],
                                            'count'      => 0,
                                        ],
                                    ];

                                    if ( ! isset($itemsIdsCache[$item->id])) {
                                        array_push($assignee->reminders[$project->id]['past_due'][$itemType], $item);
                                        $itemsIdsCache[$item->id] = 1;
                                        $assignee->reminders[$project->id]['past_due']['count']++;
                                    }

                                    $assignees[$assigneeId] = $assignee;
                                }
                            }
                        } elseif (($forcedDate > 0 && $currentTimestamp > $forcedDate) || $currentTimestamp + ($secondsInADay * $daysInterval) >= $endDate) {

                            foreach ($assignees as $assigneeId => $assignee) {
                                if ( ! isset($assignee->reminders[$project->id])) {
                                    $assignee->reminders[$project->id] = [
                                        'milestones' => [],
                                        'tasks'      => [],
                                        'bugs'       => [],
                                        'files'      => [],
                                        'past_due'   => [
                                            'milestones' => [],
                                            'tasks'      => [],
                                            'bugs'       => [],
                                            'count'      => 0,
                                        ],
                                    ];

                                }

                                if ( ! isset($itemsIdsCache[$item->id])) {
                                    $r = json_decode($item->reminders[$i]);
                                    $r->triggered = true;
                                    $item->reminders[$i] = json_encode($r);

                                    array_push($assignee->reminders[$project->id][$itemType], $item);
                                    $itemsIdsCache[$item->id] = 1;
                                }

                                $assignees[$assigneeId] = $assignee;
                            }
                        }
                    }
                }
            }
        }

        foreach ($users as $user_id => $user) {
            if (count($user->reminders) === 0) {
                continue;
            }

            $html               = [];
            $itemsIds           = [];
            $hasAnyUpcomingDate = false;

            foreach ($user->reminders as $project_id => $reminders) {
                // Check if user doesn't want to receive notifications for the project.
                $meta = (array)get_post_meta($project_id, '_upstream_project_disable_notifications_' . $user_id);
                if (count($meta) > 0 && $meta[0] === 'on') {
                    continue;
                }

                if (empty($html)) {
                    $html = [
                        '<p>' . sprintf(_x('Hello %s', '%s: User name', 'upstream-email-notifications'),
                            $user->name) . ',</p>',
                        '<p>' . __('This email is to remind you about the following due dates:',
                            'upstream-email-notifications') . '</p>',
                    ];
                }

                $project = $projectsCache[$project_id];

                $html[] = '<fieldset>';

                $html[] = '<legend><h1><a href="' . $project->url . '">' . $project->title . '</a></h1></legend>';

                foreach ($itemsType as $itemType) {
                    if (count($reminders[$itemType]) > 0) {
                        $hasAnyUpcomingDate = true;

                        $html[] = '<h3>' . $itemsLabelsMap[$itemType] . '</h3>';
                        $html[] = '<ul>';
                        foreach ($reminders[$itemType] as $item) {
                            if ($itemType === 'milestones') {
                                $itemTitle   = $item->milestone;
                                $itemEndDate = $item->end_date;
                            } else {
                                $itemTitle   = $item->title;
                                $itemEndDate = self::__fix_timestamp($itemType, isset($item->end_date) ? $item->end_date : $item->due_date);
                            }

                            $html[]     = '<li>' . $itemTitle . ' (<span style="color: #E67E22">' . upstream_format_date($itemEndDate) . '</span>)</li>';
                            $itemsIds[] = $item->id;
                        }
                        $html[] = '</ul>';
                    }
                }

                if ($reminders['past_due']['count'] > 0) {
                    if ($hasAnyUpcomingDate) {
                        $html[] = '<hr />';
                    }

                    $html[] = '<h2>' . __('Past Due Dates', 'upstream-email-notifications') . '</h2>';
                    foreach ($itemsType as $itemType) {
                        if (count($reminders['past_due'][$itemType]) === 0) {
                            continue;
                        }

                        $html[] = '<h3>' . $itemsLabelsMap[$itemType] . '</h3>';
                        $html[] = '<ul>';
                        foreach ($reminders['past_due'][$itemType] as $item) {
                            if ($itemType === 'milestones') {
                                $itemTitle   = $milestones[$item->milestone];
                                $itemEndDate = $item->end_date;
                            } else {
                                $itemTitle   = $item->title;
                                $itemEndDate = self::__fix_timestamp($itemType, isset($item->end_date) ? $item->end_date : $item->due_date);
                            }

                            $html[]     = '<li>' . $itemTitle . ' (<small>' . __('due date',
                                    'upstream') . '</small> <span style="color: #E74C3C;">' . upstream_format_date($itemEndDate) . '</span>)</li>';
                            $itemsIds[] = $item->id;
                        }
                        $html[] = '</ul>';
                    }
                }

                $html[] = '</fieldset>';
            }

            $html = implode('', $html);

            /**
             * Allow to get custom email messages.
             *
             * @param int       $user_id
             * @param \stdClass $user Available attributes: (string) name, (string) email, (array) reminders
             * @param array     $projects
             * @param array     $itemsType
             * @param array     $itemsLabelsMap
             * @param array     $milestones
             *
             * @return string
             */
            $html = apply_filters('upstream_email_notifications_reminder_email_body', $html, $user_id, $user,
                $projectsCache, $itemsType, $itemsLabelsMap, $milestones);

            if ( ! empty($html)) {
                $html      .= '<p>&mdash;</p><p><small>' . __('Please do not reply this message.',
                        'upstream-email-notifications') . '</small></p>';
                $emailBody = $html;

                try {
                    $emailHasBeenSent = Plugin::doSendEmail($adminEmail, $user->email, $user->name, $emailSubject,
                        $emailBody);
                    if ($emailHasBeenSent) {
                        foreach ($user->reminders as $project_id => $reminders) {
                            $project = &$projects[$project_id];

                            foreach ($itemsType as $itemType) {
                                if (count($reminders[$itemType]) > 0) {
                                    foreach ($reminders[$itemType] as $itemIndex => $item) {
                                        foreach ($item->reminders as $reminderIndex => $reminder) {
                                            $reminder = json_decode($reminder);

                                            if (isset($reminder->triggered)) {
                                                $reminder->sent = true;
                                                $reminder->sent_at = $currentTimestamp;
                                                $item->reminders[$reminderIndex] = json_encode($reminder);
                                            }
                                        }

                                        $reminders[$itemType][$itemIndex]              = $item;
                                        $projects[$project_id]->{$itemType}[$item->id] = $item;
                                    }
                                }
                            }

                            if ($reminders['past_due']['count'] > 0) {
                                foreach ($itemsType as $itemType) {
                                    if (count($reminders['past_due'][$itemType]) > 0) {
                                        foreach ($reminders['past_due'][$itemType] as $itemIndex => $item) {
                                            foreach ($item->reminders as $reminderIndex => $reminder) {
                                                $reminder                        = json_decode($reminder);
                                                $reminder->sent                  = true;
                                                $reminder->sent_at               = $currentTimestamp;
                                                $item->reminders[$reminderIndex] = json_encode($reminder);
                                            }

                                            $reminders['past_due'][$itemType][$itemIndex]  = $item;
                                            $projects[$project_id]->{$itemType}[$item->id] = $item;
                                        }
                                    }
                                }
                            }

                            foreach ($itemsType as $itemType) {
                                if (count($project->{$itemType}) > 0) {
                                    array_walk($project->{$itemType}, function (&$item, $itemIndex) {
                                        $item = (array)$item;
                                    });

                                    if ($itemType === 'milestones') {
                                        MilestoneReminders::getInstance()->updateMilestoneRemindersFromJson($item->id, $item->reminders);
                                    } else {
                                        update_post_meta($project->id, '_upstream_project_' . $itemType,
                                            array_values($project->{$itemType}));
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }

    /**
     * Hook that will ensure all data about default reminders will be available on the frontend JS.
     *
     * @param array $data The data coming through the filter.
     *
     * @return  array   $data
     * @since   1.1.3
     * @static
     *
     */
    public static function loadDefaultRemindersOnJS($data)
    {
        $pluginOptions           = Plugin::getOptions();
        $defaultReminders        = [];
        $defaultRemindersOptions = isset($pluginOptions['default_reminders']) ? (array)$pluginOptions['default_reminders'] : [];
        if (count($defaultRemindersOptions) > 0) {
            $remindersLabels = [
                1 => __('A day before', 'upstream-email-notifications'),
                2 => __('Two days before', 'upstream-email-notifications'),
                3 => __('Three days before', 'upstream-email-notifications'),
                4 => __('A week before', 'upstream-email-notifications'),
                5 => __('Two weeks before', 'upstream-email-notifications'),
            ];

            foreach ($defaultRemindersOptions as $defaultReminderType) {
                $defaultReminderType = (int)$defaultReminderType;

                $defaultReminders[$defaultReminderType] = $remindersLabels[$defaultReminderType];
            }
        }

        $data['email-notifications'] = [
            'default_reminders' => $defaultReminders,
        ];

        return $data;
    }

    public static function defineRemindersField($schema)
    {
        if (isset($schema['comments'])) {
            $comments = $schema['comments'];
            unset($schema['comments']);
        }

        $schema['reminders'] = [
            'type'       => 'array',
            'label'      => __('Reminders', 'upstream-email-notifications'),
            'isEditable' => true,
            'isHidden'   => true,
        ];

        if (isset($comments)) {
            $schema['comments'] = $comments;
        }

        return $schema;
    }

    /**
     * @param $row
     *
     * @return array
     */
    public static function addRemindersToMilestone($row)
    {
        $model = MilestoneReminders::getInstance();

        // Check if we have reminders for this milestone.
        $reminders = $model->getMilestoneReminders($row['id']);

        if ( ! empty($reminders)) {
            $remindersRowset = [];

            foreach ($reminders as $reminder) {
                $remindersRowset[] = json_encode($reminder);
            }

            $row['reminders'] = $remindersRowset;
        }

        return $row;
    }
}
