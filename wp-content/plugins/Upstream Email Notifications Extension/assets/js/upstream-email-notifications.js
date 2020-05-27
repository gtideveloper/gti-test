(function (window, document, $, undefined) {
    if (!$) {
        console.error('jQuery not found.');
        return;
    }

    $(document).ready(function () {
        var request_has_been_sent = false;

        $('div.upstream_options form#upstream_email_notifications button#upstream-email-notifications-test').on('click', function () {
            var self = $(this);

            if (!request_has_been_sent) {
                $.ajax({
                    type: 'POST',
                    url: 'admin-ajax.php',
                    data: {
                        action: 'upstream_email_notifications_send_test_email'
                    },
                    beforeSend: function (jqXHR, settings) {
                        self.addClass('disabled');
                        self.text(self.data('loading-text'));

                        request_has_been_sent = true;
                    },
                    success: function (data, textStatus, jqXHR) {
                        alert(data.message);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error(textStatus + ': ' + errorThrown);
                    },
                    complete: function (jqXHR, textStatus) {
                        self.text(self.attr('title'));
                        self.removeClass('disabled');

                        request_has_been_sent = false;
                    }
                });
            }
        });

        var handler = $('form#upstream_email_notifications #handler');
        handler.on('change', function () {
            var selectedHandler = $(this).val();
            var selectedHandlerWrapper = $('div[data-handler="' + selectedHandler + '"]');
            if (selectedHandlerWrapper.length > 0) {
                uncollapseHandlerWrapper(selectedHandlerWrapper);
            }

            var otherWrappers = $('div[data-handler][data-handler!="' + selectedHandler + '"]');
            if (otherWrappers.length > 0) {
                for (var wrapperIndex = 0; wrapperIndex < otherWrappers.length; wrapperIndex++) {
                    var wrapper = $(otherWrappers[wrapperIndex]);
                    if (wrapper.length) {
                        collapseHandlerWrapper(wrapper);
                    }
                }
            }
        });

        handler.trigger('change');

        var iconCollapsedClass = 'dashicons-arrow-up-alt2';
        var iconNotCollapsedClass = 'dashicons-arrow-down-alt2';
        var isCollapsedClass = 'is-collapsed';

        function collapseHandlerWrapper (wrapper) {
            wrapper.addClass(isCollapsedClass);

            $('.up-c-collapse__content', wrapper).slideUp();
            $('span', $('h3[role="button"]', wrapper)).removeClass(iconCollapsedClass)
                .addClass(iconNotCollapsedClass);
        }

        function uncollapseHandlerWrapper (wrapper) {
            wrapper.removeClass(isCollapsedClass);

            $('.up-c-collapse__content', wrapper).slideDown();
            $('span', $('h3[role="button"]', wrapper)).removeClass(iconNotCollapsedClass)
                .addClass(iconCollapsedClass);
        }

        function toggleHandlerWrapperCollapseStatus (wrapper) {
            if (wrapper.hasClass(isCollapsedClass)) {
                uncollapseHandlerWrapper(wrapper);
            } else {
                collapseHandlerWrapper(wrapper);
            }
        }

        $('.up-c-collapse > [role="button"]').on('click', function (e) {
            e.preventDefault();

            var self = $(this);
            var wrapper = $(self.parent());

            toggleHandlerWrapperCollapseStatus(wrapper);
        });
    });
})(window, window.document, jQuery || null);
