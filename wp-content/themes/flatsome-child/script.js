jQuery(document).ready(function($) {
    $('#pa_color').on('change', function() {
        if($('#pa_size').val() == ''){
            var flag = false;
            $("#pa_size > option").each(function() {
                if($(this).val() == 'm'){
                    $("#pa_size").val($(this).val());
                    flag = true;
                }
            });
            if(flag == false){
                $("#pa_size").val($("#pa_size option:eq(1)").val());
            }
            $('#pa_size').trigger('change');
        }
    });
    $('#pa_size').on('change', function() {
        if($(this).val() == ''){
            var flag = false;
            $("#pa_size > option").each(function() {
                if($(this).val() == 'm'){
                    $("#pa_size").val($(this).val());
                    flag = true;
                }
            });
            if(flag == false){
                $(this).val($("#pa_size option:eq(1)").val());
            }
            $(this).trigger('change');
        }
    });

    console.log("working");
    
});
