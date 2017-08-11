<?php
/**
 * Ajax Form Functions
 *
 * @package     EMD
 * @copyright   Copyright (c) 2014,  Emarket Design
 * @since       WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
global $wp_version;
/**
 * Add paging javascript for search results
 *
 * @since WPAS 4.0
 * @param string $form_name
 *
 */
if (!function_exists('emd_get_paging_js')) {
	function emd_get_paging_js($form_name) { ?>
		<script type='text/javascript'>
			var page =1;
		$(window).scrollTop(0);
		$('form[id="<?php echo $form_name; ?>"]').fadeOut(1000);
		$('#<?php echo $form_name; ?>_show_link a').click(function(){
			$('form[id="<?php echo $form_name; ?>"]').fadeIn(1000);
			$('#<?php echo $form_name; ?>_hide_link').show();
			$('#<?php echo $form_name; ?>_show_link').hide();
		});
		$('#<?php echo $form_name; ?>_hide_link a').click(function(){
			$('form[id="<?php echo $form_name; ?>"]').fadeOut(1000);
			$('#<?php echo $form_name; ?>_show_link').show();
			$('#<?php echo $form_name; ?>_hide_link').hide();
		});
		$('.pagination-bar a').click(function(){
				if($(this).hasClass('prev')){
				page --;
				}  
				else if($(this).hasClass('next')){
				page ++;
				}  
				else{  
				page = $(this).text();
				}
				var div_id = $(this).closest('.emd-view-results').attr('id');
				var entity = $('#emd_entity').val();
				var view = $('#emd_view').val();
				var app = $('#emd_app').val();
				var form = $(this).closest('.search-success-error').attr('id').replace('-search-success-error','');
				load_posts(div_id,entity,view,form,app);
				return false;
				}); 
		var load_posts = function(div_id,entity,view,form,app){
			$.ajax({
	type: 'GET',
	url: <?php echo $form_name; ?>_vars.ajax_url,
	cache: false,
	async: false,
	data: {action:'emd_get_pagenum',pageno: page,entity:entity,view:view,form:form,app:app},
	success: function(response)
	{
	$('#'+ div_id).html(response);
	$('.pagination-bar a').click(function(){
		if($(this).hasClass('prev')){
		page --;
		}
		else if($(this).hasClass('next')){
		page ++;
		}
		else{
		page = $(this).text();
		}
		var div_id = $(this).closest('.emd-view-results').attr('id');
		var entity = $('#emd_entity').val();
		var view = $('#emd_view').val();
		var app = $('#emd_app').val();
		var form = $(this).closest('.search-success-error').attr('id').replace('-search-success-error','');
		load_posts(div_id,entity,view,form,app);
		return false;
		});
	},
		});
	}</script>
	<?php
	}
}
/**
 * Get page layout
 *
 * @since WPAS 4.0
 *
 * @return string $list_layout
 */
if (!function_exists('emd_get_pagenum')) {
	function emd_get_pagenum() {
		$response = false;
		$pageno = isset($_GET['pageno']) ? (int) $_GET['pageno'] : 1;
		$myentity = isset($_GET['entity']) ? (string) $_GET['entity'] : '';
		$myview = isset($_GET['view']) ? (string) $_GET['view'] : '';
		$myapp = isset($_GET['app']) ? (string) $_GET['app'] : '';
		$myform = isset($_GET['form']) ? (string) $_GET['form'] : '';
		$sess_name = strtoupper($myapp);
		$session_class = $sess_name();
		$sess_form_args = $session_class->session->get($myform . '_args');
		if (!empty($myentity) && !empty($myform) && !empty($sess_form_args)) {
			$func_layout = $myapp . "_" . $myview . "_set_shc";
			$list_layout = $func_layout('', $sess_form_args, $myform, $pageno);
			if ($list_layout != '') {
				echo $list_layout;
				die();
			}
		}
		echo $response;
		die();
	}
}
/**
 * Process submit form or show errors if form doesn't validate
 *
 * @since WPAS 4.0
 * @param string $form_name
 * @param string $myapp
 * @param string $myentity
 * @param string $post_status
 * @param string $success_msg
 * @param string $error_msg
 *
 */
