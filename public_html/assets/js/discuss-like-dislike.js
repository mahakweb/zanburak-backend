
$(document).on('submit' , '.discuss-like , .discuss-dislike', function (e){
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
                    icon: data.type,
                    title: data.msg
                })
            }else if(data.status == 1){

                x.closest('.like-dislike-section').find('.count').text(data.count)

            }


        }
    });

});

