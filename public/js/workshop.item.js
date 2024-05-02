// Emoji UTF-8 ranges
var emojiPickerRanges = [
    [128512, 128591],
    [9989,9990],
    [10060,10061]
];

// Variables to temporary hold the comment we are moving
var movingCommentId;
var $movingCommentElement;

// This function handles the ratings.
// It's done this way because when we update a rating the server provides a new HTML render,
// but we want to make this new HTML also be able to handle the rating.
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

// Disable comment move mode
function disableCommentMoveMode(){

    // Reset elements
    $('.comment-buttons').show();
    $('.workshop-item-move-box').hide();
    $('.workshop-item-move-cancel-box').hide();
    $movingCommentElement.removeClass('border-primary');

    // Reset variables
    movingCommentId = null;
    $movingCommentElement = null;

    return;
}

// Wait for document load
$(function(e){

    // Initialize image lightbox
    var lightbox = new PhotoSwipeLightbox({
        bgOpacity: 0.8,
        gallery: '#workshop-images',
        children: 'a.workshop-image',
        pswpModule: PhotoSwipe,
        mainClass: 'pswp-with-perma-preloader',
        initialZoomLevel: 'fit',
        showHideAnimationType: 'zoom'
    });

    // Init lightbox
    lightbox.init();

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

    // Hide comment submit extra
    $('#comment-submit-extra').hide();

    // Show comment submit extra when clicking in the comment box
    $('#comment-input').on('focus', function(e){
        $('#comment-submit-extra').slideDown('fast');
    });

    // Hide comment area on cancel
    $('#comment-cancel').on('click', function(e){
        $('#comment-submit-extra').slideUp('fast');
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
        let $commentElement = $("#comment-" + commentId);

        if($commentElement.length === 0){

            // We should display a warning because a notification can link to a comment that has been deleted
            toastr.warning('Comment not found');

            // Remove the hash bang from the URL
            window.location.hash = "";

        } else {

            // Add a nice border to the left
            $commentElement.css('border-left', '3px solid white');

            // Scroll the comment into view
            $commentElement[0].scrollIntoView({
                behavior: 'auto',
                block: 'center',
                inline: 'center'
            });

            // Animation to highlight comment for a small time
            let originalBackgroundColor = $commentElement.css('background-color');
            if(typeof originalBackgroundColor === 'string'){
                $commentElement.animate({ scale: '1', backgroundColor: 'rgba(100,100,100,0.08)' }, 300);
                $commentElement.animate({ backgroundColor: originalBackgroundColor }, 450);
            }
        }
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
        let $originalContentElement = $commentElement.find('.workshop-item-comment-content').first();
        let $editContentElement     = $commentElement.find('.workshop-item-comment-edit').first();
        let $editTextarea           = $editContentElement.find('textarea').first();
        let $isEditedElement        = $commentElement.find('.workshop-comment-is-edited').first();
        let $replyForm              = $commentElement.find('form[data-comment-reply="true"]').first();
        let $collapseUp             = $(this).find('[data-comment-collapse="up"]');
        let $collapseDown           = $(this).find('[data-comment-collapse="down"]');

        // Make sure variables are found
        if(typeof userId == 'undefined' || typeof commentId == 'undefined' || typeof action == 'undefined'){
            toastr.error('Something went wrong.');
            return false;
        }

        // Collapse
        if(action === "collapse")
        {
            $collapseDown.toggle();
            $collapseUp.toggle();
            $originalContentElement.toggle();
            $commentElement.find('.workshop-item-comment').toggle();
            return true;
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

            // If the reply form is already visible we hide it
            if($replyForm.is(':visible')){
                $replyForm.slideUp('fast');
            }

            // Remember that the comment is being edited
            $commentElement.data('comment-edit', true);

            // Rate the workshop item
            $.ajax({
                type: 'GET',
                url: '/api/v1/workshop/comment/' + commentId,
                dataType: 'json', // return type data,
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

            // Edit the comment
            $.ajax({
                type: 'PUT',
                url: '/workshop/item/' + workshop_item.id + '/comment/' + commentId,
                dataType: 'json', // return type data,
                data: {
                    content: $editArea.val(),
                    [app_store.csrf.keys.name]: app_store.csrf.name,
                    [app_store.csrf.keys.value]: app_store.csrf.value
                },
                success: function(data){

                    if(typeof data.success === 'undefined'){
                        toastr.error('Something went wrong.');
                        return false;
                    }

                    if(!data.success){
                        toastr.error('Failed to edit comment.');
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

            return true;
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
                success: function(data){

                    if(typeof data.success === 'undefined' || !data.success){
                        toastr.error('Something went wrong.');
                        return false;
                    }

                    $commentElement.remove();

                    toastr.success("Comment deleted!");

                    // Show 'no comments' message if there are no comments now
                    if($('[data-comment-id]').length == 0){
                        $('#no-comments').show();
                    }
                }
            });

            return true;
        }

        // Reply
        if(action === "reply")
        {
            $('textarea').blur();

            // Close the edit area if its open
            $editTextarea.text('');
            $editContentElement.hide();
            $originalContentElement.show();
            $commentElement.data('comment-edit', false);

            // If the reply form is already visible we hide it
            if($replyForm.is(':visible')){
                $replyForm.slideUp('fast');
                return true;
            }

            // Hide all forms
            $('form[data-comment-reply="true"]').slideUp('fast');

            // Show this reply form
            $replyForm.slideDown('fast');
            $replyForm.find('textarea').focus();

            return true;
        }

        if(action === 'cancel-reply'){

            $('textarea').blur();

            // Close the edit area if its open
            $editTextarea.text('');
            $editContentElement.hide();
            $originalContentElement.show();
            $commentElement.data('comment-edit', false);

            // If the reply form is already visible we hide it
            if($replyForm.is(':visible')){
                $replyForm.slideUp('fast');
                return true;
            }

            return true;
        }

        if(action === "report")
        {
            // Show report modal
            let commentContents = $(this).parents('.workshop-item-comment').first().find('.workshop-item-comment-content').html();
            $('#commentModalArea').html(commentContents);
            $('#reportModal').data('comment-id', commentId);
            $('#reportModal').modal('show');
            return true;
        }

        if(action === "move")
        {
            movingCommentId       = commentId;
            $movingCommentElement = $commentElement;

            // Hide all comment buttons because we are in "move" mode
            $('.comment-buttons').hide();

            // Highlight the current comment
            $commentElement.addClass('border-primary');

            // Show places where to move it to
            $('.workshop-item-move-box').show();

            // But don't show this place, instead show "cancel move"
            $commentElement.find('.workshop-item-move-box').hide();
            $commentElement.find('.workshop-item-move-cancel-box').show();

            return true;
        }
    });

    // Disable comment move mode
    $('[data-disable-move-comment-mode="true"]').on('click', disableCommentMoveMode);

    // Handle "Move comment here"
    $('[data-move-comment-to]').on('click', function(e){

        // Get the location where we need to move the comment to
        let moveCommentTo = $(this).data('move-comment-to');

        var moveTarget = $(this).parent();

        // Edit the comment
        $.ajax({
            type: 'PUT',
            url: '/workshop/item/' + workshop_item.id + '/comment/' + movingCommentId,
            dataType: 'json', // return type data,
            data: {
                parent: moveCommentTo,
                [app_store.csrf.keys.name]: app_store.csrf.name,
                [app_store.csrf.keys.value]: app_store.csrf.value
            },
            success: function(data){

                if(typeof data.success === 'undefined'){
                    toastr.error('Something went wrong.');
                    return false;
                }

                if(!data.success){
                    toastr.error('Failed to move comment.');
                    return false;
                }

                // Success
                moveTarget.replaceWith($movingCommentElement);
                toastr.success('Comment has been successfully moved!');

                disableCommentMoveMode();
            }
        });
    });

    // Report modal: Select reason textarea on show
    $('body').on('shown.bs.modal', '#reportModal', function () {
        $('textarea[name="reason"]', this).focus();
    })

    // Comment report
    $('#reportComment').on('click', function(e){

        $('#reportComment').prop('disabled', true);

        let reason = $('#reportModal textarea[name=reason]').val();
        let commentId = $('#reportModal').data('comment-id');

        $.ajax({
            type: 'POST',
            url: '/workshop/report/comment/' + commentId,
            dataType: 'json', // return type data,
            data: {
                'reason': reason,
                [app_store.csrf.keys.name]: app_store.csrf.name,
                [app_store.csrf.keys.value]: app_store.csrf.value
            },
            complete: function(e) {
                $('#reportComment').prop('disabled', false);
            },
            success: function(data){

                if(typeof data.success === 'undefined' || !data.success){
                    toastr.error('Something went wrong.');
                    return false;
                }

                toastr.success("Comment reported!");

                $('#reportModal').modal('hide');
            }
        });

    });

    // Moderator: Close report
    $('button[data-comment-report-button="close"]').on('click', function(){

        // Variables
        let $allReportsElement     = $(this).closest('.workshop-item-comment-reports');
        let $reportElement         = $(this).closest('.workshop-item-comment-report');
        let $commentElement        = $(this).closest('.workshop-item-comment');
        let $commentContentElement = $commentElement.find('.workshop-item-comment-content');
        let commentId              = $reportElement.data('report-id');

        $.ajax({
            type: 'DELETE',
            url: '/workshop/report/comment/' + commentId,
            dataType: 'json', // return type data,
            data: {
                [app_store.csrf.keys.name]: app_store.csrf.name,
                [app_store.csrf.keys.value]: app_store.csrf.value
            },
            success: function(data){

                if(typeof data.success === 'undefined' || !data.success){
                    toastr.error('Something went wrong.');
                    return false;
                }

                toastr.success("Report removed!");

                // Remove the report alert
                $reportElement.remove();

                // Remove red border from comment if this was the last report that is being removed
                if($allReportsElement.find('.workshop-item-comment-report').length === 0){
                    $commentContentElement.removeClass('workshop-comment-report');
                }
            }
        });
    });

    var emojiCounter = 0;
    $.each($('[data-emoji-picker="true"]'), function(i, el){

        let textarea = $(el).parents('.workshop-item-comment-edit, form').find('textarea');
        let textareaId = $(textarea).attr('id');

        if(typeof textareaId == 'undefined'){
            textareaId = 'textarea-' + emojiCounter;
        }

        let emojiPickerId = 'emoji-picker-' + emojiCounter;
        emojiCounter++;

        $(el).attr('id', emojiPickerId);
        $(textarea).attr('id', textareaId);

        new emojiButtonList( emojiPickerId, {
            textBoxID: textareaId,
            emojiRangesToShow: emojiPickerRanges
        })
    });

});
