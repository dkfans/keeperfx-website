

function handleRatingHtml(el){

    var $ratingBox     = $(el);
    var ratingType     = $ratingBox.attr('data-workshop-rating-type');

    $.each($(el).find('img'), function(index, element){

        var ratingScore = parseInt($(element).attr('data-rating-score'));

        $(element).css('cursor', 'pointer');

        $(element).on('click', function(e){

            // Hide open popover
            $('.popover').hide();

            // Make sure we are logged in
            if(app_store.account === null){
                window.location = '/login'
                    + '?redirect=/workshop/item/' + workshop_item.id + '/' + workshop_item.slug
                    + '&msg=workshop-rate';
                return;
            }

            // Disable rating own account items
            if(ratingType === 'quality' && app_store.account.id === workshop_item.submitter_id){
                toastr.warning('You can not rate your own workshop items!');
                return;
            }

            // Rate the workshop item
            $.ajax({
                type: 'POST',
                url: '/workshop/rate/' + workshop_item.id + '/' + ratingType,
                data: {
                    score: ratingScore,
                    [app_store.csrf.keys.name]: app_store.csrf.name,
                    [app_store.csrf.keys.value]: app_store.csrf.value
                },
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

                    // Update rating stars HTML
                    $ratingBox.html(data.html);
                    handleRatingHtml($ratingBox);

                    // Set template for rating info
                    $('#' + ratingType + '-rating-info').html('<span id="' + ratingType + '-rating-score" class="text-muted"></span> / 5' +
                        '<span style="margin: 0 3px" class="text-muted"> &bull; </span>' +
                        '<span id="' + ratingType + '-rating-count" class="text-muted"></span>' +
                        '<span style="margin: 0 3px" class="text-muted"> &bull; </span>' +
                        'Your rating: ' +
                        '<span id="' + ratingType + '-rating-self" class="text-muted"></span> ' + // needs a space at the end
                        '<a href="#" data-workshop-rating-type="' + ratingType + '" class="rating-remove text-stand-out">Remove</a>'
                    );

                    // Update rating score
                    $('#' + ratingType + '-rating-score').text(data.rating_score);

                    // Update rating count
                    if(data.rating_count === 1){
                        $('#' + ratingType + '-rating-count').text('1 rating');
                    } else {
                        $('#' + ratingType + '-rating-count').text('' + data.rating_count + ' ratings');
                    }

                    // Update self rating
                    $('#' + ratingType + '-rating-self').text(ratingScore);

                    // Update CSRF tokens
                    app_store.csrf.name = data.csrf.name;
                    app_store.csrf.value = data.csrf.value;
                    $('input[name=csrf_name]').val(data.csrf.name);
                    $('input[name=csrf_value]').val(data.csrf.value);

                    toastr.success('You have successfully rated this workshop item!');

                },
            });
        });

        new bootstrap.Popover(element, {
            'placement': 'top',
            'trigger': 'hover',
            'content': 'Rate this item ' + ratingScore + ' out of 5'
        });
    });
}

