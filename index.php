<?php

    require_once 'helpers.php';
    require_once 'functions.php';

    // session_start();

    // if ( !isset($_SESSION['id']) && empty($_SESSION['id']) ) {
    //     $_SESSION['id'] = uniqid();
    // }

    /* DONT NEED */
    // if ( isset($_POST['get_countries']) ) {

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL,
    //         "https://kayaposoft.com/enrico/json/v2.0/?action=getSupportedCountries"
    //     );
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //     $countries = (Object) json_decode(curl_exec($ch));
    //     curl_close($ch);

    //     exit(json_encode($countries, JSON_PRETTY_PRINT));
    // }
    /* DONT NEED END */

    if ( isset($_POST['get_holidays']) ) {

        // function get_formated_date($obj) {

        //     $day = sprintf('%02d', intval($obj->date->day));
        //     $month = sprintf('%02d', intval($obj->date->month));
        //     $year = intval($obj->date->year);

        //     $date_string = $year . '-' . $month . '-' . $day;

        //     return $date_string;
        // }

        $return = array();

        $public_holidays_count = 0;
        $grouped_public_holidays = array();
        $is_today_workday = '';
        $today_holiday = array();
        $weekday_of_today = '';

        if ( (isset($_POST['country']) && !empty($_POST['country']))
             && (isset($_POST['year']) && !empty($_POST['year'])) ) {

            if ( intval($_POST['year']) < 2011 ) {
                exit(json_encode("small", JSON_PRETTY_PRINT));
            }

            if ( intval($_POST['year']) > 10000 ) {
                exit(json_encode("large", JSON_PRETTY_PRINT));
            }

            // $filename = $_SESSION['id'] . "/" . $_POST['year'] . "-" . $_POST['country'] . ".txt";

            // if ( file_exists($filename) ) {

            //     $from_data_store = file_get_contents($filename);
            //     exit($from_data_store);
            // }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,
                "https://kayaposoft.com/enrico/json/v2.0/?action=getHolidaysForYear&year=" . $_POST['year'] . "&country=" . $_POST['country']
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
            $holidays = (Object) json_decode(curl_exec($ch));
            curl_close($ch);

            $holidays_array = array();
            foreach ($holidays as $holiday) {
                $holidays_array[] = $holiday;
            }

            // function public_holidays($obj) {
            //     if ( $obj->holidayType != 'public_holiday' ) {
            //         return false;
            //     }
            //     return true;
            // }

            // function extra_working_days($obj) {
            //     if ( $obj->holidayType != 'extra_working_day' ) {
            //         return false;
            //     }
            //     return true;
            // }

            $public_holidays = array_filter( $holidays_array, 'public_holidays' );

            // function sort_by_date($a, $b) {

            //     $a_date = get_formated_date($a);
            //     $b_date = get_formated_date($b);

            //     return strtotime($a_date) - strtotime($b_date);
            // }
            usort($public_holidays, "sort_by_date");

            $public_holidays_count = count($public_holidays);

            // function group_by_month($holidays) {

            //     $grouped_by_month = array();
            //     foreach ($holidays as $holiday) {
            //         $formated_date = get_formated_date($holiday);
            //         $time = strtotime($formated_date);
            //         $month = date('F', $time);

            //         $name = '';
            //         foreach ($holiday->name as $key => $value) {
            //             if ( $value->lang == "en" ) {
            //                 $name = $value->text . '(' . date('jS, l', $time) .  ')';
            //                 break;
            //             }
            //         }

            //         $grouped_by_month[$month][] = !empty($name) ? $name : '';
            //     }
            //     return $grouped_by_month;
            // }

            $grouped_public_holidays = group_by_month($public_holidays);

            // function group_day_obj_by_month($holidays) {
            //     $grouped_by_month = array();
            //     foreach ($holidays as $holiday) {
            //         $formated_date = get_formated_date($holiday);
            //         $time = strtotime($formated_date);
            //         $month = date('F', $time);
            //         $grouped_by_month[$month][] = $holiday;
            //     }
            //     return $grouped_by_month;
            // }

            $grouped_day_obj = group_day_obj_by_month($public_holidays);

            // function free_day_row_count($grouped) {
            // function free_day_row_count($all_holidays) {

            //     $holidays_filtered = array_filter( $all_holidays, 'public_holidays' );
            //     usort($holidays_filtered, "sort_by_date");
            //     $grouped_holidays = group_day_obj_by_month($holidays_filtered);

            //     $extra_working_days_filtered = array_filter( $all_holidays, 'extra_working_days' );
            //     usort($extra_working_days_filtered, "sort_by_date");
            //     $grouped_extra_working_days = group_day_obj_by_month($extra_working_days_filtered);

            //     $free_days_in_row = 0;

            //     foreach ($grouped_holidays as $month_name => $holiday_group) {
            //         $row_count = 0;
            //         $pointer = new stdClass();
            //         foreach ($holiday_group as $single_holiday) {

            //             $holiday_date = strtotime(get_formated_date($single_holiday));
            //             // $weekday_of_holiday = $single_holiday->date->dayOfWeek; 

            //             if ( empty($pointer) ) {
            //                 // if ( $single_holiday->holidayType != 'extra_working_day' ) {
            //                     // $pointer = array(
            //                     //     'holiday' => $holiday_date,
            //                     //     'weekday' => $single_holiday->date->dayOfWeek,
            //                     // );
            //                     $pointer = $single_holiday;
            //                     $row_count += 1;
            //                 // }

            //                 if ( $single_holiday->date->dayOfWeek == 7 ) {
            //                     $row_count += 1;

            //                     $prev_date = strtotime('-1 day', $holiday_date);

            //                     // (check for extra working days)
            //                     if ( !empty($grouped_extra_working_days) && isset($grouped_extra_working_days[$month_name]) ) {
            //                         foreach ($grouped_extra_working_days[$month_name] as $single_workday) {
            //                             // foreach ($workday_group as $single_workday) {
            //                                 $workday_date = strtotime(get_formated_date($single_workday));
            //                                 if ( $workday_date == $prev_date ) {
            //                                     $row_count -= 1;
            //                                     break;
            //                                 }
            //                             // }
            //                         }
            //                     }
            //                     // else {
            //                     //     $row_count += 1;
            //                     // }
            //                 } else if ( $single_holiday->date->dayOfWeek == 1 ) {
            //                     $row_count += 2;

            //                     if ( !empty($grouped_extra_working_days) && isset($grouped_extra_working_days[$month_name]) ) {
            //                         for ($i=1; $i < 3; $i++) { 
            //                             $prev_date = strtotime('-'.$i.' day', $holiday_date);

            //                             foreach ($grouped_extra_working_days[$month_name] as $single_workday) {

            //                                     $workday_date = strtotime(get_formated_date($single_workday));
            //                                     if ( $workday_date == $prev_date ) {
            //                                         $row_count -= 1;
            //                                         break;
            //                                     }

            //                             }

            //                         }
            //                     }
            //                 }

            //             } else {

            //                 $next_date = strtotime('+1 day', $holiday_date);

            //                 if ( $holiday_date == $next_date ) {
            //                     // $pointer = array(
            //                     //     'date' => $holiday_date,
            //                     //     'weekday' => $single_holiday->date->dayOfWeek,
            //                     // );
            //                     $pointer = $single_holiday;
            //                     $row_count += 1;
            //                 } else {



            //                     // $pointer = array(
            //                     //     'date' => $holiday_date,
            //                     //     'weekday' => $single_holiday->date->dayOfWeek,
            //                     // );
            //                     $pointer = $single_holiday;
            //                     $row_count = 1;

            //                     // jānočeko vai sestdiena/svētdiena
            //                     // jānočeko vai extra_working_day (jānočeko tikai kad čeko sestd vai sv)
            //                 }
            //             }
            //         }

            //         if ( $row_count > $free_days_in_row ) {
            //             $free_days_in_row = $row_count;
            //         }
            //     }

            //     return $free_days_in_row;
            // }

            // $free_days_in_row_count = free_day_row_count($grouped_day_obj);
            // $free_days_in_row_count = $grouped_day_obj;
            $free_days_in_row_count = free_day_row_count($holidays_array, $_POST['year']);

            // function is_today_holiday($obj) {

            //     $today = date('Y-m-d');

            //     $date = get_formated_date($obj);

            //     if ( $today == $date ) {
            //         return true;
            //     } else {
            //         return false;
            //     }
            // }

            $today_holiday = array_filter( $public_holidays, 'is_today_holiday' );

            // function is_workday($country_code) {

            //     $today = date('d-m-Y');
    
            //     $ch = curl_init();
            //     curl_setopt($ch, CURLOPT_URL,
            //         "https://kayaposoft.com/enrico/json/v2.0/?action=isWorkDay&date=" . $today . "&country=" . $country_code
            //     );
            //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
            //     $result = (Object) json_decode(curl_exec($ch));
            //     curl_close($ch);
    
            //     return $result->isWorkDay;
            // }
            $is_today_workday = is_workday($_POST['country']);

            $weekday_of_today = date('l');

            /* ******** */

            $contents_array = array(
                'public_holidays' => !empty($grouped_public_holidays) ? $grouped_public_holidays : '',
                'public_holidays_count' => !empty($public_holidays_count) ? $public_holidays_count : '',
                'is_today_workday' => !empty($is_today_workday) ? $is_today_workday : false,
                'is_today_holiday' => !empty($today_holiday) ? $today_holiday : false,
                'weekday_of_today' => !empty($weekday_of_today) ? $weekday_of_today : '',
                'free_days_in_row_count' => !empty($free_days_in_row_count) ? $free_days_in_row_count : 0,
            );

            /* ******** */

            // if ( !file_exists($_SESSION['id']) ) {
            //     mkdir($_SESSION['id'], 0777, true);
            // }

            // if ( !file_exists($filename) ) {
            //     file_put_contents($filename, json_encode($contents_array));
            // }

        }

        $return['public_holidays'] = !empty($grouped_public_holidays) ? $grouped_public_holidays : '';
        $return['public_holidays_count'] = !empty($public_holidays_count) ? $public_holidays_count : '';
        $return['is_today_workday'] = !empty($is_today_workday) ? $is_today_workday : false;
        $return['is_today_holiday'] = !empty($today_holiday) ? $today_holiday : false;
        $return['weekday_of_today'] = !empty($weekday_of_today) ? $weekday_of_today : '';
        $return['free_days_in_row_count'] = !empty($free_days_in_row_count) ? $free_days_in_row_count : 0;

        exit(json_encode($return, JSON_PRETTY_PRINT));
    }

