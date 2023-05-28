$(function(){

    // Show rename button when javascript is enabled
    $('[data-rename-id]').show();

    // Handle renames
    $('[data-rename-id]').on('click', function(e){

        e.preventDefault();

        var $renameButton   = $(this);
        var fileId          = $renameButton.attr('data-rename-id');

        let currentFilename   = $renameButton.attr('data-rename-filename');
        let filenameParts     = currentFilename.split('.');
        let filenameExtension = filenameParts.pop();
        let filenameName      = filenameParts.join('.');

        var newName = window.prompt("New filename (without file extension)", filenameName);

        if(newName.length < 1 || newName == filenameName){
            return;
        }

        if(newName.length > 64){
            alert('Filename too long. 64 characters max');
            return;
        }

        // TODO: check valid filename

        $.ajax({
            type: 'POST',
            url: '/workshop/edit/'+ workshop_item.id + '/files/' + fileId + '/rename',
            data: {
                name: newName,
                [app_store.csrf.keys.name]: app_store.csrf.name,
                [app_store.csrf.keys.value]: app_store.csrf.value
            },
            dataType: 'json', // return type data,
            error: function(data){
                toastr.error('Something went wrong.');
            },
            success: function(data){

                // Make sure rename went successful
                if(typeof data.success === 'undefined' || data.success !== true){
                    toastr.error('Something went wrong.');
                    return;
                }

                var $filenameAnchor   = $renameButton.parent().find('a:first');

                $renameButton.attr('data-rename-filename', data.filename);
                $filenameAnchor.text(data.filename);
                $filenameAnchor.attr('href', '/workshop/download/' + workshop_item.id + '/' + fileId + '/' + data.filename);

                // Update CSRF tokens
                app_store.csrf.name = data.csrf.name;
                app_store.csrf.value = data.csrf.value;

                toastr.success('File has been renamed!');
            }
        });

    });

});
