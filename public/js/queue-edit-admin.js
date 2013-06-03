$(document).ready(function(){
    $('table .btn-group').delegate('.btn', 'click', function() {
        var $btn = $(this),
            $store_extension_id = $btn.parent().data('store-extension-id');
        if(!$btn.hasClass('active')) {
            $.ajax({
                url : '/queue/set-payment-for-extension',
                type : 'post',
                data : {payment : $btn.data('value'), store_extension_id : $store_extension_id},
                success : function(result) {
                }
            });
        }
    });
});