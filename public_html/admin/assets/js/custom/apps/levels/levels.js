"use strict";
var KTAppLevels = function() {
    var t, e, n = () => {
        t.querySelectorAll('[data-kt-level-filter="delete_row"]').forEach((t => {
            t.addEventListener("click", (function(t) {
                t.preventDefault();
                const n = t.target.closest("tr"),
                    o = n.querySelector('[data-kt-level-filter="level_name"]').innerText,
                    f = n.querySelector('form');

                Swal.fire({
                    text: "از حذف سطح " + o + " اطمینان دارید؟",
                    icon: "warning",
                    showCancelButton: !0,
                    buttonsStyling: !1,
                    confirmButtonText: "بله حذف کن!",
                    cancelButtonText: "لغو",
                    customClass: {
                        confirmButton: "btn fw-bold btn-danger",
                        cancelButton: "btn fw-bold btn-active-light-primary"
                    }
                }).then((function(t) {

                    if(t.value){


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
                                        text: "سطح  " + o + " حذف شد!",
                                        icon: "success",
                                        buttonsStyling: !1,
                                        confirmButtonText: "بسیار خب!",
                                        customClass: {
                                            confirmButton: "btn fw-bold btn-primary"
                                        }
                                    }).then((function() {
                                        e.row($(n)).remove().draw()
                                    }))
                                }
                            }
                        });


                    }else if("cancel" === t.dismiss){

                        Swal.fire({
                            text: o + " حذف نشد .",
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
            (t = document.querySelector("#kt_level_table")) && ((e = $(t).DataTable({
                info: !1,
                order: [],
                pageLength: 10,
                columnDefs: [{
                    orderable: !1,
                    targets: 0
                }, {
                    orderable: !1,
                    targets: 3
                }]
            })).on("draw", (function() {
                n()
            })), document.querySelector('[data-kt-level-filter="search"]').addEventListener("keyup", (function(t) {
                e.search(t.target.value).draw()
            })), n())
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTAppLevels.init()
}));
