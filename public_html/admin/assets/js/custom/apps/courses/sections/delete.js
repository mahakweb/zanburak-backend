"use strict";
var KTCoursesSectionDelete = function() {
    var t, e, f;
    return {
        init: function() {
            (e = document.querySelector("#kt_sections_table")) 
            && 
            (e.querySelectorAll('[data-kt-sections-table-filter="delete_row"]').forEach((e => {
                e.addEventListener("click", (function(e) {
                    e.preventDefault();
                    const n = e.target.closest(".col-lg-6");
                        f = n.querySelectorAll("form.delete-section");
                        // console.log(o)
                    Swal.fire({
                        text: "مطمئنی میخوای این فصل رو حذف کنی؟ چون تمام جلساتش هم حذف میشن.",
                        icon: "warning",
                        showCancelButton: !0,
                        buttonsStyling: !1,
                        confirmButtonText: "بله حذف کن!",
                        cancelButtonText: "لغو",
                        customClass: {
                            confirmButton: "btn fw-bold btn-danger",
                            cancelButton: "btn fw-bold btn-active-light-primary"
                        }
                    }).then((function(e) {
                        if(e.value) {

                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
                                }
                            });

                            $.ajax({
                                url: $(f).attr('action'),
                                method: $(f).attr('method'),
                                data: $(f).serialize(),
                                datatype: "json",
                                success: function (data){
                                    if(data.status == 1){
                                        Swal.fire({
                                            text: "فصل با موفقیت حذف شد! ",
                                            icon: "success",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn fw-bold btn-primary"
                                            }
                                        }).then((function() {
                                            ($(n)).remove()
                                        }))
                                    }
                                }
                            });

                        } else if("cancel" === e.dismiss){
                            Swal.fire({
                                text: "فصل حذف نشد.",
                                icon: "error",
                                buttonsStyling: !1,
                                confirmButtonText: "بسیار خب!",
                                customClass: {
                                    confirmButton: "btn fw-bold btn-primary"
                                }
                            })
                        }
                        
                        
                    }))
                }))
            })))
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTCoursesSectionDelete.init()
}));