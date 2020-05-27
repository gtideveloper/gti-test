(function (window, document, $, $l, undefined) {
    $(document).ready(function () {

        function clearFieldErrorsOnForm (form) {
            if (!form || form.length === 0) return;

            $('.parsley-errors-list', form).remove();
            $('.parsley-error', form).removeClass('parsley-error');
        }

        function onModalAddOpen () {
            var self = $(this);
            var metabox = $(self.parents('.x_panel')[0]);
            var table = $('.x_content table.dataTable', metabox);
            var modal = $(self.attr('data-target'));
            var form = $('.tab-content form.add', modal);

            clearFieldErrorsOnForm(form);

            setTimeout(function () {
                $('thead th[data-field-name][data-field-type="file"]', table).each(function () {
                    var th = $(this);
                    var columnName = th.attr('data-field-name');

                    var input = $('[name="' + columnName + '"]', form);
                    $('.file-preview', input.parent()).html('');
                });

                $('.form-group[data-field-type="checkbox"] .up-c-input-checkbox').each(function () {
                    var wrapper = $(this);

                    $('input[type="checkbox"]', wrapper).prop('checked', false);
                    $('input[type="checkbox"][data-selected-by-default]', wrapper).prop('checked', true);
                });

                $('.form-group[data-field-type="radio"] .up-c-input-radio').each(function () {
                    var wrapper = $(this);

                    $('input[type="radio"]', wrapper).prop('checked', false);
                    $('input[type="radio"][data-selected-by-default][value]', wrapper).prop('checked', true);
                });
            }, 50);
        }

        /**
         * Make sure the default value is loaded for fields in the adding form.
         */
        $('body').on('openmodal', function (event, modal) {
            // Ignore if we are editing anything.
            if ($(modal.el).find('input#upstream_form_type').val() !== 'add') {
                return;
            }

            setTimeout(function () {
                // Pre-select all the default values.
                var fields = $(modal.el).find('select,input,textarea'),
                    defaultValue,
                    options,
                    type,
                    field,
                    $field,
                    currentDefaultValue;

                if (fields.length > 0) {
                    for (var i = 0; i < fields.length; i++) {
                        field = fields[i];
                        $field = $(field);

                        defaultValue = $field.data('default');

                        if (defaultValue === undefined) {
                            defaultValue = $field.data('def');

                            if (defaultValue === undefined) {
                                if ($field.data('selectedByDefault') !== undefined) {
                                    defaultValue = $field.attr('value');
                                }
                            }
                        }

                        if (defaultValue !== null && defaultValue !== undefined && defaultValue !== '') {
                            if ($field[0].nodeName === 'select') {
                                defaultValue = defaultValue.split(',');
                            } else {
                                defaultValue = [defaultValue];
                            }

                            if (defaultValue.length > 0) {
                                for (var k = 0; k < defaultValue.length; k++) {
                                    currentDefaultValue = defaultValue[k];

                                    if (field.tagName === 'SELECT') {
                                        options = $field.find('option');

                                        if (options.length > 0) {
                                            $.each(options, function (i, option) {
                                                if ($(option).prop('value') === currentDefaultValue) {
                                                    $(option).attr('selected', true);

                                                    $field.trigger('change').trigger('chosen:updated');
                                                    ;
                                                }
                                            });
                                        }
                                    } else if (field.tagName === 'INPUT') {
                                        type = $field.prop('type').toLowerCase();

                                        if (type === 'text') {
                                            $field.val(currentDefaultValue);
                                        } else if (type === 'radio' || type === 'checkbox') {
                                            if ($field.prop('value') === currentDefaultValue) {
                                                $field.attr('checked', true);
                                                $field.trigger('change').trigger('chosen:updated');
                                                ;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Initialize color pickers.
                        if ($field.parent().data('field-type') === 'colorpicker') {
                            if (!$field.hasClass('wp-color-picker')) {
                                $field.attr('type', 'text');

                                var args = {
                                    palettes: $field.attr('data-palettes')
                                };

                                $field.wpColorPicker(args);

                                var wrapper = $($field.parents('.wp-picker-container'));
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

                            var selectedColor = $field.val();

                            if (selectedColor) {
                                $field.iris('color', selectedColor);
                            } else {
                                $field.val('');

                                var wrapper = field.parents('.wp-picker-container');
                                wrapper.find('.wp-color-result').css('background-color', '');
                            }
                        }
                    }
                }
            }, 500);
        });

        $('.btn[data-toggle="up-modal"][data-target][data-action="add"]').on('click', onModalAddOpen);

        function onModalEditOpen () {
            var self = $(this);

            var tr = $(self.parents('tr')[0]);
            var modal = $(self.attr('data-target'));
            var form = $('.tab-content form.add', modal);

            clearFieldErrorsOnForm(form);

            setTimeout(function () {
                $('td[data-name][data-value][data-field-type="colorpicker"]', tr).each(function () {
                    var td = $(this);
                    var field = $('input[name="' + td.attr('data-name') + '"]', form);

                    if (field.length > 0) {
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

                        var selectedColor = td.attr('data-value');

                        if (selectedColor) {
                            field.iris('color', selectedColor);
                        } else {
                            field.val('');

                            var wrapper = field.parents('.wp-picker-container');
                            wrapper.find('.wp-color-result').css('background-color', '');
                        }
                    }
                });

                $('td[data-name][data-value][data-field-type="checkbox"]', tr).each(function () {
                    var td = $(this);
                    var fieldName = td.attr('data-name');
                    var fieldWrapper = $('input[name="' + fieldName + '[]"]', form);

                    if (fieldWrapper.length === 0) {
                        return;
                    }

                    fieldWrapper = $($(fieldWrapper[0]).parents('.up-c-input-checkbox'));

                    var selectedOptions = td.attr('data-value').split('#');

                    var options = $('input[type="checkbox"][name="' + fieldName + '[]"]', fieldWrapper);
                    options.prop('checked', false);

                    if (selectedOptions.length > 0) {
                        $.each(options, function (cbx_index, cbx) {
                            cbx = $(cbx);

                            if (selectedOptions.indexOf(cbx.val()) >= 0) {
                                cbx.prop('checked', true);
                            } else {
                                cbx.prop('checked', false);
                            }
                        });
                    } else {
                        var noneOption = $('input[type="checkbox"][name="' + fieldName + '[]"][value=""]', fieldWrapper);
                        if (noneOption.length > 0) {
                            noneOption.prop('checked', true);
                        }
                    }
                });

                $('td[data-name][data-value][data-field-type="radio"]', tr).each(function () {
                    var td = $(this);
                    var fieldName = td.attr('data-name');
                    var radioOptions = $('input[name="' + fieldName + '"]', form);

                    if (radioOptions.length === 0) {
                        return;
                    }

                    radioOptions.prop('checked', false);

                    var fieldWrapper = $($(radioOptions[0]).parents('.up-c-input-radio'));

                    var selectedOption = td.attr('data-value');
                    if (selectedOption.length > 0) {
                        $('input[name="' + fieldName + '"][value="' + selectedOption + '"]', form).prop('checked', true);
                    } else if ($('input[name="' + fieldName + '"][value=""]', form).length > 0) {
                        $('input[name="' + fieldName + '"][value="__none__"]', form).prop('checked', true);
                        $('input[name="' + fieldName + '"][value=""]', form).prop('checked', true);
                    }
                });

                $('td[data-name][data-value][data-field-type="file"]', tr).each(function () {
                    var td = $(this);
                    var fieldName = td.attr('data-name');
                    var field = $('[name="' + fieldName + '"]', form);
                    var wrapper = $(field.parents('.form-group'));

                    field.attr('required', null).removeClass('required');

                    var attachment_id = td.attr('data-attachment-id');
                    attachment_id = !isNaN(parseInt(attachment_id)) && attachment_id >= 0 ? attachment_id : '';
                    wrapper.find('input[type="hidden"][name="' + fieldName + '_id"]').val(attachment_id);

                    var anchor = $('a', td).clone();
                    var filePreview = wrapper.find('.file-preview');
                    filePreview.html(anchor);

                    if (anchor.length > 0) {
                        var removeAnchor = $('<button></button>', {
                            type: 'button',
                            class: 'btn btn-xs btn-default up-o-custom-field-file-btn',
                            'data-action': 'file.remove',
                            'data-field-name': fieldName
                        }).html('<i class="fa fa-trash"></i> ' + $l.LB_REMOVE);

                        removeAnchor.on('click', function (e) {

                            e.preventDefault();

                            var undoAnchor = $('<button></button>', {
                                type: 'button',
                                class: 'btn btn-xs btn-default up-o-custom-field-file-btn',
                                'data-action': 'file.remove.undo',
                                'data-field-name': fieldName
                            }).html('<i class="fa fa-undo"></i> ' + $l.LB_UNDO);

                            undoAnchor.on('click', function (e) {
                                e.preventDefault();

                                undoAnchor.remove();

                                removeAnchor.show();
                                $('.up-c-custom-field', filePreview).show();
                                $('input', filePreview).remove();
                            });

                            $('.up-c-custom-field', filePreview).hide();

                            filePreview.append(undoAnchor);
                            filePreview.append($('<input/>', {
                                type: 'hidden',
                                name: anchor.attr('data-field-name') + '_remove',
                                value: 1
                            }));

                            removeAnchor.hide();
                        });

                        filePreview.append(removeAnchor);
                    }
                });

                $('td[data-name][data-value][data-field-type="category"],td[data-name][data-value][data-field-type="tag"],td[data-name][data-value][data-field-type="user"]', tr).each(function () {
                    var td = $(this);
                    var fieldName = td.attr('data-name');
                    var theField = $('select[name="' + fieldName + '"]', form);

                    if (theField.length === 0) {
                        fieldName += '[]';
                        theField = $('select[name="' + fieldName + '"]', form);
                        if (theField.length === 0) return;
                    }

                    var selectedOptions = td.attr('data-value').split('#') || [];

                    $('option', theField).prop('selected', false);

                    if (selectedOptions.length > 0) {
                        $.each(selectedOptions, function (optionIndex, optionValue) {
                            var option = $('option[value="' + optionValue + '"]', theField);
                            if (option.length > 0) {
                                option.prop('selected', true);
                            }
                        });
                    }
                });

                $('td[data-name][data-value][data-field-type="autoincrement"]', tr).each(function () {
                    var td = $(this);
                    var fieldName = td.attr('data-field-name');
                    var fieldId = td.attr('data-field-id');

                    var valueWrapper = $('up-autoincrement', td);

                    var selectedOption = td.attr('data-value');
                    if (selectedOption.length > 0) {
                        $('input[name="' + fieldName + '"][value="' + selectedOption + '"]', form).prop('checked', true);
                    } else if ($('input[name="' + fieldName + '"][value=""]', form).length > 0) {
                        $('input[name="' + fieldName + '"][value="__none__"]', form).prop('checked', true);
                        $('input[name="' + fieldName + '"][value=""]', form).prop('checked', true);
                    }
                });
            }, 250);
        }

        $('a[data-toggle="up-modal"][data-target][data-id]').on('click', onModalEditOpen);

        $('.up-c-custom-field-form-group[data-field-type="checkbox"] .btn[data-action="toggle-selection"]').on('click', function (e) {
            e.preventDefault();

            var self = $(this);
            var wrapper = $('.up-c-input-checkbox', self.parent());
            var firstCbx = $($('input[type="checkbox"]', wrapper).get(0));

            var selectAll = firstCbx.prop('checked');
            $('input[type="checkbox"]', wrapper).prop('checked', !selectAll);
        });

        $('.modal.modal-add[data-type] form .form-group button[type="submit"]').on('click', function (e) {
            var form = $($(this).parents('form')[0]);

            var requiredCheckboxFields = $('.form-group.is-required[data-field-type="checkbox"]', form);
            if (requiredCheckboxFields.length > 0) {
                for (var wrapperIndex = 0; wrapperIndex < requiredCheckboxFields.length; wrapperIndex++) {
                    var wrapper = $(requiredCheckboxFields[wrapperIndex]);

                    var selectedBoxes = $('input[type="checkbox"]', wrapper).serializeArray();
                    if (selectedBoxes.length === 0) {
                        $($('input[type="checkbox"]', wrapper).get(0)).focus();

                        e.preventDefault();
                        e.stopPropagation();

                        return false;
                    }
                }
            }

            var requiredRadioFields = $('.form-group.is-required[data-field-type="radio"]', form);
            if (requiredRadioFields.length > 0) {
                for (var wrapperIndex = 0; wrapperIndex < requiredRadioFields.length; wrapperIndex++) {
                    var wrapper = $(requiredRadioFields[wrapperIndex]);

                    var selectedBoxes = $('input[type="radio"]', wrapper).serializeArray();
                    if (selectedBoxes.length === 0) {
                        $($('input[type="radio"]', wrapper).get(0)).focus();

                        e.preventDefault();
                        e.stopPropagation();

                        return false;
                    }
                }
            }
        });

        var mediaManagerFrame = null;
        $('.o-btn-media').on('click', function (e) {
            e.preventDefault();

            var self = $(this);
            var wrapper = $(self.parent());
            var field_name = self.attr('data-name');

            if (!mediaManagerFrame) {
                mediaManagerFrame = wp.media.frames.file_frame = wp.media({
                    title: self.attr('data-title'),
                    button: {
                        text: $l.MSG_USE_THIS_FILE,
                        multiple: false
                    }
                });

                mediaManagerFrame.on('select', function () {

                    $('body').addClass('modal-open');

                    var file = mediaManagerFrame.state().get('selection').first().toJSON();

                    var isImage = (new RegExp(/^image\//i)).test(file.mime);
                    var file_url = isImage ? file.sizes.full.url : file.url;

                    $('[name="' + field_name.replace(']', '_id]') + '"]', wrapper).val(file.id);
                    $('[name="' + field_name + '"]', wrapper).val(file_url);

                    var previewWrapper = $('.file-preview', wrapper);

                    previewWrapper.html('');

                    var a = $('<a></a>', {
                        href: file_url,
                        target: '_blank',
                        rel: 'noopener noreferrer',
                        class: 'up-c-custom-field',
                        'data-type': 'file',
                        'data-field-name': field_name
                    }).css('display', 'inline');

                    var img = $('<img>', {
                        src: isImage ? file.sizes.thumbnail.url : file.icon,
                        width: 32,
                        height: 32,
                        class: 'itemfile'
                    });

                    a.append(img);
                    a.append($('<span><i class="fa fa-external-link"></i> ' + file.filename + '</span>'));
                    previewWrapper.append(a);

                    var btn = $('<button></button>', {
                        type: 'button',
                        class: 'btn btn-xs btn-default up-o-custom-field-file-btn',
                        'data-action': 'file.remove',
                        'data-field-name': field_name
                    });

                    btn.on('click', '.btn[data-action="file.remove"]', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        var self = $(this);
                        var field_name = self.data('field-name');
                        self.attr('data-action', 'file.remove.undo');
                        self.html('<i class="fa fa-refresh"></i> ' + $l.LB_UNDO);

                        $('[name="data[' + field_name + '__remove]"]', wrapper).remove();
                        wrapper.append($('<input type="hidden" name="data[@' + field_name + '__remove]" value="1">'));
                        $('.media-left,h5', wrapper).hide();
                    });

                    btn.on('click', '[data-action="file.remove.undo"]', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        var self = $(this);
                        var field_name = self.data('field-name');
                        self.attr('data-action', 'file.remove');
                        self.html('<i class="fa fa-trash"></i> ' + $l.LB_REMOVE);

                        $('[name="data[@' + field_name + '__remove]"]', wrapper).remove();
                        $('.media-left,h5', wrapper).show();
                    });

                    btn.html('<i class="fa fa-trash"></i> ' + $l.LB_REMOVE);

                    previewWrapper.append(btn);

                    mediaManagerFrame = null;
                });
            }

            mediaManagerFrame.open();
        });
    });
})(window, window.document, jQuery || null, $l || {});
