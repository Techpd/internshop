// Blog Ajax posts
var ppp = 1; // Post per page
var pageNumber = 2;
var blogFlag = true;

function edit_billing_checkout() {
	jQuery("#edit_billing_details").toggle();
	jQuery("#show_billing_details").toggle();
}

function saveBillingInfo() {
	//jQuery("#edit_billing_details").toggle();
	//jQuery("#show_billing_details").toggle();

	var payment_checkout_form = jQuery('#payment_checkout_form');


	//if(billing_first_name!="" && billing_last_name!="" && billing_address_1!="" && billing_postcode!="" && billing_city!="" && billing_phone!="" && billing_email!=""){
	jQuery.ajax({
		url: adminajax.ajax_url,
		data: payment_checkout_form.serialize() + '&action=checkout_pay_by_invoice', // form data
		type: payment_checkout_form.attr('method'), // POST
		dataType: 'json',
		success: function (data) {
			if (data.success) {
				//jQuery('#msg').html('');
				jQuery("#edit_billing_details").toggle();
				jQuery("#show_billing_details").toggle();
				window.location.reload();
			}
			else{
				if(jQuery('#pay_by_invoice').find('.woocommerce-error').length>0){
					jQuery('#msg').html('');
				}
				else{
					jQuery('#msg').html(data.msg);
				}
			}
		}
	});
	/* }
	else{
		jQuery("#place_order").submit();// validation will be called
	} */
}

