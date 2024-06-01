
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
    $("time").timeago();

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
    if(app_store.send_email !== false){

        $.ajax({
            type: 'GET',
            url: '/email/send/' + app_store.send_email,
            dataType: 'json'
        });
    }
});
