"use strict";
var KTAppMissionsSaveMission = function() {

    return {
        init: function() {
            
            $("select2.form-select").select2()
            
            $("#kt_ecommerce_add_product_options").repeater({
                initEmpty: !1,
                defaultValues: {
                    "text-input": "foo"
                },
                show: function() {
                    $(this).slideDown()
                },
                hide: function(e) {
                    $(this).slideUp(e)
                }
            })
            
            var o, a;
            ["#kt_missions_add_mission_description", "#kt_missions_add_mission_meta_description"].forEach((e => {
                let t = document.querySelector(e);
                t && (t = new Quill(e, {
                    modules: {
                        toolbar: [
                            [{
                                header: [1, 2, 3, 4, 5, 6, !1]
                            }],
                            // [{ 'font': [] }],
                            [{ 'align': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'indent': '-1'}, { 'indent': '+1' }], 
                            ["bold", "italic", "underline"],
                            ["image", "code-block", "link"],
                            [{ 'color': [] }, { 'background': [] }], 
                          
                        ]
                    },
                    // placeholder: "متن خود را اینجا وارد کنید...",
                    theme: "snow"
                }))
            })), ["#kt_missions_add_mission_tags"].forEach((e => {
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
                const t = document.getElementById("kt_missions_add_mission_form"),
                    o = document.getElementById("kt_missions_add_mission_submit");
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
                            // var formElement = document.querySelector('#kt_missions_add_mission_form');
                            // const form = new FormData($('#kt_missions_add_mission_form')[0]);
                            // $(t).find("textarea[name=description]").html($('#kt_missions_add_mission_description').html());
                            const form = new FormData(document.querySelector('#kt_missions_add_mission_form'));
                            // form.append('description', $('#kt_missions_add_mission_description').html())
                            // for (var x of form) console.log(x);               description
                            // console.log($(formData))
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
                                            text: "دوره با موفقیت ایجاد شد!",
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
    KTAppMissionsSaveMission.init()
}));