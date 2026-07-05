<?php

namespace App\Http\Controllers\Concerns;

/**
 * Course-scoped admin authorization helpers.
 */
trait AuthorizesAdminCourses
{
    use AppliesContentScope;
}