jQuery(document).ready(function ($) {
	var maxLength = 100;

	setTimeout(()=>{

		jQuery("input[type='radio'][name='custom_var_pa_color']:first").trigger('click');
		//jQuery("input[type='radio'][name='custom_var_pa_size']:first").trigger('click');

	}, 1000);
	$(document).on('change', "input[type='radio'][name='custom_var_pa_color']", function (event) {
		setTimeout(()=> {
			var size = $("#pa_size option:eq(1)").val();console.log('-->'+size);
			$("input[name=custom_var_pa_size][value=" + size + "]").attr('checked', 'checked').click();
			var val = jQuery("input[type='radio'][name='custom_var_pa_size']:checked").val();
			jQuery('#pa_size').val(val);
			jQuery('#pa_size').trigger('change');
		}, 10);
	});

	$(".show-read-more").each(function(){
		var regex = /(<([^>]+)>)/ig;
		var body = $(".show-read-more").html();
		var result = body.replace(regex, "");
		var removedStr='';
		//alert(result);
		var myStr = $(this).html();
		//var myStr = result;
		if($.trim(myStr).length > maxLength){
			var newStr = myStr.substring(0, maxLength);
			removedStr = myStr.substring(maxLength, $.trim(myStr).length);
			$(this).empty().html(newStr);
			//	$(this).append(' <a href="javascript:void(0);" class="read-more">Les mer</a>');
			$('.read-more-div').removeClass('display-read');

			$(this).append('<span class="more-text">' + removedStr + '</span>');

		}
	});
	$(".read-more").click(function(){
		//alert('UIS');

		$(".more-text").css('display','block');
		$('.read-more-div').addClass('display-read');
		$('.read-less-div').removeClass('display-read');
	});

	$(".read-less").click(function(){
		$(".more-text").css('display','none');
		$('.read-more-div').removeClass('display-read');
		$('.read-less-div').addClass('display-read');
	});





	jQuery(document).on('change', '.custom_var_wrap input[type="radio"]', function (event) {
		changeVariation(jQuery(this));
		//console.log(this);

	});

	jQuery(document).on('woocommerce_update_variation_values', function () {
		jQuery('.summary .variations select').each(function (index, el) {
			attr_name = jQuery(el).data('attribute_name');
			jQuery('.custom_var_wrap[data-attribute_name="' + attr_name + '"] label').addClass('inactive');
			jQuery(this).find('option').each(function () {
				val = jQuery(this).attr('value');
				if (!(jQuery(this).is(':disabled'))) {
					jQuery('.custom_var_wrap[data-attribute_name="' + attr_name + '"]').find('input:radio').filter('[value="' + val + '"]').parent().removeClass('inactive');
				}
			});
		});

	});

	jQuery(document).on('click', '.custom_var_wrap[name=attribute_pa_color] input[type=radio]', function () {
		jQuery('#simple_attr_div_0').html(jQuery(this).attr('color'));
	});

	/*** Change of Variations ***/
	function changeVariation(obj) {
		console.log(obj);
		console.log('Pbjec avive');
		var attribute_name = jQuery(obj).parents('.custom_var_wrap').data('attribute_name');
		console.log(attribute_name);
		console.log('dasd too');
		var v_value = jQuery(obj).val();
		console.log(v_value);

		if(attribute_name=='attribute_pa_color'){
			var n_value = jQuery(obj).attr('color');

			/* jQuery('label[for="pa_color"]').text('Velg farge: '+n_value); */
		}


		jQuery('.summary select[name="' + attribute_name + '"]').val('');
		jQuery('.summary select[name="' + attribute_name + '"]').trigger('change');
		jQuery('.summary select[name="' + attribute_name + '"]').val(v_value);
		jQuery('.summary select[name="' + attribute_name + '"]').trigger('change');

		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').val('');
		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').trigger('change');
		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').val(v_value);
		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').trigger('change');

		jQuery('.custom_var_wrap[data-attribute_name="' + attribute_name + '"]').each(function () {
			jQuery(obj).find('input:radio').filter('[value="' + v_value + '"]').prop('checked', true);
		});
		var var_id = jQuery('input[name="variation_id"]').val();

		// active variation stock display
		jQuery('.variations_box .variation_stock_wrap .variation_stock').removeClass('active');
		if (var_id) {
			jQuery('.variation_stock_wrap').find('#' + var_id).addClass('active');
		}
		jQuery('.custom_single_product_description .product_description').removeClass('active');

		if (var_id) {
			jQuery('.custom_single_product_description #desc-' + var_id).addClass('active');
		}

		/*jsondata = {
			 action: 'get_variation_price_data'
		}
        var formData = new FormData();
		formData.append('action', 'get_variation_price_data');*/

		if(var_id){
			/*formData.append('variation_id', var_id);
             //alert('Heraaaasssde');
             jQuery.ajax({
             url: adminajax.ajax_url,
             type: 'POST',
             dataType:"json",
             data: formData,
             processData: false,
             contentType: false,
             success: function (data) {
             // location.reload();
             }
         });*/
		}
	}

	// handling login / registration
	$(document).on('click', '#popup_login', function (e) {
		e.preventDefault();

		$('#login_form_errors').hide();
		$('#login_form_errors').html('');
		$('.login-field').each(function () {
			$(this).removeClass('error-field');
		});

		$('.login-errors').hide();

		var formData = new FormData($('#' + $('#craft-login').attr("id"))[0]);
		formData.append('action', 'craft_ajax_login');
		$.ajax({
			url: adminajax.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (data) {
				var data = JSON.parse(data);
				if (data.errors.length) {

					var err_html = '<ul>';
					for (var i = 0; i < data.errors.length; i++) {
						err_html += '<li>' + data.errors[i] + '</li>';
					}
					err_html += '</ul>';

					$('#login_form_errors').html(err_html);
					$('#login_form_errors').show();

					if (data.err_flds) {
						for (var i = 0; i < data.err_flds.length; i++) {
							if ($('input[name=' + data.err_flds[i] + ']').length) {
								$('input[name=' + data.err_flds[i] + ']').addClass('error-field');
								$('.' + data.err_flds[i] + '-error').show();
							}
						}
					}

				} else {
					console.log("all good and submitting form");
					//$('#craft-login').submit();
					window.location.href=data.redirect_link;
				}
			},
			error: function (dataError) {
				if (dataError.status == 401)
					window.location.reload();
				else
					console.log(dataError);
			}
		});
	});

	$(document).on('submit', '#craft-register', function (e) {
		e.preventDefault();

		$('#register_form_errors').hide();
		$('#register_form_errors').html('');

		var formData = new FormData($('#' + $(this).attr("id"))[0]);
		formData.append('action', 'craft_ajax_register');
		$.ajax({
			url: adminajax.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (data) {
				var data = JSON.parse(data);
				if (data.errors.length) {

					var err_html = '<ul>';
					for (var i = 0; i < data.errors.length; i++) {
						err_html += '<li>' + data.errors[i] + '</li>';
					}
					err_html += '</ul>';

					$('#register_form_errors').html(err_html);
					$('#register_form_errors').show();

					if (data.err_flds) {
						for (var i = 0; i < data.err_flds.length; i++) {
							if ($('input[name=' + data.err_flds[i] + ']').length) {
								$('input[name=' + data.err_flds[i] + ']').addClass('error-field');
								$('.' + data.err_flds[i] + '-error').show();
							}
						}
					}

				} else if (data.redirect_link != "") {
					window.location.href = data.redirect_link;
				}
			},
			error: function (dataError) {
				if (dataError.status == 401)
					window.location.reload();
				else
					console.log(dataError);
			}
		});
	});


	jQuery(document).on('click', '#place_order', function (e) {

		setTimeout(function() {
			if(jQuery('#pay_by_invoice').find('.woocommerce-error').length>0){
				jQuery("#edit_billing_details").show();
				jQuery("#show_billing_details").hide();
				jQuery('#msg').html('');
			}
		}, 2000);
	});


	jQuery('label[for="pa_color"]').text('Velg farge');
});






