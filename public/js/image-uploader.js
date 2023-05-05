var uploaderImages = {};
var $imageBox      = $('<div></div>').addClass('image-upload-box');

function renderImageUploader()
{
    var imageCount = Object.keys(uploaderImages).length;

    var $container = $('#image-uploader-container');

    // Clear upload container
    $container.html('');

    // Add pictures
    for(let i = 0; i < imageCount; i++){
        $container.append(
            $imageBox.clone().addClass('image-upload-image').append(
                $('<img></img>').attr('src', uploaderImages[i].src)
            )
        );
    }

    // Show upload button
    $container.append($imageBox.clone().addClass('image-upload-button'));

    // Add placeholders if image count is below threshold
    if(imageCount < 2){
        $container.append($imageBox.clone());
        if(imageCount < 1){
            $container.append($imageBox.clone());
        }
    }
}

$(function(){

    if (!window.File || !window.FileReader || !window.FileList || !window.Blob) {
        console.warning('The File APIs are not fully supported in this browser.');
        return;
    }

    if (!URL || !URL.createObjectURL) {
        console.warning('The URL APIs are not fully supported in this browser.');
        return;
    }

    // Show image uploader (because it should not be visible if javascript is disabled)
    $('#image-uploader').show();
    renderImageUploader();

    // Handle sorting/drag/drop
    $('#image-uploader-container').sortable({
        placeholder: "ui-sortable-placeholder",
        zIndex: 100,
        items: ">.image-upload-image",
        opacity: 0.5,
        tolerance: "pointer",
        distance: 1,
        appendTo: "body",
    });

    // Handle file uploading
    $('#image-uploader-container').on('click', function(e){

        // Check if clicking on the upload button
        let $target = $(e.target);
        if(!$target.hasClass('image-upload-button')){
            return;
        }

        // Create dynamic file input
        var $input = $('<input></input>')
            .attr('type', 'file')
            .attr('multiple', true)
            .attr('accept', '.jpg,.jpeg,.png,.webp,.gif');

        // Handle file input
        $input.on('change', function(e){

            // Loop trough all files
            $.each($(this)[0].files, function(i, file){

                // Check file size
                if(file.size > app_store.upload_limit.workshop_image.size){
                    toastr.warning('Image "' + file.name + '" exceeds maximum file size of ' + app_store.upload_limit.workshop_image.formatted);
                    return;
                }

                // Add images to uploader object
                uploaderImages[Object.keys(uploaderImages).length] = {
                    'name': file.name,
                    'size': file.size,
                    'src': URL.createObjectURL(file)
                };
            });

            renderImageUploader();
        });

        // Open browser file input
        $input.click();
    });

});
