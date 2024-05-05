jQuery(document).ready(function($) {
    $('input[name="_nw_stock_sync_enabled"]').change(function() {
        let isChecked = $(this).prop('checked');
        $('input[data-parent="_nw_stock_sync_enabled"][type="text"]').each(function(){
            $(this).attr('disabled',!isChecked);
        });
        $('input[data-parent="_nw_stock_sync_enabled"][type="number"]').attr('disabled',!isChecked);
        $('input[data-parent="_nw_stock_sync_enabled"][type="url"]').attr('disabled',!isChecked);
    });

    $('input[name="_nw_order_export_enabled"]').change(function() {
        let isChecked = $(this).prop('checked');
        $('input[data-parent="_nw_order_export_enabled"][type="text"], input[data-parent="_nw_order_export_enabled"][type="checkbox"]').each(function(){
            $(this).attr('disabled',!isChecked);
        });
        $('input[data-parent="_nw_order_export_enabled"][type="number"]').attr('disabled',!isChecked);
        $('input[data-parent="_nw_order_export_enabled"][type="url"]').attr('disabled',!isChecked);
    });

    $('input[name="_nw_order_tracking_enabled"]').change(function() {
        let isChecked = $(this).prop('checked');
        $('input[data-parent="_nw_order_tracking_enabled"][type="text"], select[data-parent="_nw_order_tracking_enabled"]').each(function(){
            $(this).attr('disabled',!isChecked);
        });
    });

    $('input[name="_nw_export_sales_csv"]').change(function() {
        let isChecked = $(this).prop('checked');
        $('input[data-parent="_nw_export_sales_csv"][type="text"], input[data-parent="_nw_export_sales_csv"][type="password"], input[data-parent="_nw_export_sales_csv"][type="number"]').each(function(){
            $(this).attr('disabled',!isChecked);
        });
    });

    $('input[name="_nw_product_import_enabled"]').change(function() {
        let isChecked = $(this).prop('checked');
        $('input[data-parent="_nw_product_import_enabled"]').prop('disabled', !isChecked);
        $('input[data-parent="_nw_product_import_enabled"]').prop('checked', isChecked);

        $('input[data-parent="_nw_product_import_enabled"][type="text"]').each(function(){
            $(this).attr('disabled',!isChecked);
        });

        $('select[data-parent="_nw_product_import_enabled"]').each(function(){
            $(this).attr('disabled',!isChecked);
        });
    });

    //Group, vendor and club management feature can only be enabled when the API type is GraphQL
    // $("input[name='_nw_shop_feature'], select[name='_nw_api_type']").on("change", function () {
    //     const apitype = $("select[name='_nw_api_type']").val();
  
    //     if ($(this).val() && apitype !=='graphql') {
    //         $("input[name='_nw_shop_feature']").prop("checked", false);
    //     }
    // });

    //Toggle selection of shop and product concept,material and icons feature
    $('.feature-cb').change(function() {
        if ($(this).prop('checked')) {
          $('.feature-cb').not(this).prop('checked', false);
        }
    });

    //Product properties feature can only be enabled when the API type is RPC
    // $("input[name='_nw_feature_properties'], select[name='_nw_api_type']").on("change", function () {
    //     const apitype = $("select[name='_nw_api_type']").val();
  
    //     if ($(this).val() && apitype !=='rpc') {
    //         alert('Product properties feature can only be enabled when the API type is RPC');
    //         $("input[name='_nw_feature_properties']").prop("checked", false);
    //     }
    // });

    //Feature panels
    const tabs = document.querySelectorAll('.nw-settings .nav-tab');
	const contents = document.querySelectorAll('tr[data-panel-content]');

	tabs.forEach(tab => {
	  tab.addEventListener('click', (e) => {
		e.preventDefault();
		tabs.forEach(t => t.classList.remove('nav-tab-active'));
		tab.classList.add('nav-tab-active');
		
		const targetTab = tab.getAttribute('data-tab');
		contents.forEach(content => {
            if (content.getAttribute('data-panel-content') === targetTab) {
            content.classList.remove('active');
            content.classList.add('active');
            }else{
            content.classList.remove('active');
            }
		});
	  });
	});

    function handleCheckboxChange(checkboxName, dataParent) {
        $(`input[name='${checkboxName}']`).on("change", function () {
            $(`input[data-parent='${dataParent}']`).attr("required", $(this).prop("checked"));
        });
    }
    
    handleCheckboxChange("_nw_product_import_enabled", "_nw_product_import_enabled");
    handleCheckboxChange("_nw_stock_sync_enabled", "_nw_stock_sync_enabled");
    handleCheckboxChange("_nw_order_export_enabled", "_nw_order_export_enabled");
    handleCheckboxChange("_nw_export_sales_csv", "_nw_export_sales_csv");

    // Handle product types
    var checkboxes = jQuery('input[name="_nw_product_types[]"]');

    // Add a change event listener to each checkbox
    checkboxes.change(function () {
        var selectedCheckbox = jQuery(this);
        var selectedValue = selectedCheckbox.val();

        // If the "variable" checkbox is selected, uncheck the other checkboxes
        if (selectedValue === 'variable') {
            checkboxes.not(selectedCheckbox).prop('checked', false);
        }

        // If any other checkbox is selected, uncheck the "variable" checkbox
        else {
            checkboxes.filter('[value="variable"]').prop('checked', false);
        }
    });
});