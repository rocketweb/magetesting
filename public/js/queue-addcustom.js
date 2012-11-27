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
});