$(document).ready(function () {

    $("button").click(function(e) {
        e.preventDefault();

        $(".results").fadeOut(0);

        if ( $("select").val().length == 0 ) {
            alert("Please select a country!");
        } else if ( $("input").val().length == 0 ) {
            alert("Please enter a year!");
        } else {

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

        }

    });
});