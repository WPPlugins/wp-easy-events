<?php
/**
 * Setup and Process submit and search forms
 * @package WP_EASY_EVENTS
 * @since WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
if (is_admin()) {
	add_action('wp_ajax_wp_easy_events_submit_ajax_form', 'wp_easy_events_submit_ajax_form');
	add_action('wp_ajax_nopriv_wp_easy_events_submit_ajax_form', 'wp_easy_events_submit_ajax_form');
	add_action('wp_ajax_nopriv_emd_check_unique', 'emd_check_unique');
}
/**
 * Process ajax submit form
 * @since WPAS 4.0
 */
function wp_easy_events_submit_ajax_form() {
	$form_name = isset($_POST['form_name']) ? sanitize_text_field($_POST['form_name']) : '';
	$vals = isset($_POST['vals']) ? $_POST['vals'] : '';
	if (!empty($vals)) {
		parse_str(stripslashes($vals) , $post_array);
		foreach ($post_array as $pkey => $mypost) {
			$_POST[$pkey] = $mypost;
		}
	}
	$form_init_variables = get_option('wp_easy_events_glob_forms_init_list');
	$form_variables = get_option('wp_easy_events_glob_forms_list');
	switch ($form_name) {
		case 'event_attendee':
			emd_submit_ajax_form('event_attendee', 'wp_easy_events', 'emd_event_attendee', 'publish', 'publish', __('Thanks for your registration.', 'wp-easy-events') , __('There has been an error when processing your registration. Please contact the site administrator.', 'wp-easy-events'));
		break;
	}
}
add_action('init', 'wp_easy_events_form_shortcodes', -2);
/**
 * Start session and setup upload idr and current user id
 * @since WPAS 4.0
 *
 */
function wp_easy_events_form_shortcodes() {
	global $file_upload_dir;
	$upload_dir = wp_upload_dir();
	$file_upload_dir = $upload_dir['basedir'];
	if (!empty($_POST['emd_action'])) {
		if ($_POST['emd_action'] == 'wp_easy_events_user_login' && wp_verify_nonce($_POST['emd_login_nonce'], 'emd-login-nonce')) {
			emd_process_login($_POST, 'wp_easy_events');
		} elseif ($_POST['emd_action'] == 'wp_easy_events_user_register' && wp_verify_nonce($_POST['emd_register_nonce'], 'emd-register-nonce')) {
			emd_process_register($_POST, 'wp_easy_events');
		}
	}
}
add_shortcode('event_attendee', 'wp_easy_events_process_event_attendee');
/**
 * Set each form field(attr,tax and rels) and render form
 *
 * @since WPAS 4.0
 *
 * @return object $form
 */