if (!function_exists('emd_submit_ajax_form')) {
	function emd_submit_ajax_form($form_name, $myapp, $myentity, $post_status, $visitor_post_status, $success_msg, $error_msg) {
		$ret = check_ajax_referer($form_name, 'nonce', false);
		if ($ret === false) {
			echo '<div class="text-danger"><a href="' . wp_get_referer() . '">' . __('Please refresh the page and try again.', 'emd-plugins') . '</a></div>';
			die();
		}
		$sess_name = strtoupper($myapp);
		$session_class = $sess_name();
		$sess_uploads = $session_class->session->get('uploads');
		if (!empty($sess_uploads)) {
			foreach ($sess_uploads as $key_attr => $files) {
				$_FILES[$key_attr] = $files;
			}
		}
		$set_fname = $myapp . "_set_" . $form_name;
		$add_new_link = '<a href="' . esc_url(wp_get_referer()) . '" title="' . __('Add New', 'emd-plugins') . '">' . '&nbsp;' . __('Add New', 'emd-plugins') . '</a>';
		$form = $set_fname();
		if ($form->validate()) {
			$result = emd_submit_form($myapp, $myentity, $post_status, $visitor_post_status, $form);
			if ($result !== false) {
				$rel_uniqs = $result['rel_uniqs'];
				if(!empty($rel_uniqs)){
					foreach($rel_uniqs as $kconn => $rel_conn){
						if(is_array($rel_conn)){        
							foreach($rel_conn as $rpid){
								do_action('emd_notify', $myapp, $result['id'], 'rel', 'front_add', Array($kconn => $rpid));
							}
						}
						else{
							do_action('emd_notify', $myapp, $result['id'], 'rel', 'front_add', Array($kconn => $rel_conn));
						}
					}
				}
				do_action('emd_notify', $myapp, $result['id'], 'entity', 'front_add', $rel_uniqs);
				echo '<div class="text-success">' . $success_msg . $add_new_link . '</div>';
				die();
			} else {
				echo '<div class="text-danger">' . $error_msg . $add_new_link . '</div>';
				die();
			}
		} else {
			foreach ($form->errors as $myerror) {
				foreach ($myerror as $err_text) {
					echo '<div class="text-danger">' . $err_text;
				}
			}
			echo $add_new_link . '</div>';
			die();
		}
	}
}
/**
 * Process search form or show errors if form doesn't validate
 *
 * @since WPAS 4.0
 * @param string $form_name
 * @param string $myapp
 * @param string $myentity
 * @param string $noresult_msg
 * @param string $view_name
 *
 */
if (!function_exists('emd_search_ajax_form')) {
	function emd_search_ajax_form($form_name, $myapp, $myentity, $noresult_msg, $view_name) {
		$ret = check_ajax_referer($form_name, 'nonce', false);
		if ($ret === false) {
			echo '<div class="text-danger"><a href="' . wp_get_referer() . '">' . __('Please refresh the page and try again.', 'emd-plugins') . '</a></div>';
			die();
		}
		$sess_name = strtoupper($myapp);
		$session_class = $sess_name();
		$sess_uploads = $session_class->session->get('uploads');
		if (!empty($sess_uploads)) {
			foreach ($sess_uploads as $key_attr => $files) {
				$_FILES[$key_attr] = $files;
			}
		}
		$set_fname = $myapp . "_set_" . $form_name;
		$form = $set_fname();
		if ($form->validate()) {
			$path = plugin_dir_path(__FILE__);
			$layout = emd_search_form($myapp, $myentity, $form_name, $view_name, $noresult_msg, $path);
			echo '<div id="' . $form_name . '_show_link" style="padding-top:10px;padding-bottom:20px;"><a href="#">
			<span id="' . $form_name . '_show_link_span" style="color:#fff;background-color:#5bc0de;border-color:#5bc0de;padding:1px 5px;font-size:12px;line-height:1.5;border-radius:3px;display:inline-block;margin-bottom:0;font-weight:normal;text-align:center;vertical-align:middle;touch-action:manipulation;cursor:pointer;background-image:none;border:1px solid rgba(0,0,0,0);white-space:nowrap;">'. __('Show Form','emd-plugins') . '</span></a></div>
			<div id="' . $form_name . '_hide_link" style="display:none;padding-top:10px;padding-bottom:20px;"><a href="#">
			<span id="' . $form_name . '_hide_link_span" style="color:#fff;background-color:#d9534f;border-color:#d43f3a;padding:1px 5px;font-size:12px;line-height:1.5;border-radius:3px;display:inline-block;margin-bottom:0;font-weight:normal;text-align:center;vertical-align:middle;touch-action:manipulation;cursor:pointer;background-image:none;border:1px solid rgba(0,0,0,0);white-space:nowrap;">' . __('Hide Form','emd-plugins') . '</span></a></div>';
			echo $layout;
			echo emd_get_paging_js($form_name);
			die();
		} else {
			foreach ($form->errors as $myerror) {
				foreach ($myerror as $err_text) {
					echo '<div class="text-danger">' . $err_text . '</div>';
				}
			}
			die();
		}
	}
}
/**
 * Ajax load file
 *
 * @since WPAS 4.0
 *
 */
if (!function_exists('emd_load_file')) {
	function emd_load_file() {
		$ret = check_ajax_referer('emd_load_file', 'nonce', false);
		if ($ret === false) {
			echo '<div class="text-danger"><a href="' . wp_get_referer() . '">' . __('Please refresh the page and try again.', 'emd-plugins') . '</a></div>';
			die();
		}
		require_once constant($_POST['path']) . 'assets/ext/filepicker/upload.php';
		$upload_handler = new UploadHandler(true, $_POST['field'], $_POST['extensions']);
		die();
	}
}
