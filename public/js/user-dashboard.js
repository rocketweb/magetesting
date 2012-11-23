$(document).ready(function(){
	"use strict";
	
	var _instances = $('.instance-toggle');
	
	// On click accordion row
	_instances.click(function(){
		"use strict";
	 	var _thisClicked = $(this);
		var _id = _thisClicked.attr('href');
		
		if(_thisClicked.parent().parent().attr('data-active') == 0){
			_instances.each(function(){
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
					_instance.parent().removeClass('active-accordion-header');
				} else {
					_parent.addClass('mt_shadow');
					_instance.parent().addClass('active-accordion-header');
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
				_instance.parent().addClass('active-accordion-header');
			});
		}
		
	});

});