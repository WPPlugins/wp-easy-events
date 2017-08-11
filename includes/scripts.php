<?php
/**
 * Enqueue Scripts Functions
 *
 * @package WP_EASY_EVENTS
 * @since WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
add_action('admin_enqueue_scripts', 'wp_easy_events_load_admin_enq');
/**
 * Enqueue style and js for each admin entity pages and settings
 *
 * @since WPAS 4.0
 * @param string $hook
 *
 */
function wp_easy_events_load_admin_enq($hook) {
	global $typenow;
	$dir_url = WP_EASY_EVENTS_PLUGIN_URL;
	do_action('emd_ext_admin_enq', 'wp_easy_events', $hook);
	$min_trigger = get_option('wp_easy_events_show_rateme_plugin_min', 0);
	$tracking_optin = get_option('wp_easy_events_tracking_optin', 0);
	if (-1 !== intval($tracking_optin) || - 1 !== intval($min_trigger)) {
		wp_enqueue_style('emd-plugin-rateme-css', $dir_url . 'assets/css/emd-plugin-rateme.css');
		wp_enqueue_script('emd-plugin-rateme-js', $dir_url . 'assets/js/emd-plugin-rateme.js');
	}
	if ($hook == 'edit-tags.php') {
		return;
	}
	if ($hook == 'event-settings_page_wp_easy_events_calendar') {
		emd_calendar_admin_enq('wp_easy_events');
		return;
	}
	if (isset($_GET['page']) && in_array($_GET['page'], Array(
		'wp_easy_events_notify',
		'wp_easy_events_settings'
	))) {
		wp_enqueue_script('accordion');
		wp_enqueue_style('codemirror-css', $dir_url . 'assets/ext/codemirror/codemirror.css');
		wp_enqueue_script('codemirror-js', $dir_url . 'assets/ext/codemirror/codemirror.js', array() , '', true);
		wp_enqueue_script('codemirror-css-js', $dir_url . 'assets/ext/codemirror/css.js', array() , '', true);
		return;
	} else if (isset($_GET['page']) && $_GET['page'] == 'wp_easy_events') {
		wp_enqueue_style('lazyYT-css', $dir_url . 'assets/ext/lazyyt/lazyYT.min.css');
		wp_enqueue_script('lazyYT-js', $dir_url . 'assets/ext/lazyyt/lazyYT.min.js');
		wp_enqueue_script('getting-started-js', $dir_url . 'assets/js/getting-started.js');
		return;
	} else if (isset($_GET['page']) && in_array($_GET['page'], Array(
		'wp_easy_events_store',
		'wp_easy_events_designs',
		'wp_easy_events_support'
	))) {
		wp_enqueue_style('admin-tabs', $dir_url . 'assets/css/admin-store.css');
		return;
	}
	if (in_array($typenow, Array(
		'emd_wpe_event',
		'emd_event_organizer',
		'emd_event_venues',
		'emd_event_attendee'
	))) {
		$theme_changer_enq = 1;
		$sing_enq = 0;
		$tab_enq = 0;
		if ($hook == 'post.php' || $hook == 'post-new.php') {
			$unique_vars['msg'] = __('Please enter a unique value.', 'wp-easy-events');
			$unique_vars['reqtxt'] = __('required', 'wp-easy-events');
			$unique_vars['app_name'] = 'wp_easy_events';
			$ent_list = get_option('wp_easy_events_ent_list');
			if (!empty($ent_list[$typenow])) {
				$unique_vars['keys'] = $ent_list[$typenow]['unique_keys'];
				if (!empty($ent_list[$typenow]['req_blt'])) {
					$unique_vars['req_blt_tax'] = $ent_list[$typenow]['req_blt'];
				}
			}
			$tax_list = get_option('wp_easy_events_tax_list');
			if (!empty($tax_list[$typenow])) {
				foreach ($tax_list[$typenow] as $txn_name => $txn_val) {
					if ($txn_val['required'] == 1) {
						$unique_vars['req_blt_tax'][$txn_name] = Array(
							'hier' => $txn_val['hier'],
							'type' => $txn_val['type'],
							'label' => $txn_val['label'] . ' ' . __('Taxonomy', 'wp-easy-events')
						);
					}
				}
			}
			wp_enqueue_script('unique_validate-js', $dir_url . 'assets/js/unique_validate.js', array(
				'jquery',
				'jquery-validate'
			) , WP_EASY_EVENTS_VERSION, true);
			wp_localize_script("unique_validate-js", 'unique_vars', $unique_vars);
		} elseif ($hook == 'edit.php') {
			wp_enqueue_style('wp-easy-events-allview-css', WP_EASY_EVENTS_PLUGIN_URL . '/assets/css/allview.css');
		}
		switch ($typenow) {
			case 'emd_wpe_event':
				$tab_enq = 1;
				$sing_enq = 1;
			break;
			case 'emd_event_organizer':
				$sing_enq = 1;
			break;
			case 'emd_event_venues':
				$sing_enq = 1;
			break;
			case 'emd_event_attendee':
				$sing_enq = 1;
			break;
		}
		if ($sing_enq == 1) {
			wp_enqueue_script('radiotax', WP_EASY_EVENTS_PLUGIN_URL . 'includes/admin/singletax/singletax.js', array(
				'jquery'
			) , WP_EASY_EVENTS_VERSION, true);
		}
		if ($tab_enq == 1) {
			wp_enqueue_style('jq-css', WP_EASY_EVENTS_PLUGIN_URL . 'assets/css/smoothness-jquery-ui.css');
		}
	}
}
add_action('wp_enqueue_scripts', 'wp_easy_events_frontend_scripts');
/**
 * Enqueue style and js for each frontend entity pages and components
 *
 * @since WPAS 4.0
 *
 */
