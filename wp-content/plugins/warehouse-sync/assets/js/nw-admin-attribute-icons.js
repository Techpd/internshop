jQuery(document).ready(function($) {
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
});

// //TODO create a separate file
// jQuery(document).ready(function($) {
// 	// window.setTimeout(function() {
// 		$('.general_options, .options_group.pricing').css({'display': 'block !important'});
// 	// }, 200);
// 	// $('.general_options, .options_group.pricing').show();
// 	// $('.options_group.pricing').show();
// });
