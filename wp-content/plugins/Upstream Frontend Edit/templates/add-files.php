<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

$post_id          = upstream_post_id();
$itemTypeSingular = 'file';
$itemTypePlural   = 'files';
$fieldPrefix      = '_upstream_project_' . $itemTypeSingular . '_';

$formActionURL = (isset($_SERVER['QUERY_STRING']) && ! empty($_SERVER['QUERY_STRING'])
        ? $_SERVER['QUERY_STRING'] . '&'
        : "")
                 . 'action=add_' . $itemTypeSingular;

$allowComments = upstreamAreCommentsEnabledOnFiles();
$members       = (array)upstream_project_users_dropdown();
?>

<div id="modal-<?php echo $itemTypeSingular; ?>" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"
     data-type="<?php echo $itemTypeSingular; ?>">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-file"></i> <span></span>
                </h4>
            </div>
            <div class="modal-body">
                <?php if ($allowComments): ?>
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#modal-<?php echo $itemTypeSingular; ?>-data-wrapper"
                               aria-controls="modal-<?php echo $itemTypeSingular; ?>-data-wrapper" role="tab"
                               data-toggle="tab"><?php esc_html_e('Data', 'upstream'); ?></a>
                        </li>
                        <li role="presentation">
                            <a href="#modal-<?php echo $itemTypeSingular; ?>-comments-wrapper"
                               aria-controls="modal-<?php echo $itemTypeSingular; ?>-comments-wrapper" role="tab"
                               data-toggle="tab"
                               data-nonce="<?php echo wp_create_nonce('upstream:project.' . $itemTypePlural . '.fetch_comments'); ?>">
                                <?php esc_html_e('Comments'); ?>
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade in active"
                         id="modal-<?php echo $itemTypeSingular; ?>-data-wrapper">
                        <form id="the_<?php echo $itemTypeSingular; ?>" method="POST"
                              data-type="<?php echo $itemTypeSingular; ?>" class="o-modal-form"
                              enctype="multipart/form-data">
                            <div class="row upstream-file-title form_row_title">
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
                            <div class="row upstream-file-assigned-to form_row_assigned_to">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label for="<?php echo $fieldPrefix . 'assigned_to'; ?>"><?php esc_html_e('Assigned To',
                                            'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                    <select id="<?php echo $fieldPrefix . 'assigned_to'; ?>" name="data[assigned_to][]"
                                            class="form-control" data-placeholder="<?php esc_html_e('None', 'upstream'); ?>"
                                            multiple>
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
                            <div class="row upstream-file-description form_row_description">
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
                            <div class="row upstream-file-file form_row_file">
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                                <div class="col-xs-12 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <label for="<?php echo $fieldPrefix . 'file'; ?>"><?php esc_html_e('File',
                                            'upstream'); ?></label>
                                </div>
                                <div class="col-xs-12 col-sm-9 col-md-8 col-lg-8">
                                    <button type="button" class="btn btn-default btn-xs o-media-library-btn"
                                            data-title="<?php esc_attr_e('File', 'upstream'); ?>" data-name="file">
                                        <i class="fa fa-upload"></i>
                                        <?php esc_html_e('Add or Upload File', 'cmb2'); ?>
                                    </button>
                                    <div class="file-preview" style="display: none;">
                                        <div></div>
                                    </div>
                                </div>
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                            </div>

                            <?php do_action('upstream.frontend-edit:renderAdditionalFields', 'file'); ?>

                            <input type="hidden" id="post_id" name="post_id" value="<?php echo $post_id; ?>"/>
                            <input type="hidden" id="type" name="type" value="<?php echo $itemTypePlural; ?>"/>
                            <?php wp_nonce_field('upstream_security', 'upstream-nonce'); ?>
                            <?php wp_nonce_field('upload-file', 'upstream-' . $itemTypePlural . '-nonce'); ?>
                        </form>
                    </div>
                    <?php if ($allowComments): ?>
                        <div role="tabpanel" class="tab-pane fade"
                             id="modal-<?php echo $itemTypeSingular; ?>-comments-wrapper" data-wrapper-type="comments">
                            <?php if (upstream_permissions('publish_project_discussion')):
                                $editor_id = 'upstream_' . $itemTypePlural . '_comment_editor';
                                wp_editor("", $editor_id, [
                                    'media_buttons' => true,
                                    'textarea_rows' => 5,
                                    'textarea_name' => $editor_id,
                                ]);
                                ?>
                                <div class="comments-controls-btns">
                                    <button type="button" class="btn btn-success" data-action="comments.add_comment"
                                            data-editor_id="<?php echo $editor_id; ?>"
                                            data-nonce="<?php echo wp_create_nonce('upstream:project.' . $itemTypePlural . '.add_comment'); ?>"
                                            data-item_type="<?php echo $itemTypeSingular; ?>"
                                            data-item_title=""><?php esc_html_e('Add Comment', 'upstream'); ?></button>
                                </div>
                            <?php endif; ?>
                            <div class="c-comments" data-type="<?php echo $itemTypeSingular; ?>"
                                 data-nonce="<?php echo wp_create_nonce('upstream:project.' . $itemTypePlural . '.fetch_comments'); ?>"></div>
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
                            <?php esc_html_e('Save', 'upstream-frontend-edit'); ?>
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
