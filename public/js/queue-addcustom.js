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

    //first fire this up on load
    var customProtocolElement = $("#custom_protocol");
    updateLabels();

    //then fire it up on every change
    customProtocolElement.change(function() {
        updateLabels();
    });

    function updateLabels(){
        if (customProtocolElement.val() == 'ssh') {
            $("#custom_remote_path").parent().find('label').html('Remote absolute path to Magento Root');
            $("#custom_file").parent().find('label').html('Remote absolute path to .zip or .tar.gz package containing all store files');
            $("#custom_sql").parent().find('label').html('Remote absolute path to SQL dump');
        } else {
            $("#custom_remote_path").parent().find('label').html('Remote relative path to Magento Root');
            $("#custom_file").parent().find('label').html('Remote relative path to .zip or .tar.gz package containing all store files');
            $("#custom_sql").parent().find('label').html('Remote relative path to SQL dump');
        }
    }


});