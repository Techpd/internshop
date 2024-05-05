jQuery(document).ready(function($) {

	/**
	 * Do AJAX call to update order item meta
	 *
	 */
	$(document).on('click', '.nw-change-asw-status', function(e) {
		e.preventDefault();
		var table = 'table.woocommerce_order_items';
		$(table).block({
      message    : '',
      overlayCSS : {
        background : '#fff',
        opacity    : 0.6
      }
    });

		// Get IDs of order items to apply action to
		var $table = $( 'table.woocommerce_order_items' );
		var $rows = $table.find( 'tr.selected' );
		var item_ids = $.map( $rows, function( $row ) {
			return parseInt( $( $row ).data( 'order_item_id' ), 10 );
		});

		var data = {
			order_id : woocommerce_admin_meta_boxes.post_id,
			order_item_ids : item_ids,
			value : $(this).data('value'),
			action : 'nw_update_order_items',
			security : $('.nw-change-asw-status').first().data('nonce'),
		};

		// Perform AJAX call and reload order items table
		$.post(ajaxurl, data, function(response) {
			$(table).load(window.location.toString() + ' ' + table + ' > *', function() {
				$(table).trigger('reload').unblock();
			});
		});
	});
});