$(function(e){

    // Render workshop ratings
    $.each($('[data-workshop-rating-type]'), function(i, el){
        handleRatingHtml(el);
    });

    // Show 'Show all versions' and hide 'Versions list'
    // This is users with javascript disabled can still view all files
    $('#show-all-versions').show();
    $('#all-versions-list').hide();

    // Handle 'Show all versions'
    $('#show-all-versions a').on('click', function(e){
        e.preventDefault();
        if($('#all-versions-list').is(':visible') === true){
            $(this).text('Show all versions (' + $('#all-versions-list tbody tr').length + ')');
            $('#all-versions-list').slideUp("fast");
        } else if($('#all-versions-list').is(':visible') === false) {
            $(this).text('Hide all versions (' + $('#all-versions-list tbody tr').length + ')');
            $('#all-versions-list').slideDown("fast");
        }
    });

    // Hide comment submit button
    $('#comment-submit-button').hide();

    // Show comment submit button when clicking in the comment box
    $('#comment-input').on('focus', function(e){
        if($(this).val().length === 0){
            $('#comment-submit-button').slideDown('fast');
        }
    });
    $('#comment-input').on('focusout', function(e){
        if($(this).val().length === 0){
            $('#comment-submit-button').slideUp('fast');
        } else {
            $('#comment-submit-button').slideDown('fast');
        }
    });

    // Handle remove rating
    $('body').on('click', function(e){

        let $target = $(e.target);
        if(!$target.hasClass('rating-remove')){
            return true;
        }

        e.preventDefault();

        var ratingType = $target.attr('data-workshop-rating-type');
        var $ratingBox = $target.parent().parent().parent().find('span:first');

        // Rate the workshop item
        $.ajax({
            type: 'POST',
            url: '/workshop/rate/' + workshop_item.id + '/' + ratingType + '/remove',
            data: {
                [app_store.csrf.keys.name]: app_store.csrf.name,
                [app_store.csrf.keys.value]: app_store.csrf.value
            },
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

                // Update rating stars HTML
                $ratingBox.html(data.html);
                handleRatingHtml($ratingBox);

                if(data.rating_count === 0){
                    $('#' + ratingType + '-rating-info').text('Not rated yet');
                } else {
                    // Set template for rating info
                    $('#' + ratingType + '-rating-info').html('<span id="' + ratingType + '-rating-score" class="text-muted"></span> out of 5' +
                        '<span style="margin: 0 3px" class="text-muted"> &bull; </span>' +
                        '<span id="' + ratingType + '-rating-count" class="text-muted"></span>'
                    );

                    // Update rating score
                    $('#' + ratingType + '-rating-score').text(data.rating_score);

                    // Update rating count
                    if(data.rating_count === 1){
                        $('#' + ratingType + '-rating-count').text('1 rating');
                    } else {
                        $('#' + ratingType + '-rating-count').text('' + data.rating_count + ' ratings');
                    }
                }

                // Update CSRF tokens
                app_store.csrf.name = data.csrf.name;
                app_store.csrf.value = data.csrf.value;
                $('input[name=csrf_name]').val(data.csrf.name);
                $('input[name=csrf_value]').val(data.csrf.value);

                toastr.success('You have successfully removed your rating for this workshop item.');

            },
        });
    });

    // Check hash bang for comment to focus onto it
    var hashBang = window.location.hash.substr(1);
    if(hashBang.match("^comment\-")){
        let commentId = hashBang.slice(8);
        $("#comment-" + commentId).css('border', '1px solid white');
        $("#comment-" + commentId).animate({ scale: '1' }, 200);
        $("#comment-" + commentId)[0].scrollIntoView({
            behavior: 'auto',
            block: 'center',
            inline: 'center'
        });
    }

    // Show reply and comment menu buttons
    $('.comment-buttons').show();

    // Handle comment actions
    $('[data-comment-action]').on('click', function(e){

        e.preventDefault();

        // Get variables
        let action                  = $(this).data('comment-action');
        let $commentElement         = $(this).parents('.workshop-item-comment').first();
        let userId                  = $commentElement.data('comment-user-id');
        let commentId               = $commentElement.data('comment-id');
        let $originalContentElement = $commentElement.find('.workshop-item-comment-content');
        let $editContentElement     = $commentElement.find('.workshop-item-comment-edit');
        let $editTextarea           = $editContentElement.find('textarea');
        let $isEditedElement        = $commentElement.find('.workshop-comment-is-edited');

        // Make sure variables are found
        if(typeof userId == 'undefined' || typeof commentId == 'undefined' || typeof action == 'undefined'){
            toastr.error('Something went wrong.');
            return false;
        }

        // Edit
        if(action === "edit")
        {
            // If this comment is not ours, and we don't have a role of moderator or higher
            if(userId !== app_store.account.id && app_store.account.role < 5){
                toastr.error("You can not edit this comment");
                return false;
            }

            // If this comment is already being edited, we'll close it again
            if($commentElement.data('comment-edit') == true){
                $editTextarea.text('');
                $editContentElement.hide();
                $originalContentElement.show();
                $commentElement.data('comment-edit', false);
                return true;
            }

            $commentElement.data('comment-edit', true);

            // Rate the workshop item
            $.ajax({
                type: 'GET',
                url: '/api/v1/workshop/comment/' + commentId,
                dataType: 'json', // return type data,
                error: function(data){
                    toastr.error('Something went wrong.');
                },
                success: function(data){

                    console.log(data);

                    if(typeof data.workshop_comment === 'undefined'){
                        toastr.error('Something went wrong.');
                        return false;
                    }

                    $originalContentElement.hide();
                    $editTextarea.text(data.workshop_comment.content);
                    $editContentElement.show();
                }
            });
        }

        // Cancel Edit
        if(action === "cancel-edit"){
            $editTextarea.text('');
            $editContentElement.hide();
            $originalContentElement.show();
            $commentElement.data('comment-edit', false);
            return true;
        }

        // Do Edit
        if(action === "do-edit") {
            let $editArea = $editContentElement.find('[data-comment-edit-area]');

            // Make sure new comment is not empty
            if($editArea.val() === ""){
                toastr.warning("Comment can not be empty.");
                return true;
            }

            // Edit the workshop item
            $.ajax({
                type: 'PUT',
                url: '/workshop/item/' + workshop_item.id + '/comment/' + commentId,
                dataType: 'json', // return type data,
                data: {
                    content: $editArea.val(),
                    [app_store.csrf.keys.name]: app_store.csrf.name,
                    [app_store.csrf.keys.value]: app_store.csrf.value
                },
                error: function(data){
                    toastr.error('Something went wrong.');
                },
                success: function(data){

                    if(typeof data.success === 'undefined' || !data.success){
                        toastr.error('Something went wrong.');
                        return false;
                    }

                    if(typeof data.workshop_comment === 'undefined'){
                        toastr.error('Something went wrong.');
                        return false;
                    }

                    $originalContentElement.html(data.workshop_comment.content_html);
                    $editTextarea.text('');
                    $editContentElement.hide();
                    $originalContentElement.show();
                    $commentElement.data('comment-edit', false);
                    $isEditedElement.show();

                    toastr.success("Comment updated!");
                }
            });
        }

        // Delete
        if(action === "delete"){

            $.ajax({
                type: 'DELETE',
                url: '/workshop/item/' + workshop_item.id + '/comment/' + commentId,
                dataType: 'json', // return type data,
                data: {
                    [app_store.csrf.keys.name]: app_store.csrf.name,
                    [app_store.csrf.keys.value]: app_store.csrf.value
                },
                error: function(data){
                    toastr.error('Something went wrong.');
                },
                success: function(data){

                    if(typeof data.success === 'undefined' || !data.success){
                        toastr.error('Something went wrong.');
                        return false;
                    }

                    $commentElement.remove();

                    toastr.success("Comment deleted!");

                    // Show 'no comments' message when the item has no more items after this
                    if($('[data-comment-id]').length == 0){
                        $('#no-comments').show();
                    }
                }
            });
        }

















        // if(action === "report")
        // {
        //     // Show report modal
        //     let commentContents = $(this).parents('.workshop-item-comment').first().find('.workshop-item-comment-content').html();
        //     $('#commentModalArea').html(commentContents);
        //     $('#reportModal').modal('show');
        //     return true;
        // }

        // if(action === "reply")
        // {
        //     $('textarea').blur();

        //     let replyForm = $(this).parents('.workshop-item-comment').first().find('form[data-comment-reply="true"]');

        //     // If the reply form is already visible we hide it
        //     if(replyForm.is(':visible')){
        //         replyForm.slideUp('fast');
        //         return true;
        //     }

        //     // Hide all forms
        //     $('form[data-comment-reply="true"]').slideUp('fast');

        //     // Show this reply form
        //     replyForm.slideDown('fast');
        //     replyForm.find('textarea').focus();

        //     return true;
        // }




    });

});
