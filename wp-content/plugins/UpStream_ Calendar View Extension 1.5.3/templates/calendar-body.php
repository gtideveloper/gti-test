<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
  exit;
}
?>

<div class="c-calendar__body">
  <?php do_action('upstream.calendar-view:renderCalendar'); ?>
  <div class="c-curtain">
    <div class="c-curtain__overlay"></div>
    <div class="c-curtain__msg">
      <i class="fa fa-spinner fa-spin"></i> <?php esc_html_e('Loading...', 'upstream-calendar-view'); ?>
    </div>
  </div>
</div>
