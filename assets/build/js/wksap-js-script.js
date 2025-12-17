$ = jQuery.noConflict();
jQuery(function ($) {
      window.stop_clock = "yes";
      window.formSubmitting = true;
      window.sapConnection = "";
      window.locator = "";
      window.counter_block_id = "";
      window.disableuser = false;
      window.allowAlert =
      wksap_ajax_object.translation.import_duplicates;
      window.item_ids = new Array();
      window.sync_all = "";
      window.onload = function () {
        window.addEventListener("beforeunload", function (e) {
          if (window.formSubmitting) {
            return undefined;
          }
          var confirmationMessage =
          wksap_ajax_object.translation.confirmationMessage +
          wksap_ajax_object.translation.confirmationMessage2;
          (e || window.event).returnValue = confirmationMessage;
          return confirmationMessage;
        });
      };

      $(".wksap-cm-confirm").on("click", function (e) {
        var result = confirm( wksap_ajax_object.translation.want_continue );
        if (!result) {
          return false;
        }
      });
      /* User sync code */

    $(".wksap-resync-user").on("click", function () {
        if (!wksap_ajax_object?.wksap_enable) {
            Swal.fire({
                title: wksap_ajax_object.translation.error,
                text: wksap_ajax_object.translation.enable_plugin,
                icon: 'error',
                showConfirmButton: true,
                timer: 3000
            });
            return;
        }
        if (window.disableuser == false){
            var user_id = $(this).prop("id");
            user_id = user_id.replace("resync_", "");
            real_user_id = $("#wksap_real_pid_" + user_id).html();
            Swal.fire({
              title: wksap_ajax_object.translation.are_you_sure,
              text: wksap_ajax_object.translation.you_want_sync,
              icon: "warning",
              showCancelButton: true,
              confirmButtonColor: "#2ea2cc",
              cancelButtonColor: "#d33",
              confirmButtonText: wksap_ajax_object.translation.sync_it,
            }).then((result) => {
              if (result.isConfirmed) {
                window.user_item_ids = new Array();
                window.user_item_ids.push(user_id);
                msgBlock = getProcessBlock("clock_block_export_user");
                wkSAPmyAdminNoticesAppend(
                  "updated",
                  wksap_ajax_object.translation.export_start,
                  msgBlock
                );
                window.stop_clock = "no";
                timer_clock1("clock_block_export_user");
                if (window.user_item_ids.length > 0) {
                  window.disableuser = true;
                  wksap_woo_export_users(
                    window.user_item_ids.length,
                    window.user_item_ids.length,
                    1,
                    0,
                    0,
                    0,
                    0,
                    0
                  );
                }
              }
            });
        } else {
            window.disableuser = false;
            sweet_alert( wksap_ajax_object.translation.cannot_start, wksap_ajax_object.translation.another_is_going);
          }

    	});

      /* Working on background user sync */
    $("#wksap_syncronize_user_button").on("click", function () {
        if (!wksap_ajax_object?.wksap_enable) {
            Swal.fire({
                title: wksap_ajax_object.translation.error,
                text: wksap_ajax_object.translation.enable_plugin,
                icon: 'error',
                showConfirmButton: true,
                timer: 3000
            });
            return;
        }
        if (window.disableuser == false) {
          Swal.fire({
            text: wksap_ajax_object.translation.export_option,
            input: "select",
            padding: "1.6rem",
            inputOptions: {
              created: wksap_ajax_object.translation.date_created,
              all: wksap_ajax_object.translation.export_all,
            },
            inputPlaceholder: wksap_ajax_object.translation.export_basis,
            type: "warning",
            confirmButtonColor: " #2ea2cc",
            cancelButtonColor: "#d33",
            confirmButtonText: wksap_ajax_object.translation.next,
            showCancelButton: true,
            inputValidator: (value) => {
              return new Promise((resolve) => {
                if (value !== "") {
                  resolve();
                } else {
                  resolve(wksap_ajax_object.translation.select_option);
                }
              });
            },
          }).then((result) => {
            if (result.isConfirmed) {
              selectedoption = result.value;
              let title = selectedoption === "created"
                ? wksap_ajax_object.translation.order_create_between
                : wksap_ajax_object.translation.order_modified_between;
              if (selectedoption == "created" || selectedoption == "modified") {
                Swal.fire({
                  title: title,
                  width: 600,
                  html: ' <span class="mb-0" ><input class="form-control form-control-solid" placeholder="'+ wksap_ajax_object.translation.pick_date_range +'" id="kt_daterangepicker_2" autocomplete="off"/></span> &nbsp;And &nbsp;<span class="mb-0"><input class="form-control form-control-solid" placeholder="'+ wksap_ajax_object.translation.pick_date_range +'" id="kt_daterangepicker_3" autocomplete="off"/></span>',
                  showCancelButton: true,
                  showDenyButton: true,
                  denyButtonText: wksap_ajax_object.translation.back,
                  confirmButtonColor: "#2ea2cc",
                  denyButtonColor: "#6e7881",
                  cancelButtonColor: "#d33",
                  confirmButtonText: wksap_ajax_object.translation.confirm,
                  cancelButtonText: wksap_ajax_object.translation.cancel,
                  preConfirm: () => {
                    return [
                      $("#kt_daterangepicker_2").val(),
                      $("#kt_daterangepicker_3").val(),
                    ];
                  },

                  willOpen: function () {
                    $("#kt_daterangepicker_2").daterangepicker({
                      timePicker: true,
                      singleDatePicker: true,
                      showDropdowns: true,
                      minYear: 1901,
                      linkedCalendars: false,
                      timePickerSeconds: true,
                      locale: {
                        format: "YYYY/MM/DD hh:mm A",
                      },
                    });
                    $("#kt_daterangepicker_3").daterangepicker({
                      timePicker: true,
                      singleDatePicker: true,
                      showDropdowns: true,
                      minYear: 1901,
                      linkedCalendars: false,
                      timePickerSeconds: true,
                      locale: {
                        format: "YYYY/MM/DD hh:mm A",
                      },
                    });
                  },
                }).then((result) => {
                  if (result.isConfirmed) {
                    selected_date_range = result.value;
                    if (selected_date_range[0] <= selected_date_range[1]) {
                      if (window.disableuser == false) {
                        window.disableuser = true;
                        var synced_ticked = $("#wksap_elm_synced_items").prop("checked");
                        var unsynced_ticked = $("#wksap_elm_unsynced_items").prop(
                          "checked"
                        );
                        var error_ticked = $("#wksap_elm_error_items").prop("checked");

                        if (synced_ticked && unsynced_ticked) {
                          sync = "A";
                        } else if (synced_ticked) {
                          sync = "S";
                        } else if (unsynced_ticked) {
                          sync = "U";
                        } else if (error_ticked) {
                          sync = "E";
                        } else {
                          sync = "N";
                        }
                        wksap_export_object_with_background_job(
                          sync,
                          "user",
                          selected_date_range[0],
                          selected_date_range[1],
                          selectedoption
                        );
                      }
                    } else {
                      sweet_alert(
                        wksap_ajax_object.translation.invalid_date,
                        wksap_ajax_object.translation.before_date
                      );
                    }
                  } else if (result?.isDenied) {
                    $("#wksap_syncronize_user_button").trigger("click");
                  }
                });
              } else if (selectedoption == "all") {
                 if (window.disableuser == false) {
                  window.disableuser = true;
                  var synced_ticked = $("#wksap_elm_synced_items").prop("checked");
                  var unsynced_ticked = $("#wksap_elm_unsynced_items").prop("checked");
                  var error_ticked = $("#wksap_elm_error_items").prop("checked");

                  if (synced_ticked && unsynced_ticked) {
                    sync = "A";
                  } else if (synced_ticked) {
                    sync = "S";
                  } else if (unsynced_ticked) {
                    sync = "U";
                  } else if (error_ticked) {
                    sync = "E";
                  } else {
                    sync = "N";
                  }
                  wksap_export_object_with_background_jobs(sync, "user", start_date = "", end_date = "", option = "", url = "wksap_export_object_with_background_jobs");
                }
              }
            }
          });
        } else {
            window.disableuser = false;
          sweet_alert( wksap_ajax_object.translation.cannot_start, wksap_ajax_object.translation.another_is_going);
        }
      });

      /* end here */
      $("#wksap-sync-limit-check").on("click", function () {
        if ($(this).is(":checked")) {
          $("#woosap-sync-limit").prop("disabled", false);
        } else {
          $("#woosap-sync-limit").prop("disabled", true);
        }
      });
      // Delete Log File Ajax
      $("#wksap-deletelog").on("click", function () {
        if (
          confirm(
            wksap_ajax_object.translation.last_seven_days
          )
        ) {
          jQuery.ajax({
            type: "POST",
            url: wksap_ajax_object.wksap_ajax,
            data: {
              action: "wksap_delete_generated_log_files",
              wksap_nonce: wksap_ajax_object.wksap_nonce,
            },
            success: function (response) {
              window.location.search += "&action=logsdeleted";
            },
          });
        }
      });

      $(".target").click(function () {
        $.post(
          ajax_object.ajaxurl,
          {
            action: "ajax_action",
            post_id: $(this).find("input.post_id").attr("value"),
          },
          function (data) {
            alert(data); // alerts 'ajax submitted'
          }
        );
      });

      $(document).on("click", ".notice-dismiss", function () {
        if (window.stop_clock == "no") {
          if (
            confirm(
              wksap_ajax_object.translation.are_you_sure_delete_log_file
            )
          ) {
            $(this).parent().remove();
          }
        } else {
          $(this).parent().remove();
        }
      });

    $(document).on("click", "#doaction, #doaction2", function (event) {
        if (!wksap_ajax_object?.wksap_enable) {
            Swal.fire({
                title: wksap_ajax_object.translation.error,
                text: wksap_ajax_object.translation.enable_plugin,
                icon: 'error',
                showConfirmButton: true,
                timer: 3000
            });
            return false;
        }
        var selected_value_top = $("#bulk-action-selector-top").val();
        if ($(this).prop("id") == "doaction") {
          if (selected_value_top == "-1") {
            event.preventDefault();
            return false;
          }
          /* working on selected export user  */

          if (selected_value_top == "exportUsers") {
          event.preventDefault();
          if (window.stop_clock == "no") {
            Swal.fire({
              title: wksap_ajax_object.translation.are_you_sure,
              text: wksap_ajax_object.translation.are_you_sure_export_user,
              icon: "warning",
              showCancelButton: true,
              confirmButtonColor: "#28a745",
              cancelButtonColor: "#d33",
              confirmButtonText: wksap_ajax_object.translation.sync_it,
            }).then((result) => {
              if (result.isConfirmed) {
                window.user_item_ids = new Array();
                jQuery("input[name='mergeduser[]']:checkbox").each(
                  function () {
                    if (jQuery(this).prop("checked") == true) {
                      item_id = jQuery(this).val();
                      window.user_item_ids.push(item_id);
                    }
                  }
                );
                if (window.user_item_ids.length > 0) {
                  msgBlock = getProcessBlock("clock_block_export_user");
                  wkSAPmyAdminNoticesAppend(
                    "updated",
                    wksap_ajax_object.translation.export_start,
                    msgBlock
                  );
                  window.stop_clock = "no";
                  timer_clock1("clock_block_export_user");
                  wksap_woo_export_users(
                    window.user_item_ids.length,
                    window.user_item_ids.length,
                    1,
                    0,
                    0,
                    0,
                    0,
                    0
                  );
                } else {
                  sweet_alert(
                    wksap_ajax_object.translation.select_user,
                    wksap_ajax_object.translation.sap_partner
                  );
                }
              }
            });
          } else {
            window.user_item_ids = new Array();
            jQuery("input[name='mergeduser[]']:checkbox").each(function () {
              if (jQuery(this).prop("checked") == true) {
                item_id = jQuery(this).val();
                window.user_item_ids.push(item_id);
              }
            });
            if (window.user_item_ids.length > 0) {
              msgBlock = getProcessBlock("clock_block_export_user");
              wkSAPmyAdminNoticesAppend(
                "updated",
                wksap_ajax_object.translation.export_start,
                msgBlock
              );
              window.stop_clock = "no";
              timer_clock1("clock_block_export_user");
              wksap_woo_export_users(
                window.user_item_ids.length,
                window.user_item_ids.length,
                1,
                0,
                0,
                0,
                0,
                0
              );
            } else {
              sweet_alert(
                wksap_ajax_object.translation.select_user,
                wksap_ajax_object.translation.sap_partner
              );
            }
          }
        }
          /* selected user export end here */


           if ( selected_value_top == "delete-user" || selected_value_top == "delete-all-user" ) {
            event.preventDefault();
            wksap_delete_mapping_data(selected_value_top, "user");
          }
        }
      });

      $("#wksap-export-stop-user-button").on("click", function () {
        Swal.fire({
          title: wksap_ajax_object.translation.records_already,
          text: wksap_ajax_object.translation.records_already_stop,
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#d33",
          confirmButtonText: wksap_ajax_object.translation.stop_it,
        }).then((result) => {
          if (result.isConfirmed) {
            wksap_stop_background_job("user");
          }
        });
      });

        function getProcessBlock(clockBlockId) {
          msgBlock = '<span id="wksap_notice_block">';
          msgBlock += "<br>";
          msgBlock += '<span id="completed_b">0</span>% '+ wksap_ajax_object.translation.completed +' ';
          msgBlock += "<br>";
          msgBlock +=
            '<span id="current_option_going">'+ wksap_ajax_object.translation.fetch_logs +'</span>';
          msgBlock += "<hr>";
          msgBlock += wksap_ajax_object.translation.time_elapsed+' <span id="' + clockBlockId + '">00:00:00</span>';
          msgBlock += "<br>";
          msgBlock += '<span id="wpsap_end_notice"></span>';
          msgBlock += "</span>";
          return msgBlock;
        }


        function timer_clock1(block_id) {
          var count = 0;
          window.counter_block_id = setInterval(timer_clock, 1000);

          function timer_clock() {
            count = count + 1;
            if (window.stop_clock == "yes") {
              clearInterval(window.counter_block_id);
              return;
            }
            var seconds = count % 60;
            var minutes = Math.floor(count / 60);
            var hours = Math.floor(minutes / 60);
            minutes %= 60;
            hours %= 60;
            if (hours < 10) {
              hours = "0" + hours;
            }
            if (minutes < 10) {
              minutes = "0" + minutes;
            }
            if (seconds < 10) {
              seconds = "0" + seconds;
            }
            jQuery("#" + block_id).html(hours + ":" + minutes + ":" + seconds);
          }
        }

        function wkSAPmyAdminNoticesAppend(type, title, message) {
          var a =
            '<div class="' +
            type +
            ' notice is-dismissible" id="error_response">\
        		<p> <strong>' +
            title +
            " </strong> " +
            message +
            '</p>\
        		<button class="notice-dismiss" type="button">\
        			<span class="screen-reader-text">'+ wksap_ajax_object.translation.dismiss_notice+'</span>\
        		</button>\
        	</div>';
          jQuery("#wksap_my_wrap").prepend(a);
        }

    /* working on User Export */

    function wksap_woo_export_users(goal, totalCount, limit, offset, index, updatedValue, addedValue, errorsValue) {
        if (!wksap_ajax_object?.wksap_enable) {
            Swal.fire({
                title: wksap_ajax_object.translation.error,
                text: wksap_ajax_object.translation.enable_plugin,
                icon: 'error',
                showConfirmButton: true,
                timer: 3000
            });
            return;
        }
            let operationBlock = `
                <span id="current_option_going">
                    <b id="b_added">${addedValue}</b> added /
                    <b id="b_updated">${updatedValue}</b> updated /
                    <b id="b_errors">${errorsValue}</b> errors of
                    <b id="b_total_records">${goal}</b> records
                </span>`;

            jQuery("#current_option_going").html(operationBlock);

            if (window.user_item_ids.length > 0) {
                window.formSubmitting = false;
                index += 1;
                const user_id = window.user_item_ids[0];
                const data = {
                    action: "wksap_woo_export_users",
                    user_id: user_id,
                    wksap_nonce: wksap_ajax_object.wksap_nonce,
                };

                jQuery.ajax({
                    type: "POST",
                    url: wksap_ajax_object.wksap_ajax,
                    data: data,
                    beforeSend: function () {
                        jQuery("#synchronizeusers_id").addClass("is-active");
                        const wrapper = jQuery("#wksap_my_wrap");
                        if (!wrapper.hasClass("processing")) {
                            jQuery(".spinner").addClass("is-active");
                            wrapper.addClass("processing").css({
                                position: 'relative',
                                opacity: 0.6,
                                backgroundColor: '#fff'
                            });
                        }
                    },
                    success: function (output) {
                        try {
                            const obj = JSON.parse(output);
                            if (obj.error !== "") {
                                jQuery("#sync_status_icon_" + user_id).attr("title", obj.error);
                                jQuery("#sync_status_icon_" + user_id).css("color", "red");
                                jQuery("#sync_status_icon_" + user_id).removeClass("dashicons-yes");
                                jQuery("#sync_status_icon_" + user_id).addClass("dashicons-welcome-comments");
                            } else {
                                jQuery("#sync_status_icon_" + user_id).css("color", "green");
                                jQuery("#sync_status_icon_" + user_id).addClass("dashicons-yes");
                                jQuery("#sync_status_icon_" + user_id).removeClass("dashicons-welcome-comments");
                                jQuery("#sync_status_icon_" + user_id).attr("title", "");
                            }

                            if (obj.total) {
                                const cat_id = window.user_item_ids[0];
                                if (obj.updated) {
                                    jQuery("#add_sf_sync_time_" + cat_id).html(obj.syncc_time);
                                } else if (obj.added) {
                                    jQuery("#add_sap_user_id_" + cat_id).html(obj.sap_user_id);
                                    jQuery("#add_sf_sync_time_" + cat_id).html(obj.syncc_time);
                                }

                                totalCount = totalCount - limit;
                                offset = index * limit;
                                let percentage = (offset / goal) * 100;
                                if (percentage > 100) percentage = 100;
                                jQuery("#completed_b").text(parseInt(percentage));

                                updatedValue = obj.updated + parseInt(jQuery("#b_updated").text());
                                addedValue = obj.added + parseInt(jQuery("#b_added").text());
                                errorsValue = obj.errorsValue + parseInt(jQuery("#b_errors").text());

                                window.user_item_ids.shift();
                                wksap_woo_export_users(goal, totalCount, limit, offset, index, updatedValue, addedValue, errorsValue);
                            }
                        } catch (err) {
                            jQuery(".spinner").removeClass("is-active");
                            jQuery("#wksap_my_wrap").removeClass("processing").css({opacity: 1});
                            jQuery("#wpsap_end_notice").append("<mark class='incomplete tips' style='background-color:red;color:white;'>"+ wksap_ajax_object.translation.completed +"</mark>");
                            window.stop_clock = "yes";
                            wkSAPmyAdminNoticesAppend("error", wksap_ajax_object.translation.error_ex, output);
                        }
                    },
                    error: function (xhr) {
                        window.formSubmitting = true;
                        jQuery(".spinner").removeClass("is-active");
                        jQuery("#wksap_my_wrap").removeClass("processing").css({opacity: 1});
                        jQuery("#wpsap_end_notice").append("<mark class='incomplete tips' style='background-color:red;color:white;'>"+ wksap_ajax_object.translation.completed +"</mark>");
                        window.stop_clock = "yes";
                        wkSAPmyAdminNoticesAppend("error", wksap_ajax_object.translation.error_ex, xhr.responseText);
                    },
                    complete: function () {
                        if (window.user_item_ids.length === 0) {
                            jQuery(".spinner").removeClass("is-active");
                            jQuery("#wksap_my_wrap").removeClass("processing").css({opacity: 1});
                            window.formSubmitting = true;
                            jQuery("#synchronizeusers_id").removeClass("is-active");
                            jQuery("#wpsap_end_notice").append("<mark class='completed tips'>"+ wksap_ajax_object.translation.completed +"</mark>");
                            window.stop_clock = "yes";
                            window.disableuser = false;
                            clearInterval(window.counter_block_id);

                            if (window.sync_all === "yes") {
                                window.sync_all = "no";
                            }
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    },
                });
            } else {
                jQuery(".spinner").removeClass("is-active");
                jQuery("#wksap_my_wrap").removeClass("processing").css({opacity: 1});
                window.formSubmitting = true;
                jQuery("#synchronizeusers_id").removeClass("is-active");
                jQuery("#wpsap_end_notice").append("<mark class='completed tips'>"+ wksap_ajax_object.translation.completed +"</mark>");
                window.stop_clock = "yes";
                window.disableuser = false;
                clearInterval(window.counter_block_id);

                if (window.sync_all === "yes") {
                    window.sync_all = "no";
                }
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
      }
      /* end here */

        function wksap_export_object_with_background_jobs( sync, sObject, start_date = "", end_date = "", option = "", url = "wksap_export_object_with_background_job") {

            if (!wksap_ajax_object?.wksap_enable) {
                Swal.fire({
                    title: wksap_ajax_object.translation.error,
                    text: wksap_ajax_object.translation.enable_plugin,
                    icon: 'error',
                    showConfirmButton: true,
                    timer: 3000
                });
                return;
            }

          jQuery.ajax({
            type: "POST",
            url: wksap_ajax_object.wksap_ajax,
            data: {
              action: url,
              sync: sync,
              sObject: sObject,
              start_date: start_date,
              end_date: end_date,
              option: option,
              wksap_nonce: wksap_ajax_object.wksap_nonce,
            },
              beforeSend: function () {
                Swal.fire({
                    title: wksap_ajax_object.translation.finding,
                    text: wksap_ajax_object.translation.wait,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
              success: function (output) {
                var obj = jQuery.parseJSON(output);
                let errorMessages = '';

                Swal.fire({
                    title: wksap_ajax_object.translation.background_job,
                    html: `${obj.message}${errorMessages}`,
                    icon: obj.code,
                    showConfirmButton: true,
                    showCancelButton: false,
                }).then((result) => {
                    location.reload();
                });
            },
            error: function (xhr) {
              // if error occured
              wkSAPmyAdminNoticesAppend("error", wksap_ajax_object.translation.error_ex, xhr.responseText);
            },
          });
        }

        function wksap_export_object_with_background_job( sync, sObject, start_date = "", end_date = "", option = "", url = "wksap_export_object_with_background_job") {
          jQuery.ajax({
            type: "POST",
            url: wksap_ajax_object.wksap_ajax,
            data: {
              action: url,
              sync: sync,
              sObject: sObject,
              start_date: start_date,
              end_date: end_date,
              option: option,
              wksap_nonce: wksap_ajax_object.wksap_nonce,
            },
              beforeSend: function () {
                Swal.fire({
                    title: wksap_ajax_object.translation.finding,
                    text: wksap_ajax_object.translation.wait,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function (output) {
              var obj = jQuery.parseJSON(output);
              if (obj.code == "warning") {
                Swal.fire({
                  title: wksap_ajax_object.translation.background_job,
                  text: obj.message,
                  icon: obj.code,
                  timer: 2000,
                  showConfirmButton: false,
                  showCancelButton: false,
                  timer: 2000,
                });
              } else {
                Swal.fire({
                  title: wksap_ajax_object.translation.background_job,
                  text: obj.message,
                  icon: obj.code,
                  timer: 2000,
                  showConfirmButton: false,
                  showCancelButton: false,
                  timer: 2000,
                }).then((result) => {
                  location.reload();
                });
              }
            },
            error: function (xhr) {
              // if error occured
              wkSAPmyAdminNoticesAppend("error", wksap_ajax_object.translation.error_ex, xhr.responseText);
            },
          });
        }

        function wksap_stop_background_job(sObject) {
          jQuery.ajax({
            type: "POST",
            url: wksap_ajax_object.wksap_ajax,
            data: {
              action: "wksap_stop_background_job",
              sObject: sObject,
              wksap_nonce: wksap_ajax_object.wksap_nonce,
            },
            beforeSend: function () {},
            success: function (output) {
              var obj = jQuery.parseJSON(output);
              Swal.fire({
                title: wksap_ajax_object.translation.background_job,
                text: obj.message,
                icon: obj.code,
                timer: 2000,
                showConfirmButton: false,
                showCancelButton: false,
              }).then((result) => {
                location.reload();
              });
            },
            error: function (xhr) {
              // if error occured
              wkSAPmyAdminNoticesAppend("error", wksap_ajax_object.translation.error_ex, xhr.responseText);
            },
          });
        }

        function sweet_alert(msg, text = "") {
          Swal.fire({
            icon: "warning",
            title: msg,
            text: text,
          });
        }

        function wkSAPsalert(sObject, type , message = '') {
        	sObject = sObject.charAt(0).toUpperCase() + sObject.slice(1);
        	if(type == 'unlink' && message == ''){
        		$msg = wksap_ajax_object.translation.no + sObject +  wksap_ajax_object.translation.select_unlink;
        	}
        	else if(type == 'export'){
        		$msg = wksap_ajax_object.translation.no + sObject +  wksap_ajax_object.translation.select_export;
        	}
        	else if(type == 'import'){
        		$msg = wksap_ajax_object.translation.no + sObject +  wksap_ajax_object.translation.select_import;
        	}else if(type == 'unlink' && message != ''){
        		$msg  = message;
        	}else if(type == 'dateFiler'){
        		$msg = message;
        	}
        	Swal.fire({
            title: wksap_ajax_object.translation.warning,
            text: $msg,
            icon: 'info',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
            }).then(() => {
                location.reload(); // Refresh the page after the alert closes
            });
        }

    function wksap_delete_mapping_data(process, sObject) {
        if (!wksap_ajax_object?.wksap_enable) {
            Swal.fire({
                title: wksap_ajax_object.translation.error,
                text: wksap_ajax_object.translation.enable_plugin,
                icon: 'error',
                showConfirmButton: true,
                timer: 3000
            });
            return;
        }
          let selectedDataMsg = wksap_ajax_object.translation.selected_unlink_mapping;
          let allDataMsg = wksap_ajax_object.translation.all_mapping_unlink;
          /* Unlink User Ajax */
          if (sObject == "user") {
            if (process == "delete-user"){
              let isSelected = null;
              let isMappingNotExisting = 0;
              let selectedCheckboxes = jQuery("input[name='mergeduser[]']:checkbox:checked");
              if (selectedCheckboxes.length > 0) {
                let selectedValues = selectedCheckboxes.map(function() {
                  return this.value;
                }).get();
                isMappingNotExisting = selectedValues.filter(value => !document.getElementById('row_' + value)).length;
                if (isMappingNotExisting === selectedValues.length) {
                  isSelected = 0;
                  wkSAPsalert(sObject, 'unlink', wksap_ajax_object.translation.no_mapping_available);
                  event.preventDefault();
                }else{
                  isSelected = 1;
                }
              }
            if(isSelected == null || isSelected == 1){
              window.user_item_ids = new Array();
              jQuery("input[name='mergeduser[]']:checkbox").each(function () {
                if (jQuery(this).prop("checked") == true) {
                  item_id = jQuery(this).val();
                  window.user_item_ids.push(item_id);
                }
              });
              if (window.user_item_ids.length > 0) {
                jQuery.ajax({
                  type: "POST",
                  url: wksap_ajax_object.wksap_ajax,
                  data: {
                    action: "wksap_delete_mapping_data",
                    deleteIds: window.user_item_ids,
                    process: process,
                    sObject: sObject,
                    wksap_nonce: wksap_ajax_object.wksap_nonce
                  },
                  beforeSend: function () {},
                    success: function (output) {
                    Swal.fire({
                      title: wksap_ajax_object.translation.unlinked,
                      text: selectedDataMsg,
                      icon: "success",
                      timer: 2000,
                      showConfirmButton: false,
                      showCancelButton: false,
                    }).then((result) => {
                      location.reload();
                    });
                  },
                  error: function (xhr) {
                    // if error occured
                    wkSAPmyAdminNoticesAppend("error", wksap_ajax_object.translation.error_ex, xhr.responseText);
                  },
                  complete: function () {},
                });
              } else {
                wkSAPsalert(sObject, 'unlink');
              }
            }

            }
            if (process == "delete-all-user") {
              jQuery.ajax({
                type: "POST",
                url: wksap_ajax_object.wksap_ajax,
                data: {
                  action: "wksap_delete_mapping_data",
                  process: process,
                  sObject: sObject,
                  wksap_nonce: wksap_ajax_object.wksap_nonce
                },
                success: function (output) {
                  if(output){
                    Swal.fire({
                      title: wksap_ajax_object.translation.success,
                      text: wksap_ajax_object.translation.all_unlinked,
                      icon: 'success',
                      showConfirmButton: false,
                      timer: 3000,
                      timerProgressBar: true,
                    }).then(function() {
                      location.reload();
                    });
                  }else{
                    Swal.fire({
                      title: wksap_ajax_object.translation.warning,
                      text: wksap_ajax_object.translation.no_records_mapping,
                      icon: 'info',
                      showConfirmButton: false,
                      timer: 2000,
                      timerProgressBar: true,
                    }).then(function() {
                      location.reload();
                    });
                  }

                },
                error: function (xhr) {
                  // if error occured
                  wkSAPmyAdminNoticesAppend("error", wksap_ajax_object.translation.error_ex, xhr.responseText);
                },
                complete: function () {},
              });
            }
          }

        }
});

function wksap_sweet_alert(msg, text = "") {
    Swal.fire({
      icon: "warning",
      title: msg,
      text: text,
    });
  }
function wkSAPvalidateform() {
    var uname = document.myForm.wwsconnector_username.value;
    var password = document.myForm.wwsconnector_password.value;
    var cDB = document.myForm.wwsconnector_sapdb.value;
    var inst = document.myForm.wwsconnector_instance.value;
    var prefix = document.myForm.wwsconnector_sap_prefix.value;
    var license_key = document.myForm.wwsconnector_sap_license_key.value;

    if (uname == null || uname == "") {
        wksap_sweet_alert(wksap_ajax_object.translation.username);
        return false;
    } else if (password == null || password == "") {
        wksap_sweet_alert(wksap_ajax_object.translation.password);
        return false;
    } else if (cDB == null || cDB == "") {
        wksap_sweet_alert(wksap_ajax_object.translation.company);
        return false;
    } else if (inst == null || inst == "") {
        wksap_sweet_alert(wksap_ajax_object.translation.service);
        return false;
    } else if (prefix == null || prefix == "") {
        wksap_sweet_alert(wksap_ajax_object.translation.sap_prefix);
        return false;
    } else if (license_key == null || license_key == "") {
        wksap_sweet_alert(wksap_ajax_object.translation.licence_key);
        return false;
    } else {
        if (prefix.length > 3) {
            wksap_sweet_alert(wksap_ajax_object.translation.prefix_3);
            return false;
        } else if (prefix.length < 3) {
            wksap_sweet_alert(wksap_ajax_object.translation.prefix_2);
            return false;
        }
    }
}
window.connectionType = '';

function wkSAPinitilaizeConnection(target) {
    if (target == 'P') {
        window.connectionType = 'production';
        sap_userName = document.getElementById("wwsconnector_username").value;
        sap_password = document.getElementById("wwsconnector_password").value;
        sap_db = document.getElementById("wwsconnector_sapdb").value;
        sap_instance = document.getElementById("wwsconnector_instance").value;
        sap_prefix = document.getElementById("wwsconnector_sap_prefix").value;
        sap_license_key = document.getElementById("wwsconnector_sap_license_key").value;

        var obj = {
            UserName: sap_userName,
            Password: sap_password,
            CompanyDB: sap_db,
            instance: sap_instance,
            sap_prefix: sap_prefix,
            sap_license_key: sap_license_key
        };
        jQuery('#wksap_connector_data').val(JSON.stringify(obj));
        jQuery('#wksap-form').submit();
    }

}
