$(document).ready(function() {
    var $version_list_buttons = $('.version-list'),
        $version_list_modal = $('.version-list-modal'),
        $version_list_modal_title = $version_list_modal.find('.modal-header h3'),
        $version_list_modal_table_body = $version_list_modal.find('tbody'),
        $version_list_modal_form = $version_list_modal.find('form'),
        $version_list_modal_version_field = $version_list_modal.find(':text'),
        $version_list_modal_extension_field = $version_list_modal.find('input[type=hidden]');

    if($version_list_buttons.length) {
        $version_list_buttons.click(function() {
            var $this = $(this);
            if(!$this.hasClass('disable')) {
                $this.addClass('disable');
                $.ajax({
                    url : $this.attr('href'),
                    type : 'POST',
                    dataType : 'json',
                    success : function(result) {
                        if(expectInObject(['status', 'message'], result)) {
                            if('ok' == result.status) {
                                $version_list_modal_title.text($this.parents('.wrapper:first').find('.info .info-main h5').text());
                                $version_list_modal_table_body.html(result.message);
                                $version_list_modal_extension_field.val($this.data('extension-id'));
                                $version_list_modal.modal('show');
                                $this.removeClass('disable');
                            }
                        }
                    }
                });
            }
            return false;
        });
    }

    $version_list_modal_form.submit(function() {
        var found_version = false,
            version_field = $.trim($version_list_modal_version_field.val());
        $version_list_modal_table_body.find('tr td:first-child').each(function(k, e) {
            if($.trim($(e).text()) == version_field) {
                found_version = true;
            }
        });

        if(found_version) {
            alert('Given version already exists.');
            return false;
        }
        if(!version_field.length) {
            alert('You have to specify version.');
            return false;
        }
        if(!$.trim($version_list_modal_extension_field.val()).length) {
            return false;
        }
    });
});

function expectInObject(attributes, object) {
    if('object' == typeof object) {
        var size = attributes.length;
        for(var i = 0; i < size; i++) {
            if('undefined' == typeof object[attributes[i]]) {
                // one of the given attributes does not exist in object
                return false;
            }
        }
        // everything is ok
        return true;
    }
    // object is not an object
    return false;
}