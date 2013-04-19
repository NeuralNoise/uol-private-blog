/**
 * script for privacy plugin
 */
jQuery(document).ready(function($){
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
});