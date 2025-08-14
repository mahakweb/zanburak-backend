"use strict";
var KTUsersRoleDelete = function() {
    var t, e, f, o;
    return {
        init: function() {
            (e = document.querySelector("#kt_roles_table")) 
            && 
            (e.querySelectorAll('[data-kt-roles-table-filter="delete_row"]').forEach((e => {
                e.addEventListener("click", (function(e) {
                    e.preventDefault();
                    const n = e.target.closest(".col-md-4");
                        o = n.querySelectorAll(".card .card-header .card-title")[0].innerText;
                        f = n.querySelectorAll("form.delete-role");
                        // console.log(o)
                    Swal.fire({
                        text: "مطمئنی میخوای گروه  " + o + " رو حذف کنی؟",
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
                                            text: "گروه  " + o + " حذف شد! ",
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
                                text: o + " حذف نشد.",
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
    KTUsersRoleDelete.init()
}));