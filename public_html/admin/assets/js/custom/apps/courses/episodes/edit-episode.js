"use strict";
var KTAppCoursesEditEpisode = function() {

    const e = () => {
        $("#kt_episode_add_attach").repeater({
            initEmpty: !1,
            isFirstItemUndeletable: false,
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
    };

    return {
        init: function() {
            var o, a;
            e();
            (() => {
                let e;
                const t = document.getElementById("kt_courses_update_episode_form"),
                    o = document.getElementById("kt_courses_update_episode_submit");
                e = FormValidation.formValidation(t, {
                    // fields: {

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

                            const form = new FormData(document.querySelector('#kt_courses_update_episode_form'));

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
                                            html: "به نظر میرسه تعدادی خطا وجود داره اونارو برطرف کن بعدا تلاش کن.",
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
                                            text: "جلسه با موفقیت ویرایش شد!",
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
                            html: "به نظر میرسه تعدادی خطا وجود داره اونارو برطرف کن بعدا تلاش کن.",
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
    KTAppCoursesEditEpisode.init()
}));
