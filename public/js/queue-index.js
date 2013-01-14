var siteRoot = $('body').data('siteRoot');
/* status auto-update start*/
//define globals
var requests = [],
    domain;
  
//refresh statuses
setInterval(updateStatuses,5000);

function updateStatuses(){
 
  $('.progress.install-bar').each(function(){
  
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
            url: siteRoot + "/queue/getstatus",
            data: "domain=" + domain,
            dataType: "json",
            success: function(json){
                updateLabel(row, json, domain);
            }
      });
  });
}

function updateLabel(row, new_status, domain){
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
            location.reload();
        } else if(new_status != "pending") {
            //update progress bar status
            row.find('div').text(labels[new_status]);
        } else {
            //update pending time counter
            $.ajax({
                type: "POST",
                url: siteRoot + "/queue/getminutesleft",
                data: "domain=" + domain,
                dataType: "json",
                success: function(json){
                    row.find('div').text("In Queue - " + json + " minutes left");
                }
          });
        }
        
        //remove installing label now when everything is updated
        if (new_status.substr(0,10) != "installing" && new_status != "pending"){
            row.removeClass("install-bar");
        }
}
/* status auto-update end*/