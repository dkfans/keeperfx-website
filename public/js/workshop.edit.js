

$(function(){

    // Load image widget
    $('#image-uploader').show();

    // Add images on from widget on form submit
    $('#edit-item-form').on('submit', function(e){

        // Prevent form from being submitted while we add the images
        e.preventDefault();

        // Check if image widget data is available
        if(typeof imageWidgetData == 'undefined'){
            return;
        }

        // Load image widget post data into form
        $('#image-widget').val(getImageWidgetPostData());

        // Submit the form
        HTMLFormElement.prototype.submit.call(this);
    });

});
