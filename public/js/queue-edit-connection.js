$(document).ready(function(){
    
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
        $ftp_fields = $('fieldset:eq(3)').find('select, input'),
        $sql_field = $('#custom_sql'),
        $container = $('#main .container'),
        $remote_path_field = $('#custom_remote_path'),
        f_create_flash_message = function(message, type, $element) {
            if(type === undefined || (type !== 'success' && type !== 'error')) {
                type = 'success';
            }

            $element.popover({
                'title' : 'Status',
                'content' : message,
                'html' : true,
                'trigger' : 'manual',
                'placement' : 'right'
            }).popover('show');

            // overwrite alert close event, by popover destroy
            $element.data('popover').$tip.find('.close').click(function() {
                // destroy popover on click
                $element.popover('destroy');

                // do not use alert data dismiss event
                return false;
            });
        };

    if($validate_connection.length) {
        $validate_connection.click(function() {
            var $this = $(this),
                $ftp_fields_data = {};
                $ftp_fields.each(function(k, element) {
                    var $e = $(element);
                    $ftp_fields_data[$e.attr('name')] = $e.val();
                });

            // disable button
            $this.button('loading');
            $.ajax({
                url : siteRoot + '/queue/validate-ftp-credentials',
                data : $ftp_fields_data,
                type : 'POST',
                dataType : 'json',
                success : function(response) {
                    if(response.status !== undefined) {
                        if(response.message) {
                            console.log($validate_connection);
                            f_create_flash_message(response.message, response.status, $validate_connection);
                        }
                        if(response.value) {
                            $remote_path_field.val(response.value);
                        }
                    }
                },
                complete : function() {
                    // enable button
                    $this.button('reset');
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

            // disable button
            $this.button('loading');
            $.ajax({
                url : siteRoot + '/queue/find-sql-file',
                data : $ftp_fields_data,
                type : 'POST',
                dataType : 'json',
                success : function(response) {
                    if(response.status !== undefined) {
                        if(response.message) {
                            f_create_flash_message(response.message, response.status, $find_sql_file);
                        }
                        if(response.value) {
                            $sql_field.val(response.value);
                        }
                    }
                },
                complete : function() {
                    // enable button
                    $this.button('reset');
                }
            });
            return false;
        });
    }
});