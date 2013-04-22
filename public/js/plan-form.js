$(document).ready(function() {
    $('.btn-group').delegate('.btn', 'click', function() {
        var $this = $(this);
        $this.parent().find('input[value=' + $this.data('value') + ']').prop('checked', true);
    });
});