$(document).on('o:prepare-value', function(e, type, value) {
    if ('numeric:timestamp' === type) {
        var v = value.find('input[data-value-key="@value"]');
        var y = value.find('input[name="numeric-timestamp-year"]');
        var m = value.find('select[name="numeric-timestamp-month"]');
        var d = value.find('select[name="numeric-timestamp-day"]');

        // Set existing year, month, and day during initial load.
        if (v.val()) {
            var date = new Date(v.val() * 1000); // convert s to ms
            y.val(date.getFullYear());
            m.val(date.getMonth());
            d.val(date.getDate());
        }

        y.on('input', function(e) {
            setTimestamp(v, y, m, d);
        });
        m.on('change', function(e) {
            setTimestamp(v, y, m, d);
        });
        d.on('change', function(e) {
            setTimestamp(v, y, m, d);
        });
    }
});

/**
 * Set a timestamp to a value.
 *
 * We store timestamp and not ISO 8601 because the former is a signed integer
 * and thus better suited for simple database comparisons.
 *
 * Note that the Date object range is -100,000,000 days to 100,000,000 days
 * relative to 01 January, 1970 UTC.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Numbers_and_dates#Date_object
 * @param v Value input
 * @param y Year input
 * @param m Month select 
 * @param d Day select 
 */
var setTimestamp = function (v, y, m, d) {
    var year = y.val() ? y.val() : null;
    if (year) {
        var month = m.val() ? m.val() : 0;
        var day = d.val() ? d.val() : 1;
        var timestamp = new Date(year, month, day, 0, 0, 0).getTime();
        v.val(timestamp ? timestamp * .001: null); // convert ms to s
    } else {
        // Date() recognizes a null year, but we don't.
        v.val(null);
    }
}
