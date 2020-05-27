
(function (window, document, $, ajaxurl, languageStrings, undefined) {
    if (!$) {
        console.error('UpStream > Copy Project requires jQuery to run.');
        return;
    }

$(document).ready(function () {
  
    $('.upstream-frontend-copy-project-anchor[data-post-id]').on('click', function (e) {
        e.preventDefault();

        var self = $(this);

        if (!confirm(upstreamFrontEditLang['MSG_CONFIRM_COPY'])) return;

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                project_id: self.data('post-id'),
                security: self.data('nonce'),
                action: 'upstream.frontend-edit:copy_project'
            },
            beforeSend: function (jqXHR, settings) {
                self.addClass('disabled');
                self.text(self.data('disabled-text'));
                self.blur();
            },
            success: function (response) {
                if (response) {
                    var errorMessage = null;

                    if (response.err) {
                        errorMessage = response.err;
                    } else {
                        if (!response.success) {
                            errorMessage = languageStrings['ERR_UNABLE_TO_COPY'];
                        } else {
                            return window.location.reload();
                        }
                    }
                } else {
                    errorMessage = languageStrings['ERR_INVALID_RESPONSE'];
                }

                alert(errorMessage);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error(errorThrown);
            },
            complete: function (jqXHR, textStatus) {
                self.removeClass('disabled');
                self.text(self.data('text'));
            }
        });
    });
});
})(window, window.document, jQuery || null, ajaxurl, upstreamCopyProjectLangStrings);
