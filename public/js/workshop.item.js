

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

                toastr.success('You have successfully removed your rating for this workshop item.');

            },
        });
    });
});
