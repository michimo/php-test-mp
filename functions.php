<?php

function public_holidays($obj) {
    if ( $obj->holidayType != 'public_holiday' ) {
        return false;
    }
    return true;
}

function extra_working_days($obj) {
    if ( $obj->holidayType != 'extra_working_day' ) {
        return false;
    }
    return true;
}

function sort_by_date($a, $b) {

    $a_date = get_formated_date($a);
    $b_date = get_formated_date($b);

    return strtotime($a_date) - strtotime($b_date);
}

function group_by_month($holidays) {

    $grouped_by_month = array();
    foreach ($holidays as $holiday) {
        $formated_date = get_formated_date($holiday);
        $time = strtotime($formated_date);
        $month = date('F', $time);

        $name = '';
        foreach ($holiday->name as $key => $value) {
            if ( $value->lang == "en" ) {
                $name = $value->text . '(' . date('jS, l', $time) .  ')';
                break;
            }
        }

        $grouped_by_month[$month][] = !empty($name) ? $name : '';
    }
    return $grouped_by_month;
}

function group_day_obj_by_month($holidays) {
    $grouped_by_month = array();
    foreach ($holidays as $holiday) {
        $formated_date = get_formated_date($holiday);
        $time = strtotime($formated_date);
        $month = date('F', $time);
        $grouped_by_month[$month][] = $holiday;
    }
    return $grouped_by_month;
}

function is_today_holiday($obj) {

    $today = date('Y-m-d');

    $date = get_formated_date($obj);

    if ( $today == $date ) {
        return true;
    } else {
        return false;
    }
}

function is_workday($country_code) {

    $today = date('d-m-Y');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,
        "https://kayaposoft.com/enrico/json/v2.0/?action=isWorkDay&date=" . $today . "&country=" . $country_code
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = (Object) json_decode(curl_exec($ch));
    curl_close($ch);

    return $result->isWorkDay;
}

function is_public_holiday($time, $holiday_array) {
    $status = false;

    foreach ( $holiday_array as $holiday ) {
        $holiday_date = strtotime(get_formated_date($holiday));
        if ( $time == $holiday_date ) {
            $status = true;
            break;
        }
    }

    return $status;
}

function is_weekend($month_name, $time, $grouped_extra_workdays) {
    $status = false;

    $weekday = date('w', $time);

    if ( $weekday == 6 || $weekday == 0 ) {
        $status = true;
    }

    if ( !empty($grouped_extra_workdays) && isset($grouped_extra_workdays[$month_name]) ) {
        // foreach ($grouped_extra_workdays as $month_name => $extra_workday_group) {
            foreach ( $grouped_extra_workdays[$month_name] as $workday ) {
                if ( $status ) {
                    $workday_date = strtotime(get_formated_date($workday));
                    if ( $time == $workday_date ) {
                        $status = false;
                        break;
                    }
                }
            }
        // }
    }

    return $status;
}

