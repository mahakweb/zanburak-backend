"use strict";

var KTUsersViewRole = function() {
    var selected = [];
    var t, e, f, o = () => {
        const r = e.querySelectorAll('[type="checkbox"]'),
            c = document.querySelector('[data-kt-view-roles-table-select="delete_selected"]');
            
        r.forEach((t => {
            t.addEventListener("click", (function() {
                setTimeout((function() {
                    n()
                }), 50)
            }))
        })), c.addEventListener("click", (function() {
            var z = $(c.closest("form"))
            Swal.fire({
                text: "از حذف کاربران انتخاب شده از این گروه اطمینان دارید؟",
                icon: "warning",
                showCancelButton: !0,
                buttonsStyling: !1,
                confirmButtonText: "بله!",
                cancelButtonText: "لغو",
                customClass: {
                    confirmButton: "btn fw-bold btn-danger",
                    cancelButton: "btn fw-bold btn-active-light-primary"
                }
            }).then((function(c) {
                if(c.value){
                    r.forEach((e => {
                        if($(e).prop("checked") == true){
                            selected.push(e.value)
                        }
                        
                    }));
                    selected = $.unique(selected);

                    
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
                        }
                    });

                    $.ajax({
                        url: $(z).attr('action'),
                        method: $(z).attr('method'),
                        data: {_method: 'delete',user_id: selected},
                        datatype: "json",
                        success: function (data){
                            if(data.status == 1){
                                selected = []
                                Swal.fire({
                                    text: " کاربران انتخاب شده از این گروه حذف شدند! ",
                                    icon: "success",
                                    buttonsStyling: !1,
                                    confirmButtonText: "بسیار خب!",
                                    customClass: {
                                        confirmButton: "btn fw-bold btn-primary"
                                    }
                                }).then((function() {
                                    r.forEach((e => {
                                        e.checked && t.row($(e.closest("tbody tr"))).remove().draw()
                                    }));
                                    e.querySelectorAll('[type="checkbox"]')[0].checked = !1
                                })).then((function() {
                                    n(), o()
                                    
                                }))
                            }
                        }
                    });


                }else if("cancel" === c.dismiss) {
                    Swal.fire({
                    text: "عملیات لغو شد!",
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
    };
    const n = () => {
        const t = document.querySelector('[data-kt-view-roles-table-toolbar="base"]'),
            o = document.querySelector('[data-kt-view-roles-table-toolbar="selected"]'),
            n = document.querySelector('[data-kt-view-roles-table-select="selected_count"]'),
            r = e.querySelectorAll('tbody [type="checkbox"]');
        let c = !1,
            l = 0;
        r.forEach((t => {
            t.checked && (c = !0, l++)
        })), c ? (n.innerHTML = l, t.classList.add("d-none"), o.classList.remove("d-none")) : (t.classList.remove("d-none"), o.classList.add("d-none"))
    };
    return {
        init: function() {
            (e = document.querySelector("#kt_roles_view_table")) && (e.querySelectorAll("tbody tr").forEach((t => {
                const e = t.querySelectorAll("td"),
                    o = moment(e[2].innerHTML, "DD MMM YYYY, LT").format();
                e[2].setAttribute("data-order", o)
            })), t = $(e).DataTable({
                info: !1,
                order: [],
                pageLength: 5,
                lengthChange: !1,
                columnDefs: [{
                    orderable: !1,
                    targets: 0
                }, {
                    orderable: !1,
                    targets: 3
                }]
            }), document.querySelector('[data-kt-roles-table-filter="search"]').addEventListener("keyup", (function(e) {
                t.search(e.target.value).draw()
            })), e.querySelectorAll('[data-kt-roles-table-filter="delete_row"]').forEach((e => {
                e.addEventListener("click", (function(e) {
                    e.preventDefault();
                    const o = e.target.closest("tr"),
                        n = o.querySelectorAll("td")[1].innerText;
                        f = o.querySelectorAll("form.detachRole");
                    Swal.fire({
                        // text: "مطمئنی که میخوای  " + n + " رو از این دسترسی سلب کنی؟",
                        text: "از حذف کاربر انتخاب شده از این گروه اطمینان دارید؟",
                        icon: "warning",
                        showCancelButton: !0,
                        buttonsStyling: !1,
                        confirmButtonText: "بله!",
                        cancelButtonText: "لغو",
                        customClass: {
                            confirmButton: "btn fw-bold btn-danger",
                            cancelButton: "btn fw-bold btn-active-light-primary"
                        }
                    }).then((function(e) {
                        if(e.value){


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
                                            // text:  n + " از این دسترسی سلب شد!",
                                            text: " کاربر مورد نظر از این گروه حذف شد!",
                                            icon: "success",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn fw-bold btn-primary"
                                            }
                                        }).then((function() {
                                            t.row($(o)).remove().draw()
                                        }))
                                    }
                                }
                            });
 
                        }
                        else if("cancel" === e.dismiss){
                            Swal.fire({
                                text: "عملیات انجام نشد!",
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
            })), o())
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTUsersViewRole.init()
}));