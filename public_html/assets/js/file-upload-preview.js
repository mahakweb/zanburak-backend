function readURL(input, preivewElement) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $("#"+preivewElement).css(
                "background-image",
                "url(" + e.target.result + ")"
            );
            $("#"+preivewElement).hide();
            $("#"+preivewElement).fadeIn(650);
        };
        reader.readAsDataURL(input.files[0]);
    }
}
$("#poster").change(function () {
    readURL(this, 'poster-preview');
});
