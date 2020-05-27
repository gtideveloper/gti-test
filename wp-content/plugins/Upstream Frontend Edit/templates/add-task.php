<?php
// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

$post_id = upstream_post_id();
$itemTypeSingular = 'task';
$itemTypePlural = 'tasks';
$fieldPrefix = '_upstream_project_' . $itemTypeSingular . '_';

$formActionURL = (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])
        ? $_SERVER['QUERY_STRING'] . '&'
        : "")
    . 'action=add_task';

$allowComments = upstreamAreCommentsEnabledOnTasks();
$members = (array)upstream_project_users_dropdown();

$areMilestonesEnabled = (function_exists('upstream_disable_milestones') && !upstream_disable_milestones())
    && (function_exists('upstream_are_milestones_disabled') && !upstream_are_milestones_disabled($post_id));

if ($areMilestonesEnabled) {
    $projectMilestones = (array)upstream_project_milestones($post_id);
}

$statuses = (array)upstream_admin_get_task_statuses();
$progresses = (array)upstream_get_percentages_for_dropdown();
?>

<div id="modal-task" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-type="task">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-wrench"></i> <span></span>
                </h4>
            </div>
            <div class="modal-body">
                <?php if ($allowComments): ?>
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#modal-task-data-wrapper" aria-controls="modal-task-data-wrapper" role="tab"
                               data-toggle="tab"><?php esc_html_e('Data', 'upstream'); ?></a>
                        </li>
                        <li role="presentation">
                            <a href="#modal-task-comments-wrapper" aria-controls="modal-task-comments-wrapper"
                               role="tab" data-toggle="tab"
                               data-nonce="<?php echo wp_create_nonce('upstream:project.' . $itemTypePlural . '.fetch_comments'); ?>">
                                <?php esc_html_e('Comments'); ?>
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade in active" id="modal-task-data-wrapper">
                        <form id="the_task" method="POST" data-type="task" class="form-horizontal o-modal-form"
                              enctype="multipart/form-data">
                            <?php if (upstream_permissions('task_title_field')): ?>
                                <div class="row upstream-task-title form_row_title">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label for="<?php echo $fieldPrefix . 'title'; ?>"><?php esc_html_e('Title',
                                                'upstream'); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <input type="text" id="<?php echo $fieldPrefix . 'title'; ?>" name="data[title]"
                                               class="form-control" required>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php if (upstream_permissions('task_assigned_to_field')): ?>
                                <div class="row upstream-task-assigned-to form_row_assigned_to">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label
                                                for="<?php echo $fieldPrefix . 'assigned_to'; ?>"><?php esc_html_e('Assigned To',
                                                'upstream'); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <select id="<?php echo $fieldPrefix . 'assigned_to'; ?>"
                                                name="data[assigned_to][]" class="form-control"
                                                data-placeholder="<?php esc_html_e('None', 'upstream'); ?>" multiple>
                                            <option></option>
                                            <?php foreach ($members as $userId => $userName): ?>
                                                <?php if (empty($userId)) {
                                                    continue;
                                                } ?>
                                                <option value="<?php echo $userId; ?>"><?php echo esc_html($userName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php if (upstream_permissions('task_status_field')): ?>
                                <div class="row upstream-task-status form_row_status">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label for="<?php echo $fieldPrefix . 'status'; ?>"><?php esc_html_e('Status',
                                                'upstream'); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <select id="<?php echo $fieldPrefix . 'status'; ?>" name="data[status]"
                                                class="form-control"
                                                data-placeholder="<?php esc_html_e('None', 'upstream'); ?>">
                                            <option></option>
                                            <?php foreach ($statuses as $statusId => $statusName): ?>
                                                <?php if (empty($statusId)) {
                                                    continue;
                                                } ?>
                                                <option
                                                        value="<?php echo $statusId; ?>"><?php echo esc_html($statusName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php if (upstream_permissions('task_progress_field')): ?>
                                <div class="row upstream-task-progress form_row_progress">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label for="<?php echo $fieldPrefix . 'progress'; ?>"><?php esc_html_e('Progress',
                                                'upstream'); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <select id="<?php echo $fieldPrefix . 'progress'; ?>" name="data[progress]"
                                                class="form-control" data-placeholder="0%">
                                            <option></option>
                                            <?php foreach ($progresses as $progressValue => $progressName): ?>
                                                <option
                                                        value="<?php echo $progressValue; ?>"><?php echo esc_html($progressName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php if ($areMilestonesEnabled && upstream_permissions('task_milestone_field')): ?>
                                <div class="row upstream-task-milestone form_row_milestone">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label
                                                for="<?php echo $fieldPrefix . 'milestone'; ?>"><?php echo esc_html(upstream_milestone_label()); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <select id="<?php echo $fieldPrefix . 'milestone'; ?>" name="data[milestone]"
                                                class="form-control"
                                                data-placeholder="<?php esc_html_e('None', 'upstream'); ?>">
                                            <option></option>
                                            <?php foreach ($projectMilestones as $milestone): ?>
                                                <option
                                                        value="<?php echo $milestone['id']; ?>"><?php echo esc_html($milestone['milestone']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php if (upstream_permissions('task_start_date_field')): ?>
                                <div class="row upstream-task-start-date form_row_start_date">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label for="<?php echo $fieldPrefix . 'start_date'; ?>"><?php esc_html_e('Start Date',
                                                'upstream'); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <input type="text" id="<?php echo $fieldPrefix . 'start_date'; ?>"
                                               name="data[start_date]" class="form-control o-datepicker"
                                               placeholder="<?php esc_html_e('None', 'upstream'); ?>" data-elt="end_date"
                                               autocomplete="off">
                                        <input type="hidden" id="<?php echo $fieldPrefix . 'start_date_timestamp'; ?>"
                                               data-name="start_date">
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php if (upstream_permissions('task_end_date_field')): ?>
                                <div class="row upstream-task-end-date form_row_end_date">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label for="<?php echo $fieldPrefix . 'end_date'; ?>"><?php esc_html_e('End Date',
                                                'upstream'); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <input type="text" id="<?php echo $fieldPrefix . 'end_date'; ?>"
                                               name="data[end_date]" class="form-control o-datepicker"
                                               placeholder="<?php esc_html_e('None', 'upstream'); ?>" data-egt="start_date"
                                               autocomplete="off">
                                        <input type="hidden" id="<?php echo $fieldPrefix . 'end_date_timestamp'; ?>"
                                               data-name="end_date">
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php do_action('upstream.frontend-edit:renderAfter.project.items.end_dates', 'tasks'); ?>

                            <?php if (upstream_permissions('task_notes_field')): ?>
                                <div class="row upstream-task-notes form_row_notes">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label for=""><?php esc_html_e('Notes', 'upstream'); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <?php
                                        wp_editor('', $fieldPrefix . 'notes', [
                                            'media_buttons' => true,
                                            'textarea_rows' => 5,
                                            'textarea_name' => 'data[notes]',
                                        ]);
                                        ?>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php do_action('upstream.frontend-edit:renderAdditionalFields', 'task'); ?>

                            <input type="hidden" id="type" name="type" value="tasks"/>
                            <input type="hidden" id="post_id" name="post_id" value="<?php echo $post_id; ?>"/>

                            <?php wp_nonce_field('upstream_security', 'upstream-nonce'); ?>
                        </form>
                    </div>
                    <?php if ($allowComments): ?>
                        <div role="tabpanel" class="tab-pane fade" id="modal-task-comments-wrapper"
                             data-wrapper-type="comments">
                            <?php if (upstream_permissions('publish_project_discussion')):
                                $editor_id = 'upstream_tasks_comment_editor';
                                wp_editor("", $editor_id, [
                                    'media_buttons' => true,
                                    'textarea_rows' => 5,
                                    'textarea_name' => $editor_id,
                                ]);
                                ?>
                                <div class="comments-controls-btns">
                                    <button type="button" class="btn btn-success" data-action="comments.add_comment"
                                            data-editor_id="<?php echo $editor_id; ?>"
                                            data-nonce="<?php echo wp_create_nonce('upstream:project.tasks.add_comment'); ?>"
                                            data-item_type="task"
                                            data-item_title=""><?php esc_html_e('Add Comment', 'upstream'); ?></button>
                                </div>
                            <?php endif; ?>
                            <div class="c-comments" data-type="task"
                                 data-nonce="<?php echo wp_create_nonce('upstream:project.tasks.fetch_comments'); ?>"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row" data-visible-when="edit">
                    <div class="col-md-3 col-sm-3 col-xs-12 text-left">
                        <button type="button" class="btn btn-danger" data-action="delete"
                                data-nonce="<?php echo wp_create_nonce('upstream.frontend-edit:delete_item'); ?>"><?php esc_html_e('Delete',
                                'upstream'); ?></button>
                    </div>
                    <div class="col-md-3 col-sm-3 hidden-xs"></div>
                    <div class="col-md-6 col-sm-6 col-xs-12 text-right">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Cancel',
                                'upstream-frontend-edit'); ?></button>
                        <button type="submit" class="btn btn-primary" form="the_<?php echo $itemTypeSingular; ?>">
                            <i class="fa fa-save"></i>
                            <?php _e('Save', 'upstream-frontend-edit'); ?>
                        </button>
                    </div>
                </div>
                <div class="row" data-visible-when="add">
                    <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Cancel',
                                'upstream-frontend-edit'); ?></button>
                        <button type="submit" class="btn btn-primary" form="the_<?php echo $itemTypeSingular; ?>">
                            <i class="fa fa-save"></i>
                            <?php _e('Save', 'upstream-frontend-edit'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
