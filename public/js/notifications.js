

// Live notifications
// TODO: Refactor this after implementing TwigJS

// Document ready
$(function(){

    // We only want to get live notifications when we are logged in
    if(app_store.account == null){
        return;
    }

    // Time definitions
    var notificationPollIntervalTimeDefault = 5 * 60 * 1000; // 5 minutes
    var notificationPollIntervalTimeWhenIdle = 20 * 60 * 1000; // 20 minutes

    // Variables
    var notificationPollInterval;
    var notificationPollIntervalTime = notificationPollIntervalTimeDefault; // Start with the default time interval

    // The dropdown button
    var $dropdownButton = $('#navbarNotificationDropdown');

    // The polling function
    function notificationPoll(){

        var $dropdown = $('[aria-labelledby="navbarNotificationDropdown"]');
        var currentNotificationCount = $dropdown.find('.dropdown-item').length - 2;

        // Do nothing if we have the notification dropdown open
        if($dropdownButton.hasClass('show')){
            return;
        }

        // Poll
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
        notificationPollIntervalStop();
        notificationPollIntervalStart();
    }

    // Start polling (after the initial interval)
    notificationPollIntervalStart();

    // Handle switching between browser tabs
    $(document).on('visibilitychange', function() {
        if (document.hidden) {
            // Tab is hidden
            // Switch to slow poll
            notificationPollIntervalTime = notificationPollIntervalTimeWhenIdle;
            notificationPollIntervalRefresh();
        } else {
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
