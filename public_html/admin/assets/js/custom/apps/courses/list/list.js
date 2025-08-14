"use strict";
var KTAppCorsesCourse = function() {
    var t, e, n = () => {
        t.querySelectorAll('[data-kt-courses-course-filter="delete_row"]').forEach((t => {
            t.addEventListener("click", (function(t) {
                t.preventDefault();
                var parent = t.target.closest('div');
                var form = parent.querySelector('form');
                // console.log(form);
                const n = t.target.closest("tr"),
                    r = n.querySelector('[data-kt-courses-course-filter="course_title"]').innerText;

                Swal.fire({
                    text: "آیا از حذف دوره "+r+" اطمینان دارید؟",
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
                            url: $(form).attr('action'),
                            method: $(form).attr('method'),
                            data: $(form).serialize(),
                            datatype: "json",
                            success: function (data){
                                if(data.status == 1){
                                    Swal.fire({
                                        text: "دوره " + r + " با موفقیت حذف شد! ",
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
                            text: "عملیات لغو شد.",
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
        }))
    };
    return {
        init: function() {
            (t = document.querySelector("#kt_courses_table")) && ((e = $(t).DataTable({
                info: !1,
                order: [],
                pageLength: 10,
                columnDefs: [
                {
                    orderable: !1,
                    targets: 0
                },
                {
                    orderable: !1,
                    targets: 8
                }]
            })).on("draw", (function() {
                n()
            })), document.querySelector('[data-kt-courses-course-filter="search"]').addEventListener("keyup", (function(t) {
                e.search(t.target.value).draw()
            })), (() => {
                const t = document.querySelector('[data-kt-courses-course-filter="status"]');
                $(t).on("change", (t => {
                    let n = t.target.value;
                    "all" === n && (n = ""), e.column(6).search(n).draw()
                }))
            })(), n())
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTAppCorsesCourse.init()
}));
