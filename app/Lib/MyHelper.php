<?php

namespace App\Lib;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MyHelper
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public static function setTimezone(int $utc)
    {
        $arr = [7 => 'Asia/Jakarta', 8 => 'Asia/Ujung_Pandang', 9 => 'Asia/Jayapura'];
        $timezone = $arr[$utc] ?? 'Asia/Jakarta';
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        return true;
    }

    public static function adjustTimezone($timeserver, $timezone = null, $format = null, $indo = false)
    {
        if (is_null($timezone)) {
            $user = request()->user();
            if ($user) {
                $timezone = $user->user_time_zone_utc ? ($user->user_time_zone_utc == 0 ? 7 : $user->user_time_zone_utc) : 7;
            } else {
                $timezone = 7;
            }
        }

        if (!is_numeric($timeserver)) {
            $timeserver = strtotime($timeserver);
        }

        $time = $timeserver + (($timezone - 7) * 3600);

        if ($format) {
            if ($indo) {
                return self::indonesian_date_v2($time, $format);
            }
            return date($format, $time);
        }
        return $time;
    }

    public static function reverseAdjustTimezone($timeserver, $timezone = null, $format = null, $indo = false)
    {
        if (is_null($timezone)) {
            $user = request()->user();
            if ($user) {
                $timezone = $user->user_time_zone_utc ?? 7;
            } else {
                $timezone = 7;
            }
        }

        if (!is_numeric($timeserver)) {
            $timeserver = strtotime($timeserver);
        }

        $time = $timeserver - (($timezone - 7) * 3600);

        if ($format) {
            if ($indo) {
                return self::indonesian_date_v2($time, $format);
            }
            return date($format, $time);
        }
        return $time;
    }

    public static function indonesian_date_v2($timestamp = '', $date_format = 'l, d F Y H:i')
    {
        if (trim($timestamp) == '') {
            $timestamp = time();
        } elseif (!ctype_digit($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        # remove S (st,nd,rd,th) there are no such things in indonesia :p
        $date_format = preg_replace("/S/", "", $date_format);

        $pattern = array(
            '/Mon[^day]/', '/Tue[^sday]/', '/Wed[^nesday]/', '/Thu[^rsday]/',
            '/Fri[^day]/', '/Sat[^urday]/', '/Sun[^day]/', '/Monday/', '/Tuesday/',
            '/Wednesday/', '/Thursday/', '/Friday/', '/Saturday/', '/Sunday/',
            '/Jan[^uary]/', '/Feb[^ruary]/', '/Mar[^ch]/', '/Apr[^il]/', '/May/',
            '/Jun[^e]/', '/Jul[^y]/', '/Aug[^ust]/', '/Sep[^tember]/', '/Oct[^ober]/',
            '/Nov[^ember]/', '/Dec[^ember]/', '/January/', '/February/', '/March/',
            '/April/', '/June/', '/July/', '/August/', '/September/', '/October/',
            '/November/', '/December/',
        );
        $replace = array(
            'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min',
            'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jum\'at', 'Sabtu', 'Minggu',
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des',
            'Januari', 'Februari', 'Maret', 'April', 'Juni', 'Juli', 'Agustus', 'September',
            'Oktober', 'November', 'Desember',
        );
        $date = date($date_format, $timestamp);
        $date = preg_replace($pattern, $replace, $date);
        $date = "{$date}";
        return $date;
    }

    public static function addLeadingZeros($input)
    {
        $number = substr($input, 1);
        $leadingZeros = '';

        if (strlen($number) === 1) {
            $leadingZeros = '0';
        }

        return substr($input, 0, 1) . $leadingZeros . $number;
    }
}