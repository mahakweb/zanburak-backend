$(document).ready(function() {
    // Configure/customize these variables.
    var showChar = 260;  // How many characters are shown by default
    var ellipsestext = "...";
    var moretext = "<button class='btn btn-sm btn-outline-yellow mx-auto mt-2'> بیشتر <i class='fa fa-eye ml-1'></i></button>";
    var lesstext = "<button class='btn btn-sm btn-outline-yellow mx-auto mt-2'> کمتر <i class='fa fa-eye-slash ml-1'></i></button>";
    

    $('.more').each(function() {
        var content = $(this).html();
 
        if(content.length > showChar) {
 
            var c = content.substr(0, showChar);
            var h = content.substr(showChar, content.length - showChar);
 
            var html = c + '<span class="moreellipses">' + ellipsestext+ '&nbsp;</span><span class="morecontent"><span>' + h + '</span>&nbsp;&nbsp;<a href="" class="morelink">' + moretext + '</a></span>';
 
            $(this).html(html);
        }
 
    });
 
    $(".morelink").click(function(){
        if($(this).hasClass("less")) {
            $(this).removeClass("less");
            $(this).html(moretext);
        } else {
            $(this).addClass("less");
            $(this).html(lesstext);
        }
        $(this).parent().prev().toggle(500);
        $(this).prev().toggle(500);
        return false;
    });
});