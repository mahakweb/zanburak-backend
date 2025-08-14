jQuery('a[href*=\\#]:not([href=\\#])').on('click', function(e){
    e.preventDefault();
    var href = $(this).attr('href');
    $('html, body').animate({
        scrollTop:$(href).offset().top
    }, 1000);
});