function load_more_products(){
	blogFlag = false;
	var str = '&pageNumber=' + pageNumber + '&ppp=' + ppp + '&action=more_products_ajax&cat='+jQuery("#cat").val();
	jQuery('body').addClass('ajax_loading');
	jQuery.ajax({
		type: "POST",
		dataType: "json",
		url: adminajax.ajax_url,
		data: str,
		success: function(data){
			jQuery('body').removeClass('ajax_loading');
			if(data.html)
			{
				blogFlag = true;
				jQuery(".products").append(data.html);
				pageNumber++;
			}

			if (data.last == true){
				jQuery('#load_more').remove();
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			//loader.html(jqXHR + " :: " + textStatus + " :: " + errorThrown);
		}

	});
	return false;
}

jQuery(document).on('change', '#pa_size, #pa_color', function (event) {
	var $options = jQuery("#pa_color_all > option").clone();
	jQuery('#pa_color').html($options);
	var val = jQuery("input[type='radio'][name='custom_var_pa_color']:checked").val();
	jQuery('#pa_color').val(val);
	jQuery('#pa_color_all').val(val);
	jQuery('.variations').find('label[for="pa_color"]').text('Velg farge: '+jQuery('#pa_color option:selected').text());
});

// Checkout Functions\
	 jQuery(document).ready(function() {
        if (jQuery('.check-this').length) {
            jQuery('.check-this').click(function() {
                jQuery(this).toggleClass('checked');
                if (jQuery(this).parent('.choose-coupon').length) {
                    if (jQuery(this).hasClass('checked')) {
                        jQuery('.coupon-details').show();
                    } else {
                        jQuery('.coupon-details').hide();
                    }
                }
                if (jQuery(this).parent('.after-checkout-gift-card-form').length) {
                    if (jQuery(this).hasClass('checked')) {
                        jQuery('.ywgc_enter_code').show();
                    } else {
                        jQuery('.ywgc_enter_code').hide();
                    }
                }
                

            });


        }
     });


(function($) {
    $(document).ready(function() {

        let el = jQuery('.top-links-anim-bar .links-container .link-wrapper');
        let n = el.children().length;
        let i = 0;

        jQuery('.top-links-anim-bar').css('--i', i).css('--n', n);

        function interval_action() { //auto
            jQuery('.top-links-anim-bar').css('--i', (i * -1));

            if (i == (n)) {
                i = 0;
                jQuery('.top-links-anim-bar').addClass('no-effect').css('--i', 0);
                // el.delay(3000).fadeOut(500, function(){
                // }).delay(1000).fadeIn(500);
            } else {
                jQuery('.top-links-anim-bar').removeClass('no-effect')
                i++;
            }
        }

        let watch = 5000;
        let interval = setInterval(interval_action, watch);

        $('.top-links-anim-bar .left-arrow').on('click', function() {
            // console.log('left arrow');
            i--;
            if (i < 0) {
                i = n - 1;
                jQuery('.top-links-anim-bar').addClass('no-effect').css('--i', i);
            } else {
                jQuery('.top-links-anim-bar').removeClass('no-effect');
            }

            clearInterval(interval);
            interval = setInterval(interval_action, watch);

            jQuery('.top-links-anim-bar').css('--i', (i * -1));
        })

        $('.top-links-anim-bar .right-arrow').on('click', function() {
            // console.log('right arrow');
            i++;
            if (i > (n - 1)) {
                i = 0;
                jQuery('.top-links-anim-bar').addClass('no-effect').css('--i', 0);
            } else {
                jQuery('.top-links-anim-bar').removeClass('no-effect');
            }

            clearInterval(interval);
            interval = setInterval(interval_action, watch);

            jQuery('.top-links-anim-bar').css('--i', (i * -1));
        })

        jQuery('.front-page-products.new-products-variants div[data-varient-url]').each((i, ele) => {
            // console.log( ele );          
            jQuery(ele).find('.inner-wrap a').attr('href', jQuery(ele).data('varient-url'))
        })
        
 jQuery(document).ready(function(){
    jQuery('.item.slick-slide.slick-current.slick-active').css({"opacity" : "1"});
    jQuery('.custom-width-thumb').find('.slick-track .item').each(function(){
        jQuery(this).css({"opacity" : "0"});
    });

    jQuery('.item.slick-slide.slick-current.slick-active').css({"opacity" : "1"});
});


    })

})(jQuery);