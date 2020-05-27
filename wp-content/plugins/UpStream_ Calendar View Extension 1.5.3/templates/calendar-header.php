<?php

use UpStream\Plugins\CalendarView\Calendar;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
  exit;
}

$hideMilestones = Calendar::isCalendarOverviewPage() ? upstream_disable_milestones() : upstream_disable_milestones() || upstream_are_milestones_disabled();
$hideTasks      = Calendar::isCalendarOverviewPage() ? upstream_disable_tasks() : upstream_disable_tasks() || upstream_are_tasks_disabled();
$hideBugs       = Calendar::isCalendarOverviewPage() ? upstream_disable_bugs() : upstream_disable_bugs() || upstream_are_bugs_disabled();

$projectTimeframesOnly = isset($projectTimeframesOnly) ? (bool)$projectTimeframesOnly : false;
?>

<div class="c-calendar__header">
  <form class="form-inline" onsubmit="void(0);">
    <?php if ( ! $projectTimeframesOnly): ?>
      <div class="form-group">
        <select class="form-control input-sm"
                title="<?php esc_html_e('Filter items by the assigned user', 'upstream-calendar-view'); ?>"
                data-toggle="tooltip" data-delay="500" data-filter="user"
                data-current_user="<?php echo get_current_user_id(); ?>">
          <option value="all"><?php esc_html_e('Any user', 'upstream-calendar-view'); ?></option>
          <option value="me"><?php esc_html_e('Assigned to me', 'upstream'); ?></option>
          <option value="none"><?php esc_html_e('No assigned user', 'upstream-calendar-view'); ?></option>
          <?php if (isset($projectMembers) && count($projectMembers) > 0): ?>
            <optgroup label="<?php printf(__('%s Members', 'upstream'), esc_html(upstream_project_label())); ?>">
              <?php foreach ($projectMembers as $user): ?>
                <?php if ($user->id === get_current_user_id()) {
                  continue;
                } ?>
                <option value="<?php echo $user->id; ?>"><?php echo esc_html($user->name); ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
        </select>
      </div>
    <?php endif; ?>
    <?php if (( ! $hideMilestones || ! $hideTasks || ! $hideBugs) && ! $projectTimeframesOnly): ?>
      <div class="form-group">
        <select class="form-control input-sm"
                title="<?php esc_html_e('Filter items by their type', 'upstream-calendar-view'); ?>" data-toggle="tooltip"
                data-delay="500" data-filter="type">
          <option value="all"><?php esc_html_e('All types', 'upstream-calendar-view'); ?></option>
          <?php if ( ! $hideMilestones): ?>
            <option value="milestone"><?php echo esc_html(upstream_milestone_label_plural()); ?></option>
          <?php endif; ?>
          <?php if ( ! $hideTasks): ?>
            <option value="task"><?php echo esc_html(upstream_task_label_plural()); ?></option>
          <?php endif; ?>
          <?php if ( ! $hideBugs): ?>
            <option value="bug"><?php echo esc_html(upstream_bug_label_plural()); ?></option>
          <?php endif; ?>
        </select>
      </div>
    <?php endif; ?>
    <div class="form-group">
      <select class="form-control input-sm" title="<?php esc_html_e('Select how many weeks will be displayed on the calendar',
        'upstream-calendar-view'); ?>" data-toggle="tooltip" data-delay="500" data-filter="weeks"
              data-default="<?php echo (int)$options->weeks_count; ?>">
        <?php for ($wI = 1; $wI <= 12; $wI++): ?>
          <option
            value="<?php echo $wI; ?>"<?php echo ((int)$options->weeks_count) === $wI ? ' selected' : ''; ?>><?php echo esc_html(sprintf(_n('%s week',
              '%s weeks', $wI, 'upstream-calendar-view'), $wI)); ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="form-group">
      <button type="button" class="btn btn-default btn-sm"
              title="<?php esc_attr_e('Reset all filters to their defaults', 'upstream-calendar-view'); ?>"
              data-toggle="tooltip" data-delay="500" data-action="reset_filters" style="margin: 0;">
        <i class="fa fa-eraser"></i> <?php esc_html_e('Reset', 'upstream-calendar-view'); ?>
      </button>
    </div>
    <div>
      <div class="form-group">
        <select class="form-control input-sm"
                title="<?php esc_attr_e('Jump to a certain month', 'upstream-calendar-view'); ?>" data-toggle="tooltip"
                data-delay="500" data-filter="month">
          <option value="0" selected><?php esc_html_e('Month'); ?></option>
          <option value="1"><?php esc_html_e('January'); ?></option>
          <option value="2"><?php esc_html_e('February'); ?></option>
          <option value="3"><?php esc_html_e('March'); ?></option>
          <option value="4"><?php esc_html_e('April'); ?></option>
          <option value="5"><?php esc_html_e('May'); ?></option>
          <option value="6"><?php esc_html_e('June'); ?></option>
          <option value="7"><?php esc_html_e('July'); ?></option>
          <option value="8"><?php esc_html_e('August'); ?></option>
          <option value="9"><?php esc_html_e('September'); ?></option>
          <option value="10"><?php esc_html_e('October'); ?></option>
          <option value="11"><?php esc_html_e('November'); ?></option>
          <option value="12"><?php esc_html_e('December'); ?></option>
        </select>
      </div>
      <div class="btn-group" role="group" aria-label="...">
        <button type="button" class="btn btn-default btn-sm" data-toggle="tooltip" data-delay="500"
                title="<?php esc_attr_e('Back 1 month', 'upstream-calendar-view'); ?>" data-target="calendar"
                data-direction="past" data-amount="month">
          <i class="fa fa-angle-double-left"></i>
        </button>
        <button type="button" class="btn btn-default btn-sm" data-target="calendar" data-direction="past"
                data-amount="week" data-toggle="tooltip" data-delay="500"
                title="<?php esc_attr_e('Back 1 week', 'upstream-calendar-view'); ?>">
          <i class="fa fa-angle-left"></i>
        </button>
        <button type="button" class="btn btn-default btn-sm" data-target="calendar" data-direction="today"
                data-toggle="tooltip" data-delay="500"
                title="<?php echo esc_attr(sprintf(__('Today is %s', 'upstream-calendar-view'),
                  date_i18n(get_option('date_format') . ' ' . get_option('time_format')), time())); ?>">
          <?php esc_html_e('Today', 'upstream-calendar-view'); ?>
        </button>
        <button type="button" class="btn btn-default btn-sm" data-target="calendar" data-direction="future"
                data-amount="week" data-toggle="tooltip" data-delay="500"
                title="<?php esc_html_e('Forward 1 week', 'upstream-calendar-view'); ?>">
          <i class="fa fa-angle-right"></i>
        </button>
        <button type="button" class="btn btn-default btn-sm" data-target="calendar" data-direction="future"
                data-amount="month" data-toggle="tooltip" data-delay="500"
                title="<?php esc_html_e('Forward 1 month', 'upstream-calendar-view'); ?>">
          <i class="fa fa-angle-double-right"></i>
        </button>
      </div>
    </div>
  </form>
</div>
