
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

});
