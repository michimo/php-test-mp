<?php

    require_once 'incl/helpers.php';
    require_once 'incl/functions.php';

    session_start();

    if ( !isset($_SESSION['id']) && empty($_SESSION['id']) ) {
        $_SESSION['id'] = uniqid();
    }

    if ( isset($_POST['get_holidays']) ) {

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

            $filename = $_SESSION['id'] . "/" . $_POST['year'] . "-" . $_POST['country'] . ".txt";

            if ( file_exists($filename) ) {

                $from_data_store = file_get_contents($filename);
                exit($from_data_store);
            }

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

            $public_holidays = array_filter( $holidays_array, 'public_holidays' );
            usort($public_holidays, "sort_by_date");

            $public_holidays_count = count($public_holidays);

            $grouped_public_holidays = group_by_month($public_holidays);
            $grouped_day_obj = group_day_obj_by_month($public_holidays);

            $free_days_in_row_count = free_day_row_count($holidays_array, $_POST['year']);

            $today_holiday = array_filter( $public_holidays, 'is_today_holiday' );

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

            if ( !file_exists($_SESSION['id']) ) {
                mkdir($_SESSION['id'], 0777, true);
            }

            if ( !file_exists($filename) ) {
                file_put_contents($filename, json_encode($contents_array));
            }

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
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <title>Mediapark PHP Assignment</title>

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    </head>
    <body>
        <section class="container">

            <h1>Mediapark PHP Assignment</h1>

            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="year-input">Year</label>
                    <input id="year-input" type="number" class="form-control" placeholder="Year" min="2011" step="1" name="year" value="<?php echo date("Y"); ?>">
                </div>

                <?php
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,
                        "https://kayaposoft.com/enrico/json/v2.0/?action=getSupportedCountries"
                    );
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            
                    $countries = (Object) json_decode(curl_exec($ch));
                    curl_close($ch);
                ?>

                <div class="form-group">
                    <label for="country-select">Country</label>
                    <select id="country-select" class="form-control" name="country">
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
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>

            <div class="results"></div>

        </section>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
        <script src="js/script.js"></script>

    </body>
</html>