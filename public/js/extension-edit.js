$(document).ready(function (){
    $(function(){
        var $jquery_uploader = $('table[role=presentation]');
        if($jquery_uploader.length) {
            $jquery_uploader.on('click', '.delete-row', function(){
                $(this).parents('tr:first').remove();
            });
        }
    });
});