<?php
/**
 * License Functions
 *
 * @package     EMD
 * @copyright   Copyright (c) 2014,  Emarket Design
 * @since       WPAS 4.2
 */
if (!defined('ABSPATH')) exit;

add_action('emd_show_license_page','emd_show_license_page',1);
/**
 * Show settings page for licenses
 *
 * @param string $app
 * @since WPAS 4.2
 *
 * @return html page content
 */

function emd_show_license_page($app){
	//display app license and all extension licenses	
	global $title;
	$settings = get_option('emd_license_settings');
	?>
		<div class="wrap">
		<h2><?php echo $title; ?></h2>
		<p><?php _e('Please enter and activate your <b>license keys</b>.', 'emd-plugins'); ?></p>
		<form method="post" action="options.php">
		<table class="form-table">
		<tbody>
		<?php 
		settings_fields('emd_licenses'); 
	if(!empty($settings[$app])){
		emd_show_license_row($app,$settings[$app]['name']);
	}
	foreach($settings as $key => $val){
		if($val['type'] == 'ext'){
			emd_show_license_row($key,$val['name']);
		}
	} 
	?>
		</tbody>
		</table>
		<?php submit_button(); ?>
		</form>
		</div>
		<?php
}

/**
 * Show input field and license activate/deactivate link for app and each extension
 *
 * @param string $app
 * @param string $name license name
 * @since WPAS 4.2
 *
 * @return html page content
 */
function emd_show_license_row($app,$name){
	$license = false;
	$licenses = get_option('emd_licenses',Array());
	$status = get_option($app . '_license_status','inactive');
	$error = get_option($app . '_license_error','');
	if(!empty($licenses) && isset($licenses[$app . '_license_key'])){
		$license = $licenses[$app . '_license_key'];
	}
	?>
		<tr>
		<th scope="row">
		<?php echo $name; ?>
		</th>
		<td>
		<input id="<?php esc_attr_e($app) ?>_license_key" name="emd_licenses[<?php esc_attr_e($app) ?>_license_key]" type="text" class="regular-text" value="<?php esc_attr_e($license); ?>" />
		<?php if (false !== $license) { ?>
		<input type="hidden" id="<?php esc_attr_e($app) ?>_license_status" name="<?php esc_attr_e($app) ?>_license_status" value="<?php echo $status;?>">
			<?php if ($status == 'valid') { ?>
				<?php wp_nonce_field($app . '_license_nonce', $app . '_license_nonce'); ?>
					<input type="submit" class="button-secondary" name="<?php esc_attr_e($app) ?>_license_deactivate" value="<?php _e('Deactivate License', 'emd-plugins'); ?>"/>
					<?php
			} else {
				wp_nonce_field($app . '_license_nonce', $app . '_license_nonce'); ?>
					<input type="submit" class="button-secondary" name="<?php esc_attr_e($app) ?>_license_activate" value="<?php _e('Activate License', 'emd-plugins'); ?>"/>
					<?php
			}
			if($status == 'valid'){
				echo "<span class='dashicons dashicons-yes' style='color:green;font-size:28px;padding-right:8px;'></span><span style='color:green;font-weight:700;'>" . strtoupper($status) . "</span>";
			}
			else {
				echo "<span class='dashicons dashicons-flag' style='color:red;font-size:28px;padding-right:8px;'></span><span style='color:red;font-weight:700;'>" . strtoupper($status) . "</span>";
			}
		}
		if(!empty($error)){
			echo "<div style='padding:5px;'><span style='color:red;font-weight:700;background-color:white;'>" . __('Error:','emd-plugins') . '&nbsp;' . $error . "</span></div>";
		}
		?>
	</td>
		</tr>
		<?php 
}

add_action( 'admin_init', 'emd_license_register');

/**
 * Register license settings option
 *
 * @since WPAS 4.2
 *
 */
