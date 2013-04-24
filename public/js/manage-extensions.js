(function($) {
    $.fn.blink = function(blink_times, fade_time, target) {
        if(blink_times === undefined || isNaN(fade_time)) {
            blink_times = 1;
        }
        if(fade_time === undefined || isNaN(fade_time)) {
            fade_time = 100;
        }
        if(target === undefined) {
            target = this;
        }
        return target.each(function() {
            var $this = $(this);
            for(i = 0; i < blink_times; i++) {
                $this.fadeOut(fade_time).fadeIn(fade_time);
            }
        });
    }
})(jQuery);

$(document).ready(function() {
    var siteRoot = $('body').data('siteRoot'),
        $version_list_modal = $('.version-list-modal'),
        $version_list_modal_title = $version_list_modal.find('.modal-header h3'),
        $version_list_modal_table_body = $version_list_modal.find('tbody'),
        $version_list_modal_form = $version_list_modal.find('form'),
        $version_list_modal_version_field = $version_list_modal.find(':text'),
        $version_list_modal_sync = $version_list_modal_version_field.next(),
        $version_list_modal_extension_field = $version_list_modal.find('input[type=hidden]');

    $('#container').delegate('.version-list', 'click', function(e) {
        var $this = $(this);
        if(!$this.hasClass('disabled')) {
            $this.addClass('disabled');
            $.ajax({
                url : $this.attr('href'),
                type : 'POST',
                dataType : 'json',
                success : function(result) {
                    if(expectInObject(['status', 'message'], result)) {
                        if('ok' == result.status) {
                            $version_list_modal_title.text($this.parents('.wrapper:first').find('.info .info-main h5').text() + ' releases');
                            $version_list_modal_table_body.html(result.message);
                            $version_list_modal_extension_field.val($this.data('extension-id'));
                            $version_list_modal_version_field.val('');
                            $version_list_modal.modal('show');
                            $this.removeClass('disabled');
                        } else {
                            window.location.reload();
                        }
                    }
                }
            });
        }
        return false;
    });

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

    $version_list_modal_sync.click(function() {
        if(!$version_list_modal_sync.hasClass('disabled')) {
            $version_list_modal_sync.addClass('disabled');
            $.ajax({
                url : siteRoot + '/extension/sync',
                type : 'POST',
                data : {
                    extension_id : $version_list_modal_extension_field.val()
                },
                dataType : 'json',
                success : function(result) {
                    if(expectInObject(['status', 'message'], result)) {
                        if('error' == result.status) {
                            if(result.message) {
                                alert(result.message);
                            }
                        } else {
                            $version_list_modal_sync
                                .blink(3, 100, $version_list_modal_version_field.val(result.message));
                        }
                    }
                },
                complete: function() {
                    $version_list_modal_sync.removeClass('disabled');
                }
            });
        }

        return false;
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