function wp_easy_events_set_event_attendee($atts) {
	global $file_upload_dir;
	$show_captcha = 0;
	$form_variables = get_option('wp_easy_events_glob_forms_list');
	$form_init_variables = get_option('wp_easy_events_glob_forms_init_list');
	$current_user = wp_get_current_user();
	$attr_list = get_option('wp_easy_events_attr_list');
	if (!empty($atts['set'])) {
		$set_arrs = emd_parse_set_filter($atts['set']);
	}
	if (!empty($form_variables['event_attendee']['captcha'])) {
		switch ($form_variables['event_attendee']['captcha']) {
			case 'never-show':
				$show_captcha = 0;
			break;
			case 'show-always':
				$show_captcha = 1;
			break;
			case 'show-to-visitors':
				if (is_user_logged_in()) {
					$show_captcha = 0;
				} else {
					$show_captcha = 1;
				}
			break;
		}
	}
	$req_hide_vars = emd_get_form_req_hide_vars('wp_easy_events', 'event_attendee');
	$form = new Zebra_Form('event_attendee', 1, 'POST', '', array(
		'class' => 'form-container wpas-form wpas-form-stacked',
		'session_obj' => WP_EASY_EVENTS()->session
	));
	$csrf_storage_method = (isset($form_variables['event_attendee']['csrf']) ? $form_variables['event_attendee']['csrf'] : $form_init_variables['event_attendee']['csrf']);
	if ($csrf_storage_method == 0) {
		$form->form_properties['csrf_storage_method'] = false;
	}
	if (!in_array('rel_event_attendee', $req_hide_vars['hide'])) {
		$form->add('label', 'label_rel_event_attendee', 'rel_event_attendee', __('Events', 'wp-easy-events') , array(
			'class' => 'control-label'
		));
		$attrs = array(
			'class' => 'input-md'
		);
		if (!empty($_GET['rel_event_attendee'])) {
			$attrs['value'] = sanitize_text_field($_GET['rel_event_attendee']);
		} elseif (!empty($set_arrs['rel']['event_attendee'])) {
			$attrs['value'] = $set_arrs['rel']['event_attendee'];
		}
		$obj = $form->add('selectadv', 'rel_event_attendee', __('Please select', 'wp-easy-events') , $attrs, '', '{"allowClear":true,"placeholder":"' . __("Please Select", "wp-easy-events") . '"}');
		//get entity values
		$rel_ent_arr = Array();
		$rel_ent_arr[''] = __('Please Select', 'wp-easy-events');
		$rel_ent_args = Array(
			'post_type' => 'emd_wpe_event',
			'numberposts' => - 1,
			'orderby' => 'title',
			'order' => 'ASC'
		);
		$rel_ent_pids = get_posts($rel_ent_args);
		if (!empty($rel_ent_pids)) {
			foreach ($rel_ent_pids as $my_ent_pid) {
				$rel_ent_arr[$my_ent_pid->ID] = get_the_title($my_ent_pid->ID);
			}
		}
		$obj->add_options($rel_ent_arr);
		$zrule = Array(
			'dependencies' => array() ,
		);
		if (in_array('rel_event_attendee', $req_hide_vars['req'])) {
			$zrule = array_merge($zrule, Array(
				'required' => array(
					'error',
					__('Events is required!', 'wp-easy-events')
				)
			));
		}
		$obj->set_rule($zrule);
	}
	if (!in_array('emd_attendee_first_name', $req_hide_vars['hide'])) {
		//text
		$form->add('label', 'label_emd_attendee_first_name', 'emd_attendee_first_name', __('First Name', 'wp-easy-events') , array(
			'class' => 'control-label'
		));
		$attrs = array(
			'class' => 'input-md form-control',
			'placeholder' => __('First Name', 'wp-easy-events')
		);
		if (!empty($_GET['emd_attendee_first_name'])) {
			$attrs['value'] = sanitize_text_field($_GET['emd_attendee_first_name']);
		} elseif (!empty($set_arrs['attr']['emd_attendee_first_name'])) {
			$attrs['value'] = $set_arrs['attr']['emd_attendee_first_name'];
		} elseif (!empty($current_user) && !empty($attr_list['emd_event_attendee']['emd_attendee_first_name']['user_map'])) {
			$attrs['value'] = (string)$current_user->$attr_list['emd_event_attendee']['emd_attendee_first_name']['user_map'];
		}
		$obj = $form->add('text', 'emd_attendee_first_name', '', $attrs);
		$zrule = Array(
			'dependencies' => array() ,
		);
		if (in_array('emd_attendee_first_name', $req_hide_vars['req'])) {
			$zrule = array_merge($zrule, Array(
				'required' => array(
					'error',
					__('First Name is required', 'wp-easy-events')
				)
			));
		}
		$obj->set_rule($zrule);
	}
	if (!in_array('emd_attendee_last_name', $req_hide_vars['hide'])) {
		//text
		$form->add('label', 'label_emd_attendee_last_name', 'emd_attendee_last_name', __('Last Name', 'wp-easy-events') , array(
			'class' => 'control-label'
		));
		$attrs = array(
			'class' => 'input-md form-control',
			'placeholder' => __('Last Name', 'wp-easy-events')
		);
		if (!empty($_GET['emd_attendee_last_name'])) {
			$attrs['value'] = sanitize_text_field($_GET['emd_attendee_last_name']);
		} elseif (!empty($set_arrs['attr']['emd_attendee_last_name'])) {
			$attrs['value'] = $set_arrs['attr']['emd_attendee_last_name'];
		} elseif (!empty($current_user) && !empty($attr_list['emd_event_attendee']['emd_attendee_last_name']['user_map'])) {
			$attrs['value'] = (string)$current_user->$attr_list['emd_event_attendee']['emd_attendee_last_name']['user_map'];
		}
		$obj = $form->add('text', 'emd_attendee_last_name', '', $attrs);
		$zrule = Array(
			'dependencies' => array() ,
		);
		if (in_array('emd_attendee_last_name', $req_hide_vars['req'])) {
			$zrule = array_merge($zrule, Array(
				'required' => array(
					'error',
					__('Last Name is required', 'wp-easy-events')
				)
			));
		}
		$obj->set_rule($zrule);
	}
	if (!in_array('emd_attendee_quantity', $req_hide_vars['hide'])) {
		//text
		$form->add('label', 'label_emd_attendee_quantity', 'emd_attendee_quantity', __('Quantity', 'wp-easy-events') , array(
			'class' => 'control-label'
		));
		$attrs = array(
			'class' => 'input-md form-control',
			'placeholder' => __('Quantity', 'wp-easy-events')
		);
		if (!empty($_GET['emd_attendee_quantity'])) {
			$attrs['value'] = sanitize_text_field($_GET['emd_attendee_quantity']);
		} elseif (!empty($set_arrs['attr']['emd_attendee_quantity'])) {
			$attrs['value'] = $set_arrs['attr']['emd_attendee_quantity'];
		}
		$obj = $form->add('text', 'emd_attendee_quantity', '1', $attrs);
		$zrule = Array(
			'dependencies' => array() ,
			'regex' => array(
				'^-?\d+$',
				'error',
				__('Quantity: A positive or negative non-decimal number please')
			) ,
			'custom' => array(
				'emd_check_min_max_value',
				1,
				0,
				1,
				'error',
				__('Quantity: Please enter a value between 1 and no maximum value.')
			)
		);
		if (in_array('emd_attendee_quantity', $req_hide_vars['req'])) {
			$zrule = array_merge($zrule, Array(
				'required' => array(
					'error',
					__('Quantity is required', 'wp-easy-events')
				)
			));
		}
		$obj->set_rule($zrule);
	}
	if (!in_array('emd_attendee_email', $req_hide_vars['hide'])) {
		//text
		$form->add('label', 'label_emd_attendee_email', 'emd_attendee_email', __('Email', 'wp-easy-events') , array(
			'class' => 'control-label'
		));
		$attrs = array(
			'class' => 'input-md form-control',
			'placeholder' => __('Email', 'wp-easy-events')
		);
		if (!empty($current_user) && !empty($current_user->user_email)) {
			$attrs['value'] = (string)$current_user->user_email;
		}
		if (!empty($_GET['emd_attendee_email'])) {
			$attrs['value'] = sanitize_email($_GET['emd_attendee_email']);
		} elseif (!empty($set_arrs['attr']['emd_attendee_email'])) {
			$attrs['value'] = $set_arrs['attr']['emd_attendee_email'];
		}
		$obj = $form->add('text', 'emd_attendee_email', '', $attrs);
		$zrule = Array(
			'dependencies' => array() ,
			'email' => array(
				'error',
				__('Email: Please enter a valid email address', 'wp-easy-events')
			) ,
		);
		if (in_array('emd_attendee_email', $req_hide_vars['req'])) {
			$zrule = array_merge($zrule, Array(
				'required' => array(
					'error',
					__('Email is required', 'wp-easy-events')
				)
			));
		}
		$obj->set_rule($zrule);
	}
	//hidden_func
	$emd_attendee_ticket_id = emd_get_hidden_func('unique_id');
	$form->add('hidden', 'emd_attendee_ticket_id', $emd_attendee_ticket_id);
	//hidden_func
	$emd_attendee_full_name = emd_get_hidden_func('concat');
	$form->add('hidden', 'emd_attendee_full_name', $emd_attendee_full_name);
	//hidden
	$obj = $form->add('hidden', 'wpas_form_name', 'event_attendee');
	//hidden_func
	$wpas_form_submitted_by = emd_get_hidden_func('user_login');
	$form->add('hidden', 'wpas_form_submitted_by', $wpas_form_submitted_by);
	//hidden_func
	$wpas_form_submitted_ip = emd_get_hidden_func('user_ip');
	$form->add('hidden', 'wpas_form_submitted_ip', $wpas_form_submitted_ip);
	$ext_inputs = Array();
	$ext_inputs = apply_filters('emd_ext_form_inputs', $ext_inputs, 'wp_easy_events', 'event_attendee');
	foreach ($ext_inputs as $input_param) {
		$inp_name = $input_param['name'];
		if (!in_array($input_param['name'], $req_hide_vars['hide'])) {
			if ($input_param['type'] == 'hidden') {
				$obj = $form->add('hidden', $input_param['name'], $input_param['vals']);
			} elseif ($input_param['type'] == 'select') {
				$form->add('label', 'label_' . $input_param['name'], $input_param['name'], $input_param['label'], array(
					'class' => 'control-label'
				));
				$ext_class['class'] = 'input-md';
				if (!empty($input_param['multiple'])) {
					$ext_class['multiple'] = 'multiple';
					$input_param['name'] = $input_param['name'] . '[]';
				}
				$obj = $form->add('select', $input_param['name'], '', $ext_class, '', '{"allowClear":true,"placeholder":"' . __("Please Select", "wp-easy-events") . '","placeholderOption":"first"}');
				$obj->add_options($input_param['vals']);
				$obj->disable_spam_filter();
			} elseif ($input_param['type'] == 'text') {
				$form->add('label', 'label_' . $input_param['name'], $input_param['name'], $input_param['label'], array(
					'class' => 'control-label'
				));
				$obj = $form->add('text', $input_param['name'], '', array(
					'class' => 'input-md form-control',
					'placeholder' => $input_param['label']
				));
			} elseif ($input_param['type'] == 'checkbox') {
				$form->add('label', 'label_' . $input_param['name'] . '_1', $input_param['name'] . '_1', $input_param['label'], array(
					'class' => 'control-label'
				));
				$obj = $form->add('checkbox', $input_param['name'], 1, $input_param['default']);
				$obj->set_attributes(array(
					'class' => 'input_' . $input_param['name'] . ' control checkbox',
				));
			}
			if ($input_param['type'] != 'hidden' && in_array($inp_name, $req_hide_vars['req'])) {
				$zrule = Array(
					'dependencies' => $input_param['dependencies'],
					'required' => array(
						'error',
						$input_param['label'] . __(' is required', 'wp-easy-events')
					)
				);
				$obj->set_rule($zrule);
			}
		}
	}
	$form->assign('show_captcha', $show_captcha);
	if ($show_captcha == 1) {
		//Captcha
		$form->add('captcha', 'captcha_image', 'captcha_code', '', '<span style="font-weight:bold;" class="refresh-txt">Refresh</span>', 'refcapt');
		$form->add('label', 'label_captcha_code', 'captcha_code', __('Please enter the characters with black color.', 'wp-easy-events'));
		$obj = $form->add('text', 'captcha_code', '', array(
			'placeholder' => __('Code', 'wp-easy-events')
		));
		$obj->set_rule(array(
			'required' => array(
				'error',
				__('Captcha is required', 'wp-easy-events')
			) ,
			'captcha' => array(
				'error',
				__('Characters from captcha image entered incorrectly!', 'wp-easy-events')
			)
		));
	}
	$form->add('submit', 'singlebutton_event_attendee', '' . __('Submit', 'wp-easy-events') . ' ', array(
		'class' => 'wpas-button wpas-juibutton-success   col-md-12 col-lg-12 col-xs-12 col-sm-12'
	));
	return $form;
}
/**
 * Process each form and show error or success
 *
 * @since WPAS 4.0
 *
 * @return html
 */
