$(function (){

    $("#profile-change-password").on('submit', function (e){

        e.preventDefault();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
            }
        });
        var info = new FormData(this);
        info.append('old-password', $('#profile-change-password input[name=old-password]').val());
        info.append('new-password', $('#profile-change-password input[name=new-password]').val());
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
            },
            success: function (data){
                if(data.status == 0){
                    $.each(data.error, function (prefix, val){
                        $("span."+prefix+"_error strong").text(val[0]);
                        $("input[name="+prefix+"]").addClass('is-invalid');
                    })
                }else{
                    $("#profile-change-password")[0].reset();
                    // toastr.success('رمز عبور با موفقیت تغییر کرد.', 'موفق', 'close');
                    Toast.fire({
                        icon: 'success',
                        title: 'رمز عبور با موفقیت تغییر کرد'
                    })
                }

            }


        });

    });


});


$(function () {

    $("#profile-change-mobile").on('submit', function (e) {
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
                $("#profile-change-mobile").find(":submit").addClass('is-loading');
                $("#profile-change-mobile").find(":submit").attr('disabled', true);
                $("#profile-change-mobile").find("span.error-text strong").text('');
                $("#profile-change-mobile").find("input.is-invalid").removeClass('is-invalid');
            },
            success: function (data){
                $("#profile-change-mobile").find(":submit").removeClass('is-loading');
                $("#profile-change-mobile").find(":submit").attr('disabled', false);
                if(data.status == 0) {
                    $.each(data.error, function (prefix, val){
                        $("span."+prefix+"_error strong").text(val[0]);
                        $("input[name="+prefix+"]").addClass('is-invalid');
                    })
                }else if(data.status == 1){
                    $('#user_phone').text(data.user_phone);
                    $('#modal-change-mobile').modal('show');
                }
            }
        });
    });
});




$(function () {

    $(".profile-verify-token").on('submit', function (e) {
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
                $(".profile-verify-token").find(":submit").addClass('is-loading');
                $(".profile-verify-token").find(":submit").attr('disabled', true);
                $(".profile-verify-token").find("span.error-text strong").text('');
                $(".profile-verify-token").find("input.is-invalid").removeClass('is-invalid');
            },
            success: function (data){

                $(".profile-verify-token").find(":submit").removeClass('is-loading');
                $(".profile-verify-token").find(":submit").attr('disabled', false);

                if(data.status == 0) {
                    $.each(data.error, function (prefix, val){
                        // $("span."+prefix+"_error strong").text(val[0]);
                        // $("input[name="+prefix+"]").addClass('is-invalid');
                        $(".profile-verify-token").find("span."+prefix+"_error strong").text(val[0]);
                        Toast.fire({
                            icon: 'error',
                            title: val[0]
                        })
                    })
                }else if(data.status == 1){
                    $('#modal-change-mobile').modal('hide');

                    $("#profile-change-mobile").find("input[type=tel]").val(data.new_mobile);

                    Toast.fire({
                        icon: 'success',
                        title: 'شماره موبایل با موفقیت تغییر یافت.'
                    })
                }
            }
        });
    });
});




$(function () {

    $("#profile-change-info").on('submit', function (e) {

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
                $("#profile-change-info").find(":submit").addClass('is-loading');
                $("#profile-change-info").find(":submit").attr('disabled', true);
                $("#profile-change-info").find("span.error-text strong").text('');
                $("#profile-change-info").find("input.is-invalid, textarea.is-invalid").removeClass('is-invalid');
            },
            success: function (data){
                $("#profile-change-info").find(":submit").removeClass('is-loading');
                $("#profile-change-info").find(":submit").attr('disabled', false);
                if(data.status == 0){
                    $.each(data.error, function (prefix, val){
                        $("span."+prefix+"_error strong").text(val[0]);
                        $("input[name="+prefix+"] , textarea[name="+prefix+"]").addClass('is-invalid');
                    })
                }else{
                    // $("#profile-change-info")[0].reset();
                    // toastr.success('اطلاعات با موفقیت تغییر کرد.', 'موفق', 'close');
                    Toast.fire({
                        icon: 'success',
                        title: 'اطلاعات با موفقیت تغییر کرد'
                    })
                }

            }


        });

    });
});




$(function () {

    $(".delete-session").on('submit', function (e) {
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
                    $.each(data.error, function (prefix, val){
                        Toast.fire({
                            icon: 'error',
                            title: val[0]
                        })
                    })
                }
                else if(data.status == 2){
                    Toast.fire({
                        icon: 'warning',
                        title: 'به دلایل امنیتی، امکان لغو نشستهای قبلی از دستگاهی که به تازگی با آن به حسابتان متصل شده‌اید وجود ندارد. لطفا از یک اتصال قدیمی‌تر استفاده کنید یا چند ساعت منتظر بمانید.'
                    })
                }
                else if(data.status == 1){
                    x.closest('#sessions').remove();
                    Toast.fire({
                        icon: 'success',
                        title: 'نشست مورد نظر با موفقیت حذف شد.'
                    })
                }
            }
        });
    });
});



    $("#profile-file").on('change', function(event){
        event.preventDefault();
        var profile = document.getElementById("profile-output");
        profile.src = URL.createObjectURL(event.target.files[0]);
        $("#update-profile").submit();
    });

    $("#cover-file").on('change', function(event){
        event.preventDefault();
        var cover = document.getElementById("cover-output");
        cover.src = URL.createObjectURL(event.target.files[0]);
       $("#update-cover").submit()
    });



    $(function () {
        $(document).on("submit", "#update-profile", function(event){
            event.preventDefault();
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
                    $("#update-profile").find('label span').addClass('is-loading');
                },
                success: function (data){
                    $("#update-profile").find('.-label span').removeClass('is-loading');
                    if(data.status == 0){
                        $.each(data.error, function (prefix, val){
                            Toast.fire({
                                icon: 'error',
                                title: val[0]
                            })
                        })
                    }else if(data.status == 1){
                        Toast.fire({
                            icon: 'success',
                            title: 'تصویر پروفایل با موفقیت تغییر کرد'
                        })
                    }

                }


            });
        });


        $(document).on("submit", "#update-cover", function(event){
            event.preventDefault();
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
                    $("#update-cover").find('label span').addClass('is-loading');
                },
                success: function (data){
                    $("#update-cover").find('.-label span').removeClass('is-loading');
                    if(data.status == 0){
                        $.each(data.error, function (prefix, val){
                            Toast.fire({
                                icon: 'error',
                                title: val[0]
                            })
                        })
                    }else if(data.status == 1){
                        Toast.fire({
                            icon: 'success',
                            title: 'تصویر کاور با موفقیت تغییر کرد'
                        })
                    }

                }


            });
        });
    });
