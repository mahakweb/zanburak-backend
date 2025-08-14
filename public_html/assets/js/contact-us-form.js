$(document).on('submit' , '.contact-us-form', function (e){

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
            x.find("span.error-text strong").text('');
            x.find("input.is-invalid").removeClass('is-invalid');
            x.find("textarea.is-invalid").removeClass('is-invalid');
        },
        success: function (data){
            if(data.status == 0) {
                $.each(data.error, function (prefix, val){
                    $("span."+prefix+"_error strong").text(val[0]);
                    $("input[name="+prefix+"] , textarea[name="+prefix+"]").addClass('is-invalid');
                })
            }else if(data.status == 1){
                Toast.fire({
                    icon: 'success',
                    title: 'پیام شما با موفقیت ارسال شد.'
                })
            }
        }
    });

});
