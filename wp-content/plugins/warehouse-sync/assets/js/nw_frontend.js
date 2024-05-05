jQuery(document).ready(function($) {
	/**
	 * Enable/disable submit button if any product have been selected for return
	 *
	 */
	$('input.newwave-return-products-cb').on('change', function() {
		if ($('input.newwave-return-products-cb:checked').length > 0) {
			$('#newwave-return-products').attr('disabled', false).css({'cursor' : 'pointer', 'opacity' : 1});
		}
		else {
			$('#newwave-return-products').attr('disabled', true).css({'cursor' : 'default', 'opacity' : 0.5});
		}
	});
	$('#newwave-return-products').attr('disabled', true).css({'cursor' : 'default', 'opacity' : 0.5});

	/**
	 * Update class when a shipping option is selected
	 *
	 */
	$('.newwave-shipping-address-box').on('click', function() {
		$(this).find('input[type="radio"]').prop('checked', true);
		$('.newwave-shipping-address-box').removeClass('selected');
		$(this).addClass('selected');
	});

	/**
	 * Trigger select when any child element of a shipping option is selected
	 *
	 */
	$('.newwave-shipping-address-box *').on('click', function() {
		$(this).parents('.newwave-shipping-address-box').trigger('click');
	});
});


function productAccess(){
	var productAccess =  jQuery('#product_access_form');
	var club = getURLParameter('klubb');
	 jQuery.ajax({
            url:adminajax.ajax_url,
            //url:'https://staging.internshop.no/wp-admin/admin-ajax.php',
			//url:productAccess.attr('action'),
            data:productAccess.serialize()+'&product_access=1&club='+club, // form data
            type:productAccess.attr('method'), // POST
			dataType:'json',
            success:function(data){
				if(data.success){
					//jQuery('#msg').html('<h3 style="color:green;">'+data.msg+'</h3>');
					//setTimeout(function () {
						window.location.href = "/butikk/";
					//}, 5000);
				}
				else
					jQuery('#msg').html('<h3 style="color:red;">'+data.msg+'</h3>');
            }
        });
}

// function to ge the browser uri
function getURLParameter(name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [, ""])[1].replace(/\+/g, '%20')) || null;
}