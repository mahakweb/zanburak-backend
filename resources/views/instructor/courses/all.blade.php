@extends('layouts.master')

@section('title', 'مدیریت دوره ها')

@section('head')
@endsection

@section('header')
    {{--    @parent--}}
    @include('instructor.layouts.header')
@endsection

@section('content')
    <div class="mdk-header-layout__content page-content ">
        <div class="page-section bg-alt border-bottom-2">
            <div class="container page__container">
                <div class="d-flex flex-column flex-lg-row align-items-center">
                    <div class="flex d-flex flex-column align-items-center align-items-lg-start mb-16pt mb-lg-0 text-center text-lg-left">
                        <h1 class="h2 mb-8pt">Manage Courses</h1>
                        <div>
                                    <span class="chip chip-outline-secondary d-inline-flex align-items-center" data-toggle="tooltip" data-title="Earnings" data-placement="bottom">
                                    <i class="material-icons icon--left">trending_up</i> &dollar;12.3k
                                    </span>
                            <span class="chip chip-outline-secondary d-inline-flex align-items-center" data-toggle="tooltip" data-title="Sales" data-placement="bottom">
                                    <i class="material-icons icon--left">receipt</i> 264
                                    </span>
                        </div>
                    </div>
                    <div class="ml-lg-16pt">
                        <a href="learnly-teacher-profile.html" class="btn btn-light">My Profile</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-section">
            <div class="container page__container">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center mb-24pt" style="white-space: nowrap;">
                            <small class="flex text-muted text-headings text-uppercase mr-3 mb-2 mb-sm-0">Displaying 4 out of 10 results</small>
                            <div class="w-auto ml-sm-auto table d-flex align-items-center mb-2 mb-sm-0">
                                <small class="text-muted text-headings text-uppercase mr-3 d-none d-sm-block">Sort by</small>
                                <a href="#" class="sort desc small text-headings text-uppercase">Newest</a>
                                <a href="#" class="sort small text-headings text-uppercase ml-2">Popularity</a>
                            </div>
                        </div>
                        <div class="page-separator">
                            <div class="page-separator__text">Development Courses</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 col-xl-4">
                                <div class="card card-sm card--elevated p-relative o-hidden overlay overlay--primary js-overlay mdk-reveal js-mdk-reveal " data-overlay-onload-show data-popover-onload-show data-force-reveal data-partial-height="44" data-toggle="popover" data-trigger="click">
                                    <a href="learnly-instructor-edit-course.html" class="js-image" data-position="">
                                        <img src="/assets/images/paths/angular_430x168.png" alt="course">
                                        <span class="overlay__content align-items-start justify-content-start">
                                            <span class="overlay__action card-body d-flex align-items-center">
                                            <i class="material-icons mr-4pt">edit</i>
                                            <span class="card-title text-white">Edit</span>
                                            </span>
                                            </span>
                                    </a>
                                    <div class="mdk-reveal__content">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex">
                                                    <a class="card-title mb-4pt" href="learnly-instructor-edit-course.html">Learn Angular fundamentals</a>
                                                </div>
                                                <a href="learnly-instructor-edit-course.html" class="ml-4pt material-icons text-black-20 card-course__icon-favorite">edit</a>
                                            </div>
                                            <div class="d-flex">
                                                <div class="rating flex">
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star_border</span></span>
                                                </div>
                                                <small class="text-black-50">6 hours</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="popoverContainer d-none">
                                    <div class="media">
                                        <div class="media-left mr-12pt">
                                            <img src="/assets/images/paths/angular_40x40@2x.png" width="40" height="40" alt="Angular" class="rounded">
                                        </div>
                                        <div class="media-body">
                                            <div class="card-title mb-0">Learn Angular fundamentals</div>
                                            <p class="lh-1">
                                                <span class="text-black-50 small">with</span>
                                                <span class="text-black-50 small font-weight-bold">Elijah Murray</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="my-16pt text-black-70">Learn the fundamentals of working with Angular and how to create basic applications.</p>
                                    <div class="mb-16pt">
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Fundamentals of working with Angular</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Create complete Angular applications</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Working with the Angular CLI</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Understanding Dependency Injection</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Testing with Angular</small></p>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">access_time</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>6 hours</small></p>
                                            </div>
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">play_circle_outline</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>12 lessons</small></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">assessment</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>Beginner</small></p>
                                            </div>
                                        </div>
                                        <div class="col text-right">
                                            <a href="learnly-instructor-edit-course.html" class="btn btn-primary">Edit course</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <div class="card card-sm card--elevated p-relative o-hidden overlay overlay--primary js-overlay mdk-reveal js-mdk-reveal " data-partial-height="44" data-toggle="popover" data-trigger="click">
                                    <a href="learnly-instructor-edit-course.html" class="js-image" data-position="">
                                        <img src="/assets/images/paths/swift_430x168.png" alt="course">
                                        <span class="overlay__content align-items-start justify-content-start">
                                            <span class="overlay__action card-body d-flex align-items-center">
                                            <i class="material-icons mr-4pt">edit</i>
                                            <span class="card-title text-white">Edit</span>
                                            </span>
                                            </span>
                                    </a>
                                    <div class="mdk-reveal__content">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex">
                                                    <a class="card-title mb-4pt" href="learnly-instructor-edit-course.html">Build an iOS Application in Swift</a>
                                                </div>
                                                <a href="learnly-instructor-edit-course.html" class="ml-4pt material-icons text-black-20 card-course__icon-favorite">edit</a>
                                            </div>
                                            <div class="d-flex">
                                                <div class="rating flex">
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star_border</span></span>
                                                </div>
                                                <small class="text-black-50">6 hours</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="popoverContainer d-none">
                                    <div class="media">
                                        <div class="media-left mr-12pt">
                                            <img src="/assets/images/paths/swift_40x40@2x.png" width="40" height="40" alt="Angular" class="rounded">
                                        </div>
                                        <div class="media-body">
                                            <div class="card-title mb-0">Build an iOS Application in Swift</div>
                                            <p class="lh-1">
                                                <span class="text-black-50 small">with</span>
                                                <span class="text-black-50 small font-weight-bold">Elijah Murray</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="my-16pt text-black-70">Learn the fundamentals of working with Angular and how to create basic applications.</p>
                                    <div class="mb-16pt">
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Fundamentals of working with Angular</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Create complete Angular applications</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Working with the Angular CLI</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Understanding Dependency Injection</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Testing with Angular</small></p>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">access_time</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>6 hours</small></p>
                                            </div>
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">play_circle_outline</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>12 lessons</small></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">assessment</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>Beginner</small></p>
                                            </div>
                                        </div>
                                        <div class="col text-right">
                                            <a href="learnly-instructor-edit-course.html" class="btn btn-primary">Edit course</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <div class="card card-sm card--elevated p-relative o-hidden overlay overlay--primary js-overlay mdk-reveal js-mdk-reveal " data-partial-height="44" data-toggle="popover" data-trigger="click">
                                    <a href="learnly-instructor-edit-course.html" class="js-image" data-position="">
                                        <img src="/assets/images/paths/wordpress_430x168.png" alt="course">
                                        <span class="overlay__content align-items-start justify-content-start">
                                            <span class="overlay__action card-body d-flex align-items-center">
                                            <i class="material-icons mr-4pt">edit</i>
                                            <span class="card-title text-white">Edit</span>
                                            </span>
                                            </span>
                                    </a>
                                    <div class="mdk-reveal__content">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex">
                                                    <a class="card-title mb-4pt" href="learnly-instructor-edit-course.html">Build a WordPress Website</a>
                                                </div>
                                                <a href="learnly-instructor-edit-course.html" class="ml-4pt material-icons text-black-20 card-course__icon-favorite">edit</a>
                                            </div>
                                            <div class="d-flex">
                                                <div class="rating flex">
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star_border</span></span>
                                                </div>
                                                <small class="text-black-50">6 hours</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="popoverContainer d-none">
                                    <div class="media">
                                        <div class="media-left mr-12pt">
                                            <img src="/assets/images/paths/wordpress_40x40@2x.png" width="40" height="40" alt="Angular" class="rounded">
                                        </div>
                                        <div class="media-body">
                                            <div class="card-title mb-0">Build a WordPress Website</div>
                                            <p class="lh-1">
                                                <span class="text-black-50 small">with</span>
                                                <span class="text-black-50 small font-weight-bold">Elijah Murray</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="my-16pt text-black-70">Learn the fundamentals of working with Angular and how to create basic applications.</p>
                                    <div class="mb-16pt">
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Fundamentals of working with Angular</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Create complete Angular applications</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Working with the Angular CLI</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Understanding Dependency Injection</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Testing with Angular</small></p>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">access_time</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>6 hours</small></p>
                                            </div>
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">play_circle_outline</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>12 lessons</small></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">assessment</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>Beginner</small></p>
                                            </div>
                                        </div>
                                        <div class="col text-right">
                                            <a href="learnly-instructor-edit-course.html" class="btn btn-primary">Edit course</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <div class="card card-sm card--elevated p-relative o-hidden overlay overlay--primary js-overlay mdk-reveal js-mdk-reveal " data-partial-height="44" data-toggle="popover" data-trigger="click">
                                    <a href="learnly-instructor-edit-course.html" class="js-image" data-position="left">
                                        <img src="/assets/images/paths/react_430x168.png" alt="course">
                                        <span class="overlay__content align-items-start justify-content-start">
                                            <span class="overlay__action card-body d-flex align-items-center">
                                            <i class="material-icons mr-4pt">edit</i>
                                            <span class="card-title text-white">Edit</span>
                                            </span>
                                            </span>
                                    </a>
                                    <div class="mdk-reveal__content">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex">
                                                    <a class="card-title mb-4pt" href="learnly-instructor-edit-course.html">Become a React Native Developer</a>
                                                </div>
                                                <a href="learnly-instructor-edit-course.html" class="ml-4pt material-icons text-black-20 card-course__icon-favorite">edit</a>
                                            </div>
                                            <div class="d-flex">
                                                <div class="rating flex">
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star_border</span></span>
                                                </div>
                                                <small class="text-black-50">6 hours</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="popoverContainer d-none">
                                    <div class="media">
                                        <div class="media-left mr-12pt">
                                            <img src="/assets/images/paths/react_40x40@2x.png" width="40" height="40" alt="Angular" class="rounded">
                                        </div>
                                        <div class="media-body">
                                            <div class="card-title mb-0">Become a React Native Developer</div>
                                            <p class="lh-1">
                                                <span class="text-black-50 small">with</span>
                                                <span class="text-black-50 small font-weight-bold">Elijah Murray</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="my-16pt text-black-70">Learn the fundamentals of working with Angular and how to create basic applications.</p>
                                    <div class="mb-16pt">
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Fundamentals of working with Angular</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Create complete Angular applications</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Working with the Angular CLI</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Understanding Dependency Injection</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Testing with Angular</small></p>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">access_time</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>6 hours</small></p>
                                            </div>
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">play_circle_outline</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>12 lessons</small></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">assessment</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>Beginner</small></p>
                                            </div>
                                        </div>
                                        <div class="col text-right">
                                            <a href="learnly-instructor-edit-course.html" class="btn btn-primary">Edit course</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-32pt">
                            <ul class="pagination justify-content-start pagination-xsm m-0">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" aria-label="Previous">
                                        <span aria-hidden="true" class="material-icons">chevron_left</span>
                                        <span>Prev</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#" aria-label="Page 1">
                                        <span>1</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#" aria-label="Page 2">
                                        <span>2</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#" aria-label="Next">
                                        <span>Next</span>
                                        <span aria-hidden="true" class="material-icons">chevron_right</span>
                                    </a>
                                </li>
                            </ul>
                            <!-- <ul class="pagination justify-content-center pagination-sm">
                                <li class="page-item disabled">
                                  <a class="page-link" href="#" aria-label="Previous">
                                    <span aria-hidden="true" class="material-icons">chevron_left</span>
                                    <span>Prev</span>
                                  </a>
                                </li>
                                <li class="page-item active">
                                  <a class="page-link" href="#" aria-label="1">
                                    <span>1</span>
                                  </a>
                                </li>
                                <li class="page-item">
                                  <a class="page-link" href="#" aria-label="1">
                                    <span>2</span>
                                  </a>
                                </li>
                                <li class="page-item">
                                  <a class="page-link" href="#" aria-label="Next">
                                    <span>Next</span>
                                    <span aria-hidden="true" class="material-icons">chevron_right</span>
                                  </a>
                                </li>
                                </ul> -->
                        </div>
                        <div class="page-separator">
                            <div class="page-separator__text">Design Courses</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 col-xl-4">
                                <div class="card card-sm card--elevated p-relative o-hidden overlay overlay--primary js-overlay mdk-reveal js-mdk-reveal " data-partial-height="44" data-toggle="popover" data-trigger="click">
                                    <a href="learnly-instructor-edit-course.html" class="js-image" data-position="">
                                        <img src="/assets/images/paths/sketch_430x168.png" alt="course">
                                        <span class="overlay__content align-items-start justify-content-start">
                                            <span class="overlay__action card-body d-flex align-items-center">
                                            <i class="material-icons mr-4pt">edit</i>
                                            <span class="card-title text-white">Edit</span>
                                            </span>
                                            </span>
                                    </a>
                                    <div class="mdk-reveal__content">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex">
                                                    <a class="card-title mb-4pt" href="learnly-instructor-edit-course.html">Learn Sketch</a>
                                                </div>
                                                <a href="learnly-instructor-edit-course.html" class="ml-4pt material-icons text-black-20 card-course__icon-favorite">edit</a>
                                            </div>
                                            <div class="d-flex">
                                                <div class="rating flex">
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star_border</span></span>
                                                </div>
                                                <small class="text-black-50">6 hours</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="popoverContainer d-none">
                                    <div class="media">
                                        <div class="media-left mr-12pt">
                                            <img src="/assets/images/paths/sketch_40x40@2x.png" width="40" height="40" alt="Angular" class="rounded">
                                        </div>
                                        <div class="media-body">
                                            <div class="card-title mb-0">Learn Sketch</div>
                                            <p class="lh-1">
                                                <span class="text-black-50 small">with</span>
                                                <span class="text-black-50 small font-weight-bold">Elijah Murray</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="my-16pt text-black-70">Learn the fundamentals of working with Angular and how to create basic applications.</p>
                                    <div class="mb-16pt">
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Fundamentals of working with Angular</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Create complete Angular applications</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Working with the Angular CLI</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Understanding Dependency Injection</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Testing with Angular</small></p>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">access_time</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>6 hours</small></p>
                                            </div>
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">play_circle_outline</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>12 lessons</small></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">assessment</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>Beginner</small></p>
                                            </div>
                                        </div>
                                        <div class="col text-right">
                                            <a href="learnly-instructor-edit-course.html" class="btn btn-primary">Edit course</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <div class="card card-sm card--elevated p-relative o-hidden overlay overlay--primary js-overlay mdk-reveal js-mdk-reveal " data-partial-height="44" data-toggle="popover" data-trigger="click">
                                    <a href="learnly-instructor-edit-course.html" class="js-image" data-position="">
                                        <img src="/assets/images/paths/flinto_430x168.png" alt="course">
                                        <span class="overlay__content align-items-start justify-content-start">
                                            <span class="overlay__action card-body d-flex align-items-center">
                                            <i class="material-icons mr-4pt">edit</i>
                                            <span class="card-title text-white">Edit</span>
                                            </span>
                                            </span>
                                    </a>
                                    <div class="mdk-reveal__content">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex">
                                                    <a class="card-title mb-4pt" href="learnly-instructor-edit-course.html">Learn Flinto</a>
                                                </div>
                                                <a href="learnly-instructor-edit-course.html" class="ml-4pt material-icons text-black-20 card-course__icon-favorite">edit</a>
                                            </div>
                                            <div class="d-flex">
                                                <div class="rating flex">
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star_border</span></span>
                                                </div>
                                                <small class="text-black-50">6 hours</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="popoverContainer d-none">
                                    <div class="media">
                                        <div class="media-left mr-12pt">
                                            <img src="/assets/images/paths/flinto_40x40@2x.png" width="40" height="40" alt="Angular" class="rounded">
                                        </div>
                                        <div class="media-body">
                                            <div class="card-title mb-0">Learn Flinto</div>
                                            <p class="lh-1">
                                                <span class="text-black-50 small">with</span>
                                                <span class="text-black-50 small font-weight-bold">Elijah Murray</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="my-16pt text-black-70">Learn the fundamentals of working with Angular and how to create basic applications.</p>
                                    <div class="mb-16pt">
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Fundamentals of working with Angular</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Create complete Angular applications</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Working with the Angular CLI</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Understanding Dependency Injection</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Testing with Angular</small></p>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">access_time</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>6 hours</small></p>
                                            </div>
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">play_circle_outline</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>12 lessons</small></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">assessment</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>Beginner</small></p>
                                            </div>
                                        </div>
                                        <div class="col text-right">
                                            <a href="learnly-instructor-edit-course.html" class="btn btn-primary">Edit course</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <div class="card card-sm card--elevated p-relative o-hidden overlay overlay--primary js-overlay mdk-reveal js-mdk-reveal " data-partial-height="44" data-toggle="popover" data-trigger="click">
                                    <a href="learnly-instructor-edit-course.html" class="js-image" data-position="">
                                        <img src="/assets/images/paths/photoshop_430x168.png" alt="course">
                                        <span class="overlay__content align-items-start justify-content-start">
                                            <span class="overlay__action card-body d-flex align-items-center">
                                            <i class="material-icons mr-4pt">edit</i>
                                            <span class="card-title text-white">Edit</span>
                                            </span>
                                            </span>
                                    </a>
                                    <div class="mdk-reveal__content">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex">
                                                    <a class="card-title mb-4pt" href="learnly-instructor-edit-course.html">Learn Photoshop</a>
                                                </div>
                                                <a href="learnly-instructor-edit-course.html" class="ml-4pt material-icons text-black-20 card-course__icon-favorite">edit</a>
                                            </div>
                                            <div class="d-flex">
                                                <div class="rating flex">
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star_border</span></span>
                                                </div>
                                                <small class="text-black-50">6 hours</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="popoverContainer d-none">
                                    <div class="media">
                                        <div class="media-left mr-12pt">
                                            <img src="/assets/images/paths/photoshop_40x40@2x.png" width="40" height="40" alt="Angular" class="rounded">
                                        </div>
                                        <div class="media-body">
                                            <div class="card-title mb-0">Learn Photoshop</div>
                                            <p class="lh-1">
                                                <span class="text-black-50 small">with</span>
                                                <span class="text-black-50 small font-weight-bold">Elijah Murray</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="my-16pt text-black-70">Learn the fundamentals of working with Angular and how to create basic applications.</p>
                                    <div class="mb-16pt">
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Fundamentals of working with Angular</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Create complete Angular applications</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Working with the Angular CLI</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Understanding Dependency Injection</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Testing with Angular</small></p>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">access_time</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>6 hours</small></p>
                                            </div>
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">play_circle_outline</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>12 lessons</small></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">assessment</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>Beginner</small></p>
                                            </div>
                                        </div>
                                        <div class="col text-right">
                                            <a href="learnly-instructor-edit-course.html" class="btn btn-primary">Edit course</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <div class="card card-sm card--elevated p-relative o-hidden overlay overlay--primary js-overlay mdk-reveal js-mdk-reveal " data-partial-height="44" data-toggle="popover" data-trigger="click">
                                    <a href="learnly-instructor-edit-course.html" class="js-image" data-position="">
                                        <img src="/assets/images/paths/mailchimp_430x168.png" alt="course">
                                        <span class="overlay__content align-items-start justify-content-start">
                                            <span class="overlay__action card-body d-flex align-items-center">
                                            <i class="material-icons mr-4pt">edit</i>
                                            <span class="card-title text-white">Edit</span>
                                            </span>
                                            </span>
                                    </a>
                                    <div class="mdk-reveal__content">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex">
                                                    <a class="card-title mb-4pt" href="learnly-instructor-edit-course.html">Newsletter Design</a>
                                                </div>
                                                <a href="learnly-instructor-edit-course.html" class="ml-4pt material-icons text-black-20 card-course__icon-favorite">edit</a>
                                            </div>
                                            <div class="d-flex">
                                                <div class="rating flex">
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star</span></span>
                                                    <span class="rating__item"><span class="material-icons">star_border</span></span>
                                                </div>
                                                <small class="text-black-50">6 hours</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="popoverContainer d-none">
                                    <div class="media">
                                        <div class="media-left mr-12pt">
                                            <img src="/assets/images/paths/mailchimp_40x40@2x.png" width="40" height="40" alt="Angular" class="rounded">
                                        </div>
                                        <div class="media-body">
                                            <div class="card-title mb-0">Newsletter Design</div>
                                            <p class="lh-1">
                                                <span class="text-black-50 small">with</span>
                                                <span class="text-black-50 small font-weight-bold">Elijah Murray</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="my-16pt text-black-70">Learn the fundamentals of working with Angular and how to create basic applications.</p>
                                    <div class="mb-16pt">
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Fundamentals of working with Angular</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Create complete Angular applications</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Working with the Angular CLI</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Understanding Dependency Injection</small></p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-icons icon-16pt text-black-50 mr-8pt">check</span>
                                            <p class="flex text-black-50 lh-1 mb-0"><small>Testing with Angular</small></p>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">access_time</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>6 hours</small></p>
                                            </div>
                                            <div class="d-flex align-items-center mb-4pt">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">play_circle_outline</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>12 lessons</small></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="material-icons icon-16pt text-black-50 mr-4pt">assessment</span>
                                                <p class="flex text-black-50 lh-1 mb-0"><small>Beginner</small></p>
                                            </div>
                                        </div>
                                        <div class="col text-right">
                                            <a href="learnly-instructor-edit-course.html" class="btn btn-primary">Edit course</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="pagination justify-content-start pagination-xsm m-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" aria-label="Previous">
                                    <span aria-hidden="true" class="material-icons">chevron_left</span>
                                    <span>Prev</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#" aria-label="Page 1">
                                    <span>1</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#" aria-label="Page 2">
                                    <span>2</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#" aria-label="Next">
                                    <span>Next</span>
                                    <span aria-hidden="true" class="material-icons">chevron_right</span>
                                </a>
                            </li>
                        </ul>
                        <!-- <ul class="pagination justify-content-center pagination-sm">
                            <li class="page-item disabled">
                              <a class="page-link" href="#" aria-label="Previous">
                                <span aria-hidden="true" class="material-icons">chevron_left</span>
                                <span>Prev</span>
                              </a>
                            </li>
                            <li class="page-item active">
                              <a class="page-link" href="#" aria-label="1">
                                <span>1</span>
                              </a>
                            </li>
                            <li class="page-item">
                              <a class="page-link" href="#" aria-label="1">
                                <span>2</span>
                              </a>
                            </li>
                            <li class="page-item">
                              <a class="page-link" href="#" aria-label="Next">
                                <span>Next</span>
                                <span aria-hidden="true" class="material-icons">chevron_right</span>
                              </a>
                            </li>
                            </ul> -->
                    </div>
                    <div class="col-lg-4">
                        <div class="accordion js-accordion accordion--boxed mb-24pt" id="instructor-accordion">
                            <div class="accordion__item">
                                <a href="#" class="accordion__toggle collapsed" data-toggle="collapse" data-target="#instructor-accordion-menu" data-parent="#instructor-accordion">
                                    <span class="flex">My Account</span>
                                    <span class="accordion__toggle-icon material-icons">keyboard_arrow_down</span>
                                </a>
                                <div class="accordion__menu collapse" id="instructor-accordion-menu">
                                    <div class="accordion__menu-link">
                                                <span class="icon-holder icon-holder--small icon-holder--light rounded-circle d-inline-flex icon--left">
                                                <i class="material-icons icon-16pt">school</i>
                                                </span>
                                        <a class="flex" href="learnly-instructor-dashboard.html">Dashboard</a>
                                    </div>
                                    <div class="accordion__menu-link active">
                                                <span class="icon-holder icon-holder--small icon-holder--light rounded-circle d-inline-flex icon--left">
                                                <i class="material-icons icon-16pt">import_contacts</i>
                                                </span>
                                        <a class="flex" href="learnly-instructor-courses.html">Manage Courses</a>
                                    </div>
                                    <div class="accordion__menu-link">
                                                <span class="icon-holder icon-holder--small icon-holder--light rounded-circle d-inline-flex icon--left">
                                                <i class="material-icons icon-16pt">help</i>
                                                </span>
                                        <a class="flex" href="learnly-instructor-quizzes.html">Manage Quizzes</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="page-separator">
                            <div class="page-separator__text">Recommended</div>
                        </div>
                        <div class="mb-8pt d-flex align-items-center">
                            <a href="learnly-student-course.html" class="avatar avatar-4by3 overlay overlay--primary mr-12pt">
                                <img src="/assets/images/paths/angular_routing_200x168.png" alt="Angular Routing In-Depth" class="avatar-img rounded">
                                <span class="overlay__content"></span>
                            </a>
                            <div class="flex">
                                <a class="card-title mb-4pt" href="learnly-student-course.html">Angular Routing In-Depth</a>
                                <div class="d-flex align-items-center">
                                    <div class="rating mr-8pt">
                                        <span class="rating__item"><span class="material-icons">star</span></span>
                                        <span class="rating__item"><span class="material-icons">star</span></span>
                                        <span class="rating__item"><span class="material-icons">star</span></span>
                                        <span class="rating__item"><span class="material-icons">star_border</span></span>
                                        <span class="rating__item"><span class="material-icons">star_border</span></span>
                                    </div>
                                    <small class="text-muted">3/5</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-16pt d-flex align-items-center">
                            <a href="learnly-student-course.html" class="avatar avatar-4by3 overlay overlay--primary mr-12pt">
                                <img src="/assets/images/paths/angular_testing_200x168.png" alt="Angular Unit Testing" class="avatar-img rounded">
                                <span class="overlay__content"></span>
                            </a>
                            <div class="flex">
                                <a class="card-title mb-4pt" href="learnly-student-course.html">Angular Unit Testing</a>
                                <div class="d-flex align-items-center">
                                    <div class="rating mr-8pt">
                                        <span class="rating__item"><span class="material-icons">star</span></span>
                                        <span class="rating__item"><span class="material-icons">star</span></span>
                                        <span class="rating__item"><span class="material-icons">star</span></span>
                                        <span class="rating__item"><span class="material-icons">star</span></span>
                                        <span class="rating__item"><span class="material-icons">star_border</span></span>
                                    </div>
                                    <small class="text-muted">4/5</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0">
                                <a href="learnly-student-course.html" class="card-title mb-4pt">Angular Best Practices</a>
                                <p class="lh-1 mb-0">
                                    <small class="text-muted mr-8pt">6h 40m</small>
                                    <small class="text-muted mr-8pt">13,876 Views</small>
                                    <small class="text-muted">13 May 2018</small>
                                </p>
                            </div>
                            <div class="list-group-item px-0">
                                <a href="learnly-student-course.html" class="card-title mb-4pt">Unit Testing in Angular</a>
                                <p class="lh-1 mb-0">
                                    <small class="text-muted mr-8pt">6h 40m</small>
                                    <small class="text-muted mr-8pt">13,876 Views</small>
                                    <small class="text-muted">13 May 2018</small>
                                </p>
                            </div>
                            <div class="list-group-item px-0">
                                <a href="learnly-student-course.html" class="card-title mb-4pt">Migrating Applications from AngularJS to Angular</a>
                                <p class="lh-1 mb-0">
                                    <small class="text-muted mr-8pt">6h 40m</small>
                                    <small class="text-muted mr-8pt">13,876 Views</small>
                                    <small class="text-muted">13 May 2018</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('sidebar')
    @include('instructor.layouts.sidebar')
@endsection

@section('script')

@endsection