function free_day_row_count($all_holidays, $year) {

    $holidays_filtered = array_filter( $all_holidays, 'public_holidays' );
    usort($holidays_filtered, "sort_by_date");
    $grouped_holidays = group_day_obj_by_month($holidays_filtered);

    $extra_working_days_filtered = array_filter( $all_holidays, 'extra_working_days' );
    usort($extra_working_days_filtered, "sort_by_date");
    $grouped_extra_working_days = group_day_obj_by_month($extra_working_days_filtered);

    $free_days_in_row = 0;
    foreach ($grouped_holidays as $month_name => $holiday_group) {

        $day_of_month = get_time_by_month_name($month_name, $year); // first day
        $days_in_month = date("t", $day_of_month);

        // print_r($month_name . ": " . $days_in_month . "<br>");

        $free_day_group = array();
        for ($i = 0; $i < $days_in_month; $i++) {

            // print_r($i . "<br>");
            
            $prev_day = strtotime('-1 day', $day_of_month);
            $next_day = strtotime('+1 day', $day_of_month);

            $is_public_holiday = is_public_holiday($day_of_month, $holiday_group);
            $is_weekend = is_weekend($month_name, $day_of_month, $grouped_extra_working_days);

            // print_r("day: " . date('F jS, l', $day_of_month)."<br>");

            if ( $is_public_holiday || $is_weekend ) {

                // print_r("is: " . date('F jS, l', $day_of_month)."<br>");

                if ( empty($free_day_group) ) {
                    $free_day_group[] = $day_of_month;
                    // print_r("empty: " . date('F jS, l', $day_of_month)."<br>");
                } else {
                    $prev_free_day = $free_day_group[count($free_day_group)-1];
                    if ( $prev_day == $prev_free_day ) {
                        $free_day_group[] = $day_of_month;
                        // print_r("prev_day: " . date('F jS, l', $day_of_month)."<br>");
                    } else {
                        // $free_day_array[$month_name] = $free_day_group;
                        // $free_day_group = array();
                        // if ( $free_days_in_row == 0 ) {
                        //     $free_days_in_row = count($free_day_group);
                        // } else

                        // print_r($month_name . "<br>");
                        //     print_r($free_day_group);
                        //     print_r("<br>");

                        if ( $free_days_in_row < count($free_day_group) ) {

                            // print_r($month_name . "<br>");
                            // print_r($free_day_group);
                            // print_r("<br>");

                            $free_days_in_row = count($free_day_group);
                        }
                        // print_r(date('F jS, l', $day_of_month)."<br>");
                        // print_r("---<br>");
                        $free_day_group = array();
                        $free_day_group[] = $day_of_month;
                        // print_r("start: " . date('F jS, l', $day_of_month)."<br>");
                    }
                }

            }

            $day_of_month = $next_day;
        }

        if ( !empty($free_day_group) ) {
            if ( $free_days_in_row < count($free_day_group) ) {

                // print_r($month_name . "<br>");
                // print_r($free_day_group);
                // print_r("<br>");

                $free_days_in_row = count($free_day_group);
            }
        }

        // print_r("---<br>");
        // print_r("---<br>");

        // break;

        // $row_count = 0;
        // $pointer = new stdClass();
        // foreach ($holiday_group as $single_holiday) {

        //     $holiday_date = strtotime(get_formated_date($single_holiday));
        //     // $weekday_of_holiday = $single_holiday->date->dayOfWeek; 

        //     if ( empty($pointer) ) {
        //         // if ( $single_holiday->holidayType != 'extra_working_day' ) {
        //             // $pointer = array(
        //             //     'holiday' => $holiday_date,
        //             //     'weekday' => $single_holiday->date->dayOfWeek,
        //             // );
        //             $pointer = $single_holiday;
        //             $row_count += 1;
        //         // }

        //         if ( $single_holiday->date->dayOfWeek == 7 ) {
        //             $row_count += 1;

        //             $prev_date = strtotime('-1 day', $holiday_date);

        //             // (check for extra working days)
        //             if ( !empty($grouped_extra_working_days) && isset($grouped_extra_working_days[$month_name]) ) {
        //                 foreach ($grouped_extra_working_days[$month_name] as $single_workday) {
        //                     // foreach ($workday_group as $single_workday) {
        //                         $workday_date = strtotime(get_formated_date($single_workday));
        //                         if ( $workday_date == $prev_date ) {
        //                             $row_count -= 1;
        //                             break;
        //                         }
        //                     // }
        //                 }
        //             }
        //             // else {
        //             //     $row_count += 1;
        //             // }
        //         } else if ( $single_holiday->date->dayOfWeek == 1 ) {
        //             $row_count += 2;

        //             if ( !empty($grouped_extra_working_days) && isset($grouped_extra_working_days[$month_name]) ) {
        //                 for ($i=1; $i < 3; $i++) { 
        //                     $prev_date = strtotime('-'.$i.' day', $holiday_date);

        //                     foreach ($grouped_extra_working_days[$month_name] as $single_workday) {

        //                             $workday_date = strtotime(get_formated_date($single_workday));
        //                             if ( $workday_date == $prev_date ) {
        //                                 $row_count -= 1;
        //                                 break;
        //                             }

        //                     }

        //                 }
        //             }
        //         }

        //     } else {

        //         $next_date = strtotime('+1 day', $holiday_date);

        //         if ( $holiday_date == $next_date ) {
        //             // $pointer = array(
        //             //     'date' => $holiday_date,
        //             //     'weekday' => $single_holiday->date->dayOfWeek,
        //             // );
        //             $pointer = $single_holiday;
        //             $row_count += 1;
        //         } else {



        //             // $pointer = array(
        //             //     'date' => $holiday_date,
        //             //     'weekday' => $single_holiday->date->dayOfWeek,
        //             // );
        //             $pointer = $single_holiday;
        //             $row_count = 1;

        //             // jānočeko vai sestdiena/svētdiena
        //             // jānočeko vai extra_working_day (jānočeko tikai kad čeko sestd vai sv)
        //         }
        //     }
        // }

        // if ( $row_count > $free_days_in_row ) {
        //     $free_days_in_row = $row_count;
        // }
    }

    return $free_days_in_row;
}

?>