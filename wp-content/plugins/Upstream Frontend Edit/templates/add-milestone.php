<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

if (
    (
        function_exists('upstream_disable_milestones') &&
        upstream_disable_milestones()
    ) ||
    (
        function_exists('upstream_are_milestones_disabled') &&
        upstream_are_milestones_disabled()
    )
) {
    return;
}

$post_id          = get_the_ID();
$itemTypeSingular = 'milestone';
$itemTypePlural   = 'milestones';
$fieldPrefix      = '_upstream_project_' . $itemTypeSingular . '_';

$formActionURL = (isset($_SERVER['QUERY_STRING']) && ! empty($_SERVER['QUERY_STRING'])
        ? $_SERVER['QUERY_STRING'] . '&'
        : "")
                 . 'action=add_milestone';

$allowComments = upstreamAreCommentsEnabledOnMilestones();

$milestones = (array)upstream_admin_get_options_milestones();
$members    = (array)upstream_project_users_dropdown();

if ( ! upstream_disable_milestone_categories()) {
    $categories = upstream_admin_get_milestone_categories();
}
?>

<div id="modal-milestone" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-type="milestone">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-flag"></i> <span></span>
                </h4>
            </div>
            <div class="modal-body">
                <?php if ($allowComments): ?>
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#modal-milestone-data-wrapper" aria-controls="modal-milestone-data-wrapper"
                               role="tab" data-toggle="tab"><?php esc_html_e('Data', 'upstream'); ?></a>
                        </li>
                        <li role="presentation">
                            <a href="#modal-milestone-comments-wrapper" aria-controls="modal-milestone-comments-wrapper"
                               role="tab" data-toggle="tab"
                               data-nonce="<?php echo wp_create_nonce('upstream:project.' . $itemTypePlural . '.fetch_comments'); ?>">
                                <?php _e('Comments'); ?>
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade in active" id="modal-milestone-data-wrapper">
                        <form id="the_milestone" method="POST" data-type="milestone" class="o-modal-form"
                              enctype="multipart/form-data">
                            <?php if (upstream_permissions('milestone_milestone_field')): ?>
                                <div class="row upstream-milestone-value form_row_milestone">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label
                                                for="<?php echo $fieldPrefix . 'milestone'; ?>"><?php echo esc_html(upstream_milestone_label()); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <input type="text" id="<?php echo $fieldPrefix . 'milestone'; ?>"
                                               name="data[milestone]" class="form-control"
                                               autocomplete="on"/>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php if ( ! upstream_disable_milestone_categories()) : ?>
                                <div class="row upstream-milestone-categories form_row_categories">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label
                                                for="<?php echo $fieldPrefix . 'categories'; ?>"><?php echo esc_html(upstream_milestone_category_label_plural()); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <select id="<?php echo $fieldPrefix . 'categories'; ?>"
                                                name="data[categories][]" class="form-control"
                                                data-placeholder="<?php esc_attr_e('None', 'upstream'); ?>" multiple>
                                            <option></option>
                                            <?php foreach ($categories as $id => $name): ?>
                                                <option value="<?php echo $id; ?>"><?php echo esc_html($name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <?php if (upstream_permissions('milestone_assigned_to_field')): ?>
                                <div class="row upstream-milestone-assigned-to form_row_assigned_to">
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

                            <?php if (upstream_permissions('milestone_start_date_field')): ?>
                                <div class="row upstream-milestone-start-date form_row_start_date">
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

                            <?php if (upstream_permissions('milestone_end_date_field')): ?>
                                <div class="row upstream-milestone-end-date form_row_end_date">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label for="<?php echo $fieldPrefix . 'end_date'; ?>"><?php esc_html_e('End Date',
                                                'upstream'); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <input type="text" id="<?php echo $fieldPrefix . 'end_date'; ?>"
                                               name="data[end_date]" class="form-control o-datepicker"
                                               placeholder="<?php esc_attr_e('None', 'upstream'); ?>" data-egt="start_date"
                                               autocomplete="off">
                                        <input type="hidden" id="<?php echo $fieldPrefix . 'end_date_timestamp'; ?>"
                                               data-name="end_date">
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <div class="row upstream-milestone-color form_row_color">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label for="<?php echo $fieldPrefix . 'color'; ?>"><?php esc_html_e('Color',
                                            'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                    <input type="text" id="<?php echo $fieldPrefix . 'color'; ?>"
                                           name="data[color]" class="form-control colorpicker"
                                           data-egt="color"
                                           placeholder="<?php echo \UpStream\Milestone::DEFAULT_COLOR; ?>"
                                           autocomplete="off">
                                </div>
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                            </div>

                            <?php do_action('upstream.frontend-edit:renderAfter.project.items.end_dates',
                                'milestones'); ?>

                            <?php if (upstream_permissions('milestone_notes_field')): ?>
                                <div class="row upstream-milestone-notes form_row_notes">
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

                            <?php do_action('upstream.frontend-edit:renderAdditionalFields', 'milestone'); ?>

                            <input type="hidden" id="type" name="type" value="milestones"/>
                            <input type="hidden" id="post_id" name="post_id" value="<?php echo $post_id; ?>"/>

                            <?php wp_nonce_field('upstream_security', 'upstream-nonce'); ?>
                        </form>
                    </div>
                    <?php if ($allowComments): ?>
                        <div role="tabpanel" class="tab-pane fade" id="modal-milestone-comments-wrapper"
                             data-wrapper-type="comments">
                            <?php if (upstream_permissions('publish_project_discussion')):
                                $editor_id = 'upstream_milestones_comment_editor';
                                wp_editor("", $editor_id, [
                                    'media_buttons' => true,
                                    'textarea_rows' => 5,
                                    'textarea_name' => $editor_id,
                                ]);
                                ?>
                                <div class="comments-controls-btns">
                                    <button type="button" class="btn btn-success" data-action="comments.add_comment"
                                            data-editor_id="<?php echo $editor_id; ?>"
                                            data-nonce="<?php echo wp_create_nonce('upstream:project.milestones.add_comment'); ?>"
                                            data-item_type="milestone"
                                            data-item_title=""><?php esc_html_e('Add Comment', 'upstream'); ?></button>
                                </div>
                            <?php endif; ?>
                            <div class="c-comments" data-type="milestone"
                                 data-nonce="<?php echo wp_create_nonce('upstream:project.milestones.fetch_comments'); ?>"></div>
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
                            <?php esc_html_e('Save', 'upstream-frontend-edit'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
