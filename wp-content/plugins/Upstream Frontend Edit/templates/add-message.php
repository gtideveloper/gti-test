<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

$post_id          = upstream_post_id();
$itemTypeSingular = 'discussion';
$itemTypePlural   = 'discussions';
$fieldPrefix      = '_upstream_project_' . $itemTypeSingular . '_';
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
                    <i class="fa fa-comments"></i> <span><?php esc_html_e('New Message', 'upstream'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade in active"
                         id="modal-<?php echo $itemTypeSingular; ?>-data-wrapper">
                        <form name="the_<?php echo $itemTypeSingular; ?>" method="POST"
                              data-type="<?php echo $itemTypeSingular; ?>" class="form-horizontal o-modal-form">
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                  <?php
                  wp_editor('', $fieldPrefix . 'comment', [
                      'media_buttons' => true,
                      'textarea_rows' => 5,
                      'textarea_name' => 'comment',
                  ]);
                  ?>
                </div>
                                <div class="hidden-xs hidden-sm col-md-1 col-lg-1"></div>
                            </div>

                            <input type="hidden" id="type" name="type" value="<?php echo $itemTypeSingular; ?>"/>
                            <?php wp_nonce_field('upstream:project.add_comment', 'discussion.csrf'); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Cancel',
                                'upstream-frontend-edit'); ?></button>
                        <button
                                type="button"
                                class="btn btn-primary"
                                data-action="comments.add_comment"
                                data-editor_id="<?php echo $fieldPrefix . 'comment'; ?>"
                                data-nonce="<?php echo wp_create_nonce('upstream:project.add_comment'); ?>"
                                data-item_type="project">
                            <i class="fa fa-plus"></i>
                            <?php esc_html_e('Add Comment', 'upstream'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'comment_reply.php'; ?>
