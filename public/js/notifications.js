

// Live notifications
// TODO: Refactor this after implementing TwigJS

// Document ready
$(function(){

    var notificationPollIntervalTimeDefault = 2 * 60 * 1000; // 2 minutes
    var notificationPollIntervalTimeWhenIdle = 10 * 60 * 1000; // 10 minutes

    var notificationPollInterval;
    var notificationPollIntervalTime = notificationPollIntervalTimeDefault; // 1 second

    // We only want to get live notifications when we are logged in
    if(app_store.account == null){
        return;
    }

    // The polling function
    function notificationPoll(){

        var $dropdown = $('[aria-labelledby="navbarNotificationDropdown"]');
        var $dropdownButton = $('#navbarNotificationDropdown');
        var currentNotificationCount = $dropdown.find('.dropdown-item').length - 2;

        if($dropdownButton.hasClass('show')){
            return;
        }

        $.ajax({
            type: 'GET',
            url: '/',
            dataType: 'html',
            success: function(data){

                // Get the new dropdown HTML
                var $html = $(data);
                var $newDropdown = $html.find('[aria-labelledby="navbarNotificationDropdown"]');
                var newNotificationCount = $newDropdown.find('.dropdown-item').length - 2;

                // Make sure our current dropdown is not open
                // And that there are either less or more notifications now
                if($dropdownButton.hasClass('show') == false && currentNotificationCount != newNotificationCount){

                        // Update the dropdown HTML
                        $dropdown.html($newDropdown.html());
                        $("time").timeago();

                        // Handle the badge
                        if(newNotificationCount <= 0){
                            $('#notificationBadge').hide();
                        } else {
                            $('#notificationBadge').text(newNotificationCount);
                            $('#notificationBadge').show();
                        }
                }
            }
        });

    }

    // The function that starts the polling
    function notificationPollIntervalStart()
    {
        notificationPollInterval = setInterval(notificationPoll, notificationPollIntervalTime);
    }

    // Function that ends the polling
    function notificationPollIntervalStop()
    {
        clearInterval(notificationPollInterval);
    }

    function notificationPollIntervalRefresh()
    {
        console.log('refresh');
        notificationPollIntervalStop();
        notificationPollIntervalStart();
    }

    // Start polling (after the initial interval)
    notificationPollIntervalStart();

    // Handle switching between browser tabs
    $(document).on('visibilitychange', function() {
        if (document.hidden) {
            console.log('hidden!')
            // Tab is hidden
            // Switch to slow poll
            notificationPollIntervalTime = notificationPollIntervalTimeWhenIdle;
            notificationPollIntervalRefresh();
        } else {
            console.log('shown!')
            // Tab is opened
            // Poll instantly
            notificationPoll();
            // Switch to 'fast' poll
            notificationPollIntervalTime = notificationPollIntervalTimeDefault;
            notificationPollIntervalRefresh();
        }
    });

    // Poll on window focus
    $(window).on('focus', function(){
        notificationPoll();
    });
});
