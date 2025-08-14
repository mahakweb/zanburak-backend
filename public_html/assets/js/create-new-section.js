var num = 50;
$('#add-section').click(function (){
    ++num;
    var html = '';
    html += '<div id="new-section" class="accordion__item">';
    html += '<a href="#" class="accordion__toggle collapsed" data-toggle="collapse" data-target="#course-toc-'+(num)+'" data-parent="#parent">';
    html += '<span class="flex">'+$('#create-section-title').val()+'</span>';
    html += '<span class="accordion__toggle-icon "><i class="material-icons">keyboard_arrow_down</i></span>';
    html += '<span type="button" class="btn btn-sm btn-outline-warning mr-1"><i class="material-icons">border_color</i></span>';
    html += '<span type="button" id="delete-section" class="btn btn-sm btn-outline-danger"><i class="material-icons">delete</i></span>';
    html += '</a>';
    html += '<div class="accordion__menu collapse" id="course-toc-'+(num)+'">';
    html += '<div class="accordion__menu-link">';
    html += '<i class="material-icons text-70 icon-16pt icon--left">drag_handle</i>';
    html += '<a class="flex" href="learnly-student-lesson.html">Template Syntax</a>';
    html += '<span class="text-muted">04:23</span>';
    html += '</div>';
    html += '</div>';
    html += '</div>';

    $('#parent').append(html);
});
$(document).on('click' , '#delete-section', function (){
    $(this).closest('#new-section').remove();
});
