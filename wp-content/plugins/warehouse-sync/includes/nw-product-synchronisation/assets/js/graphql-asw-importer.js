jQuery(document).ready(function($) {

	/**
	 * Block the import modal
	 *
	 */
	function blockModal() {
		$('#nwp-asw-importer').block({
		    message    : '',
		    overlayCSS : {
		      background : '#fff',
		      opacity    : 0.6
		    }
		  });
	}

	/**
	 * Helper function to unblock the import modal
	 *
	 */
	function unblockModal() {
		$('#nwp-asw-importer').unblock();
	}

	/**
	 * Auto-open ASW importer modal if new product
	 *
	 */
	if ($(document.body).hasClass('nwp-new-product')) {
		$(this).WCBackboneModal({
			template : 'nwp-modal-asw-importer',
		});
	}

	/**
	 * Open modal by button press
	 *
	 */
	$(document).on('click', '#nwp-open-asw-import-dialog', function(e) {
  	e.preventDefault();
		$(this).WCBackboneModal({
			template : 'nwp-modal-asw-importer',
		});

		blockModal();

		var data = {
			action 		 : 'nwp_asw_search_reimport',
			security	 : $('#nwp-open-asw-import-dialog').data('nonce'),
			product_id : woocommerce_admin_meta_boxes.post_id,
		};

		$.post(ajaxurl, data, function(response) {
			$('#nwp-asw-importer-list').empty().append(response);
			unblockModal();
			if ($('#nwp-asw-importer-list input:enabled').length > 0) {
				imageUploader();
				$('#nwp-do-asw-import').prop('disabled', true);
				$('#nwp-asw-importer tr:not(.nwp-color-row) input:enabled').trigger('click');
			}
		});
	});


	/**
	 * Enable search button when there are more than 2 characters in input field
	 *
	 */
	$('#nwp-asw-product-number').on('change', function() {
		if ($(this).val().length > 2)
			$('#nwp-do-asw-search').prop('disabled', false);
		else
			$('#nwp-do-asw-search').prop('disabled', true);
	}).keyup(function() {
		$(this).change();
	});


	/**
	 * Enable import button only when variations are checked
	 *
	 */
	$(document).on('change', '#nwp-asw-importer-list tbody input[type="checkbox"]', function() {
		if ($('#nwp-asw-importer-list tbody input[type="checkbox"]:checked').length)
			$('#nwp-do-asw-import').prop('disabled', false);
		else
			$('#nwp-do-asw-import').prop('disabled', true);
	});

	/**
	 * Update all checkboxes when checkbox in table header changes, and
	 * disable/enable import button depending on state
	 *
	 */
	$(document).on('change', '#nwp-asw-importer-list thead input[type="checkbox"]', function() {
		let all_cbs = $(this).closest('table').find('tbody input[type="checkbox"]:not(:disabled)').prop('checked', $(this).is(':checked'));
		if ($(this).is(':checked')) {
			$('#nwp-do-asw-import').prop('disabled', false);
		}
		else {
			$('#nwp-do-asw-import').prop('disabled', true);
		}
	});


	/**
	 * Do ajax call to get a list of variations of product to select for import,
	 * successful response from ASW server will produce a table  with rows
	 * of variations, or an error message
	 *
	 */
  // var validatedProductId = false;
	var validatedProductType = true;
	$(document).on('click', '#nwp-do-asw-search', function(){
		blockModal();

		var data = {
			action  		 : 'nwp_asw_search',
			security	 	 : $('#nwp-do-asw-search').data('nonce'),
			product_type : $('#nwp-asw-product-type').val(),
			product 		 : $('#nwp-asw-product-number').val(),
		};

		$.post(ajaxurl, data, function(response) {
			unblockModal();
			$('#nwp-asw-importer-list').empty().append(response);
			if ($('#nwp-asw-importer-list').has('table').length) {
				$('#nwp-do-asw-import').prop('disabled', false);
				validatedProductType = $('#nwp-asw-product-type').val();
				validatedSKU = $('#nwp-asw-product-number').val();

				imageUploader();

				//Enable import button only when variations are checked
				jQuery('#nwp-asw-importer-list tbody input[type="checkbox"]', function() {
					if (jQuery('#nwp-asw-importer-list tbody input[type="checkbox"]:checked').length)
						jQuery('#nwp-do-asw-import').prop('disabled', false);
					else
						jQuery('#nwp-do-asw-import').prop('disabled', true);
				});
			}
			else {
				$('#nwp-do-asw-import').prop('disabled', true);
			}

			//select club dropdown with search - select2
			jQuery("[name='select_club']").select2({});
		});
	});

	/**
	 * Initiate import of products
	 *
	 */
	$(document).on('click', '#nwp-do-asw-import', function() {
		blockModal();
		var data, action;
		var nonce = $('#nwp-do-asw-import').data('nonce');

		var cat_arr = [];
		jQuery('input[type="checkbox"][name="cust_cat[]"]:checked').each(function(){
			cat_arr.push(jQuery(this).val());
		});

		var club_arr = [];
		jQuery('input[type="checkbox"][name="nw_club[]"]:checked').each(function(){
			club_arr.push(jQuery(this).val());
		});

		var var_img_arr = {};
		jQuery('input[name="custom-var-img-id"]').each(function( index, ele ) {
			var_img_arr[jQuery(this).attr('id')] = jQuery(this).val(); 
		});

		var arr1 = {};
		jQuery('input[name="custom_date_field"]').each(function( index, ele ) {
			arr1[jQuery(this).attr('id')] = jQuery(this).val(); 
		});

		var variant_color = {};
		jQuery('input[name="nw_color[]"]').each(function( index, ele ) {
			variant_color[jQuery(this).attr('id')] = jQuery(this).val(); 
		});

		var variant_product_status = {};
		jQuery('input[name="nwp_asw_product_variant_status"]').each(function( index, ele ) {
			variant_product_status[jQuery(this).attr('id')] = (jQuery(this).prop('checked') || jQuery(this).prop('indeterminate'))  ? 'publish' : 'private'; 
		});

		// If this is a completely new product, create the main product first
		if ($(document.body).hasClass('nwp-new-product')) {
			data = {
				action					: 'nwp_create_product',
				product_sku				: validatedSKU,
				product_type			: validatedProductType,
				security				: nonce,
				skus					: $('#nwp-asw-importer-list').find('tr:not(.nwp-color-row) input').serialize(),
				custom_clubs			: club_arr,
				product_brand			: jQuery('#product_brand').val() ? jQuery('#product_brand').val() : "",
				custom_single_club  	: jQuery('[name="select_club"]').val(),
				custom_tag				: jQuery('#custom_tag').val(),
				custom_feature_img		: jQuery('#featureImg').val(),
				custom_var_img			: var_img_arr,
				custom_textarea			: jQuery('#custom_textarea').val(),
				print_instructions  	: jQuery('#print_instructions').val(),
				short_description 		: jQuery("#short_description").val(),
				custom_cat				: cat_arr,
				cdate_arr 				: JSON.stringify(arr1),
				custom_color			: variant_color,
				nwp_asw_product_status 	: JSON.stringify(variant_product_status),
				show_slick_slider_gallery: jQuery("#show_slick_slider_gallery").val(),
			}
		}
		// If product already exist, perform an update instead
		else {
			data = {
				action		 			: 'nwp_update_product',
				product_id 				: woocommerce_admin_meta_boxes.post_id,
				security	 			: nonce,
				skus 					: $('#nwp-asw-importer-list').find('tr:not(.nwp-color-row) input').serialize(),
				cdate_arr 				: JSON.stringify(arr1),
				custom_var_img			: var_img_arr,
				nwp_asw_product_status	: JSON.stringify(variant_product_status)
			};
		}

		// Create/update main product
		$.post(ajaxurl, data, function(response) {
			try {
				response = JSON.parse(response);
				if (!$.isPlainObject(response))
					throw 'Response is not an object';
				if (!response.hasOwnProperty('number_of_variations') || !response.hasOwnProperty('edit_link') || !response.hasOwnProperty('cache_id')) {
					throw 'Response is missing values';
				}
			}
			// Errorful response
			catch (e) {
				$('#nwp-asw-importer-list').empty().html(response);
				unblockModal();
				return;
			}

			// Success, continuing..
			var numberOfVariations = response['number_of_variations'];
			var editLink = response['edit_link'];
			var cacheID = response['cache_id'];

			// We are now creating a variation at a time, display a progressbar over the blocked modal
			$('#nwp-asw-importer').block({
				message : '<div id="nwp-progressbar"></div><div id="nwp-progressbar-msg"></div>',
				overlayCSS : {
					background : '#fff',
					opacity    : 0.6
				}
			});

			var progressBar = $('#nwp-progressbar');
			var progressMsg = $('#nwp-progressbar-msg');

			$('.blockOverlay').addClass('nwp-hide-spinner');
			progressBar.progressbar({
				value : 0,
				max : numberOfVariations,
			});
			progressMsg.html(0 + '/' + numberOfVariations);

			// Wrap creation of all variations in promises
			function createVariation(i) {
				return $.post(ajaxurl, {
					action 						: 'nwp_create_variation',
					variation_index 	: i,
					cache_id					: cacheID,
					security					: nonce,
				}, function(response) {
					try { JSON.parse(response); }
					catch (e) {
						return $.Deferred().reject(response);

					}
				});
			}

			var indices = [];
			for (var i = 0; i < numberOfVariations; i++) {
				indices.push(i);
			}

			var promise = $.Deferred().resolve();
			indices.forEach(function(index) {
				promise = promise.fail(function(error) {
					$('#nwp-asw-importer-list').empty().html(response);
					unblockModal();
					// TODO display link to created product anyway
				}).then(function() {
					if ((index + 1) == numberOfVariations) {
						window.location.href = editLink;
					}
					else {
						progressBar.progressbar('value', index);
						progressMsg.html(index + '/' + numberOfVariations);
					}
					return createVariation(index);
				});
			});
		});
	});

	// Open up panel and show all sizes for a particular color
	$(document).on('click', '#nwp-asw-importer .toggle-indicator', function() {
		$(this).toggleClass('open').parents('tr').nextUntil('.nwp-color-row').toggle();
	});

	// Open up panel and show all sizes for a particular color, when label with ASW-number is clicked
	$(document).on('click', '#nwp-asw-importer label.nwp-asw-sku', function() {
		$(this).parents('td').next('.toggleIndicator').toggleClass('open');
		$(this).parents('tr').nextUntil('.nwp-color-row').toggle();
	});

	// Check all checkboxes for sizes belonging to a color, when the master cb of that color is clicked
	$(document).on('click', '#nwp-asw-importer .nwp-color-row input[name="nw_color[]"]', function() {
		$(this).parents('tr').nextUntil('.nwp-color-row').find('input').prop('checked', $(this).prop('checked'));
	});

	// Display dash in parent checkbox to indicate only some of the child checkboxes are checked
	$(document).on('click', '#nwp-asw-importer tr:not(.nwp-color-row) input:enabled', function() {
		var colorRow = $(this).parents('tr').prevAll('.nwp-color-row:first');
		var inputs = colorRow.nextUntil('.nwp-color-row').find('input');
		var checked = inputs.filter(':checked');
		colorRow = colorRow.find('input');

		if (checked.length == inputs.length) {
			colorRow.prop('checked', true).prop('indeterminate', false);
		}
		else if (checked.length) {
			colorRow.prop('checked', false).prop('indeterminate', true);
		}
		else {
			colorRow.prop('checked', false).prop('indeterminate', false);
		}
	});

	//select/unselect all cbs when master cb is clicked - clubs
	$(document).on('click', '#select_all_club', function() {
		$('input[name="nw_club[]"]').prop('checked', $(this).prop('checked'));
	});

	//if one of the club is unchecked, uncheck "select all clubs" cb
	$(document).on('change', '.custom_clubs_div input[name="nw_club[]"]', function() {
		let input = jQuery('input[name="nw_club[]"]').length;
		let checked = jQuery('input[name="nw_club[]"]').filter(':checked').length;

		if(input != checked && jQuery("#select_all_club").prop('checked')){
			jQuery("#select_all_club").prop('checked',false);
			console.log('uncheck master');
		}else{
			console.log('all good');
		}
	});

	/* Image uploader */
	function imageUploader(){
		var frame;

		jQuery('.upload-custom-img').on( 'click', function( event ){
		
		obj = jQuery(this).parents('.img-div');
			event.preventDefault();
			
			// If the media frame already exists, reopen it.
			if ( frame ) {
			  frame.open();
			  return;
			}
			
			frame = wp.media({
				title: 'Select or Upload Media Of Your Chosen Persuasion',
				button: {
					text: 'Add image'
				},
				multiple: false  // Set to true to allow multiple files to be selected
			});
			
			frame.on( 'select', function() {
				// Get media attachment details from the frame state
				var attachment = frame.state().get('selection').first().toJSON();
				
				// Send the attachment URL to our custom image input field.
				obj.find('.custom-img-container').append( '<img src="'+attachment.url+'" alt=""  class="new-img"/>' );
				
				if(obj.hasClass('image-var-col'))
				{
					obj.find('.new-img').css('max-width','40px');
				}
				else
				{
					obj.find('.new-img').css('max-width','100%');
				}
				obj.find('.old-img').hide();
				
				// Send the attachment id to our hidden input
				obj.find('.custom-img-id').val( attachment.id );
				
				// Hide the add image link
				obj.find('.upload-custom-img').addClass( 'hidden' );
				
				// Unhide the remove image link
				obj.find('.delete-custom-img').removeClass( 'hidden' );
			});
			frame.open();
		
		});
		
		// DELETE IMAGE LINK
		jQuery('.delete-custom-img').on( 'click', function( event ){
			event.preventDefault();
			obj = jQuery(this).parents('.img-div');
			// Clear out the preview image
			// obj.find('.custom-img-container').html( '' );
			obj.find('.old-img').show();
			obj.find('.new-img').remove();
			// Un-hide the add image link
			obj.find('.upload-custom-img').removeClass( 'hidden' );

			// Hide the delete image link
			obj.find('.delete-custom-img').addClass( 'hidden' );

			// Delete the image id from the hidden input
			obj.find('.custom-img-id').val( '' );
		});
	}
});
