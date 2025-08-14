$(".set-best").on("submit", function(e) {
    e.preventDefault();
    var form = this;
    swal({
        title: "اخطار!",
        text: "مطمئنی میخوای این پاسخ رو به عنوان بهترین پاسخ ثبت کنی؟",
        type: "warning",
        showCancelButton: true,
        cancelButtonText: "نه دستم خورده",
        confirmButtonText: "بله مطمئنم",
        closeOnConfirm: false
    }, function(isConfirm) {
        if (isConfirm) {
            form.submit();
        }
    });

});




$(document).on('submit' , '.send-question', function (e){
    var x = $(this);
    e.preventDefault();

    $('.send-question button[type="submit"]').addClass('is-loading');
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
        }
    });
    // var question = document.querySelector('input[name=question]');
    // question.value = quill.root.innerHTML;
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
            $('.send-question button[type="submit"]').removeClass('is-loading');
            if(data.status == 0) {
                $.each(data.error, function (prefix, val){
                    $(".send-question span." + prefix + "_error strong").text(val[0]);
                    Toast.fire({
                        icon: 'error',
                        title: val[0]
                    })

                })

                // $.each(data.error, function (prefix, val) {
                //     Toast.fire({
                //         icon: 'error',
                //         title: val[0]
                //     })
                //     // $(".send-comment span." + prefix + "_error strong").text(val[0]);
                //     $(" textarea[name=" + prefix + "]",x).addClass('is-invalid');
                // })
            }else if(data.status == 1){

                Swal.fire({
                    title: "با تشکر",
                    text: "پرسش شما با موفقیت ثبت شد",
                    icon: "success",
                    showCancelButton: false,
                    confirmButtonText: "بسیار خب",
                    closeOnConfirm: true
                }).then((function(t){
                    if(t.value){
                        window.location.href = data.redirect_url
                    }

                }))

                // Toast.fire({
                //     icon: 'success',
                //     title: 'پرسش شما با موفقیت ثبت شد.'
                // })


            }


        }
    });

});




$(document).on('submit' , '.send-answer', function (e){
    var x = $(this);
    e.preventDefault();

    $('.send-answer button[type="submit"]').addClass('is-loading');
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
            x.find("textarea.is-invalid").removeClass('is-invalid');
        },
        success: function (data){
            $('.send-answer button[type="submit"]').removeClass('is-loading');
            if(data.status == 0) {
                $.each(data.error, function (prefix, val) {
                    Toast.fire({
                        icon: 'error',
                        title: val[0]
                    })
                    $(".send-answer span." + prefix + "_error strong").text(val[0]);
                })
            }else if(data.status == 1){
                $('.send-answer').each(function() {
                    this.reset();
                })

                // swal({
                //     title: "موفق",
                //     text: "پاسخ شما با موفقیت ثبت شد",
                //     type: "success",
                //     showCancelButton: false,
                //     confirmButtonText: "بسیار خب",
                //     closeOnConfirm: true
                // })

                Toast.fire({
                    icon: 'success',
                    title: 'پاسخ شما با موفقیت ثبت شد.'
                })


            }


        }
    });

});




$(document).on('show.bs.modal','#modal-send-report', function (event) {
    var button = $(event.relatedTarget)
    let reportable_id = button.data('id')
    let reportable_type = button.data('model')

    var modal = $(this)

    modal.find("input[name='reportable_id']").val(reportable_id)
    modal.find("input[name='reportable_type']").val(reportable_type)
})


$(document).on('submit' , '.send-report', function (e){
    var x = $(this);
    e.preventDefault();

    $('.send-report button[type="submit"]').addClass('is-loading');
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
        success: function (data){
            $('.send-report button[type="submit"]').removeClass('is-loading');
            if(data.status == 0) {
                $.each(data.error, function (prefix, val) {
                    Toast.fire({
                        icon: 'error',
                        title: val[0]
                    })
                })
            }else if(data.status == 1){
                $('#modal-send-report').modal('hide');

                Toast.fire({
                    icon: 'success',
                    title: 'با تشکر، گزارش تخلف شما در اولین فرصت بررسی خواهد شد.'
                })

            }


        }
    });

});

