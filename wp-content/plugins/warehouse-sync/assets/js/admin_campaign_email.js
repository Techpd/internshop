jQuery(document).ready(function($) {

	$('#nw-open-campaign-email-modal').on('click', function() {
		$(document.body).WCBackboneModal({
			template : 'nw-modal-campaign-email',
		});
	});

	// Enable send button once at least one checkbox is ticked
	$(document).on('change', '#nw-campaign-email td input[type="checkbox"]', function() {
		if ($('#nw-campaign-email td input[type="checkbox"]:checked').length > 0) {
			disableSendButton = false;
			$('#nw-send-emails').prop('disabled', false);
		}
		else {
			disableSendButton = true;
			$('#nw-send-emails').prop('disabled', true);
		}
	});

	var disableSendButton = true;
	$(document).on('click', '#nw-send-emails', function() {
		var nonce = $('#nw-send-emails').data('nonce');

		// Disable multiple clicks on button while sending
		$('#nw-send-emails').addClass('disabled');
		if (disableSendButton) {
			return;
		}
		else {
			disableSendButton = true;
		}

		var emailAddresses = $('#nw-campaign-email input[type="checkbox"]:checked');
		var emailsSent = 0;

		if (emailAddresses.length == 0) {
			return;
		}

		// Setup visual feedback for the progress
		$('#nw-modal-campaign-email').block({
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
			value : emailsSent,
			max : emailAddresses.length,
		});
		progressMsg.html(emailsSent+'/'+emailAddresses.length);

		// Send email, wrapped in a promise
		function sendEmail(address) {
		 	return $.post(ajaxurl, {
				action    : 'nw_send_campaign_email',
				address   : address,
				security	: nonce,
			}, function(response) {
					if (response === 0) {
						return $.Deferred().reject(address);
					}
			});
		}

		// Clear the cached email
		let clearCache = $.post(ajaxurl, {
			action : 'nw_clear_email_cache',
			security : nonce,
		});

		// After cleared cache, send emails in sequence; one at a time
		clearCache.then(function() {
			var promise = $.Deferred().resolve();
			emailAddresses.each(function() {
				var address = $(this).data('sendTo');
				promise = promise.fail(function(error) {
					alert(error);
					console.log(error);
					$('#nw-modal-campaign-email').unblock();
				}).then(function(){
					progressBar.progressbar('value', ++emailsSent);
					progressMsg.html(emailsSent+'/'+emailAddresses.length);
					return sendEmail(address);
				});
			});
			promise.then(function() {
				$('#nw-modal-campaign-email').unblock();
			});
		});
	});
});
