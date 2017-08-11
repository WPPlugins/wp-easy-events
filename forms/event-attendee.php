
<div class="form-alerts">
<?php
echo (isset($zf_error) ? $zf_error : (isset($error) ? $error : ''));
$form_list = get_option('wp_easy_events_glob_forms_list');
$form_list_init = get_option('wp_easy_events_glob_forms_init_list');
if (!empty($form_list['event_attendee'])) {
	$form_variables = $form_list['event_attendee'];
}
$form_variables_init = $form_list_init['event_attendee'];
$max_row = count($form_variables_init);
foreach ($form_variables_init as $fkey => $fval) {
	if (empty($form_variables[$fkey])) {
		$form_variables[$fkey] = $form_variables_init[$fkey];
	}
}
$ext_inputs = Array();
$ext_inputs = apply_filters('emd_ext_form_inputs', $ext_inputs, 'wp_easy_events', 'event_attendee');
$form_variables = apply_filters('emd_ext_form_var_init', $form_variables, 'wp_easy_events', 'event_attendee');
$req_hide_vars = emd_get_form_req_hide_vars('wp_easy_events', 'event_attendee');
$glob_list = get_option('wp_easy_events_glob_list');
?>
</div>
<fieldset>
<?php wp_nonce_field('event_attendee', 'event_attendee_nonce'); ?>
<input type="hidden" name="form_name" id="form_name" value="event_attendee">
<div class="event_attendee-btn-fields container-fluid">
<!-- event_attendee Form Attributes -->
<div class="event_attendee_attributes">
<div id="row1" class="row ">
<!-- rel-ent input-->
<?php if ($form_variables['rel_event_attendee']['show'] == 1) { ?>
<div class="col-md-<?php echo $form_variables['rel_event_attendee']['size']; ?>">
<div class="form-group">
<label id="label_rel_event_attendee" class="control-label" for="rel_event_attendee">
<?php _e('Events', 'wp-easy-events'); ?>
<span style="display: inline-flex;right: 0px; position: relative; top:-6px;">
<?php if (in_array('rel_event_attendee', $req_hide_vars['req'])) { ?>
<a href="#" data-html="true" data-toggle="tooltip" title="<?php _e('Events field is required', 'wp-easy-events'); ?>" id="info_rel_event_attendee" class="helptip">
<span class="field-icons icons-required"></span>
</a>
<?php
	} ?>
</span>
</label>
<?php echo $rel_event_attendee; ?>
</div>
</div>
<?php
} ?>
</div>
<div id="row2" class="row ">
<!-- text input-->
<?php if ($form_variables['emd_attendee_first_name']['show'] == 1) { ?>
<div class="col-md-<?php echo $form_variables['emd_attendee_first_name']['size']; ?> woptdiv">
<div class="form-group">
<label id="label_emd_attendee_first_name" class="control-label" for="emd_attendee_first_name">
<?php _e('First Name', 'wp-easy-events'); ?>
<span style="display: inline-flex;right: 0px; position: relative; top:-6px;">
<?php if (in_array('emd_attendee_first_name', $req_hide_vars['req'])) { ?>
<a href="#" data-html="true" data-toggle="tooltip" title="<?php _e('First Name field is required', 'wp-easy-events'); ?>" id="info_emd_attendee_first_name" class="helptip">
<span class="field-icons icons-required"></span>
</a>
<?php
	} ?>
</span>
</label>
<?php echo $emd_attendee_first_name; ?>
</div>
</div>
<?php
} ?>
</div>
<div id="row3" class="row ">
<!-- text input-->
<?php if ($form_variables['emd_attendee_last_name']['show'] == 1) { ?>
<div class="col-md-<?php echo $form_variables['emd_attendee_last_name']['size']; ?> woptdiv">
<div class="form-group">
<label id="label_emd_attendee_last_name" class="control-label" for="emd_attendee_last_name">
<?php _e('Last Name', 'wp-easy-events'); ?>
<span style="display: inline-flex;right: 0px; position: relative; top:-6px;">
<?php if (in_array('emd_attendee_last_name', $req_hide_vars['req'])) { ?>
<a href="#" data-html="true" data-toggle="tooltip" title="<?php _e('Last Name field is required', 'wp-easy-events'); ?>" id="info_emd_attendee_last_name" class="helptip">
<span class="field-icons icons-required"></span>
</a>
<?php
	} ?>
</span>
</label>
<?php echo $emd_attendee_last_name; ?>
</div>
</div>
<?php
} ?>
</div>
<div id="row4" class="row ">
<!-- text input-->
<?php if ($form_variables['emd_attendee_quantity']['show'] == 1) { ?>
<div class="col-md-<?php echo $form_variables['emd_attendee_quantity']['size']; ?> woptdiv">
<div class="form-group">
<label id="label_emd_attendee_quantity" class="control-label" for="emd_attendee_quantity">
<?php _e('Quantity', 'wp-easy-events'); ?>
<span style="display: inline-flex;right: 0px; position: relative; top:-6px;">
<?php if (in_array('emd_attendee_quantity', $req_hide_vars['req'])) { ?>
<a href="#" data-html="true" data-toggle="tooltip" title="<?php _e('Quantity field is required', 'wp-easy-events'); ?>" id="info_emd_attendee_quantity" class="helptip">
<span class="field-icons icons-required"></span>
</a>
<?php
	} ?>
</span>
</label>
<?php echo $emd_attendee_quantity; ?>
</div>
</div>
<?php
} ?>
</div>
<div id="row5" class="row ">
<!-- text input-->
<?php if ($form_variables['emd_attendee_email']['show'] == 1) { ?>
<div class="col-md-<?php echo $form_variables['emd_attendee_email']['size']; ?> woptdiv">
<div class="form-group">
<label id="label_emd_attendee_email" class="control-label" for="emd_attendee_email">
<?php _e('Email', 'wp-easy-events'); ?>
<span style="display: inline-flex;right: 0px; position: relative; top:-6px;">
<?php if (in_array('emd_attendee_email', $req_hide_vars['req'])) { ?>
<a href="#" data-html="true" data-toggle="tooltip" title="<?php _e('Email field is required', 'wp-easy-events'); ?>" id="info_emd_attendee_email" class="helptip">
<span class="field-icons icons-required"></span>
</a>
<?php
	} ?>
</span>
</label>
<?php echo $emd_attendee_email; ?>
</div>
</div>
<?php
} ?>
</div>
 
 
<div id="row6" class="row ext-row">
<?php if (!empty($form_variables['mailchimp_optin']) && $form_variables['mailchimp_optin']['show'] == 1) { ?>
<div class="col-sm-12">
<div class="form-group">
<div class="col-md-<?php echo $form_variables['mailchimp_optin']['size']; ?> ">
<div id="mailchimp_optin-singlec" style="padding-bottom:10px;" class="checkbox">
<?php echo $mailchimp_optin_1; ?>
<?php echo $form_variables['mailchimp_optin']['label']; ?>
</div>
</div>
</div>
</div>
<?php
} ?>
</div>
 
 
 
</div><!--form-attributes-->
<?php if ($show_captcha == 1) { ?>
<div class="row">
<div class="col-xs-12">
<div id="captcha-group" class="form-group">
<?php echo $captcha_image; ?>
<label style="padding:0px;" id="label_captcha_code" class="control-label" for="captcha_code">
<a id="info_captcha_code_help" class="helptip" data-html="true" data-toggle="tooltip" href="#" title="<?php _e('Please enter the characters with black color in the image above.', 'wp-easy-events'); ?>">
<span class="field-icons icons-help"></span>
</a>
<a id="info_captcha_code_req" class="helptip" title="<?php _e('Security Code field is required', 'wp-easy-events'); ?>" data-toggle="tooltip" href="#">
<span class="field-icons icons-required"></span>
</a>
</label>
<?php echo $captcha_code; ?>
</div>
</div>
</div>
<?php
} ?>
<!-- Button -->
<div class="row">
<div class="col-md-12">
<div class="wpas-form-actions">
<?php echo $singlebutton_event_attendee; ?>
</div>
</div>
</div>
</div><!--form-btn-fields-->
</fieldset>