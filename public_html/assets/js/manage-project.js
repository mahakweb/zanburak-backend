$(document).on('submit' , '.request-project', function (e){
    var x = $(this);
    e.preventDefault();


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
        }
    });
    var info = new FormData(this);
    info.description = easyMDE.value();
    $.ajax({
        url: $(this).attr('action'),
        method: $(this).attr('method'),
        // async: false,
        data: info,
        datatype: "json",
        contentType: false,
        processData: false,
        beforeSend: function (){
            x.find("span.error-text strong").text('');
            x.find("input.is-invalid").removeClass('is-invalid');
            $(x).find(":submit").addClass('is-loading');
            $(x).find(":submit").prop('disabled', true);
        },
        success: function (data){
            $(x).find(":submit").removeClass('is-loading');
            $(x).find(":submit").prop('disabled', false);
            if(data.status == 0) {
                $.each(data.error, function (prefix, val){
                    $("span."+prefix+"_error strong").text(val[0]);
                    $("input[name="+prefix+"]").addClass('is-invalid');
                })
            }else if(data.status == 1){

                Toast.fire({
                    icon: 'success',
                    title: 'سفارش پروژه شما با موفقیت ثبت شد، کارشناسان ما به زودی با شما تماس خواهند گرفت.'
                })


            }


        }
    });

});
