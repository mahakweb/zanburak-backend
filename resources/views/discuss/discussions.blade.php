@extends('layouts.master')
@section('title', 'بخش پرسش و پاسخ زنبورک')
@section('head')
    <link href="/assets/css/highlight/11.7.0/rainbow.min.css" rel="stylesheet">
    <script src="/assets/js/highlight/11.7.0/highlight.min.js"></script>
    <script src="/assets/js/highlight/highlightjs-line-numbers.min.js"></script>
    <script>
        hljs.highlightAll();
        hljs.initLineNumbersOnLoad();
    </script>

    <link href="/assets/css/editor/easymde/custom-style.css" rel="stylesheet">
@endsection

@section('content')
    <div class="mdk-header-layout__content page-content" >

        <div class="page-section">
            <div class="container page__container">
                <div class="row border-bottom pb-4">
                    <div class="col-md-6 d-flex flex-column text-center text-lg-left text-md-left mt-3 mt-lg-0 mt-md-0 order-1 order-lg-0 order-md-0">

                        <h1>بخش پرسش و پاسخ‌های زنبورک</h1>
                        <p class="font-size-20pt text-50 mt-0 mt-lg-48pt">
                            اینجا میتونی سوالت رو مطرح کنی و به کمک بقیه بهترین جواب رو براش پیدا کنی. یا بین سوالات مختلف
                            بگردی...
                        </p>
                        <div class="mt-auto">
                            <a href="#questions-list" class="btn  btn-light ">
                                بریم سر وقت سوالات
                                <svg class="ml-2" width="18" height="18" viewBox="0 0 23 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4"
                                        d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863"
                                        fill="currentColor"></path>
                                    <path
                                        d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292"
                                        fill="currentColor"></path>
                                </svg>
                            </a>

                            <a href="{{ route('discuss-create-question') }}" class="btn btn-yellow ml-2">
                                ایجاد پرسش
                                <i class="fa fa-plus ml-1"></i>
                            </a>
                        </div>

                    </div>
                    <div class="col-md-6 order-0 order-lg-1 order-md-1 text-center text-md-right text-lg-right">
                        <svg xmlns="http://www.w3.org/2000/svg" width="70%" viewBox="0 0 740.67538 473.94856" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M341.17319,342.44851a122.0417,122.0417,0,0,1,10.10051-38.51722q2.27961-5.09249,5.01812-9.96076a.7438.7438,0,0,0-1.28353-.75026,123.72825,123.72825,0,0,0-13.7678,37.98231q-1.03367,5.58356-1.55378,11.24593c-.08811.95214,1.39893.94613,1.48648,0Z" transform="translate(-229.66231 -213.02572)" fill="#e6e6e6"/><circle cx="130.22983" cy="73.9144" r="9.4144" fill="#e6e6e6"/><path d="M342.13622,342.69855a79.17409,79.17409,0,0,1,6.55267-24.98792q1.47889-3.30372,3.25549-6.462a.48254.48254,0,0,0-.83269-.48673,80.26843,80.26843,0,0,0-8.93181,24.64089q-.67059,3.6223-1.008,7.29576c-.05716.6177.90755.6138.96435,0Z" transform="translate(-229.66231 -213.02572)" fill="#e6e6e6"/><circle cx="124.61776" cy="93.66195" r="6.10756" fill="#e6e6e6"/><path d="M340.91907,342.122a79.17418,79.17418,0,0,1-10.20241-23.73277q-.86592-3.51453-1.40763-7.09749a.48254.48254,0,0,0-.95593.12838,80.26861,80.26861,0,0,0,8.11306,24.92246q1.6992,3.26858,3.69253,6.37255c.33485.5222,1.09311-.07423.76038-.59313Z" transform="translate(-229.66231 -213.02572)" fill="#e6e6e6"/><circle cx="98.68311" cy="93.23322" r="6.10756" fill="#e6e6e6"/><path d="M322.16929,347.52572v-12a4.50508,4.50508,0,0,1,4.5-4.5h28a4.50508,4.50508,0,0,1,4.5,4.5v12a4.50508,4.50508,0,0,1-4.5,4.5h-28A4.50508,4.50508,0,0,1,322.16929,347.52572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><path d="M353.66929,271.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,353.66929,271.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="108.00698" y="9.5" width="20" height="4" fill="#ccc"/><rect x="108.00698" y="40.5" width="20" height="4" fill="#ccc"/><path d="M383.66929,271.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,383.66929,271.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="138.00698" y="9.5" width="20" height="4" fill="#ccc"/><rect x="138.00698" y="40.5" width="20" height="4" fill="#ccc"/><path d="M413.66929,271.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,413.66929,271.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="168.00698" y="9.5" width="20" height="4" fill="#ccc"/><rect x="168.00698" y="40.5" width="20" height="4" fill="#ccc"/><path d="M429.66929,352.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,429.66929,352.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="184.00698" y="90.5" width="20" height="4" fill="#ccc"/><rect x="184.00698" y="121.5" width="20" height="4" fill="#ccc"/><path d="M459.66929,352.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,459.66929,352.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="214.00698" y="90.5" width="20" height="4" fill="#ccc"/><rect x="214.00698" y="121.5" width="20" height="4" fill="#ccc"/><path d="M489.66929,352.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,489.66929,352.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="244.00698" y="90.5" width="20" height="4" fill="#ccc"/><rect x="244.00698" y="121.5" width="20" height="4" fill="#ccc"/><path d="M350.66929,433.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,350.66929,433.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="105.00698" y="171.5" width="20" height="4" fill="#ccc"/><rect x="105.00698" y="202.5" width="20" height="4" fill="#ccc"/><path d="M380.66929,433.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,380.66929,433.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="135.00698" y="171.5" width="20" height="4" fill="#ccc"/><rect x="135.00698" y="202.5" width="20" height="4" fill="#ccc"/><path d="M410.66929,433.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,410.66929,433.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="165.00698" y="171.5" width="20" height="4" fill="#ccc"/><rect x="165.00698" y="202.5" width="20" height="4" fill="#ccc"/><path d="M443.66929,271.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,443.66929,271.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="198.00698" y="9.5" width="20" height="4" fill="#ccc"/><rect x="198.00698" y="40.5" width="20" height="4" fill="#ccc"/><path d="M473.66929,271.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,473.66929,271.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="228.00698" y="9.5" width="20" height="4" fill="#ccc"/><rect x="228.00698" y="40.5" width="20" height="4" fill="#ccc"/><path d="M503.66929,271.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,503.66929,271.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="258.00698" y="9.5" width="20" height="4" fill="#ccc"/><rect x="258.00698" y="40.5" width="20" height="4" fill="#ccc"/><path d="M533.66929,271.02572h-12a4.50508,4.50508,0,0,1-4.5-4.5v-49a4.50508,4.50508,0,0,1,4.5-4.5h12a4.50508,4.50508,0,0,1,4.5,4.5v49A4.50508,4.50508,0,0,1,533.66929,271.02572Z" transform="translate(-229.66231 -213.02572)" fill="#f2f2f2" style="isolation:isolate"/><rect x="288.00698" y="9.5" width="20" height="4" fill="#ccc"/><rect x="288.00698" y="40.5" width="20" height="4" fill="#ccc"/><path d="M578.61024,272.52572h-285a1,1,0,0,1,0-2h285a1,1,0,0,1,0,2Z" transform="translate(-229.66231 -213.02572)" fill="#ccc"/><path d="M578.61024,353.52572h-285a1,1,0,0,1,0-2h285a1,1,0,0,1,0,2Z" transform="translate(-229.66231 -213.02572)" fill="#ccc"/><path d="M578.61024,434.52572h-285a1,1,0,0,1,0-2h285a1,1,0,0,1,0,2Z" transform="translate(-229.66231 -213.02572)" fill="#ccc"/><path d="M561.73228,591.508c0-7.732-29.10156-14-65-14s-65,6.268-65,14c0,4.95545,11.96436,9.30622,30,11.79432V680.008a6.5,6.5,0,0,0,13,0V604.684c6.87207.53241,14.27686.824,22,.824s15.12793-.29162,22-.824v75.324a6.5,6.5,0,0,0,13,0V603.30232C549.76793,600.81422,561.73228,596.46345,561.73228,591.508Z" transform="translate(-229.66231 -213.02572)" fill="#ccc"/><polygon points="319.563 460.906 331.823 460.906 337.655 413.618 319.561 413.618 319.563 460.906" fill="#a0616a"/><path d="M546.59844,670.42825h38.53073a0,0,0,0,1,0,0v14.88687a0,0,0,0,1,0,0H561.4853a14.88686,14.88686,0,0,1-14.88686-14.88686v0A0,0,0,0,1,546.59844,670.42825Z" transform="translate(902.09624 1142.69183) rotate(179.99738)" fill="#2f2e41"/><polygon points="338.701 454.064 350.398 450.393 341.806 403.528 324.542 408.945 338.701 454.064" fill="#a0616a"/><path d="M566.15243,658.42388h38.53073a0,0,0,0,1,0,0v14.88687a0,0,0,0,1,0,0H581.03928a14.88686,14.88686,0,0,1-14.88686-14.88686v0A0,0,0,0,1,566.15243,658.42388Z" transform="translate(1113.68794 912.87602) rotate(162.57738)" fill="#2f2e41"/><path d="M560.16112,645.32445l-22.42725-58.87109c-5.03149,1.18164-47.22656,10.51855-61.35889-4.52832-4.84326-5.15723-5.78784-12.62891-2.80761-22.208l8.24756-10.36426.25634.01367c3.11841.16406,76.43531,4.27344,81.46119,26.38867,4.23754,18.64454,11.97656,53.40528,14.24731,63.61524a4.51573,4.51573,0,0,1-2.814,5.18359l-9.01928,3.38184a4.46228,4.46228,0,0,1-1.572.28613A4.51293,4.51293,0,0,1,560.16112,645.32445Z" transform="translate(-229.66231 -213.02572)" fill="#2f2e41"/><path d="M547.16112,652.32445l-22.42725-58.87109c-5.03174,1.18164-47.22656,10.51757-61.35889-4.52832-4.84326-5.15723-5.78784-12.62891-2.80761-22.208l8.24756-10.36426.25634.01367c3.11841.16406,76.43531,4.27344,81.46119,26.38867,4.23754,18.64454,11.97656,53.40528,14.24731,63.61524a4.51573,4.51573,0,0,1-2.814,5.18359l-9.01928,3.38184a4.46228,4.46228,0,0,1-1.572.28613A4.51293,4.51293,0,0,1,547.16112,652.32445Z" transform="translate(-229.66231 -213.02572)" fill="#2f2e41"/><path d="M577.46925,511.20192a10.52657,10.52657,0,0,0-.88488,1.40152l-49.32026,5.19623-7.09959-9.734-16.09071,8.79449,13.94447,23.62125,60.48739-15.42252a10.49579,10.49579,0,1,0-1.03642-13.857Z" transform="translate(-229.66231 -213.02572)" fill="#a0616a"/><path d="M460.25023,569.82543a4.50694,4.50694,0,0,1-1.81861-4.34864c2.78248-18.34277-.41894-44.61914-3.59472-63.43261a24.79543,24.79543,0,0,1,27.55932-28.76172,79.86459,79.86459,0,0,1,9.91285,1.9541h0a24.59626,24.59626,0,0,1,18.582,24.46777c-.47486,18.1543,3.21142,40.80567,4.81836,49.70117a4.51862,4.51862,0,0,1-.83374,3.51075,4.39229,4.39229,0,0,1-3.09546,1.75c-17.80811,1.81738-37.01221,10.50195-46.87451,15.5166a4.50062,4.50062,0,0,1-2.04956.501A4.42884,4.42884,0,0,1,460.25023,569.82543Z" transform="translate(-229.66231 -213.02572)" fill="#fed700"/><path d="M508.321,522.5139a4.47083,4.47083,0,0,1-2.47363-2.39746l-9.9314-22.94238a11.49973,11.49973,0,1,1,21.10718-9.13574l9.93164,22.94238a4.5057,4.5057,0,0,1-2.342,5.917l-12.8479,5.56153a4.46857,4.46857,0,0,1-3.44385.05468Z" transform="translate(-229.66231 -213.02572)" fill="#fed700"/><path d="M530.46925,509.20192a10.52657,10.52657,0,0,0-.88488,1.40152l-49.32026,5.19623-7.09959-9.734-16.09071,8.79449,13.94447,23.62125,60.48739-15.42252a10.49579,10.49579,0,1,0-1.03642-13.857Z" transform="translate(-229.66231 -213.02572)" fill="#a0616a"/><path d="M461.321,523.5139a4.47083,4.47083,0,0,1-2.47363-2.39746l-9.9314-22.94238a11.49973,11.49973,0,1,1,21.10718-9.13574l9.93164,22.94238a4.5057,4.5057,0,0,1-2.342,5.917l-12.8479,5.56153a4.46857,4.46857,0,0,1-3.44385.05468Z" transform="translate(-229.66231 -213.02572)" fill="#fed700"/><circle cx="250.90622" cy="227.89093" r="24.56103" fill="#a0616a"/><path d="M463.008,457.77336a2.13481,2.13481,0,0,1,1.85636-2.81906,4.93049,4.93049,0,0,1,3.4761,1.715,13.83414,13.83414,0,0,0,3.07115,2.63711c1.18812.59889,2.79953.51354,3.47686-.62825.636-1.0722.20022-2.508-.18483-3.75346a36.90711,36.90711,0,0,1-1.62991-9.77c-.11092-3.70031.41115-7.562,2.45972-10.44806,2.64387-3.72476,7.37142-5.13883,11.84544-5.03631s8.87547,1.48363,13.30714,2.35666c1.52991.30139,3.32825.45549,4.35152-.73025,1.08805-1.26082.68844-3.3014.22563-5.00376-1.20094-4.41743-2.475-8.98461-5.26525-12.55225a18.89838,18.89838,0,0,0-12.06081-6.79013,28.93848,28.93848,0,0,0-13.46236,1.52838,36.09622,36.09622,0,0,0-17.68285,12.3186,29.23591,29.23591,0,0,0-5.57809,21.60019,26.66717,26.66717,0,0,0,9.88579,16.85462Z" transform="translate(-229.66231 -213.02572)" fill="#2f2e41"/><path d="M369.22831,516.90206a122.0417,122.0417,0,0,1,10.10051-38.51722q2.27961-5.09249,5.01812-9.96076a.7438.7438,0,0,0-1.28353-.75026,123.72825,123.72825,0,0,0-13.7678,37.98231q-1.03368,5.58355-1.55378,11.24593c-.08812.95214,1.39892.94613,1.48648,0Z" transform="translate(-229.66231 -213.02572)" fill="#3f3d56"/><circle cx="158.28495" cy="248.36795" r="9.4144" fill="#fed700"/><path d="M370.19134,517.1521a79.17409,79.17409,0,0,1,6.55267-24.98792q1.47889-3.30372,3.25549-6.462a.48254.48254,0,0,0-.83269-.48673,80.26817,80.26817,0,0,0-8.93181,24.64089q-.67059,3.62231-1.008,7.29576c-.05716.6177.90755.6138.96435,0Z" transform="translate(-229.66231 -213.02572)" fill="#3f3d56"/><circle cx="152.67288" cy="268.1155" r="6.10756" fill="#fed700"/><path d="M368.97418,516.57556a79.17415,79.17415,0,0,1-10.2024-23.73277q-.86592-3.51453-1.40763-7.09749a.48254.48254,0,0,0-.95593.12838,80.26861,80.26861,0,0,0,8.11306,24.92246q1.69919,3.26857,3.69253,6.37255c.33485.5222,1.09311-.07423.76037-.59313Z" transform="translate(-229.66231 -213.02572)" fill="#3f3d56"/><circle cx="126.73823" cy="267.68677" r="6.10756" fill="#fed700"/><path d="M350.22441,521.97927v-12a4.50508,4.50508,0,0,1,4.5-4.5h28a4.50508,4.50508,0,0,1,4.5,4.5v12a4.50508,4.50508,0,0,1-4.5,4.5h-28A4.50508,4.50508,0,0,1,350.22441,521.97927Z" transform="translate(-229.66231 -213.02572)" fill="#ccc"/><rect x="289.06998" y="305.48228" width="86" height="7" rx="3.5" fill="#ccc"/><path d="M654.23228,525.508h-89a6.50736,6.50736,0,0,1-6.5-6.5v-49a6.50737,6.50737,0,0,1,6.5-6.5h89a6.50737,6.50737,0,0,1,6.5,6.5v49A6.50736,6.50736,0,0,1,654.23228,525.508Z" transform="translate(-229.66231 -213.02572)" fill="#ccc"/><circle cx="380.06998" cy="281.48228" r="6" fill="#fff"/><path d="M880.73228,591.508c0-7.732-29.10156-14-65-14s-65,6.268-65,14c0,4.95545,11.96436,9.30622,30,11.79432V680.008a6.5,6.5,0,0,0,13,0V604.684c6.87207.53241,14.27686.824,22,.824s15.12793-.29162,22-.824v75.324a6.5,6.5,0,0,0,13,0V603.30232C868.76793,600.81422,880.73228,596.46345,880.73228,591.508Z" transform="translate(-229.66231 -213.02572)" fill="#ccc"/><circle cx="593.25735" cy="222.33974" r="28" fill="#2f2e41"/><polygon points="523.577 461.906 511.317 461.906 505.485 414.618 523.579 414.618 523.577 461.906" fill="#ffb8b8"/><path d="M502.56,458.40253h23.64387a0,0,0,0,1,0,0v14.88687a0,0,0,0,1,0,0H487.67309a0,0,0,0,1,0,0v0A14.88686,14.88686,0,0,1,502.56,458.40253Z" fill="#2f2e41"/><polygon points="504.439 455.064 492.742 451.393 501.334 404.528 518.598 409.945 504.439 455.064" fill="#ffb8b8"/><path d="M712.66827,659.42388h23.64387a0,0,0,0,1,0,0v14.88687a0,0,0,0,1,0,0H697.78141a0,0,0,0,1,0,0v0A14.88686,14.88686,0,0,1,712.66827,659.42388Z" transform="translate(2.90601 -397.12769) rotate(17.42262)" fill="#2f2e41"/><path d="M738.09007,649.22191a4.46224,4.46224,0,0,1-1.572-.28613l-9.01929-3.38184a4.51573,4.51573,0,0,1-2.814-5.18359c2.27075-10.21,10.00976-44.9707,14.24731-63.61524,5.02588-22.11523,78.34278-26.22461,81.46118-26.38867l.25635-.01367L828.8972,560.717c2.98022,9.5791,2.03564,17.05078-2.80762,22.208-14.13232,15.04687-56.32739,5.71-61.35888,4.52832l-22.42725,58.87109A4.51293,4.51293,0,0,1,738.09007,649.22191Z" transform="translate(-229.66231 -213.02572)" fill="#2f2e41"/><path d="M751.09007,656.22191a4.46224,4.46224,0,0,1-1.572-.28613l-9.01929-3.38184a4.51573,4.51573,0,0,1-2.814-5.18359c2.27075-10.21,10.00976-44.9707,14.24731-63.61524,5.02588-22.11523,78.34278-26.22461,81.46118-26.38867l.25635-.01367L841.8972,567.717c2.98022,9.5791,2.03564,17.05078-2.80762,22.208-14.13232,15.04589-56.32715,5.71-61.35888,4.52832l-22.42725,58.87109A4.51293,4.51293,0,0,1,751.09007,656.22191Z" transform="translate(-229.66231 -213.02572)" fill="#2f2e41"/><path d="M724.99531,512.20192a10.52563,10.52563,0,0,1,.88489,1.40152l49.32026,5.19623,7.09959-9.734,16.09071,8.79449-13.94447,23.62125L723.9589,526.05891a10.4958,10.4958,0,1,1,1.03641-13.857Z" transform="translate(-229.66231 -213.02572)" fill="#ffb8b8"/><path d="M839.60838,571.68383a4.50062,4.50062,0,0,1-2.04956-.501c-9.8623-5.01465-29.06641-13.69922-46.87451-15.5166a4.39229,4.39229,0,0,1-3.09546-1.75,4.51858,4.51858,0,0,1-.83374-3.51075c1.60693-8.8955,5.29321-31.54687,4.81836-49.70117a24.59626,24.59626,0,0,1,18.582-24.46777h0a79.86445,79.86445,0,0,1,9.91284-1.9541,24.79544,24.79544,0,0,1,27.55933,28.76172c-3.17578,18.81347-6.3772,45.08984-3.59473,63.43261a4.50694,4.50694,0,0,1-1.8186,4.34864A4.42884,4.42884,0,0,1,839.60838,571.68383Z" transform="translate(-229.66231 -213.02572)" fill="#fed700"/><path d="M792.48631,523.83226a4.49628,4.49628,0,0,1-1.78662-.373l-12.8479-5.56153a4.50569,4.50569,0,0,1-2.342-5.917l9.93164-22.94238a11.49973,11.49973,0,1,1,21.10718,9.13574l-9.9314,22.94238a4.51063,4.51063,0,0,1-4.13086,2.71582Z" transform="translate(-229.66231 -213.02572)" fill="#fed700"/><circle cx="592.23373" cy="228.89093" r="24.56103" fill="#ffb8b8"/><path d="M796.89293,429.84957A88.59059,88.59059,0,0,0,835.21911,442.478l-4.03992-4.84061a29.68817,29.68817,0,0,0,9.17074,1.82105c3.13021-.04875,6.40987-1.254,8.18642-3.83171a9.342,9.342,0,0,0,.62531-8.62974,17.694,17.694,0,0,0-5.56636-6.96014,33.13951,33.13951,0,0,0-30.84447-5.51248,19.80609,19.80609,0,0,0-9.21238,5.90943c-2.32838,2.87238-6.81094,5.43156-5.61879,8.93167Z" transform="translate(-229.66231 -213.02572)" fill="#2f2e41"/><path d="M826.02581,410.0287a75.48471,75.48471,0,0,0-27.463-17.7592c-6.63872-2.45941-13.86459-3.97895-20.80509-2.58225s-13.50411,6.19807-15.44041,13.00778c-1.58332,5.56836.05158,11.56379,2.50871,16.80555s5.73758,10.10247,7.72463,15.53985a35.46793,35.46793,0,0,1-35.689,47.56227c6.81938.91437,13.10515,4.119,19.77076,5.82483s14.53281,1.59011,19.48624-3.18519c5.24091-5.05244,5.34584-13.26718,5.09245-20.54249l-1.13-32.445c-.1921-5.51543-.35615-11.20763,1.63288-16.35551s6.71617-9.65569,12.23475-9.60885c4.18253.0355,7.88442,2.56926,11.23865,5.068s6.90446,5.16474,11.0706,5.53641,8.92293-2.71144,8.61118-6.88249" transform="translate(-229.66231 -213.02572)" fill="#2f2e41"/><polygon points="482.197 281.087 538.75 282.422 546.943 342.878 494.716 344.05 482.197 281.087" fill="#e6e6e6"/><path d="M771.99531,510.20192a10.52563,10.52563,0,0,1,.88489,1.40152l49.32026,5.19623,7.09959-9.734,16.09071,8.79449-13.94447,23.62125L770.9589,524.05891a10.4958,10.4958,0,1,1,1.03641-13.857Z" transform="translate(-229.66231 -213.02572)" fill="#ffb8b8"/><path d="M839.48631,524.83226a4.49628,4.49628,0,0,1-1.78662-.373l-12.8479-5.56153a4.50569,4.50569,0,0,1-2.342-5.917l9.93164-22.94238a11.49973,11.49973,0,1,1,21.10718,9.13574l-9.9314,22.94238a4.51063,4.51063,0,0,1-4.13086,2.71582Z" transform="translate(-229.66231 -213.02572)" fill="#fed700"/><path d="M723.23228,524.508h-398a6.5,6.5,0,1,0,0,13h11.5v141.5a6.5,6.5,0,0,0,13,0V549.99806l72.8711,132.07788a6.5,6.5,0,0,0,11.2583-6.5L357.6859,537.508H705.73228v.08417l-76.1289,137.98377a6.49977,6.49977,0,1,0,11.25781,6.5l64.87109-117.57813V679.008a6.5,6.5,0,0,0,13,0V540.93537l1.89112-3.42737h2.60888a6.5,6.5,0,0,0,0-13Z" transform="translate(-229.66231 -213.02572)" fill="#3f3d56"/><path d="M969.147,686.97428H230.853a1.19069,1.19069,0,0,1,0-2.38137H969.147a1.19068,1.19068,0,0,1,0,2.38137Z" transform="translate(-229.66231 -213.02572)" fill="#3f3d56"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-section" id="questions-list">
            <div class="container page__container">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-body border-0 shadow-none pb-lg-0 pb-md-0">
                            <div class="d-flex flex-column flex-lg-row flex-md-row">
                                <div class="col-lg-9 col-md-9">
                                    <div class="d-lg-none d-xl-none mb-16pt">
                                        <button class="btn btn-block btn-dark rounded-lg" data-toggle="modal"  data-target="#modal-filters">
                                            همه فیلترها
                                            <svg class="ml-2" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M20.6009 4.10156V6.30156C20.6009 7.10156 20.1009 8.10156 19.6009 8.60156L15.3009 12.4016C14.7009 12.9016 14.3009 13.9016 14.3009 14.7016V19.0016C14.3009 19.6016 13.9009 20.4016 13.4009 20.7016L12.0009 21.6016C10.7009 22.4016 8.90086 21.5016 8.90086 19.9016V14.6016C8.90086 13.9016 8.50086 13.0016 8.10086 12.5016L7.63086 12.0116C7.32086 11.6816 7.26086 11.1816 7.51086 10.7916L12.6309 2.57156C12.8109 2.28156 13.1309 2.10156 13.4809 2.10156H18.6009C19.7009 2.10156 20.6009 3.00156 20.6009 4.10156Z" fill="currentColor"></path>
                                            <path d="M10.3504 3.63156L6.80039 9.32156C6.46039 9.87156 5.68039 9.95156 5.23039 9.48156L4.30039 8.50156C3.80039 8.00156 3.40039 7.10156 3.40039 6.50156V4.20156C3.40039 3.00156 4.30039 2.10156 5.40039 2.10156H9.50039C10.2804 2.10156 10.7604 2.96156 10.3504 3.63156Z" fill="currentColor"></path> </g></svg>
                                        </button>
                                    </div>
                                    <div class="form-group w-100">
                                        <div class="input-group input-group-merge rounded-lg search-input" dir="">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text py-0"
                                                    style="border-top-right-radius: 10px; border-bottom-right-radius: 10px">
                                                    <span class="text-muted">
                                                        <button
                                                            class="btn btn-sm">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.25007 2.38782C8.54878 2.0992 10.1243 2 12 2C13.8757 2 15.4512 2.0992 16.7499 2.38782C18.06 2.67897 19.1488 3.176 19.9864 4.01358C20.824 4.85116 21.321 5.94002 21.6122 7.25007C21.9008 8.54878 22 10.1243 22 12C22 13.8757 21.9008 15.4512 21.6122 16.7499C21.321 18.06 20.824 19.1488 19.9864 19.9864C19.1488 20.824 18.06 21.321 16.7499 21.6122C15.4512 21.9008 13.8757 22 12 22C10.1243 22 8.54878 21.9008 7.25007 21.6122C5.94002 21.321 4.85116 20.824 4.01358 19.9864C3.176 19.1488 2.67897 18.06 2.38782 16.7499C2.0992 15.4512 2 13.8757 2 12C2 10.1243 2.0992 8.54878 2.38782 7.25007C2.67897 5.94002 3.176 4.85116 4.01358 4.01358C4.85116 3.176 5.94002 2.67897 7.25007 2.38782ZM9 11.5C9 10.1193 10.1193 9 11.5 9C12.8807 9 14 10.1193 14 11.5C14 12.8807 12.8807 14 11.5 14C10.1193 14 9 12.8807 9 11.5ZM11.5 7C9.01472 7 7 9.01472 7 11.5C7 13.9853 9.01472 16 11.5 16C12.3805 16 13.202 15.7471 13.8957 15.31L15.2929 16.7071C15.6834 17.0976 16.3166 17.0976 16.7071 16.7071C17.0976 16.3166 17.0976 15.6834 16.7071 15.2929L15.31 13.8957C15.7471 13.202 16 12.3805 16 11.5C16 9.01472 13.9853 7 11.5 7Z" fill="currentColor"></path></svg>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                            <input id="search-question" type="text" dir="rtl"
                                                class="form-control form-control-prepended"
                                                style="border-top-left-radius: 10px; border-bottom-left-radius: 10px"
                                                placeholder="جستجو..." value="{{ \request()->get('search') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    <a href="{{ route('discuss-create-question') }}"
                                        class="w-100 btn btn-yellow rounded-lg px-0"><span class="mr-2">ایجاد پرسش
                                            جدید</span><i class="fa fa-plus"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="search-title mb-16pt {{ ! \request()->get('search') ? 'd-none' : '' }}">
                            <div class="badge badge-lg badge-dark rounded-lg font-size-14pt">
                                محتوای جستجو شده:
                                <span class="search-keyword mx-2 font-bold">
                                    {{ \request()->get('search') }}
                                </span>
                                <a class="search-null" href="#">
                                    <svg class="text-yellow" width="18" height="18" viewBox="0 0 24 24" id="magicoon-Filled" xmlns="http://www.w3.org/2000/svg" fill="none">
                                    <path fill="currentColor" d="M15,2.5H9A6.513,6.513,0,0,0,2.5,9v6A6.513,6.513,0,0,0,9,21.5h6A6.513,6.513,0,0,0,21.5,15V9A6.513,6.513,0,0,0,15,2.5Zm.71,11.79a1.008,1.008,0,0,1,0,1.42,1.014,1.014,0,0,1-1.42,0L12,13.42,9.71,15.71a1.014,1.014,0,0,1-1.42,0,1.008,1.008,0,0,1,0-1.42L10.58,12,8.29,9.71A1,1,0,0,1,9.71,8.29L12,10.58l2.29-2.29a1,1,0,0,1,1.42,1.42L13.42,12Z"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div class="" id="search-result">
                            {{--  @include('discuss.search-result-all')  --}}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filters-larg-screen d-none d-lg-block">
                            @include('discuss.component.selection-question')

                            @include('discuss.component.category-question')

                            <div class="card card-body border-0 shadow-none">
                                <h5 class="mb-1"> کاربران برتر 30 روز قبل </h5>
                                <hr>
                                @include('discuss.component.top-users', [
                                    ($numberOfDays = 30),
                                    ($numberOfUsers = 15),
                                ])
                            </div>


                        </div>
                    </div>
                </div>


            </div>





        </div>
    </div>
