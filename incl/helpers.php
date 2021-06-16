<?php

function get_formated_date($obj) {

    $day = sprintf('%02d', intval($obj->date->day));
    $month = sprintf('%02d', intval($obj->date->month));
    $year = intval($obj->date->year);

    $date_string = $year . '-' . $month . '-' . $day;

    return $date_string;
}

function get_time_by_month_name($name, $year) {
    $month = "";
    switch($name) {
        case 'January':
            $month = "01";
            break;
        case 'February':
            $month = "02";
            break;
        case 'March':
            $month = "03";
            break;
        case 'April':
            $month = "04";
            break;
        case 'May':
            $month = "05";
            break;
        case 'June':
            $month = "06";
            break;
        case 'July':
            $month = "07";
            break;
        case 'August':
            $month = "08";
            break;
        case 'September':
            $month = "09";
            break;
        case 'October':
            $month = "10";
            break;
        case 'November':
            $month = "11";
            break;
        case 'December':
            $month = "12";
            break;
    }
    $time_str = strtotime($year . "-" . $month . "-01");
    return $time_str;
}

?>