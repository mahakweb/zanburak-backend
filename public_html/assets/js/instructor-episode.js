
$(document).on('click' , '#delete-episode', function (e){

        e.preventDefault();
    var x = $(this);
        $('#delete-episode button[type="submit"]').addClass('is-loading');
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
            }
        });
        var info = new FormData(this);
        $.ajax({
            url: $(this).attr('action'),
            method: 'delete',
            async: false,
            data: info,
            datatype: "json",
            contentType: false,
            processData: false,
            beforeSend: function (){

            },
            success: function (data){
                $('#delete-episode button[type="submit"]').removeClass('is-loading');
                x.closest('#new-episode').remove();
                toastr.success('قسمت مورد نظر با موفقیت حذف شد.', 'موفق', 'close');
                // swal('موفق','با موفقیت حذف شد.', 'success');
                // alert(data.msg)
            }
        });

});
