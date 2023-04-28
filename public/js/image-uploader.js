var uploaderImages = {};

function renderUploader()
{
    var imageCount = Object.keys(uploaderImages).length;

    var $container = $('#image-uploader-container');

    var $imageBox = $('<div></div>')
        .addClass('image-upload-box');

    var $thumbnailBox = $imageBox.clone()
        .addClass('image-upload-box-thumbnail');

    // Clear upload container
    $container.html('');

    // Handle no images
    if(imageCount === 0){
        $container
            .append(
                $thumbnailBox.clone().addClass('image-upload-button')
            )
            .append($imageBox.clone())
            .append($imageBox.clone());

        return;
    }

    // Handle 1 image
    if(imageCount === 1){
        $container
            .append(
                $thumbnailBox.clone().addClass('image-upload-image').append(
                    $('<img></img>').attr('src', uploaderImages[0].src)
                )
            )
            .append($imageBox.clone().addClass('image-upload-button'))
            .append($imageBox.clone());

        return;
    }

    // Handle more than 1 image
    if(imageCount > 1){
        $container
            .append(
                $thumbnailBox.clone().addClass('image-upload-image').append(
                    $('<img></img>').attr('src', uploaderImages[0].src)
                )
            );

        for(let i = 1; i < imageCount; i++){
            $container
            .append($imageBox.clone().addClass('image-upload-image').append(
                $('<img></img>').attr('src', uploaderImages[i].src)
            ));
        }

        $container.append($imageBox.clone().addClass('image-upload-button'))

        return;
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

            var files  = $(this)[0].files;

            $.each(files, function(i, file){

                // Check filesize
                if(file.size > app_store.upload_limit.workshop_image.size){
                    toastr.warning('Image "' + file.name + '" exceeds maximum filesize of ' + app_store.upload_limit.workshop_image.formatted);
                    return;
                }

                // Add images to uploader object
                uploaderImages[Object.keys(uploaderImages).length] = {
                    'name': file.name,
                    'size': file.size,
                    'src': URL.createObjectURL(file)
                };
            });

            renderUploader();



            // var reader = new FileReader();

            // reader.onload = function (e) {
            //     $('#blah').attr('src', e.target.result);
            // }

            // reader.readAsDataURL(input.files[0]);

        });

        // Open browser file input
        $input.click();
    });
});
