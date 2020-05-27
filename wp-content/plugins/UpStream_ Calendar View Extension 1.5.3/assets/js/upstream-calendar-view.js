(function (window, document, $, $data, undefined) {
    'use strict';

    if (!$) {
        console.error('UpStream > Calendar View requires jQuery to run.');
        return;
    }

    if (!$data) {
        console.error('Invalid data.');
        return;
    }

    $(window).on('upstream-sidebar', function (event) {
        $('#sidebar-menu').find('a').filter(function () {
            var current = this.href == window.location.href.split('#')[0];

            if (current) {
                $('#sidebar-menu li.current-page').removeClass('current-page');
            }
            return current;
        }).parent('li').addClass('current-page').parents('ul').slideDown(function () {
            setContentHeight();
        }).parent().addClass('active');
    });

    $(document).ready(function () {
        function reloadPopovers () {

            $('#calendar [data-toggle="popover"]').tooltip({
                content: function () {
                    return $(this).data('popover-title');
                },
                show: {
                    delay: 1000,
                    duration: 300
                }
            });

            // Popovers for calendar items
            $('#calendar [data-toggle="popover"]').popover('destroy');
            $('#calendar [data-toggle="popover"]').popover({
                html: true,
                placement: 'auto',
                container: 'body',
                title: function () {
                    return $(this).data('popover-icon') + ' ' + $(this).data('popover-title');
                }
            });

            // Popovers for the create item buttons
            $('#calendar [data-toggle="popover-top"]').popover('destroy');
            $('#calendar [data-toggle="popover-top"]').popover({
                html: true,
                placement: 'top',
                container: 'body'
            });

            $('#calendar [data-toggle="popover"]').on('hide.bs.popover', function () {
                $('.o-calendar-day').removeClass('is-active');
            });
        }

        reloadPopovers();

        var calendar = $('#calendar');
        var calendarFilters = {
            assigned_to: -1,
            type: 'all'
        };

        function filterCalendar (calendar, filters) {
            var itemTypes = ['milestone', 'task', 'bug'];
            filters.type = itemTypes.indexOf(filters.type) === -1 ? 'all' : filters.type;
            filters.assigned_to = parseInt(filters.assigned_to);

            $('.c-calendar__body table tbody .o-calendar-day__item', calendar).each(function () {
                var self = $(this);
                var shouldDisplay = false;

                if (filters.type !== 'all') {
                    shouldDisplay = self.attr('data-type') === filters.type;
                } else {
                    shouldDisplay = true;
                }

                if (shouldDisplay) {
                    var assignees = typeof(self.data('assigned_to')) != 'undefined' ? self.data('assigned_to').toString().split(',') : [];
                    if (filters.assigned_to < 0
                        || assignees.indexOf(filters.assigned_to + '') >= 0
                    ) {
                        self.show();
                    } else {
                        self.hide();
                    }
                } else {
                    self.hide();
                }
            });

            USQuickItems.check_init_quick_items();
            USDragAndDrop.init();
        }

        function getAssignedToFilterValue () {
            var self = $('select[data-filter="user"]', calendar);

            if (self.length == 0) {
                return -1;
            }

            var value = self.val();
            var assigned_to = null;

            if (value === 'none') {
                assigned_to = 0;
            } else if (value === 'me') {
                assigned_to = self.data('current_user');
            } else if (value === 'all') {
                assigned_to = -1;
            } else {
                assigned_to = value;
            }

            return assigned_to;
        }

        function getTypeFilterValue () {
            return $('select[data-filter="type"]', calendar).val();
        }

        $('select[data-filter="month"]', calendar).on('change', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var calendar = $('#calendar');

            $.ajax({
                type: 'GET',
                url: $data.ajaxurl,
                data: {
                    action: 'upstream.calendar-view:project.get_calendar_data',
                    nonce: $data.nonce,
                    project_id: $data.project_id,
                    weeks: $('select[data-filter="weeks"]', calendar).val(),
                    month: $(this).val(),
                    is_archive: $data.isArchive
                },
                beforeSend: function () {
                    calendar.addClass('is-loading');
                },
                success: function (response) {
                    if (response.success) {
                        if (response.data) {
                            var tableSelector = '.c-calendar__body table';
                            $(tableSelector, calendar).replaceWith(response.data);
                            reloadPopovers();
                            truncateCalendarDaysWidth();
                            filterCalendar(calendar, {
                                type: getTypeFilterValue(),
                                assigned_to: getAssignedToFilterValue()
                            });
                        } else {
                            console.error('no data returned');
                        }
                    } else {
                        console.error(response.error_message);
                    }

                    calendar.removeClass('is-loading');
                },
                error: function (request, textStatus, errorThrown) {
                    console.error(errorThrown);
                    calendar.removeClass('is-loading');
                }
            });
        });

        $('select[data-filter="weeks"]', calendar).on('change', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var calendar = $('#calendar');

            $.ajax({
                type: 'GET',
                url: $data.ajaxurl,
                data: {
                    action: 'upstream.calendar-view:project.get_calendar_data',
                    nonce: $data.nonce,
                    project_id: $data.project_id,
                    weeks: $(this).val(),
                    is_archive: $data.isArchive,
                    date: $('.o-calendar', calendar).attr('data-date')
                },
                beforeSend: function () {
                    calendar.addClass('is-loading');
                },
                success: function (response) {
                    if (response.success) {
                        if (response.data) {
                            var tableSelector = '.c-calendar__body table';
                            $(tableSelector, calendar).replaceWith(response.data);
                            reloadPopovers();
                            truncateCalendarDaysWidth();
                            filterCalendar(calendar, {
                                type: getTypeFilterValue(),
                                assigned_to: getAssignedToFilterValue()
                            });
                        } else {
                            console.error('no data returned');
                        }
                    } else {
                        console.error(response.error_message);
                    }

                    calendar.removeClass('is-loading');
                },
                error: function (request, textStatus, errorThrown) {
                    console.error(errorThrown);
                    calendar.removeClass('is-loading');
                }
            });
        });

        $('select[data-filter]', calendar).on('change', function () {
            filterCalendar(calendar, {
                assigned_to: getAssignedToFilterValue(),
                type: getTypeFilterValue()
            });
        });

        function reloadCalendar (direction, amount) {
            var calendar = $('#calendar');
            var calendarTable = $('table', calendar);

            $.ajax({
                type: 'GET',
                url: $data.ajaxurl,
                data: {
                    action: 'upstream.calendar-view:project.calendar.change_date',
                    nonce: $data.nonce,
                    project_id: $data.project_id,
                    direction: typeof direction !== 'undefined' ? direction : 'today',
                    amount: typeof amount !== 'undefined' ? amount : 'none',
                    start_date: calendarTable.data('date'),
                    first_day: calendarTable.data('first_day'),
                    weeks: calendarTable.data('weeks'),
                    is_archive: $data.isArchive
                },
                beforeSend: function () {
                    calendar.addClass('is-loading');
                    $('select[data-filter="month"]', calendar).val('0');
                },
                success: function (response) {
                    if (response.success) {
                        if (response.data) {
                            var tableSelector = '.c-calendar__body table';
                            $(tableSelector, calendar).replaceWith(response.data);
                            reloadPopovers();
                            truncateCalendarDaysWidth();

                            USQuickItems.check_init_quick_items();
                            USDragAndDrop.init();
                        }
                    } else if (response.error_message) {
                        alert(response.error_message);
                    }

                    calendar.removeClass('is-loading');
                },
                error: function (request, textStatus, errorThrown) {
                    console.error(errorThrown);
                    calendar.removeClass('is-loading');
                }
            });
        }

        $('[data-target="calendar"][data-direction]').on('click', function (e) {
            var self = $(this);

            reloadCalendar(self.data('direction'), self.data('amount') || 'none');
        });

        $('#calendar').on('click', '.o-calendar-day__item:not(.s-filler)', function () {
            var wrapper = $(this).parents('.o-calendar-day');
            if ($('.o-calendar-day__items > .o-calendar-day__item:not(.s-filler)', wrapper).length > 0) {
                wrapper.addClass('is-active');
            }
        });

        $('[data-action="reset_filters"]').on('click', function (e) {
            e.preventDefault();

            calendarFilters = {
                assigned_to: -1,
                type: 'all'
            };

            var filterUserField = $('select[data-filter="user"]', calendar);
            $('option', filterUserField).attr('selected', false);
            $('option[value="all"]', filterUserField).attr('selected', 'selected');

            var filterTypeField = $('select[data-filter="type"]', calendar);
            if (filterTypeField.length === 1) {
                $('option', filterTypeField).attr('selected', false);
                $('option[value="all"]', filterTypeField).attr('selected', 'selected');
            }

            var filterWeeksField = $('select[data-filter="weeks"]', calendar);
            var filterWeeksFieldDefaultValue = filterWeeksField.attr('data-default');
            $('option[value="' + filterWeeksFieldDefaultValue + '"]', filterWeeksField).attr('selected', 'selected');
            $('table', calendar).attr('data-weeks', filterWeeksFieldDefaultValue);

            $('select[data-filter="month"]', calendar).val('0');

            reloadCalendar();
        });

        /**
         * Edit button in the popup
         */
        $('body').on('click', '[data-action="calendar.edit"][data-id]', function (e) {
            e.preventDefault();

            if (e.which !== 1) return;

            var self = $(this);

            var data_id = self.attr('data-id') || '';
            if (data_id) {
                var item = $('.o-data-table > tbody > tr[data-id="' + data_id + '"] > td .o-edit-link[data-toggle="up-modal"]');
                if (item.length > 0) {
                    var tr = $(item.parents('tr[data-id]').get(0));

                    $('.fa-pencil', self).removeClass('fa-pencil').addClass('fa-spinner fa-spin');
                    self.attr('disabled', 'disabled').addClass('disabled');

                    item.trigger('click');

                    var i = 0;
                    var modalCheckInterval = setInterval(function () {
                        if (tr.hasClass('is-being-opened')) {
                            activePopover.popover('hide');
                            clearInterval(modalCheckInterval);

                            $('.fa', self).removeClass('fa-spinner fa-spin').addClass('fa-pencil');
                            self.attr('disabled', null).removeClass('disabled');
                        }

                        if (i === 50) {
                            clearInterval(modalCheckInterval);
                        }

                        i++;
                    }, 200);
                } else {
                    // Backwards compatibility.
                    item = $('table.datatable[data-type] > tbody > tr > td > a[data-toggle="up-modal"][data-id="' + data_id + '"]');
                    if (item.length > 0) {
                        activePopover.popover('hide');
                    }
                }
            }
        });

        function truncateCalendarDaysWidth () {
            var calendar = $('#calendar .o-calendar');
            var newWidth = Math.floor(calendar.width() / 7) - 12;

            $('.o-calendar-day__items > .o-calendar-day__item:not(.s-pill)').each(function () {
                var self = $(this);

                self.css('width', newWidth);

                if (!self.hasClass('s-truncate')) {
                    self.addClass('s-truncate');
                }
            });
        }

        truncateCalendarDaysWidth();

        $(window).on('resize', truncateCalendarDaysWidth);

        var activePopover = null;
        $('#calendar').on('click', '.o-calendar-day__item[data-toggle="popover"]', function (e) {
            e.preventDefault();

            var self = $(this);

            if (activePopover) {
                activePopover.popover('hide');
            }

            activePopover = self;

            self.popover('toggle');

            e.stopPropagation();
        });

        $(document).on('click', function (e) {
            var targetEl = $(e.srcElement || e.originalTarget);

            if (targetEl.parents('.popover[id^="popover"]').length === 0) {
                $('.o-calendar-day__item').popover('hide');
            }
        });

        // Enables quick creation/edit of drafts on a particular date from the calendar
        var USQuickItems = {
            start_date: null,
            end_date: null,
            selecting: false,

            /**
             * When user clicks the '+' on an individual calendar date or
             * double clicks on a calendar square pop up a form that allows
             * them to create a post for that date
             */
            init: function () {

                var $days = $('td.o-calendar-day');

                // Bind the click on the calendar square
                $days.on('mousedown.upstream.quickItems', USQuickItems.start_selection);
                $days.on('mouseup.upstream.quickItems', USQuickItems.stop_selection);
                $('.main_container').on('mouseup.upstream.quickItems', USQuickItems.check_outside_calendar_unselect);
                $days.mouseover(USQuickItems.show_quickpost_label);
                $days.mouseover(USQuickItems.drag_selection);
                $days.mouseout(USQuickItems.hide_quickpost_label);

                // Allow to clear selection pressing ESC,
                $('body').keydown(USQuickItems.check_esc_clear_selection);

                // Create the content for the popup to select item type to create
                var content = '<div class="o-calendar-add-btn-wrapper">';

                // Get the current filtered type, if any, to filter what buttons should display.
                var filterType = getTypeFilterValue();

                // Reset popovers.
                var $cells = $days.find('.o-calendar-day-cell');
                $cells.popover('destroy');

                $cells
                    .data('toggle', null)
                    .data('trigger', null)
                    .data('content', null)
                    .attr('title', null)
                    .data('html', null)
                    .data('placement', null)
                    .data('container', null);

                if ($('button[data-target="#modal-task"]').length > 0 && (filterType === 'all' || filterType === 'task')) {
                    content += '<a href="#" class="o-calendar-add-btn" data-type="task">+<i class="fa fa-wrench"></i></a>';
                }

                if ($('button[data-target="#modal-bug"]').length > 0 && (filterType === 'all' || filterType === 'bug')) {
                    content += '<a href="#" class="o-calendar-add-btn" data-type="bug">+<i class="fa fa-bug"></i></a>';
                }

                if ($('button[data-target="#modal-milestone"]').length > 0 && (filterType === 'all' || filterType === 'milestone')) {
                    content += '<a href="#" class="o-calendar-add-btn" data-type="milestone">+<i class="fa fa-flag"></i></a>';
                }

                content += '</div>';

                // Prepare the popovers.
                $cells
                    .data('toggle', 'popover-top')
                    .data('trigger', 'manual')
                    .data('content', content)
                    // .attr('title', 'Create new item')
                    .data('html', 'true')
                    .data('placement', 'top')
                    .data('container', 'body');

                reloadPopovers();
            },

            /**
             * When user hovers the day's cell, displays the label about
             * click to create new content.
             */
            show_quickpost_label: function (e) {

                var $this = $(this),
                    target = e.srcElement || e.target,
                    $target = $(target);

                e.preventDefault();
                e.stopPropagation();

                if ($target.is('td.o-calendar-day') || $target.is('.o-calendar-day__items')) {
                    $('.o-calendar-new-item-label').stop().hide();
                    $this.find('.o-calendar-new-item-label').stop().show();
                }
            },

            check_outside_calendar_unselect: function (e) {
                if ($(e.target).parents('.o-calendar-day').length === 0 && !$(e.target).is('.o-calendar-day')) {
                    USQuickItems.unselect_all();
                    USQuickItems.hide_popover();
                }
            },

            /**
             * When user gets out of the day's cell, hides the label about
             * click to create new content.
             */
            hide_quickpost_label: function (e) {
                var $this = $(this);

                // Is it another day cell?
                if ($(e.toElement).is('td') && e.toElement !== this) {
                    $this.find('.o-calendar-new-item-label').stop().hide();
                }

                if (!$(e.toElement).is('ul.post-list')
                    && !$(e.toElement).is('.o-calendar-new-item-label')
                    && !$(e.toElement).is('form')
                    && !$(e.toElement).is('div.day-unit-label')
                    && !($(e.toElement).is('td') && e.toElement == this)
                ) {
                    $this.find('.o-calendar-new-item-label').stop().hide();
                }
            },

            /**
             * Start date selection.
             *
             * @param e
             */
            start_selection: function (e) {
                // Ignore if the clicked element is a link or an item
                if ($(e.target).is('a') || $(e.target).is('input, button') || $(e.target).parent('.o-calendar-day__item').length > 0 || $(e.target).is('.o-calendar-day__item')) {
                    return true;
                }

                e.preventDefault();
                e.stopPropagation();

                USQuickItems.unselect_all();

                USQuickItems.start_date = $(this).data('date');
                USQuickItems.selecting = true;

                // Mark the current date cell.
                $(this).addClass('o-calendar-selected');
            },

            /**
             * Stop date selection.
             *
             * @param e
             */
            stop_selection: function (e) {
                // Ignore if the clicked element is a link or an item
                if ($(e.target).is('a') || $(e.target).is('input, button') || $(e.target).parent('.o-calendar-day__item').length > 0 || $(e.target).is('.o-calendar-day__item')) {
                    return true;
                }

                USQuickItems.end_date = $(this).data('date');
                USQuickItems.selecting = false;

                // Reorganize dates.
                var date1 = Date.parse(USQuickItems.start_date);
                var date2 = Date.parse(USQuickItems.end_date);
                var self = this;

                if (date1 > date2) {
                    var tmp = USQuickItems.start_date;

                    USQuickItems.start_date = USQuickItems.end_date;
                    USQuickItems.end_date = tmp;
                }

                // Show the popover.
                USQuickItems.hide_popover();

                window.setTimeout(function () {
                    activePopover = $(self).find('.o-calendar-day-cell');
                    activePopover.popover('show');

                    $('.popover .o-calendar-add-btn').click(function () {
                        var type = $(this).data('type'),
                            $btn = $('button[data-toggle="up-modal"][data-target="#modal-' + type + '"]');

                        $btn.trigger('click');

                        USQuickItems.hide_popover();

                        // Update the date in the date fields.
                        window.setTimeout(function () {
                            var $modal = $('#modal-' + type),
                                startDate = new Date(Date.parse(USQuickItems.start_date)),
                                endDate = new Date(Date.parse(USQuickItems.end_date));

                            startDate = new Date(startDate.getTime() + Math.abs(startDate.getTimezoneOffset() * 60000));
                            endDate = new Date(endDate.getTime() + Math.abs(endDate.getTimezoneOffset() * 60000));

                            switch (type) {
                                case 'task':
                                case 'milestone':
                                    $modal.find('[name$="[start_date]"]').datepicker('setDate', startDate);
                                    $modal.find('[name$="[end_date]"]').datepicker('setDate', endDate);
                                    break;

                                case 'bug':
                                    $modal.find('[name$="[due_date]"]').datepicker('setDate', startDate);
                                    break;
                            }

                            // If filtering by one user, we automatically set the item assigned to him.
                            var assignedTo = getAssignedToFilterValue();
                            if (assignedTo > 0) {
                                var $field = $modal.find('[name$="[assigned_to][]"]');

                                $field.val(assignedTo);
                                $field.trigger('chosen:updated');
                            }
                        }, 900);
                    });
                }, 200);
            },

            hide_popover: function () {
                if (activePopover) {
                    activePopover.popover('hide');
                }
            },

            check_esc_clear_selection: function (e) {
                if (e.key === 'Escape') {
                    USQuickItems.unselect_all();
                    USQuickItems.hide_popover();
                }
            },

            unselect_all: function () {
                $('.o-calendar-selected').removeClass('o-calendar-selected');
                USQuickItems.selecting = false;
                USQuickItems.start_date = null;
                USQuickItems.end_date = null;
            },

            drag_selection: function (e) {
                var $this = $(this),
                    target = e.srcElement || e.target,
                    $target = $(target);

                e.preventDefault();
                e.stopPropagation();

                if (!USQuickItems.selecting) {
                    return true;
                }

                if ($target.is('td.o-calendar-day') || $target.is('.o-calendar-day__items')) {
                    USQuickItems.end_date = $(this).data('date');

                    var date1 = Date.parse(USQuickItems.start_date);
                    var date2 = Date.parse(USQuickItems.end_date);

                    $('.o-calendar-day').each(function () {
                        var currentDate = Date.parse($(this).data('date'));

                        if (date1 < date2) {
                            if (currentDate >= date1 && currentDate <= date2) {
                                $(this).addClass('o-calendar-selected');
                            } else {
                                $(this).removeClass('o-calendar-selected');
                            }
                        } else {
                            if (currentDate >= date2 && currentDate <= date1) {
                                $(this).addClass('o-calendar-selected');
                            } else {
                                $(this).removeClass('o-calendar-selected');
                            }
                        }
                    });
                }
            },

            check_init_quick_items: function () {
                if ($data.can_add_items == 1) {
                    USQuickItems.init();
                }
            }
        };

        // Allow to drag-and-drop items.
        var USDragAndDrop = {
            $fromDay: null,
            $toDay: null,

            init: function () {
                /**
                 * Instantiates drag and drop sorting for posts on the calendar
                 */
                $('.o-calendar-day__items').sortable({
                    items: '.o-calendar-day__item',
                    connectWith: '.o-calendar-day__items',
                    placeholder: 'ui-state-highlight',
                    helper: 'clone',
                    start: function (event, ui) {
                        USQuickItems.unselect_all();
                        USQuickItems.hide_popover();
                        USDragAndDrop.$fromDay = $(this).parents('.o-calendar-day');
                    },
                    receive: function (event, ui) {
                        calendar.addClass('is-loading');
                        USQuickItems.unselect_all();

                        USDragAndDrop.$toDay = $(this).parents('.o-calendar-day');

                        var $item = ui.item;

                        // Make Ajax request to change the date.
                        var params = {
                            action: 'upstream.calendar-view:calendar.move_item',
                            nonce: $data.nonce,
                            project_id: $item.data('project-id'),
                            item_type: $item.data('type'),
                            item_id: $item.data('id'),
                            item_date_ref: $item.data('date-ref'),
                            new_date: USDragAndDrop.$toDay.data('date')
                        };

                        $.post(
                            $data.ajaxurl,
                            params,
                            function (data, status) {
                                reloadCalendar();

                                switch (params.item_type) {
                                    case 'task':
                                        var column = params.item_date_ref === 'start' ? 'start_date' : 'end_date';

                                        $('#tasks tr[data-id="' + params.item_id + '"] td[data-column="' + column + '"]').text(data.data.new_date_str);
                                        break;

                                    case 'milestone':
                                        var column = params.item_date_ref === 'start' ? 'start_date' : 'end_date';

                                        $('#milestones tr[data-id="' + params.item_id + '"] td[data-column="' + column + '"]').text(data.data.new_date_str);
                                        break;

                                    case 'bug':
                                        var column = 'due_date';

                                        $('#bugs tr[data-id="' + params.item_id + '"] td[data-column="' + column + '"]').text(data.data.new_date_str);
                                        break;
                                }
                            }
                        );
                    }
                });
            }
        };

        // Do not enable selection and Add behavior if in the Calendar Overview page.
        setTimeout(function() {
            if ($('.o-calendar').data('is-single-project') == 1) {
                // Quick add/edit items in the calendar
                USQuickItems.check_init_quick_items();
            }
        }, 1000);

        USDragAndDrop.init();
    });
})(window, window.document, jQuery, $data);
