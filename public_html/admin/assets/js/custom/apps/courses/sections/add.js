"use strict";
var KTCourseAddSection = function() {
    const t = document.getElementById("kt_modal_add_section"),
        e = t.querySelector("#kt_modal_add_section_form"),
        n = new bootstrap.Modal(t);
    return {
        init: function() {
            (() => {
                var o = FormValidation.formValidation(e, {
                    fields: {
                        title: {
                            validators: {
                                notEmpty: {
                                    message: "فیلد عنوان ضروری است"
                                },
                            }
                        },
                        english_title: {
                            validators: {
                                notEmpty: {
                                    message: "فیلد عنوان لاتین ضروری است"
                                },
                                
                            }
                        }
                    },
                    plugins: {
                        trigger: new FormValidation.plugins.Trigger,
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                            rowSelector: ".fv-row",
                            eleInvalidClass: "",
                            eleValidClass: ""
                        })
                    }
                });
                t.querySelector('[data-kt-sections-modal-action="close"]').addEventListener("click", (t => {
                    t.preventDefault(), Swal.fire({
                        text: "مطمئنی میخوای فرم رو ببندی؟",
                        icon: "warning",
                        showCancelButton: !0,
                        buttonsStyling: !1,
                        confirmButtonText: "بله!",
                        cancelButtonText: "لغو",
                        customClass: {
                            confirmButton: "btn btn-primary",
                            cancelButton: "btn btn-active-light"
                        }
                    }).then((function(t) {
                        t.value && n.hide()
                    }))
                })), t.querySelector('[data-kt-sections-modal-action="cancel"]').addEventListener("click", (t => {
                    t.preventDefault(), Swal.fire({
                        text: "مطمئنی میخوای فرم رو ببندی؟",
                        icon: "warning",
                        showCancelButton: !0,
                        buttonsStyling: !1,
                        confirmButtonText: "بله!",
                        cancelButtonText: "لغو",
                        customClass: {
                            confirmButton: "btn btn-primary",
                            cancelButton: "btn btn-active-light"
                        }
                    }).then((function(t) {
                        t.value ? (e.reset(), n.hide()) : "cancel" === t.dismiss && Swal.fire({
                            text: "فرم لغو نشد!",
                            icon: "error",
                            buttonsStyling: !1,
                            confirmButtonText: "بسیار خب",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        })
                    }))
                }));
                const i = t.querySelector('[data-kt-sections-modal-action="submit"]');
                i.addEventListener("click", (function(t) {
                    t.preventDefault(), o && o.validate().then((function(t) {
                        console.log("validated!"), "Valid" == t ? (i.setAttribute("data-kt-indicator", "on"), i.disabled = !0, setTimeout((function() {
                            
                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
                                }
                            });
                            $.ajax({
                                url: $(e).attr('action'),
                                method: $(e).attr('method'),
                                data: $(e).serialize(),
                                datatype: "json",
                                beforeSend: function (){
                                    $(e).find("div.error_text div").text('');
                                },
                                success: function (data){
                                    i.removeAttribute("data-kt-indicator"), i.disabled = !1
                                    if(data.status == 0){
                                    $.each(data.error, function (prefix, val){
                                        $("div."+prefix+"_error div").text(val[0]);
                                    })
                                    }else if(data.status == 1){

                                        Swal.fire({
                                            text: "فصل جدید با موفقیت ایجاد شد!",
                                            icon: "success",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        }).then((function(t) {
                                            // t.isConfirmed && n.hide()
                                            location.reload()
                                        }))

                                    }
                                                    
                                }
                                                    
                            });


                        }), 2e3)) : Swal.fire({
                            text: "به نظر تعدادی خطا وجود داره. اونارو برطرف کن بعدا تلاش کن!",
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
    KTCourseAddSection.init()
}));