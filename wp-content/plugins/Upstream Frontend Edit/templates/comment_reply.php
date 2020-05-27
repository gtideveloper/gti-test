<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

if (upstream_permissions('publish_project_discussion')): ?>

    <div id="modal-reply_comment" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">×</span>
                    </button>
                    <h4 class="modal-title"><?php esc_html_e('Replying Comment', 'upstream-frontend-edit'); ?></h4>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="panel panel-default o-comment-reply">
                            <div class="panel-body">
                                <div class="o-comment">
                                    <div class="o-comment__body">
                                        <div class="o-comment__body__left">
                                            <img class="o-comment__user_photo" src="">
                                        </div>
                                        <div class="o-comment__body__right">
                                            <div class="o-comment__body__head">
                                                <div class="o-comment__user_name"></div>
                                                <div class="o-comment__reply_info"></div>
                                                <div class="o-comment__date"></div>
                                            </div>
                                            <div class="o-comment__content"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
            <?php
            wp_editor("", '_upstream_project_comment_reply', [
                'media_buttons' => true,
                'textarea_rows' => 5,
                'textarea_name' => 'comment_reply',
            ]);
            ?>
          </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-reply"></i> <?php esc_html_e('Reply'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>
