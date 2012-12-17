$(document).ready(function(){
	"use strict";
	
	var _stores = $('.store-toggle');
	
	// On click accordion row
	_stores.click(function(){
		"use strict";
		var _thisClicked = $(this);
		var _id = _thisClicked.attr('href');
		if($.trim(_thisClicked.find('.statusHolder').text()) == 'Closed') {
		    return false;
		}
		if(_thisClicked.parent().parent().attr('data-active') == 0){
			_stores.each(function(e){
				var _store = $(this);
				var _parent = _store.parent().parent();
				
				if(!_thisClicked.is(_store)){
					_parent.attr('data-active', '0');
					_parent.animate(
						{
							marginTop:		'0px',
							marginBottom:	'-1px',
							opacity:		0.4
						}
					);
					_parent.removeClass('mt_shadow');
					//_store.parent().removeClass('active-accordion-header');
				} else {
					_parent.addClass('mt_shadow');
					//_store.parent().addClass('active-accordion-header');
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
			_stores.each(function(){
				var _store = $(this);
				var _parent = _store.parent().parent();
				_parent.attr('data-active', '0');
				_parent.animate(
					{
						marginTop:		'0px',
						marginBottom:	'-1px',
						opacity:		1
					}
				);
				_parent.removeClass('mt_shadow');
				//_store.parent().parent().removeClass('active-accordion-header');
			});
		}
		
	});

    if(_stores.length === 1){
        $(_stores[0]).trigger('click');
    }
    
    $('.accordion-add-new').click(function(event){
        window.location.pathname = $(this).find('.accordion-heading .accordion-toggle .title a').attr('href');
        event.preventDefault();
        event.stopPropagation(); 
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
      domain = $(this).find(".storedomain").val();
      requests[domain];

      //abort last request if it is still in queue
      if(typeof requests[domain] !== "undefined"){
      requests[domain].abort();
      }
      
      //make new request
      requests[domain] = $.ajax({
            type: "POST",
            url: "/queue/getstatus",
            data: "domain=" + $(this).find(".storedomain").val(),
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
            data: "domain=" + row.find(".storedomain").val(),
            dataType: "json",
            success: function(json){
                row.find("span.statusHolder").html("In Queue - " + json + " minutes left");
            }
      });
        }
        
        //remove installing label now when everything is updated
        if (new_status.substr(0,10) != "installing" && new_status != "pending"){
            row.removeClass("installing");
        }
}
/* status auto-update end*/


/* commit modal handle  start */
$(document).ready(function(){
    $('.commit-button').click(function(){
       $('#commit-domain').val($(this).parentsUntil('.accordion-group').parent().find('.storedomain').val());        
    });

    $('.commit-confirm').click(function(){      

        store = $(this).parentsUntil('commitModal').parent().find(".storedomain").val();

            $.ajax({

                type: "POST",
                url: "/queue/commit",
                data: "domain=" + store + "&commit_comment=" + $('#commit_comment').val(),
                dataType: "json",
                success: function(json){
                    $('#commitModal').hide();
                    $('.modal-backdrop').removeClass('in');
                }

          });
    });
});
/* commit modal handle end */