function emd_license_register(){
	if ( false == get_option( 'emd_licenses' ) ) {
                add_option( 'emd_licenses' );
        }
	$settings = get_option('emd_license_settings');
	if(!empty($settings)){
		foreach($settings as $key => $val){
			add_settings_field($key . '_license_key', $val['name'],'','emd_licenses');
		}
	}
	register_setting('emd_licenses','emd_licenses','emd_sanitize_license');
}
/**
 * Sanitize license settings
 *
 * @since WPAS 4.2
 * @param array $new
 *
 * @return array $new
 */
function emd_sanitize_license($new){
	$old = get_option('emd_licenses');
	if(empty($_POST)){ return $new; }
	if(!isset($_POST['submit'])) { return $old; }
	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $old;
        }
	foreach($new as $nkey => $nval){
		if(preg_match('/_license_key$/',$nkey)){
			$match = str_replace("_license_key","",$nkey);
			if($new[$nkey] != $old[$nkey]){
				update_option($match.'_license_status','inactive');
				update_option($match . '_license_error', '');
			}
			if(empty($new[$nkey])){
				unset($old[$nkey]);
			}
			else {
				$old[$nkey] = $new[$nkey];
			}
		}
	}
	return $old;
}

add_action( 'admin_init', 'emd_activate_deactivate_license');
/**
 * Activate/Deactivate license by calling edd api on plugin author's site
 * @since WPAS 4.0
 *
 */
