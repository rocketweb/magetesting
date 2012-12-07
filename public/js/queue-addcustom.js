$(document).ready(function(){
    $("#edition").change(function(){
        
        $.ajax({
            type: "POST",
            url: "/queue/getVersions",
            data: "edition="+ $("#edition").val(),
            dataType: "json",
            success: function(json){
                var opts = "";

                $.each(json,function(index, item){
                    opts =  opts + "<option value=\""+ item.id +"\">" + item.version + "</option>";
                });

                $("#version").html(opts);
            }

        });
        
    });
    
    
    var defaultRadioAlert = 'alert alert-info';
    
    $('div.input-radio .input-radio-option, div.input-radio .input-radio-button').click(function(){
        var $this = $(this);
        var $radioGroup = $this.parent().parent();
        
        // Update alerts
        $radioGroup.find('.input-radio-alert')
            .removeClass(defaultRadioAlert)
            .find('input').css('width', '');
        
        $this.parent().find('.input-radio-alert')
            .addClass(defaultRadioAlert)
            .find('input').css('width', '100%');
        
        // Update radio buttons
        $radioGroup.find('.input-radio-button input').removeAttr('checked');
        $this.parent().find('.input-radio-button input').attr('checked', 'checked');
        
    });
});