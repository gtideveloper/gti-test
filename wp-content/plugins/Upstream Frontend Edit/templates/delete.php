<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}
?>

<div id="confirm-delete" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">

        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title"><?php esc_html_e('Delete This Item', 'upstream-frontend-edit'); ?></h4>
            </div>

            <div class="modal-body">

                <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Cancel',
                        'upstream-frontend-edit'); ?></button>
                <a class="btn btn-danger btn-ok" data-post_id="<?php echo get_the_ID() ?>"><?php esc_html_e('Delete',
                        'upstream'); ?></a>

            </div>
        </div>
    </div>
</div>
