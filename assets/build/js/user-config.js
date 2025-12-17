$ = jQuery.noConflict();

function setJSONString() {
    var Obj = {};
    $("#wksap-user-setting-table")
      .find("td")
      .each(function () {
        $(this)
          .find("input, select")
          .each(function () {
            var val = "";
            if ($(this).attr("type") == "checkbox") {
              val = $(this).is(":checked");
            } else {
              val = $(this).val();
            }
            Obj[$(this).attr("data-name")] = val;
          });
      });
    var sum = Obj;
    $("#wksap_user_data_json").val(JSON.stringify(sum));
    return true;
  }
