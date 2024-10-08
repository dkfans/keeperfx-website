
// Document ready
$(function(){

    // Load popovers
    $.each($('[data-bs-toggle="popover"]'), function(i, element){
        new bootstrap.Popover(element, {trigger: 'hover'})
    });

    // Show outgoing icon on URLs on hover
    $('a.outgoing-hover-icon ion-icon').hide();
    $('a.outgoing-hover-icon').on('mouseenter', function(e){
        $(this).find('ion-icon').show();
    }).on('mouseleave', function(e){
        $(this).find('ion-icon').hide();
    })

    // Load dynamic timestamps
    jQuery.timeago.settings.allowFuture = true;
    $("time").timeago();
    $("time").attr("title", null);

    // Handle failed/invalid json responses for Ajax calls
    $(document).ajaxError(function (event, xhr, settings){

        // Only handle json data types
        if(settings.dataType !== 'json'){
            return false;
        }

        // Unauthorized check
        if(xhr.status === 401){
            toastr.warning('You need to be logged in to do this.');
            return false;
        }

        toastr.error('Something went wrong.');
    });

    // Show button loader on form submits
    $("form").on('submit', function(event){
        $(this).find('.btn[type=submit]').buttonLoader(true);
    });

    // Send email verifications
    if(app_store.send_email != -1){

        $.ajax({
            type: 'GET',
            url: '/email/send/' + app_store.send_email,
            dataType: 'json'
        });
    }

    // Handle spoilers
    $.each($('.spoiler'), function(i, el){
        let $el = $(el);

        // Remove fallback spoiler CSS
        $el.removeClass('spoiler-hover');
        $el.addClass('spoiler-clickable');

        // Handle click
        $el.on('click', function(e){
            $(this).css('transition', '0.3s');
            $(this).removeClass('spoiler-clickable');
            $(this).removeClass('spoiler');
        });
    });
});
