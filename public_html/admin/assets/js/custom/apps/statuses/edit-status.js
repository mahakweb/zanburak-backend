"use strict";
var KTAppEditStatus = function() {
    return {
        init: function() {
            (() => {
                let e;
                const t = document.getElementById("kt_edit_status_form"),
                    o = document.getElementById("kt_edit_status_submit");
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
                    //
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
                                            text: "وضعیت با موفقیت ویرایش شد!",
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
    KTAppEditStatus.init()
}));
