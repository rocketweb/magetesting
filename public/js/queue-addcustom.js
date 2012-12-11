$(document).ready(function(){
    var $version = $('#version'),
        $version_options = $version.find('option');
    $("#edition").change(function(){
        $version.val($version_options.hide().filter('[value^="'+$(this).val()+'"]').show().filter(':first').val());//hide().filter('[value^="'+$this.val()+'"]').show().filter(':first').val();
    });

    $('div.input-radio .input-radio-option, div.input-radio .input-radio-button').bind('click focusin focusout', function(){
        var $this = $(this);
        var $radioGroup = $this.parent().parent();
        
        // Update alerts
        $radioGroup.find('.input-radio-alert')
            .removeClass('alert-info')
            .addClass('alert-blank');
        
        $this.parent().find('.input-radio-alert')
            .removeClass('alert-blank')
            .addClass('alert-info');
        
        // Update radio buttons
        $radioGroup.find('.input-radio-button input').removeAttr('checked');
        $this.parent().find('.input-radio-button input').attr('checked', 'checked');
        
    });
    
});