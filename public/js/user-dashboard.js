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
					_parent.find('.right-content').addClass('inactive');
					_parent.find('.tab-stripe').removeClass('active');
					_parent.find('.left-content .arrow .icon').removeClass('icon-chevron-up').addClass('icon-chevron-down')
					//_store.parent().removeClass('active-accordion-header');
				} else {
					_parent.addClass('mt_shadow');
					_parent.find('.right-content').removeClass('inactive');
					_parent.find('.tab-stripe').addClass('active');
					_parent.find('.left-content .arrow .icon').removeClass('icon-chevron-down').addClass('icon-chevron-up')
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
				_parent.find('.right-content').addClass('inactive');
				_parent.find('.tab-stripe').removeClass('active');
				_parent.find('.left-content .arrow .icon').removeClass('icon-chevron-up').addClass('icon-chevron-down')
				//_store.parent().parent().removeClass('active-accordion-header');
			});
		}
		
	});

    if(_stores.length === 1){
        $(_stores[0]).trigger('click');
    }
    
    $('.accordion-add-new').click(function(event){

        if ($(this).find('.accordion-inner .btn-add-store').attr('href')){
            window.location.pathname = $(this).find('.accordion-inner .btn-add-store').attr('href');
            event.preventDefault();
            event.stopPropagation(); 
        } else if ($(this).find('.accordion-inner .btn-select-plan-to-add').attr('href')) {
            window.location.pathname = $(this).find('.accordion-inner .btn-select-plan-to-add').attr('href');
            event.preventDefault();
            event.stopPropagation(); 
        }

    });

    $(".btn.disabled.request-buy").tooltip({});

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
            url: $('body').data('siteRoot') + "/queue/getstatus",
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
                url: "/queue/commit/page/"+$('#page').val(),
                data: "domain=" + store + "&commit_comment=" + $('#commit_comment').val(),
                dataType: "json",
                success: function(json){
                    $('#commitModal').hide();
                    $('.modal-backdrop').removeClass('in');
                }

          });
    });

    $('.conflict-button').click(function(event){
        event.preventDefault();
        var storeId = $(this).parentsUntil('.accordion-group').parent().find('.storeid').val();
        $('#conflictModal'+storeId).modal({
            show: true
        });
    });

    setConflictButtons();
});

function setConflictButtons(){
    $('.ignore-conflict-button, .unignore-conflict-button').click(function(event){
        event.preventDefault(); // So it doesn't jump up
        var id = $(this).attr('href').replace('#','');
        var ignore = $(this).hasClass('ignore-conflict-button') ? 1 : 0;
        var parentDiv = $(this).closest('.modal-body');

        var linkClass = '.conflict-store-'+parentDiv.attr('id').replace('store_id_','');
        var linkText = $(linkClass).text().split('(')[0];

        $.ajax({
            type: "POST",
            url: "/queue/conflict",
            data: "conflict_id=" + id + "&ignore="+ignore,
            dataType: "json",
            success: function(json){
                parentDiv.html(json.modalData);
                $(linkClass).text(linkText+' ('+json.count+')');
                setConflictButtons();
            }

        });
    });
    $('.rerun-button').click(function(event){
        event.preventDefault();
        var parentDiv = $(this).closest('.modal-body');
        var store_id = parentDiv.attr('id').replace('store_id_','');

        $.ajax({
            type: "POST",
            url: "/queue/runconflict/page/"+$('#page').val(),
            data: "store_id=" + store_id,
            dataType: "json",
            success: function(json){
                location.reload();
            }

        });

    });
    $('.rerun-button').tooltip();

    $('.show-ignored-button').click(function(){
        $(this).siblings('.hide').removeClass('hide');
        $(this).addClass('hide');
    });
}

/* commit modal handle end */

function niceStatus(status) {
    return (status.replace(/-/g, ' ') + '').replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function ($1) {
        return $1.toUpperCase();
  });
}

function leftTime(seconds) {
    
    if (seconds == null || seconds == 0 ){
        location.reload();
    }
    
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
