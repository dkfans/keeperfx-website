
function handleSpoilers()
{
    $.each($('.spoiler'), function(i, el){
        let $el = $(el);

        // Reset if it is already initialized
        $el.off('click');

        // Remove fallback spoiler CSS
        $el.removeClass('spoiler-hover');
        $el.addClass('spoiler-clickable');

        // Handle click
        $el.on('click', function(e){

            $(this).css('transition', '0.3s');
            $(this).removeClass('spoiler-clickable');

            // We remove the spoiler class
            // This makes the element impossible to be re-initialized
            // If we ever do want to reinitialize them, the logic needs to be changed
            $(this).removeClass('spoiler');
        });
    });
}

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
    handleSpoilers();

    // Check for a possible slow CloudFlare endpoint on download (workshop and alphas).
    // Some providers don't work nicely with CloudFlare so this check will see what
    // country we are from, and what CloudFlare endpoint we're connected to.
    // If this is a known combo that produces slow downloads we'll alert the user.
    if(app_store.cf_slow_endpoint_check)
    {
        $('a[href^="/workshop/download/"], a[href^="/download/"]').on('click', function(e){

            // Try and get the CloudFlare CDN trace
            $.ajax({
                type: 'GET',
                url: '/cdn-cgi/trace',
                success: function(data){

                    let knowIssues = false;

                    // Grab `colo` and `loc`
                    let colo = data.match(/colo=(\w+)/)[1];
                    let loc = data.match(/loc=(\w+)/)[1];
                    if(typeof colo == 'undefined' || typeof loc == 'undefined'){
                        return;
                    }

                    // Check for Germany connecting to an endpoint in the US
                    if(colo == "EWR" && loc == "DE"){
                        knowIssues = true;
                    }

                    // If there are known issues we'll show a notice
                    if(knowIssues){
                        toastr.info('You might be experiencing slow download speeds. Please try again later if you have any issues.');
                    }
                }
            });
        });
    }

    // Check if URL has #nav-top and scroll instantly to it using an offset
    if (window.location.hash === '#nav-top') {

        // Scroll
        const $navTop = $('#nav-top');
        if ($navTop.length) {
            window.scrollTo({top:$navTop[0].offsetTop - 15, behavior: 'auto'});
        }

        // Remove hashbang including "#"
        history.replaceState(null, null, window.location.pathname + window.location.search);
    }

    // Fix broken avatars
    $('img.user-avatar, .account-settings-avatar img').each(function() {

        // Fallback image URL
        let fallbackImage = "/img/horny-face-256.png";

        // Function to run on broken images
        function fixBrokenImage(img) {
            if (img.src !== fallbackImage) {
                img.src = fallbackImage;
            }
        }

        // Handle avatars that are not loaded yet
        $(this).on("error", function() {
            fixBrokenImage(this);
        });

        // Handle avatars that have already been loaded
        if (this.complete && this.naturalWidth === 0) {
            fixBrokenImage(this);
        }
    });

});

