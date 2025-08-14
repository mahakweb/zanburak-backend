$(document).on('submit' , '.add-to-cart', function (e){

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

        },
        success: function (data){
            if(data.status == 0) {
                Toast.fire({
                    icon: 'warning',
                    title: 'آیتم مورد نظر از قبل در سبد خرید موجود میباشد.'
                })
            }else{
                if($('.count-cart').html('')){
                    $('.count-cart').html('<span class="badge badge-notifications badge-accent"></span>');
                    $('.count-cart span').text(data.num);
                }else{
                    $('.count-cart span').text(data.num);
                }


                Toast.fire({
                    icon: 'success',
                    title: 'آیتم مورد نظر با موفقیت به سبد خرید اضافه شد.'
                })
            }


        }
    });

});

$(document).on('submit' , '.delete-from-cart', function (e){

    var x = $(this);
    e.preventDefault();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
        }
    });
    var info = new FormData(this);
    $.ajax({
        async: false,
        url: $(this).attr('action'),
        method: $(this).attr('method'),
        data: info,
        datatype: 'JSON',
        contentType: false,
        processData: false,
        beforeSend: function (){

        },
        success: function (data){
            if(data.status == 0) {

            }else{
                $('.count-cart span').text(data.num);
                x.closest('.cart-items').remove();

                $("#total-price").text(data.priceTotal)
                $("#total-discount").text(data.discountTotal)
                $("#finish-price").text(data.finishPrice)

                Toast.fire({
                    icon: 'success',
                    title: 'آیتم مورد نظر با موفقیت از سبد حذف شد.'
                });
                if(data.num == 0){
                    $('.cart-not-empty').hide();
                    $('.cart-empty').show();
                    $('.count-cart span').hide();
                }
            }


        }
    });

});

$(document).on('submit' , '.destroy-cart', function (e){

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

        },
        success: function (data){
            if(data.status == 0) {

            }else{
                $('.cart-not-empty').hide();
                $('.cart-empty').show();
                $('.count-cart span').hide();
                Toast.fire({
                    icon: 'success',
                    title: 'سبد خرید با موفقیت خالی شد.'
                })
            }


        }
    });


    //
    // e.preventDefault();
    // var info = new FormData(this);
    // var x = $(this)
    // swal({
    //     title: "مطمئنی؟",
    //     text: "این پاسخ به عنوان بهترین ثبت میشه",
    //     type: "warning",
    //     showCancelButton: true,
    //     cancelButtonText: "نه دستم خورده",
    //     confirmButtonText: "بله مطمئنم",
    //     closeOnConfirm: false
    // }, function(isConfirm) {
    //     if (isConfirm) {
    //         $.ajaxSetup({
    //             headers: {
    //                 'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
    //             }
    //         });
    //
    //         $.ajax({
    //             url: x.attr('action'),
    //             method: x.attr('method'),
    //             async: false,
    //             data: info,
    //             datatype: "json",
    //             contentType: false,
    //             processData: false,
    //             beforeSend: function (){
    //
    //             },
    //             success: function (data){
    //                 if(data.status == 0) {
    //
    //                 }else{
    //                     $('.cart-not-empty').hide();
    //                     $('.cart-empty').show();
    //                     $('.count-cart span').hide();
    //                     // Toast.fire({
    //                     //     icon: 'success',
    //                     //     title: 'سبد خرید با موفقیت خالی شد.'
    //                     // })
    //                     swal({
    //                         title: "موفق",
    //                         text: "سبد با موفقیت خالی شد :)",
    //                         type: "success",
    //                         confirmButtonText: "بسیار خب",
    //                         closeOnConfirm: true
    //                     })
    //                 }
    //
    //
    //             }
    //         });
    //     }
    // });


});
