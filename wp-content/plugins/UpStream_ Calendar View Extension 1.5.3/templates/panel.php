<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
  exit;
}

$collapseBox = \UpStream\Frontend\getSectionCollapseState('calendar') === 'closed';
?>

<div class="row" id="project-section-calendar">
  <div id="calendar" class="col-md-12">
    <div class="x_panel" data-section="calendar">
      <div class="x_title">
        <h2>
          <i class="fa fa-bars sortable_handler"></i>
          <i class="fa fa-calendar"></i> <?php esc_html_e('Calendar View', 'upstream-calendar-view'); ?>
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
        <?php include 'calendar.php'; ?>
      </div>
    </div>
  </div>
</div>
