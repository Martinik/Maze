$('.matrix-cell').on('click', function () {
    $('#start').val($(this).data('val'));
    $('.matrix-cell').removeClass('selected');
    $(this).addClass('selected')
});
