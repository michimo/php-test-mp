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
                $name = $value->text . " (" . date('jS, l', $time) .  ")";
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
        foreach ( $grouped_extra_workdays[$month_name] as $workday ) {
            if ( $status ) {
                $workday_date = strtotime(get_formated_date($workday));
                if ( $time == $workday_date ) {
                    $status = false;
                    break;
                }
            }
        }
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

        $day_of_month = get_time_by_month_name($month_name, $year); // first day of month
        $days_in_month = date("t", $day_of_month);

        $free_day_group = array();
        for ($i = 0; $i < $days_in_month; $i++) { // go through each day of month
            
            $prev_day = strtotime('-1 day', $day_of_month);
            $next_day = strtotime('+1 day', $day_of_month);

            $is_public_holiday = is_public_holiday($day_of_month, $holiday_group);
            $is_weekend = is_weekend($month_name, $day_of_month, $grouped_extra_working_days); // extra working days are removed and don't count as weekend

            if ( $is_public_holiday || $is_weekend ) {

                if ( empty($free_day_group) ) {
                    $free_day_group[] = $day_of_month;
                } else {
                    $prev_free_day = $free_day_group[count($free_day_group)-1];
                    // check if days are going in sequence
                    if ( $prev_day == $prev_free_day ) {
                        $free_day_group[] = $day_of_month;
                    } else {
                        if ( $free_days_in_row < count($free_day_group) ) {
                            $free_days_in_row = count($free_day_group);
                        }
                        $free_day_group = array(); // restart the free day array
                        $free_day_group[] = $day_of_month;
                    }
                }

            }

            $day_of_month = $next_day;
        }

        // if we have reached end of month and the free day array still has elements then check it
        if ( !empty($free_day_group) ) {
            if ( $free_days_in_row < count($free_day_group) ) {
                $free_days_in_row = count($free_day_group);
            }
        }
    }

    return $free_days_in_row;
}

?>