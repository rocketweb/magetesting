$(document).ready(function() {
    $('#version-'+$("#edition").val()).show();
    
    $("#edition").change(function() {
        $('#version-CE').hide();
        $('#version-EE').hide();
        $('#version-PE').hide();
        $('#version-'+$(this).val()).show();
    });

    $('form').submit(function() {
        $('select:hidden').remove();
    });
});