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
			else {
				if (jQuery('#pay_by_invoice').find('.woocommerce-error').length > 0) {
					jQuery('#msg').html('');
				}
				else {
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

// jQuery(document).ready(function ($) {
// 	var maxLength = 100;
//         var myStr = "";

// 	setTimeout(()=>{


// 		// jQuery(".first.slider-items.is-selected").trigger("click");
// 		//jQuery("input[type='radio'][name='custom_var_pa_size']:first").trigger('click');

// 	}, 100);


// 	$(document).on('change', "input[type='radio'][name='custom_var_pa_color']", function (event) {
// 		var slider = $(".woocommerce-product-gallery__wrapper .flickity-slider");
// 		var product_thumb = $(".product-thumbnails .flickity-slider");
// 		var translateX = 100;

// 		// Remove old images except the first child
// 		// product_thumb.children().not(":first-child").remove();
// 		product_thumb.find('.col:not(:first-child)').remove();

// 		setTimeout(()=> {
// 			var size = $("#pa_size option:eq(1)").val();
// 			$("input[name=custom_var_pa_size][value=" + size + "]").attr('checked', 'checked').click();
// 			var val = jQuery("input[type='radio'][name='custom_var_pa_size']:checked").val();
// 			jQuery('#pa_size').val(val);
// 			jQuery('#pa_size').trigger('change');

// 			//new code
// 			var pa_color = jQuery("input[type='radio'][name='custom_var_pa_color']:checked").val();
// 			var product_id = jQuery("input[type='hidden'][name='product_id']").val();

// 			console.log("pa_color", pa_color);
// 			console.log("product_id", product_id);

// 			$.ajax({
// 				url: adminajax.ajax_url, // WordPress AJAX URL
// 				type: 'POST',
// 				data: {
// 					action: 'get_meta_key_by_color',
// 					pa_color: pa_color,
// 					product_id: product_id
// 				},
// 				success: function(response) {
// 					console.log('Response:', response.data);

// 					if (response.success && response.data.length > 0) {
// 						response.data.forEach(function(imageUrl) {
// 							var col = $('<div class="woocommerce-product-gallery__image slide" aria-hidden="true" style="position: absolute; left: 0px; transform: translateX(' + translateX + '%);"><a><img src="' + imageUrl + '" alt="" width="247" height="296" class="attachment-woocommerce_thumbnail"></a></div>');
// 							slider.append(col);

// 							var col2 = $('<div class="col" aria-hidden="true" style="position: absolute; left: 0px; transform: translateX(' + translateX + '%);"><a><img src="' + imageUrl + '" alt="" width="247" height="296" class="attachment-woocommerce_thumbnail"></a></div>');
// 							product_thumb.append(col2);
// 							translateX += 100;
// 						});
// 					} else {
// 						// product_thumb.children().not(":first-child").remove();
// 						product_thumb.find('.col:not(:first-child)').remove();
// 						console.error('No image URLs found in response');
// 					}
// 				},
// 				error: function(xhr, status, error) {
// 					console.error('AJAX Error:', status, error);
// 				}
// 			});
// 			//new code ends
// 		}, 10);
// 	});

// 	jQuery("input[type='radio'][name='custom_var_pa_color']:first").trigger('click');//uncomment this

// 	$(".show-read-more").each(function(){
// 		var regex = /(<([^>]+)>)/ig;
// 		var body = $(".show-read-more").html();
// 		var result = body.replace(regex, "");
// 		var removedStr='';
// 		console.log(result);
// 		myStr = $(this).html();
//                 console.log(myStr);
// 		//var myStr = result;
//                 console.log($.trim(myStr).length);
// 		if($.trim(myStr).length > maxLength){
//                     console.log("length > maxlength");

//                         var openTag = 0, closeTag = 0,i=0;
//                         for(i; i<maxLength; i++)
//                         {
//                             if(myStr[i] == "<")
//                                 openTag++;
//                             if(myStr[i] == ">")
//                                 closeTag++;
//                         }
//                         if(openTag > closeTag)
//                         {
//                             while(myStr[i] != ">")
//                                 i++;
//                         }
//                         maxLength = i+1;


// 			var newStr = myStr.substring(0, maxLength);
//                         console.log("newStr:"+newStr);
// 			removedStr = myStr.substring(maxLength, $.trim(myStr).length);
//                         console.log("removedStr:"+removedStr);
// 			$(this).empty().html(newStr);
// 			//	$(this).append(' <a href="javascript:void(0);" class="read-more">Les mer</a>');
// 			$('.read-more-div').removeClass('display-read');

// //			$(this).append('<span class="more-text">' + removedStr + '</span>');

// 		}
// 	});
// 	$(".read-more").click(function(){
// 		//alert('UIS');

// //		$(".more-text").css('display','block');
//                 $(".show-read-more").empty().html(myStr);
// 		$('.read-more-div').addClass('display-read');
// 		$('.read-less-div').removeClass('display-read');
// 	});

// 	$(".read-less").click(function(){
// //		$(".more-text").css('display','none');
//                 var newStr = myStr.substring(0, maxLength);
//                 $(".show-read-more").empty().html(newStr);
// 		$('.read-more-div').removeClass('display-read');
// 		$('.read-less-div').addClass('display-read');
// 	});





// 	jQuery(document).on('change', '.custom_var_wrap input[type="radio"]', function (event) {
// 		changeVariation(jQuery(this));
// 		//console.log(this);

// 	});

// 	jQuery(document).on('woocommerce_update_variation_values', function () {
// 		jQuery('.summary .variations select').each(function (index, el) {
// 			attr_name = jQuery(el).data('attribute_name');
// 			jQuery('.custom_var_wrap[data-attribute_name="' + attr_name + '"] label').addClass('inactive');
// 			jQuery(this).find('option').each(function () {
// 				val = jQuery(this).attr('value');
// 				if (!(jQuery(this).is(':disabled'))) {
// 					jQuery('.custom_var_wrap[data-attribute_name="' + attr_name + '"]').find('input:radio').filter('[value="' + val + '"]').parent().removeClass('inactive');
// 				}
// 			});
// 		});

// 	});

// 	jQuery(document).on('click', '.custom_var_wrap[name=attribute_pa_color] input[type=radio]', function () {
// 		jQuery('#simple_attr_div_0').html(jQuery(this).attr('color'));
// 	});

// 	jQuery(document).on('click', '.flickity-slider .col',function(){
// 		var sel_img = jQuery(this).find('img').attr('src');
// 		console.log(jQuery(this).find('img').attr('src'));
// 		console.log(jQuery('.flickity-slider .woocommerce-product-gallery__image.is-selected').find('img'));
// 		jQuery('.flickity-slider .woocommerce-product-gallery__image.is-selected').find('img').attr("src",sel_img);
// 		jQuery('.flickity-slider .woocommerce-product-gallery__image.is-selected').find('img').attr("srcset","");
// 	});
// 	// jQuery(".flickity-slider .col").click(function(){

// 	// });

// 	/*** Change of Variations ***/
// 	function changeVariation(obj) {
// 		console.log(obj);
// 		console.log('Pbjec avive');
// 		var attribute_name = jQuery(obj).parents('.custom_var_wrap').data('attribute_name');
// 		console.log(attribute_name);
// 		console.log('dasd too');
// 		var v_value = jQuery(obj).val();
// 		console.log(v_value);

// 		if(attribute_name=='attribute_pa_color'){
// 			var n_value = jQuery(obj).attr('color');

// 			/* jQuery('label[for="pa_color"]').text('Velg farge: '+n_value); */
// 		}


// 		jQuery('.summary select[name="' + attribute_name + '"]').val('');
// 		jQuery('.summary select[name="' + attribute_name + '"]').trigger('change');
// 		jQuery('.summary select[name="' + attribute_name + '"]').val(v_value);
// 		jQuery('.summary select[name="' + attribute_name + '"]').trigger('change');

// 		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').val('');
// 		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').trigger('change');
// 		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').val(v_value);
// 		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').trigger('change');

// 		jQuery('.custom_var_wrap[data-attribute_name="' + attribute_name + '"]').each(function () {
// 			jQuery(obj).find('input:radio').filter('[value="' + v_value + '"]').prop('checked', true);
// 		});
// 		var var_id = jQuery('input[name="variation_id"]').val();

// 		// active variation stock display
// 		jQuery('.variations_box .variation_stock_wrap .variation_stock').removeClass('active');
// 		if (var_id) {
// 			jQuery('.variation_stock_wrap').find('#' + var_id).addClass('active');
// 		}
// 		jQuery('.custom_single_product_description .product_description').removeClass('active');

// 		if (var_id) {
// 			jQuery('.custom_single_product_description #desc-' + var_id).addClass('active');
// 		}

// 		/*jsondata = {
// 			 action: 'get_variation_price_data'
// 		}
//         var formData = new FormData();
// 		formData.append('action', 'get_variation_price_data');*/

// 		if(var_id){
// 			/*formData.append('variation_id', var_id);
//              //alert('Heraaaasssde');
//              jQuery.ajax({
//              url: adminajax.ajax_url,
//              type: 'POST',
//              dataType:"json",
//              data: formData,
//              processData: false,
//              contentType: false,
//              success: function (data) {
//              // location.reload();
//              }
//          });*/
// 		}
// 	}

// 	// handling login / registration
// 	$(document).on('click', '#popup_login', function (e) {
// 		e.preventDefault();

// 		$('#login_form_errors').hide();
// 		$('#login_form_errors').html('');
// 		$('.login-field').each(function () {
// 			$(this).removeClass('error-field');
// 		});

// 		$('.login-errors').hide();

// 		var formData = new FormData($('#' + $('#craft-login').attr("id"))[0]);
// 		formData.append('action', 'craft_ajax_login');
// 		$.ajax({
// 			url: adminajax.ajax_url,
// 			type: 'POST',
// 			data: formData,
// 			processData: false,
// 			contentType: false,
// 			success: function (data) {
// 				var data = JSON.parse(data);
// 				if (data.errors.length) {

// 					var err_html = '<ul>';
// 					for (var i = 0; i < data.errors.length; i++) {
// 						err_html += '<li>' + data.errors[i] + '</li>';
// 					}
// 					err_html += '</ul>';

// 					$('#login_form_errors').html(err_html);
// 					$('#login_form_errors').show();

// 					if (data.err_flds) {
// 						for (var i = 0; i < data.err_flds.length; i++) {
// 							if ($('input[name=' + data.err_flds[i] + ']').length) {
// 								$('input[name=' + data.err_flds[i] + ']').addClass('error-field');
// 								$('.' + data.err_flds[i] + '-error').show();
// 							}
// 						}
// 					}

// 				} else {
// 					console.log("all good and submitting form");
// 					//$('#craft-login').submit();
// 					window.location.href=data.redirect_link;
// 				}
// 			},
// 			error: function (dataError) {
// 				if (dataError.status == 401)
// 					window.location.reload();
// 				else
// 					console.log(dataError);
// 			}
// 		});
// 	});

// 	$(document).on('submit', '#craft-register', function (e) {
// 		e.preventDefault();

// 		$('#register_form_errors').hide();
// 		$('#register_form_errors').html('');

// 		var formData = new FormData($('#' + $(this).attr("id"))[0]);
// 		formData.append('action', 'craft_ajax_register');
// 		$.ajax({
// 			url: adminajax.ajax_url,
// 			type: 'POST',
// 			data: formData,
// 			processData: false,
// 			contentType: false,
// 			success: function (data) {
// 				var data = JSON.parse(data);
// 				if (data.errors.length) {

// 					var err_html = '<ul>';
// 					for (var i = 0; i < data.errors.length; i++) {
// 						err_html += '<li>' + data.errors[i] + '</li>';
// 					}
// 					err_html += '</ul>';

// 					$('#register_form_errors').html(err_html);
// 					$('#register_form_errors').show();

// 					if (data.err_flds) {
// 						for (var i = 0; i < data.err_flds.length; i++) {
// 							if ($('input[name=' + data.err_flds[i] + ']').length) {
// 								$('input[name=' + data.err_flds[i] + ']').addClass('error-field');
// 								$('.' + data.err_flds[i] + '-error').show();
// 							}
// 						}
// 					}

// 				} else if (data.redirect_link != "") {
// 					window.location.href = data.redirect_link;
// 				}
// 			},
// 			error: function (dataError) {
// 				if (dataError.status == 401)
// 					window.location.reload();
// 				else
// 					console.log(dataError);
// 			}
// 		});
// 	});


// 	jQuery(document).on('click', '#place_order', function (e) {

// 		setTimeout(function() {
// 			if(jQuery('#pay_by_invoice').find('.woocommerce-error').length>0){
// 				jQuery("#edit_billing_details").show();
// 				jQuery("#show_billing_details").hide();
// 				jQuery('#msg').html('');
// 			}
// 		}, 2000);
// 	});


// 	jQuery('label[for="pa_color"]').text('Velg farge');
// });




function load_more_products() {
	blogFlag = false;
	var str = '&pageNumber=' + pageNumber + '&ppp=' + ppp + '&action=more_products_ajax&cat=' + jQuery("#cat").val();
	jQuery('body').addClass('ajax_loading');
	jQuery.ajax({
		type: "POST",
		dataType: "json",
		url: adminajax.ajax_url,
		data: str,
		success: function (data) {
			jQuery('body').removeClass('ajax_loading');
			if (data.html) {
				blogFlag = true;
				jQuery(".products").append(data.html);
				pageNumber++;
			}

			if (data.last == true) {
				jQuery('#load_more').remove();
			}
		},
		error: function (jqXHR, textStatus, errorThrown) {
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
	jQuery('.variations').find('label[for="pa_color"]').text('Velg farge: ' + jQuery('#pa_color option:selected').text());
});

// Checkout Functions\
jQuery(document).ready(function () {
	if (jQuery('.check-this').length) {
		jQuery('.check-this').click(function () {
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


(function ($) {
	$(document).ready(function () {

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

		$('.top-links-anim-bar .left-arrow').on('click', function () {
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

		$('.top-links-anim-bar .right-arrow').on('click', function () {
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

		jQuery(document).ready(function () {
			jQuery('.item.slick-slide.slick-current.slick-active').css({ "opacity": "1" });
			jQuery('.custom-width-thumb').find('.slick-track .item').each(function () {
				jQuery(this).css({ "opacity": "0" });
			});

			jQuery('.item.slick-slide.slick-current.slick-active').css({ "opacity": "1" });
		});
	});
})(jQuery);

var color_selection_by_default = 0;
(function ($) {
	$(document).ready(function () {
		window.up_slicked = false;

		jQuery(".upsells_product_wrap li.type-product_variation").addClass(
			"type-product  product-type-variable"
		);

		$(".variations label span").on("click", function () {
			// if (jQuery('.upsells_product_wrap ul.products').hasClass('slick-initialized')) {
			//  jQuery('.upsells_product_wrap .upsells ul.products').slick('destroy')
			// }

			console.log("varaiation label click");

			if (jQuery(".upsells_product_wrap .ucs-up-sells.products").html()) {
				jQuery(".upsells_product_wrap li.type-product_variation").addClass(
					"type-product  product-type-variable"
				);
				slic_for_upsell();
			}
		});

		$("body").on(
			"DOMSubtreeModified",
			".upsells_product_wrap .ucs-up-sells.products",
			function () {
				jQuery(".upsells_product_wrap li.type-product_variation").addClass(
					"type-product  product-type-variable"
				);

				console.log("subtreemoidified");

				// if( !window.up_slicked ){
				//  console.log('sliding here', window.up_slicked);
				//  window.up_slicked = true;
				//  slic_for_upsell();
				// }
			}
		);

		if (jQuery(window).width() < 768) {
			jQuery(".upsells_product_wrap .related .products").slick({
				infinite: true,
				arrows: false,
				slidesToShow: 1,
				centerPadding: "115px",
				centerMode: true,
				responsive: [
					{
						breakpoint: 480,
						settings: {
							slidesToShow: 1,
							slidesToScroll: 1,
						},
					},
				],
			});
		}

		if (
			jQuery(".upsells_product_wrap ul.products li").length > 1 &&
			jQuery(window).width() < 768
		) {
			jQuery(".upsells_product_wrap .up-sells .products").slick({
				infinite: true,
				arrows: false,
				slidesToShow: 1,
				centerPadding: "115px",
				centerMode: true,
				responsive: [
					{
						breakpoint: 480,
						settings: {
							slidesToShow: 1,
							slidesToScroll: 1,
						},
					},
				],
			});
		}

		if (
			(jQuery(".cart-collaterals .cross-sells .products li").length > 2 &&
				jQuery(window).width() < 770) ||
			(jQuery(".cart-collaterals .cross-sells .products li").length >= 5 &&
				jQuery(window).width() > 769)
		) {
			jQuery(".cart-collaterals .cross-sells .products").slick({
				infinite: true,
				arrows: true,
				slidesToShow: 4,
				responsive: [
					{
						breakpoint: 769,
						settings: {
							arrows: false,
							centerPadding: "115px",
							centerMode: true,
							slidesToShow: 2,
							slidesToScroll: 1,
						},
					},
					{
						breakpoint: 480,
						settings: {
							centerPadding: "115px",
							centerMode: true,
							slidesToShow: 1,
							slidesToScroll: 1,
						},
					},
				],
			});
		}

		if (jQuery(window).width() < 768) {
			jQuery(".ambassadorer_wrap .bot_wrap .title-wrap .tax_title").on(
				"click",
				function (event) {
					event.preventDefault();
					jQuery(this).toggleClass("opened");
					jQuery(this)
						.parent()
						.next(".bot_wrap_inner")
						.toggleClass("showcontent");
				}
			);
		}

		/*** Plus/Minus Quantity ***/
		jQuery(document).on("click", ".plus, .minus", function () {
			// Get values
			var $qty = jQuery(this).closest(".quantity").find(".qty"),
				currentVal = Number($qty.val()),
				max = Number($qty.attr("max")),
				min = Number($qty.attr("min")),
				step = Number($qty.attr("step")),
				newValue = 1;

			// Format values
			if (!currentVal || currentVal === "" || currentVal === "NaN")
				currentVal = 0;
			if (max === "" || max === "NaN") max = "";
			if (min === "" || min === "NaN") min = 1;
			if (
				step === "any" ||
				step === "" ||
				step === undefined ||
				parseFloat(step) === "NaN"
			)
				step = 1;

			// Change the value
			if (jQuery(this).is(".plus")) {
				if (max && (max == currentVal || currentVal > max)) {
					//$qty.val(max);
					newValue = max;
				} else {
					//$qty.val(currentVal + step);
					newValue = currentVal + step;
				}
			} else {
				if (min && (min == currentVal || currentVal < min)) {
					$qty.val(min);
					newValue = min;
				} else if (currentVal != min) {
					$qty.val(currentVal - step);
					newValue = currentVal - step;
				}
			}

			// console.log($qty, currentVal, max, min, step, newValue);

			if (newValue != currentVal) {
				$qty.val(newValue);

				// Trigger change event
				$qty.trigger("change");
			}
		});


		jQuery(document).on("change", ".woocommerce-cart .qty", function () {

			jQuery('.button[name="update_cart"]').attr("clicked", "true");
			jQuery(".woocommerce-cart-form").trigger("submit");
		});

		// Open minicart on hover
		$(document).on("mouseover", ".header-cart.hover-cart", function () {
			$(".panelholder").show();

			if ($("body").hasClass("admin-bar")) {
				$(".page_overlay").css({
					top: $(".header-wrap").height(),
					height: $(".site-wrapper").height() - $(".header-wrap").height(),
				});
			} else {
				$(".page_overlay").css({
					top: $(".header-wrap").height(),
					height: $(".site-wrapper").height() - $(".header-wrap").height(),
				});
			}

			$("body").addClass("overlay");

			if ($(".live_search_box .live_search_results").is(":visible")) {
				$(".live_search_box .search_input").blur();
			}
		});

		// mobile mini-cart
		$(".mobile-header #cart_link").click(function (e) {
			e.preventDefault();

			var count = parseInt($(this).find(".cart_count").text());

			if (count > 0) {
				$(".panelholder").show();
				$(".panelholder .added-message").hide();

				if ($("body").hasClass("admin-bar")) {
					$(".page_overlay").css({
						top: $(".header-wrap").height(),
						height: $(".site-wrapper").height() - $(".header-wrap").height(),
					});
				} else {
					$(".page_overlay").css({
						top: $(".header-wrap").height(),
						height: $(".site-wrapper").height() - $(".header-wrap").height(),
					});
				}

				$("body").addClass("overlay");
			}
		});

		// overlay minicart on add-to-cart
		$("body").on("added_to_cart", function () {
			setTimeout(function () {
				if ($(".panelholder").is(":visible")) {
					$("body").addClass("overlay");
				} else {
					$("body").removeClass("overlay");
				}

				setTimeout(function () {
					$(".panelholder .added-message").hide();
				}, 10000);
			}, 500);
		});

		jQuery(document).on("click", ".close_btn", function () {
			jQuery(".panelholder").hide();
			jQuery("body").removeClass("overlay");
		});

		jQuery(document).click(function (e) {
			var container = $(".header-wrap");
			if (
				!container.is(e.target) && // if the target of the click isn't the container...
				container.has(e.target).length === 0
			) {
				// ... nor a descendant of the container
				jQuery(".panelholder").hide();
				jQuery("body").removeClass("overlay");
			}
		});

		// Autoselect first color if none are specified in URL
		if (getAllUrlParams().attribute_pa_color == undefined) {
			setTimeout(function () {
				color_selection_by_default = 1;
				// jQuery('div[name="attribute_pa_color"] input[type="radio"]').first().click().trigger('change');
				//              jQuery('div[name="attribute_pa_color"] input[type="radio"]').first().prop("checked","checked");
				console.log(
					"Color:" +
					jQuery('div[name="attribute_pa_color"] input[type="radio"]:checked')
						.length
				);
				if (
					jQuery('div[name="attribute_pa_color"] input[type="radio"]:checked')
						.length > 0
				) {
					selected_color = jQuery(
						'div[name="attribute_pa_color"] input[type="radio"]:checked'
					);
					console.log("default color selected:" + $(selected_color).val());
					jQuery("#simple_attr_div_0").html(
						jQuery(selected_color).attr("color")
					);
					jQuery(selected_color).change();
				} else {
					console.log("default color not selected:");
					jQuery('div[name="attribute_pa_color"] input[type="radio"]')
						.first()
						.prop("checked", "checked");
					jQuery("#simple_attr_div_0").html(
						jQuery('div[name="attribute_pa_color"] input[type="radio"]')
							.first()
							.attr("color")
					);
					jQuery('div[name="attribute_pa_color"] input[type="radio"]')
						.first()
						.change();
				}
			}, 100);
		} else {
			jQuery("#simple_attr_div_0").html(getAllUrlParams().attribute_pa_color);
			if (jQuery("#simple_attr_select_1").find(":selected").length) {
				jQuery("#simple_attr_select_1 option:selected").removeAttr("selected");
			}
			jQuery('.summary select[name="attribute_pa_size"]').val("");
			jQuery('.summary select[name="attribute_pa_size"]').trigger("change");
			console.log("logic for buy button disable");
		}

		// Autoselect first available size if any
		jQuery('div[name="attribute_pa_color"] input[type="radio"]').on(
			"change",
			function (event) {
				//            console.log("change 1");
				// We must deselect the size dropdown to get the correct options available //#important
				if (jQuery("#simple_attr_select_1").find(":selected").length) {
					jQuery("#simple_attr_select_1 option:selected").removeAttr(
						"selected"
					);
				}
				var params = getAllUrlParams();
				var colorInput = $(this);
				// Commented so size is not selected by default
				/* setTimeout(function() {
						// If color selected is the same as the URL pa_color parameter, and a URL parameter pa_size is specified, select that size
						if (colorInput.val() == params.attribute_pa_color && params.attribute_pa_size != undefined) {
							jQuery('div[name="attribute_pa_size"] label:not(.inactive) input[value="'+params.attribute_pa_size+'"]').first().click().trigger('change');
						}
						// If no active inputs are available, deselect any previously selected pa_size options
						else if (jQuery('div[name="attribute_pa_size"] label:not(.inactive)').length == 0) {
							jQuery('input[name="attribute_pa_size"]:checked').prop('checked', false);
						}
						// Select the first available pa_size attribute
						else {
							jQuery('div[name="attribute_pa_size"] label:not(.inactive) input[type="radio"]').first().click().trigger('change');
						}
					}, 50); */
			}
		);

		// Cancel event if size selected is out of stock
		jQuery(document).on(
			"click",
			'input[name="custom_var_simple_attr_select_1"]',
			function (event) {
				//            console.log("Click 1");
				if (jQuery(event.target).parent().hasClass("inactive")) {
					//                console.log("Click 1: inside if");
					event.preventDefault();
					return;
				}
			}
		);
		/* Single product variation begins */
		jQuery(document).on(
			"change",
			'.custom_var_wrap input[type="radio"]',
			function (event) {
				//            console.log("change 2");
				changeVariation(jQuery(this));
				//            console.log("simple_attr_select_1 length:"+jQuery('#simple_attr_select_1').find(':selected').length);
				if (jQuery("#simple_attr_select_1").find(":selected").length) {
					jQuery("#simple_attr_select_1 option:selected").removeAttr(
						"selected"
					);
				}
			}
		);

		// Update inactive options when a variation option is selected
		jQuery(document).on("woocommerce_update_variation_values", function () {
			//TODO

			// Get the currently selected color
			var color = jQuery('select[name="attribute_pa_color"]')
				.find(":selected")
				.val();

			// For each attribute ..
			jQuery(".summary .variations select").each(function (index, el) {
				attr_name = jQuery(el).data("attribute_name");

				// Set all options as inactive as standard
				jQuery(
					'.custom_var_wrap[data-attribute_name="' + attr_name + '"] label'
				).addClass("inactive");

				// If no color is selected, don't active any option
				if (color.length != 0) {
					jQuery(this)
						.find("option")
						.each(function () {
							val = jQuery(this).attr("value");
							jQuery(
								'.custom_var_wrap[data-attribute_name="' + attr_name + '"]'
							)
								.find("input:radio")
								.filter('[value="' + val + '"]')
								.parent()
								.removeClass("inactive");
						});
				} else {
					// If no color is selected, but a size was previously selected, deselect said size
					var selectedSize = jQuery(
						'input[name="custom_var_simple_attr_select_1"]:checked'
					);
					if (selectedSize.length) {
						selectedSize.prop("checked", false);
					}
				}
			});
		});

		/*** Select2 init for Orderby ***/
		$(".woocommerce-ordering select").select2({
			minimumResultsForSearch: Infinity,
			width: "resolve",
		});

		//elite popup

		jQuery(".left_img_wrap .concepts_wrap").on("click", function (event) {
			jQuery(this).toggleClass("opened");
		});

		/* jQuery('.left_img_wrap .concepts_wrap .overlay_close').on('click', function (event) {
				// console.log('click');
				event.preventDefault();
				//jQuery('#conid').toggleClass('opened');
    
			}); */

		// storeguide popup
		$(function () {
			$(".popup-modal").magnificPopup({
				closeOnBgClick: true,
				callbacks: {
					beforeOpen: function () {
						$.magnificPopup.instance.close = function () {
							$.magnificPopup.proto.close.call(this);
						};
					},
				},
			});
			$(document).on("click", ".popup-modal-dismiss", function (e) {
				e.preventDefault();
				$.magnificPopup.close();
			});
			if (jQuery(window).width() < 768) {
				$(document).on("click", ".storrelsesguide-menu", function (e) {
					e.preventDefault();
					$.magnificPopup.open({
						items: {
							src: $("#store-modal"),
							type: "inline",
						},
					});
				});
			}
			$(document).on("click", ".pop-image", function (e) {
				e.preventDefault();
				$.magnificPopup.proto.close.call(this);
				$.magnificPopup.open({
					mainClass: "popped-image",
					type: "image",
					items: {
						src: this.src,
					},
					callbacks: {
						beforeOpen: function () {
							$.magnificPopup.instance.close = function () {
								$.magnificPopup.proto.close.call(this);
								$(".popup-modal").trigger("click");
							};
						},
						close: function () { },
					},
				});
			});
			$("#terms-pop").magnificPopup({
				type: "inline",
				prependTo: document.getElementById("wrapper"),
				midClick: true,
			});
		});

		/* // No longer needed, price will always be the same for all variations
			jQuery(document).on('woocommerce_update_variation_values', function () {
				if(jQuery('.woocommerce-variation-add-to-cart .variation_id').length) {
					//setTimeout(function(){
    
						val = jQuery('.woocommerce-variation-add-to-cart .variation_id').val();
    
						if(val > 0) {
							jQuery('.summary .price-wrap .price').hide();
							jQuery('.summary .price-wrap .price[data-id="'+val+'"]').fadeIn();
						} else {
							jQuery('.summary .price-wrap .price').hide();
							jQuery('.summary .price-wrap .product-price').fadeIn();
						}
    
					//}, 100);
				}
			});
			*/

		$(document).on(
			"click",
			".custom_var_wrap[name=attribute_pa_color] input[type=radio]",
			function () {
				$("#simple_attr_div_0").html($(this).attr("color"));
			}
		);

		// Read more on Single Product
		// $('.show-full').click(function(e) {
		//     e.preventDefault();

		//     $('.prod-excerpt').hide();
		//     $('.prod-content').show();
		// });

		// $('.show-excerpt').click(function(e) {
		//     e.preventDefault();

		//     $('.prod-content').hide();
		//     $('.prod-excerpt').show();
		// });

		/*
		 *  toggle functionality for product excerpt vs description to come up only for responsive view
		 */
		// if($(window).width() < 768){
		//  $('.woocommerce-product-details__short-description div a').show(); /* .woocommerce-product-details__short-description div,  */
		// } else {
		//  $('.woocommerce-product-details__short-description .prod-excerpt, .woocommerce-product-details__short-description .prod-content a').hide();
		//  $('.woocommerce-product-details__short-description .prod-content').show();
		// }

		$(".woocommerce-product-details__short-description");

		//cart
		jQuery(document).on("click", ".variatio_toggle_cart", function (event) {
			event.preventDefault();
			id_str = jQuery(this).attr("id");
			id_arr = id_str.split("_");
			jQuery("#variation_div_" + id_arr[2] + "_" + id_arr[3]).toggle();
			jQuery(this).hide();
		});
		jQuery(document).on("change", ".prod_single_attr", function (e) {
			e.preventDefault();
			id_str = jQuery(this).attr("id");
			id_arr = id_str.split("_");
			//attr_product_count=jQuery('#attr_product_count').val();

			attrkey = jQuery(this).attr("data-attrkey");
			cart_item_key = jQuery(this)
				.closest(".variation_cart_wrap")
				.find(".old_key")
				.val();
			attributes_arr = jQuery("#attributes_arr_" + id_arr[2]).val();
			quantity = jQuery(this).parents(".cart_item").find(".qty").val();

			//console.log(cart_item_key);
			form_data = jQuery(
				"#variation_div_" + id_arr[2] + "_" + id_arr[4] + " :input"
			).serialize();

			jQuery(".woocommerce").block({
				message: null,
				overlayCSS: {
					opacity: 0.6,
				},
			});

			jQuery.ajax({
				url: myAjax.ajaxurl,
				type: "post",
				//dataType: 'html',
				data: {
					action: "mod_product_variation",
					cart_item_key: cart_item_key,
					form_data: form_data,
					attributes_arr: attributes_arr,
					quantity: quantity,
				},

				success: function (response) {
					jQuery(".woocommerce").stop(true).css("opacity", "1").unblock();
					jQuery("#error_msg_variation").html("");
					if (response == "F") {
						jQuery("#error_msg_variation_" + id_arr[2] + "_" + id_arr[4]).html(
							"<p>Product Selected Variation does not exist</p>"
						);
					} else if (response == "S") {
						jQuery("#error_msg_variation_" + id_arr[2] + "_" + id_arr[4]).html(
							"<p>Product Selected Variation out of Stock </p>"
						);
					} else if (response != "F" || response != "S") {
						var html = jQuery.parseHTML(response);
						var new_form = jQuery("table.shop_table.cart", html).closest(
							"form"
						);
						var new_totals = jQuery(".cart_totals", html);

						jQuery("table.shop_table.cart")
							.closest("form")
							.replaceWith(new_form);
						jQuery(".cart_totals").replaceWith(new_totals);
						if (jQuery("div.woocommerce-message").length == 0) {
							jQuery("div.entry-content div.woocommerce").prepend(
								'<div class="woocommerce-message">' +
								update_variation_params.cart_updated_text +
								"</div>"
							);
						}
					}
				},
			});
		});

		jQuery(document).on("click", "#load_more_product", function (e) {
			e.preventDefault();

			// console.log('load_more_product');

			var search_val = jQuery("#search_val").val();

			var no_of_post = jQuery("#no_of_post").val();

			var query_type = jQuery("#query_type").val();

			var cat_arr = jQuery("#cat_arr").val();

			var pageNumber = parseInt(jQuery("#page_no").val()) + 1;

			var str =
				"pageNumber=" +
				pageNumber +
				"&no_of_post=" +
				no_of_post +
				"&action=product_search&search_value=" +
				search_val +
				"&query_type=" +
				query_type +
				"&cat_arr=" +
				cat_arr;

			jQuery.ajax({
				type: "POST",
				dataType: "json",
				url: myAjax.ajaxurl,
				data: str,
				success: function (data) {
					if (jQuery.trim(data.load_more_status) == "0") {
						jQuery("#load_more_product").css("display", "none");
						jQuery("#page_no").val(0);
					} else {
						jQuery("#page_no").val(data.load_more_status);
					}
					if (data.data != "") {
						jQuery("#product_block .products").append(data.data);
					}
				},
				error: function (data) { },
			});
		});

		jQuery("input[name='categories']").change(function () {
			var cat_arr = "";
			var cat_url_str = "";
			var count = 0;
			jQuery("input:checkbox[name=categories]:checked").each(function () {
				count++;
				cat_arr += jQuery(this).val() + ",";
				cat_url_str += "&Categories=" + jQuery(this).val();
			});
			if (count == 0) {
				jQuery("#query_type").val("S");
			} else {
				jQuery("#query_type").val("SC");
			}
			var myURL = jQuery("#site_url").val();

			cat_arr = cat_arr.slice(0, -1);

			jQuery("#cat_arr").val(cat_arr);
			var search_val = jQuery("#search_val").val();

			var no_of_post = jQuery("#no_of_post").val();

			var query_type = jQuery("#query_type").val();

			//var cat_arr = jQuery("#cat_arr").val();

			//var pageNumber = parseInt(jQuery('#page_no').val())+1;
			var pageNumber = 1;

			var new_url = myURL + "?s=" + search_val + cat_url_str;
			window.history.pushState("data", "Title", new_url);

			var str =
				"pageNumber=" +
				pageNumber +
				"&no_of_post=" +
				no_of_post +
				"&action=product_search&search_value=" +
				search_val +
				"&query_type=" +
				query_type +
				"&cat_arr=" +
				cat_arr;

			jQuery.ajax({
				type: "POST",
				dataType: "json",
				url: myAjax.ajaxurl,
				data: str,
				success: function (data) {
					if (jQuery.trim(data.load_more_status) == "0") {
						jQuery("#load_more_product").css("display", "none");
						jQuery("#page_no").val(0);
					} else {
						jQuery("#load_more_product").css("display", "inline-block");
						jQuery("#page_no").val(data.load_more_status);
					}
					if (data.data != "") {
						jQuery("#product_block .products").html(data.data);
					}
				},
				error: function (data) { },
			});
		});

		// Checkout Functions
		if ($(".check-this").length) {
			$(".check-this").click(function () {
				$(this).toggleClass("checked");
				if ($(this).parent(".choose-coupon").length) {
					if ($(this).hasClass("checked")) {
						$(".coupon-details").show();
					} else {
						$(".coupon-details").hide();
					}
				}
				if ($(this).parent(".after-checkout-gift-card-form").length) {
					if ($(this).hasClass("checked")) {
						$(".ywgc_enter_code").show();
					} else {
						$(".ywgc_enter_code").hide();
					}
				}
				if ($(this).parent(".mailchimp-newsletter").length) {
					$("#mailchimp_woocommerce_newsletter_custom").prop(
						"checked",
						!$("#mailchimp_woocommerce_newsletter_custom").prop("checked")
					);
				}
			});
		}

		if ($("#coup-code").length) {
			$("#coup-code").keyup(function () {
				var val = $(this).val();

				$(".checkout_coupon #coupon_code").val(val);
			});

			// Verbatim from binded submit() function in checkout.js,
			// but placing response HTML below the submit coupon HTML button
			$("#use-coup").click(function () {
				var $form = $("form.checkout");

				if ($form.is(".processing")) {
					return false;
				}

				$form.addClass("processing").block({
					message: null,
					overlayCSS: {
						background: "#fff",
						opacity: 0.6,
					},
				});

				var data = {
					security: wc_checkout_params.apply_coupon_nonce,
					coupon_code: $form.find("#coup-code").val(),
				};

				$.ajax({
					type: "POST",
					url: wc_checkout_params.wc_ajax_url
						.toString()
						.replace("%%endpoint%%", "apply_coupon"),
					data: data,
					dataType: "html",
					success: function (response) {
						$(".woocommerce-error, .woocommerce-message").remove();
						$form.removeClass("processing").unblock();
						if (response) {
							$("#coupon-messages").html(response);
							$(document.body).trigger("update_checkout", {
								update_shipping_method: false,
							});
						}
					},
				});
			});
		}
		if ($("#sfsiid_instagram")) {
			jQuery("#sfsiid_instagram").attr("target", "_blank");
		}
		$(document).ajaxComplete(function (e, xhr, opt) {
			$(".gift-card-calculated").hide();
			setTimeout(function () {
				var str1 = opt.data;
				var str2 = "ywgc_apply_gift_card_code";
				var str3 = "ywgc_remove_gift_card_code";
				if (
					str1 != undefined &&
					str1 != undefined &&
					str1.indexOf(str2) != -1
				) {
					$(".gift-card-defautlt").hide();
					//console.log($('tr .ywgc-gift-card-applied th').text());
					$(".gift-card-calculated").show();
					var title = $(".ywgc-gift-card-applied th").html();
					var value = $(
						".ywgc-gift-card-applied .woocommerce-Price-amount"
					).text();
					//alert('hie');
					$(".gift-card").html(
						"<div class='labelle'>" +
						title +
						"</div><div class='value'>" +
						value +
						"</div>"
					);
					$(".right_total").html(
						$(".order-total .woocommerce-Price-amount").html()
					);
				}
				if (
					str1 != undefined &&
					str1 != undefined &&
					str1.indexOf(str3) != -1
				) {
					$(".gift-card-defautlt").hide();
					$(".gift-card-calculated").hide();
					$(".gift-card").html("");
					$(".right_total").html(
						$(".order-total .woocommerce-Price-amount").html()
					);
				}
			}, 1000);
			// console.log(opt);
		});

		//scroll top as soon as product is added to cart
		$(".ajax_add_to_cart ").on("click", function () {
			setTimeout(function () {
				$("html, body").animate({ scrollTop: 0 }, "slow");
			}, 1000);
		});
	});

	// On load
	$(window).load(function () {
		// Close minicart
		if ($(".panelholder").is(":visible")) {
			$(".panelholder").hide();
		} else {
			$("body").addClass("overlay");
		}

		setTimeout(function () {
			$(".panelholder").hide();
			$("body").removeClass("overlay");
			$("body").removeClass("loading");
		}, 100);

		// Set Remaining
		if ($(".craft-more").length) {
			setTimeout(function () {
				var total = parseInt($(".page-count-holder .loop-count").text());
				var existing = parseInt($(".page-count-holder .page-count").text());

				$(".lmp_load_more_button .lmp_button .post-count").text(
					total - existing
				);
			}, 500);
		}

		/* if (getAllUrlParams().attribute_pa_color != '') {
				var selectedColor = jQuery("input[name=custom_var_simple_attr_select_0]:checked");
    
				setTimeout(function() {
					jQuery(selectedColor).click();
					jQuery(selectedColor).change();
				}, 100);
			} */
	});

	/*
		  // Update AJAX Loader Counts
		  $( document ).ajaxComplete(function( event, xhr, settings ) {
			  if(the_lmp_js_data) {
				  var $data = $('<div>'+xhr.responseText+'</div>');
				  var total = parseInt($('.page-count-holder .loop-count').text());
				  var existing = $('.type-product:not(.normal, .wide)').length;
				  var loaded = $data.find('.type-product:not(.normal, .wide)').length;
	  
				  $('.page-count-holder .page-count').text(existing);
				  $('.lmp_load_more_button .lmp_button .post-count').text(total - existing);
	  
				  if(!isNaN(total)) {
					  if(total <= existing) {
						  $('.lmp_load_more_button .lmp_button').hide();
					  }
				  }
			  }
		  });
	  
		  */
	if ($(".countdown_timer_val").length != 0) {
		if ($(".countdown_timer_val").val()) {
			updateCountdownTimer($(".countdown_timer_val").val());
		}
	}

	if ($(".quiz_form").length) {
		var total_questions = get_quiz_questions_count();
		$(".quiz_form").append(
			'<div class="quiz_nav_wrapper"><input type="hidden" id="current_quiz_step" value="1"><span class="step_num"><span class="current_step">1</span>/' +
			total_questions +
			'</span><a class="quiz_next_button" href="javascript:void(0)" onclick="next_quiz_question()">NESTE</a></div>'
		);
	}
	if (jQuery(".ywgc-remove-gift-card")) {
		jQuery(".ywgc-remove-gift-card").text("[Fjern]");
		jQuery(".ywgc-remove-gift-card").css({
			color: "#a8a8a8",
			"font-weight": "normal",
		});
	}

	/*** Change of Variations ***/
	function changeVariation(obj) {
		var attribute_name = jQuery(obj)
			.parents(".custom_var_wrap")
			.data("attribute_name");
		var v_value = jQuery(obj).val();
		//    console.log("attribute_name:"+attribute_name);
		//    console.log("v_value:"+v_value);
		jQuery('.summary select[name="' + attribute_name + '"]').val("");
		jQuery('.summary select[name="' + attribute_name + '"]').trigger("change");
		jQuery('.summary select[name="' + attribute_name + '"]').val(v_value);
		jQuery('.summary select[name="' + attribute_name + '"]').trigger("change");

		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').val("");
		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').trigger(
			"change"
		);
		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').val(v_value);
		jQuery('.mobile.cart_box select[name="' + attribute_name + '"]').trigger(
			"change"
		);

		jQuery('.custom_var_wrap[data-attribute_name="' + attribute_name + '"]').each(
			function () {
				jQuery(obj)
					.find("input:radio")
					.filter('[value="' + v_value + '"]')
					.prop("checked", true);
			}
		);
		var var_id = jQuery('input[name="variation_id"]').val();

		// handle Outlet Discount Badges 20190328
		// jQuery('.custom-woocommerce-variation-disc-message').hide();
		// jQuery('.custom-variation-'+var_id).show();

		// active variation stock display
		jQuery(".variations_box .variation_stock_wrap .variation_stock").removeClass(
			"active"
		);
		if (var_id) {
			jQuery(".variation_stock_wrap")
				.find("#" + var_id)
				.addClass("active");
		}
		jQuery(".custom_single_product_description .product_description").removeClass(
			"active"
		);

		if (var_id) {
			jQuery(".custom_single_product_description #desc-" + var_id).addClass(
				"active"
			);
		}

		jQuery(".price-wrap .product-price").html(
			jQuery(
				"form.variations_form .single_variation_wrap .woocommerce-variation-price .price"
			).html()
		);

		//display relevant outlet price details
		// jQuery('.outlet-price-wrapper').css('display', 'none');
		// jQuery('.outlet-price-wrapper#' + var_id).css('display', 'block');

		if (
			attribute_name == "attribute_pa_color" &&
			1 != color_selection_by_default
		) {
			// $('.images').fadeOut(300, function () {
			// 	$(this).remove();
			// });

			// if (!$('.product-gallery').find('.slider-wrapper').length) {
			// 	var wrappedData = '<div style="height:100%;max-height:500px;" class="slider-wrapper"></div>';
			// 	$('.product-gallery').prepend(wrappedData);
			// }
			post_id = jQuery("form.variations_form.cart").data("product_id");
			// console.log(post_id);
			jQuery.ajax({
				type: "post",
				dataType: "json",
				url: myAjax.ajaxurl,
				data: {
					action: "change_variant_gallery",
					attribute_pa_color: v_value,
					post_id: post_id,
				},
				success: function (response) {
					//                // console.log(response.data);
					if (!response.error) {
						jQuery(".product-gallery .slider-wrapper").html(response.data).fadeIn(500);

						$('.easyzoom').easyZoom();
					}
				},
				error: function (data) {
					// console.log('error');
				},
			});

			jQuery(".variations_form").attr(
				"action",
				"https://" +
				window.location.hostname +
				window.location.pathname +
				"?attribute_pa_color=" +
				v_value
			);
		} else if (1 == color_selection_by_default) color_selection_by_default = 2;
	}

	/*** Get URL Params ***/
	function getAllUrlParams(url) {
		// get query string from url (optional) or window
		var queryString = url ? url.split("?")[1] : window.location.search.slice(1);

		// we'll store the parameters here
		var obj = {};

		// if query string exists
		if (queryString) {
			// stuff after # is not part of query string, so get rid of it
			queryString = queryString.split("#")[0];

			// split our query string into its component parts
			var arr = queryString.split("&");

			for (var i = 0; i < arr.length; i++) {
				// separate the keys and the values
				var a = arr[i].split("=");

				// in case params look like: list[]=thing1&list[]=thing2
				var paramNum = undefined;
				var paramName = a[0].replace(/\[\d*\]/, function (v) {
					paramNum = v.slice(1, -1);
					return "";
				});

				// set parameter value (use 'true' if empty)
				var paramValue = typeof a[1] === "undefined" ? true : a[1];

				// (optional) keep case consistent
				paramName = paramName.toLowerCase();
				paramValue = paramValue.toLowerCase();

				// if parameter name already exists
				if (obj[paramName]) {
					// convert value to array (if still string)
					if (typeof obj[paramName] === "string") {
						obj[paramName] = [obj[paramName]];
					}
					// if no array index number specified...
					if (typeof paramNum === "undefined") {
						// put the value on the end of the array
						obj[paramName].push(paramValue);
					}
					// if array index number specified...
					else {
						// put the value at that index number
						obj[paramName][paramNum] = paramValue;
					}
				}
				// if param name doesn't exist yet, set it
				else {
					obj[paramName] = paramValue;
				}
			}
		}

		return obj;
	}
})(jQuery);