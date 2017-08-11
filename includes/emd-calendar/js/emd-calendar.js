(function($) {
	$('#emd-calendar-'+calendar_vars.cname).fullCalendar({
		header: {
				left: calendar_vars.header_left,
				center: calendar_vars.header_center,
				right: calendar_vars.header_right,
			},
		defaultView: calendar_vars.default_view,
		theme: calendar_vars.jui_theme,
		eventSources: [{
			url: calendar_vars.ajax_url,
			data: {
				action: 'emd_calendar_ajax',
				ptype: $('#ptype').val(),
				cname: $('#cname').val(),
				app_name: $('#app_name').val(),
			},
			success : function(data){
                        }
		}],
		eventRender: function(event, element) {
			element.attr('title', event.tooltip);
		},
		loading: function(bool) {
			if (bool) {
				$('.emd-calendar-loading').show();
			}else {
				$('.emd-calendar-loading').hide();
			}
		},
	});
 })(jQuery);

