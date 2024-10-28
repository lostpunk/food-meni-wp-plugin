jQuery(document).ready(function ($) {
    $('body').on('click', '.year-btn', function () {
        const year = $(this).data('year');
        $('.year-btn').removeClass('current');
        $(this).addClass('current');
        $('.year-container').removeClass('current');
        $('.year-container[data-year=' + year + ']').addClass('current');
    });

    $('body').on('click', '.month-btn', function () {
        const year = $(this).data('year');
        const month = $(this).data('month');
        $('.month-btn').removeClass('current');
        $(this).addClass('current');
        $('.month-item').each(function (i, el) {
            if ($(el).data('month') == month && $(el).data('year') == year) {
                $(el).addClass('current');
            } else {
                $(el).removeClass('current');
            }
        });
    });

    $('.nav-tab').on('click', function (e) {
        e.preventDefault();
        var tabId = $(this).data('tab');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-pane').hide();
        $('#' + tabId).show();
    });

    $('.nav-tab').first().trigger('click');
});
