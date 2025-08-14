<?php

use Illuminate\Support\Facades\Route;

if (! function_exists('isActive')){
    function isActive($key, $class = 'active'){
        if (is_array($key)){
            return in_array(Route::currentRouteName(), $key) ? $class : '';
        }

        return Route::currentRouteName() == $key ? $class : '';
    }
}
