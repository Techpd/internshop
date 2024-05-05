jQuery(document).ready(function($) { 

	/**
	 * Block the import modal
	 *
	 */
	function blockModal() {
		$('#nw-asw-importer').block({
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
		$('#nw-asw-importer').unblock();
	}

	/**
	 * Auto-open ASW importer modal if new product
	 *
	 */
	if ($(document.body).hasClass('nw-new-product')) {
		$(this).WCBackboneModal({
			template : 'nw-modal-asw-importer',
		});
	}

	/**
	 * Change existing popup 2 on ASW Impoter ( #nw-open-asw-import-dailog ) button click
	 *
	 */
	// $(document).on('click', '#nw-open-asw-import-dialog', function(e) {
  	// e.preventDefault();
	// 	$(this).WCBackboneModal({
	// 		template : 'nw-modal-asw-importer',
	// 	});

	// 	blockModal();

	// 	var data = {
	// 		action 		 : 'nw_asw_search_reimport',
	// 		security	 : $('#nw-open-asw-import-dialog').data('nonce'),
	// 		product_id : woocommerce_admin_meta_boxes.post_id,
	// 	};

	// 	$.post(ajaxurl, data, function(response) {
	// 		$('#nw-asw-importer-list').empty().append(response);
	// 		unblockModal();
	// 		if ($('#nw-asw-importer-list input:enabled').length > 0) {
	// 			$('#nw-do-asw-import').prop('disabled', true);
	// 			// $('#nw-asw-importer tbody tr:not(.nw-color-row) input:enabled').trigger('click');
	// 		}
	// 	});
	// });

	/**
	 * Open modal by button press
	 *
	 */
	 $(document).on('click', '#nw-open-asw-import-dialog', function(e) {
		e.preventDefault();
		$(this).WCBackboneModal({
			template : 'nw-modal-asw-importer',
		});

		blockModal();
		var data = {
			action  		   : 'nw_asw_search',
			security	 	   : $('#nw-open-asw-import-dialog').data('nonce'),
			product 		   : $("#_sku").val(),
			current_product_id : woocommerce_admin_meta_boxes.post_id
		};

	 	$.post(ajaxurl, data, function(response) {
			//unblockModal();
			$('#nw-asw-importer').show();
			$('.blockOverlay').hide();
			
		 	$('#nw-asw-importer-list').empty().append(response);
		 	if ($('#nw-asw-importer-list').has('table').length) {

				//Disable all input field if the variants row has disable class
				if( $(".nw-color-row").hasClass('nw-disabled-row') ){
					$(".nw-disabled-row :input").attr("disabled", true);
				}
				

			 	$('#nw-asw-importer').css('width','75%');
			 	$('#nw-do-asw-import').prop('disabled', false);
			 	validatedSKU = $("#_sku").val();

				/**
				 * Start select2 loading js to attribute icon dropdown
				 */
				if ($().select2) {
					$('#nw-attribute-icons .nw-select2').select2({
						templateResult: formatAttributeIcons,
						width : '100%',
						dropdownCssClass : 'nw-attribute-icons-dropdown'
					});
				}
		 
				function formatAttributeIcons(option) {
					if (option.loading) {
						return option.text;
					}
			
				let icon_url = $(option['element']).data('icon');
			
					if (!icon_url) {
						return option.text;
					}
					return $('<img src="' + icon_url + '"/><span>' + option.text + '</span>');
				}	
		 
				function select2_sortable($select2){
					var ul = $select2.next('.select2-container').first('ul.select2-selection__rendered');
					ul.sortable({
						forcePlaceholderSize: true,
						items       : 'li:not(.select2-search__field)',
						tolerance   : 'pointer',
						stop: function() {
							$($(ul).find('.select2-selection__choice').get().reverse()).each(function() {
								var id = $(this).data('data').id;
								var option = $select2.find('option[value="' + id + '"]')[0];
								$select2.prepend(option);
							});
						}
					});
				}
		 
			 	select2_sortable($("#nw-attribute-icons select"));

			 	//End select2 loading js

				/* Image uploader */
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
			else {
				$('#nw-do-asw-import').prop('disabled', true);
			}
		});
	});

	/**
	 * Enable search button when there are more than 2 characters in input field
	 *
	 */
	$('#nw-asw-product-number').on('change', function() {
		if ($(this).val().length > 2)
			$('#nw-do-asw-search').prop('disabled', false);
		else
			$('#nw-do-asw-search').prop('disabled', true);
	}).keyup(function() {
		$(this).change();
	});


	/**
	 * Enable import button only when variations are checked
	 *
	 */
	$(document).on('change', '#nw-asw-importer-list tbody .product_attribute_variations', function() {
		if($('.product_attribute_variations:checked, .product_attribute_variations:indeterminate').length){
			$('#nw-do-asw-import').prop('disabled', false);
			$("#nw-product-name").prop('checked',true);
		}else{
			$('#nw-do-asw-import').prop('disabled', true);
			$("#nw-product-name").prop('checked',false);
		}
	});

	/**
	 * Update all checkboxes when checkbox in table header changes, and
	 * disable/enable import button depending on state
	 *
	 */
	$(document).on('change', '#nw-asw-importer-list thead #nw-product-name', function() {
		let all_cbs = $(this).closest('table').find('tbody input[type="checkbox"]:not(:disabled)').prop('checked', $(this).is(':checked'));
		if ($(this).is(':checked')) {
			$('#nw-do-asw-import').prop('disabled', false);
		}
		else {
			$('#nw-do-asw-import').prop('disabled', true);
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
	$(document).on('click', '#nw-do-asw-search', function(){
		blockModal();

		var data = {
			action  		 : 'nw_asw_search',
			security	 	 : $('#nw-do-asw-search').data('nonce'),
			product 		 : $('#nw-asw-product-number').val(),
			product_type 	 : $('#nw-asw-product-type').val(),
		};

		$.post(ajaxurl, data, function(response) {
			unblockModal();
			$('#nw-asw-importer-list').empty().append(response);
			if ($('#nw-asw-importer-list').has('table').length) {
				$('#nw-asw-importer').css('width','75%');
				$('#nw-do-asw-import').prop('disabled', false);
				validatedSKU = $('#nw-asw-product-number').val();

				/**
				 * Start select2 loading js to attribute icon dropdown
				 */
				if ($().select2) {
					$('#nw-attribute-icons .nw-select2').select2({
						templateResult: formatAttributeIcons,
						width : '100%',
						dropdownCssClass : 'nw-attribute-icons-dropdown'
					});
				 }
			
				function formatAttributeIcons(option) {
					if (option.loading) {
						return option.text;
					}
			
				  let icon_url = $(option['element']).data('icon');
			
					if (!icon_url) {
						return option.text;
					}
					return $('<img src="' + icon_url + '"/><span>' + option.text + '</span>');
				}
			
				function select2_sortable($select2){
					var ul = $select2.next('.select2-container').first('ul.select2-selection__rendered');
					ul.sortable({
						forcePlaceholderSize: true,
						items       : 'li:not(.select2-search__field)',
						tolerance   : 'pointer',
						stop: function() {
							$($(ul).find('.select2-selection__choice').get().reverse()).each(function() {
								var id = $(this).data('data').id;
								var option = $select2.find('option[value="' + id + '"]')[0];
								$select2.prepend(option);
							});
						}
					});
				}
			
				select2_sortable($("#nw-attribute-icons select"));

				//End select2 loading js

				/* Image uploader */
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
			else {
				$('#nw-do-asw-import').prop('disabled', true);
			}
		});
	});

	/**
	 * remove and add checked attribute from "Active" checkbox on click
	 */

	$(document).on('click', '.nw-asw-product-status', function(){
        if (this.checked) {
			this.setAttribute("checked", "checked");
			$(this).val('publish');
		} else {
			this.removeAttribute("checked");
			$(this).val('private');
		}
    });

	/**
	 * Initiate import of products
	 *
	 */
	$(document).on('click', '#nw-do-asw-import', function() {
		
		blockModal();
		var data, action;
		var nonce = $('#nw-do-asw-import').data('nonce');
		var arr1 = {};
		jQuery('input[name="custom_date_field"]').each(function( index, ele ) {
			arr1[jQuery(this).attr('id')] = jQuery(this).val(); 
		});
		var var_img_arr = {};
		jQuery('input[name="custom-var-img-id"]').each(function( index, ele ) {
			var_img_arr[jQuery(this).attr('id')] = jQuery(this).val(); 
		});
		
		var cat_arr = [];
		jQuery('input[type="checkbox"][name="cust_cat[]"]:checked').each(function(){
			cat_arr.push(jQuery(this).val());
		});

		var variant_product_status = {};
		jQuery('input[name="nw_asw_product_variant_status"]').each(function( index, ele ) {
			variant_product_status[jQuery(this).attr('id')] = jQuery(this).val(); 
		});

		var attribute_icons = [];
		jQuery('.nw_attribute_icon_div .nw-select2 :selected').each(function(){
			attribute_icons.push(jQuery(this).val());
		});
		
		console.log( variant_product_status );
		
		// If this is a completely new product, create the main product first
		if ($(document.body).hasClass('nw-new-product')) {
			data = {
				action 				     : 'nw_create_product',
				product_sku 	         : validatedSKU,
				product_type 	 		 : $('#nw-asw-product-type').val() ? $('#nw-asw-product-type').val() : 'variable',
				security			     : nonce,
				skus 				 	 : $('#nw-asw-importer-list').find('tr:not(.nw-color-row) input').serialize(),
				cdate_arr 				 : JSON.stringify(arr1),
				concept        			 : jQuery('input[type="radio"][name="nw_product_concept"]:checked','.left-div').val(),
				custom_tag				 : jQuery('#custom_tag').val(),
				product_brand			 : jQuery('#product_brand').val(),
				custom_cat				 : cat_arr,
				custom_feature_img		 : jQuery('#featureImg').val(),
				custom_var_img		     : var_img_arr,
				custom_textarea		     : jQuery('#custom_textarea').val(),
				print_instructions  	 : jQuery('#print_instructions').val(),
				short_description 		 : jQuery("#short_description").val(),
				nw_product_material		 : $('#nw-product-material').val(),
				nw_attribute_icons       : attribute_icons,
				nw_asw_product_status    : JSON.stringify(variant_product_status)
			}
			
		}
		// If product already exist, perform an update instead
		else {
			data = {
				action		             : 'nw_update_product',
				product_id               : woocommerce_admin_meta_boxes.post_id,
				product_type 	 		 : $('#nw-asw-product-type').val(),
				security	             : nonce,
				skus 			         : $('#nw-asw-importer-list').find('tr:not(.nw-color-row) input').serialize(),
				cdate_arr 				 : JSON.stringify(arr1),
				concept        			 : jQuery('input[type="radio"][name="nw_product_concept"]:checked','.left-div').val(),
				custom_tag				 : jQuery('#custom_tag').val(),
				product_brand			 : jQuery('#product_brand').val() ? jQuery('#product_brand').val() : '',
				custom_cat				 : cat_arr,
				custom_feature_img		 : jQuery('#featureImg').val(),
				custom_var_img		     : var_img_arr,
				custom_textarea		     : jQuery('#custom_textarea').val(),
				nw_product_material		 : $('#nw-product-material').val(),
				nw_attribute_icons       : attribute_icons,
				nw_asw_product_status    : JSON.stringify(variant_product_status)
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
				$('#nw-asw-importer-list').empty().html(response);
				console.log(e);
				unblockModal();
				return;
			}

			// Success, continuing..
			var numberOfVariations = response['number_of_variations'];
			var editLink = response['edit_link'];
			var cacheID = response['cache_id'];

			// We are now creating a variation at a time, display a progressbar over the blocked modal
			$('#nw-asw-importer').block({
				message : '<div id="nw-progressbar"></div><div id="nw-progressbar-msg"></div>',
				overlayCSS : {
					background : '#fff',
					opacity    : 0.6
				}
			});

			var progressBar = $('#nw-progressbar');
			var progressMsg = $('#nw-progressbar-msg');

			$('.blockOverlay').addClass('nw-hide-spinner');
			progressBar.progressbar({
				value : 0,
				max : numberOfVariations,
			});
			progressMsg.html(0 + '/' + numberOfVariations);

			// Wrap creation of all variations in promises
			function createVariation(i) {
				return $.post(ajaxurl, {
					action 						: 'nw_create_variation',
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
					$('#nw-asw-importer-list').empty().html(response);
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
	$(document).on('click', '#nw-asw-importer .toggle-indicator', function() {
		$(this).toggleClass('open').parents('tr').nextUntil('.nw-color-row').toggle();
	});

	// Open up panel and show all sizes for a particular color, when label with ASW-number is clicked
	$(document).on('click', '#nw-asw-importer label.nw-asw-sku', function() {
		$(this).parents('td').next('.toggleIndicator').toggleClass('open');
		$(this).parents('tr').nextUntil('.nw-color-row').toggle();
	});

	// Check all checkboxes for sizes belonging to a color, when the master cb of that color is clicked
	$(document).on('click', '#nw-asw-importer .nw-color-row .product_attribute_variations', function() {
		$(this).parents('tr').nextUntil('.nw-color-row').find('input:enabled').prop('checked', $(this).prop('checked'));
	});

	// Display dash in parent checkbox to indicate only some of the child checkboxes are checked
	$(document).on('click', '#nw-asw-importer tbody tr:not(.nw-color-row) input:enabled', function() {
		var colorRow = $(this).parents('tr').prevAll('.nw-color-row:first');
		var inputs = colorRow.nextUntil('.nw-color-row').find('input');
		var checked = inputs.filter(':checked');
		colorRow = colorRow.find('input');

		if (checked.length == inputs.length) {
			colorRow.prop('checked', true).prop('indeterminate', false);
			$("#nw-product-name").prop('checked',true);
		}
		else if (checked.length) {
			colorRow.prop('checked', false).prop('indeterminate', true);
			$("#nw-product-name").prop('checked',true);
		}
		else {
			colorRow.prop('checked', false).prop('indeterminate', false);
		}
	});

	
});
