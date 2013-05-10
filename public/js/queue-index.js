var siteRoot = location.origin;
$(document).ready(function() {
    siteRoot = $('body').data('siteRoot');
});
/* status auto-update start*/
//define globals
var requests = [],
    domain;
  
//refresh statuses
setInterval(updateStatuses,5000);

function updateStatuses() {
    
    $('.progress.install-bar').each(function() {
        var row = $(this);

        //create 
        domain = $(this).data('domain');
        requests[domain];

        //abort last request if it is still in queue
        if(typeof requests[domain] !== "undefined"){
          requests[domain].abort();
        }

        //make new request
        requests[domain] = $.ajax({
              type: "POST",
              url: $('body').data('siteRoot') + "/queue/getstatus",
              data: "domain=" + domain,
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