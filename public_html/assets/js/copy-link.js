$('.copy-link').click(function (e) {
    e.preventDefault();
    var copyText = $(this).attr('link');

    document.addEventListener('copy', function(e) {
        e.clipboardData.setData('text/plain', copyText);
        e.preventDefault();
    }, true);

    document.execCommand('copy');

    Toast.fire({
        icon: 'success',
        title: 'لینک موردنظر با موفقیت کپی شد.'
    });
});
