
// Document ready
$(function(){

    // Get query params for navigating
    var queryParams = new URLSearchParams(location.search);

    // Handle browse filters and the category menu
    $('#browse-filters a, #browseMenu a').on('click', function(e){
        let filterType = $(this).attr('data-browse-filter-type');

        if(typeof filterType !== 'undefined'){
            e.preventDefault();
            let filterValue = $(this).attr('data-browse-filter-value');
            queryParams.set(filterType, filterValue);
            queryParams.delete('page');
            window.location.href = '/workshop/browse?' + queryParams.toString();
        }
    });

    // Show rating overviews
    $.each($('span[data-workshop-rating-score]'), function(index, element){

        let ratingType = $(this).attr('data-workshop-rating-type');
        ratingType = ratingType.charAt(0).toUpperCase() + ratingType.slice(1);

        let string = ratingType + ' rating: ' + $(this).attr('data-workshop-rating-score');

        new bootstrap.Popover(element, {
            'placement': 'top',
            'trigger': 'hover',
            'content': string,
            'offset': '5px'
        });
    });

    // Load the image widget if it is present
    $('#image-uploader').show();

    // Item max upload file size check
    $('#workshop-file').on('change', function(){
        fileFileSize = 0;
        if(this.files[0].size > app_store.upload_limit.workshop_item.size){
            toastr.warning('File exceeds maximum file size of ' + app_store.upload_limit.workshop_item.formatted);
            this.value = null;
        }
    });

    // Handle form submits
    $('#upload-item-form, #edit-item-form, #moderator-upload-item-form, #moderator-edit-item-form').on('submit', function(e){

        e.preventDefault();

        $(this).find('button[type=submit]').buttonLoader(true);

        // Check total upload size
        var totalUploadSize = 0;

        // TODO: CHECK IMAGE WIDGET
        // TODO: CHECK FILE SIZE

        // var totalUploadSize = fileFileSize + thumbnailFileSize + screenshotsTotalFileSize;
        if(totalUploadSize > app_store.upload_limit.total.size){
            toastr.warning(
                'Maximum total upload size of ' + app_store.upload_limit.total.formatted + ' is exceeded. ' +
                'If you have many images, try uploading the item without them and then add them later.'
            );
            $(this).find('button[type=submit]').buttonLoader(false);
            return false;
        }

        // Load image widget post data into form
        if(typeof imageWidgetData !== 'undefined'){
            $('#image-widget').val(getImageWidgetPostData());
        }

        // Submit the form
        HTMLFormElement.prototype.submit.call(this);
    });

    // Show original author input if selected
    $('#is_not_original_author').on('change', function(e){
        $('#original_author_box').slideToggle("fast");
        if($('#is_not_original_author').is(":checked") === false){
            $('#original_author').val('');
        }
    });

    // Show original creation date input if selected
    $('#is_not_original_creationdate').on('change', function(e){
        $('#original_creationdate_box').slideToggle("fast");
        if($('#is_not_original_creationdate').is(":checked") === false){
            $('#original_creation_date').val('');
        }
    });

    // Handle category changes
    $('#category').on('change', function(e){

        let categoryValue = parseInt($(this).val());

        // Show/hide difficulty rating
        if(app_store.workshop.categories_without_difficulty.indexOf(categoryValue) === -1){
            $('#difficulty-rating-box').show();
            $('#difficulty-rating').prop('checked', true);
        } else {
            $('#difficulty-rating-box').hide();
            $('#difficulty-rating').prop('checked', false);
        }

        // Show/hide map number input when category is Map
        if($('#map-number-box').is(':visible') === true && categoryValue !== 10){
            $('#map-number-box').slideUp("fast");
            $('#map_number').removeAttr('required');
        } else if($('#map-number-box').is(':visible') === false && categoryValue === 10) {
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
            url: '/api/v1/workshop/map_number/' + map_number,
            dataType: 'json', // return type data,
            error: function(data){
                $mapNumberInput[0].setCustomValidity('Something went wrong');
            },
            success: function(data){

                // Make sure request went successful
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
                    $mapNumberInput[0].setCustomValidity('Map number unavailable');
                    return;
                }

                // Map is available!
                $mapNumberInput.addClass('is-valid');
                $mapNumberInput[0].setCustomValidity('');
            },
            complete:  function(data){
                $('#map-number-loader').hide();
            }
        });

    });

    // Delete item confirmation
    $('#deleteWorkshopItem').on('click', function(e){
        e.preventDefault();
        e.stopPropagation();

        let deleteString = window.prompt("Are you sure you want to delete this workshop item?\n\nWrite 'delete' to confirm:\n\n");

        if(deleteString !== null && deleteString.toLowerCase() === 'delete'){
            window.location = $(this).attr('href');
        }
    });

    // Select submitter radio button when selecting the input
    $('#submitter_username_input').on('focus', function(e){
        $('#submitter_username').prop('checked', true);
    });

    $('#workshop-search-form').on('submit', function(e){
        e.preventDefault();
        queryParams.set('search', $('#workshop-search').val());
        queryParams.delete('page');
        window.location.href = '/workshop/browse?' + queryParams.toString();
    });

    $.each($('.workshop-grid-item-image-container img'), function(e){
        setImageLoadingHandler(this, "/img/broken-image-256.png");
    });

});
