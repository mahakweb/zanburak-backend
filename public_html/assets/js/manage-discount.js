$(function (){

    $("#set-discount").on('submit', function (e){

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
            data: info,
            datatype: "json",
            contentType: false,
            processData: false,
            beforeSend: function (){
                $(document).find("span.error-text strong").text('');
                $(document).find("input.is-invalid").removeClass('is-invalid');
                $('#set-discount button[type="submit"]').addClass('is-loading');
            },
            success: function (data){
                $('#set-discount button[type="submit"]').removeClass('is-loading');
                if(data.status == 0){
                    $.each(data.error, function (prefix, val){
                        $("span."+prefix+"_error strong").text(val);
                        $("input[name="+prefix+"]").addClass('is-invalid');
                    })
                }else if (data.status == 1){

                    $("#total-price").text(data.priceTotal)
                    $("#total-discount").text(data.discountTotal)
                    $("#finish-price").text(data.finishPrice)

                    Toast.fire({
                        icon: 'success',
                        title: 'کد تخفیف با موفقیت ثبت شد.'
                    })
                }

            },
            error: function () {
                $('#set-discount button[type="submit"]').removeClass('is-loading');
            }


        });

    });


});
