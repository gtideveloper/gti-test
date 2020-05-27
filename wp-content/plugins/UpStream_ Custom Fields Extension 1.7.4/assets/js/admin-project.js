(function (window, document, $, undefined) {
    $(document).ready(function () {
        $('.cmb-row.cmb-repeat-group-wrap.cmb-type-group.cmb-repeat[data-fieldtype] .cmb-row .cmb-add-row button').each(function () {
            var self = $(this);

            self.on('click', function (e) {
                setTimeout(function () {
                    var wrapper = $(self.parents('.cmb-field-list')[0]);
                    var newRow = $('.postbox[data-iterator]:last', wrapper);

                    // Default values
                    var checkboxesFields = $('div.o-up-custom-field[data-field-type="checkbox"]', newRow);
                    if (checkboxesFields.length > 0) {
                        $('input[type="checkbox"]', checkboxesFields).prop('checked', false);
                        $('input[type="checkbox"][data-selected-by-default]', checkboxesFields).prop('checked', true);
                    }

                    $('input[type="radio"]', newRow).prop('checked', false);
                    $('input[type="radio"][data-selected-by-default]', newRow).prop('checked', true);

                    $('input[type="text"][data-default]', newRow).each(function () {
                        $(this).val($(this).data('default'));
                    });

                    $('input.wp-color-picker', newRow).each(function () {
                        $(this).iris('color', $(this).val());
                    });
                }, 150);
            });
        });

        $('#publish').on('click', function (e) {
            var requiredCheckboxFields = $('.cmb-row.postbox:visible div.o-up-custom-field[data-field-type="checkbox"] .cmb2-checkbox-list.is-required');
            if (requiredCheckboxFields.length > 0) {
                for (var wrapperIndex = 0; wrapperIndex < requiredCheckboxFields.length; wrapperIndex++) {
                    var wrapper = $(requiredCheckboxFields[wrapperIndex]);

                    var selectedBoxes = $('input[type="checkbox"]', wrapper).serializeArray();
                    if (selectedBoxes.length === 0) {
                        var mainWrapper = $(wrapper.parents('.postbox'));
                        if (mainWrapper.length > 0) {
                            $('.handlediv', mainWrapper).attr('aria-expanded', 'true');
                            mainWrapper.removeClass('closed is-new-closed');
                        }

                        $($('input[type="checkbox"]', wrapper).get(0)).focus();

                        e.preventDefault();
                        e.stopPropagation();

                        return false;
                    }
                }
            }

            var requiredRadioFields = $('.cmb-row.postbox:visible div.o-up-custom-field[data-field-type="radio"] .cmb2-radio-list.is-required');
            if (requiredRadioFields.length > 0) {
                for (var wrapperIndex = 0; wrapperIndex < requiredRadioFields.length; wrapperIndex++) {
                    var wrapper = $(requiredRadioFields[wrapperIndex]);

                    var selectedRadio = $('input[type="radio"]', wrapper).serializeArray();
                    if (selectedRadio.length === 0) {
                        var mainWrapper = $(wrapper.parents('.postbox'));
                        if (mainWrapper.length > 0) {
                            $('.handlediv', mainWrapper).attr('aria-expanded', 'true');
                            mainWrapper.removeClass('closed is-new-closed');
                        }

                        $($('input[type="radio"]', wrapper).get(0)).focus();

                        e.preventDefault();
                        e.stopPropagation();

                        return false;
                    }
                }
            }

            /*
            if ($('#_upstream_project_tasks_repeat .task-title').length == 1 && $('#_upstream_project_tasks_repeat .task-title').val() == '') {
                $('#_upstream_project_tasks_repeat').remove();
            }
            if ($('#_upstream_project_bugs_repeat .bug-title').length == 1 && $('#_upstream_project_bugs_repeat .bug-title').val() == '') {
                $('#_upstream_project_bugs_repeat').remove();
            }
            if ($('#_upstream_project_files_repeat .file-title').length == 1 && $('#_upstream_project_files_repeat .file-title').val() == '') {
                $('#_upstream_project_files_repeat').remove();
            }

             */

            $('#_upstream_project_tasks_repeat .postbox:hidden select').removeAttr('required');
            $('#_upstream_project_tasks_repeat .postbox:hidden input').removeAttr('required');

            $('#_upstream_project_files_repeat .postbox:hidden select').removeAttr('required');
            $('#_upstream_project_files_repeat .postbox:hidden input').removeAttr('required');

            $('#_upstream_project_bugs_repeat .postbox:hidden select').removeAttr('required');
            $('#_upstream_project_bugs_repeat .postbox:hidden input').removeAttr('required');

        });

        (function () {
            function randomString (len, charSet) {
                charSet = charSet || 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

                var randomString = '';
                for (var i = 0; i < len; i++) {
                    var randomPoz = Math.floor(Math.random() * charSet.length);
                    randomString += charSet.substring(randomPoz, randomPoz + 1);
                }

                return randomString;
            }

            var customFieldsCache = [];

            $('.up-c-custom-field').each(function () {
                var self = $(this);
                var customFieldWrapper = $(self.parents('.row.cmb2GridRow').get(0));

                var uid = customFieldWrapper.attr('data-uid');
                if (!uid) {
                    uid = randomString(7);

                    customFieldWrapper.attr('data-uid', uid);

                    var wrapper = $(customFieldWrapper.parents('.cmb-field-list').get(0));
                    customFieldWrapper.appendTo($('.up-c-tab-content-data', wrapper));
                }
            });
        })();

        $('.cmb-row.up-o-select2-wrapper select.cmb2_select').select2();
    });
})(window, window.document, jQuery);