?>

<!doctype html>
<html>
    <head></head>
    <body>
        <h1>PHP Test MP</h1>

        <form method="post" enctype="multipart/form-data">
            <label for="year-input">Year</label>
            <input id="year-input" type="number" min="2011" step="1" name="year" />

            <?php
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,
                    "https://kayaposoft.com/enrico/json/v2.0/?action=getSupportedCountries"
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
                $countries = (Object) json_decode(curl_exec($ch));
                curl_close($ch);
            ?>

            <label for="country-select">Country</label>
            <select id="country-select" name="country">
                <?php
                    $index = 0;
                    foreach ($countries as $key => $country) {
                        if ( $index == 0 ) {
                ?>
                            <option value="">Select</option>    
                <?php
                        }
                ?>
                        <option value="<?php echo $country->countryCode; ?>"><?php echo $country->fullName; ?></option>
                <?php
                    $index++; }
                ?>
            </select>

            <button type="submit">Search</button>
        </form>

        <div class="results"></div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

        <script type="text/javascript">

            $(document).ready(function () {

                $("button").click(function(e) {
                    e.preventDefault();

                    $(".results").fadeOut(0);

                    $.ajax({
                        type: "POST",
                        url: "index.php",
                        data: {
                            "get_holidays": true,
                            "country": $("select").val(),
                            "year": $("input").val()
                        },
                        success: function (resp) {

                            var data = JSON.parse(resp);

                            console.log(data);

                            if ( data == "small" ) {
                                alert("Too small number for year!");
                            }

                            if ( data == "large" ) {
                                alert("Too large number for year!");
                            }

                            $(".results").empty();

                            $(".results").append("<hr/><p>Holiday count: " + data.public_holidays_count + "</p>");

                            var today = data.is_today_holiday ? 'Holiday' : (data.is_today_workday ? 'Workday' : data.weekday_of_today)
                            $(".results").append("<p>Today: " + today + "</p>");

                            $(".results").append("<p>Maximum number of free days (incl. holidays + weekend) in a row: " + data.free_days_in_row_count + "</p><hr/>");

                            if ( data.public_holidays.length == 0 ) {

                                $(".results").append("There are no public holidays for this country!");

                            } else {

                                $(".results").append("<ul></ul>");

                                var holidayItem = "";
                                $.each(data.public_holidays, function (key, holiday) {
                                    var days = "";
                                    $.each(holiday, function (subkey, holidayName) {
                                        days += "<li>" + holidayName + "</li>";
                                    });
                                    holidayItem += "<li>" + key + "<ul>" + days + "</ul></li>";
                                });
                                $(".results > ul").append(holidayItem);
                            }

                            $(".results").fadeIn(400);

                        },
                    });

                });
            });

        </script>
    </body>
</html>