jQuery(document).ready(function ($) {
  let dateFormat = "dd-mm-yy";
  let startDate = $("#nw_campaign_start_date");
  let endDate = $("#nw_campaign_end_date");

  // Enable Datepicker for campaign start date
  startDate
    .datepicker({
      defaultDate: "+1w",
      dateFormat: dateFormat,
      changeMonth: true,
    })
    .on("change", function () {
      endDate.datepicker("option", "minDate", getDate(this));
    });

  // Enable Datepicker for campaign end date
  endDate
    .datepicker({
      defaultDate: "+2w",
      dateFormat: dateFormat,
      changeMonth: true,
    })
    .on("change", function () {
      startDate.datepicker("option", "maxDate", getDate(this));
    });

  // Format the date from element value
  function getDate(element) {
    let date;
    try {
      date = $.datepicker.parseDate(dateFormat, element.value);
    } catch (error) {
      date = null;
    }
    return date;
  }
});
