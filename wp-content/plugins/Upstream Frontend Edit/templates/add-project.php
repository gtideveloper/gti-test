<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

$post_id     = upstream_post_id();
$fieldPrefix = '_upstream_project_';

$formActionURL = (isset($_SERVER['QUERY_STRING']) && ! empty($_SERVER['QUERY_STRING'])
        ? $_SERVER['QUERY_STRING'] . '&'
        : "")
                 . 'action=add_task';

$statuses = (array)upstream_get_all_project_statuses();
$members  = (array)upstream_project_users_dropdown();

if ( ! is_clients_disabled()) {
    $clients = (array)upstream_admin_get_all_clients();
}
?>

<div id="modal-project" class="modal fade" aria-hidden="true">
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
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade in active" id="modal-task-data-wrapper">
                        <form id="the_project" method="POST" data-type="project" class="form-horizontal o-modal-form"
                              enctype="multipart/form-data">
                            <div class="row upstream-project-title form_row_title">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label for="<?php echo $fieldPrefix . 'title'; ?>"><?php esc_html_e('Title',
                                            'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                    <input type="text" id="<?php echo $fieldPrefix . 'title'; ?>" name="data[title]"
                                           class="form-control" required
                                        <?php echo upstream_override_access_field(current_user_can('project_title_field'), UPSTREAM_ITEM_TYPE_PROJECT, $post_id, null, 0, 'title', UPSTREAM_PERMISSIONS_ACTION_EDIT) ? '' : ''; ?>>
                                </div>
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                            </div>

                            <div class="row upstream-project-status  form_row_status">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label
                                            for="<?php echo $fieldPrefix . 'status'; ?>"><?php esc_html_e('Status',
                                            'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                    <select id="<?php echo $fieldPrefix . 'status'; ?>"
                                            name="data[status]" class="form-control"
                                            data-placeholder="<?php esc_attr_e('None', 'upstream'); ?>"
                                            <?php echo upstream_override_access_field(current_user_can('project_status_field'), UPSTREAM_ITEM_TYPE_PROJECT, $post_id, null, 0, 'status', UPSTREAM_PERMISSIONS_ACTION_EDIT) ?  '' : ''; ?>>
                                    <option></option>
                                    <?php foreach ($statuses as $statusId => $status): ?>
                                        <?php if (empty($statusId)) {
                                            continue;
                                        } ?>
                                        <option
                                                value="<?php echo $statusId; ?>"><?php echo esc_html($status['name']); ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                            </div>
                            <div id="project_category_section" class="row upstream-project-categories  form_row_categories">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label
                                            for="<?php echo $fieldPrefix . 'categories'; ?>"><?php esc_html_e('Categories',
                                            'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                    <select id="<?php echo $fieldPrefix . 'categories'; ?>"
                                            name="data[categories][]" class="form-control"
                                            data-placeholder="<?php esc_attr_e('None', 'upstream'); ?>" multiple>
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



                            <div class="row upstream-project-owner form_row_owner">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label
                                            for="<?php echo $fieldPrefix . 'owner'; ?>"><?php esc_html_e('Owner',
                                            'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                    <select id="<?php echo $fieldPrefix . 'owner'; ?>"
                                            name="data[owner]" class="form-control"
                                            data-placeholder="<?php esc_attr_e('None',
                                                'upstream'); ?>">
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

                            <?php if ( ! is_clients_disabled()) : ?>
                                <div class="row upstream-project-client  form_row_client">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label
                                                for="<?php echo $fieldPrefix . 'client'; ?>"><?php echo esc_html(upstream_client_label()); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <select id="<?php echo $fieldPrefix . 'client'; ?>"
                                                name="data[client]" class="form-control"
                                                data-placeholder="<?php esc_attr_e('None', 'upstream'); ?>">
                                            <option></option>
                                            <?php foreach ($clients as $userId => $userName): ?>
                                                <?php if (empty($userId)) {
                                                    continue;
                                                } ?>
                                                <option value="<?php echo $userId; ?>"><?php echo esc_html($userName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>

                                <div class="row upstream-project-client-users  form_row_client_users">
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                    <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                        <label
                                                for="<?php echo $fieldPrefix . 'client_users'; ?>"><?php echo esc_html(upstream_client_label() . ' ' . __('Users',
                                                    'upstream')); ?></label>
                                    </div>
                                    <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                        <select id="<?php echo $fieldPrefix . 'client_users'; ?>"
                                                name="data[client_users][]" class="form-control"
                                                data-placeholder="<?php esc_attr_e('None', 'upstream'); ?>"
                                                multiple
                                                data-nonce="<?php echo wp_create_nonce('upstream.frontend-edit:fetch_client_users'); ?>">
                                            <option></option>
                                        </select>
                                    </div>
                                    <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                </div>
                            <?php endif; ?>

                            <div class="row upstream-project-start-date  form_row_start">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label for="<?php echo $fieldPrefix . 'start'; ?>"><?php esc_html_e('Start Date',
                                            'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                    <input type="text" id="<?php echo $fieldPrefix . 'start'; ?>"
                                           name="data[start]" class="form-control o-datepicker"
                                           placeholder="<?php esc_attr_e('None', 'upstream'); ?>" data-elt="end"
                                           autocomplete="off">
                                    <input type="hidden" id="<?php echo $fieldPrefix . 'start_timestamp'; ?>"
                                           data-name="start">
                                </div>
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                            </div>

                            <div class="row upstream-project-end-date  form_row_end">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label for="<?php echo $fieldPrefix . 'end'; ?>"><?php esc_html_e('End Date',
                                            'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                    <input type="text" id="<?php echo $fieldPrefix . 'end'; ?>"
                                           name="data[end]" class="form-control o-datepicker"
                                           placeholder="<?php esc_attr_e('None', 'upstream'); ?>" data-egt="start"
                                           autocomplete="off">
                                    <input type="hidden" id="<?php echo $fieldPrefix . 'end_timestamp'; ?>"
                                           data-name="end">
                                </div>
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                            </div>

                            <div class="row upstream-project-description  form_row_description">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label for=""><?php esc_html_e('Description', 'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                  <?php
                                  wp_editor('', $fieldPrefix . 'description', [
                                      'media_buttons' => true,
                                      'textarea_rows' => 5,
                                      'textarea_name' => 'data[description]',
                                  ]);
                                  ?>
                                </div>
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                            </div>

                            <?php do_action('upstream.frontend-edit:renderAfter.project.items.end_dates', 'projects'); ?>

                            <?php do_action('upstream.frontend-edit:renderAdditionalFields', 'project'); ?>

                            <input type="hidden" id="type" name="type" value="project"/>
                            <input type="hidden" id="post_id" name="post_id" value="<?php echo $post_id; ?>"/>

                            <?php wp_nonce_field('upstream_security', 'upstream-nonce'); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row" data-visible-when="edit">
                    <div class="col-md-3 col-sm-3 col-xs-12 text-left">
                        <button type="button" class="btn btn-danger" data-action="delete"
                                data-nonce="<?php echo wp_create_nonce('my_delete_post_nonce') ?>"
                                id="delete_project"><?php esc_html_e('Delete',
                                'upstream'); ?></button>
                    </div>
                    <div class="col-md-3 col-sm-3 hidden-xs"></div>
                    <div class="col-md-6 col-sm-6 col-xs-12 text-right">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Cancel',
                                'upstream-frontend-edit'); ?></button>
                        <button type="submit" class="btn btn-primary" form="the_project"
                                data-editor_id="<?php echo $fieldPrefix . 'description'; ?>"
                                data-nonce="<?php echo wp_create_nonce('upstream.frontend-edit:save_project'); ?>"
                        >
                            <i class="fa fa-save"></i>
                            <?php esc_html_e('Save', 'upstream-frontend-edit'); ?>
                        </button>
                    </div>
                </div>
                <div class="row" data-visible-when="add">
                    <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Cancel',
                                'upstream-frontend-edit'); ?></button>
                        <button type="submit" class="btn btn-primary" form="the_project"
                                data-editor_id="<?php echo $fieldPrefix . 'description'; ?>"
                                data-nonce="<?php echo wp_create_nonce('upstream.frontend-edit:save_project'); ?>"
                        >
                            <i class="fa fa-save"></i>
                            <?php esc_html_e('Save', 'upstream-frontend-edit'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    jQuery(function ($) {
        $.fn.modal.Constructor.prototype.enforceFocus = $.noop;
    });
</script>
