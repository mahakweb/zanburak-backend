"use strict";
var KTUsersUpdateRole = function() {
    const t = document.getElementById("kt_modal_update_role"),
        e = t.querySelector("#kt_modal_update_role_form"),
        n = new bootstrap.Modal(t);
    return {
        init: function() {
            (() => {
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
                            confirmButtonText: "Ok, got it!",
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
                const o = t.querySelector('[data-kt-users-modal-action="submit"]');
                o.addEventListener("click", (function(t) {
                    t.preventDefault(), o.setAttribute("data-kt-indicator", "on"), o.disabled = !0, setTimeout((function() {
                        
                        

                        $.post( $(e).attr('action'), $(e).serialize(), function( data ) {
                                
                            o.removeAttribute("data-kt-indicator"), o.disabled = !1 
                            if(data.status == 1){
                                Swal.fire({
                                    text: "گروه های کاربر مورد نظر با موفقیت بروزرسانی شد!",
                                    icon: "success",
                                    buttonsStyling: !1,
                                    confirmButtonText: "بسیار خب!",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                }).then((function(t) {
                                    t.isConfirmed && n.hide()
                                }))
                            }else{
                                Swal.fire({
                                    text: "عملیات نا موفق بود لطفا دوباره تلاش کنید!",
                                    icon: "error",
                                    buttonsStyling: !1,
                                    confirmButtonText: "بسیار خب!",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                })
                            }
                            
                        });

                    
                    }), 2e3)
                }))
            })()
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTUsersUpdateRole.init()
}));