function wp_easy_events_frontend_scripts() {
	$dir_url = WP_EASY_EVENTS_PLUGIN_URL;
	wp_register_style('wp-easy-events-allview-css', $dir_url . '/assets/css/allview.css');
	$grid_vars = Array();
	$local_vars['ajax_url'] = admin_url('admin-ajax.php');
	$local_vars['validate_msg']['required'] = __('This field is required.', 'emd-plugins');
	$local_vars['validate_msg']['remote'] = __('Please fix this field.', 'emd-plugins');
	$local_vars['validate_msg']['email'] = __('Please enter a valid email address.', 'emd-plugins');
	$local_vars['validate_msg']['url'] = __('Please enter a valid URL.', 'emd-plugins');
	$local_vars['validate_msg']['date'] = __('Please enter a valid date.', 'emd-plugins');
	$local_vars['validate_msg']['dateISO'] = __('Please enter a valid date ( ISO )', 'emd-plugins');
	$local_vars['validate_msg']['number'] = __('Please enter a valid number.', 'emd-plugins');
	$local_vars['validate_msg']['digits'] = __('Please enter only digits.', 'emd-plugins');
	$local_vars['validate_msg']['creditcard'] = __('Please enter a valid credit card number.', 'emd-plugins');
	$local_vars['validate_msg']['equalTo'] = __('Please enter the same value again.', 'emd-plugins');
	$local_vars['validate_msg']['maxlength'] = __('Please enter no more than {0} characters.', 'emd-plugins');
	$local_vars['validate_msg']['minlength'] = __('Please enter at least {0} characters.', 'emd-plugins');
	$local_vars['validate_msg']['rangelength'] = __('Please enter a value between {0} and {1} characters long.', 'emd-plugins');
	$local_vars['validate_msg']['range'] = __('Please enter a value between {0} and {1}.', 'emd-plugins');
	$local_vars['validate_msg']['max'] = __('Please enter a value less than or equal to {0}.', 'emd-plugins');
	$local_vars['validate_msg']['min'] = __('Please enter a value greater than or equal to {0}.', 'emd-plugins');
	$local_vars['unique_msg'] = __('Please enter a unique value.', 'emd-plugins');
	$wpas_shc_list = get_option('wp_easy_events_shc_list');
	$local_vars['event_attendee'] = emd_get_form_req_hide_vars('wp_easy_events', 'event_attendee');
	wp_register_style('event-attendee-forms', $dir_url . 'assets/css/event-attendee-forms.css');
	wp_register_script('event-attendee-forms-js', $dir_url . 'assets/js/event-attendee-forms.js');
	wp_localize_script('event-attendee-forms-js', 'event_attendee_vars', $local_vars);
	wp_register_style('wpasui', WP_EASY_EVENTS_PLUGIN_URL . 'assets/ext/wpas-jui/wpas-jui.min.css');
	wp_register_script('view-single-event', $dir_url . 'assets/js/view-single-event.js');
	wp_register_script('modaljs-js', $dir_url . 'assets/js/modaljs.js');
	wp_register_style('view-wpee-event-grid', $dir_url . 'assets/css/view-wpee-event-grid.css');
	wp_register_script('modalcdn', $dir_url . 'assets/js/modalcdn.js');
	wp_register_style('wpee-listgroup', $dir_url . 'assets/css/wpee-listgroup.css');
	wp_register_script('wpas-jvalidate-js', $dir_url . 'assets/ext/jvalidate1150/wpas.validate.min.js', array(
		'jquery'
	));
	wp_register_script('single-event-js', $dir_url . 'assets/js/single-event.js');
	if (is_single() && get_post_type() == 'emd_wpe_event') {
		wp_enqueue_script('jquery');
		wp_enqueue_script('view-single-event');
		wp_enqueue_script('single-event-js');
		wp_enqueue_style('wp-easy-events-allview-css');
		wp_easy_events_enq_custom_css();
		return;
	}
	if (is_single() && get_post_type() == 'emd_event_organizer') {
		wp_enqueue_script('jquery');
		wp_enqueue_script('modalcdn');
		wp_enqueue_script('modaljs-js');
		wp_enqueue_style('wp-easy-events-allview-css');
		wp_easy_events_enq_custom_css();
		return;
	}
	if (is_single() && get_post_type() == 'emd_event_venues') {
		wp_enqueue_script('jquery');
		wp_enqueue_script('modalcdn');
		wp_enqueue_script('modaljs-js');
		wp_enqueue_style('wp-easy-events-allview-css');
		wp_easy_events_enq_custom_css();
		return;
	}
	if (is_single() && get_post_type() == 'emd_event_attendee') {
		wp_enqueue_style('wp-easy-events-allview-css');
		wp_easy_events_enq_custom_css();
		return;
	}
}
/**
 * Enqueue custom css if set in settings tool tab
 *
 * @since WPAS 5.3
 *
 */