function wp_easy_events_process_event_attendee($atts) {
	$show_form = 1;
	$access_views = get_option('wp_easy_events_access_views', Array());
	if (!current_user_can('view_event_attendee') && !empty($access_views['forms']) && in_array('event_attendee', $access_views['forms'])) {
		$show_form = 0;
	}
	$form_init_variables = get_option('wp_easy_events_glob_forms_init_list');
	$form_variables = get_option('wp_easy_events_glob_forms_list');
	if ($show_form == 1) {
		if (!empty($form_init_variables['event_attendee']['login_reg'])) {
			$show_login_register = (isset($form_variables['event_attendee']['login_reg']) ? $form_variables['event_attendee']['login_reg'] : $form_init_variables['event_attendee']['login_reg']);
			if (!is_user_logged_in() && $show_login_register != 'none') {
				do_action('emd_show_login_register_forms', 'wp_easy_events', 'event_attendee', $show_login_register);
				return;
			}
		}
		wp_enqueue_script('wpas-jvalidate-js');
		wp_enqueue_style('wpasui');
		wp_enqueue_style('jq-css');
		wp_enqueue_style('event-attendee-forms');
		wp_enqueue_script('event-attendee-forms-js');
		wp_easy_events_enq_custom_css();
		do_action('emd_ext_form_enq', 'wp_easy_events', 'event_attendee');
		$success_msg = (isset($form_variables['event_attendee']['success_msg']) ? $form_variables['event_attendee']['success_msg'] : $form_init_variables['event_attendee']['success_msg']);
		$error_msg = (isset($form_variables['event_attendee']['error_msg']) ? $form_variables['event_attendee']['error_msg'] : $form_init_variables['event_attendee']['error_msg']);
		return emd_submit_php_form('event_attendee', 'wp_easy_events', 'emd_event_attendee', 'publish', 'publish', $success_msg, $error_msg, 2, 1, $atts);
	} else {
		$noaccess_msg = (isset($form_variables['event_attendee']['noaccess_msg']) ? $form_variables['event_attendee']['noaccess_msg'] : $form_init_variables['event_attendee']['noaccess_msg']);
		return "<div class='alert alert-info not-authorized'>" . $noaccess_msg . "</div>";
	}
}
