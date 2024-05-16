
$.fn.buttonLoader = function (show = true) {

    // Loop trough all triggered buttons
    $.each($(this),function(i, el){

        if (show) {

            // Remember button size because it can change when
            // the inner HTML of the button is updated
            let width = $(el).outerWidth();
            let height = $(el).outerHeight();

            // Disable the button
            $(el).prop('disabled', true);
            $(el).addClass('disabled');

            console.log(width, height);

            // Remember original button
            $(el).data('original-width', width);
            $(el).data('original-height', height);
            $(el).data('original-html', $(el).html());

            // Set button contents to loader
            $(el).html('<div class="lds-ring"><div></div><div></div><div></div><div></div></div>');

            // Reset button size (because it might have changed)
            $(el).outerWidth(width);
            $(el).outerHeight(height);

        } else {

            // Enable the button
            $(el).prop('disabled', false);
            $(el).removeClass('disabled');

            // Reset button contents
            if($(el).data('original-html')){
                $(el).html($(el).data('original-html'));
                $(el).removeData('original-html');
            }

            // Reset button size
            if($(el).data('original-width')){
                $(el).outerWidth($(el).data('original-width'));
                $(el).removeData('original-width');
            }
            if($(el).data('original-height')){
                $(el).outerHeight($(el).data('original-height'));
                $(el).removeData('original-height');
            }
        }

    });
};