@endsection





@section('script')
    <script src="/assets/js/scrollTo.js"></script>
    <script src="/assets/js/manage-bookmark.js"></script>

    <script type="text/javascript">
        if ($(window).width() < 960) {
            $(".filters-larg-screen").remove()
        }



        var paginate = 1;
        var endData = false;

        var is_loading_data = false;

        var currentUrl = window.location.href;
        var url = new URL(currentUrl);

        loadMoreData(url);

        $(document).on('submit', '#filter-type', function(e){
            e.preventDefault();

            var currentUrl = window.location.href;
            var url = new URL(currentUrl);

            if($(this).find("input[name=type]:checked").val() == 'all'){
                url.searchParams.delete("type");
            }else{
                url.searchParams.set("type", $(this).find("input[name=type]:checked").val()); // setting your param
            }


            paginate = 1;
            url.searchParams.delete("page");
            var newUrl = decodeURI(url.href); // for refuse encode char for example %5B=[ , %5D = ]

            window.history.pushState('', '', newUrl);

            endData = false;
            loadMoreData(newUrl);
        })


        $(document).on('submit', '#filter-category', function(e){
            e.preventDefault();
            var newUrl = ''

            $(this).find('input[type=checkbox]').each(
                function(index){
                    var input = $(this);

                    var currentUrl = window.location.href;
                    var url = new URL(currentUrl);

                    if(input.is(':checked')){
                        url.searchParams.set(input.attr('name'), input.val()); // setting your param
                        url.searchParams.delete("page");
                        newUrl = decodeURI(url.href); // for refuse encode char for example %5B=[ , %5D = ]
                    }else{
                        url.searchParams.delete(input.attr('name'));
                        url.searchParams.delete("page");
                        newUrl = decodeURI(url.href);
                    }

                    window.history.pushState('', '', newUrl);

                }
            );
            paginate = 1;
            endData = false;



            loadMoreData(newUrl);


        })



        $(window).scroll(function() {
            {{--  var $el = $('#search-result');
            var bottom = $el.position().top + $el.outerHeight(true);
            if ($(window).scrollTop() + $(window).height() >= $(window).height() + bottom &&
                !endData && !is_loading_data)  --}}
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - $('footer').height() &&
                !endData && !is_loading_data) {
                is_loading_data = true;
                paginate++;
                var currentUrl = window.location.href;
                var url = new URL(currentUrl);
                url.searchParams.set("page", paginate);
                var newUrl = decodeURI(url.href);
                loadMoreData(newUrl);
            }
        });
        // run function when user reaches to end of the page
        function loadMoreData(newUrl) {
            $.ajax({
                    url: newUrl,
                    type: 'get',
                    datatype: 'html',
                    beforeSend: function() {
                        $("body > *:not(.loading)").css("filter","blur(5px)")
                        $('.loading').show();
                    }
                })
                .done(function(data) {
                    $("body > *:not(.loading)").css("filter","blur(0)")
                    $('.loading').hide();

                    if (data.length == 0) {
                        endData = true;
                    }

                    var checkUrl = new URL(newUrl);
                    if( ! checkUrl.searchParams.has("page") || checkUrl.searchParams.get("page") == 1 ){
                        $('#search-result').html(data);
                    }else{
                        $('#search-result').append(data);
                    }

                    setTimeout(()=>{
                        is_loading_data = false
                    }, 1500)
                })
                .fail(function(jqXHR, ajaxOptions, thrownError) {
                    alert('Something went wrong.');
                });
        }
    </script>

    <script>
        $('#search-question').focusin(function() {
            $(".search-input").addClass('shadow-sm');
        })
        $('#search-question').focusout(function() {
            $(".search-input").removeClass('shadow-sm');
        })

        var timeout = null;
        var search_keyword = '{{ \request()->get('search') }}';
        $(document).on('keyup', ('#search-question'), function(event) {
            var key = $(this);


            clearTimeout(timeout);

            timeout = setTimeout(()=>{
                if(key.val() != search_keyword){

                    search_keyword = key.val();

                    endData = false;

                    $(".search-title").removeClass('d-none');
                    $(".search-title .search-keyword").text(key.val());


                    var currentUrl = window.location.href;
                    var url = new URL(currentUrl);
                    url.searchParams.set("search", key.val());
                    if(key.val() == ''){
                        url.searchParams.delete("search");
                    }
                    var newUrl = decodeURI(url.href);
                    window.history.pushState('', '', newUrl);

                    loadMoreData(newUrl);
                }
            }, 1500)

        });

        $(document).on('click', '.search-null', function(event){

            event.preventDefault();

            search_keyword = '';

            $('#search-question').val('');

            $(".search-title").addClass('d-none');

            $(".search-title .search-keyword").text('');

            endData = false;

            var currentUrl = window.location.href;
            var url = new URL(currentUrl);

            url.searchParams.delete("search");

            var newUrl = decodeURI(url.href);
            window.history.pushState('', '', newUrl);

            loadMoreData(newUrl);
        });
    </script>
