(function (window, document, $, $plg, undefined) {
    var disablerInterval = setInterval(function () {
        var submitButtons = $('#publish, #save-post');
        if (submitButtons.length > 0) {
            submitButtons.addClass('disabled').attr('disabled', 'disabled');
            clearInterval(disablerInterval);
            disablerInterval = null;
        }
    }, 50);

    $(document).ready(function () {
        $('#title').attr('required', 'required');

        setTimeout(function () {
            $('#publish, #save-post').removeClass('disabled').attr('disabled', null);
        }, 100);

        $('#publish').on('click', function (e) {
            var fieldType = $('[name="_upstream__custom-fields:type"]').val();
            $('#cmb2-metabox-_upstream__custom-fieldsargs .c-field-type-args[data-type!="' + fieldType + '"]').remove();

            var requiredFields = $('.cmb2-postbox [required="required"]:focusable');
            for (var requiredFieldsIds = 0; requiredFieldsIds < requiredFields.length; requiredFieldsIds++) {
                var requireEl = $(requiredFields[requiredFieldsIds]);
                var value = (requireEl.val() || '').trim();

                if (value.length === 0) {
                    var wrapper = requireEl.parents('.postbox.cmb-row.cmb-repeatable-grouping');
                    if (wrapper.hasClass('closed')) {
                        wrapper.removeClass('closed');
                    }
                }
            }
        });

        $('[name="_upstream__custom-fields:type"]').on('change', function (e) {
            e.preventDefault();

            var self = $(this);
            var fieldType = self.val();
            var hideClassName = 'up-s-hidden';

            $('#cmb2-metabox-_upstream__custom-fieldsargs .c-field-type-args').addClass(hideClassName);

            var wrapper = $('#cmb2-metabox-_upstream__custom-fieldsargs .c-field-type-args[data-type="' + fieldType + '"]');
            if (wrapper.length > 0) {
                wrapper.removeClass(hideClassName);
            }

            if (fieldType === 'file') {
                $('.cmb2-id--upstream--custom-fieldsis-filterable').hide();
            } else {
                $('.cmb2-id--upstream--custom-fieldsis-filterable').show();
            }

            // Autoincrement is only supported on Projects right now
            if (fieldType === 'autoincrement') {
                hideAllUsageOptionsExceptProjects();
            } else {
                showAllUsageOptions();
            }
        });

        function showAllUsageOptions() {
            $('[name="_upstream__custom-fields:usage[]"]').each(function () {
                var option = $(this);

                option.parent().show();
            });
        }

        function hideAllUsageOptionsExceptProjects() {
            $('[name="_upstream__custom-fields:usage[]"]').each(function () {
                var option = $(this);

                if (option.attr('id') === '_upstream__custom-fields:usage1') {
                    option.prop('checked', true);
                } else {
                    option.parent().hide();
                    option.prop('checked', false);
                }
            });
        }

        $('input[name="_upstream__custom-fields:args:multiple"]').on('change', function (e) {
            var defaultValueEl = $('select[name="_upstream__custom-fields:default_value"]');
            var self = $(this);
            var value = self.val();
        });

        /**
         * Make Default Value color picker be affected by Disable Palettes field changes.
         */
        function bindDisablePalettesField (e) {
            var self = typeof e === 'string' ? $(e) : $(this);

            var defaultValueEl = $(document.getElementById('_upstream__custom-fields:colorpicker:default_value'));

            var isChecked = self.is(':checked');
            if (isChecked) {
                defaultValueEl.iris('option', 'palettes', false);
            } else {
                defaultValueEl.iris('option', 'palettes', true);
            }
        }

        // Make sure Default Value field is consistent with Palettes option uppon page load.
        var initialFieldType = $('input[name="_upstream__custom-fields:type"]').val();
        if (initialFieldType === 'colorpicker') {
            setTimeout(function () {
                bindDisablePalettesField('input[name="_upstream__custom-fields:colorpicker:args:disable_palettes"]');
            }, 250);
        } else if (initialFieldType === 'file') {
            $('.cmb2-id--upstream--custom-fieldsis-filterable').remove();
        }

        $('input[name="_upstream__custom-fields:colorpicker:args:disable_palettes"]').on('change', bindDisablePalettesField);

        // If editing a custom field, this ensures its correspondent Args section is displayed.
        if (window.location.href.indexOf('/post-new.php') === -1) {
            $('[name="_upstream__custom-fields:type"]').trigger('change');
        }

        var slugfy = function (str) {
            str = str.replace(/^\s+|\s+$/g, '');
            str = str.toLowerCase();

            var from = 'ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;';
            var to = 'aaaaaeeeeeiiiiooooouuuunc------';
            for (var i = 0, l = from.length; i < l; i++) {
                str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
            }

            str = str.replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');

            return str;
        };

        function bindGroupedOptionsValuesFields (valueField) {
            var wrapper = $(valueField.parents('.cmb-field-list').get(0));

            var labelField = $('input[type="text"][id$="_label"]', wrapper);
            labelField.on('keyup', function (e) {
                e = e || window.event;

                var charCodePressed = e.which || e.keyCode;
                var charPressed = String.fromCharCode(charCodePressed);

                if (charCodePressed === 13 || charCodePressed === 27) {
                    e.preventDefault();
                    e.stopPropagation();

                    labelField.trigger('blur');

                    return false;
                } else {
                    var labelFieldValue = labelField.val();
                    var labelFieldValueSlugfied = slugfy(labelFieldValue);

                    valueField.val(labelFieldValueSlugfied);
                }
            });
        }

        $('.up-o-field-option_value').each(function () {
            var self = $(this);

            bindGroupedOptionsValuesFields(self);
        });

        $('div[data-groupid$="args:options"][id$="args:options_repeat"] .cmb-add-row .cmb-add-group-row').on('click', function (e) {
            var self = $(this);
            var wrapper_id = $(self.parents('div[data-groupid$="args:options"][id$="args:options_repeat"]')).attr('id');

            // Give some time until the new row is added.
            setTimeout(function () {
                var wrapper = $('div[id="' + wrapper_id + '"]');
                var newRow = $('div.postbox[data-iterator]:last', wrapper);

                var labelValueField = $('.up-o-field-option_value', newRow);
                if (labelValueField.length > 0) {
                    bindGroupedOptionsValuesFields(labelValueField);
                }
            }, 150);
        });

        $('input[name$=":args:options_type"]').on('change', function (e) {
            var self = $(this);

            var value = self.val();

            $('.up-c-args-wrapper').removeClass('is-active');

            var wrapper = $('#up-dropdown-options-' + value);
            if (wrapper.length > 0) {
                wrapper.addClass('is-active');
            }
        });

        $('input[name$=":args:options_type"]').each(function () {
            var self = $(this);
            if (self.is(':checked')) {
                self.trigger('change');
                return false;
            }
        });
    });
})(window, window.document, jQuery, $upstreamCustomFields);
