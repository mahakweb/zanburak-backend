"use strict";
var KTUsersUpdateComment = function() {
    return {
        init: function() {
            document.querySelectorAll('[data-kt-menu-id="kt-users-comments"]').forEach((t => {
                const e = t.querySelector('[data-kt-users-update-comment-status="reset"]'),
                    n = t.querySelector('[data-kt-users-update-comment-status="submit"]'),
                    o = t.querySelector('[data-kt-menu-id="kt-users-comments-form"]');
                var a = FormValidation.formValidation(o, {
                    fields: {
                        comment_status: {
                            validators: {
                                notEmpty: {
                                    message: "فیلد وضعیت الزامی است"
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
                $(o.querySelector('[name="comment_status"]')).on("change", (function() {
                    a.revalidateField("comment_status")
                })), e.addEventListener("click", (e => {
                    e.preventDefault(), Swal.fire({
                        text: "آیا مطمئن هستید که می خواهید بازنشانی کنید؟",
                        icon: "warning",
                        showCancelButton: !0,
                        buttonsStyling: !1,
                        confirmButtonText: "بله، بازنشانی کن!",
                        cancelButtonText: "خیر",
                        customClass: {
                            confirmButton: "btn btn-primary",
                            cancelButton: "btn btn-active-light"
                        }
                    }).then((function(e) {
                        e.value ? (o.reset(), t.hide()) : "cancel" === e.dismiss && Swal.fire({
                            text: "فرم بازنشانی نشد!.",
                            icon: "error",
                            buttonsStyling: !1,
                            confirmButtonText: "بسیار خب!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        })
                    }))
                })), n.addEventListener("click", (e => {
                    e.preventDefault(), a && a.validate().then((function(e) {
                        console.log("validated!"), "Valid" == e ? (n.setAttribute("data-kt-indicator", "on"), n.disabled = !0, setTimeout((function() {



                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
                                }
                            });
                            
                            // console.log($(o).find('input[name="comment_id"]').val())
                            // var info = $(o).serialize()
                            // var m = {'comment_id': $(o).find('input[name="comment_id"]').val(), 'comment_status': $(o).find('select[name="comment_status"]').val(), '_token': $('meta[name="csrf-token"]').attr('content')}
                            // console.log(m)
                            
                            $.ajax({
                                    url: $(o).attr('action'),
                                    method: $(o).attr('method'),
                                    data: { comment_id: $(o).find('input[name="comment_id"]').val(), comment_status: $(o).find('select[name="comment_status"]').val()},
                                    datatype: "JSON",
                                    success: function (data){
                                        n.removeAttribute("data-kt-indicator"), n.disabled = !1
                                        // console.log(data.msg)
                                        if(data.status == 0) {
                                            
                                            Swal.fire({
                                                text: "با عرض پوزش، به نظر می رسد برخی از خطاها شناسایی شده است، لطفا دوباره امتحان کنید.",
                                                icon: "error",
                                                buttonsStyling: !1,
                                                confirmButtonText: "بسیار خب!",
                                                customClass: {
                                                    confirmButton: "btn btn-primary"
                                                }
                                            }).then((function() {}))

                                        }else if(data.status == 1){
                                            var text = ''
                                            if($(o).find('select[name="comment_status"]').val() == 1){
                                                text = ' تایید و انتشار '
                                            }else{
                                                text = ' عدم تایید '
                                            }

                                            Swal.fire({
                                                text: 'وضعیت کامنت مورد نظر با موفقیت به'+text+'تغییر داده شد.',
                                                icon: "success",
                                                buttonsStyling: !1,
                                                confirmButtonText: "بسیار خب!",
                                                customClass: {
                                                    confirmButton: "btn btn-primary"
                                                }
                                            }).then((function(e) {
                                                e.isConfirmed && t.hide()
                                            }))


                                        }


                                    }
                                });



                        }), 2e3)) : Swal.fire({
                            text: "با عرض پوزش، به نظر می رسد برخی از خطاها شناسایی شده است، لطفا دوباره امتحان کنید.",
                            icon: "error",
                            buttonsStyling: !1,
                            confirmButtonText: "بسیار خب!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        }).then((function() {}))
                    }))
                }))
            }))
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTUsersUpdateComment.init()
}));
