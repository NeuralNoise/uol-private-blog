/**
 * script for privacy plugin
 */
jQuery(document).ready(function($){
	var has_ips = (typeof uol_allowed_ips !== 'undefined' && uol_allowed_ips !== '');
	if (has_ips) {
		if (!$('#uol_privacy_options-logged_in_users_only:checked').length) {
			$('#uol_privacy_options-allow_network_users').prop('checked', false);
			$('#uol_privacy_options-allow_network_users').parents('tr').hide();
		}
		$('#uol_privacy_options-logged_in_users_only').click(function(){
			if ($(this).is(':checked')) {
				$('#uol_privacy_options-allow_network_users').parents('tr').show();
			} else {
				$('#uol_privacy_options-allow_network_users').prop('checked', false);
				$('#uol_privacy_options-allow_network_users').parents('tr').hide();
			}
		});
	} else {
		$('#uol_privacy_options-allow_network_users').prop('checked', false);
		$('#uol_privacy_options-allow_network_users').parents('tr').hide();
	}
	function ip_entry_box_toggle()
	{
		if ($('#force_login:checked').length) {
			$('#ip_list_entry').hide();
		} else {
			$('#ip_list_entry').show();
		}
	}
	$('#force_login').on('click', ip_entry_box_toggle);
	ip_entry_box_toggle();
});