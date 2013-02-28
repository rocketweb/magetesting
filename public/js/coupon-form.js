$(document).ready(function() {
    var $datepickers = $('.datepicker');
    $datepickers.datepicker({
        format : 'yyyy-mm-dd'
    }).on('changeDate', function(e) {
        $(e.currentTarget).datepicker('hide');
    }).on('click', function(e) {
        $datepickers.not(e.target).datepicker('hide');
    });
});