function wp_easy_events_enq_custom_css() {
	$tools = get_option('wp_easy_events_tools');
	if (!empty($tools['custom_css'])) {
		$url = home_url();
		if (is_ssl()) {
			$url = home_url('/', 'https');
		}
		wp_enqueue_style('wp-easy-events-custom', add_query_arg(array(
			'wp-easy-events-css' => 1
		) , $url));
	}
}
/**
 * If app custom css query var is set, print custom css
 */
function wp_easy_events_print_css() {
	// Only print CSS if this is a stylesheet request
	if (!isset($_GET['wp-easy-events-css']) || intval($_GET['wp-easy-events-css']) !== 1) {
		return;
	}
	ob_start();
	header('Content-type: text/css');
	$tools = get_option('wp_easy_events_tools');
	$raw_content = isset($tools['custom_css']) ? $tools['custom_css'] : '';
	$content = wp_kses($raw_content, array(
		'\'',
		'\"'
	));
	$content = str_replace('&gt;', '>', $content);
	echo $content; //xss okay
	die();
}
add_action('plugins_loaded', 'wp_easy_events_print_css');
/**
 * Enqueue if allview css is not enqueued
 *
 * @since WPAS 4.5
 *
 */
function wp_easy_events_enq_allview() {
	if (!wp_style_is('wp-easy-events-allview-css', 'enqueued')) {
		wp_enqueue_style('wp-easy-events-allview-css');
	}
}
