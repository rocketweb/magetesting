$(document).ready(function(){
    var $version = $('#version'),
        $version_options = $version.find('option'),
        siteRoot = $('body').data('siteRoot');
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

    var $validate_connection = $('.validate-connection-details'),
        $find_sql_file = $find_sql_file = $('.find-sql-file'),
        $ftp_fields = $('fieldset:eq(1)').find('select, input'),
        $sql_field = $('#custom_sql'),
        $container = $('#main .container'),
        $remote_path_field = $('#custom_remote_path'),
        f_create_flash_message = function(message, type) {
            if(type === undefined || (type !== 'success' && type !== 'error')) {
                type = 'success';
            }
            $container.prepend($(message).attr('id', 'show-flashmessage'));
            window.location.hash = '#show-flashmessage';
        };

    if($validate_connection.length) {
        $validate_connection.click(function() {
            var $this = $(this),
                $ftp_fields_data = {};
                $ftp_fields.each(function(k, element) {
                    var $e = $(element);
                    $ftp_fields_data[$e.attr('name')] = $e.val();
                });

            $.ajax({
                url : siteRoot + '/queue/validate-ftp-credentials',
                data : $ftp_fields_data,
                type : 'POST',
                dataType : 'json',
                success : function(response) {
                    if(response.status !== undefined) {
                        if(response.message) {
                            f_create_flash_message(response.message, response.status);
                        }
                        if(response.value) {
                            $remote_path_field.val(response.val);
                        }
                    }
                }
            });
            return false;
        });
    }

    if($find_sql_file.length) {
        $find_sql_file.click(function() {
            var $this = $(this),
                $ftp_fields_data = {};
                $ftp_fields.add($sql_field).each(function(k, element) {
                    var $e = $(element);
                    $ftp_fields_data[$e.attr('name')] = $e.val();
                });

            $.ajax({
                url : siteRoot + '/queue/find-sql-file',
                data : $ftp_fields_data,
                type : 'POST',
                dataType : 'json',
                success : function(response) {
                    if(response.status !== undefined) {
                        if(response.message) {
                            f_create_flash_message(response.message, response.status);
                        }
                        if(response.value) {
                            $sql_field.val(response.val);
                        }
                    }
                }
            });
            return false;
        });
    }
});