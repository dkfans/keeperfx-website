
var $imageBox = $('<div></div>').addClass('image-widget-box');

function renderImageWidget()
{
    var imageCount = Object.keys(imageWidgetData).length;

    var $container = $('#image-widget-container');

    // Clear widget container
    $container.html('');

    // Add pictures
    for(let i = 0; i < imageCount; i++){
        $container.append(
            $imageBox.clone().addClass('image-widget-image').append(
                $('<img></img>').attr('src', imageWidgetData[i].src)
            )
        );
    }

    // Show upload button
    $container.append($imageBox.clone().addClass('image-widget-upload-button'));

    // Add placeholders if image count is below threshold
    if(imageCount < 2){
        $container.append($imageBox.clone());
        if(imageCount < 1){
            $container.append($imageBox.clone());
        }
    }
}

function getImageWidgetFileList()
{
        let dataTransfer = new DataTransfer();

        $.each(imageWidgetData, function(i, image){
            dataTransfer.items.add(image.file);
        });

        return dataTransfer.files;
}

$(function(){

    // Make sure image widget data has been defined
    if(typeof imageWidgetData == 'undefined'){
        console.warning('imageWidgetData is not defined');
        return;
    }

    // Make sure browser API's exist
    if (!window.File || !window.FileReader || !window.FileList || !window.Blob) {
        console.warning('The File APIs are not fully supported in this browser.');
        return;
    }
    if (!URL || !URL.createObjectURL) {
        console.warning('The URL APIs are not fully supported in this browser.');
        return;
    }

    // Show image widget (because it should not be visible if javascript is disabled)
    $('#image-widget-container').show();
    renderImageWidget();

    // Handle sorting/drag/drop
    $('#image-widget-container').sortable({
        placeholder: "ui-sortable-placeholder",
        zIndex: 100,
        items: ">.image-widget-image",
        opacity: 0.5,
        tolerance: "pointer",
        distance: 1,
        appendTo: "body",
    });

    // Handle file uploading
    $('#image-widget-container').on('click', function(e){

        // Check if clicking on the upload button
        let $target = $(e.target);
        if(!$target.hasClass('image-widget-upload-button')){
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

                // Add images to widget
                imageWidgetData[Object.keys(imageWidgetData).length] = {
                    'id': null,
                    'name': file.name,
                    'size': file.size,
                    'src': URL.createObjectURL(file),
                    'file': file
                };
            });

            renderImageWidget();
        });

        // Open browser file input
        $input.click();
    });

    // TODO: change order in data object to match position on drag/drop

});
