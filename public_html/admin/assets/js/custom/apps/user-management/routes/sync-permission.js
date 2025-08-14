"use strict";
var KTAppUserRouteSyncPermission = function() {

    var e;
    return {
        init: function() {

            (() => {
                e = document.querySelector("#kt_routes_table");
                e.querySelectorAll('[data-kt-routes-table-filter="sync_permission"]').forEach((e => {
                    const i = e.querySelector('[data-kt-routes-form-action="submit"]');
                    i.addEventListener("click", (function(t) {
                        t.preventDefault();

                        var form = this.closest('form')

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
                            beforeSend: function (){
                                i.setAttribute("data-kt-indicator", "on")
                                i.disabled = !0
                            },
                            success: function (data){
                                i.removeAttribute("data-kt-indicator"), i.disabled = !1
                                if(data.status == 0){
                                $.each(data.error, function (prefix, val){
                                    toastr.options = {
                                        closeButton: !0,
                                        debug: !1,
                                        newestOnTop: !1,
                                        progressBar: !0,
                                        positionClass: "toastr-bottom-right",
                                        preventDuplicates: !1,
                                        showDuration: "300",
                                        hideDuration: "1000",
                                        timeOut: "5000",
                                        extendedTimeOut: "1000",
                                        showEasing: "swing",
                                        hideEasing: "linear",
                                        showMethod: "fadeIn",
                                        hideMethod: "fadeOut"
                                    }, toastr.error(val[0]);
                                })
                                }else if(data.status == 1){

                                    toastr.options = {
                                        closeButton: !0,
                                        debug: !1,
                                        newestOnTop: !1,
                                        progressBar: !0,
                                        positionClass: "toastr-bottom-right",
                                        preventDuplicates: !1,
                                        showDuration: "300",
                                        hideDuration: "1000",
                                        timeOut: "5000",
                                        extendedTimeOut: "1000",
                                        showEasing: "swing",
                                        hideEasing: "linear",
                                        showMethod: "fadeIn",
                                        hideMethod: "fadeOut"
                                    }, toastr.success("دسترسی‌های این روت با موفقیت بروزرسانی شد");

                                }

                            }

                        });

                    }));

                }))
            })()
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTAppUserRouteSyncPermission.init()
}));
