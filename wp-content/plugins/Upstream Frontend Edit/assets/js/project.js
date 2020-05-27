function fixBodyScrollIfNeeded () {
    if (jQuery('.modal:visible').length > 0) {
        jQuery('body').addClass('modal-open');
    }
}

(function (window, document, $, $l, undefined) {
    'use strict';

    // Fix compatibility with the theme Alliance.
    if (typeof window.THEMEREX_GLOBALS === 'undefined') {
        window.THEMEREX_GLOBALS = {};
    }

    /**
     * We remove the client's offset from the datetime to return the UTC date.
     *
     * @param int datetime
     */
    function getUTCDate (datetime) {
        // Timezone Offset in seconds
        var offset = new Date().getTimezoneOffset() * 60;
        // Remove the offset.
        datetime = parseInt(datetime) + parseInt(offset);

        return new Date(datetime * 1000);
    }

    /**
     * Get the datetime with the timezone offset
     *
     * @param int datetime
     */
    function getLocalTimeZoneDate (datetime) {
        //Return the local date
        var d = new Date(datetime * 1000);
        return new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate());
    }


    function openModal (target, onShowCallback, onHiddenCallback) {
        var modal = new Modal({
            el: target
        });

        modal.on('show', onShowCallback);
        modal.on('hidden', onHiddenCallback);

        modal.show();

        return modal;
    }

    function resetModalForm (modal) {
        var modalWrapper = modal.el;
        var form = $('.o-modal-form', modalWrapper);

        form.get(0).reset();

        $('#project_category_section').css('display', 'none');

        $('.is-invalid', modalWrapper).removeClass('is-invalid');
        $('.has-error', modalWrapper).removeClass('has-error');
        $('.o-modal-form .o-error-message').remove();

        $('.row', form).css('display', 'initial');
        $('.row [data-was-required]', form).attr('required', 'true').removeAttr('data-was-required');
        $('.row [data-added-disabled]', form).removeAttr('disabled').removeAttr('data-added-disabled');

        var taid = $('.row textarea', form).attr('id');
        if (typeof(taid) !== 'undefined' && taid != '') {
            try {
                tinymce.get(taid).setMode('design');
            } catch (etm) {}
        }

        var taid2 = $('[data-wrapper-type="comments"] textarea', modalWrapper).attr('id');
        if (typeof(taid2) !== 'undefined' && taid2 != '') {
            try {
                tinymce.get(taid2).setMode('design');
            } catch (etm) {}
        }

        try {
            $('.wp-color-picker').wpColorPicker('color', "#ccc");
        } catch (etm2) {}

        $('select', modalWrapper).each(
            function () {

                if ($(this).prop('multiple')) {
                    $(this)[0].value = null;
                } else {
                    $(this).val(null);
                }

                $(this).trigger('change').trigger('chosen:updated');
            }
        );

        $('input[type="hidden"][data-default != "1"]', modalWrapper).each(function () {
            var self = $(this);

            var id = self.attr('id');
            if (['type', 'post_id', 'upstream-nonce', '_wp_http_referer'].indexOf(id) === -1) {
                self.val('');
            }
        });

        $('.c-comments', modalWrapper).html('');
        $('.file-preview').html('<div></div>');

        $('[data-wrapper-type="comments"] button[data-action="comments.add_comment"]', modalWrapper).css('visibility', 'visible');

        // Reset color pickers.
        $('.wp-color-picker', modalWrapper).each(function () {
            $(this).val($(this).data('default'));
        });

        // Make sure Data tab is visible.
        $('.modal-body > .nav.nav-tabs', modalWrapper).show();
        $('.modal-body > .nav.nav-tabs > li', modalWrapper).removeClass('active');
        $('.modal-body > .nav.nav-tabs > li:first-child', modalWrapper).addClass('active');

        // Make sure Data tab-content is visible.
        $('.modal-body .tab-content > .tab-pane', modalWrapper).removeClass('active in');
        $('.modal-body .tab-content > .tab-pane:first-child', modalWrapper).addClass('active in');

        // Make sure Delete button is visible.
        $('.modal-footer > .row[data-visible-when!="edit"]', modalWrapper).hide();
        $('.modal-footer > .row[data-visible-when="edit"]', modalWrapper).show();



        var table = $('table.reminders tbody', form);

        // try to remove reminders
        if (table.length > 0) {
            table.html('');
            $('input[type="hidden"][name="data[reminders][]"]', form).remove();

            var select = $('select[name="reminder"]', form);
            $('option:not(:first-child)', select).attr('disabled', null);
        }
    }

    function createModal (selector) {
        var modalEl = $(selector);
        if (modalEl.length === 0) return;

        var modal = new Modal({
            el: modalEl.get(0),
            backdrop: "static",
            keyboard: false
        });

        return modal;
    }

    function getCommentEditor (editor_id) {
        var TinyMceSingleton = window.tinyMCE ? window.tinyMCE : (window.tinymce ? window.tinymce : null);
        var theEditor = false;

        if (TinyMceSingleton !== null) {
            theEditor = TinyMceSingleton.get(editor_id);
        }

        return theEditor;
    }

    function getCommentEditorTextarea (editor_id) {
        return $('#' + editor_id);
    }

    function getEditorContent (editor_id, asHtml) {
        asHtml = typeof asHtml === 'undefined' ? true : (asHtml ? true : false);

        var theEditor = getCommentEditor(editor_id);
        var content = '';

        var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;
        if (isEditorInVisualMode) {
            if (asHtml) {
                content = (theEditor.getContent() || '').trim();
            } else {
                content = (theEditor.getContent({format: 'text'}) || '').trim();
            }
        } else {
            theEditor = getCommentEditorTextarea(editor_id);
            content = theEditor.val().trim();
        }

        return content;
    }

    function appendCommentHtmlToDiscussion (commentHtml, wrapper) {
        var comment = $(commentHtml);
        comment.hide();

        commentHtml = comment.html()
            .replace(/\\'/g, '\'')
            .replace(/\\"/g, '"');

        comment.html(commentHtml);

        comment.prependTo(wrapper);

        $('[data-toggle="tooltip"]', comment).tooltip();

        comment.slideDown();
    }

    function resetCommentEditorContent (editor_id) {
        var theEditor = getCommentEditor(editor_id);
        if (theEditor) {
            theEditor.setContent('');
        }

        var theEditorTextarea = getCommentEditorTextarea(editor_id);
        theEditorTextarea.val('');
    }

    function isIE11 () {
        return !!window.MSInputMethodContext && !!document.documentMode;
    }

    $(document).ready(function () {
        var project_id = $('#post_id').val();
        var flag = false;

        $(".o-modal-form #_upstream_project_task_status").on('chosen:ready', function() {
            var obj = $(this);
            $('.chosen-container').on('click', function() {
                var taskId = $(obj).val();
                var curPer = $(".o-modal-form #_upstream_project_task_progress").val();
                flag = true;
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'upstream.task-edit.gettaskpercent',
                        task_id: taskId,
                        cur_per: curPer
                    },
                    success: function (response) {
                        $(".o-modal-form #_upstream_project_task_progress").val(response).trigger('change').trigger('chosen:updated');
                        flag = false;
                    }
                });
            });
        }).chosen({
            allow_single_deselect: true
        });

        $(".o-modal-form #_upstream_project_task_progress").on('chosen:ready', function() {
            var obj = $(this);
            $('.chosen-container').on('click', function() {
                if (flag == false) {
                    var taskPercent = $(obj).val();
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'upstream.task-edit.gettaskstatus',
                            task_percent: taskPercent
                        },
                        success: function (response) {
                          // RSD: this causes a mess in changing the numbers
                            // $(".o-modal-form #_upstream_project_task_status").val(response).trigger('change').trigger('chosen:updated');
                        }
                    });
                }
            });
        }).chosen({
            allow_single_deselect: true
        });

        $('.o-modal-form select.form-control:not([multiple])').chosen({
            allow_single_deselect: true
        });

        $('.o-modal-form select.form-control[multiple]').chosen();

        // Fix submit on modal in IE 11
        if (isIE11()) {
            var selector = '#modal-milestone button[type="submit"]'
                + ', #modal-tasks button[type="submit"]'
                + ', #modal-bugs button[type="submit"]'
                + ', #modal-files button[type="submit"]';

            $(selector).on('click', function (event) {
                $('#' + $(event.target).attr('form')).submit();
            });
        }

        /**
         * Add persistent scroll position when editing items.
         */
        $('.modal-dialog button[type="submit"]').click(function () {
            if (window.sessionStorage) {

                var scrollPosition = (document.documentElement.scrollTop || document.body.scrollTop);
                window.sessionStorage.setItem('upstream-scroll-position', scrollPosition);
            }
        });

        if (typeof($(window).on) === 'function') {
            $(window).on('load', function () {
                window.setTimeout(function () {
                    if (window.sessionStorage.getItem('upstream-scroll-position') !== null) {
                        if (typeof document.documentElement.scrollTop !== 'undefined') {
                            document.documentElement.scrollTop = window.sessionStorage.getItem('upstream-scroll-position');
                        } else if (typeof document.body.scrollTop !== 'undefined') {
                            document.body.scrollTop = window.sessionStorage.getItem('upstream-scroll-position');
                        }

                        sessionStorage.removeItem('upstream-scroll-position');
                    }
                }, 200);
            });
        }
        else {
            $(window).load(function () {
                window.setTimeout(function () {
                    if (window.sessionStorage.getItem('upstream-scroll-position') !== null) {
                        if (typeof document.documentElement.scrollTop !== 'undefined') {
                            document.documentElement.scrollTop = window.sessionStorage.getItem('upstream-scroll-position');
                        } else if (typeof document.body.scrollTop !== 'undefined') {
                            document.body.scrollTop = window.sessionStorage.getItem('upstream-scroll-position');
                        }

                        sessionStorage.removeItem('upstream-scroll-position');
                    }
                }, 200);
            });
        }

        /**
         * Add Items.
         */
        $('.btn[data-toggle="up-modal"][data-target][data-form-type="add"]').on('click', function (e) {
            e.preventDefault();

            var self = $(this);
            var modal = createModal(self.attr('data-target') || '');

            try {
                resetModalForm(modal);
            } catch (e) {}

            modal.on('show', function (modal) {
                $('.modal-title span', modal.el).text(self.attr('data-modal-title'));

                // Make sure Data tab is visible.
                $('.modal-body > .nav.nav-tabs', modal.el).hide();
                $('.modal-body > .nav.nav-tabs > li', modal.el).removeClass('active');
                $('.modal-body > .nav.nav-tabs > li:first-child', modal.el).addClass('active');

                // Make sure Data tab-content is visible.
                $('.modal-body .tab-content > .tab-pane', modal.el).removeClass('active in');
                $('.modal-body .tab-content > .tab-pane:first-child', modal.el).addClass('active in');

                // Make sure Delete button is hidden.
                $('.modal-footer > .row[data-visible-when!="edit"]', modal.el).show();
                $('.modal-footer > .row[data-visible-when="edit"]', modal.el).hide();

                var formTypeField = $(modal.el).find('input#upstream_form_type');

                if (formTypeField.length === 0) {
                    formTypeField = $('<input />', {
                        type: 'hidden',
                        name: 'form_type',
                        id: 'upstream_form_type'
                    });

                    $(modal.el).find('form').append(formTypeField);
                }

                formTypeField.val('add');

                // RSD: to fix the editing previous item bug
                $('[name="editing"]').remove();

                // Init color pickers.
                $('.colorpicker', modal.el).each(function () {
                    var field = $(this);

                    field.attr('type', 'text');

                    var args = {
                        palettes: field.attr('data-palettes')
                    };

                    field.wpColorPicker(args);

                    var wrapper = $(field.parents('.wp-picker-container'));
                    wrapper.find('.color-alpha').on('click', function () {
                        wrapper.toggleClass('wp-picker-active');
                    });
                    wrapper.find('.wp-color-result').addClass('button');

                    var legacyWrapperWrap = wrapper.find('.wp-picker-input-wrap');
                    var wrapperWrap = $('<div class="wp-picker-input-wrap"></div>');
                    var wrapperWrapLabel = $('<label></label>');

                    wrapperWrapLabel.append($('<span class="screen-reader-text"></span>'));
                    wrapperWrapLabel.append(legacyWrapperWrap.find('.wp-color-picker'));
                    wrapperWrap.append(wrapperWrapLabel);
                    wrapperWrap.append(legacyWrapperWrap.find('.wp-picker-clear'));

                    legacyWrapperWrap.replaceWith(wrapperWrap);

                    field.iris('color', field.data('default'));
                });

                if (typeof(window.upstream_defaultReminders) !== 'undefined') {
                    window.upstream_defaultReminders(modal.el);
                }

                // Trigger event for other add-ons.
                $('body').trigger('openmodal', [modal]);
            });
            modal.on('hidden', resetModalForm);
            modal.show();
        });

        /**
         * Edit Items.
         */
        $('.o-data-table').on('click', '> tbody > tr[data-id] > td > .o-edit-link', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var tr = $(self.parents('tr[data-id]'));
            var item_id = tr.attr('data-id');

            var modal = createModal(self.attr('data-target') || '');
            try {
                resetModalForm(modal);
            } catch (e) {}

            modal.on('show', function (modal) {
                var modalWrapper = modal.el;

                // Make sure Data tab is visible.
                $('.modal-body > .nav.nav-tabs', modal.el).show();
                $('.modal-body > .nav.nav-tabs > li', modal.el).removeClass('active');
                $('.modal-body > .nav.nav-tabs > li:first-child', modal.el).addClass('active');

                // Make sure Data tab-content is visible.
                $('.modal-body .tab-content > .tab-pane', modal.el).removeClass('active in');
                $('.modal-body .tab-content > .tab-pane:first-child', modal.el).addClass('active in');

                // Make sure Delete button is visible.
                $('.modal-footer > .row[data-visible-when!="edit"]', modalWrapper).hide();
                $('.modal-footer > .row[data-visible-when="edit"]', modalWrapper).show();

                // Set modal title.
                $('.modal-title span', modalWrapper).text(self.attr('data-value'));

                tr.addClass('is-being-opened');
            });

            modal.on('shown', function () {
                tr.removeClass('is-being-opened');
                tr.addClass('is-open');
            });

            modal.on('hidden', function (theModal) {
                tr.removeClass('is-being-opened is-open');

                resetModalForm(theModal);
            });

            var table = $(self.parents('table.o-data-table').get(0));
            var item_type = table.attr('type');
            $.ajax({
                type: 'GET',
                url: upstream.ajaxurl,
                data: {
                    action: 'upstream.frontend-edit:fetch_item',
                    nonce: self.attr('data-nonce'),
                    project_id: project_id,
                    item_type: item_type,
                    item_id: item_id
                },
                beforeSend: function () {
                    $('i', self).removeClass('fa-pencil').addClass('fa-spinner fa-spin');
                },
                success: function (response) {
                    
                    // try to get debug info
                    try {

                        if (response.error) {
                            alert(response.error);
                        } else {
                            if (!response.success) {
                                console.error('Something went wrong.');
                            } else {
                                var modalWrapper = modal.el;
                                var form = $('.o-modal-form', modalWrapper);

                                $('[name="editing"]', form).remove();

                                var idField = $('<input />', {
                                    type: 'hidden',
                                    name: 'editing'
                                });
                                idField.val(response.data.id);
                                form.append(idField);


                                if ((typeof (response.data.canViewComments) != 'undefined' && !response.data.canViewComments) ||
                                    (typeof (response.data.canEditComments) != 'undefined' && !response.data.canEditComments)) {
                                    var taid3 = $('[data-wrapper-type="comments"] textarea', modalWrapper).attr('id');
                                    if (typeof(taid3) !== 'undefined' && taid3 != '') {
                                        try {
                                            tinymce.get(taid3).setMode('readonly');
                                        } catch (etm) {}
                                    }
                                    $('[data-wrapper-type="comments"] button[data-action="comments.add_comment"]', modalWrapper).css('visibility', 'hidden');
                                }

                                // Inject the title in the Add Comments button data.
                                if (typeof response.data.data.title !== 'undefined') {
                                    $('button[data-action="comments.add_comment"]', modalWrapper).data('item_title', response.data.data.title.value);
                                } else if (typeof response.data.data.milestone !== 'undefined') {
                                    $('button[data-action="comments.add_comment"]', modalWrapper).data('item_title', response.data.data.milestone.value);
                                }

                                for (var column_name in response.data.data) {
                                    var column = response.data.data[column_name];

                                    if (!column.has_view_permission) {
                                        $('.form_row_' + column_name, modalWrapper).css('display', 'none');
                                        $('.form_row_' + column_name + ' [required]', modalWrapper).attr('data-was-required', 'true').removeAttr('required');
                                        continue;
                                    } else if (!column.has_edit_permission) {
                                        $('.form_row_' + column_name + ' .form-control', modalWrapper).attr('disabled', 'true').attr('data-added-disabled', 'true');
                                        var taid2 = $('.form_row_' + column_name + ' textarea', modalWrapper).attr('id');
                                        if (typeof(taid2) !== 'undefined' && taid2 != '') {
                                            try {
                                                tinymce.get(taid2).setMode('readonly');
                                            } catch (etm) {}
                                        }
                                    }

                                    column.name = column_name;

                                    if (column.type === 'date') {
                                        if (column.value > 0) {
                                            var date = getLocalTimeZoneDate(column.value);

                                            $('[name="data[' + column_name + ']"]', form).datepicker('setDate', date);
                                            $('#_upstream_project_' + item_type + '_' + column_name + '_timestamp', form).val(column.value);
                                        }
                                    } else if (column.type === 'wysiwyg') {
                                        if (column.value.length > 0) {
                                            var editor_id = '_upstream_project_' + item_type + '_' + column_name;

                                            var theEditor = getCommentEditor(editor_id);
                                            if (theEditor) {
                                                theEditor.setContent(column.value);
                                            }

                                            var theEditorTextarea = getCommentEditorTextarea(editor_id);
                                            theEditorTextarea.val(column.value);
                                        }
                                    } else if (column.type === 'file') {
                                        if (!column.value || column.value.length === 0) continue;

                                        var filePreviewWrapper = $('.file-preview', $('[data-name="' + column_name + '"]', form).parent());

                                        var mediaWrapper = $('' +
                                            '<div class="c-media-preview media">' +
                                            '<div class="media-left">' +
                                            '<a href="#" target="_blank" rel="noopener noreferrer">' +
                                            '<img class="media-object" src="" alt="">' +
                                            '</a>' +
                                            '</div>' +
                                            '<div class="media-body">' +
                                            '<h5 class="media-heading"></h5>' +
                                            '<div>' +
                                            '<ul>' +
                                            '<li>' +
                                            '<a href="#" class="btn btn-xs btn-default" data-action="file.remove" data-field-name="' + column_name + '"><i class="fa fa-trash"></i> ' + $l.LB_REMOVE + '</a>' +
                                            '</li>' +
                                            '</ul>' +
                                            '</div>' +
                                            '<input type="hidden" name="data[' + column_name + '_id]" value="' + column.id + '">' +
                                            '<input type="hidden" name="data[' + column_name + ']" value="' + column.value + '">' +
                                            '</div>' +
                                            '</div>'
                                        );

                                        $('img', mediaWrapper).attr('src', column.value);
                                        $('h5', mediaWrapper).html(column.title + ' <a href="' + column.value + '" target="_blank">(download)</a>');

                                        filePreviewWrapper.html(mediaWrapper);

                                        mediaWrapper.on('click', '.btn[data-action="file.remove"]', function (e) {
                                            e.preventDefault();
                                            e.stopPropagation();

                                            var self = $(this);
                                            var column_name = self.data('field-name');
                                            self.attr('data-action', 'file.remove.undo');
                                            self.html('<i class="fa fa-refresh"></i> ' + $l.LB_UNDO);

                                            $('[name="data[' + column_name + '__remove]"]', mediaWrapper).remove();
                                            mediaWrapper.append($('<input type="hidden" name="data[@' + column_name + '__remove]" value="1">'));
                                            $('.media-left,h5', mediaWrapper).hide();
                                        });

                                        mediaWrapper.on('click', '[data-action="file.remove.undo"]', function (e) {
                                            e.preventDefault();
                                            e.stopPropagation();

                                            var self = $(this);
                                            var column_name = self.data('field-name');
                                            self.attr('data-action', 'file.remove');
                                            self.html('<i class="fa fa-trash"></i> ' + $l.LB_REMOVE);

                                            $('[name="data[@' + column_name + '__remove]"]', mediaWrapper).remove();
                                            $('.media-left,h5', mediaWrapper).show();
                                        });

                                        filePreviewWrapper.show();
                                    } else if (column.type === 'user' || column.type === 'taxonomies') {
                                        var select = $('[name="data[' + column_name + '][]"]', form);
                                        $('option', select).attr('selected', null);
                                        if (typeof column.value === 'object') {
                                            var cvKeys = Object.keys(column.value);
                                            for (var cvIndex = 0; cvIndex < cvKeys.length; cvIndex++) {
                                                $('option[value="' + column.value[cvKeys[cvIndex]] + '"]', select).attr('selected', 'selected');
                                            }
                                        } else {
                                            select.val(column.value);
                                        }

                                        select.trigger('change').trigger('chosen:updated');
                                        select = null;
                                    } else if (column.type === 'array') {

                                        // handle email reminders - if they exist
                                        if (column.name === 'reminders') {
                                            if (typeof (window.upstream_loadReminders) !== 'undefined') {
                                                window.upstream_loadReminders(item_type, column.value);
                                            }
                                        }
                                        else {
                                            $('#upstream_' + item_type + '_' + column_name, form).val(column.value).trigger('chosen:updated');
                                        }

                                    } else if (column.type === 'checkbox') {
                                        if (typeof column.value === 'object') {
                                            $(column.value).each(function () {
                                                $('input#upstream_' + item_type + '_' + column_name + '_' + this).prop('checked', true);
                                            });
                                        }
                                    } else if (column.type === 'radio') {
                                        if (column.value === '' || column.value === null || column.value === '__none__' || (typeof column.value === 'object' && column.value.length === 0)) {
                                            $('input#upstream_' + item_type + '_my-radio_none').prop('checked', true);
                                        } else {
                                            $('input[type="radio"][value="' + column.value + '"]', form).prop('checked', true);
                                        }
                                    } else if (column.type === 'colorpicker') {
                                        var field = $('input#_upstream_project_' + item_type + '_' + column_name);

                                        if (!field.hasClass('wp-color-picker')) {
                                            field.attr('type', 'text');

                                            var args = {
                                                palettes: field.attr('data-palettes')
                                            };

                                            field.wpColorPicker(args);

                                            var wrapper = $(field.parents('.wp-picker-container'));
                                            wrapper.find('.color-alpha').on('click', function () {
                                                wrapper.toggleClass('wp-picker-active');
                                            });
                                            wrapper.find('.wp-color-result').addClass('button');

                                            var legacyWrapperWrap = wrapper.find('.wp-picker-input-wrap');
                                            var wrapperWrap = $('<div class="wp-picker-input-wrap"></div>');
                                            var wrapperWrapLabel = $('<label></label>');

                                            wrapperWrapLabel.append($('<span class="screen-reader-text"></span>'));
                                            wrapperWrapLabel.append(legacyWrapperWrap.find('.wp-color-picker'));
                                            wrapperWrap.append(wrapperWrapLabel);
                                            wrapperWrap.append(legacyWrapperWrap.find('.wp-picker-clear'));

                                            legacyWrapperWrap.replaceWith(wrapperWrap);
                                        }

                                        var selectedColor = column.value;

                                        if (selectedColor) {
                                            field.iris('color', selectedColor);
                                        } else {
                                            field.val('');

                                            var wrapper = field.parents('.wp-picker-container');
                                            wrapper.find('.wp-color-result').css('background-color', '');
                                        }
                                    } else {
                                        $('[name="data[' + column_name + ']"]', form).val(column.value).trigger('change').trigger('chosen:updated');
                                    }
                                }

                                if (response.data.comments.length > 0) {
                                    var commentsWrapper = $('.c-comments', modalWrapper);

                                    $.each(response.data.comments, function (rowIndex, commentHtmlString) {
                                        var comment = $(commentHtmlString);
                                        commentsWrapper.append(comment);
                                    });

                                    $('[data-toggle="tooltip"]', commentsWrapper).tooltip();
                                }


                                var modalObj = self.attr('data-target');
                                $(modalObj).modal({backdrop: "static"});
                                if (self.hasClass("text-info")) {
                                    $(modalObj + ' .modal-footer > .row[data-visible-when="edit"]').hide();
                                    $(modalObj + ' .modal-footer > .row[data-visible-when!="edit"]').show();
                                } else {
                                    $(modalObj + ' .modal-footer > .row[data-visible-when!="edit"]').hide();
                                    $(modalObj + ' .modal-footer > .row[data-visible-when="edit"]').show();
                                }
                                $(modalObj + " .modal-title span").text(self.attr('data-value'));
                            }
                        }

                    } catch (e) {
                        alert("UpStream error: \n\n" + e.stack);
                    }
                },
                error: function (request, textStatus, errorThrown) {
                    alert(textStatus);
                },
                complete: function () {
                    $('i', self).removeClass('fa-spinner fa-spin').addClass('fa-pencil');
                }
            });
        });

        $('.o-modal-form .o-datepicker + input[type="hidden"]').each(function () {
            var self = $(this);

            self.attr('name', 'data[' + self.attr('data-name') + '_timestamp]');
        });

        var loading_modal = false;

        $('body').on('click', 'a.edit-project, #upstream_new_project', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var project_id = self.data('id');

            var modal = createModal(self.data('target') || '');

            modal.on('show', function (modal) {
                var modalWrapper = modal.el;

                // Make sure Data tab is visible.
                $('.modal-body > .nav.nav-tabs', modal.el).show();
                $('.modal-body > .nav.nav-tabs > li', modal.el).removeClass('active');
                $('.modal-body > .nav.nav-tabs > li:first-child', modal.el).addClass('active');

                // Make sure Data tab-content is visible.
                $('.modal-body .tab-content > .tab-pane', modal.el).removeClass('active in');
                $('.modal-body .tab-content > .tab-pane:first-child', modal.el).addClass('active in');

                // Make sure Delete button is visible.
                $('.modal-footer > .row[data-visible-when!="edit"]', modalWrapper).hide();
                $('.modal-footer > .row[data-visible-when="edit"]', modalWrapper).show();

                // Set modal title.
                $('.modal-title span', modalWrapper).text(self.attr('data-value'));

                // Trigger event for other add-ons.
                $('body').trigger('openmodal', [modal]);
            });

            modal.on('hidden', function (theModal) {
                resetModalForm(theModal);
            });

            $.ajax({
                type: 'GET',
                url: upstream.ajaxurl,
                data: {
                    action: 'upstream.frontend-edit:fetch_project',
                    nonce: self.data('nonce'),
                    project_id: project_id
                },
                beforeSend: function () {
                    $('i', self).removeClass('fa-pencil').addClass('fa-spinner fa-spin');
                },
                success: function (response) {

                    try {
                        if (response.error) {
                            alert(response.error);
                        } else {
                            if (!response.success) {
                                console.error('Something went wrong.');
                            } else {
                                loading_modal = true;

                                var modalWrapper = modal.el;
                                var form = $('.o-modal-form', modalWrapper);

                                $('[name="editing"]', form).remove();

                                var idField = $('<input />', {
                                    type: 'hidden',
                                    name: 'editing',
                                    id: 'upstream_editing_id'
                                });
                                idField.val(response.data.id);
                                form.append(idField);

                                var formTypeField = $('<input />', {
                                    type: 'hidden',
                                    name: 'form_type',
                                    id: 'upstream_form_type'
                                });
                                var item_type = "project";
                                formTypeField.val(response.action);
                                form.append(formTypeField);

                                for (var column_name in response.data.data) {
                                    var column = response.data.data[column_name];

                                    if (!column.has_view_permission) {
                                        $('.form_row_' + column_name, modalWrapper).css('display', 'none');
                                        $('.form_row_' + column_name + ' [required]', modalWrapper).attr('data-was-required', 'true').removeAttr('required');
                                        continue;
                                    } else if (!column.has_edit_permission) {
                                        $('.form_row_' + column_name + ' .form-control', modalWrapper).attr('disabled', 'true').attr('data-added-disabled', 'true');
                                        var taid2 = $('.form_row_' + column_name + ' textarea', modalWrapper).attr('id');
                                        if (typeof(taid2) !== 'undefined' && taid2 != '') {
                                            try {
                                                tinymce.get(taid2).setMode('readonly');
                                            } catch (etm) {}
                                        }
                                    }

                                    column.name = column_name;

                                    if (column.value === null) {
                                        column.value = '';
                                    }

                                    if (column.type === 'date') {
                                        if (column.value > 0) {
                                            //var date = getUTCDate(column.value);
                                            var date = getLocalTimeZoneDate(column.value);

                                            $('[name="data[' + column_name + ']"]', form).datepicker('setDate', date);
                                            $('#_upstream_project_' + column_name + '_timestamp', form).val(column.value);
                                        }
                                    } else if (column.type === 'wysiwyg') {
                                        if (column.value.length > 0) {
                                            var editor_id = '_upstream_project_' + column_name;

                                            var theEditor = getCommentEditor(editor_id);
                                            if (theEditor) {
                                                theEditor.setContent(column.value);
                                            }

                                            var theEditorTextarea = getCommentEditorTextarea(editor_id);
                                            theEditorTextarea.val(column.value);
                                        }
                                    } else if (column.type === 'file') {
                                        if (!column.value || column.value.length === 0) continue;

                                        var filePreviewWrapper = $('.file-preview', $('#upstream_project_' + column_name + '_url', form).parent());

                                        var mediaWrapper = $('' +
                                            '<div class="c-media-preview media">' +
                                            '<div class="media-left">' +
                                            '<a href="#" target="_blank" rel="noopener noreferrer">' +
                                            '<img class="media-object" src="" alt="">' +
                                            '</a>' +
                                            '</div>' +
                                            '<div class="media-body">' +
                                            '<h5 class="media-heading"></h5>' +
                                            '<div>' +
                                            '<ul>' +
                                            '<li>' +
                                            '<a href="#" class="btn btn-xs btn-default" data-action="file.remove" data-field-name="' + column_name + '"><i class="fa fa-trash"></i> ' + $l.LB_REMOVE + '</a>' +
                                            '</li>' +
                                            '</ul>' +
                                            '</div>' +
                                            '<input type="hidden" name="data[' + column_name + '_id]" value="' + column.id + '">' +
                                            '<input type="hidden" name="data[' + column_name + ']" value="' + column.value + '">' +
                                            '</div>' +
                                            '</div>'
                                        );

                                        $('img', mediaWrapper).attr('src', column.value);
                                        $('h5', mediaWrapper).text(column.title);

                                        filePreviewWrapper.html(mediaWrapper);

                                        mediaWrapper.on('click', '.btn[data-action="file.remove"]', function (e) {
                                            e.preventDefault();
                                            e.stopPropagation();

                                            var self = $(this);
                                            self.attr('data-action', 'file.remove.undo');
                                            self.html('<i class="fa fa-refresh"></i> ' + $l.LB_UNDO);

                                            $('[name="data[' + column_name + '__remove]"]', mediaWrapper).remove();
                                            mediaWrapper.append($('<input type="hidden" name="data[@' + column_name + '__remove]" value="1">'));
                                            $('.media-left,h5', mediaWrapper).hide();
                                        });

                                        mediaWrapper.on('click', '[data-action="file.remove.undo"]', function (e) {
                                            e.preventDefault();
                                            e.stopPropagation();

                                            var self = $(this);
                                            self.attr('data-action', 'file.remove');
                                            self.html('<i class="fa fa-trash"></i> ' + $l.LB_REMOVE);

                                            $('[name="data[@' + column_name + '__remove]"]', mediaWrapper).remove();
                                            $('.media-left,h5', mediaWrapper).show();
                                        });

                                        filePreviewWrapper.show();
                                    } else if (column.type === 'user' || column.type === 'taxonomies' || column.type === 'tag' || column.type === 'category') {
                                        var select = $('[name="data[' + column_name + '][]"], [name="data[' + column_name + ']"]', form);
                                        $('option', select).attr('selected', null);
                                        if (typeof column.value === 'object') {
                                            var cvKeys = Object.keys(column.value);
                                            for (var cvIndex = 0; cvIndex < cvKeys.length; cvIndex++) {
                                                $('option[value="' + column.value[cvKeys[cvIndex]] + '"]', select).attr('selected', 'selected');
                                            }
                                        } else {
                                            select.val(column.value);
                                        }

                                        select.trigger('change').trigger('chosen:updated');
                                        select = null;
                                    } else if (column.type === 'array') {
                                        $('[name="data[' + column_name + '][]"]', form).val(column.value).trigger('chosen:updated');
                                    } else if (column.type === 'category') {
                                        $('#upstream_' + item_type + '_' + column_name, form).val(column.value).trigger('chosen:updated');
                                    } else if (column.type === 'tag' && self.hasClass("text-info")) {
                                        $('#upstream_' + item_type + '_' + column_name, form).val(column.value).trigger('chosen:updated');
                                    } else if (column.type === 'checkbox') {
                                        if (typeof column.value === 'object') {
                                            $(column.value).each(function () {
                                                $('input#upstream_' + item_type + '_' + column_name + '_' + this).prop('checked', true);
                                            });
                                        }
                                    } else if (column.type === 'project-category') {

                                        $('#project_category_section').css('display', 'none');

                                        var select = $('[name="data[' + column_name + '][]"], [name="data[' + column_name + ']"]', form);
                                        $('option', select).remove();
                                        if (typeof column.value === 'object') {

                                            $(column.value).each(function (key, value) {
                                                $('#project_category_section').css('display', 'initial');
                                                var checked = value.checked == true ? 'selected' : '';
                                                select.append('<option class="project-category" value="' + value.term_id + '" id="category_id-' + value.term_id + '" class="categorychecklist" ' + checked + '>' + value.name + '</option>');
                                            });

                                        }

                                        select.trigger('change').trigger('chosen:updated');
                                        select = null;

                                    } else if (column.type === 'radio') {
                                        if (column.value === '' || column.value === null || column.value === '__none__' || (typeof column.value === 'object' && column.value.length === 0)) {
                                            $('input#upstream_project_my-radio_none').prop('checked', true);
                                        } else {
                                            $('input[type="radio"][value="' + column.value + '"]', form).prop('checked', true);
                                        }
                                    } else if (column.type === 'colorpicker') {
                                        var field = $('input#upstream_project_' + column_name);

                                        if (!field.hasClass('wp-color-picker')) {
                                            field.attr('type', 'text');

                                            var args = {
                                                palettes: field.attr('data-palettes')
                                            };

                                            field.wpColorPicker(args);

                                            var wrapper = $(field.parents('.wp-picker-container'));
                                            wrapper.find('.color-alpha').on('click', function () {
                                                wrapper.toggleClass('wp-picker-active');
                                            });
                                            wrapper.find('.wp-color-result').addClass('button');

                                            var legacyWrapperWrap = wrapper.find('.wp-picker-input-wrap');
                                            var wrapperWrap = $('<div class="wp-picker-input-wrap"></div>');
                                            var wrapperWrapLabel = $('<label></label>');

                                            wrapperWrapLabel.append($('<span class="screen-reader-text"></span>'));
                                            wrapperWrapLabel.append(legacyWrapperWrap.find('.wp-color-picker'));
                                            wrapperWrap.append(wrapperWrapLabel);
                                            wrapperWrap.append(legacyWrapperWrap.find('.wp-picker-clear'));

                                            legacyWrapperWrap.replaceWith(wrapperWrap);
                                        }

                                        var selectedColor = column.value;

                                        if (selectedColor) {
                                            field.iris('color', selectedColor);
                                        } else {
                                            field.val('');

                                            var wrapper = field.parents('.wp-picker-container');
                                            wrapper.find('.wp-color-result').css('background-color', '');
                                        }
                                    } else if (column.type === 'autoincrement') {
                                        $('div[data-name="' + column.name + '"] .up-autoincrement', form).text(column.value);
                                    } else {
                                        $('[name="data[' + column_name + ']"]', form).val(column.value).trigger('change').trigger('chosen:updated');
                                    }
                                }

                                // Update the list of client users
                                var select = $('[name="data[client_users][]"]', form);
                                var list = typeof (response.data.data.all_client_users) !== 'undefined' && response.data.data.all_client_users.value ? response.data.data.all_client_users.value : [];
                                var users = typeof (response.data.data.client_users) !== 'undefined' && response.data.data.client_users.value ? response.data.data.client_users.value : [];
                                var option;

                                var idx = 0;
                                for (idx = 0; idx < users.length; idx++) {
                                    users[idx] = parseInt(users[idx]);
                                }

                                $('option', select).remove();

                                $.each(list, function (index, item) {
                                    option = $('<option>', {
                                        value: item.id
                                    }).text(item.display_name);

                                    if (users.indexOf(parseInt(item.id)) >= 0) {
                                        option.attr('selected', true);
                                    }

                                    option.appendTo(select);
                                });

                                select.trigger('change').trigger('chosen:updated');

                                $("#modal-project").modal({backdrop: "static"});
                                if (self.hasClass("text-info")) {
                                    $('#modal-project .modal-footer > .row[data-visible-when!="edit"]').hide();
                                    $('#modal-project .modal-footer > .row[data-visible-when="edit"]').show();
                                } else {
                                    $('#modal-project .modal-footer > .row[data-visible-when="edit"]').hide();
                                    $('#modal-project .modal-footer > .row[data-visible-when!="edit"]').show();
                                }
                                $("#modal-project .modal-title span").text(self.attr('data-value'));
                            }
                        }

                    } catch (e) {
                        alert("UpStream error: \n\n" + e.stack);
                    }
                },
                error: function (request, textStatus, errorThrown) {
                    alert(textStatus);
                },
                complete: function () {
                    $('i', self).removeClass('fa-spinner fa-spin').addClass('fa-pencil');
                }
            });
        });

        $('#modal-project #_upstream_project_client').on('change', function (e) {

            // Avoid to reload the list of client users if we are just loading the modal.
            if (loading_modal) {
                loading_modal = false;
                return;
            }

            var $self = $(this),
                select = $('#modal-project #_upstream_project_client_users'),
                modal = $('#modal-project');

            $('option', select).remove();

            $.ajax({
                type: 'GET',
                url: upstream.ajaxurl,
                data: {
                    action: 'upstream.frontend-edit:fetch_client_users',
                    client_id: modal.find('#_upstream_project_client').val(),
                    nonce: select.data('nonce')
                },
                beforeSend: function () {
                    $self.addClass('disabled');
                },
                success: function (response) {
                    $self.removeClass('disabled');

                    if (response.success) {
                        var option;

                        $.each(response.data.users, function (index, item) {
                            option = $('<option>', {
                                value: item.id,
                                selected: upstreamFrontEditLang.option_select_users_by_default == '1'
                            }).text(item.display_name);

                            option.appendTo(select);
                        });

                        select.trigger('change').trigger('chosen:updated');
                    } else {
                        console.error(response.error);
                    }
                },
                error: function (request, textStatus, errorThrown) {
                    console.error(errorThrown);

                    $self.removeClass('disabled');
                }
            });
        });

        //When in edit mode and the cancel button is clicked remove the lock
        $('.modal-footer button[type="button"]').on('click', function (e) {
            if ($(this).attr('data-dismiss') == 'modal') {
                var post_id = $('#upstream_editing_id').val();
                $.ajax({
                    type: 'GET',
                    url: upstream.ajaxurl,
                    data: {
                        action: 'upstream.frontend-edit:cancel_edit',
                        project_id: post_id,
                    },
                });
            }
        });

        $('#modal-project button[type="submit"]').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var editor_id = self.data('editor_id');
            var descriptionContent = getEditorContent(editor_id, true);
            var wrapper = $(self.parents('.modal-content'))[0];
            var modal = $('#modal-project');
            var data = {
                action: 'upstream.frontend-edit:save_project',
                nonce: self.data('nonce'),
                teeny: 1
            };

            // Make sure we add the custom fields too.
            var form = $('form#the_project');
            var valid = true;

            $.each(form[0].elements, function () {
                var el = $(this);

                if (el.attr('name')) {
                    var name = el.attr('name').replace(/data\[([a-z0-09\-_]+)\]/, '$1');
                    var value = null;

                    if (el.prop('tagName') === 'INPUT' && ['checkbox', 'radio'].indexOf(el.attr('type')) > -1) {
                        value = el.is(':checked') ? el.val() : null;
                    } else {
                        value = el.val();
                    }

                    if (!data[name]) {
                        if (name.match(/\[\]$/)) {
                            data[name] = [value];
                        } else {
                            data[name] = value;
                        }
                    } else {
                        // Is it an array?
                        if (name.match(/\[\]$/)) {
                            if (value !== null) {
                                data[name].push(value);
                            }
                        }
                    }
                }
            });

            // Add the description
            data['description'] = descriptionContent;

            if (typeof (form[0].checkValidity) == 'function') {
                valid = form[0].checkValidity();
            }

            // Check if any field is invalid
            $.each(form.children(), function () {
                var el = $(this);

                if (el.is(':invalid')) {
                    valid = false;
                }
            });

            if ($('.has-error', form).length) {
                valid = false;
            }

            if (valid) {
                $.ajax({
                    type: 'POST',
                    url: upstream.ajaxurl,
                    data: data,
                    beforeSend: function () {
                        self
                            .addClass('disabled')
                            .text($l.LB_SAVING);
                    },
                    success: function (response) {
                        self
                            .removeClass('disabled')
                            .text($l.LB_SAVE);

                        if (response.success) {
                            resetDescriptionEditorContent(editor_id);

                            window.location.href = response.url;
                        } else {
                            console.error(response.error);

                            $('.modal-body', wrapper).prepend($('' +
                                '<div class="alert alert-danger alert-dismissible" role="alert">' +
                                '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>' +
                                response.error +
                                '</div>'));
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        console.error(errorThrown);

                        self
                            .removeClass('disabled')
                            .text($l.LB_SAVE);
                    }
                });
            } else {
                if (typeof (form[0].reportValidity) == 'function') {
                    form[0].reportValidity();
                }
            }
        });

        function getDescriptionEditor (editor_id) {
            var TinyMceSingleton = window.tinyMCE ? window.tinyMCE : (window.tinymce ? window.tinymce : null);

            var theEditor = false;

            if (TinyMceSingleton !== null) {
                theEditor = TinyMceSingleton.get(editor_id);
            }

            return theEditor;
        }

        function getDescriptionEditorTextarea (editor_id) {
            return $('#' + editor_id);
        }

        function getEditorContent (editor_id, asHtml) {
            asHtml = typeof asHtml === 'undefined' ? true : (asHtml ? true : false);

            var theEditor = getDescriptionEditor(editor_id);
            var content = '';

            var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;
            if (isEditorInVisualMode) {
                if (asHtml) {
                    content = (theEditor.getContent() || '').trim();
                } else {
                    content = (theEditor.getContent({format: 'text'}) || '').trim();
                }
            } else {
                theEditor = getDescriptionEditorTextarea(editor_id);
                content = theEditor.val().trim();
            }

            return content;
        }

        function resetDescriptionEditorContent (editor_id) {
            var theEditor = getDescriptionEditor(editor_id);
            if (theEditor) {
                theEditor.setContent('');
            }

            var theEditorTextarea = getDescriptionEditorTextarea(editor_id);
            theEditorTextarea.val('');
        }
    });
})(window, window.document, jQuery, upstreamFrontEditLang);

