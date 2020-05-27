(function (window, document, $, $l, $upstream, undefined) {
    var options = {
        '1': $l['LB_ONE_DAY_BEFORE'],
        '2': $l['LB_TWO_DAYS_BEFORE'],
        '3': $l['LB_THREE_DAYS_BEFORE'],
        '4': $l['LB_ONE_WEEK_BEFORE'],
        '5': $l['LB_TWO_WEEKS_BEFORE']
    };

    function extractItemTypeFromString (subject) {
        var itemType = /modal\-([A-Za-z]+)/.exec(subject)[1];

        return itemType;
    }

    $(document).ready(function () {
        $('body').on('click', '[data-action="reminder:add"]', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var wrapper = $(self.parents('.reminders-wrapper'));
            var table = $('table.reminders tbody', wrapper);

            var select = $('select[name="reminder"]', wrapper);
            var selectValue = select.val();

            var choose = $('input[name="chooseadate"]', wrapper);
            var chooseValue = choose.val();

            var chooseTs = $('#' + choose.attr('id') + '_timestamp');
            var chooseTsValue = chooseTs.val();

            if (!selectValue && (!chooseValue || !chooseTsValue)) {
                return;
            }

            $('tr[data-no-items]', wrapper).remove();

            if (selectValue) {
                let tr = $('<tr data-reminder="' + selectValue + '"></tr>');
                tr.append('<td style="padding-left: 10px;">' + options[selectValue] + '</td>');
                tr.append($('<td class="text-center"><span class="dashicons dashicons-minus" title="' + $l['MSG_NOT_SAVED_YET'] + '"></span></td>'));
                tr.append($('<td class="text-center"><a href="#" data-action="reminder:remove"><span class="dashicons dashicons-trash"></span></a></td>'));

                table.append(tr);

                $('option[value="' + selectValue + '"]', select).attr('disabled', 'disabled');
                select.val('');

                wrapper.append('<input type="hidden" name="data[reminders][]" value="' + selectValue + '" />');
            }
            else {
                try {
                    let tr = $('<tr data-reminder="' + chooseValue + '"></tr>');
                    tr.append('<td style="padding-left: 10px;">' + chooseValue + '</td>');
                    tr.append($('<td class="text-center"><span class="dashicons dashicons-minus" title="' + $l['MSG_NOT_SAVED_YET'] + '"></span></td>'));
                    tr.append($('<td class="text-center"><a href="#" data-action="reminder:remove"><span class="dashicons dashicons-trash"></span></a></td>'));

                    table.append(tr);
                    choose.val("");

                    wrapper.append('<input type="hidden" name="data[reminders][]" value="' + chooseTsValue + '" />');
                } catch (e) {
                    // don't do anything
                }
            }
        });

        $('body').on('click', '[data-action="reminder:remove"]', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var table = $(self.parents('tbody'));
            var wrapper = $(table.parents('.reminders-wrapper'));

            var tr = self.parents('tr');

            var reminderType = tr.data('reminder');
            var reminderId = tr.attr('data-id');

            $('select[name="reminder"] option[value="' + reminderType + '"]', wrapper).attr('disabled', false);
            $('input[type="hidden"][name="data[reminders][]"][value="' + reminderId + '"]', wrapper).remove();
            $('input[type="hidden"][name="data[reminders][]"][value="' + reminderType + '"]', wrapper).remove();

            tr.remove();

            if ($('tr', table).length === 0) {
                table.append($('<tr data-no-items><td colspan="3" class="text-center">' + $l['MSG_NO_DATA_FOUND'] + '</td></tr>'));
            }
        });

        if (typeof(window.upstream_loadReminders) === 'undefined') {
            window.upstream_loadReminders = function(itemType, reminders) {

                if (['milestone', 'task', 'bug'].indexOf(itemType) >= 0) {

                    var modal = $('#modal-' + itemType);
                    var form = $('.o-modal-form', modal);
                    var table = $('table.reminders tbody', form);

                    if (table.length > 0) {

                        table.html('');
                        $('input[type="hidden"][name="data[reminders][]"]', form).remove();

                        var select = $('select[name="reminder"]', form);
                        $('option:not(:first-child)', select).attr('disabled', null);
                        var remindersWrapper = $('.reminders-wrapper', form);

                        for (var i = 0; i < reminders.length; i++) {

                            var r = JSON.parse(reminders[i]);

                            if (r.reminder <= 1000) {
                                $('option[value="' + r.reminder + '"]', select).attr('disabled', 'disabled');

                                var tr_reminder = $('<tr data-reminder="' + r.reminder + '" data-id="' + r.id + '"></tr>');

                                tr_reminder.append('<td style="padding-left: 10px;">' + options[r.reminder] + '</td>');
                                tr_reminder.append($('<td class="text-center"><span class="dashicons dashicons-' + (r.sent ? 'yes' : 'clock') + '"></span></td>'));
                                tr_reminder.append($('<td class="text-center"><a href="#" data-action="reminder:remove"><span class="dashicons dashicons-trash"></span></a></td>'));

                                table.append(tr_reminder);

                                remindersWrapper.append($('<input type="hidden" name="data[reminders][]" value="' + r.id + '" />'));
                            }
                            else {
                                var d = new Date(r.reminder * 1000);

                                var tr_reminder = $('<tr data-reminder="' + r.reminder + '" data-id="' + r.id + '"></tr>');

                                tr_reminder.append('<td style="padding-left: 10px;">' + new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate()).toLocaleDateString() + '</td>');
                                tr_reminder.append($('<td class="text-center"><span class="dashicons dashicons-' + (r.sent ? 'yes' : 'clock') + '"></span></td>'));
                                tr_reminder.append($('<td class="text-center"><a href="#" data-action="reminder:remove"><span class="dashicons dashicons-trash"></span></a></td>'));

                                table.append(tr_reminder);

                                remindersWrapper.append($('<input type="hidden" name="data[reminders][]" value="' + r.id + '" />'));
                            }
                        }

                    }
                }

            };
        }

        if (typeof(window.upstream_defaultReminders) === 'undefined') {

            window.upstream_defaultReminders = function (modal) {

                if (typeof(upstream) !== 'undefined' && 'email-notifications' in upstream && typeof(upstream['email-notifications'].default_reminders) !== 'undefined') {

                    var form = $('.o-modal-form', modal);
                    var table = $('table.reminders tbody', form);

                    var select = $('select[name="reminder"]', form);
                    $('option:not(:first-child)', select).attr('disabled', null);
                    var remindersWrapper = $('.reminders-wrapper', form);

                    var type = $(modal.el).attr('data-type');
                    var reminders = [];

                    for (k in upstream['email-notifications'].default_reminders) {

                        let tr = $('<tr data-reminder="' + k + '"></tr>');
                        tr.append('<td style="padding-left: 10px;">' + upstream['email-notifications'].default_reminders[k] + '</td>');
                        tr.append($('<td class="text-center"><span class="dashicons dashicons-minus" title="' + $l['MSG_NOT_SAVED_YET'] + '"></span></td>'));
                        tr.append($('<td class="text-center"><a href="#" data-action="reminder:remove"><span class="dashicons dashicons-trash"></span></a></td>'));

                        table.append(tr);

                        $('option[value="' + k + '"]', select).attr('disabled', 'disabled');
                        select.val('');

                        remindersWrapper.append('<input type="hidden" name="data[reminders][]" value="' + k + '" />');

                    }

                }

            };
        }

    });
})(window, window.document, jQuery || null, l || {}, upstream || {});
