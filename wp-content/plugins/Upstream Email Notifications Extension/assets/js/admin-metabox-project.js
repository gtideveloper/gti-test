(function (window, document, $, $l, undefined) {
    if (!$) {
        console.error('jQuery not found.');
        return;
    }

    var $itemWrapper = null;
    var $itemType = null;

    var options = {
        '1': $l['LB_ONE_DAY_BEFORE'],
        '2': $l['LB_TWO_DAYS_BEFORE'],
        '3': $l['LB_THREE_DAYS_BEFORE'],
        '4': $l['LB_ONE_WEEK_BEFORE'],
        '5': $l['LB_TWO_WEEKS_BEFORE']
    };

    function fetchReminders (itemType, modal, itemWrapper) {
        var projectId = $('#post_ID').length ? $('#post_ID').val() : null;

        if (projectId && modal && itemWrapper && ['milestones', 'tasks', 'bugs'].indexOf(itemType) >= 0) {
            var table = $('#modal-reminders-wrapper tbody');

            $itemWrapper = itemWrapper;
            $itemType = itemType;

            var table = $('#modal-reminders-wrapper tbody');
            var remindersInputs = $('input[type="hidden"][data-reminder]:not([data-reminder-default])', itemWrapper);

            if (remindersInputs.length === 0) {
                table.html('<tr data-no-items><td colspan="3" class="text-center">' + $l['MSG_NO_DATA_FOUND'] + '</td></tr>');
            } else {
                var select = $('#modal-reminders-wrapper select[name="reminder"]');

                table.html('');
                var tr = $('<tr></tr>');
                for (var i = 0; i < remindersInputs.length; i++) {

                    var reminder = $(remindersInputs[i]);
                    var reminderType = reminder.attr('data-reminder');

                    if (reminderType > 1000) {

                        var d = new Date(reminderType * 1000);
                        var tr = $('<tr data-reminder="' + reminderType + '"></tr>');

                        tr.append($('<td>' + new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate()).toLocaleDateString() + '</td>'));
                        tr.append($('<td class="text-center"><span class="dashicons dashicons-' + (reminder.attr('data-sent') ? 'yes' : 'clock') + '"></span></td>'));
                        tr.append($('<td class="text-center"><a href="#" data-action="reminder:remove"><span class="dashicons dashicons-trash"></span></a></td>'));

                        table.append(tr);

                    } else {

                        $('option[value="' + reminderType + '"]', select).attr('disabled', 'disabled');

                        var tr = $('<tr data-reminder="' + reminderType + '"></tr>');

                        tr.append($('<td>' + options[reminderType] + '</td>'));
                        tr.append($('<td class="text-center"><span class="dashicons dashicons-' + (reminder.attr('data-sent') ? 'yes' : 'clock') + '"></span></td>'));
                        tr.append($('<td class="text-center"><a href="#" data-action="reminder:remove"><span class="dashicons dashicons-trash"></span></a></td>'));

                        table.append(tr);

                    }

                }
            }
        } else {
            alert($l['MSG_RELOAD_PAGE']);
        }
    }

    function addReminder (e) {
        e.preventDefault();
        e.stopPropagation();

        var modal = $(this).parents('#modal-reminders-wrapper');
        var table = $('table tbody', modal);
        var input = $('select[name="reminder"]', modal);
        var reminderType = input.val();

        var choose = $('input[name="chooseadate2"]', modal);
        var chooseValue = choose.val();

        var chooseTs = $('#' + choose.attr('id') + '_timestamp');
        var chooseTsValue = chooseTs.val();


        if (!reminderType && (!chooseValue || !chooseTsValue)) {
            return;
        }

        $('tr[data-no-items]', table).remove();

        if (reminderType) {
            var html = '<tr data-reminder="' + reminderType + '"><td>' + options[reminderType] + '</td><td class="text-center"><span class="dashicons dashicons-clock"></span></td><td class="text-center"><a href="#" data-action="reminder:remove"><span class="dashicons dashicons-trash"></span></a></td></tr>';
            table.append(html);

            $('option[value="' + reminderType + '"]', input).attr('disabled', 'disabled');
            input.val(null);

            if ($itemWrapper) {
                var reminder = '<input type="hidden" name="_upstream_project_' + $itemType + '[' + $itemWrapper.data('iterator') + '][reminders][]" data-reminder="' + reminderType + '" value="' + reminderType + '" />';
                $itemWrapper.append($(reminder));
            }
        } else {


            var html = '<tr data-reminder="' + chooseTsValue + '"><td>' + chooseValue + '</td><td class="text-center"><span class="dashicons dashicons-clock"></span></td><td class="text-center"><a href="#" data-action="reminder:remove"><span class="dashicons dashicons-trash"></span></a></td></tr>';
            table.append(html);

            choose.val("");

            if ($itemWrapper) {
                var reminder = '<input type="hidden" name="_upstream_project_' + $itemType + '[' + $itemWrapper.data('iterator') + '][reminders][]" data-reminder="' + chooseTsValue + '" value="' + chooseTsValue + '" />';
                $itemWrapper.append($(reminder));
            }

        }

    }

    function removeReminder (e) {
        e.preventDefault();

        var self = $(this);
        var modal = self.parents('#modal-reminders-wrapper');
        var table = $('table tbody', modal);
        var input = $('select[name="reminder"]', modal);

        var row = self.parents('tr[data-reminder]');

        var reminder = row.data('reminder');

        $('input[type="hidden"][data-reminder="' + reminder + '"]', $itemWrapper).remove();
        $('input[type="hidden"][name="_upstream_project_' + $itemType + '[' + $itemWrapper.data('iterator') + ']"][value="' + reminder + '"]', $itemWrapper).remove();
        row.remove();

        $('option[value="' + reminder + '"]', input).attr('disabled', null);

        if ($('tr', table).length === 0) {
            table.append($('<tr data-no-items><td class="text-center" colspan="3">' + $l['MSG_NO_DATA_FOUND'] + '</td></tr>'));
        }
    }

    var thickBoxClose = window.tb_remove;
    window.tb_remove = function () {
        thickBoxClose();

        setTimeout(function () {
            var modalContent = $('#TB_ajaxContent', $('#TB_window'));

            $('table tbody', modalContent).html($('<tr data-no-items><td class="text-center" colspan="3">' + $l['MSG_LOADING_REMINDERS'] + '</td></tr>'));
            $('select option[value!=""]', modalContent).attr('disabled', null);
        }, 250);
    };

    $(document).ready(function () {
        if ($('[data-reminder-default]').length > 0) {
            $('#post').append($('[data-reminder-default]'));
        }

        $('.cmb-repeatable-grouping:visible [data-action="reminders:browse"][data-is-new]').attr('data-is-new', null);
        $('div').on('click', 'button[data-action="reminder:add"]', addReminder);
        $('div').on('click', 'a[data-action="reminder:remove"]', removeReminder);

        $('.cmb2-metabox.cmb-field-list').on('click', 'a[data-action="reminders:browse"]', function (e) {
            var self = $(this);

            var thickBoxCheckInstanceInterval = setInterval(function () {
                var thickBoxWindow = $('#TB_window');
                if (thickBoxWindow.length) {
                    clearInterval(thickBoxCheckInstanceInterval);

                    var modalContent = $('#TB_ajaxContent', thickBoxWindow);

                    thickBoxWindow.animate({
                        width: 650,
                        height: 300
                    }, {
                        start: function () {
                            modalContent.css({
                                width: '',
                                height: ''
                            });

                            $('table tbody', modalContent).html($('<tr data-no-items><td class="text-center" colspan="3">' + $l['MSG_LOADING_REMINDERS'] + '</td></tr>'));
                            $('select option[value!=""]', modalContent).attr('disabled', null);
                        },
                        progress: function () {
                            thickBoxWindow.css({
                                'margin-left': '',
                                'margin': 'auto',
                                'left': 0,
                                'right': 0
                            });
                        },
                        done: function () {
                            fetchReminders(self.attr('data-type'), modalContent, self.parents('.postbox.cmb-row.cmb-repeatable-grouping[data-iterator]'));
                        }
                    });
                }
            }, 250);
        });

        $('[data-selector="_upstream_project_milestones_repeat"], [data-selector="_upstream_project_tasks_repeat"], [data-selector="_upstream_project_bugs_repeat"]').on('click', function () {
            var self = $(this);
            var itemType = /_upstream_project_([a-z]+)_repeat/i.exec(self.attr('data-selector'))[1];
            var wrapper = $(self.parents('[data-groupid]'));

            setTimeout(function () {
                var rows = $('.postbox.cmb-row.cmb-repeatable-grouping[data-iterator]', wrapper);
                var newRow = $(rows[rows.length - 1]);
                var newRowIteratorIndex = newRow.data('iterator');
                var defaultReminders = $('input[type="hidden"][data-reminder-default]');

                $('input[type="hidden"][data-reminder]', newRow).remove();

                if (defaultReminders.length > 0) {
                    $.each(defaultReminders, function () {
                        var self = $(this);
                        var reminderType = self.data('reminder');

                        var hiddenInput = $('<input type="hidden" name="_upstream_project_' + itemType + '[' + newRowIteratorIndex + '][reminders][]" data-reminder="' + reminderType + '" value="' + reminderType + '" />');
                        newRow.append(hiddenInput);
                    });
                }
            }, 250);
        });
    });
})(window, window.document, jQuery || null, l);
