/**
 * Manages AJAX calls and UI-functions for custom product types in WP admin
 *
 */

jQuery(document).ready(function($) {
  $('.nw-select2').select2();

  /**
   * Add general_tab as visible for custom product types
   * (doesn't work with woocommerce_product_data_tabs)
   *
   */
  ['nw_stock', 'nw_stock_logo', 'nw_special'].forEach(function(el) {
      $('.general_tab').addClass('show_if_' + el).show();
      $('.enable_variation').each(function(el1) {
          $(this).addClass('show_if_' + el);
          $(this).show();
      });
      $('.woocommerce_attribute_used_for_variations').each(function(el1) {
          $(this).addClass('enable_if_' + el);
          $(this).removeClass('disabled');
          $(this).prop('disabled', false);
      });
      $('')
      $('.options_group.pricing').addClass('show_if_' + el).show();
  });

  /**
   * Save changes in color-access-panel
   *
   */
  $(document).on('click', '#save-color-access', function(e) {
      savePanel(e, {
          post_id: woocommerce_admin_meta_boxes.post_id,
          action: 'nw_color_access',
          security: $(this).data('nonce'),
          data: $('table#nw-color-access').find('input').serialize(),
      }, '#nw_color_access_options');
  });

  /**
   * Save changes in discount panel
   *
   */
  $(document).on('click', '#save-discounts', function(e) {
      savePanel(e, {
          post_id: woocommerce_admin_meta_boxes.post_id,
          action: 'nw_discounts',
          security: $(this).data('nonce'),
          data: $('table#nw-discounts').find('input').serialize(),
      }, '#nw_discount_options');
  });

  // PLANASD-484 added restore defaults functionality
  /**
   * Save changes in discount panel
   *
   */
  $(document).on('click', '#restore-discounts', function(e) {
      var confirm_msg = $(this).data('confirm');
      if(confirm_msg != '' && confirm_msg != null && confirm_msg != undefined) {
          e.preventDefault();
          if(window.confirm(confirm_msg)) {
              savePanel(e, {
                  post_id: woocommerce_admin_meta_boxes.post_id,
                  action: 'nw_restore_discounts',
                  security: $(this).data('nonce'),
                  data: {},
              }, '#nw_discount_options');
          }
      } else {
          savePanel(e, {
              post_id: woocommerce_admin_meta_boxes.post_id,
              action: 'nw_restore_discounts',
              security: $(this).data('nonce'),
              data: {},
          }, '#nw_discount_options');
      }
  });

  /**
   * Save changes in campaign-enabled-variations-panel
   *
   */
  $(document).on('click', '#save-campaign-enabled-variations', function(e) {
      savePanel(e, {
          post_id: woocommerce_admin_meta_boxes.post_id,
          action: 'nw_campaign_enabled_variations',
          security: $(this).data('nonce'),
          data: $('table#nw-campaign-enabled-variations').find('input').serialize()
      }, '#nw_campaign_enabled_variations_options');
  });



  /**
   * Do AJAX post to data from a panel
   *
   */
  function savePanel(e, data, target) {
      e.preventDefault();

      $('#woocommerce-product-data').block({
          message: '',
          overlayCSS: {
              background: '#fff',
              opacity: 0.6
          }
      });

      target = (typeof target !== 'undefined') ? target : false;

      $.post(ajaxurl, data, function(response) {
          if (target) {
              refreshPanelAndUnblock(target);
          } else {
              $('#woocommerce-product-data').unblock();
          }
      });
  }

  /**
   * Reload panels in case variations or attributes have been edited
   *
   */
  var mustUpdateColorAccessPanel = false;
  var mustUpdateCampaignEnabledVariationsPanel = false;
  var mustUpdateStockControlPanel = false;

  $('#variable_product_options').on('click', 'button.save-variation-changes', function() {
      mustUpdateColorAccessPanel = true;
      mustUpdateCampaignEnabledVariationsPanel = true;
      mustUpdateStockControlPanel = true;
  });

  $('#product_attributes').on('click', 'button.save_attributes', function() {
      mustUpdateColorAccessPanel = true;
      mustUpdateCampaignEnabledVariationsPanel = true;
      mustUpdateStockControlPanel = true;
  });

  $('#woocommerce-product-data').on('click', '.nw_color_access_tab', function() {
      if (mustUpdateColorAccessPanel) {
          refreshPanel('#nw_color_access_options');
          mustUpdateColorAccessPanel = false;
      }
  });
  $('#woocommerce-product-data').on('click', '.nw_campaign_enabled_variations_tab', function() {
      if (mustUpdateCampaignEnabledVariationsPanel) {
          refreshPanel('#nw_campaign_enabled_variations_options');
          mustUpdateCampaignEnabledVariationsPanel = false;
      }
  });
  $('#woocommerce-product-data').on('click', '.nw_stock_control_tab', function() {
      if (mustUpdateStockControlPanel) {
          refreshPanel('#nw_stock_control_options');
          mustUpdateStockControlPanel = false;
      }
  });

  // Block and refresh a panel
  function refreshPanel(target) {
      $('#woocommerce-product-data').block({
          message: '',
          overlayCSS: {
              background: '#fff',
              opacity: 0.6
          }
      });
      refreshPanelAndUnblock(target);
  }

  /**
   * Reload a panel
   *
   */
  function refreshPanelAndUnblock(target) {
      var this_page = window.location.toString();
      this_page = this_page.replace('post-new.php?', 'post.php?post=' + woocommerce_admin_meta_boxes.post_id + '&action=edit&');

      $(target).load(this_page + ' ' + target + ' > *', function() {
          $(target).trigger('reload');
          $('#woocommerce-product-data').unblock();
      });
  }


  /**
   * Check all checkboxes in the same column
   *
   */
  $(document).on('click', '.nw-table th:not(:first-child)', function() {
      var index = $(this).index() + 1;
      var checkboxes = $(this).parents('.nw-table').find('tbody tr td:nth-child(' + index + ') input');

      if (checkboxes.filter(':not(:checked)').length)
          checkboxes.prop('checked', true).trigger('change');
      else
          checkboxes.prop('checked', false).trigger('change');
  });

  /**
   * Check all checkboxes on the same row
   *
   */
  $(document).on('click', '.nw-table tr td.nw-label', function() {
      row = $(this).parents('tr');
      if (row.find('input:not(:checked)').length)
          row.find('input').prop('checked', true).trigger('change');
      else
          row.find('input:not(:disabled)').prop('checked', false).trigger('change');
  });

  /**
   * Search/filter stores based on search input, but in a hierarchical fashion:
   * Keep group/vendor visible if itself or its clubs contain input value, but hide if not.
   * Hide all clubs if it doesn't contain it
   *
   */
  $('.nw-search input').change(function() {
      var searchTarget = $(this).parents('table');
      var searchTerm = $(this).val().toUpperCase();

      // Display 'clear input'-button
      if (searchTerm.length) {
          $(this).next('.nw-clear-input').css('display', 'inline-block');
      } else {
          $(this).next('.nw-clear-input').hide();
      }

      // If anything in search field
      if(searchTerm.length)
          $('.vendor-show-clubs span').hide();
      else
          $('.vendor-show-clubs span').show();

      if (searchTarget.length) {

          // Check if an elements content contains the search term
          function hasTerm(el) {
              if (searchTerm.length == 0) {
                  return true;
              }
              return el.find('> td').first().text().toUpperCase().indexOf(searchTerm) >= 0;
          }

          // All elements to hide/show (all rows of the table)
          var toHide = searchTarget.find('tbody tr');
          var toShow = $();

          // Check for each row whether to hide or show it, depending on input
          toHide.each(function() {
              if (hasTerm($(this))) {
                  if ($(this).hasClass('nw-club') && !$(this).hasClass('shop-individual')) {
                      if(searchTerm.length) {
                          toShow = toShow.add($(this));
                          toHide = toHide.not(this);
                      }
                  } else {
                      toShow = toShow.add($(this));
                      toHide = toHide.not(this);
                  }

                  if ($(this).hasClass('nw-club')) {
                      var vendor = $(this).prev('tr.nw-vendor');
                      toShow = toShow.add(vendor);
                      toHide = toHide.not(vendor);

                      var group = $(this).prev('tr.nw-group');
                      toShow = toShow.add($(this).prev('tr.nw-group'));
                      toHide = toHide.not(group);
                  }
              }
          });

          // Update visibility
          toHide.hide();
          toShow.show();

          if(!searchTerm.length){
            jQuery("table#nw-color-access .nw-club").show();
            jQuery("table#nw-discounts td.vendor-show-clubs span").addClass("dashicons-insert").removeClass("dashicons-remove");
          }
      }
  }).keyup(function() { // Force update on all changes
      $(this).change();
  });

  /**
   * Hierarchical checkboxes: check and disable checkboxes belonging to given checkbox
   * when enabled, and enable if given checkbox is disabled
   *
   */
  $(document).on('change', '#nw-color-access input[type="checkbox"]', function() {
      var tr = $(this).parents('tr');

      if (!tr.hasClass('nw-club')) {
          var index = $(this).parents('td').index() + 1;
          var checked = $(this).attr('checked') ? true : false;
          var type = '.nw-group';
          if (tr.hasClass('nw-vendor'))
              type += ', .nw-vendor';

          var color = $(this).attr('name').split('[')[2].split(']')[0];
          tr.nextUntil(type).find('td:nth-child(' + index + ') input[name$="[' + color + ']"]').prop('checked', checked).prop('disabled', checked);
      }
  });

  /**
   * Calculate percentage based on original price
   *
   */
  $(document).on('click', '.nw-percentage', function() {
      var input = $(this).siblings('input');
      var discount = Number(prompt('Enter discount in percentage'));
      if (discount == null || discount <= 0 || discount >= 100)
          return;

      var value = input.val() ? Number(input.val()) : Number(input.attr('placeholder'));
      input.val(Math.ceil(value * (1 - discount / 100))).trigger('change');
      $(this).siblings('.nw-reset-discount').show();
  });

  /**
   * Reset discount field
   *
   */
  $(document).on('click', '.nw-reset-discount', function() {
      $(this).hide().siblings('input').val('').trigger('change');
  });

  /**
   * Hide / display reset-discount-button depending on input
   *
   */
  $('#nw_discount_options .nw_din_pris').on('change', function() {
      // PLANASD-484 commented out as the value in placeholder will always be the possible value for the club or vendor
  //     if ($(this).val()) {
  //         $(this).siblings('.nw-reset-discount').show();
  //     } else {
  //         $(this).siblings('.nw-reset-discount').hide();
  //     }

  //     var type = $(this).parents('tr').attr('class');
  //     if (type && type != 'nw-club') {
  //         var newPlaceholder = $(this).val() ? $(this).val() : $(this).attr('placeholder');
  //         var newPlaceholder_val = $(this).val();
  //         // $(this).parents('tr').nextUntil('.' + type).find('input').attr('placeholder', newPlaceholder);
  //         $(this).parents('tr').nextUntil('.' + type).find('input').each(function() {
  //             var ori_pris = $(this).data('ori');
  //             var ven_pris = $(this).data('ven');
  //             var club_pris = $(this).data('club');


  //             if(newPlaceholder_val != ven_pris && newPlaceholder_val != '') { // vendor pris changed
  //                 $(this).attr('placeholder', newPlaceholder);
  //             } else {
  //                 $(this).attr('placeholder', club_pris);
  //             }
  //         });
  //     }

      if($(this).val() != '' && $(this).val() != undefined && $(this).val() != null)
          $(this).addClass('nw-custom-din-pris');
      else
          $(this).removeClass('nw-custom-din-pris');
  }).keyup(function() {
      $(this).change();
  });

  /**
   * Clear input in search/filter field
   *
   */
  $(document).on('click', '.nw-clear-input', function() {
      $(this).prev('input').val('').trigger('change');
  });


  /**
   * Add general_tab as visible for custom product types
   * (doesn't work with woocommerce_product_data_tabs)
   *
   */
  ['nw_stock', 'nw_stock_logo', 'nw_special'].forEach(function(el) {
      $('.general_tab').addClass('show_if_' + el).show();
      $('.options_group.pricing').addClass('show_if_' + el).show();
  });

  /**
   * Enable datepicker for 'sale period date'
   *
   */
  $('#nw_sale_period_date_picker').datepicker({
      dateFormat: 'dd-mm-yy',
      defaultDate: +14,
      firstday: 1,
      minDate: -1,
  });

  // PLANASD-484 added feature for hide/show vendor's clubs
  $(document).on('click', '.vendor-show-clubs', function() {
      if($(this).hasClass('opened')) {
          $(this).removeClass('opened');
          $(this).html('<span class="dashicons dashicons-insert"></span>');
          $('.shop-'+$(this).data('ven_id')).hide();
      } else {
          $(this).addClass('opened');
          $(this).html('<span class="dashicons dashicons-remove"></span>');
          $('.shop-'+$(this).data('ven_id')).show();
      }
  });
});