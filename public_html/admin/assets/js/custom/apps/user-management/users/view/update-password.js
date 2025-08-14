"use strict";
var KTUsersUpdatePassword = function() {
    const t = document.getElementById("kt_modal_update_password"),
        e = t.querySelector("#kt_modal_update_password_form"),
        n = new bootstrap.Modal(t);
    return {
        init: function() {
            (() => {
                var o = FormValidation.formValidation(e, {
                    fields: {
                        // current_password: {
                        //     validators: {
                        //         notEmpty: {
                        //             message: "فیلد رمز عبور فعلی ضروری است"
                        //         }
                        //     }
                        // },
                        new_password: {
                            validators: {
                                notEmpty: {
                                    message: "فیلد رمز عبور ضروری است"
                                },
                                regexp: {
                                    regexp: "^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$",
                                    message: "رمز عبور یک فرمت معتبر نیست",
                                },
                                // stringLength: {
                                //     min: 8,
                                //     message: 'رمز عبور باید حداقل 8 کاراکتر باشد',
                                // },
                                callback: {
                                    message: "لطفا یک رمز عبور معتبر وارد کنید",
                                    callback: function(t) {
                                        if (t.value.length > 0) return validatePassword()
                                    }
                                }
                            }
                        },
                        confirm_password: {
                            validators: {
                                notEmpty: {
                                    message: "فیلد تکرار رمز عبور ضروری است"
                                },
                                identical: {
                                    compare: function() {
                                        return e.querySelector('[name="new_password"]').value
                                    },
                                    message: "رمز عبور با تکرار رمز عبور مطابقت ندارد"
                                }
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
                t.querySelector('[data-kt-users-modal-action="close"]').addEventListener("click", (t => {
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
                            confirmButtonText: "بسیار خب!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        })
                    }))
                })), t.querySelector('[data-kt-users-modal-action="cancel"]').addEventListener("click", (t => {
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
                            confirmButtonText: "بسیار خب!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        })
                    }))
                }));
                const a = t.querySelector('[data-kt-users-modal-action="submit"]');
                a.addEventListener("click", (function(t) {
                    t.preventDefault(), o && o.validate().then((function(t) {
                        console.log("validated!"), "Valid" == t && (a.setAttribute("data-kt-indicator", "on"), a.disabled = !0, setTimeout((function() {
                            

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
                                // beforeSend: function (){
                                //     $(e).find("div.error_text div").text('');
                                // },
                                success: function (data){
                                    a.removeAttribute("data-kt-indicator"), a.disabled = !1
                                    if(data.status == 0){
                                        // $.each(data.error, function (prefix, val){
                                        //     $("div."+prefix+"_error div").text(val[0]);
                                        // })
                                        Swal.fire({
                                            text: "خطا لطفا دوباره تلاش کنید!",
                                            icon: "error",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        })
                                    }else if(data.status == 1){
                                       
                                        Swal.fire({
                                            text: "رمز عبور با موفقیت تغییر یافت!",
                                            icon: "success",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        }).then((function(t) {
                                            t.isConfirmed && n.hide()
                                        }))


                                    }
                    
                                }
                    
                    
                            });
  
                           
                        }), 2e3))
                    }))
                }))
            })()
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTUsersUpdatePassword.init()
}));