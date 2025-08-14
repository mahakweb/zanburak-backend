"use strict";
var KTCoursesEpisodeDelete = function() {
    var t, e, f;
    return {
        init: function() {
            (e = document.querySelector("#kt_sections_table")) 
            && 
            
            (e.querySelectorAll('.delete-episode').forEach((e => {
                
                e.addEventListener("click", (function(e) {
                    e.preventDefault();
                    const n = e.target.closest(".episodes-table");
                        f = n.querySelectorAll("form.delete-episode");
                        // console.log(o)
                    Swal.fire({
                        text: "مطمئنی میخوای این جلسه رو حذف کنی؟ چون تمام اطلاعاتش هم حذف میشن.",
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
                                            text: "جلسه مورد نظر با موفقیت حذف شد! ",
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
                                text: "جلسه مورد نظر حذف نشد.",
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
    KTCoursesEpisodeDelete.init()
}));