(function (window, document, $, l, undefined) {
    function getCommentEditor (editor_id) {
        var TinyMceSingleton = window.tinyMCE ? window.tinyMCE : (window.tinymce ? window.tinymce : null);
        var theEditor = false;

        if (TinyMceSingleton !== null) {
            theEditor = TinyMceSingleton.get(editor_id);
        }

        return theEditor;
    }

    function getCommentEditorTextarea (editor_id) {
        return $('#' + editor_id);
    }

    function getEditorContent (editor_id, asHtml) {
        asHtml = typeof asHtml === 'undefined' ? true : (asHtml ? true : false);

        var theEditor = getCommentEditor(editor_id);
        var content = '';

        var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;
        if (isEditorInVisualMode) {
            if (asHtml) {
                content = (theEditor.getContent() || '').trim();
            } else {
                content = (theEditor.getContent({format: 'text'}) || '').trim();
            }
        } else {
            theEditor = getCommentEditorTextarea(editor_id);
            content = theEditor.val().trim();
        }

        return content;
    }

    function setFocus (editor_id) {
        var theEditor = getCommentEditor(editor_id);
        var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;
        if (isEditorInVisualMode) {
            theEditor.execCommand('mceFocus', false);
        } else {
            theEditor = getCommentEditorTextarea(editor_id);
            theEditor.focus();
        }
    }

    function appendCommentHtmlToDiscussion (commentHtml, wrapper) {
        var comment = $(commentHtml);
        comment.hide();

        commentHtml = comment.html()
            .replace(/\\'/g, '\'')
            .replace(/\\"/g, '"');

        comment.html(commentHtml);

        comment.prependTo(wrapper);

        $('[data-toggle="tooltip"]', comment).tooltip();

        comment.slideDown();
    }

    function resetCommentEditorContent (editor_id) {
        var theEditor = getCommentEditor(editor_id);
        if (theEditor) {
            theEditor.setContent('');
        }

        var theEditorTextarea = getCommentEditorTextarea(editor_id);
        theEditorTextarea.val('');
    }

    function isEditorEmpty (editor_id) {
        var theEditor = getCommentEditor(editor_id),
            content;

        var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;

        if (isEditorInVisualMode) {
            content = theEditor.getContent();
        } else {
            theEditor = getCommentEditorTextarea(editor_id);
            content = theEditor.val().trim();
        }

        // Check if maybe we have images in the content (those are not identified on the text format).
        // Replace images with placeholders
        content = content.replace(/<img.*\/>/g, '[image]');
        // Remove tags and special chars.
        content = content.replace(/<[^>]+>|&[a-z]+;/g, '');
        // Remove spaces.
        content = content.trim();

        return content === '';
    }

    $(document).ready(function () {
        $('.main_container').on('click', '[data-action="comments.add_comment"]', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var commentContent = '';

            var editor_id = self.data('editor_id');
            var commentContent = getEditorContent(editor_id, false);
            if (isEditorEmpty(editor_id)) {
                setFocus(editor_id);
                return;
            } else {
                commentContent = getEditorContent(editor_id, true);
            }

            var item_id, commentsWrapper;
            var wrapper = $(self.parents('.modal-body')[0]);

            if ($('.c-comments', wrapper).length > 0) {
                item_id = $('[name="editing"]', wrapper).val();
                commentsWrapper = $('.c-comments', wrapper);
            } else {
                commentsWrapper = $('.row .x_content > .c-comments');
            }

            var item_type = self.data('item_type');
            var item_title = self.data('item_title');

            $.ajax({
                type: 'POST',
                url: upstream.ajaxurl,
                data: {
                    action: 'upstream:project.add_comment',
                    nonce: self.data('nonce'),
                    project_id: $('#post_id').val(),
                    content: commentContent,
                    item_type: item_type,
                    item_id: item_id || null,
                    item_title: item_title || null,
                    teeny: 1
                },
                beforeSend: function () {
                    self
                        .addClass('disabled')
                        .text(l.LB_ADDING);
                },
                success: function (response) {
                    self
                        .removeClass('disabled')
                        .text(l.LB_ADD_COMMENT);

                    if (response.success) {
                        resetCommentEditorContent(editor_id);

                        if (item_type === 'project') {
                            var modalWrapper = self.parents('.modal-content');

                            $('button.close[data-dismiss="modal"]', modalWrapper).trigger('click');
                        }

                        appendCommentHtmlToDiscussion(response.comment_html, commentsWrapper);
                    } else {
                        console.error(response.error);

                        $('.modal-body', wrapper).prepend($('' +
                            '<div class="alert alert-danger alert-dismissible" role="alert">' +
                            '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>' +
                            response.error +
                            '</div>'));
                    }
                },
                error: function (request, textStatus, errorThrown) {
                    console.error(errorThrown);

                    self
                        .removeClass('disabled')
                        .text(l.LB_ADD_COMMENT);
                }
            });
        });

        $('.c-comments').on('click', '.o-comment a[data-action="comment.reply"]:not(.is-being-removed)', function (e) {
            e.preventDefault();

            var self = $(this);

            var commentWrapper = $(self.parents('.o-comment').get(0));
            if (!commentWrapper) return;

            var commentWrapperBody = $(commentWrapper.find('.o-comment__body').get(0));

            var modalWrapper = $('#modal-reply_comment');
            var replyCommentWrapper = $('.o-comment', modalWrapper);

            $('.o-comment__user_photo', replyCommentWrapper).attr('src', $('.o-comment__user_photo', commentWrapperBody).attr('src'));
            $('.o-comment__user_name', replyCommentWrapper).text($('.o-comment__user_name', commentWrapperBody).text());
            $('.o-comment__date', replyCommentWrapper).text($('.o-comment__date', commentWrapperBody).text());
            $('.o-comment__content', replyCommentWrapper).html($('.o-comment__content', commentWrapperBody).html());

            resetCommentEditorContent('_upstream_project_comment_reply');

            var commentParentModalWrapper = $(self.parents('.modal[data-type]'));

            var submitBtn = $('.btn[type="submit"]', modalWrapper);
            submitBtn.attr('data-nonce', self.data('nonce'));
            submitBtn.attr('data-comment_id', commentWrapper.attr('data-id'));

            var modal = new Modal({
                el: modalWrapper.get(0)
            });

            modal.on('show', function (theModal, evnt) {
                if (commentParentModalWrapper.length > 0) {
                    commentParentModalWrapper.css('display', 'none');
                    $('.modal-backdrop.fade.in').css('display', 'none');

                    modalWrapper.attr('data-modal_id', commentParentModalWrapper.attr('id'));
                } else {
                    modalWrapper.attr('data-modal_id', null);
                }
            });

            modal.on('hide', function (theModal, evnt) {
                if (commentParentModalWrapper.length > 0) {
                    commentParentModalWrapper.css('display', 'block');
                    $('.modal-backdrop.fade.in').css('display', 'block');
                }

                $('.o-comment__user_photo', replyCommentWrapper).attr('src', '');
                $('.o-comment__user_name', replyCommentWrapper).text('');
                $('.o-comment__date', replyCommentWrapper).text('');
                $('.o-comment__content', replyCommentWrapper).html('');

                resetCommentEditorContent('_upstream_project_comment_reply');

                fixBodyScrollIfNeeded();
            });

            modal.show();
        });

        $('.c-comments').on('click', '.o-comment a[data-action="comment.trash"]:not(.is-being-removed)', function (e) {
            e.preventDefault();

            if (!confirm(upstreamFrontEditLang['MSG_CONFIRM'])) return;

            var self = $(this);

            var comment = $(self.parents('.o-comment').get(0));

            var errorCallback = function () {
                comment.removeClass('is-loading is-mouse-over is-being-removed');
                self.html('<i class="fa fa-trash"></i>&nbsp;' + upstreamFrontEditLang['LB_DELETE']);
            };

            $.ajax({
                type: 'POST',
                url: upstream.ajaxurl,
                data: {
                    action: 'upstream:project.trash_comment',
                    nonce: self.data('nonce'),
                    project_id: $('#post_id').val(),
                    comment_id: comment.attr('data-id')
                },
                beforeSend: function () {
                    comment.addClass('is-loading is-being-removed is-mouse-over');
                    self.html('<i class="fa fa-trash"></i>&nbsp;' + upstreamFrontEditLang['LB_DELETING']);
                },
                success: function (response) {
                    if (!response.success) {
                        errorCallback();

                        var errorMessage = response.error || 'Invalid request/response.';

                        console.error(errorMessage);
                        alert(errorMessage);
                    } else {
                        comment.css('background-color', '#E74C3C');
                        comment.slideUp({
                            duration: 250,
                            complete: function () {
                                comment.remove();
                            }
                        });
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    errorCallback();

                    console.error({
                        text_status: textStatus,
                        error_message: errorThrown
                    });
                }
            });
        });

        $('.c-comments').on('click', '.o-comment[data-id] a[data-action="comment.approve"]', function (e) {
            e.preventDefault();

            var self = $(this);

            var comment = $(self.parents('.o-comment[data-id]').get(0));
            if (!comment.length) {
                console.error('Comment wrapper not found.');
                return;
            }

            var errorCallback = function () {
                comment
                    .removeClass('is-loading is-mouse-over is-being-approved')
                    .css('background-color', '');
                self.html('<i class="fa fa-eye"></i>&nbsp;' + upstreamFrontEditLang['LB_APPROVE']);
            };

            $.ajax({
                type: 'POST',
                url: upstream.ajaxurl,
                data: {
                    action: 'upstream:project.approve_comment',
                    nonce: self.data('nonce'),
                    project_id: $('#post_id').val(),
                    comment_id: comment.attr('data-id'),
                    teeny: 1
                },
                beforeSend: function () {
                    comment.addClass('is-loading is-mouse-over is-being-approved');
                    self.html('<i class="fa fa-eye"></i>&nbsp;' + upstreamFrontEditLang['LB_APPROVING']);
                },
                success: function (response) {
                    if (response.error) {
                        errorCallback();
                        console.error(response.error);
                        alert(response.error);
                    } else {
                        if (!response.success) {
                            errorCallback();
                            console.error('Something went wrong.');
                        } else {
                            comment.removeClass('s-status-unapproved')
                                .addClass('s-status-approved');

                            var newComment = $(response.comment_html);
                            var newCommentBody = $('.o-comment__body', newComment);

                            $('[data-toggle="tooltip"]', newCommentBody).tooltip();

                            $(comment.find('.o-comment__body').get(0)).replaceWith(newCommentBody);
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    errorCallback();

                    var response = {
                        text_status: textStatus,
                        errorThrown: errorThrown
                    };

                    console.error(response);
                },
                complete: function () {
                    comment.removeClass('is-loading is-mouse-over is-being-approved');
                }
            });
        });

        $('.c-comments').on('click', '.o-comment[data-id] a[data-action="comment.unapprove"]', function (e) {
            e.preventDefault();

            var self = $(this);

            var comment = $(self.parents('.o-comment[data-id]').get(0));
            if (!comment.length) {
                console.error('Comment wrapper not found.');
                return;
            }

            var errorCallback = function () {
                comment
                    .removeClass('is-loading is-mouse-over is-being-unapproved')
                    .css('background-color', '');
                self.html('<i class="fa fa-eye-slash"></i>&nbsp;' + upstreamFrontEditLang['LB_UNAPPROVE']);
            };

            $.ajax({
                type: 'POST',
                url: upstream.ajaxurl,
                data: {
                    action: 'upstream:project.unapprove_comment',
                    nonce: self.data('nonce'),
                    project_id: $('#post_id').val(),
                    comment_id: comment.attr('data-id'),
                    teeny: 1
                },
                beforeSend: function () {
                    comment.addClass('is-loading is-mouse-over is-being-unapproved');
                    self.html('<i class="fa fa-eye-slash"></i>&nbsp;' + upstreamFrontEditLang['LB_UNAPPROVING']);
                },
                success: function (response) {
                    if (response.error) {
                        errorCallback();
                        console.error(response.error);
                        alert(response.error);
                    } else {
                        if (!response.success) {
                            errorCallback();
                            console.error('Something went wrong.');
                        } else {
                            comment.removeClass('s-status-approved')
                                .addClass('s-status-unapproved');

                            var newComment = $(response.comment_html);
                            var newCommentBody = $('.o-comment__body', newComment);

                            $('[data-toggle="tooltip"]', newCommentBody).tooltip();

                            $(comment.find('.o-comment__body').get(0)).replaceWith(newCommentBody);
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    errorCallback();

                    var response = {
                        text_status: textStatus,
                        errorThrown: errorThrown
                    };

                    console.error(response);
                },
                complete: function () {
                    comment.removeClass('is-loading is-mouse-over is-being-unapproved');
                }
            });
        });

        $('#modal-reply_comment button[type="submit"]').on('click', function (e) {
            e.preventDefault();

            var self = $(this);
            var parent_id = self.attr('data-comment_id');
            var editor_id = '_upstream_project_comment_reply';

            var commentContent = getEditorContent(editor_id, false);
            if (isEditorEmpty(editor_id)) {
                setFocus(editor_id);
                return;
            } else {
                commentContent = getEditorContent(editor_id, true);
            }

            var commentEl = $('.c-comments[data-nonce] .o-comment[data-id="' + parent_id + '"]');
            var commentsEl = $(commentEl.parents('.c-comments'));
            commentsEl = $(commentsEl.get(0));

            var itemEditModal = $(commentsEl.parents('.modal[data-type]'));

            var item_type, item_id;

            if (itemEditModal.length > 0) {
                item_type = itemEditModal.attr('data-type');
                item_id = $('[name="editing"]', itemEditModal).val();
            } else {
                item_type = 'project';
                item_id = null;
            }

            var btnIconHtml = '<i class="fa fa-reply"></i>&nbsp;';

            $.ajax({
                type: 'POST',
                url: upstream.ajaxurl,
                data: {
                    action: 'upstream:project.add_comment_reply',
                    nonce: self.attr('data-nonce'),
                    project_id: $('#post_id').val(),
                    parent_id: parent_id || null,
                    item_type: item_type,
                    item_id: item_id || null,
                    content: commentContent,
                    teeny: 1
                },
                beforeSend: function () {
                    self
                        .addClass('disabled')
                        .html(btnIconHtml + l.LB_REPLYING);
                },
                success: function (response) {
                    var modalWrapper = self.parents('.modal-content');

                    if (response.success) {
                        resetCommentEditorContent(editor_id);

                        $('button.close[data-dismiss="modal"]', modalWrapper).trigger('click');

                        appendCommentHtmlToDiscussion(response.comment_html, commentEl.find('.o-comment-replies').get(0));

                        self.data('nonce', '');
                        self.data('comment_id', '');
                    } else {
                        console.error(response.error);

                        $('.modal-body', modalWrapper).prepend($('' +
                            '<div class="alert alert-danger alert-dismissible" role="alert">' +
                            '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>' +
                            response.error +
                            '</div>'));
                    }

                    self
                        .removeClass('disabled')
                        .html(btnIconHtml + l.LB_REPLY);
                },
                error: function (request, textStatus, errorThrown) {
                    console.error(errorThrown);

                    self
                        .removeClass('disabled')
                        .html(btnIconHtml + l.LB_REPLY);
                }
            });

            return false;
        });

        var mediaManagerFrame = null;
        $('.o-media-library-btn').on('click', function (e) {
            e.preventDefault();

            var self = $(this);
            var wrapper = self.parent();
            var field_name = self.attr('data-name');

            if (!mediaManagerFrame) {

                if (typeof wp.media === "undefined") {
                    alert("WordPress media javascript has not been loaded.");
                }
                else if (typeof wp.media.frames === "undefined") {
                    alert("WordPress media frames javascript has not been loaded.");
                }

                mediaManagerFrame = wp.media.frames.file_frame = wp.media({
                    title: self.attr('data-title'),
                    button: {
                        text: l.MSG_USE_THIS_FILE,
                        multiple: false
                    }
                });

                mediaManagerFrame.on('select', function () {
                    var file = mediaManagerFrame.state().get('selection').first().toJSON();

                    var isImage = (new RegExp(/^image\//i)).test(file.mime);
                    var file_url = isImage ? file.sizes.full.url : file.url;

                    var mediaWrapper = $('' +
                        '<div class="c-media-preview media">' +
                        '<div class="media-left">' +
                        '<a href="#" target="_blank" rel="noopener noreferrer">' +
                        '<img class="media-object" src="" alt="">' +
                        '</a>' +
                        '</div>' +
                        '<div class="media-body">' +
                        '<h5 class="media-heading"></h5>' +
                        '<div>' +
                        '<ul>' +
                        '<li>' +
                        '<a href="#" class="btn btn-xs btn-default" data-action="file.remove" data-field-name="' + field_name + '"><i class="fa fa-trash"></i> ' + l.LB_REMOVE + '</a>' +
                        '</li>' +
                        '</ul>' +
                        '</div>' +
                        '<input type="hidden" name="data[' + field_name + '_id]" value="' + file.id + '">' +
                        '<input type="hidden" name="data[' + field_name + ']" value="' + file_url + '">' +
                        '</div>' +
                        '</div>'
                    );

                    $('[data-action="file.remove"]', mediaWrapper).on('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        $($(this).parents('.file-preview').get(0)).html('');
                    });

                    $('a[target="_blank"]', mediaWrapper).attr('href', file_url);

                    var imgUrl = file.icon;
                    if (isImage) {
                        if (file.sizes.thumbnail && file.sizes.thumbnail.url) {
                            imgUrl = file.sizes.thumbnail.url;
                        } else if (file.sizes.full && file.sizes.full.url) {
                            imgUrl = file.sizes.full.url;
                        }
                    }

                    $('img', mediaWrapper).attr('src', imgUrl);
                    $('h5', mediaWrapper).text(file.filename);

                    $('.file-preview', wrapper).html(mediaWrapper).show();
                });
            }

            mediaManagerFrame.open();
        });

        $('.modal .btn[data-action="delete"]').on('click', function () {
            var self = $(this);

            var modal = $(self.parents('.modal').get(0));
            var type = modal.attr('data-type');
            var post_id = $('#post_id').val();
            var item_id = $('.o-modal-form input[name="editing"]', modal).val();

            if (
                post_id &&
                item_id &&
                type &&
                confirm(upstreamFrontEditLang['MSG_CONFIRM'])
            ) {
                $.ajax({
                    type: 'POST',
                    url: upstream.ajaxurl,
                    data: {
                        action: 'upstream.frontend-edit:delete_item',
                        nonce: self.attr('data-nonce'),
                        post_id: post_id,
                        item_id: item_id,
                        item_type: type
                    },
                    success: function (response) {

                        if (typeof (response.message !== "undefined")) {
                            alert(response.message);
                        }

                        window.location.reload();
                    }
                });
            }
        });

        $('#delete_project').on('click', function () {
            var self = $(this);

            var modal = $(self.parents('.modal').get(0));
            var type = modal.attr('data-type');

            var post_id = $('#post_id').val();
            if ($('table#projects').length > 0 && $('#upstream_editing_id').length > 0) {
                post_id = $('#upstream_editing_id').val();
            }

            if (
                post_id &&
                confirm(upstreamFrontEditLang['MSG_CONFIRM'])
            ) {
                $.ajax({
                    type: 'POST',
                    url: upstream.ajaxurl,
                    data: {
                        action: 'upstream.frontend-edit:delete_project',
                        nonce: self.attr('data-nonce'),
                        id: post_id
                    },
                    success: function (response) {
                        //window.location.reload();
                        if (response == "error") alert("An error occurred.");
                        else {
                            window.location.href = response;
                        }
                    }
                });
            }
        });

        function setErrorMessageToFieldWithName (message, targetEl) {
            targetEl.addClass('is-invalid');

            var wrapper = targetEl.parent();
            wrapper.addClass('has-error');

            $('.o-error-message', wrapper).remove();

            var errorMessage = $('<p></p>', {
                class: 'o-error-message'
            }).text(message);

            wrapper.append(errorMessage);
        }

        function removeErrorMessageToFieldWithName (targetEl) {
            var wrapper = targetEl.parent();

            wrapper.removeClass('has-error');
            $('.o-error-message', wrapper).remove();

            targetEl.removeClass('is-invalid');
        }

        function validateIntervalBetweenDatesFields (startDateEl, endDateEl) {

            if (!startDateEl || !endDateEl) return true;

            // RSD: the code below doesn't work because val() returns a date
            // this is a fix for frontend issue 141
            //var startDateTimestampEl = $('[id$="_timestamp"]', startDateEl.val());
            //var endDateTimestampEl = $('[id$="_timestamp"]', endDateEl.val());

            var startDateTimestampEl = $('[id="'+startDateEl.attr('id')+'_timestamp"]');
            var endDateTimestampEl = $('[id="'+endDateEl.attr('id')+'_timestamp"]');

            var sdate = new Date(startDateEl.val());
            var edate = new Date(endDateEl.val());

            var startDateTimestamp = new Date(sdate).getTime();
            var endDateTimestamp = new Date(edate).getTime();

            if (!startDateTimestampEl.length || !endDateTimestampEl.length) return true;

            if (isNaN(startDateTimestamp)
                || isNaN(endDateTimestamp)
                || startDateTimestamp <= 0
                || endDateTimestamp <= 0
            ) {
                return true;
            }

            return startDateTimestamp <= endDateTimestamp;
        }

        $('.o-datepicker[data-elt]').on('change', function (e) {
            var goodDate = true;

            var self = $(this);
            var nameReg = self.attr('name').match(/^data\[(\w+)\]/i);
            if (nameReg && nameReg.length > 1) {
                var endDateEl = $('[name="data[' + self.attr('data-elt') + ']"]', $(self.parents('form').get(0)));
                goodDate = validateIntervalBetweenDatesFields(self, endDateEl);
            }

            if (goodDate) {
                removeErrorMessageToFieldWithName(self);

                if (typeof endDateEl !== 'undefined' || endDateEl) {
                    removeErrorMessageToFieldWithName(endDateEl);
                }
            } else {
                setErrorMessageToFieldWithName(l.MSG_INVALID_DATE, self);
            }
        });

        $('.o-datepicker[data-egt]').on('change', function (e) {
            var goodDate = true;

            var self = $(this);
            var nameReg = self.attr('name').match(/^data\[(\w+)\]/i);
            if (nameReg && nameReg.length > 1) {
                var startDateEl = $('[name="data[' + self.attr('data-egt') + ']"]', $(self.parents('form').get(0)));

                goodDate = validateIntervalBetweenDatesFields(startDateEl, self);
            }

            if (goodDate) {
                removeErrorMessageToFieldWithName(self);

                if (typeof startDateEl !== 'undefined' || startDateEl) {
                    removeErrorMessageToFieldWithName(startDateEl);
                }
            } else {
                setErrorMessageToFieldWithName(l.MSG_INVALID_DATE, self);
            }
        });

        $('.o-modal-form').on('submit', function (e) {
            var self = $(this);

            var invalidatedFields = $('.is-invalid', self);
            if (invalidatedFields.length > 0) {
                e.preventDefault();
                e.stopPropagation();

                $(invalidatedFields.get(0)).focus();

                return false;
            }
        });
    });
})(window, window.document, jQuery, upstreamFrontEditLang);
