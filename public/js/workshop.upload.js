var popover = new bootstrap.Popover($('[data-bs-toggle="popover"]'), {trigger: 'hover'});

$(function(){

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

    // Show map number input when type is Map
    $('#type').on('change', function(e){
        if($('#map-number-box').is(':visible') === true && $(this).val() !== '10'){
            $('#map-number-box').slideUp("fast");
        } else if($('#map-number-box').is(':visible') === false && $(this).val() === '10') {
            $('#map-number-box').slideDown("fast");
        }
    });

    // TODO: CHECK MAP NUMBER TYPE!!!
});
