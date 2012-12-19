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
        window.location.pathname = $(this).find('.accordion-inner .btn-add-store').attr('href');
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

function updateStatuses() {
 
  $("div.accordion-toggle.installing").each(function() {
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
            success: function(json) {
                updateLabel(row, json);
            }
      });
  });
}

function updateLabel(row, new_status) {
    if(new_status != row.data('status')) {
        location.reload();
    }

    if (new_status == "ready" || new_status == "error") {
        location.reload();
    } else {
        //update pending time counter
        $.ajax({
            type: "POST",
            url: "/queue/gettimeleft",
            data: "domain=" + row.find(".storedomain").val(),
            dataType: "json",
            success: function(json) {
                row.find("span.statusHolder").html(niceStatus(new_status) + " - " + leftTime(json));
            }
        });
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

function niceStatus(status) {
    return (status.replace(/-/g, ' ') + '').replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function ($1) {
        return $1.toUpperCase();
  });
}

function leftTime(seconds) {
    string = '';
    denominator = 60;
    
    if(seconds > denominator) {
        sec = seconds % denominator;
        min = Math.floor(seconds/denominator);
                    
        string = min + ' minute';
                    
        if(min > 1) {
            string += 's';
        }
                    
        string += ' ' + sec + ' seconds';
                    
    } else {
        string = seconds + ' seconds';
    }
    
    return string += ' left';
}
