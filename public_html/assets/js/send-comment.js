$(document).on('submit' , '.send-comment', function (e){
    var x = $(this);
    e.preventDefault();

    $('.send-comment button[type="submit"]').addClass('is-loading');
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
            $('.send-comment button[type="submit"]').removeClass('is-loading');
            if(data.status == 0) {
                $.each(data.error, function (prefix, val) {
                    Toast.fire({
                        icon: 'error',
                        title: val[0]
                    })
                })
            }else{

                Toast.fire({
                    icon: 'success',
                    title: 'کامنت شما با موفقیت ثبت شد.'
                })

            }


        }
    });

});



function hideCommentForm(){
    $("#new-comment-form").addClass("d-none")
}

$(document).on('click', '.commentForm', function(e){
    var parentId = $(this).data("parent-id")

    $(this).closest('.list').after($('#new-comment-form'));

    $("#new-comment-form").removeClass("d-none") //this command may be change order to above command


    $("#new-comment-form form input[name=parent_id]").val(parentId)
    
    $('html, body').animate({
        scrollTop:$("#new-comment-form").offset().top
    }, 1000);
});