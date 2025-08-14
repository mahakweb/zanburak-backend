$(document).on('submit' , '.send-rating', function (e){
    var x = $(this);
    e.preventDefault();
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
        }
    });
    var info = new FormData(this);
    $.ajax({
        url: $(this).attr('action'),
        method: $(this).attr('method'),
        async: false,
        data: info,
        datatype: "json",
        contentType: false,
        processData: false,
        beforeSend: function (){
            $('.send-rating button[type="submit"]').addClass('is-loading');
            $(".send-rating").find("span.error-text strong").text('');
        },
        success: function (data){
            $('.send-rating button[type="submit"]').removeClass('is-loading');
            if(data.status == 0) {
                $.each(data.error, function (prefix, val) {
                    $(".send-rating").find("span."+prefix+"_error strong").text(val[0]);
                    // Toast.fire({
                    //     icon: 'error',
                    //     title: val[0]
                    // })
                })
            }else if(data.status == 2){
                Toast.fire({
                    icon: 'error',
                    title: 'شما قبلا بازخورد خود را ثبت کرده اید!'
                });

            }else {
                Toast.fire({
                    icon: 'success',
                    title: 'بازخورد شما با موفقیت ثبت شد.'
                });
            }


        }
    });

});
