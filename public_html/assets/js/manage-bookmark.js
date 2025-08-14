$(document).on('submit' , '#send-bookmark', function (e){

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
                    title: 'برای بوکمارک کردن ابتدا نیاز است وارد سایت شوید!'
                })
            }else{
                if(data.bookmark == true){
                    $(x).find('#fillOrEmpty').attr('fill', 'currentColor')
                    $(x).find('#fillOrEmpty').attr('stroke', 'none')
                    $(x).find('#countOfBookmark').text(data.count)
                }else if (data.bookmark == false){
                    $(x).find('#fillOrEmpty').attr('fill', 'none')
                    $(x).find('#fillOrEmpty').attr('stroke', 'currentColor')
                    $(x).find('#countOfBookmark').text(data.count)
                }

            }


        }
    });

});