function emd_activate_deactivate_license() {
	if(!isset($_POST['emd_licenses'])) return;
	if(isset($_POST['submit'])) return;
	$license_action = "";
	$license_on = "";
	$license_settings = get_option('emd_license_settings');
	
	if(!empty($license_settings)){
		foreach($license_settings as $key => $val){
			$license_status = get_option($key . '_license_status','');
			if($license_status != 'valid' && isset($_POST[$key . '_license_activate'])){
				$license_action = "activate";
				$license_on = $key;
				break;
			} elseif ($license_status != 'deactivated' && isset($_POST[$key . '_license_deactivate'])) {
				$license_action = "deactivate";
				$license_on = $key;
				break;
			}
		}
	}
	if (!empty($license_action)) {
		$post_licenses = $_POST['emd_licenses'];
		// run a quick security check
		if (!check_admin_referer($license_on . '_license_nonce', $license_on . '_license_nonce')) return;
		// retrieve the license from the database
		$license = trim($post_licenses[$license_on . '_license_key']);
		// data to send in our API request
		$api_params = array(
				'edd_action' => $license_action . '_license',
				'license' => $license,
				'item_name' => urlencode(constant(strtoupper($license_on) . '_EDD_ITEM_NAME')) , // the name of product
				'url' => home_url()
				);

		// Call the custom API.
		$response = wp_remote_post(constant(strtoupper($license_on) . '_EDD_STORE_URL') , array(
					'timeout' => 15,
					'sslverify' => false,
					'body' => $api_params
					));
		$error = '';
		// make sure the response came back okay
		if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
			if(is_wp_error($response)){
                                $error = $response->get_error_message();
                        } else {
                                $error = __('An error occurred, please try again.','emd-plugins');
                        }
			update_option($license_on . '_license_error', $error);
			return false;
		}
		// decode the license data
		$license_data = json_decode(wp_remote_retrieve_body($response));

		if($license_action == 'activate' && false === $license_data->success){
			switch($license_data->error){
				case 'expired':
					$error = sprintf(
						__('Your license key expired on %s.','emd-plugins'),
						date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp')))
					);
					break;
				case 'revoked':
					$error = __('Your license key has been disabled.','emd-plugins');
					break;
				case 'missing':
					$error = __('Invalid license.','emd-plugins');
					break;
				case 'invalid':
				case 'site_inactive':
					$error = __('Your license is not active for this URL.','emd-plugins');
					break;
				case 'item_name_mismatch':
					$error = sprintf(__('This appears to be an invalid license key for %s.','emd-plugins'), constant(strtoupper($license_on) . '_EDD_ITEM_NAME'));
					break;

				case 'no_activations_left':
					$error = __('Your license key has reached its activation limit.','emd-plugins');
					break;
				default :
					$error = __( 'An error occurred, please try again.','emd-plugins');
					break;
			}
			if(!empty($error)){
				update_option($license_on . '_license_error', $error);
				return false;
			}
		}
		update_option($license_on . '_license_error', '');

		if ($license_action == 'activate') {
			update_option($license_on . '_license_status',$license_data->license);
		} elseif ($license_action == 'deactivate' && $license_data->license == 'deactivated') {
			update_option($license_on . '_license_status',$license_data->license);
		}
	}
}
function emd_check_license_weekly($license,$license_on){
	// data to send to API request
	$api_params = array(
		'edd_action'=> 'check_license',
		'license'       => $license,
		'item_name' => urlencode(constant(strtoupper($license_on) . '_EDD_ITEM_NAME')),
		'url'       => home_url()
	);

	// Call the API
	$response = wp_remote_post(
		constant(strtoupper($license_on) . '_EDD_STORE_URL'),
		array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		)
	);

	// make sure the response came back okay
	if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
		if(is_wp_error($response)){
			$error = $response->get_error_message();
		} else {
			$error = __('An error occurred, please try again.','emd-plugins');
		}
		update_option($license_on . '_license_error', $error);
		return false;
	}

	$license_data = json_decode(wp_remote_retrieve_body($response));

	update_option($license_on . '_license_status', $license_data->license);
	update_option($license_on . '_license_error', '');
	return $license_data->license;
}
function emd_check_license_notices($license_on,$plugin_name,$plugin_roles){
	$check_lic = 0;
	if(current_user_can('manage_options')){
		$check_lic = 1;
	}
	elseif(!empty($plugin_roles)){
		$user = wp_get_current_user();
		foreach($plugin_roles as $prole){
			if(in_array($prole,(array) $user->roles)) {
				$check_lic = 1;
				break;
			}
		}
	}
	if($check_lic == 0) return;		
	$message = '';
	$licenses = get_option('emd_licenses',Array());
	if(!empty($licenses) && isset($licenses[$license_on . '_license_key'])){
		$license = $licenses[$license_on . '_license_key'];
		//check status 
		$status = get_option($license_on . '_license_status','inactive');
		$error = get_option($license_on . '_license_error','');
		if($status != 'valid' && empty($error)){
			$message = 'message_activate';
		}
		elseif($status != 'valid' && !empty($error)){
			$message = 'message_invalid';
		}
		else{
			//get next license check date and see now > 
			$next_check = get_option($license_on . '_next_license_check','');
			if(!empty($next_check)){
				$today = date('Y-m-d');
				if($today >= $next_check){
					if(!empty($error)){
						$message = 'message_invalid';
					}
					else {
						$lic_check = emd_check_license_weekly($license,$license_on);
						if($lic_check != 'valid'){
							$message = 'message_invalid';
						}
					}
				}	
			}
			$next_check = date('Y-m-d', strtotime('+7 days'));
			update_option($license_on . '_next_license_check',$next_check);
		}
	}
	else {
		$message = 'message_activate';
	}
	if($message == 'message_activate'){
		if(current_user_can('manage_options')){	
			echo '<div class="error"><p>' . sprintf(__('Thanks for installing %s. Please <a href="%s">activate your license key</a> before start using.','emd-plugins'), $plugin_name,admin_url("admin.php?page=" . $license_on . "_licenses")) . '</p></div>';
		}
		else {
			echo '<div class="error"><p>' . sprintf(__('Please notify the site administrator to activate %s license.','emd-plugins'), $plugin_name) . '</p></div>';
		}
	}
	elseif($message == 'message_invalid'){
		if(current_user_can('manage_options')){	
			echo '<div class="error"><p>' . sprintf(__('You have invalid or expired license for %s. Please <a href="%s">go to licenses page</a> to correct this issue.','emd-plugins'), $plugin_name,admin_url("admin.php?page=" . $license_on . "_licenses")) . '</p></div>';
		}
		else {
			echo '<div class="error"><p>' . sprintf(__('Please notify the site administrator to activate expired or invalid %s license.','emd-plugins'), $plugin_name) . '</p></div>';
		}
	}
}
