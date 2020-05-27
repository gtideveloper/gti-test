(function (window, document, $, undefined) {
    function makeGanttResponsive () {
        var windowWidth = $(window).width();
        if (windowWidth <= 820) {
            $('.gantt .fn-content').width($('.gantt .rightPanel .dataPanel').width());
        } else {
            $('.gantt .fn-content').width('auto');
        }
    }

    var maxIterations = 15;
    var widthCheckIntervalIterations = 0;
    var widthCheckInterval = setInterval(function () {
        widthCheckIntervalIterations++;

        if ($('.gantt .rightPanel .dataPanel').width() > 0 || widthCheckIntervalIterations === maxIterations) {
            clearInterval(widthCheckInterval);
            makeGanttResponsive();
        }
    }, 200);

    $(window).on('resize', makeGanttResponsive);


})(window, window.document, jQuery);
