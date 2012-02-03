$(document).ready(function () {
    // configure tooltip messages in place of default browser title popovers
    $("a[rel=tooltip]").tooltip({
        placement: 'bottom'
    });

    // close message after 2 seconds using 400ms slide up effect
    $('.alert[data-dismiss]').delay(2000).fadeOut();

});