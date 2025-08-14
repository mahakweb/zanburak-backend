const easyMDE = new EasyMDE({
    element: document.getElementById('editor'),
    autofocus: false,
    maxHeight: "300px",
    placeholder: "متن خودرا اینجا وارد کنید...",
    toolbar: [
        {
            name: 'heading',
            action: EasyMDE.toggleHeadingSmaller,
            className: 'fas fa-heading',
            title: 'عنوان'
        },
        {
            name: "bold",
            action: EasyMDE.toggleBold,
            className: "fa fa-bold",
            title: "تاکید",
        },
        {
            name: 'italic',
            action: EasyMDE.toggleItalic,
            className: 'fas fa-italic',
            title: 'متن کج'
        },

        '|',
        {
            name: "horizontal-rule",
            action: EasyMDE.drawHorizontalRule,
            className: "fa fa-align-center",
            title: "خط جدا کننده",
        },
        {
            name: 'unordered-list',
            action: EasyMDE.toggleUnorderedList,
            className: 'fa fa-list-ul',
            title: 'لیست'
        },
        {
            name: 'ordered-list',
            action: EasyMDE.toggleOrderedList,
            className: 'fa fa-list-ol',
            title: 'لیست شماره‌ای'
        },
        '|',
        {
            name: 'quote',
            action: EasyMDE.toggleBlockquote,
            className: 'fa fa-quote-left',
            title: 'نقل قول'
        },
        {
            name: 'link',
            action: EasyMDE.drawLink,
            className: 'fa fa-link',
            title: 'لینک'
        },
        {
            name: 'image',
            action: EasyMDE.drawImage,
            className: 'fa fa-image',
            title: 'تصویر'
        },
        {
            name: "code",
            action: EasyMDE.toggleCodeBlock,
            className: "fa fa-code",
            title: "قرار دادن کد",
        },
        {
            name: "uploadImage",
            action: function uploadImage(editor) {
                // Add your own code
                $('#modal-upload-image').modal('show');
            },
            className: "fa fa-images",
            title: "آپلود تصویر",
        },
        '|',
        {
            name: 'undo',
            action: EasyMDE.undo,
            className: 'fa fa-undo',
            title: 'عقب'
        },
        {
            name: 'redo',
            action: EasyMDE.redo,
            className: 'fa fa-redo',
            title: 'جلو'
        },

        // {
        //     name: "others",
        //     className: "fa fa-plus",
        //     title: "other btn",
        //     children: [
        //         {
        //             name: "preview",
        //             action: EasyMDE.togglePreview,
        //             className: "fa fa-eye no-disable",
        //             title: "preview",
        //         },
        //     ]
        // },

    ],

    // shortcuts: {
    //     uploadImage: "Cmd-Alt-M",
    //     drawHorizontalRule: "Cmd-Alt-R"
    // },

    initialValue: ' ',
    hideIcons: ["guide", "fullscreen", "side-by-side"],
    forceSync: true,
    direction: 'rtl',
    insertTexts: {
        horizontalRule: ["", "\n\n-----\n\n"],
        image: ["![](http://", ")"],
        link: ["[", "](https://)"],
        table: ["", "\n\n| Column 1 | Column 2 | Column 3 |\n| -------- | -------- | -------- |\n| Text     | Text      | Text     |\n\n"],
    },
    autoDownloadFontAwesome: false,
    renderingConfig: {
        codeSyntaxHighlighting: true
    },
    status: false,
    spellChecker: false,
    lineWrapping: true,



});




$(document).on('submit', '#editor-upload-image', function(event) {
    event.preventDefault();
    $(this).find("button[type=submit]").addClass('is-loading');
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        }
    });
    var pos = easyMDE.codemirror.getCursor();
    $.ajax({
        url: $(this).attr('action'),
        method: $(this).attr('method'),
        data: new FormData(this),
        datatype: "json",
        contentType: false,
        processData: false,
        beforeSend: function() {
            $(document).find("span.error-text strong").text('');
            $(document).find("input.is-invalid").removeClass('is-invalid');
        },
        success: function(data) {
            $('#editor-upload-image').find("button[type=submit]").removeClass('is-loading');

            if (data.status == 0) {
                $.each(data.error, function(prefix, val) {
                    $("span." + prefix + "_error strong").text(val[0]);
                    $("input[name=" + prefix + "]").addClass('is-invalid');
                })
            } else if (data.status == 1) {
                easyMDE.codemirror.setSelection(pos, pos);
                easyMDE.codemirror.replaceSelection("![](" + data.path + ")\n"); //after // insert address image
                $('#editor-upload-image').each(function() {
                    this.reset();
                })
                $('#modal-upload-image').modal('hide');
            }

        }


    });


});





// $(document).on('click', '#output', function(event) {
//     event.preventDefault();
//     console.log(marked.parse(easyMDE.value()))
// });

$(document).on('change', '#preview-btn', function(event) {
    event.preventDefault();
    easyMDE.togglePreview()
});
