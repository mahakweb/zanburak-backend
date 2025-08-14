@extends('layouts.master')

@section('title', 'آموزش برنامه نویسی زنبورک')

@section('content')
<div class="mdk-header-layout__content page-content ">
    <div class="page-section mb-64pt">
        <div class="container page__container">
            <div class="row">
                <div class="col-md-6 text-center text-lg-left text-md-left mt-3 mt-lg-0 mt-md-0 order-1 order-lg-0 order-md-0">

                    <h1>اگه هنوز فکر میکنی برنامه‌نویسی سخته، سخت در اشتباهی!</h1>
                    <p class="font-size-20pt text-50">
                        با این همه پیشرفت علم و تکنولوژی برنامه‌نویسی از آب خوردن آسونتر شده
                    </p>
                    <p class="font-size-20pt text-50">
                        یالا بزن رو دکمه پایین و شروع کن ما کنارتیم تا آخرش، نترس
                    </p>

                    <a href="{{ route('paths') }}" class="btn  btn-light my-3">
                        شروع ماجرای برنامه‌نویسی
                        <svg class="ml-1" width="20" height="20" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M14.9703 3.3437C13.0166 2.88543 10.9834 2.88543 9.02975 3.3437C6.20842 4.00549 4.0055 6.20841 3.3437 9.02974C2.88543 10.9834 2.88543 13.0166 3.3437 14.9703C4.0055 17.7916 6.20842 19.9945 9.02975 20.6563C10.9834 21.1146 13.0166 21.1146 14.9703 20.6563C17.7916 19.9945 19.9945 17.7916 20.6563 14.9703C21.1146 13.0166 21.1146 10.9834 20.6563 9.02975C19.9945 6.20842 17.7916 4.00549 14.9703 3.3437ZM7.72959 9.23905C7.93266 8.99536 8.29484 8.96244 8.53853 9.16551L10.5279 10.8233C11.2627 11.4357 11.2627 12.5643 10.5279 13.1766L8.53853 14.8344C8.29484 15.0375 7.93266 15.0046 7.72959 14.7609C7.52651 14.5172 7.55943 14.155 7.80313 13.9519L9.7925 12.2941C9.9762 12.141 9.9762 11.8589 9.7925 11.7058L7.80313 10.048C7.55943 9.84493 7.52651 9.48275 7.72959 9.23905ZM12.4786 13.8188C12.1614 13.8188 11.9042 14.076 11.9042 14.3932C11.9042 14.7104 12.1614 14.9676 12.4786 14.9676H15.8291C16.1463 14.9676 16.4035 14.7104 16.4035 14.3932C16.4035 14.076 16.1463 13.8188 15.8291 13.8188H12.4786Z"></path>
                        </svg>
                    </a>

                </div>
                <div class="col-md-6 text-center order-0 order-lg-1 order-md-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="100%" viewBox="0 0 693 528" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M946.5,469.5V691.31A22.71737,22.71737,0,0,1,923.81,714H753.67l1.58-2L944.5,472.04Z" transform="translate(-253.5 -186)" fill="#f2f2f2"/><path d="M524.3118,391.368H328.943a6.036,6.036,0,0,0,0,12.072H524.3118a6.036,6.036,0,0,0,0-12.072Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M389.51753,357.52356H328.943a6.036,6.036,0,0,0,0,12.072h60.57458a6.036,6.036,0,0,0,0-12.072Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M923.81,186H276.19a22.71737,22.71737,0,0,0-22.69,22.69V691.31A22.71737,22.71737,0,0,0,276.19,714H923.81a22.71737,22.71737,0,0,0,22.69-22.69V208.69A22.71737,22.71737,0,0,0,923.81,186ZM944.5,691.31A20.69,20.69,0,0,1,923.81,712H276.19a20.69,20.69,0,0,1-20.69-20.69V343.67554A155.67554,155.67554,0,0,1,411.17554,188H923.81a20.69,20.69,0,0,1,20.69,20.69Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M883.39049,273.26853H316.66a1,1,0,0,1,0-2H883.39049a1,1,0,0,1,0,2Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><ellipse cx="91.51486" cy="48.60301" rx="8.45661" ry="8.64507" fill="#e6e6e6"/><ellipse cx="120.72861" cy="48.60301" rx="8.45661" ry="8.64507" fill="#e6e6e6"/><ellipse cx="149.94237" cy="48.60301" rx="8.45661" ry="8.64507" fill="#e6e6e6"/><path d="M436.75379,241.4358a.9967.9967,0,0,1-.71485-.30078l-5.70605-5.833a.99954.99954,0,0,1,0-1.39844l5.70605-5.83251a1,1,0,1,1,1.42969,1.39843l-5.022,5.1333,5.022,5.13379a1,1,0,0,1-.71484,1.69922Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M446.34217,241.4358a1,1,0,0,1-.71485-1.69922l5.02149-5.13379-5.02149-5.1333a1,1,0,0,1,1.42969-1.39843l5.70557,5.83251a.99954.99954,0,0,1,0,1.39844l-5.70557,5.833A.9967.9967,0,0,1,446.34217,241.4358Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M832.852,226.95884h-10.61a2.18935,2.18935,0,0,0-2.19,2.19v10.62a2.18936,2.18936,0,0,0,2.19,2.19h10.61a2.19791,2.19791,0,0,0,2.2-2.19v-10.62A2.1979,2.1979,0,0,0,832.852,226.95884Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M806.852,226.95884h-10.61a2.18935,2.18935,0,0,0-2.19,2.19v10.62a2.18936,2.18936,0,0,0,2.19,2.19h10.61a2.19791,2.19791,0,0,0,2.2-2.19v-10.62A2.1979,2.1979,0,0,0,806.852,226.95884Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M857.352,227.45884h-10.61a2.18935,2.18935,0,0,0-2.19,2.19v10.62a2.18936,2.18936,0,0,0,2.19,2.19h10.61a2.19791,2.19791,0,0,0,2.2-2.19v-10.62A2.1979,2.1979,0,0,0,857.352,227.45884Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M642.4303,231.49881h-84.81a2.67,2.67,0,1,0,0,5.34h84.81a2.67,2.67,0,0,0,0-5.34Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M415.10743,596.44634l-50.717-1.60323c-7.03419-.22236-14.94447-.211-19.96166,3.42421-5.6943,4.12585-4.83213,11.43535-.10431,16.17646s12.277,7.44976,19.68637,9.652c39.36385,11.7,82.92866,14.07521,125.229,11.42782s83.73-10.13147,124.95545-17.59027c3.91-.70743,8.3414-1.7253,9.93587-4.44631,1.54247-2.63224-.47577-5.80651-3.61075-7.50776s-7.10942-2.27452-10.94329-2.78084a1027.70791,1027.70791,0,0,0-209.50054-5.976" transform="translate(-253.5 -186)" fill="#fed700"/><path d="M506.23,557.44v-12c-8.70812.12331-39.87043-.08886-48,.00027-.605,41.94091-4.31337,33.72515,30.04795,33.88847a5.97483,5.97483,0,0,1,5.95226,5.96665l0,.18a5.965,5.965,0,0,1-5.96511,5.96462H458.23v12h48c.0674-.04016-.04859-11.58721.00015-12,2.02955-34.76507-1.98621-22.26148-31.16579-23.65566a5.0949,5.0949,0,0,1-4.83453-5.09462l0-.1499a5.1001,5.1001,0,0,1,5.09983-5.09981Z" transform="translate(-253.5 -186)" fill="#3f3d56"/><path d="M565.23,557.44v-12c-8.70812.12331-39.87043-.08886-48,.00027-.61022,42.30528-4.378,33.57862,30.95215,33.89466a5.1,5.1,0,0,1,5.04814,5.09957c-.00006.63012-.00011,1.27547-.00015,1.90717a5.0988,5.0988,0,0,1-5.09982,5.09833H517.23v12h48c.0674-.04016-.04859-11.58721.00015-12,2.02955-34.76507-1.98621-22.26148-31.16579-23.65566a5.0949,5.0949,0,0,1-4.83453-5.09462l0-.1499a5.1001,5.1001,0,0,1,5.09983-5.09981Z" transform="translate(-253.5 -186)" fill="#3f3d56"/><path d="M444.23,557.44v-12h-48c-.034,10.70325.0245,39.0319-.00007,49.99987-9.40881,15.92872,44.04243,4.90065,48.00271,8.00441C444.23,603.44,444.23,591.44,444.23,591.44c-36.5211-.19074-37.43887,6.6459-36.264-26.42981a7.84378,7.84378,0,0,1,7.83373-7.57093C426.358,557.44,444.23,557.44,444.23,557.44Z" transform="translate(-253.5 -186)" fill="#3f3d56"/><path d="M539.10743,453.11245l-50.717-1.33642c-7.03419-.18536-14.94447-.17591-19.96166,2.85435-5.6943,3.43922-4.83213,9.53228-.10431,13.48439s12.277,6.21,19.68637,8.04575c39.36385,9.75291,82.92866,11.73282,125.229,9.526s83.73-8.4454,124.95545-14.66291c3.91-.5897,8.3414-1.43818,9.93587-3.70636,1.54247-2.19419-.47577-4.84019-3.61075-6.25832s-7.10942-1.896-10.94329-2.31806a1231.4445,1231.4445,0,0,0-209.50054-4.98149" transform="translate(-253.5 -186)" fill="#fed700"/><path d="M530.22986,400.44c-.81725,18.52643,5.99213,24.3577-17.501,21.99778-1.58724-11.0521,6.46193-26.38959-11.99874-22.49565,3.69817,8.01754-10.3027,68.844,11.99588,57.99455,1.25236-18.27481-7.00207-26.29029,17.50448-23.49444,1.96518,11.297-7.2074,27.92118,11.99857,23.996C538.53142,450.41692,552.53242,389.596,530.22986,400.44Z" transform="translate(-253.5 -186)" fill="#3f3d56"/><path d="M653.22986,400.44c1.64714,1.01689-15.09043,18.72384-15.45157,20.1747-.50211-1.23824-16.47736-18.56236-14.5483-20.174-22.22954-10.985-8.37541,50.19481-12.00424,58.002.00411-.00271,12.00411-.00271,12.00411-.00271V420.44177c16.86715,22.74983,13.73491,18.92152,30.00031-1.11688L653.22986,458.44c22.2288,10.98872,8.37573-50.19873,12.00412-58.0027C665.22986,400.44,653.22986,400.44,653.22986,400.44Z" transform="translate(-253.5 -186)" fill="#3f3d56"/><path d="M600.22986,400.44h-48c-2.82037,16.82193,6.50508,11.1801,18.00167,11.99963L570.22986,458.44h12v-46C593.67321,411.66182,603.08577,417.18779,600.22986,400.44Z" transform="translate(-253.5 -186)" fill="#3f3d56"/><path d="M687.22986,446.44a128.27936,128.27936,0,0,1,0-46h-12c.06918,7.60291-.02139,38.28212-.01434,52.31169a5.71,5.71,0,0,0,5.7058,5.71268c12.08981.016,35.17-.07316,42.30866-.02419L723.22986,446.44Z" transform="translate(-253.5 -186)" fill="#3f3d56"/><path d="M380.68115,448.44H331.907a9,9,0,0,0,0,18h48.77419a9,9,0,0,0,0-18Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><polygon points="569.392 517.568 559.491 517.567 554.781 479.377 569.394 479.378 569.392 517.568" fill="#ffb6b6"/><path d="M823.24065,713.89669l-30.447-.00115v-.385a11.85147,11.85147,0,0,1,11.85083-11.85065h.00073l5.56152-4.21924,10.37659,4.21991,2.65784.00006Z" transform="translate(-253.5 -186)" fill="#2f2e41"/><polygon points="604.392 517.568 594.491 517.567 589.781 479.377 604.394 479.378 604.392 517.568" fill="#ffb6b6"/><path d="M858.24065,713.89669l-30.447-.00115v-.385a11.85147,11.85147,0,0,1,11.85083-11.85065h.00073l5.56152-4.21924,10.37659,4.21991,2.65784.00006Z" transform="translate(-253.5 -186)" fill="#2f2e41"/><path d="M798.23947,495.48018s-9.5262,29.25813-7.76785,33.109S795.23,529.44,792.23,532.44s-5.61884-.189-2.80942,4.90551,7.06559,47.03407,8.93751,49.06428,4.44568,2.08468,2.15879,4.05745S795.23,588.44,798.23,592.44s10.96063,96.27559,10.96063,96.27559h15.55114s3.87318-4.46456,1.68071-7.37008.19247-4.02548,0-5.96549.53674-6.12277.67213-8.03139,2.76354-47.37757-3.55054-73.6431l11.51362-50.01216,5.75679,60.44638s-6.41553,50.70183,0,67.001c0,0-5.17233,4.30709-2.87841,6.80315S842.23,678.44,840.23,680.44s3,9,3,9l17-1,9.62205-94.73447s1.622-9.8797,0-11.57261S868.23,586.44,868.23,580.44v-8s7.25056-49.20472,7.12528-52.10236S863.23,496.44,863.23,496.44Z" transform="translate(-253.5 -186)" fill="#2f2e41"/><path d="M848.51935,377.14728l-24.823-1.70728-7.42785,11.712-17.88863,7.27293v57.69454A55.60033,55.60033,0,0,1,801.23,469.44c-.06823,11.49453-3.02253,25.03466-2,28,10,29,65,0,65,0l-1-31,1.3574-70.05937-11.23893-8.96384Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M797.23253,508.00462,795.521,489.76337l-10.55686-.47618,2.49569,19.2214a6.13324,6.13324,0,1,0,9.77265-.504Z" transform="translate(-253.5 -186)" fill="#ffb6b6"/><path d="M802.42783,392.88645l-.29266.04951a16.98787,16.98787,0,0,0-12.58455,9.62841c-3.02332,6.5379-12.8398,49.51683-8.53654,55.99808,3.3376,5.02689,2.52673,4.04615.95778,7.838s-4.0957-.25436-2.02478,5.76366,4.34666,5.1593,2.42433,7.48456-3.89421,1.86944-2.02477,5.76366,1.56555,5.20879,1.91895,6.67532.04613,11.78169.04613,11.78169,11.27634-.85538,16.888,1.82668c0,0-3.13114-10.41759-1.36073-12.08558s3.89423-1.86945,2.37818-4.29714-4.95445-2.53012-1.21216-3.74229,2.5369-22.95558,2.5369-22.95558C807.70522,446.47493,805.26991,419.92688,802.42783,392.88645Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><path d="M870.42228,509.30641l.78906-18.30436,10.51939-1.00815-1.52282,19.32283a6.13324,6.13324,0,1,1-9.78563-.01032Z" transform="translate(-253.5 -186)" fill="#ffb6b6"/><path d="M859.42612,394.59691l.29478.03468a16.9879,16.9879,0,0,1,13.05426,8.98129c3.34929,6.37706,13.94508,49.56879,11.35067,55.49615-2.41947,5.52769-2.3194,4.16845-.56116,7.87628s4.07765-.46066,2.313,5.65418-4.08085,5.372-2.04366,7.59733,3.98356,1.6706,2.313,5.65417-1.30078,5.28114-1.57975,6.76363.54829,11.769.54829,11.769-11.30514-.28542-16.77439,2.67632c0,0,2.60161-10.56228.74931-12.13883s-3.98358-1.67062-2.59193-4.17169,4.8205-2.77685,1.02182-3.79868-3.6917-22.79838-3.6917-22.79838Z" transform="translate(-253.5 -186)" fill="#e6e6e6"/><circle cx="583.21719" cy="166.41031" r="19.36219" fill="#ffb6b6"/><path d="M850.19186,368.98408c2.69286-2.10746,2.95426-6.12449,5.25223-8.65672,3.8421-4.23373,5.64722-8.78866,3.63458-13.80527-1.78015-4.437-1.40178-9.89125-5.23573-12.74732,1.883-.68165,2.32615-2.09722.66469-2.12336-1.12042-.01762-5.29722,2.12375-3.28744-.54383s-1.7946-1.61462-1.543-2.70656,1.8652-1.69671,2.57686-.83115a5.6397,5.6397,0,0,0-8.84591,1.01189c-2.26458-3.39392-4.66838-5.72925-8.13365-3.57547-3.40345,2.11532-3.359,4.73016-7.01886,4.10174-1.72058-.29541-1.88315,2.95523-3.05854,4.246a5.24691,5.24691,0,0,1-4.609,1.96555c-1.7154-.32418-3.02709-2.46193-2.06587-3.91923l-4.043,9.22356-1.62943-1.99866c-2.58913,1.68734-1.73874,6.09027.86747,7.7511s5.993,1.30585,8.99444.56967a35.27556,35.27556,0,0,1,11.05161-.91662c5.6662.80049,9.61278,7.57467,7.51461,12.89861,2.177-2.13776,6.20431-.36368,7.3321,2.47138s.30082,6.02913-.5352,8.96351" transform="translate(-253.5 -186)" fill="#2f2e41"/></svg>
                </div>
            </div>
        </div>
    </div>
    <div class="page-section mb-64pt">
        <div class="container page__container">





            <div class="row d-flex flex-column flex-lg-row">
                <div class="col-lg-3 mb-3 mb-lg-0">
                    <h4>جدیدترین دوره‌ها</h4>
                    <p class="font-size-16pt text-50">
                        دوره‌های محک‌‌وب با نهایت دقت ضبط و تهیه میشوند و از کیفیت بالایی برخوردار هستند
                    </p>
                    <p class="font-size-16pt text-50">
                        برای مشاهده همه دوره‌ها لطفا روی دکمه زیر کلیک کنید
                    </p>
                    <div class="d-flex flex-column flex-lg-row align-items-center">
                        <a href="{{ route('all-course') }}" class="btn btn-yellow font-size-12pt mb-2 mb-lg-0">
                            مشاهده همه دوره‌ها
                            <svg class="ml-2" width="15" height="15" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" opacity="0.4" d="M19.3329 2.44257C18.9139 2.17861 18.3989 2.15526 17.9599 2.37861L16.4779 3.12683C15.9298 3.40297 15.5898 3.96135 15.5898 4.58267V10.4162C15.5898 11.0375 15.9298 11.5949 16.4779 11.873L17.9589 12.6202C18.1599 12.7238 18.3749 12.7735 18.5899 12.7735C18.8479 12.7735 19.1039 12.7004 19.3329 12.5573C19.7519 12.2943 20.0019 11.8385 20.0019 11.339V3.66186C20.0019 3.16236 19.7519 2.70653 19.3329 2.44257Z"></path>
                                <path fill="currentColor" d="M9.9051 15H4.11304C1.69102 15 0 13.3299 0 10.9391V4.06091C0 1.66904 1.69102 0 4.11304 0H9.9051C12.3271 0 14.0181 1.66904 14.0181 4.06091V10.9391C14.0181 13.3299 12.3271 15 9.9051 15Z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div dir="ltr" class="col-lg-9 position-relative carousel-card">
                    <div class="js-mdk-carousel row d-block" id="carousel-latest-courses">
                        <div class="mx-12pt mb-2 text-center text-lg-right">
                            <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-latest-courses" role="button" data-slide="prev">
                                <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863" fill="currentColor"></path>
                                    <path d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292" fill="currentColor"></path>
                                </svg>
                                {{-- <i class="carousel-control-icon fa fa-chevron-right" aria-hidden="true"></i>  --}}
                                <span class="sr-only">previous</span>
                            </a>
                            <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-latest-courses" role="button" data-slide="next">
                                <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M7.12872 6.34863L2.5608 5.94466C1.53567 5.94466 0.704529 6.7839 0.704529 7.81901C0.704529 8.85412 1.53567 9.69336 2.5608 9.69336L7.12872 9.28938C7.93292 9.28938 8.58491 8.63105 8.58491 7.81901C8.58491 7.00561 7.93292 6.34863 7.12872 6.34863" fill="currentColor"></path>
                                    <path d="M22.4681 6.42292C22.3967 6.35083 22.13 6.04614 21.8795 5.79314C20.4179 4.20852 16.6016 1.61734 14.6053 0.824345C14.3022 0.697847 13.5357 0.428528 13.1248 0.409485C12.7328 0.409485 12.3583 0.500618 12.0014 0.680164C11.5555 0.931801 11.1999 1.32898 11.0032 1.79689C10.8779 2.12061 10.6812 3.09315 10.6812 3.11084C10.4859 4.17315 10.3795 5.9006 10.3795 7.81032C10.3795 9.62754 10.4859 11.2843 10.6462 12.3643C10.6637 12.3833 10.8604 13.5898 11.0746 14.0033C11.4666 14.7596 12.2331 15.2275 13.0534 15.2275H13.1248C13.6596 15.2098 14.7831 14.7405 14.7831 14.7242C16.673 13.9312 20.399 11.4652 21.897 9.82613C21.897 9.82613 22.3199 9.40447 22.5031 9.14195C22.7887 8.76381 22.9315 8.29591 22.9315 7.828C22.9315 7.30568 22.7712 6.82009 22.4681 6.42292" fill="currentColor"></path>
                                </svg>
                                {{-- <i class="carousel-control-icon fa fa-chevron-left" aria-hidden="true"></i>  --}}
                                <span class="sr-only">Next</span>
                            </a>
                        </div>
                        <div class="mdk-carousel__content">

                            @foreach(\App\Models\Course::where('publish', 1)->orderBy('id', 'desc')->limit(5)->get() as $course)

                            <div dir="rtl" class="col-md-6 col-lg-4 card-group-row__col" style="height: 420px!important;">
                                @include('course.course-card', ['course' => $course])
                            </div>

                            @endforeach

                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
    <div class="page-section my-64pt">
        <div class="container page__container">
            <div class="row d-flex flex-column flex-lg-row">
                <div class="col-lg-3 mb-3 mb-lg-0">
                    <h4>مسیرهای یادگیری</h4>
                    <p class="font-size-16pt text-50">
                        در مسیرهای یادگیری شما با گذراندن چند دوره از دوره‌های آموزشی زنبورک و یادگیری موضوعات بصورت منظم به موضوع آن مسیر کاملا مسلط می‌شوید.
                    </p>
                    <div class="d-flex flex-column flex-lg-row align-items-center">
                        <a href="{{ route('paths') }}" class="btn btn-yellow font-size-12pt mb-2 mb-lg-0">مشاهده همه مسیرها</a>

                    </div>
                </div>
                <div dir="ltr" class="col-lg-9 position-relative carousel-card">
                    <div class="js-mdk-carousel row d-block" id="carousel-paths">

                        <div class="mx-12pt mb-2 text-center text-lg-right">
                            <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-paths" role="button" data-slide="prev">
                                <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863" fill="currentColor"></path>
                                    <path d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292" fill="currentColor"></path>
                                </svg>
                                {{-- <i class="carousel-control-icon fa fa-chevron-right" aria-hidden="true"></i>  --}}
                                <span class="sr-only">previous</span>
                            </a>
                            <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-paths" role="button" data-slide="next">
                                <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M7.12872 6.34863L2.5608 5.94466C1.53567 5.94466 0.704529 6.7839 0.704529 7.81901C0.704529 8.85412 1.53567 9.69336 2.5608 9.69336L7.12872 9.28938C7.93292 9.28938 8.58491 8.63105 8.58491 7.81901C8.58491 7.00561 7.93292 6.34863 7.12872 6.34863" fill="currentColor"></path>
                                    <path d="M22.4681 6.42292C22.3967 6.35083 22.13 6.04614 21.8795 5.79314C20.4179 4.20852 16.6016 1.61734 14.6053 0.824345C14.3022 0.697847 13.5357 0.428528 13.1248 0.409485C12.7328 0.409485 12.3583 0.500618 12.0014 0.680164C11.5555 0.931801 11.1999 1.32898 11.0032 1.79689C10.8779 2.12061 10.6812 3.09315 10.6812 3.11084C10.4859 4.17315 10.3795 5.9006 10.3795 7.81032C10.3795 9.62754 10.4859 11.2843 10.6462 12.3643C10.6637 12.3833 10.8604 13.5898 11.0746 14.0033C11.4666 14.7596 12.2331 15.2275 13.0534 15.2275H13.1248C13.6596 15.2098 14.7831 14.7405 14.7831 14.7242C16.673 13.9312 20.399 11.4652 21.897 9.82613C21.897 9.82613 22.3199 9.40447 22.5031 9.14195C22.7887 8.76381 22.9315 8.29591 22.9315 7.828C22.9315 7.30568 22.7712 6.82009 22.4681 6.42292" fill="currentColor"></path>
                                </svg>
                                {{-- <i class="carousel-control-icon fa fa-chevron-left" aria-hidden="true"></i>  --}}
                                <span class="sr-only">Next</span>
                            </a>
                        </div>

                        <div class="mdk-carousel__content">
                            @foreach(\App\Models\Path::all() as $path)
                            <div class="col-12 col-sm-6 card-group-row__col" style="height: 300px!important;">

                                <div class="card border-0 shadow-none card-group-row__card">

                                    <div dir="rtl" class="card-body pb-2 d-flex flex-column">
                                        <div class="d-flex align-items-center">
                                            <div class="flex">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded mr-12pt z-0 o-hidden">
                                                        <div class="">
                                                            <img src="{{ $path->image }}" width="40" height="40" alt="{{ $path->english_title }}" class="rounded shadow-lg">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <a href="" class="btn btn-sm btn-yellow py-0">
                                                <i class="material-icons font-size-16pt mr-1">movie</i>
                                                <span>شامل {{ $path->courses->count() }} دوره</span>
                                            </a>

                                        </div>

                                        <a href="" class="mt-12pt card-title">
                                            <h5>{{ $path->title }}</h5>
                                        </a>
                                        <p class=" text-black-70 font-size-16pt flex">
                                            {!! Str::words($path->description, 30, '...') !!}
                                        </p>


                                        <div class="mt-auto d-flex align-items-center justify-content-end border-top pt-2">
                                            <a href="" class="btn btn-yellow">
                                                <span class="mr-2">مشاهده اطلاعات مسیر یادگیری</span>
                                                <svg class="" width="18" height="18" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path opacity="0.4" d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863" fill="currentColor"></path>
                                                    <path d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292" fill="currentColor"></path>
                                                </svg>
                                            </a>
                                        </div>


                                    </div>
                                </div>


                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-section my-64pt">
        <div class="container page__container">

            <div dir="ltr" class="position-relative carousel-card col-lg-12 p-0 mx-auto">
                <div class="row d-block js-mdk-carousel" id="carousel-blog-posts">
                    <div class="d-flex flex-row mx-12pt">
                        <div class="mb-2 text-center text-lg-right">
                            <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-blog-posts" role="button" data-slide="prev">
                                <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863" fill="currentColor"></path>
                                    <path d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292" fill="currentColor"></path>
                                </svg>
                                {{-- <i class="carousel-control-icon fa fa-chevron-right" aria-hidden="true"></i>  --}}
                                <span class="sr-only">previous</span>
                            </a>
                            <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-blog-posts" role="button" data-slide="next">
                                <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M7.12872 6.34863L2.5608 5.94466C1.53567 5.94466 0.704529 6.7839 0.704529 7.81901C0.704529 8.85412 1.53567 9.69336 2.5608 9.69336L7.12872 9.28938C7.93292 9.28938 8.58491 8.63105 8.58491 7.81901C8.58491 7.00561 7.93292 6.34863 7.12872 6.34863" fill="currentColor"></path>
                                    <path d="M22.4681 6.42292C22.3967 6.35083 22.13 6.04614 21.8795 5.79314C20.4179 4.20852 16.6016 1.61734 14.6053 0.824345C14.3022 0.697847 13.5357 0.428528 13.1248 0.409485C12.7328 0.409485 12.3583 0.500618 12.0014 0.680164C11.5555 0.931801 11.1999 1.32898 11.0032 1.79689C10.8779 2.12061 10.6812 3.09315 10.6812 3.11084C10.4859 4.17315 10.3795 5.9006 10.3795 7.81032C10.3795 9.62754 10.4859 11.2843 10.6462 12.3643C10.6637 12.3833 10.8604 13.5898 11.0746 14.0033C11.4666 14.7596 12.2331 15.2275 13.0534 15.2275H13.1248C13.6596 15.2098 14.7831 14.7405 14.7831 14.7242C16.673 13.9312 20.399 11.4652 21.897 9.82613C21.897 9.82613 22.3199 9.40447 22.5031 9.14195C22.7887 8.76381 22.9315 8.29591 22.9315 7.828C22.9315 7.30568 22.7712 6.82009 22.4681 6.42292" fill="currentColor"></path>
                                </svg>
                                {{-- <i class="carousel-control-icon fa fa-chevron-left" aria-hidden="true"></i>  --}}
                                <span class="sr-only">Next</span>
                            </a>
                        </div>
                        <div class="mr-auto">
                            <h4>
                                آخرین مقالات
                            </h4>
                        </div>
                    </div>

                    <div class="mdk-carousel__content" dir="rtl">

                        <div class="col-12 col-sm-4 card-group-row__col">
                            <div class="card border-0 shadow-none card--elevated posts-card-popular overlay card-group-row__card">
                                <img src="assets/images/paths/sketch_430x168.png" alt="" class="card-img">
                                <div class="fullbleed bg-primary" style="opacity: .5"></div>
                                <div class="posts-card-popular__content">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="avatar-group flex">
                                            <div class="avatar avatar-xs" data-toggle="tooltip" data-placement="top" title="Janell D.">
                                                <a href=""><img src="assets/images/256_luke-porter-261779-unsplash.jpg" alt="Avatar" class="avatar-img rounded-circle"></a>
                                            </div>
                                        </div>
                                        <a style="text-decoration: none;" class="d-flex align-items-center" href=""><i class="material-icons mr-1" style="font-size: inherit;">remove_red_eye</i> <small>327</small></a>
                                    </div>
                                    <div class="posts-card-popular__title card-body">
                                        <small class="text-muted text-uppercase">طراحی</small>
                                        <a class="card-title" href="">Merge Duplicates Inconsistent Symbols</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4 card-group-row__col">
                            <div class="card border-0 shadow-none card--elevated posts-card-popular overlay card-group-row__card">
                                <img src="assets/images/paths/sketch_430x168.png" alt="" class="card-img">
                                <div class="fullbleed bg-primary" style="opacity: .5"></div>
                                <div class="posts-card-popular__content">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="avatar-group flex">
                                            <div class="avatar avatar-xs" data-toggle="tooltip" data-placement="top" title="Janell D.">
                                                <a href=""><img src="assets/images/256_luke-porter-261779-unsplash.jpg" alt="Avatar" class="avatar-img rounded-circle"></a>
                                            </div>
                                        </div>
                                        <a style="text-decoration: none;" class="d-flex align-items-center" href=""><i class="material-icons mr-1" style="font-size: inherit;">remove_red_eye</i> <small>327</small></a>
                                    </div>
                                    <div class="posts-card-popular__title card-body">
                                        <small class="text-muted text-uppercase">طراحی</small>
                                        <a class="card-title" href="">Merge Duplicates Inconsistent Symbols</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4 card-group-row__col">
                            <div class="card border-0 shadow-none card--elevated posts-card-popular overlay card-group-row__card">
                                <img src="assets/images/paths/sketch_430x168.png" alt="" class="card-img">
                                <div class="fullbleed bg-primary" style="opacity: .5"></div>
                                <div class="posts-card-popular__content">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="avatar-group flex">
                                            <div class="avatar avatar-xs" data-toggle="tooltip" data-placement="top" title="Janell D.">
                                                <a href=""><img src="assets/images/256_luke-porter-261779-unsplash.jpg" alt="Avatar" class="avatar-img rounded-circle"></a>
                                            </div>
                                        </div>
                                        <a style="text-decoration: none;" class="d-flex align-items-center" href=""><i class="material-icons mr-1" style="font-size: inherit;">remove_red_eye</i> <small>327</small></a>
                                    </div>
                                    <div class="posts-card-popular__title card-body">
                                        <small class="text-muted text-uppercase">طراحی</small>
                                        <a class="card-title" href="">Merge Duplicates Inconsistent Symbols</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4 card-group-row__col">
                            <div class="card border-0 shadow-none card--elevated posts-card-popular overlay card-group-row__card">
                                <img src="assets/images/paths/invision_430x168.png" alt="" class="card-img">
                                <div class="fullbleed bg-primary" style="opacity: .5"></div>
                                <div class="posts-card-popular__content">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="avatar-group flex">
                                            <div class="avatar avatar-xs" data-toggle="tooltip" data-placement="top" title="Janell D.">
                                                <a href=""><img src="assets/images/256_michael-dam-258165-unsplash.jpg" alt="Avatar" class="avatar-img rounded-circle"></a>
                                            </div>
                                        </div>
                                        <a style="text-decoration: none;" class="d-flex align-items-center" href=""><i class="material-icons mr-1" style="font-size: inherit;">remove_red_eye</i> <small>327</small></a>
                                    </div>
                                    <div class="posts-card-popular__title card-body">
                                        <small class="text-muted text-uppercase">invision</small>
                                        <a class="card-title" href="">Design Systems Essentials</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4 card-group-row__col">
                            <div class="card border-0 shadow-none card--elevated posts-card-popular overlay card-group-row__card">
                                <img src="assets/images/paths/photoshop_430x168.png" alt="" class="card-img">
                                <div class="fullbleed bg-primary" style="opacity: .5"></div>
                                <div class="posts-card-popular__content">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="avatar-group flex">
                                            <div class="avatar avatar-xs" data-toggle="tooltip" data-placement="top" title="Janell D.">
                                                <a href=""><img src="assets/images/256_rsz_1andy-lee-642320-unsplash.jpg" alt="Avatar" class="avatar-img rounded-circle"></a>
                                            </div>
                                        </div>
                                        <a style="text-decoration: none;" class="d-flex align-items-center" href=""><i class="material-icons mr-1" style="font-size: inherit;">remove_red_eye</i> <small>327</small></a>
                                    </div>
                                    <div class="posts-card-popular__title card-body">
                                        <small class="text-muted text-uppercase">photoshop</small>
                                        <a class="card-title" href="">Semantic Logo Design</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-section my-64pt">
        <div class="container page__container">

            <div dir="ltr" class="position-relative carousel-card col-lg-12 p-0 mx-auto">
                <div class="row d-block js-mdk-carousel" id="carousel-feedback">

                    <div class="d-flex flex-row mx-12pt">
                        <div class="mb-2 text-center text-lg-right">
                            <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-feedback" role="button" data-slide="prev">
                                <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863" fill="currentColor"></path>
                                    <path d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292" fill="currentColor"></path>
                                </svg>
                                {{-- <i class="carousel-control-icon fa fa-chevron-right" aria-hidden="true"></i>  --}}
                                <span class="sr-only">previous</span>
                            </a>
                            <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-feedback" role="button" data-slide="next">
                                <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M7.12872 6.34863L2.5608 5.94466C1.53567 5.94466 0.704529 6.7839 0.704529 7.81901C0.704529 8.85412 1.53567 9.69336 2.5608 9.69336L7.12872 9.28938C7.93292 9.28938 8.58491 8.63105 8.58491 7.81901C8.58491 7.00561 7.93292 6.34863 7.12872 6.34863" fill="currentColor"></path>
                                    <path d="M22.4681 6.42292C22.3967 6.35083 22.13 6.04614 21.8795 5.79314C20.4179 4.20852 16.6016 1.61734 14.6053 0.824345C14.3022 0.697847 13.5357 0.428528 13.1248 0.409485C12.7328 0.409485 12.3583 0.500618 12.0014 0.680164C11.5555 0.931801 11.1999 1.32898 11.0032 1.79689C10.8779 2.12061 10.6812 3.09315 10.6812 3.11084C10.4859 4.17315 10.3795 5.9006 10.3795 7.81032C10.3795 9.62754 10.4859 11.2843 10.6462 12.3643C10.6637 12.3833 10.8604 13.5898 11.0746 14.0033C11.4666 14.7596 12.2331 15.2275 13.0534 15.2275H13.1248C13.6596 15.2098 14.7831 14.7405 14.7831 14.7242C16.673 13.9312 20.399 11.4652 21.897 9.82613C21.897 9.82613 22.3199 9.40447 22.5031 9.14195C22.7887 8.76381 22.9315 8.29591 22.9315 7.828C22.9315 7.30568 22.7712 6.82009 22.4681 6.42292" fill="currentColor"></path>
                                </svg>
                                {{-- <i class="carousel-control-icon fa fa-chevron-left" aria-hidden="true"></i>  --}}
                                <span class="sr-only">Next</span>
                            </a>
                        </div>
                        <div class="mr-auto">
                            <h4>
                                بازخوردها
                            </h4>
                        </div>
                    </div>
                    <div class="mdk-carousel__content" dir="rtl">

                        <div class="col-12 col-md-4">

                            <div class="card border-0 shadow-none card-feedback card-body">
                                <blockquote class="blockquote mb-0">
                                    <p class="text-70 small mb-0">A wonderful course on how to start. Eddie beautifully conveys all essentials of a becoming a good Angular developer. Very glad to have taken this course. Thank you Eddie Bryan.</p>
                                </blockquote>
                            </div>
                            <div class="media ml-12pt">
                                <div class="media-left mr-12pt">
                                    <a href="fixed-student-profile.html" class="avatar avatar-sm">
                                        <!-- <img src="assets/images/people/110/guy-.jpg" width="40" alt="avatar" class="rounded-circle"> -->
                                        <span class="avatar-title rounded-circle">UK</span>
                                    </a>
                                </div>
                                <div class="media-body media-middle">
                                    <a href="fixed-student-profile.html" class="card-title">Umberto Kass</a>
                                    <div class="rating mt-4pt">
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star_border</i></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-12 col-md-4">

                            <div class="card border-0 shadow-none card-feedback card-body">
                                <blockquote class="blockquote mb-0">
                                    <p class="text-70 small mb-0">A wonderful course on how to start. Eddie beautifully conveys all essentials of a becoming a good Angular developer. Very glad to have taken this course. Thank you Eddie Bryan.</p>
                                </blockquote>
                            </div>
                            <div class="media ml-12pt">
                                <div class="media-left mr-12pt">
                                    <a href="fixed-student-profile.html" class="avatar avatar-sm">
                                        <!-- <img src="assets/images/people/110/guy-.jpg" width="40" alt="avatar" class="rounded-circle"> -->
                                        <span class="avatar-title rounded-circle">UK</span>
                                    </a>
                                </div>
                                <div class="media-body media-middle">
                                    <a href="fixed-student-profile.html" class="card-title">Umberto Kass</a>
                                    <div class="rating mt-4pt">
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star_border</i></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-12 col-md-4">

                            <div class="card border-0 shadow-none card-feedback card-body">
                                <blockquote class="blockquote mb-0">
                                    <p class="text-70 small mb-0">A wonderful course on how to start. Eddie beautifully conveys all essentials of a becoming a good Angular developer. Very glad to have taken this course. Thank you Eddie Bryan.</p>
                                </blockquote>
                            </div>
                            <div class="media ml-12pt">
                                <div class="media-left mr-12pt">
                                    <a href="fixed-student-profile.html" class="avatar avatar-sm">
                                        <!-- <img src="assets/images/people/110/guy-.jpg" width="40" alt="avatar" class="rounded-circle"> -->
                                        <span class="avatar-title rounded-circle">UK</span>
                                    </a>
                                </div>
                                <div class="media-body media-middle">
                                    <a href="fixed-student-profile.html" class="card-title">Umberto Kass</a>
                                    <div class="rating mt-4pt">
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star_border</i></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-12 col-md-4">

                            <div class="card border-0 shadow-none card-feedback card-body">
                                <blockquote class="blockquote mb-0">
                                    <p class="text-70 small mb-0">A wonderful course on how to start. Eddie beautifully conveys all essentials of a becoming a good Angular developer. Very glad to have taken this course. Thank you Eddie Bryan.</p>
                                </blockquote>
                            </div>
                            <div class="media ml-12pt">
                                <div class="media-left mr-12pt">
                                    <a href="fixed-student-profile.html" class="avatar avatar-sm">
                                        <!-- <img src="assets/images/people/110/guy-.jpg" width="40" alt="avatar" class="rounded-circle"> -->
                                        <span class="avatar-title rounded-circle">UK</span>
                                    </a>
                                </div>
                                <div class="media-body media-middle">
                                    <a href="fixed-student-profile.html" class="card-title">Umberto Kass</a>
                                    <div class="rating mt-4pt">
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star_border</i></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-12 col-md-4">

                            <div class="card border-0 shadow-none card-feedback card-body">
                                <blockquote class="blockquote mb-0">
                                    <p class="text-70 small mb-0">A wonderful course on how to start. Eddie beautifully conveys all essentials of a becoming a good Angular developer. Very glad to have taken this course. Thank you Eddie Bryan.</p>
                                </blockquote>
                            </div>
                            <div class="media ml-12pt">
                                <div class="media-left mr-12pt">
                                    <a href="fixed-student-profile.html" class="avatar avatar-sm">
                                        <!-- <img src="assets/images/people/110/guy-.jpg" width="40" alt="avatar" class="rounded-circle"> -->
                                        <span class="avatar-title rounded-circle">UK</span>
                                    </a>
                                </div>
                                <div class="media-body media-middle">
                                    <a href="fixed-student-profile.html" class="card-title">Umberto Kass</a>
                                    <div class="rating mt-4pt">
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star</i></span>
                                        <span class="rating__item"><i class="material-icons">star_border</i></span>
                                    </div>
                                </div>
                            </div>

                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="/assets/js/manage-like.js"></script>
@endsection
