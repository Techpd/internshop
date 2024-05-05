jQuery(document).ready(function ($) {
  /**
   * Block the updater modal
   *
   */
  function blockModal() {
    $("#nwp-asw-updater").block({
      message: "",
      overlayCSS: {
        background: "#fff",
        opacity: 0.6,
      },
    });
  }

  /**
   * Helper function to unblock the updater modal
   *
   */
  function unblockModal() {
    $("#nwp-asw-updater").unblock();
  }

  /**
   * Open modal by button press
   *
   */
  $(document).on("click", "#nwp-open-asw-updater-dialog", function (e) {
    e.preventDefault();
    $(this).WCBackboneModal({
      template: "nwp-modal-asw-updater",
    });

    blockModal();

    var data = {
      action: "nwp_asw_pre_update",
      security: $("#nwp-open-asw-updater-dialog").data("nonce"),
      product_id: woocommerce_admin_meta_boxes.post_id,
    };

    $.post(ajaxurl, data, function (response) {
      $("#nwp-asw-updater article").empty().append(response);
      unblockModal();
    });
  });

  $(document).on("click", "#nwp-do-asw-update", function (e) {
    e.preventDefault();
    var data = {
      action: "nwp_asw_update",
      security: $(this).data("nonce"),
      product_id: woocommerce_admin_meta_boxes.post_id,
      options: $("#nwp-asw-updater tr input").serialize(),
    };

    blockModal();

    $.post(ajaxurl, data, function (response) {
      location.reload();
    });
  });

  /**
   * Enable import button only when variations are checked
   *
   */
  $(document).on(
    "change",
    '#nwp-asw-updater tbody input[type="checkbox"]',
    function () {
      if (
        $(
          '#nwp-asw-updater tbody input[type="checkbox"]:checked:not(:disabled)'
        ).length
      )
        $("#nwp-do-asw-update").prop("disabled", false);
      else $("#nwp-do-asw-update").prop("disabled", true);
    }
  );

  /**
   * If any checkbox is checked, enable the update-button, otherwise, disable it
   *
   */
  $(document).on(
    "change",
    '#nwp-asw-updater tbody input[type="checkbox"]',
    function () {
      let checked_cbs = $(
        '#nwp-asw-updater tbody input[type="checkbox"]:checked:not(:disabled)'
      );
      $("#nwp-do-asw-update").prop(
        "disabled",
        checked_cbs.length == 0 ? true : false
      );
    }
  );

  // Open up panel and show all sizes for a particular color
  $(document).on("click", "#nwp-asw-updater .toggle-indicator", function () {
    $(this)
      .toggleClass("open")
      .parents("tbody")
      .find(".nw-update-image-row")
      .toggle();
  });

  /**
   * Check child checkboxes when a parent row is selected
   *
   */
  $(document).on("change", "#nwp-asw-updater #nw_update_images", function () {
    $("#nwp-asw-updater .nw-update-image-row input:not(:disabled)")
      .prop("checked", $(this).prop("checked"))
      .trigger("change");
  });
});
