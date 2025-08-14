"use strict";
var KTAppEditCategory = function() {
    const e = () => {
            $("#kt_edit_category_conditions").repeater({
                initEmpty: !1,
                defaultValues: {
                    "text-input": "foo"
                },
                show: function() {
                    $(this).slideDown(), t()
                },
                hide: function(e) {
                    $(this).slideUp(e)
                }
            })
        },
        t = () => {
            document.querySelectorAll('[data-kt-catalog-edit-category="condition_type"]').forEach((e => {
                $(e).hasClass("select2-hidden-accessible") || $(e).select2({
                    minimumResultsForSearch: -1
                })
            }));
            document.querySelectorAll('[data-kt-catalog-edit-category="condition_equals"]').forEach((e => {
                $(e).hasClass("select2-hidden-accessible") || $(e).select2({
                    minimumResultsForSearch: -1
                })
            }))
        };
    return {
        init: function() {
            ["#kt_edit_category_description", "#kt_edit_category_meta_description"].forEach((e => {
                let t = document.querySelector(e);
                t && (t = new Quill(e, {
                    modules: {
                        toolbar: [
                            [{
                                header: [1, 2, !1]
                            }],
                            ["bold", "italic", "underline"],
                            ["image", "code-block"]
                        ]
                    },
                    placeholder: "Type your text here...",
                    theme: "snow"
                }))
            })), ["#kt_edit_category_meta_keywords"].forEach((e => {
                const t = document.querySelector(e);
                t && new Tagify(t)
            })), e(), t(), (() => {
                const e = document.getElementById("kt_edit_category_status"),
                    t = document.getElementById("kt_edit_category_status_select"),
                    o = ["bg-success", "bg-danger"];
                $(t).on("change", (function(t) {
                    switch (t.target.value) {
                        case "1":
                            e.classList.remove(...o), e.classList.add("bg-success");
                            break;
                        case "0":
                            e.classList.remove(...o), e.classList.add("bg-danger")
                    }
                }));

            })(), (() => {
                const e = document.querySelectorAll('[name="method"][type="radio"]'),
                    t = document.querySelector('[data-kt-catalog-edit-category="auto-options"]');
                e.forEach((e => {
                    e.addEventListener("change", (e => {
                        "1" === e.target.value ? t.classList.remove("d-none") : t.classList.add("d-none")
                    }))
                }))
            })(), (() => {
                let e;
                const t = document.getElementById("kt_edit_category_form"),
                    o = document.getElementById("kt_edit_category_submit");
                e = FormValidation.formValidation(t, {
                    // fields: {
                    //     icon: {
                    //         validators: {
                    //             notEmpty: {
                    //                 message: "فیلد آیکون الزامی است"
                    //             }
                    //         }
                    //     },
                    //     title: {
                    //         validators: {
                    //             notEmpty: {
                    //                 message: "فیلد عنوان فارسی الزامی است"
                    //             },
                    //             stringLength: {
                    //                 min: 5,
                    //                 max: 255,
                    //                 message: "لطفا حداقل 5 کاراکتر وارد کنید"
                    //             }
                    //         }
                    //     },
                    //     english_title: {
                    //         validators: {
                    //             notEmpty: {
                    //                 message: "فیلد عنوان لاتین الزامی است"
                    //             },
                    //             stringLength: {
                    //                 min: 5,
                    //                 max: 255,
                    //                 message: "لطفا حداقل 5 کاراکتر وارد کنید"
                    //             },
                    //             regexp: {
                    //                 regexp: /^[~`!@#$%^&*()_+=[\]\\{}|;':",.\/<>?a-zA-Z0-9- ]+$/,
                    //                 message: "لطفا فقط از حروف لاتین استفاده کنید",
                    //             },
                    //         }
                    //     },
                    //     status: {
                    //         validators: {
                    //             notEmpty: {
                    //                 message: "فیلد وضعیت الزامی است"
                    //             }
                    //         }
                    //     },
                    //     parent_id: {
                    //         validators: {
                    //             notEmpty: {
                    //                 message: "فیلد دسته والد الزامی است"
                    //             }
                    //         }
                    //     },
                    //     tags: {
                    //         validators: {
                    //             notEmpty: {
                    //                 message: "فیلد برچسب الزامی است"
                    //             }
                    //         }
                    //     },
                    // },
                    // plugins: {
                    //     trigger: new FormValidation.plugins.Trigger,
                    //     bootstrap: new FormValidation.plugins.Bootstrap5({
                    //         rowSelector: ".fv-row",
                    //         eleInvalidClass: "",
                    //         eleValidClass: ""
                    //     })
                    // }
                }), o.addEventListener("click", (a => {
                    a.preventDefault(), e && e.validate().then((function(e) {
                        "Valid" == e ? (o.setAttribute("data-kt-indicator", "on"), o.disabled = !0, setTimeout((function() {

                            const form = new FormData(t)

                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
                                }
                            });
                            $.ajax({
                                url: $(t).attr('action'),
                                method: $(t).attr('method'),
                                data: form,
                                datatype: "json",
                                async: false,
                                processData: false,
                                contentType: false,
                                beforeSend: function (){
                                    $(t).find("div.error_text div").text('');
                                },
                                success: function (data){
                                    o.removeAttribute("data-kt-indicator"), o.disabled = !1
                                    if(data.status == 0){
                                    $.each(data.error, function (prefix, val){
                                        $("div."+prefix+"_error div").text(val[0]);

                                        Swal.fire({
                                            html: "به نظر میرسه تعدادی خطا وجود داره اونارو برطرف کن بعدا تلاش کن.",
                                            icon: "error",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        })
                                    })
                                    }else if(data.status == 1){
                                        Swal.fire({
                                            text: "دسته بندی با موفقیت ویرایش شد!",
                                            icon: "success",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        }).then((function(e) {
                                            e.isConfirmed && (o.disabled = !1, window.location = t.getAttribute("data-kt-redirect"))
                                        }))

                                    }

                                }

                            });

                        }), 2e3)) : Swal.fire({
                            html: "به نظر میرسه تعدادی خطا وجود داره اونارو برطرف کن بعدا تلاش کن.",
                            icon: "error",
                            buttonsStyling: !1,
                            confirmButtonText: "بسیار خب!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        })
                    }))
                }))
            })()
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTAppEditCategory.init()
}));
