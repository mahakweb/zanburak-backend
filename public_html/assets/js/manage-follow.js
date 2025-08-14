$(document).on('submit' , '#send-follow', function (e){

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
                    title: 'برای فالو کردن این کاربر ابتدا نیاز است وارد سایت شوید!'
                })
            }else if(data.status == 1){
                Toast.fire({
                    icon: 'warning',
                    title: 'شما نمیتوانید خودتان را فالو کنید!'
                })
            }else{
                var check = '';
                if(data.hasFollow == true){
                    check = 'فالو'
                    x.find('#follow-check').text('آنفالو کردن')
                }else if (data.hasFollow == false){
                    check = 'آنفالو'
                    x.find('#follow-check').text('فالو کردن')
                }
                Toast.fire({
                    icon: 'success',
                    title: 'کاربر مورد نظر با موفقیت '+check+' شد.'
                })
            }


        }
    });

});
