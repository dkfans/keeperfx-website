$(function(){

    // Load image widget
    $('#image-uploader').show();

    // Load popovers
    $.each($('[data-bs-toggle="popover"]'), function(i, element){
        new bootstrap.Popover(element, {trigger: 'hover'})
    });

    // Item max upload filesize check
    $('#file').on('change', function(){
        fileFileSize = 0;
        if(this.files[0].size > app_store.upload_limit.workshop_item.size){
            toastr.warning('File exceeds maximum filesize of ' + app_store.upload_limit.workshop_item.formatted);
            this.value = null;
        }
    });

    // Check total upload size on form submit
    $('#upload-item-form').on('submit', function(e){

        // TODO: CHECK ALL FILES THAT ARE GOING TO BE UPLOADED!
        var totalUploadSize = 0;

        // var totalUploadSize = fileFileSize + thumbnailFileSize + screenshotsTotalFileSize;
        if(totalUploadSize > app_store.upload_limit.total.size){
            toastr.warning(
                'Maximum total upload size of ' + app_store.upload_limit.total.formatted + ' is exceeded. ' +
                'If you have many screenshots, try uploading the item without them and then add the screenshots later.'
            );
            e.preventDefault();
        }
    });

    // Show original author input if selected
    $('#is_not_original_author').on('change', function(e){
        let checked = $('#is_not_original_author').attr('checked');
        $('#original_author_box').slideToggle("fast");
    });

    // Show original creation date input if selected
    $('#is_not_original_creationdate').on('change', function(e){
        let checked = $('#is_not_original_creationdate').attr('checked');
        $('#original_creationdate_box').slideToggle("fast");
    });

    // Show map number input when category is Map
    $('#category').on('change', function(e){
        if($('#map-number-box').is(':visible') === true && $(this).val() !== '10'){
            $('#map-number-box').slideUp("fast");
            $('#map_number').removeAttr('required');
        } else if($('#map-number-box').is(':visible') === false && $(this).val() === '10') {
            $('#map-number-box').slideDown("fast");
            $('#map_number').attr('required', true);
        }
    });

    // Check map number
    $('#map_number').on('change keyup', function(e){

        var $mapNumberInput = $(this);

        // Remove valid/invalid state of input form
        $mapNumberInput.removeClass('is-valid').removeClass('is-invalid');

        // Check if a map number is given
        if(typeof $mapNumberInput.val() === 'undefined' || $mapNumberInput.val() === ''){
            return;
        }

        // Get map number as int
        var map_number = parseInt($mapNumberInput.val());

        // Check if map number is in valid range
        if(map_number < 202 || map_number > 32767) {
            $mapNumberInput.addClass('is-invalid');
            return;
        }

        // Show loading spinner while we do ajax request
        $('#map-number-loader').show();

        // Check if map number is already in use
        $.ajax({
            type: 'GET',
            url: '/workshop/upload/map_number/' + map_number,
            dataType: 'json', // return type data,
            error: function(data){
                toastr.error('Something went wrong.');
            },
            success: function(data){

                // Make sure rating went successful
                if(typeof data.success === 'undefined' || data.success !== true){
                    toastr.error('Something went wrong.');
                    return;
                }

                // Make sure this ajax request is still valid for the chosen map number
                // This protects against race conditions
                if(data.map_number !== parseInt($mapNumberInput.val())){
                    return;
                }

                // Map is not available
                if(!data.available){
                    $mapNumberInput.addClass('is-invalid');
                    return;
                }

                // Map is available!
                $mapNumberInput.addClass('is-valid');
            },
            complete:  function(data){
                $('#map-number-loader').hide();
            }
        });

    });

    // Add images on from widget on form submit
    $('#upload-item-form').on('submit', function(e){

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
