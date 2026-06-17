<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Database-dialect aware SQL fragment builder.
 *
 * The application was originally written against MySQL and uses a number of
 * MySQL-only scalar functions inside raw expressions (HOUR(), DAYOFWEEK(),
 * DATE_FORMAT(), CAST(... AS UNSIGNED), CONCAT(a, " ", b), ...). PostgreSQL
 * does not understand those, so every place that needs one of them now asks
 * this helper for the correct fragment for the active connection.
 *
 * Keeping both dialects here means the exact same code keeps working on the
 * legacy MySQL connection while running correctly on PostgreSQL.
 */
class SqlDialect
{
    public static function driver(?string $connection = null): string
    {
        return DB::connection($connection)->getDriverName();
    }

    public static function isPgsql(?string $connection = null): bool
    {
        return static::driver($connection) === 'pgsql';
    }

    /**
     * Date part of a timestamp. DATE() is valid on both MySQL and PostgreSQL.
     */
    public static function date(string $column): string
    {
        return "DATE($column)";
    }

    /**
     * Hour of day (0-23) as an integer.
     */
    public static function hour(string $column): string
    {
        return static::isPgsql()
            ? "EXTRACT(HOUR FROM $column)::int"
            : "HOUR($column)";
    }

    /**
     * Day of week normalised to MySQL's DAYOFWEEK(): 1 = Sunday ... 7 = Saturday.
     * PostgreSQL's EXTRACT(DOW) returns 0 (Sunday) - 6 (Saturday), so we add 1.
     */
    public static function dayOfWeek(string $column): string
    {
        return static::isPgsql()
            ? "(EXTRACT(DOW FROM $column)::int + 1)"
            : "DAYOFWEEK($column)";
    }

    public static function year(string $column): string
    {
        return static::isPgsql()
            ? "EXTRACT(YEAR FROM $column)::int"
            : "YEAR($column)";
    }

    public static function month(string $column): string
    {
        return static::isPgsql()
            ? "EXTRACT(MONTH FROM $column)::int"
            : "MONTH($column)";
    }

    /**
     * "YYYY-MM" bucket label.
     */
    public static function yearMonth(string $column): string
    {
        return static::isPgsql()
            ? "TO_CHAR($column, 'YYYY-MM')"
            : "DATE_FORMAT($column, '%Y-%m')";
    }

    /**
     * First day of the month as a "YYYY-MM-01" label.
     */
    public static function monthStart(string $column): string
    {
        return static::isPgsql()
            ? "TO_CHAR(date_trunc('month', $column), 'YYYY-MM-01')"
            : "DATE_FORMAT($column, '%Y-%m-01')";
    }

    /**
     * Monday-based start of the week, returned as a date.
     * Mirrors Carbon::startOfWeek(MONDAY) used on the PHP side.
     */
    public static function weekStart(string $column): string
    {
        return static::isPgsql()
            ? "(date_trunc('week', $column))::date"
            : "DATE(DATE_SUB($column, INTERVAL (WEEKDAY($column)) DAY))";
    }

    /**
     * Cast an expression to an integer for numeric ordering of string columns.
     */
    public static function castInt(string $expression): string
    {
        return static::isPgsql()
            ? "CAST($expression AS BIGINT)"
            : "CAST($expression AS UNSIGNED)";
    }

    /**
     * Day | week | month grouping expression for analytics dashboards.
     */
    public static function groupByPeriod(string $column, string $period): string
    {
        return match ($period) {
            'week' => static::weekStart($column),
            'month' => static::monthStart($column),
            default => static::date($column),
        };
    }

    /**
     * Concatenate columns/expressions with a literal separator between each.
     * PostgreSQL uses the || operator; MySQL uses CONCAT(). Note that PostgreSQL
     * treats double quotes as identifiers, so MySQL's CONCAT(a, " ", b) is invalid
     * there and must go through this helper.
     *
     * @param  array<int,string>  $columns
     */
    public static function concatWs(string $separator, array $columns): string
    {
        $sep = "'".str_replace("'", "''", $separator)."'";

        if (static::isPgsql()) {
            return implode(" || $sep || ", $columns);
        }

        return 'CONCAT('.implode(", $sep, ", $columns).')';
    }
}
