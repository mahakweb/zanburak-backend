"use strict";
var KTUsersAddRole = function() {
    // var selected = [];
    // var z;
    const t = document.getElementById("kt_modal_add_role"),
        e = t.querySelector("#kt_modal_add_role_form"),
        n = new bootstrap.Modal(t);
        
    return {
        init: function() {
            (() => {
                var o = FormValidation.formValidation(e, {
                    fields: {
                        name: {
                            validators: {
                                notEmpty: {
                                    message: "فیلد نام دسترسی ضروری است"
                                },
                                regexp: {
                                    regexp: "^[a-zA-z\-]+$",
                                    message: "لطفا فقط از حروف لاتین و علامت (-) استفاده کنید",
                                },
                            }
                        },
                        label: {
                            validators: {
                                notEmpty: {
                                    message: "فیلد توضیح دسترسی ضروری است"
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
                t.querySelector('[data-kt-roles-modal-action="close"]').addEventListener("click", (t => {
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
                })), t.querySelector('[data-kt-roles-modal-action="cancel"]').addEventListener("click", (t => {
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
                const r = t.querySelector('[data-kt-roles-modal-action="submit"]');
                r.addEventListener("click", (function(t) {
                    t.preventDefault(), o && o.validate().then((function(t) {
                        console.log("validated!"), "Valid" == t ? (r.setAttribute("data-kt-indicator", "on"), r.disabled = !0, setTimeout((function() {
                            



                            // z = e.querySelectorAll('[type="checkbox"]')
                            // z.forEach((e => {
                            //     if($(e).prop("checked") == true && $(e).prop("id") != "kt_roles_select_all"){
                            //         selected.push(e.value)
                            //     }
                                
                            // }));
                            // selected = $.unique(selected);
    
                           
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
                                    r.removeAttribute("data-kt-indicator"), r.disabled = !1
                                    if(data.status == 0){
                                        $.each(data.error, function (prefix, val){
                                            $("div."+prefix+"_error div").text(val[0]);
                                        })
                                        }else if(data.status == 1){
                                        // selected = []
                                        
                                        // console.log(data.msg)
                                        Swal.fire({
                                            text: "گروه با موفقیت ایجاد شد!",
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

                            
                        }), 2e3)) : Swal.fire({
                            text: "به نظر تعدادی خطا وجود داره، اونارو برطرف کن بعدا تلاش کن!",
                            icon: "error",
                            buttonsStyling: !1,
                            confirmButtonText: "بسیار خب!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        })
                    }))
                }))
            })(), (() => {
                const t = e.querySelector("#kt_roles_select_all"),
                    n = e.querySelectorAll('[type="checkbox"]');
                t.addEventListener("change", (t => {
                    n.forEach((e => {
                        e.checked = t.target.checked
                    }))
                }))
            })()
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTUsersAddRole.init()
}));