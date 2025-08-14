$(function (){

    $("#add-new-section").on('submit', function (e){

        e.preventDefault();

        $('#add-new-section button[type="submit"]').addClass('is-loading');
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
            }
        });

        var info = new FormData(this);
        info.append('title', $('#create-section-title').val());
        info.append('status', $('#create-section-status').val());
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
                var num = 50;
                if(data.status == 0){
                    //error message
                    $('#add-new-section button[type="submit"]').removeClass('is-loading');
                    toastr.error('لطفا دوباره اقدام نمایید!', 'خطا');


                }else{
                    $('#add-new-section button[type="submit"]').removeClass('is-loading');
                    ++num;
                    var html = '';
                    html += '<div id="new-section" class="accordion__item">';
                    html += '<div class="accordion__toggle collapsed flex" style="cursor: pointer" data-toggle="collapse" data-target="#course-toc-'+(num)+'" data-parent="#parent">';
                    html += '<span class="flex">'+$('#create-section-title').val()+'</span>';
                    html += '<span class="accordion__toggle-icon "><i class="material-icons">keyboard_arrow_down</i></span>';
                    html += '<a href="/instructor/course/'+data.course_id+'/section/'+data.section_id+'/edit" title="ویرایش بخش" class="btn btn-sm btn-outline-warning mr-1"><i class="material-icons">border_color</i></a>';
                    html += '<form id="delete-section" class="mr-1" action="/instructor/course/'+data.course_id+'/section/'+data.section_id+'/delete" method="post">';
                    html += '<input type="hidden" name="_token" value="'+$('meta[name="csrf-token"]').attr('content')+'">';
                    html += '<input type="hidden" name="_method" value="DELETE">';
                    html += '<button type="submit"  class="btn btn-sm btn-outline-danger" title="حذف بخش"><i class="material-icons">delete</i></button>';
                    html += '</form>';
                    html += '<a href="/instructor/course/'+data.course_id+'/section/'+data.section_id+'/episode/create" title="افزودن قسمت جدید" class="btn btn-sm btn-secondary mr-1"><i class="material-icons">add</i></a>';
                    html += '</div>';
                    html += '<div class="accordion__menu collapse" id="course-toc-'+(num)+'">';
                    html += '</div>';
                    html += '</div>';

                    $('#parent').append(html);

                    toastr.success('بخش مورد نظر با موفقیت اضافه شد.', 'موفق' );
                    // swal('موفق','با موفقیت اضافه شد.', 'success');
                    $('#add-new-section #create-section-title').val('');
                    $('#section-empty').hide();
                }

            }


        });

    });


});
$(document).on('click' , '#delete-section', function (e){
        var x = $(this);
        e.preventDefault();
    $('#delete-section button[type="submit"]').addClass('is-loading');
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
                $('#delete-section button[type="submit"]').removeClass('is-loading');
                x.closest('#new-section').remove();
                toastr.success('بخش مورد نظر با موفقیت حذف شد.', 'موفق', 'close');
                // swal('موفق','با موفقیت حذف شد.', 'success');
                // alert(data.msg)
            }
        });

});
