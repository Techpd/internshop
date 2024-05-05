jQuery(document).ready(function($) {
	/**
	 * Show/hide input field for maximum possible register users for a club,
	 * if user registration capping is either being enabled or disabled
	 *
	 */
	$('input[name="nw_registration_capping"]').change(function() {
		if (this.checked) {
			$('#nw-max-users').show();
			$('#nw-users-registered').hide();
		}
		else {
			$('#nw-max-users').hide();
			$('#nw-users-registered').show();
		}
	});

	/**
	 * Move and add some styling to the featured image metabox
	 *
	 */
	$('#postimagediv').appendTo('#row-nw_club_logo > td');
	if (!$('#set-post-thumbnail').has('img')) {
		$('#set-post-thumbnail').addClass('button');
	}
	$('#remove-post-thumbnail').addClass('button');
	$('#row-nw_club_logo').on('DOMNodeInserted DOMNodeRemoved', function(){
		if (!$('#set-post-thumbnail').has('img')) {
			$('#set-post-thumbnail').addClass('button');
		}
		$('#remove-post-thumbnail').addClass('button');
	});

	/**
	 * Validate fields based on the ARIA attribute pattern
	 *
	 */
	$('input[pattern]').change(function() {
		var regex = new RegExp($(this).attr('pattern'));
		var msg = $('#' + $(this).attr('aria-describedby'));

		if (regex.test($(this).val())) {
			$(this).attr('aria-invalid', false).tipTip({
				'defaultPosition' : 'top',
				'attribute' :  'data-tip',
				'activation' : 'focus',
				'fadeIn' : 50,
				'fadeOut' : 50,
				'delay' : 0
			}).focus();
		}
		else {
			$(this).attr('aria-invalid', true).tipTip();
		}
	}).keyup(function() {
		$(this).change();
	});

	/**
	 * Copy registration code when 'copy' button is clicked
	 *
	 */
	var nw_copy_temp = $('<input />');
	$('body').append(nw_copy_temp);
	$('#nw-copy-reg-code').click(function(e) {
		e.preventDefault();
		nw_copy_temp.val($('#nw-reg-code').val()).select();
		document.execCommand('copy');
		$(this).next('span').fadeIn(100).delay(1000).fadeOut();
	});

	/**
	 * Perform AJAX call to create a new registration code
	 *
	 */
	$('#nw-reset-reg-code').click(function(e) {
		e.preventDefault();
		var rx = new RegExp(/post=(\d+)/i);

		if (confirm($(this).data('nwAlert'))) {

			$('#nw-copy-reg-code, #nw-reset-reg-code').prop('disabled', true);
			var nonce = $(this).data('nonce');
			var data = {
				post_id               : rx.exec(window.location.search)[1],
				action                : 'nw_reset_registration_code',
				security : nonce,
			};

			$.post(ajaxurl, data, function(response) {
				$('#nw-reg-code').val(response);
				$('#nw-copy-reg-code, #nw-reset-reg-code').prop('disabled', false);
				alert('Done!');
			});
		};
	});
	
	/* To move banner image field before save button - START */
	if(jQuery('#post_type').val() == 'nw_club')
	{
		jQuery(".acf-postbox").insertAfter(jQuery("#row-club_email").closest('.form-table tr'));
	}
	/* To move banner image field before save button - END */

	// PLANASD-484 --- handle checkbox for reset in club/vendor
	$(document).on("click", ".reset_all_clubs", function() {
		if($(this).is(":checked")) {
			$('.reset_all_products').each(function() {
				$(this).prop("checked", true);
			});
		} else {
			$('.reset_all_products').each(function() {
				$(this).prop("checked", false);
			});
		}
	});
});
