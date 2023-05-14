
var $imageBox = $('<div></div>').addClass('image-widget-box').append(
    $('<button></button>').addClass('image-widget-delete-button')
);

// Convert blob to base64
const blobToDataUrl = blob => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
});

function renderImageWidget()
{
    var imageCount = Object.keys(imageWidgetData).length;
    var shownImageCount = 0;

    var $container = $('#image-widget-container');

    // Clear widget container
    $container.html('');

    // Add pictures
    for(let i = 0; i < imageCount; i++){

        if(typeof imageWidgetData[i].data !== 'undefined' && imageWidgetData[i].data !== null){
            $container.append(
                $imageBox.clone().addClass('image-widget-image').append(
                    $('<img></img>').attr('src', imageWidgetData[i].data)
                )
            );
            shownImageCount++;
        } else if (typeof imageWidgetData[i].src !== 'undefined' && imageWidgetData[i].src !== null){
            $container.append(
                $imageBox.clone().addClass('image-widget-image').append(
                    $('<img></img>').attr('src', imageWidgetData[i].src)
                )
            );
            shownImageCount++;
        } else {
            $container.append(
                $imageBox.clone().addClass('image-widget-image').addClass('image-widget-image-invisible').hide()
            );
        }

    }

    // Show upload button
    $container.append($imageBox.clone().addClass('image-widget-upload-button'));

    // Add placeholders if image count is below threshold
    if(shownImageCount < 2){
        $container.append($imageBox.clone());
        if(shownImageCount < 1){
            $container.append($imageBox.clone());
        }
    }
}

function getImageWidgetPostData()
{
    return JSON.stringify(imageWidgetData);
}

$(function(){

    // Make sure image widget data has been defined
    if(typeof imageWidgetData == 'undefined'){
        console.warn('imageWidgetData is not defined');
        return;
    }

    // Make sure browser API's exist
    if (!window.File || !window.FileReader || !window.FileList || !window.Blob) {
        console.warn('The File APIs are not fully supported in this browser.');
        return;
    }
    if (!URL || !URL.createObjectURL) {
        console.warn('The URL APIs are not fully supported in this browser.');
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
        update: function(event, ui){

            var oldIndex = ui.item.data('oldIndex');
            var newIndex = ui.item.index();

            let movingImage = imageWidgetData[oldIndex];
            var newObject = {};
            var index = 0;

            for(let i = 0; i < Object.keys(imageWidgetData).length; i++) {

                if(index === oldIndex){
                    index++;
                }

                if(i === newIndex){
                    newObject[i] = movingImage;
                    continue;
                }

                newObject[i] = imageWidgetData[index];

                index++;
            }

            imageWidgetData = newObject;
        },
        start: function(event, ui) {
            ui.item.data('oldIndex', ui.item.index());
        }
    });

    // Handle file uploading
    $('#image-widget-container').on('click', function(e){

        // Check if clicking on the delete button
        let $target = $(e.target);
        if($target.hasClass('image-widget-delete-button')){
            e.preventDefault();
            let imageIndex = $target.parent().index();
            imageWidgetData[imageIndex].src = null;
            imageWidgetData[imageIndex].data = null;
            renderImageWidget();
        }

        // Check if clicking on the upload button
        if($target.hasClass('image-widget-upload-button')){

            // Create dynamic file input
            var $input = $('<input></input>')
                .attr('type', 'file')
                .attr('multiple', true)
                .attr('accept', '.jpg,.jpeg,.png,.webp,.gif');

            // Handle file input
            $input.on('change', function(e){

                // Loop trough all files
                $.each($(this)[0].files, async function(i, file){

                    // Check file size
                    if(file.size > app_store.upload_limit.workshop_image.size){
                        toastr.warning('Image "' + file.name + '" exceeds maximum file size of ' + app_store.upload_limit.workshop_image.formatted);
                        return;
                    }

                    // let dataString = await blobToDataUrl(file);
                    // let data = dataString.split(',')[1];
                    let data = await blobToDataUrl(file);

                    // Add images to widget
                    imageWidgetData[Object.keys(imageWidgetData).length] = {
                        'id': null,
                        'name': file.name,
                        // 'size': file.size,
                        'src': null,
                        'data': data
                    };

                    renderImageWidget();
                });

            });

            // Open browser file input
            $input.click();
        }
    });

});
