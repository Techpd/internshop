jQuery(document).ready(function($) {
	/**
	 * Display helper tooltips even if WooCommerce scripts are not loaded
	 *
	 */
	if ($().tipTip) {
		$('.woocommerce-help-tip').tipTip({
			'attribute': 'data-tip',
			'fadeIn': 50,
			'fadeOut': 50,
			'delay': 200
		});
	}

	/**
	 * Admin bar shop selector; trigger submit of form when a shop is selected
	 *
	 */
	$('select[name="nw_admin_switch_shop_id"]').on('select2:select', function (e) {
		$('#nw_shop_select_form').submit();
	});

	/**
	 * Enable select2 for all elements with class 'nw-select2'
	 *
	 */
	if ($().select2) {
		$('.nw-select2').select2({dropdownCssClass : 'nw-select2'});
 	}

	/**
	 * Enable switchbutton
	 *
	 */
	if ($().switchButton) {
		$('.nw-toggle').each(function() {
			let onLabel = $(this).data('toggleOn');
			let offLabel = $(this).data('toggleOff');
			$(this).switchButton({
				on_label: onLabel,
				off_label: offLabel,
			});
		});
	}

	/**
	 * Check all checkboxes in the same column when a master checkbox is checked
	 *
	 */
	$(document).on('change', 'table input[type="checkbox"].nw-check-all-vertical', function() {
		let idx = $(this).closest('th, tr').index();
		let table = $(this).closest('table');
		table.find('td:nth-child('+(idx+1)+') input[type="checkbox"]:not(.nw-check-all-vertical):not(:disabled)').attr('checked', $(this).is(':checked'));
	});

	/**
	 * Check all checkboxes in the same row when a master checkbox is checked
	 *
	 */
	$(document).on('change', 'table input[type="checkbox"]', function() {
		if (!$(this).is(':checked')) {
			let idx = $(this).closest('td, tr').index();
			let table = $(this).closest('table');
			table.find('th:nth-child('+(idx+1)+') input[type="checkbox"].nw-check-all-vertical, tr:nth-child('+(idx+1)+') input[type="checkbox"].nw-check-all-vertical').attr('checked', false);
		}
	});

	/**
	 * Enable media upload
	 *
	 */
	if (wp.media) {
		var file_frame;
		var set_to_post_id = 0; // Set this

		$('.nw-upload-media').on('click', function(event){
			event.preventDefault();
			// If the media frame already exists, reopen it.
			if (file_frame) {
				// Set the post ID to what we want
				file_frame.uploader.uploader.param('post_id', set_to_post_id);
				// Open frame
				file_frame.open();
				return;
			}

			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media({
				multiple: false	// Set to true to allow multiple files to be selected
			});

			// When an image is selected, run a callback.
			file_frame.on('select', function() {
				// We set multiple to false so only get one image from the uploader
				attachment = file_frame.state().get('selection').first().toJSON();

				// Do something with attachment.id and/or attachment.url here
				$('#image-preview').attr('src', attachment.url).css('width', 'auto');
				$('#image_attachment_id').val(attachment.id);
			});

			// Finally, open the modal
			file_frame.open();
		});
	}
});
