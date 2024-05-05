jQuery(function($){
    //Select/Upload image(s) event
    $('body').on('click', '.nw-category-image-upload', function(e){
        e.preventDefault();
        var button = $(this);
        var hasImage = button.children('input').first().val() ? true : false;
        var tipSettings = {
        'fadeIn':    50,
        'fadeOut':   50,
        'delay':     200
        };

        // Upload image
        if (!hasImage) {
        var customUploader = wp.media({
            title: 'Insert image',
            library : { type : 'image' },
            button: { text: 'Use this image' },
            multiple: false
        }).on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            var attachment_thumbnail = attachment.sizes.full;

            tipSettings.attribute = 'data-rm-tip';
            button.tipTip(tipSettings);
            button.addClass('remove');
            button.children('img').attr('src', attachment_thumbnail.url);
            button.children('input').val(attachment.id);
        }).open();
        }

        // Remove image
        else {
        tipSettings.attribute = 'data-add-tip';
        button.tipTip(tipSettings);
        button.removeClass('remove');
        var placeholder = button.children('img').data('placeholder');
        button.children('img').attr('src', placeholder);
        button.children('input').val('');
        }
    });
});
  