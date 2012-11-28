$(document).ready(function(){
	"use strict";
	
	var _instances = $('.instance-toggle');
	
	// On click accordion row
	_instances.click(function(){
		"use strict";
		var _thisClicked = $(this);
		var _id = _thisClicked.attr('href');
		if(_thisClicked.find('.statusHolder').text() == 'Closed') {
		    return false;
		}
		if(_thisClicked.parent().parent().attr('data-active') == 0){
			_instances.each(function(e){
				var _instance = $(this);
				var _parent = _instance.parent().parent();
				
				if(!_thisClicked.is(_instance)){
					_parent.attr('data-active', '0');
					_parent.animate(
						{
							marginTop:		'0px',
							marginBottom:	'-1px',
							opacity:		0.4
						}
					);
					_parent.removeClass('mt_shadow');
					//_instance.parent().removeClass('active-accordion-header');
				} else {
					_parent.addClass('mt_shadow');
					//_instance.parent().addClass('active-accordion-header');
				}
			});
			
			_thisClicked.parent().parent()
				.attr('data-active', '1')
				.animate(
					{
						marginTop:		'20px',
						marginBottom:	'20px',
						opacity:		1
					}
				);
		} else {
			_instances.each(function(){
				var _instance = $(this);
				var _parent = _instance.parent().parent();
				_parent.attr('data-active', '0');
				_parent.animate(
					{
						marginTop:		'0px',
						marginBottom:	'-1px',
						opacity:		1
					}
				);
				_parent.removeClass('mt_shadow');
				//_instance.parent().parent().removeClass('active-accordion-header');
			});
		}
		
	});

});



/* status auto-update start*/
//define globals
    var requests = [];
    var domain;
  
    //refresh statuses
    setInterval(updateStatuses,5000);

function updateStatuses(){
 
  $("div.accordion-toggle.installing").each(function(){
  
      var row = $(this);
              
      //create 
      domain = $(this).find(".instancedomain").val();
      requests[domain];

      //abort last request if it is still in queue
      if(typeof requests[domain] !== "undefined"){
      requests[domain].abort();
      }
      
      //make new request
      requests[domain] = $.ajax({
            type: "POST",
            url: "/queue/getstatus",
            data: "domain=" + $(this).find(".instancedomain").val(),
            dataType: "json",
            success: function(json){
                updateLabel(row,json);
            }
      });
  });
}

function updateLabel(row,new_status){
    var labels = {
        "pending": "Pending", /* shouldn\'t update to this status anyway */
        "installing": "Installing",
        "ready": "Ready",
        "closed" : "Closed",
        "error": "Error",
        "installing-extension": "Installing Extension",
        "installing-magento": "Installing Magento",
        "installing-samples" : "Installing Sample Data",
        "installing-user" : "Installing User",
        "installing-files" : "Installing Files",
        "installing-data" : "Installing Data/Database"
    }
    
        if (new_status=="ready"){
            //remove progress bar and just insert label
            row.find("span.bar").parent().parent().html("<span class=\"span2 progress progress-success progress-striped pull-right\" style=\"height:12px; margin-bottom: 0px\"><span class=\"bar\" style=\"width: 100%;\"></span></span><span class=\"pending pull-right statusHolder\" rel=\"popover\" data-placement=\"bottom\" data-original-title=\"A Title\" data-content=\"\">" + labels[new_status] + "</span>");
            location.reload();
        } else if(new_status != "pending") {
            //update progress bar status
            row.find("span.statusHolder").html("" + labels[new_status] + "");
        } else {
            //update pending time counter
        $.ajax({
            type: "POST",
            url: "/queue/getminutesleft",
            data: "domain=" + row.find(".instancedomain").val(),
            dataType: "json",
            success: function(json){
                var time = '. $this->timeExecution .'*json;
                row.find("span.statusHolder").html("In Queue - " + time + " minutes left");
            }
      });
        }
        
        //remove installing label now when everything is updated
        if (new_status.substr(0,10) != "installing" && new_status != "pending"){
            row.removeClass("installing");
        }
}
/* status auto-update end*/