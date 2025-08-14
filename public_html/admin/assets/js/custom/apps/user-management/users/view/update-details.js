"use strict";
var KTUsersUpdateDetails = function() {
    const t = document.getElementById("kt_modal_update_details"),
        e = t.querySelector("#kt_modal_update_user_form"),
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
                const o = t.querySelector('[data-kt-users-modal-action="submit"]');
                o.addEventListener("click", (function(t) {
                    t.preventDefault(), o.setAttribute("data-kt-indicator", "on"), o.disabled = !0, setTimeout((function() {

                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
                            }
                        });
                        
                        // console.log($(o).find('input[name="comment_id"]').val())
                        // var info = $(o).serialize()
                        // var m = {'comment_id': $(o).find('input[name="comment_id"]').val(), 'comment_status': $(o).find('select[name="comment_status"]').val(), '_token': $('meta[name="csrf-token"]').attr('content')}
                        // console.log(m)
                        let files = new FormData()
                        files.append('profile_pic', $(e).find('input[name="profile_pic"]')[0].files[0]);
                        // var info = $(e).find('input[name="profile_pic"]')[0].files
                        $.ajax({
                                url: $(e).attr('action'),
                                method: $(e).attr('method'),
                                data: files,
                                datatype: "JSON",
                                async: false,
                                cache: false,
                                contentType: false,
                                enctype: 'multipart/form-data',
                                processData: false,
                                success: function (data){
                                    o.removeAttribute("data-kt-indicator"), o.disabled = !1
                                    // console.log(data.msg)
                                    if(data.status == 0) {
                                        
                                        Swal.fire({
                                            text: "عملیات با خطا مواجه شد!",
                                            icon: "error",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        }).then((function(t) { }))

                                    }else if(data.status == 1){
                                        
                                        Swal.fire({
                                            // text: "اطلاعات با موفقیت ویرایش شد!",
                                            text: data.msg,
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

                    }), 2e3)
                }))
            })()
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTUsersUpdateDetails.init()
}));