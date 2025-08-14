"use strict";
var KTAppCoursesEditCourse = function() {

    return {
        init: function() {
            var o, a;
            ["#kt_courses_update_course_tags"].forEach((e => {
                const t = document.querySelector(e);
                t && new Tagify(t, {
                    whitelist: ["laravel","لاراول","html","css","backend","frontend","blade"],
                    dropdown: {
                        maxItems: 20,
                        classname: "tagify__inline__suggestions",
                        enabled: 0,
                        closeOnSelect: !1
                    }
                })
            })), (() => {
                const e = document.querySelectorAll('[name="method"][type="radio"]'),
                    t = document.querySelector('[data-kt-ecommerce-catalog-add-category="auto-options"]');
                e.forEach((e => {
                    e.addEventListener("change", (e => {
                        "1" === e.target.value ? t.classList.remove("d-none") : t.classList.add("d-none")
                    }))
                }))
            })(), (() => {
                const e = document.querySelectorAll('select[name="type"]'),
                    t = document.getElementsByName("price");
                    $(e).on('change', function(o){
                        // console.log($(e).val())
                        switch ($(e).val()) {
                            case "free":
                                $(t).attr("disabled", "disabled");
                                $(t).attr("value", "0");
                                break;
                            case "cash":
                                $(t).removeAttr("disabled");
                                break;
                            case "cash-vip":
                                $(t).removeAttr("disabled");
                                break;
                            default:
                                $(t).attr("disabled", "disabled");
                                $(t).attr("value", "0");
                        }
                    })
            })(), (() => {
                let e;
                const t = document.getElementById("kt_courses_update_course_form"),
                    o = document.getElementById("kt_courses_update_course_submit");
                e = FormValidation.formValidation(t, {
                    // fields: {
                    //     title: {
                    //         validators: {
                    //             notEmpty: {
                    //                 message: "لطفا عنوان دوره را وارد کنید"
                    //             },
                    //             stringLength: {
                    //                 min: 10,
                    //                 max: 255,
                    //                 message: "لطفا حداقل 10 کاراکتر وارد کنید"
                    //             }
                    //         }
                    //     },
                    //     english_title: {
                    //         validators: {
                    //             notEmpty: {
                    //                 message: "لطفا عنوان لاتین دوره را وارد کنید"
                    //             },
                    //             stringLength: {
                    //                 min: 10,
                    //                 max: 255,
                    //                 message: "لطفا حداقل 10 کاراکتر وارد کنید"
                    //             },
                    //             regexp: {
                    //                 regexp: /^[~`!@#$%^&*()_+=[\]\\{}|;':",.\/<>?a-zA-Z0-9- ]+$/,
                    //                 message: "لطفا فقط از حروف لاتین استفاده کنید",
                    //             },
                    //         }
                    //     },
                    //     price: {
                    //         validators: {
                    //             notEmpty: {
                    //                 message: "لطفا قیمت دوره را وارد کنید"
                    //             },
                    //             regexp: {
                    //                 regexp: /^[0-9]*$/,
                    //                 message: "لطفا فقط از اعداد استفاده کنید",
                    //             },
                    //             lessThan: {
                    //                 max: 10000000,
                    //                 message: "حداکثر قیمت ده میلیون تومان است",
                    //             },
                    //             greaterThan: {
                    //                 min: 0,
                    //                 message: "حداقل قیمت صفر تومان است"
                    //             }
                    //         }
                    //     },
                    // },
                    // plugins: {
                    //     trigger: new FormValidation.plugins.Trigger,
                    //     bootstrap: new FormValidation.plugins.Bootstrap5({
                    //         rowSelector: ".fv-row",
                    //         eleInvalidClass: "",
                    //         eleValidClass: ""
                    //     })
                    // }
                }), o.addEventListener("click", (a => {
                    a.preventDefault(), e && e.validate().then((function(e) {
                        "Valid" == e ? (o.setAttribute("data-kt-indicator", "on"), o.disabled = !0, setTimeout((function() {

                            const form = new FormData(document.querySelector('#kt_courses_update_course_form'));

                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
                                }
                            });
                            $.ajax({
                                url: $(t).attr('action'),
                                method: $(t).attr('method'),
                                data: form,
                                datatype: "json",
                                async: false,
                                processData: false,
                                contentType: false,
                                beforeSend: function (){
                                    $(t).find("div.error_text div").text('');
                                },
                                success: function (data){
                                    o.removeAttribute("data-kt-indicator"), o.disabled = !1
                                    if(data.status == 0){
                                    $.each(data.error, function (prefix, val){
                                        $("div."+prefix+"_error div").text(val[0]);

                                        Swal.fire({
                                            html: "به نظر میرسه تعدادی خطا وجود داره اونارو برطرف کن بعدا تلاش کن. <br/><br/> تب هاب <strong>اصلی </strong> و <strong> بیشتر </strong> رو چک کن.",
                                            icon: "error",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        })
                                    })
                                    }else if(data.status == 1){
                                        // console.log(data.msg)
                                        Swal.fire({
                                            text: "دوره با موفقیت ویرایش شد!",
                                            icon: "success",
                                            buttonsStyling: !1,
                                            confirmButtonText: "بسیار خب!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        }).then((function(e) {
                                            e.isConfirmed && (o.disabled = !1, window.location = t.getAttribute("data-kt-redirect"))
                                        }))

                                    }

                                }

                            });


                        }), 2e3)) : Swal.fire({
                            html: "به نظر میرسه تعدادی خطا وجود داره اونارو برطرف کن بعدا تلاش کن. <br/><br/> تب هاب <strong>اصلی </strong> و <strong> بیشتر </strong> رو چک کن.",
                            icon: "error",
                            buttonsStyling: !1,
                            confirmButtonText: "بسیار خب!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        })
                    }))
                }))
            })()
        }
    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTAppCoursesEditCourse.init()
}));