@endsection

@section('modal')
    @parent

    <div class="modal fade" id="modal-filters" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen m-0" role="document">
            <div class="modal-content rounded-0 border-0" style="heigt: 100vh; overflow-y: auto;">
                <div class="d-flex px-3 my-16pt">
                    <div class="font-size-20pt font-bold my-auto">فیلترها</div>
                    <span class="ml-auto" data-dismiss="modal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="currentColor" d="M9.78363 8.30602C9.37561 7.89799 8.71407 7.89799 8.30604 8.30602C7.89802 8.71404 7.89802 9.37557 8.30604 9.78359L10.5225 12L8.30602 14.2164C7.89799 14.6244 7.89799 15.286 8.30602 15.694C8.71404 16.102 9.37558 16.102 9.78361 15.694L12 13.4776L14.2164 15.6939C14.6244 16.1019 15.286 16.1019 15.694 15.6939C16.102 15.2859 16.102 14.6243 15.694 14.2163L13.4776 12L15.694 9.78369C16.102 9.37567 16.102 8.71413 15.694 8.30611C15.2859 7.89809 14.6244 7.89809 14.2164 8.30611L12 10.5224L9.78363 8.30602Z"></path>
                            <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M0 12C0 21.882 2.118 24 12 24C21.882 24 24 21.882 24 12C24 2.118 21.882 0 12 0C2.118 0 0 2.118 0 12ZM2 12C2 14.4249 2.13254 16.2369 2.43771 17.6101C2.73783 18.9605 3.17768 19.7608 3.70846 20.2915C4.23924 20.8223 5.03949 21.2622 6.38993 21.5623C7.76307 21.8675 9.57515 22 12 22C14.4249 22 16.2369 21.8675 17.6101 21.5623C18.9605 21.2622 19.7608 20.8223 20.2915 20.2915C20.8223 19.7608 21.2622 18.9605 21.5623 17.6101C21.8675 16.2369 22 14.4249 22 12C22 9.57515 21.8675 7.76307 21.5623 6.38993C21.2622 5.03949 20.8223 4.23924 20.2915 3.70846C19.7608 3.17768 18.9605 2.73783 17.6101 2.43771C16.2369 2.13254 14.4249 2 12 2C9.57515 2 7.76307 2.13254 6.38993 2.43771C5.03949 2.73783 4.23924 3.17768 3.70846 3.70846C3.17768 4.23924 2.73783 5.03949 2.43771 6.38993C2.13254 7.76307 2 9.57515 2 12Z" fill-opacity="0.4"></path>
                        </svg>
                    </span>
                </div>
                <div class="px-16pt">
                    <div class="">
                        @include('discuss.component.selection-question')

                        @include('discuss.component.category-question')

                        <div class="card card-body shadow-none">
                            <h5 class="mb-1"> کاربران برتر 30 روز قبل </h5>
                            <hr>
                            @include('discuss.component.top-users', [
                                ($numberOfDays = 30),
                                ($numberOfUsers = 15),
                            ])
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
