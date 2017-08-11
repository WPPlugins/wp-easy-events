<?php
/**
 * Install and Deactivate Plugin Functions
 * @package WP_EASY_EVENTS
 * @since WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
if (!class_exists('Wp_Easy_Events_Install_Deactivate')):
	/**
	 * Wp_Easy_Events_Install_Deactivate Class
	 * @since WPAS 4.0
	 */
	class Wp_Easy_Events_Install_Deactivate {
		private $option_name;
		/**
		 * Hooks for install and deactivation and create options
		 * @since WPAS 4.0
		 */
		public function __construct() {
			$this->option_name = 'wp_easy_events';
			add_action('init', array(
				$this,
				'check_update'
			));
			register_activation_hook(WP_EASY_EVENTS_PLUGIN_FILE, array(
				$this,
				'install'
			));
			register_deactivation_hook(WP_EASY_EVENTS_PLUGIN_FILE, array(
				$this,
				'deactivate'
			));
			add_action('wp_head', array(
				$this,
				'version_in_header'
			));
			add_action('admin_init', array(
				$this,
				'setup_pages'
			));
			add_action('admin_notices', array(
				$this,
				'install_notice'
			));
			add_action('generate_rewrite_rules', 'emd_create_rewrite_rules');
			add_filter('query_vars', 'emd_query_vars');
			add_action('admin_init', array(
				$this,
				'register_settings'
			) , 0);
			if (is_admin()) {
				$this->stax = new Emd_Single_Taxonomy('wp-easy-events');
			}
			add_filter('tiny_mce_before_init', array(
				$this,
				'tinymce_fix'
			));
			add_action('init', array(
				$this,
				'init_extensions'
			) , 99);
			do_action('emd_ext_actions', $this->option_name);
		}
		public function check_update() {
			$curr_version = get_option($this->option_name . '_version', 1);
			$new_version = constant(strtoupper($this->option_name) . '_VERSION');
			if (version_compare($curr_version, $new_version, '<')) {
				P2P_Storage::install();
				$this->set_options();
				$this->set_roles_caps();
				$this->set_notification();
				if (!get_option($this->option_name . '_activation_date')) {
					$triggerdate = mktime(0, 0, 0, date('m') , date('d') + 7, date('Y'));
					add_option($this->option_name . '_activation_date', $triggerdate);
				}
				set_transient($this->option_name . '_activate_redirect', true, 30);
				do_action($this->option_name . '_upgrade', $new_version);
				update_option($this->option_name . '_version', $new_version);
			}
		}
		public function version_in_header() {
			$version = constant(strtoupper($this->option_name) . '_VERSION');
			$name = constant(strtoupper($this->option_name) . '_NAME');
			echo '<meta name="generator" content="' . $name . ' v' . $version . ' - https://emdplugins.com" />' . "\n";
		}
		public function init_extensions() {
			do_action('emd_ext_init', $this->option_name);
		}
		/**
		 * Runs on plugin install to setup custom post types and taxonomies
		 * flushing rewrite rules, populates settings and options
		 * creates roles and assign capabilities
		 * @since WPAS 4.0
		 *
		 */
		public function install() {
			$this->set_notification();
			P2P_Storage::install();
			$this->set_options();
			Emd_Wpe_Event::register();
			Emd_Event_Organizer::register();
			Emd_Event_Venues::register();
			Emd_Event_Attendee::register();
			flush_rewrite_rules();
			$this->set_roles_caps();
			set_transient($this->option_name . '_activate_redirect', true, 30);
			do_action('emd_ext_install_hook', $this->option_name);
		}
		/**
		 * Runs on plugin deactivate to remove options, caps and roles
		 * flushing rewrite rules
		 * @since WPAS 4.0
		 *
		 */
		public function deactivate() {
			flush_rewrite_rules();
			$this->remove_caps_roles();
			$this->reset_options();
			do_action('emd_ext_deactivate', $this->option_name);
		}
		/**
		 * Register notification and/or license settings
		 * @since WPAS 4.0
		 *
		 */
		public function register_settings() {
			$notif_settings = new Emd_Notifications($this->option_name);
			$notif_settings->register_settings();
			emd_calendar_register_settings($this->option_name);
			do_action('emd_ext_register', $this->option_name);
			if (!get_transient($this->option_name . '_activate_redirect')) {
				return;
			}
			// Delete the redirect transient.
			delete_transient($this->option_name . '_activate_redirect');
			$query_args = array(
				'page' => $this->option_name
			);
			wp_safe_redirect(add_query_arg($query_args, admin_url('admin.php')));
		}
		/**
		 * Sets caps and roles
		 *
		 * @since WPAS 4.0
		 *
		 */
		public function set_roles_caps() {
			global $wp_roles;
			if (class_exists('WP_Roles')) {
				if (!isset($wp_roles)) {
					$wp_roles = new WP_Roles();
				}
			}
			if (is_object($wp_roles)) {
				$mywpee_event_staff = get_role('wpee_event_staff');
				if (empty($mywpee_event_staff)) {
					$mywpee_event_staff = add_role('wpee_event_staff', __('Event Staff', 'wp-easy-events'));
				}
				$this->set_reset_caps($wp_roles, 'add');
			}
		}
		/**
		 * Removes caps and roles
		 *
		 * @since WPAS 4.0
		 *
		 */
		public function remove_caps_roles() {
			global $wp_roles;
			if (class_exists('WP_Roles')) {
				if (!isset($wp_roles)) {
					$wp_roles = new WP_Roles();
				}
			}
			if (is_object($wp_roles)) {
				$this->set_reset_caps($wp_roles, 'remove');
				remove_role('wpee_event_staff');
			}
		}
		/**
		 * Set  capabilities
		 *
		 * @since WPAS 4.0
		 * @param object $wp_roles
		 * @param string $type
		 *
		 */
		public function set_reset_caps($wp_roles, $type) {
			$caps['enable'] = Array(
				'edit_others_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'edit_private_emd_wpe_events' => Array(
					'administrator'
				) ,
				'edit_published_emd_wpe_events' => Array(
					'administrator'
				) ,
				'edit_published_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'manage_operations_emd_wpe_events' => Array(
					'administrator'
				) ,
				'edit_private_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'publish_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'delete_others_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'edit_emd_event_venuess' => Array(
					'administrator'
				) ,
				'read_private_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'edit_emd_wpe_events' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'delete_published_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'manage_operations_emd_event_organizers' => Array(
					'administrator'
				) ,
				'edit_emd_event_cat' => Array(
					'administrator'
				) ,
				'read' => Array(
					'wpee_event_staff'
				) ,
				'export' => Array(
					'administrator'
				) ,
				'assign_emd_event_tag' => Array(
					'administrator'
				) ,
				'delete_emd_event_cat' => Array(
					'administrator'
				) ,
				'manage_operations_emd_event_attendees' => Array(
					'administrator'
				) ,
				'delete_emd_event_tag' => Array(
					'administrator'
				) ,
				'edit_others_emd_wpe_events' => Array(
					'administrator'
				) ,
				'manage_emd_event_tag' => Array(
					'administrator'
				) ,
				'assign_emd_event_cat' => Array(
					'administrator'
				) ,
				'publish_emd_wpe_events' => Array(
					'administrator'
				) ,
				'read_private_emd_wpe_events' => Array(
					'administrator'
				) ,
				'edit_emd_event_organizers' => Array(
					'administrator'
				) ,
				'edit_emd_event_tag' => Array(
					'administrator'
				) ,
				'manage_emd_event_cat' => Array(
					'administrator'
				) ,
				'delete_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'delete_others_emd_wpe_events' => Array(
					'administrator'
				) ,
				'delete_published_emd_wpe_events' => Array(
					'administrator'
				) ,
				'delete_private_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'delete_private_emd_wpe_events' => Array(
					'administrator'
				) ,
				'delete_emd_wpe_events' => Array(
					'administrator'
				) ,
				'view_wp_easy_events_dashboard' => Array(
					'administrator'
				) ,
				'edit_emd_event_attendees' => Array(
					'administrator',
					'wpee_event_staff'
				) ,
				'manage_operations_emd_event_venuess' => Array(
					'administrator'
				) ,
			);
			$caps['enable'] = apply_filters('emd_ext_get_caps', $caps['enable'], $this->option_name);
			foreach ($caps as $stat => $role_caps) {
				foreach ($role_caps as $mycap => $roles) {
					foreach ($roles as $myrole) {
						if (($type == 'add' && $stat == 'enable') || ($stat == 'disable' && $type == 'remove')) {
							$wp_roles->add_cap($myrole, $mycap);
						} else if (($type == 'remove' && $stat == 'enable') || ($type == 'add' && $stat == 'disable')) {
							$wp_roles->remove_cap($myrole, $mycap);
						}
					}
				}
			}
		}
		/**
		 * Sets notification options
		 * @since WPAS 4.0
		 *
		 */
		private function set_notification() {
			$notify_list['event_attendee'] = Array(
				'label' => __('Attendee Tickets', 'wp-easy-events') ,
				'active' => 1,
				'level' => 'rel',
				'entity' => 'emd_wpe_event',
				'ev_front_add' => 1,
				'object' => 'event_attendee',
				'user_msg' => Array(
					'subject' => 'Thank you for your RSVP',
					'message' => '<p>We have received your reservations and will contact you if we have any questions.</p>
<hr>
<p><b>Details</b></p>
<hr>
<div style="width:575px;padding:10px;border:2px solid black;background-color:white">
    <table border="0" cellpadding="5" cellspacing="5">
        <tbody>
            <tr>
                <td rowspan="6">
                     {emd_event_picture},
                </td>
                <td>
                    Event: <strong>{emd_title}</strong>
                </td>
            </tr>
            <tr>
                <td>
                    Ticket ID: <strong> {emd_attendee_ticket_id}</strong>
                </td>
            </tr>
            <tr>
                <td>
                    Start Date: <strong>{emd_event_startdate}</strong>
                </td>
            </tr>
            <tr>
                <td>
                    End Date: <strong>{emd_event_enddate}</strong>
                </td>
            </tr>
            <tr>
                <td>
                    Quantity: <strong>{emd_attendee_quantity}</strong>
                </td>
            </tr>
            <tr>
                <td style="text-align:right">
                    <a href=" {emd_event_attendee_permalink}">Click to view and print your ticket</a>
                </td>
            </tr>
        </tbody>
    </table>
</div>',
					'send_to' => Array(
						Array(
							'active' => 1,
							'entity' => 'emd_event_attendee',
							'attr' => 'emd_attendee_email',
							'label' => __('Attendees Email', 'wp-easy-events') ,
							'rel' => 'event_attendee',
							'from_to' => 'from'
						)
					) ,
					'reply_to' => '',
					'cc' => '',
					'bcc' => ''
				)
			);
			update_option($this->option_name . '_notify_init_list', $notify_list);
			if (get_option($this->option_name . '_notify_list') === false) {
				update_option($this->option_name . '_notify_list', $notify_list);
			}
		}
		/**
		 * Set app specific options
		 *
		 * @since WPAS 4.0
		 *
		 */
		private function set_options() {
			$access_views = Array();
			update_option($this->option_name . '_setup_pages', 1);
			$ent_list = Array(
				'emd_wpe_event' => Array(
					'label' => __('Events', 'wp-easy-events') ,
					'rewrite' => 'events',
					'archive_view' => 0,
					'featured_img' => 1,
					'sortable' => 0,
					'searchable' => 1,
					'unique_keys' => Array(
						'blt_title'
					) ,
					'blt_list' => Array(
						'blt_content' => __('Description', 'wp-easy-events') ,
						'blt_excerpt' => __('Excerpt', 'wp-easy-events') ,
					) ,
					'req_blt' => Array(
						'blt_title' => Array(
							'msg' => __('Title', 'wp-easy-events')
						) ,
					) ,
				) ,
				'emd_event_organizer' => Array(
					'label' => __('Organizers', 'wp-easy-events') ,
					'rewrite' => 'organizers',
					'archive_view' => 0,
					'featured_img' => 1,
					'sortable' => 0,
					'searchable' => 1,
					'unique_keys' => Array(
						'blt_title'
					) ,
					'blt_list' => Array(
						'blt_content' => __('Detail', 'wp-easy-events') ,
					) ,
					'req_blt' => Array(
						'blt_title' => Array(
							'msg' => __('Title', 'wp-easy-events')
						) ,
					) ,
				) ,
				'emd_event_venues' => Array(
					'label' => __('Venues', 'wp-easy-events') ,
					'rewrite' => 'venues',
					'archive_view' => 0,
					'featured_img' => 1,
					'sortable' => 0,
					'searchable' => 1,
					'unique_keys' => Array(
						'blt_title'
					) ,
					'blt_list' => Array(
						'blt_content' => __('Details', 'wp-easy-events') ,
						'blt_excerpt' => __('Excerpt', 'wp-easy-events') ,
					) ,
					'req_blt' => Array(
						'blt_title' => Array(
							'msg' => __('Title', 'wp-easy-events')
						) ,
					) ,
				) ,
				'emd_event_attendee' => Array(
					'label' => __('Attendees', 'wp-easy-events') ,
					'rewrite' => 'attendee',
					'archive_view' => 0,
					'sortable' => 0,
					'searchable' => 0,
					'unique_keys' => Array(
						'emd_attendee_ticket_id'
					) ,
				) ,
			);
			update_option($this->option_name . '_ent_list', $ent_list);
			$shc_list['app'] = 'WP Easy Events';
			$shc_list['has_gmap'] = 1;
			$shc_list['has_bs'] = 0;
			$shc_list['remove_vis'] = 0;
			$shc_list['forms']['event_attendee'] = Array(
				'name' => 'event_attendee',
				'type' => 'submit',
				'ent' => 'emd_event_attendee'
			);
			$shc_list['shcs']['wpee_event_grid'] = Array(
				"class_name" => "emd_wpe_event",
				"type" => "std",
				'page_title' => __('Event Grid', 'wp-easy-events') ,
			);
			$shc_list['integrations']['events_calendar'] = Array(
				'type' => 'integration',
				'app_dash' => 0,
				'shc_entities' => '',
				'page_title' => __('Events Calendar', 'wp-easy-events')
			);
			if (!empty($shc_list)) {
				update_option($this->option_name . '_shc_list', $shc_list);
			}
			$attr_list['emd_wpe_event']['emd_event_featured'] = Array(
				'label' => __('Featured', 'wp-easy-events') ,
				'display_type' => 'checkbox',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 0,
				'mid' => 'emd_wpe_event_info_emd_wpe_event_0',
				'type' => 'binary',
				'options' => array(
					1 => 1
				) ,
			);
			$attr_list['emd_wpe_event']['emd_event_registration_type'] = Array(
				'label' => __('Registration Type', 'wp-easy-events') ,
				'display_type' => 'radio',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_wpe_event_info_emd_wpe_event_0',
				'desc' => __('<a href="https://emdplugins.com/plugins/wp-easy-event-woocommerce-extension/?pk_campaign=wpee-pro&amp;pk_kwd=regtypelink" title="Buy WP Easy Event WooCommerce Extension Now">WooCommerce</a> and <a href="https://emdplugins.com/plugins/wp-easy-event-easy-digital-downloads-extension/?pk_campaign=wpee-pro&amp;pk_kwd=regtypelink" title="Buy WP Easy Event Easy Digital Downloads Extension Now">Easy Digital Downloads</a> types require corresponding extensions to work.', 'wp-easy-events') ,
				'type' => 'char',
				'options' => array(
					'none' => __('No registration', 'wp-easy-events') ,
					'rsvp' => __('RSVP required', 'wp-easy-events') ,
					'woo' => __('WooCommerce', 'wp-easy-events') ,
					'edd' => __('Easy Digital Downloads', 'wp-easy-events')
				) ,
				'std' => 'none',
			);
			$attr_list['emd_wpe_event']['emd_event_startdate'] = Array(
				'label' => __('Start Date', 'wp-easy-events') ,
				'display_type' => 'datetime',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 1,
				'mid' => 'emd_wpe_event_info_emd_wpe_event_0',
				'type' => 'datetime',
				'dformat' => array(
					'dateFormat' => 'mm-dd-yy',
					'timeFormat' => 'HH:mm'
				) ,
				'date_format' => 'm-d-Y H:i',
				'time_format' => 'hh:mm',
			);
			$attr_list['emd_wpe_event']['emd_event_enddate'] = Array(
				'label' => __('End Date', 'wp-easy-events') ,
				'display_type' => 'datetime',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 1,
				'mid' => 'emd_wpe_event_info_emd_wpe_event_0',
				'type' => 'datetime',
				'dformat' => array(
					'dateFormat' => 'mm-dd-yy',
					'timeFormat' => 'HH:mm'
				) ,
				'date_format' => 'm-d-Y H:i',
				'time_format' => 'hh:mm',
			);
			$attr_list['emd_wpe_event']['emd_event_external_url'] = Array(
				'label' => __('Website', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 0,
				'mid' => 'emd_wpe_event_info_emd_wpe_event_0',
				'type' => 'char',
				'url' => true,
			);
			$attr_list['emd_wpe_event']['emd_event_display_timezone'] = Array(
				'label' => __('Display timezone on event page', 'wp-easy-events') ,
				'display_type' => 'checkbox',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 0,
				'mid' => 'emd_wpe_event_info_emd_wpe_event_0',
				'type' => 'binary',
				'options' => array(
					1 => 1
				) ,
				'conditional' => Array(
					'attr_rules' => Array(
						'emd_event_timezone' => Array(
							'type' => 'select_advanced',
							'view' => 'show',
							'depend_check' => 'is',
							'depend_value' => true
						) ,
					) ,
					'start_hide_attr' => Array(
						'emd_event_timezone'
					) ,
				) ,
			);
			$attr_list['emd_wpe_event']['emd_event_timezone'] = Array(
				'label' => __('Timezone', 'wp-easy-events') ,
				'display_type' => 'select_advanced',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 0,
				'mid' => 'emd_wpe_event_info_emd_wpe_event_0',
				'type' => 'char',
				'options' => array(
					'' => __('Please Select', 'wp-easy-events') ,
					'UTC-12' => __('UTC-12', 'wp-easy-events') ,
					'UTC-115' => __('UTC-11:30', 'wp-easy-events') ,
					'UTC-11' => __('UTC-11', 'wp-easy-events') ,
					'UTC-105' => __('UTC-10:30', 'wp-easy-events') ,
					'UTC-10' => __('UTC-10', 'wp-easy-events') ,
					'UTC-95' => __('UTC-9:30', 'wp-easy-events') ,
					'UTC-9' => __('UTC-9', 'wp-easy-events') ,
					'UTC-85' => __('UTC-8:30', 'wp-easy-events') ,
					'UTC-8' => __('UTC-8', 'wp-easy-events') ,
					'UTC-75' => __('UTC-7:30', 'wp-easy-events') ,
					'UTC-7' => __('UTC-7', 'wp-easy-events') ,
					'UTC-65' => __('UTC-6:30', 'wp-easy-events') ,
					'UTC-6' => __('UTC-6', 'wp-easy-events') ,
					'UTC-55' => __('UTC-5:30', 'wp-easy-events') ,
					'UTC-5' => __('UTC-5', 'wp-easy-events') ,
					'UTC-45' => __('UTC-4:30', 'wp-easy-events') ,
					'UTC-4' => __('UTC-4', 'wp-easy-events') ,
					'UTC-35' => __('UTC-3:30', 'wp-easy-events') ,
					'UTC-3' => __('UTC-3', 'wp-easy-events') ,
					'UTC-25' => __('UTC-2:30', 'wp-easy-events') ,
					'UTC-2' => __('UTC-2', 'wp-easy-events') ,
					'UTC-15' => __('UTC-1:30', 'wp-easy-events') ,
					'UTC-1' => __('UTC-1', 'wp-easy-events') ,
					'UTC-05' => __('UTC-0:30', 'wp-easy-events') ,
					'UTC0' => __('UTC+0', 'wp-easy-events') ,
					'UTC05' => __('UTC+0:30', 'wp-easy-events') ,
					'UTC1' => __('UTC+1', 'wp-easy-events') ,
					'UTC15' => __('UTC+1:30', 'wp-easy-events') ,
					'UTC2' => __('UTC+2', 'wp-easy-events') ,
					'UTC25' => __('UTC+2:30', 'wp-easy-events') ,
					'UTC3' => __('UTC+3', 'wp-easy-events') ,
					'UTC35' => __('UTC+3:30', 'wp-easy-events') ,
					'UTC4' => __('UTC+4', 'wp-easy-events') ,
					'UTC45' => __('UTC+4:30', 'wp-easy-events') ,
					'UTC5' => __('UTC+5', 'wp-easy-events') ,
					'UTC55' => __('UTC+5:30', 'wp-easy-events') ,
					'UTC575' => __('UTC+5:45', 'wp-easy-events') ,
					'UTC6' => __('UTC+6', 'wp-easy-events') ,
					'UTC65' => __('UTC+6:30', 'wp-easy-events') ,
					'UTC7' => __('UTC+7', 'wp-easy-events') ,
					'UTC75' => __('UTC+7:30', 'wp-easy-events') ,
					'UTC8' => __('UTC+8', 'wp-easy-events') ,
					'UTC85' => __('UTC+8:30', 'wp-easy-events') ,
					'UTC875' => __('UTC+8:45', 'wp-easy-events') ,
					'UTC9' => __('UTC+9', 'wp-easy-events') ,
					'UTC95' => __('UTC+9:30', 'wp-easy-events') ,
					'UTC10' => __('UTC+10', 'wp-easy-events') ,
					'UTC105' => __('UTC+10:30', 'wp-easy-events') ,
					'UTC11' => __('UTC+11', 'wp-easy-events') ,
					'UTC115' => __('UTC+11:30', 'wp-easy-events') ,
					'UTC12' => __('UTC+12', 'wp-easy-events') ,
					'UTC1275' => __('UTC+12:45', 'wp-easy-events') ,
					'UTC13' => __('UTC+13', 'wp-easy-events') ,
					'UTC1375' => __('UTC+13:45', 'wp-easy-events') ,
					'UTC14' => __('UTC+14', 'wp-easy-events')
				) ,
				'std' => 'UTC0',
			);
			$attr_list['emd_wpe_event']['emd_event_cost'] = Array(
				'label' => __('Cost', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 1,
				'mid' => 'emd_wpe_event_info_emd_wpe_event_0',
				'type' => 'char',
			);
			$attr_list['emd_event_organizer']['emd_eo_email'] = Array(
				'label' => __('Email', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_organizer_info_emd_event_organizer_0',
				'type' => 'char',
				'email' => true,
			);
			$attr_list['emd_event_organizer']['emd_eo_phone'] = Array(
				'label' => __('Phone', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_organizer_info_emd_event_organizer_0',
				'type' => 'char',
			);
			$attr_list['emd_event_organizer']['emd_eo_website'] = Array(
				'label' => __('Website', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_organizer_info_emd_event_organizer_0',
				'type' => 'char',
				'url' => true,
			);
			$attr_list['emd_event_venues']['emd_venue_address'] = Array(
				'label' => __('Address', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 1,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 1,
				'mid' => 'emd_event_venues_info_emd_event_venues_0',
				'type' => 'char',
				'data-cell' => 'A17',
			);
			$attr_list['emd_event_venues']['emd_venue_city'] = Array(
				'label' => __('City', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 1,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_venues_info_emd_event_venues_0',
				'type' => 'char',
				'data-cell' => 'A18',
			);
			$attr_list['emd_event_venues']['emd_venue_state'] = Array(
				'label' => __('State', 'wp-easy-events') ,
				'display_type' => 'select_advanced',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_venues_info_emd_event_venues_0',
				'type' => 'char',
				'options' => array(
					'' => __('Please Select', 'wp-easy-events') ,
					'AL' => __('Alabama', 'wp-easy-events') ,
					'AK' => __('Alaska', 'wp-easy-events') ,
					'AZ' => __('Arizona', 'wp-easy-events') ,
					'AR' => __('Arkansas', 'wp-easy-events') ,
					'CA' => __('California', 'wp-easy-events') ,
					'CO' => __('Colorado', 'wp-easy-events') ,
					'CT' => __('Connecticut', 'wp-easy-events') ,
					'DE' => __('Delaware', 'wp-easy-events') ,
					'DC' => __('District Of Columbia', 'wp-easy-events') ,
					'FL' => __('Florida', 'wp-easy-events') ,
					'GA' => __('Georgia', 'wp-easy-events') ,
					'HI' => __('Hawaii', 'wp-easy-events') ,
					'ID' => __('Idaho', 'wp-easy-events') ,
					'IL' => __('Illinois', 'wp-easy-events') ,
					'IN' => __('Indiana', 'wp-easy-events') ,
					'IA' => __('Iowa', 'wp-easy-events') ,
					'KS' => __('Kansas', 'wp-easy-events') ,
					'KY' => __('Kentucky', 'wp-easy-events') ,
					'LA' => __('Louisiana', 'wp-easy-events') ,
					'ME' => __('Maine', 'wp-easy-events') ,
					'MD' => __('Maryland', 'wp-easy-events') ,
					'MA' => __('Massachusetts', 'wp-easy-events') ,
					'MI' => __('Michigan', 'wp-easy-events') ,
					'MN' => __('Minnesota', 'wp-easy-events') ,
					'MS' => __('Mississippi', 'wp-easy-events') ,
					'MO' => __('Missouri', 'wp-easy-events') ,
					'MT' => __('Montana', 'wp-easy-events') ,
					'NE' => __('Nebraska', 'wp-easy-events') ,
					'NV' => __('Nevada', 'wp-easy-events') ,
					'NH' => __('New Hampshire', 'wp-easy-events') ,
					'NJ' => __('New Jersey', 'wp-easy-events') ,
					'NM' => __('New Mexico', 'wp-easy-events') ,
					'NY' => __('New York', 'wp-easy-events') ,
					'NC' => __('North Carolina', 'wp-easy-events') ,
					'ND' => __('North Dakota', 'wp-easy-events') ,
					'OH' => __('Ohio', 'wp-easy-events') ,
					'OK' => __('Oklahoma', 'wp-easy-events') ,
					'OR' => __('Oregon', 'wp-easy-events') ,
					'PA' => __('Pennsylvania', 'wp-easy-events') ,
					'RI' => __('Rhode Island', 'wp-easy-events') ,
					'SC' => __('South Carolina', 'wp-easy-events') ,
					'SD' => __('South Dakota', 'wp-easy-events') ,
					'TN' => __('Tennessee', 'wp-easy-events') ,
					'TX' => __('Texas', 'wp-easy-events') ,
					'UT' => __('Utah', 'wp-easy-events') ,
					'VT' => __('Vermont', 'wp-easy-events') ,
					'VA' => __('Virginia', 'wp-easy-events') ,
					'WA' => __('Washington', 'wp-easy-events') ,
					'WV' => __('West Virginia', 'wp-easy-events') ,
					'WI' => __('Wisconsin', 'wp-easy-events') ,
					'WY' => __('Wyoming', 'wp-easy-events') ,
					'AS' => __('American Samoa', 'wp-easy-events') ,
					'GU' => __('Guam', 'wp-easy-events') ,
					'MP' => __('Northern Mariana Islands', 'wp-easy-events') ,
					'PR' => __('Puerto Rico', 'wp-easy-events') ,
					'UM' => __('United States Minor Outlying Islands', 'wp-easy-events') ,
					'VI' => __('Virgin Islands', 'wp-easy-events') ,
					'AA' => __('Armed Forces Americas', 'wp-easy-events') ,
					'AP' => __('Armed Forces Pacific', 'wp-easy-events') ,
					'AE' => __('Armed Forces Others', 'wp-easy-events')
				) ,
				'data-cell' => 'A19',
			);
			$attr_list['emd_event_venues']['emd_venue_postcode'] = Array(
				'label' => __('Postal Code', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_venues_info_emd_event_venues_0',
				'type' => 'char',
				'data-cell' => 'A20',
			);
			$attr_list['emd_event_venues']['emd_venue_country'] = Array(
				'label' => __('Country', 'wp-easy-events') ,
				'display_type' => 'select_advanced',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 0,
				'mid' => 'emd_event_venues_info_emd_event_venues_0',
				'type' => 'char',
				'options' => array(
					'' => __('Please Select', 'wp-easy-events') ,
					'AFG' => __('Afghanistan', 'wp-easy-events') ,
					'ALA' => __('Åland Islands', 'wp-easy-events') ,
					'ALB' => __('Albania', 'wp-easy-events') ,
					'DZA' => __('Algeria', 'wp-easy-events') ,
					'ASM' => __('American Samoa', 'wp-easy-events') ,
					'AND' => __('Andorra', 'wp-easy-events') ,
					'AGO' => __('Angola', 'wp-easy-events') ,
					'AIA' => __('Anguilla', 'wp-easy-events') ,
					'ATA' => __('Antarctica', 'wp-easy-events') ,
					'ATG' => __('Antigua and Barbuda', 'wp-easy-events') ,
					'ARG' => __('Argentina', 'wp-easy-events') ,
					'ARM' => __('Armenia', 'wp-easy-events') ,
					'ABW' => __('Aruba', 'wp-easy-events') ,
					'AUS' => __('Australia', 'wp-easy-events') ,
					'AUT' => __('Austria', 'wp-easy-events') ,
					'AZE' => __('Azerbaijan', 'wp-easy-events') ,
					'BHS' => __('Bahamas', 'wp-easy-events') ,
					'BHR' => __('Bahrain', 'wp-easy-events') ,
					'BGD' => __('Bangladesh', 'wp-easy-events') ,
					'BRB' => __('Barbados', 'wp-easy-events') ,
					'BLR' => __('Belarus', 'wp-easy-events') ,
					'BEL' => __('Belgium', 'wp-easy-events') ,
					'BLZ' => __('Belize', 'wp-easy-events') ,
					'BEN' => __('Benin', 'wp-easy-events') ,
					'BMU' => __('Bermuda', 'wp-easy-events') ,
					'BTN' => __('Bhutan', 'wp-easy-events') ,
					'BOL' => __('Bolivia, Plurinational State of', 'wp-easy-events') ,
					'BES' => __('Bonaire, Sint Eustatius and Saba', 'wp-easy-events') ,
					'BIH' => __('Bosnia and Herzegovina', 'wp-easy-events') ,
					'BWA' => __('Botswana', 'wp-easy-events') ,
					'BVT' => __('Bouvet Island', 'wp-easy-events') ,
					'BRA' => __('Brazil', 'wp-easy-events') ,
					'IOT' => __('British Indian Ocean Territory', 'wp-easy-events') ,
					'BRN' => __('Brunei Darussalam', 'wp-easy-events') ,
					'BGR' => __('Bulgaria', 'wp-easy-events') ,
					'BFA' => __('Burkina Faso', 'wp-easy-events') ,
					'BDI' => __('Burundi', 'wp-easy-events') ,
					'KHM' => __('Cambodia', 'wp-easy-events') ,
					'CMR' => __('Cameroon', 'wp-easy-events') ,
					'CAN' => __('Canada', 'wp-easy-events') ,
					'CPV' => __('Cape Verde', 'wp-easy-events') ,
					'CYM' => __('Cayman Islands', 'wp-easy-events') ,
					'CAF' => __('Central African Republic', 'wp-easy-events') ,
					'TCD' => __('Chad', 'wp-easy-events') ,
					'CHL' => __('Chile', 'wp-easy-events') ,
					'CHN' => __('China', 'wp-easy-events') ,
					'CXR' => __('Christmas Island', 'wp-easy-events') ,
					'CCK' => __('Cocos (Keeling) Islands', 'wp-easy-events') ,
					'COL' => __('Colombia', 'wp-easy-events') ,
					'COM' => __('Comoros', 'wp-easy-events') ,
					'COG' => __('Congo', 'wp-easy-events') ,
					'COD' => __('Congo, the Democratic Republic of the', 'wp-easy-events') ,
					'COK' => __('Cook Islands', 'wp-easy-events') ,
					'CRI' => __('Costa Rica', 'wp-easy-events') ,
					'CIV' => __('Côte d\'Ivoire', 'wp-easy-events') ,
					'HRV' => __('Croatia', 'wp-easy-events') ,
					'CUB' => __('Cuba', 'wp-easy-events') ,
					'CUW' => __('Curaçao', 'wp-easy-events') ,
					'CYP' => __('Cyprus', 'wp-easy-events') ,
					'CZE' => __('Czech Republic', 'wp-easy-events') ,
					'DNK' => __('Denmark', 'wp-easy-events') ,
					'DJI' => __('Djibouti', 'wp-easy-events') ,
					'DMA' => __('Dominica', 'wp-easy-events') ,
					'DOM' => __('Dominican Republic', 'wp-easy-events') ,
					'ECU' => __('Ecuador', 'wp-easy-events') ,
					'EGY' => __('Egypt', 'wp-easy-events') ,
					'SLV' => __('El Salvador', 'wp-easy-events') ,
					'GNQ' => __('Equatorial Guinea', 'wp-easy-events') ,
					'ERI' => __('Eritrea', 'wp-easy-events') ,
					'EST' => __('Estonia', 'wp-easy-events') ,
					'ETH' => __('Ethiopia', 'wp-easy-events') ,
					'FLK' => __('Falkland Islands (Malvinas)', 'wp-easy-events') ,
					'FRO' => __('Faroe Islands', 'wp-easy-events') ,
					'FJI' => __('Fiji', 'wp-easy-events') ,
					'FIN' => __('Finland', 'wp-easy-events') ,
					'FRA' => __('France', 'wp-easy-events') ,
					'GUF' => __('French Guiana', 'wp-easy-events') ,
					'PYF' => __('French Polynesia', 'wp-easy-events') ,
					'ATF' => __('French Southern Territories', 'wp-easy-events') ,
					'GAB' => __('Gabon', 'wp-easy-events') ,
					'GMB' => __('Gambia', 'wp-easy-events') ,
					'GEO' => __('Georgia', 'wp-easy-events') ,
					'DEU' => __('Germany', 'wp-easy-events') ,
					'GHA' => __('Ghana', 'wp-easy-events') ,
					'GIB' => __('Gibraltar', 'wp-easy-events') ,
					'GRC' => __('Greece', 'wp-easy-events') ,
					'GRL' => __('Greenland', 'wp-easy-events') ,
					'GRD' => __('Grenada', 'wp-easy-events') ,
					'GLP' => __('Guadeloupe', 'wp-easy-events') ,
					'GUM' => __('Guam', 'wp-easy-events') ,
					'GTM' => __('Guatemala', 'wp-easy-events') ,
					'GGY' => __('Guernsey', 'wp-easy-events') ,
					'GIN' => __('Guinea', 'wp-easy-events') ,
					'GNB' => __('Guinea-Bissau', 'wp-easy-events') ,
					'GUY' => __('Guyana', 'wp-easy-events') ,
					'HTI' => __('Haiti', 'wp-easy-events') ,
					'HMD' => __('Heard Island and McDonald Islands', 'wp-easy-events') ,
					'VAT' => __('Holy See (Vatican City State)', 'wp-easy-events') ,
					'HND' => __('Honduras', 'wp-easy-events') ,
					'HKG' => __('Hong Kong', 'wp-easy-events') ,
					'HUN' => __('Hungary', 'wp-easy-events') ,
					'ISL' => __('Iceland', 'wp-easy-events') ,
					'IND' => __('India', 'wp-easy-events') ,
					'IDN' => __('Indonesia', 'wp-easy-events') ,
					'IRN' => __('Iran, Islamic Republic of', 'wp-easy-events') ,
					'IRQ' => __('Iraq', 'wp-easy-events') ,
					'IRL' => __('Ireland', 'wp-easy-events') ,
					'IMN' => __('Isle of Man', 'wp-easy-events') ,
					'ISR' => __('Israel', 'wp-easy-events') ,
					'ITA' => __('Italy', 'wp-easy-events') ,
					'JAM' => __('Jamaica', 'wp-easy-events') ,
					'JPN' => __('Japan', 'wp-easy-events') ,
					'JEY' => __('Jersey', 'wp-easy-events') ,
					'JOR' => __('Jordan', 'wp-easy-events') ,
					'KAZ' => __('Kazakhstan', 'wp-easy-events') ,
					'KEN' => __('Kenya', 'wp-easy-events') ,
					'KIR' => __('Kiribati', 'wp-easy-events') ,
					'PRK' => __('Korea, Democratic People\'s Republic of', 'wp-easy-events') ,
					'KOR' => __('Korea, Republic of', 'wp-easy-events') ,
					'KWT' => __('Kuwait', 'wp-easy-events') ,
					'KGZ' => __('Kyrgyzstan', 'wp-easy-events') ,
					'LAO' => __('Lao People\'s Democratic Republic', 'wp-easy-events') ,
					'LVA' => __('Latvia', 'wp-easy-events') ,
					'LBN' => __('Lebanon', 'wp-easy-events') ,
					'LSO' => __('Lesotho', 'wp-easy-events') ,
					'LBR' => __('Liberia', 'wp-easy-events') ,
					'LBY' => __('Libya', 'wp-easy-events') ,
					'LIE' => __('Liechtenstein', 'wp-easy-events') ,
					'LTU' => __('Lithuania', 'wp-easy-events') ,
					'LUX' => __('Luxembourg', 'wp-easy-events') ,
					'MAC' => __('Macao', 'wp-easy-events') ,
					'MKD' => __('Macedonia, the former Yugoslav Republic of', 'wp-easy-events') ,
					'MDG' => __('Madagascar', 'wp-easy-events') ,
					'MWI' => __('Malawi', 'wp-easy-events') ,
					'MYS' => __('Malaysia', 'wp-easy-events') ,
					'MDV' => __('Maldives', 'wp-easy-events') ,
					'MLI' => __('Mali', 'wp-easy-events') ,
					'MLT' => __('Malta', 'wp-easy-events') ,
					'MHL' => __('Marshall Islands', 'wp-easy-events') ,
					'MTQ' => __('Martinique', 'wp-easy-events') ,
					'MRT' => __('Mauritania', 'wp-easy-events') ,
					'MUS' => __('Mauritius', 'wp-easy-events') ,
					'MYT' => __('Mayotte', 'wp-easy-events') ,
					'MEX' => __('Mexico', 'wp-easy-events') ,
					'FSM' => __('Micronesia, Federated States of', 'wp-easy-events') ,
					'MDA' => __('Moldova, Republic of', 'wp-easy-events') ,
					'MCO' => __('Monaco', 'wp-easy-events') ,
					'MNG' => __('Mongolia', 'wp-easy-events') ,
					'MNE' => __('Montenegro', 'wp-easy-events') ,
					'MSR' => __('Montserrat', 'wp-easy-events') ,
					'MAR' => __('Morocco', 'wp-easy-events') ,
					'MOZ' => __('Mozambique', 'wp-easy-events') ,
					'MMR' => __('Myanmar', 'wp-easy-events') ,
					'NAM' => __('Namibia', 'wp-easy-events') ,
					'NRU' => __('Nauru', 'wp-easy-events') ,
					'NPL' => __('Nepal', 'wp-easy-events') ,
					'NLD' => __('Netherlands', 'wp-easy-events') ,
					'NCL' => __('New Caledonia', 'wp-easy-events') ,
					'NZL' => __('New Zealand', 'wp-easy-events') ,
					'NIC' => __('Nicaragua', 'wp-easy-events') ,
					'NER' => __('Niger', 'wp-easy-events') ,
					'NGA' => __('Nigeria', 'wp-easy-events') ,
					'NIU' => __('Niue', 'wp-easy-events') ,
					'NFK' => __('Norfolk Island', 'wp-easy-events') ,
					'MNP' => __('Northern Mariana Islands', 'wp-easy-events') ,
					'NOR' => __('Norway', 'wp-easy-events') ,
					'OMN' => __('Oman', 'wp-easy-events') ,
					'PAK' => __('Pakistan', 'wp-easy-events') ,
					'PLW' => __('Palau', 'wp-easy-events') ,
					'PSE' => __('Palestinian Territory, Occupied', 'wp-easy-events') ,
					'PAN' => __('Panama', 'wp-easy-events') ,
					'PNG' => __('Papua New Guinea', 'wp-easy-events') ,
					'PRY' => __('Paraguay', 'wp-easy-events') ,
					'PER' => __('Peru', 'wp-easy-events') ,
					'PHL' => __('Philippines', 'wp-easy-events') ,
					'PCN' => __('Pitcairn', 'wp-easy-events') ,
					'POL' => __('Poland', 'wp-easy-events') ,
					'PRT' => __('Portugal', 'wp-easy-events') ,
					'PRI' => __('Puerto Rico', 'wp-easy-events') ,
					'QAT' => __('Qatar', 'wp-easy-events') ,
					'REU' => __('Réunion', 'wp-easy-events') ,
					'ROU' => __('Romania', 'wp-easy-events') ,
					'RUS' => __('Russian Federation', 'wp-easy-events') ,
					'RWA' => __('Rwanda', 'wp-easy-events') ,
					'BLM' => __('Saint Barthélemy', 'wp-easy-events') ,
					'SHN' => __('Saint Helena, Ascension and Tristan da Cunha', 'wp-easy-events') ,
					'KNA' => __('Saint Kitts and Nevis', 'wp-easy-events') ,
					'LCA' => __('Saint Lucia', 'wp-easy-events') ,
					'MAF' => __('Saint Martin (French part)', 'wp-easy-events') ,
					'SPM' => __('Saint Pierre and Miquelon', 'wp-easy-events') ,
					'VCT' => __('Saint Vincent and the Grenadines', 'wp-easy-events') ,
					'WSM' => __('Samoa', 'wp-easy-events') ,
					'SMR' => __('San Marino', 'wp-easy-events') ,
					'STP' => __('Sao Tome and Principe', 'wp-easy-events') ,
					'SAU' => __('Saudi Arabia', 'wp-easy-events') ,
					'SEN' => __('Senegal', 'wp-easy-events') ,
					'SRB' => __('Serbia', 'wp-easy-events') ,
					'SYC' => __('Seychelles', 'wp-easy-events') ,
					'SLE' => __('Sierra Leone', 'wp-easy-events') ,
					'SGP' => __('Singapore', 'wp-easy-events') ,
					'SXM' => __('Sint Maarten (Dutch part)', 'wp-easy-events') ,
					'SVK' => __('Slovakia', 'wp-easy-events') ,
					'SVN' => __('Slovenia', 'wp-easy-events') ,
					'SLB' => __('Solomon Islands', 'wp-easy-events') ,
					'SOM' => __('Somalia', 'wp-easy-events') ,
					'ZAF' => __('South Africa', 'wp-easy-events') ,
					'SGS' => __('South Georgia and the South Sandwich Islands', 'wp-easy-events') ,
					'SSD' => __('South Sudan', 'wp-easy-events') ,
					'ESP' => __('Spain', 'wp-easy-events') ,
					'LKA' => __('Sri Lanka', 'wp-easy-events') ,
					'SDN' => __('Sudan', 'wp-easy-events') ,
					'SUR' => __('Suriname', 'wp-easy-events') ,
					'SJM' => __('Svalbard and Jan Mayen', 'wp-easy-events') ,
					'SWZ' => __('Swaziland', 'wp-easy-events') ,
					'SWE' => __('Sweden', 'wp-easy-events') ,
					'CHE' => __('Switzerland', 'wp-easy-events') ,
					'SYR' => __('Syrian Arab Republic', 'wp-easy-events') ,
					'TWN' => __('Taiwan, Province of China', 'wp-easy-events') ,
					'TJK' => __('Tajikistan', 'wp-easy-events') ,
					'TZA' => __('Tanzania, United Republic of', 'wp-easy-events') ,
					'THA' => __('Thailand', 'wp-easy-events') ,
					'TLS' => __('Timor-Leste', 'wp-easy-events') ,
					'TGO' => __('Togo', 'wp-easy-events') ,
					'TKL' => __('Tokelau', 'wp-easy-events') ,
					'TON' => __('Tonga', 'wp-easy-events') ,
					'TTO' => __('Trinidad and Tobago', 'wp-easy-events') ,
					'TUN' => __('Tunisia', 'wp-easy-events') ,
					'TUR' => __('Turkey', 'wp-easy-events') ,
					'TKM' => __('Turkmenistan', 'wp-easy-events') ,
					'TCA' => __('Turks and Caicos Islands', 'wp-easy-events') ,
					'TUV' => __('Tuvalu', 'wp-easy-events') ,
					'UGA' => __('Uganda', 'wp-easy-events') ,
					'UKR' => __('Ukraine', 'wp-easy-events') ,
					'ARE' => __('United Arab Emirates', 'wp-easy-events') ,
					'GBR' => __('United Kingdom', 'wp-easy-events') ,
					'USA' => __('United States', 'wp-easy-events') ,
					'UMI' => __('United States Minor Outlying Islands', 'wp-easy-events') ,
					'URY' => __('Uruguay', 'wp-easy-events') ,
					'UZB' => __('Uzbekistan', 'wp-easy-events') ,
					'VUT' => __('Vanuatu', 'wp-easy-events') ,
					'VEN' => __('Venezuela, Bolivarian Republic of', 'wp-easy-events') ,
					'VNM' => __('Viet Nam', 'wp-easy-events') ,
					'VGB' => __('Virgin Islands, British', 'wp-easy-events') ,
					'VIR' => __('Virgin Islands, U.S.', 'wp-easy-events') ,
					'WLF' => __('Wallis and Futuna', 'wp-easy-events') ,
					'ESH' => __('Western Sahara', 'wp-easy-events') ,
					'YEM' => __('Yemen', 'wp-easy-events') ,
					'ZMB' => __('Zambia', 'wp-easy-events') ,
					'ZWE' => __('Zimbabwe', 'wp-easy-events')
				) ,
				'conditional' => Array(
					'attr_rules' => Array(
						'emd_venue_state' => Array(
							'type' => 'select_advanced',
							'view' => 'show',
							'depend_check' => 'is',
							'depend_value' => 'USA',
							'comb_type' => 'any',
							'comb' => Array(
								Array(
									'key' => 'emd_venue_country',
									'depend_check' => 'is',
									'depend_value' => 'UMI',
									'type' => 'select'
								) ,
							)
						) ,
					) ,
					'start_hide_attr' => Array(
						'emd_venue_state'
					) ,
				) ,
				'data-cell' => 'A21',
			);
			$attr_list['emd_event_venues']['emd_venue_fulladdress'] = Array(
				'label' => __('Full Address', 'wp-easy-events') ,
				'display_type' => 'calculated',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 0,
				'mid' => 'emd_event_venues_info_emd_event_venues_0',
				'type' => 'char',
				'data-cell' => 'F22',
				'data-formula' => 'SERVER("EMD_VENUE_FULLADDRESS",A17,A18,A19,A20,A21)',
			);
			$attr_list['emd_event_venues']['emd_venue_map'] = Array(
				'label' => __('Map', 'wp-easy-events') ,
				'display_type' => 'map',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 0,
				'mid' => 'emd_event_venues_info_emd_event_venues_0',
				'address_field' => 'emd_venue_fulladdress',
				'type' => 'char',
			);
			$attr_list['emd_event_attendee']['emd_attendee_ticket_id'] = Array(
				'label' => __('Ticket ID', 'wp-easy-events') ,
				'display_type' => 'hidden',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'desc' => __('Unique identifier for every ticket', 'wp-easy-events') ,
				'type' => 'char',
				'hidden_func' => 'unique_id',
				'uniqueAttr' => true,
			);
			$attr_list['emd_event_attendee']['emd_attendee_first_name'] = Array(
				'label' => __('First Name', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'type' => 'char',
				'user_map' => 'user_firstname',
			);
			$attr_list['emd_event_attendee']['emd_attendee_last_name'] = Array(
				'label' => __('Last Name', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'type' => 'char',
				'user_map' => 'user_lastname',
			);
			$attr_list['emd_event_attendee']['emd_attendee_email'] = Array(
				'label' => __('Email', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'type' => 'char',
				'email' => true,
			);
			$attr_list['emd_event_attendee']['emd_attendee_full_name'] = Array(
				'label' => __('Full name', 'wp-easy-events') ,
				'display_type' => 'hidden',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 0,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'type' => 'char',
				'hidden_func' => 'concat',
				'concat_string' => '!#ent_attendee_first_name# !#ent_attendee_last_name#',
			);
			$attr_list['emd_event_attendee']['emd_attendee_quantity'] = Array(
				'label' => __('Quantity', 'wp-easy-events') ,
				'display_type' => 'text',
				'required' => 1,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'type' => 'signed',
				'std' => '1',
				'min' => 1,
				'integer' => true,
			);
			$attr_list['emd_event_attendee']['emd_attendee_checkin'] = Array(
				'label' => __('Check-in', 'wp-easy-events') ,
				'display_type' => 'checkbox',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'type' => 'binary',
				'options' => array(
					1 => 1
				) ,
			);
			$attr_list['emd_event_attendee']['wpas_form_name'] = Array(
				'label' => __('Form Name', 'wp-easy-events') ,
				'display_type' => 'hidden',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 0,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'type' => 'char',
				'options' => array() ,
				'no_update' => 1,
				'std' => 'admin',
			);
			$attr_list['emd_event_attendee']['wpas_form_submitted_by'] = Array(
				'label' => __('Form Submitted By', 'wp-easy-events') ,
				'display_type' => 'hidden',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 0,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'type' => 'char',
				'options' => array() ,
				'hidden_func' => 'user_login',
				'no_update' => 1,
			);
			$attr_list['emd_event_attendee']['wpas_form_submitted_ip'] = Array(
				'label' => __('Form Submitted IP', 'wp-easy-events') ,
				'display_type' => 'hidden',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 0,
				'mid' => 'emd_event_attendee_info_emd_event_attendee_0',
				'type' => 'char',
				'options' => array() ,
				'hidden_func' => 'user_ip',
				'no_update' => 1,
			);
			$attr_list = apply_filters('emd_ext_attr_list', $attr_list, $this->option_name);
			if (!empty($attr_list)) {
				update_option($this->option_name . '_attr_list', $attr_list);
			}
			$glob_list['glb_event_location'] = Array(
				'label' => __('Hide Event Map Icon', 'wp-easy-events') ,
				'type' => 'checkbox',
				'desc' => 'Hides map icon in event pages when checked.',
				'values' => '',
				'dflt' => '',
				'required' => 0,
			);
			if (!empty($glob_list)) {
				update_option($this->option_name . '_glob_init_list', $glob_list);
				if (get_option($this->option_name . '_glob_list') === false) {
					update_option($this->option_name . '_glob_list', $glob_list);
				}
			}
			$glob_forms_list['event_attendee']['captcha'] = 'never-show';
			$glob_forms_list['event_attendee']['noaccess_msg'] = 'You are not allowed to access to this area. Please contact the site administrator.';
			$glob_forms_list['event_attendee']['error_msg'] = 'There has been an error when processing your registration. Please contact the site administrator.';
			$glob_forms_list['event_attendee']['success_msg'] = 'Thanks for your registration.';
			$glob_forms_list['event_attendee']['login_reg'] = 'none';
			$glob_forms_list['event_attendee']['csrf'] = 1;
			$glob_forms_list['event_attendee']['rel_event_attendee'] = Array(
				'show' => 1,
				'row' => 1,
				'req' => 0,
				'size' => 12,
			);
			$glob_forms_list['event_attendee']['emd_attendee_first_name'] = Array(
				'show' => 1,
				'row' => 2,
				'req' => 0,
				'size' => 12,
			);
			$glob_forms_list['event_attendee']['emd_attendee_last_name'] = Array(
				'show' => 1,
				'row' => 3,
				'req' => 0,
				'size' => 12,
			);
			$glob_forms_list['event_attendee']['emd_attendee_quantity'] = Array(
				'show' => 1,
				'row' => 4,
				'req' => 1,
				'size' => 4,
			);
			$glob_forms_list['event_attendee']['emd_attendee_email'] = Array(
				'show' => 1,
				'row' => 5,
				'req' => 0,
				'size' => 12,
			);
			if (!empty($glob_forms_list)) {
				update_option($this->option_name . '_glob_forms_init_list', $glob_forms_list);
				if (get_option($this->option_name . '_glob_forms_list') === false) {
					update_option($this->option_name . '_glob_forms_list', $glob_forms_list);
				}
			}
			$tax_list['emd_wpe_event']['emd_event_cat'] = Array(
				'archive_view' => 0,
				'label' => __('Categories', 'wp-easy-events') ,
				'default' => '',
				'type' => 'single',
				'hier' => 0,
				'sortable' => 0,
				'list_visible' => 0,
				'required' => 0,
				'srequired' => 0,
				'rewrite' => 'emd_event_cat'
			);
			$tax_list['emd_wpe_event']['emd_event_tag'] = Array(
				'archive_view' => 0,
				'label' => __('Tags', 'wp-easy-events') ,
				'default' => '',
				'type' => 'multi',
				'hier' => 0,
				'sortable' => 0,
				'list_visible' => 0,
				'required' => 0,
				'srequired' => 0,
				'rewrite' => 'emd_event_tag'
			);
			if (!empty($tax_list)) {
				update_option($this->option_name . '_tax_list', $tax_list);
			}
			$rel_list['rel_event_organizer'] = Array(
				'from' => 'emd_wpe_event',
				'to' => 'emd_event_organizer',
				'type' => 'many-to-many',
				'from_title' => __('Organizers', 'wp-easy-events') ,
				'to_title' => __('Events', 'wp-easy-events') ,
				'required' => 0,
				'srequired' => 0,
				'show' => 'any',
				'filter' => ''
			);
			$rel_list['rel_event_venue'] = Array(
				'from' => 'emd_event_venues',
				'to' => 'emd_wpe_event',
				'type' => 'one-to-many',
				'from_title' => __('Events', 'wp-easy-events') ,
				'to_title' => __('Venues', 'wp-easy-events') ,
				'required' => 1,
				'srequired' => 0,
				'show' => 'any',
				'filter' => ''
			);
			$rel_list['rel_event_attendee'] = Array(
				'from' => 'emd_wpe_event',
				'to' => 'emd_event_attendee',
				'type' => 'one-to-many',
				'from_title' => __('Attendees', 'wp-easy-events') ,
				'to_title' => __('Events', 'wp-easy-events') ,
				'required' => 0,
				'srequired' => 0,
				'show' => 'to',
				'filter' => ''
			);
			if (!empty($rel_list)) {
				update_option($this->option_name . '_rel_list', $rel_list);
			}
			$emd_activated_plugins = get_option('emd_activated_plugins');
			if (!$emd_activated_plugins) {
				update_option('emd_activated_plugins', Array(
					'wp-easy-events'
				));
			} elseif (!in_array('wp-easy-events', $emd_activated_plugins)) {
				array_push($emd_activated_plugins, 'wp-easy-events');
				update_option('emd_activated_plugins', $emd_activated_plugins);
			}
			//conf parameters for incoming email
			//conf parameters for inline entity
			//conf parameters for calendar
			$has_calendar = Array(
				'event_calendar' => Array(
					'label' => 'Event Calendar',
					'lite' => 1,
					'entity' => 'emd_wpe_event',
					'start' => 'emd_event_startdate',
					'end' => 'emd_event_enddate',
					'title' => 'blt_title'
				)
			);
			update_option($this->option_name . '_has_calendar', $has_calendar);
			//conf parameters for woocommerce
			$has_woocommerce = Array(
				'woo_event_tickets' => Array(
					'label' => 'Woo Event Tickets',
					'entity' => 'emd_wpe_event',
					'txn' => '',
					'order_rel' => 1,
					'product_rel' => 1,
					'myaccount_before' => '',
					'myaccount_after' => '',
					'smanager_caps' => Array(
						'edit_emd_wpe_events',
						'delete_emd_wpe_events',
						'edit_others_emd_wpe_events',
						'publish_emd_wpe_events',
						'read_private_emd_wpe_events',
						'delete_private_emd_wpe_events',
						'delete_published_emd_wpe_events',
						'delete_others_emd_wpe_events',
						'edit_private_emd_wpe_events',
						'edit_published_emd_wpe_events',
						'edit_emd_event_attendees',
						'delete_emd_event_attendees',
						'edit_others_emd_event_attendees',
						'publish_emd_event_attendees',
						'read_private_emd_event_attendees',
						'delete_private_emd_event_attendees',
						'delete_published_emd_event_attendees',
						'delete_others_emd_event_attendees',
						'edit_private_emd_event_attendees',
						'edit_published_emd_event_attendees',
						'assign_emd_event_cat',
						'assign_emd_event_tag'
					) ,
					'customer_caps' => Array() ,
					'order_term' => '',
					'order_type' => 'many-to-many',
					'order_from' => 'Events',
					'order_to' => 'Woo Orders',
					'order_box' => 'from',
					'order_layout' => 'None',
					'order_header' => '',
					'order_footer' => '',
					'recent_orders_label' => '',
					'recent_orders_url' => '',
					'product_term' => '',
					'product_type' => 'one-to-one',
					'product_from' => 'Events',
					'product_to' => 'Woo Tickets',
					'product_box' => 'any',
					'product_layout' => '!#woo_product_add_to_cart#',
					'product_header' => '',
					'product_footer' => ''
				)
			);
			update_option($this->option_name . '_has_woocommerce', $has_woocommerce);
			//conf parameters for woocommerce
			$has_edd = Array(
				'edd_event_tickets' => Array(
					'label' => 'Edd Event Tickets',
					'entity' => 'emd_wpe_event',
					'txn' => '',
					'order_rel' => 1,
					'product_rel' => 1,
					'myaccount_before' => '',
					'myaccount_after' => '',
					'smanager_caps' => Array(
						'edit_emd_wpe_events',
						'delete_emd_wpe_events',
						'edit_others_emd_wpe_events',
						'publish_emd_wpe_events',
						'read_private_emd_wpe_events',
						'delete_private_emd_wpe_events',
						'delete_published_emd_wpe_events',
						'delete_others_emd_wpe_events',
						'edit_private_emd_wpe_events',
						'edit_published_emd_wpe_events',
						'edit_emd_event_attendees',
						'delete_emd_event_attendees',
						'edit_others_emd_event_attendees',
						'publish_emd_event_attendees',
						'read_private_emd_event_attendees',
						'delete_private_emd_event_attendees',
						'delete_published_emd_event_attendees',
						'delete_others_emd_event_attendees',
						'edit_private_emd_event_attendees',
						'edit_published_emd_event_attendees',
						'assign_emd_event_cat',
						'assign_emd_event_tag'
					) ,
					'sacc_caps' => Array() ,
					'svendor_caps' => Array() ,
					'sworker_caps' => Array(
						'edit_emd_wpe_events',
						'delete_emd_wpe_events',
						'edit_others_emd_wpe_events',
						'publish_emd_wpe_events',
						'read_private_emd_wpe_events',
						'delete_private_emd_wpe_events',
						'delete_published_emd_wpe_events',
						'delete_others_emd_wpe_events',
						'edit_private_emd_wpe_events',
						'edit_published_emd_wpe_events',
						'edit_emd_event_attendees',
						'delete_emd_event_attendees',
						'edit_others_emd_event_attendees',
						'publish_emd_event_attendees',
						'read_private_emd_event_attendees',
						'delete_private_emd_event_attendees',
						'delete_published_emd_event_attendees',
						'delete_others_emd_event_attendees',
						'edit_private_emd_event_attendees',
						'edit_published_emd_event_attendees',
						'assign_emd_event_cat',
						'assign_emd_event_tag'
					) ,
					'order_term' => '',
					'order_type' => 'many-to-many',
					'order_from' => 'Events',
					'order_to' => 'EDD Orders',
					'order_box' => 'from',
					'order_layout' => 'None',
					'order_header' => '',
					'order_footer' => '',
					'purchase_history_label' => '',
					'purchase_history_url' => '',
					'product_term' => '',
					'product_type' => 'one-to-one',
					'product_from' => 'Events',
					'product_to' => 'EDD Tickets',
					'product_box' => 'any',
					'product_layout' => '!#shortcode[purchase_link id="!#edd_download_id#" text="Purchase" style="button" color="blue"]#',
					'product_header' => '',
					'product_footer' => ''
				)
			);
			update_option($this->option_name . '_has_edd', $has_edd);
			//conf parameters for mailchimp
			$has_mailchimp = Array(
				'event_attendee' => Array(
					'entity' => 'emd_event_attendee',
					'tax' => Array()
				)
			);
			update_option($this->option_name . '_has_mailchimp', $has_mailchimp);
			//action to configure different extension conf parameters for this plugin
			do_action('emd_ext_set_conf', 'wp-easy-events');
		}
		/**
		 * Reset app specific options
		 *
		 * @since WPAS 4.0
		 *
		 */
		private function reset_options() {
			delete_option($this->option_name . '_shc_list');
			$emd_calendar_apps = get_option('emd_calendar_apps', Array());
			unset($emd_calendar_apps[$this->option_name]);
			update_option('emd_calendar_apps', $emd_calendar_apps);
			delete_option($this->option_name . '_has_calendar');
			delete_option($this->option_name . '_has_edd');
			delete_option($this->option_name . '_has_mailchimp');
			do_action('emd_ext_reset_conf', 'wp-easy-events');
		}
		/**
		 * Show admin notices
		 *
		 * @since WPAS 4.0
		 *
		 * @return html
		 */
		public function install_notice() {
			if (isset($_GET[$this->option_name . '_adm_notice1'])) {
				update_option($this->option_name . '_adm_notice1', true);
			}
			if (current_user_can('manage_options') && get_option($this->option_name . '_adm_notice1') != 1) {
?>
<div class="updated">
<?php
				printf('<p><a href="%1s" target="_blank"> %2$s </a>%3$s<a style="float:right;" href="%4$s"><span class="dashicons dashicons-dismiss" style="font-size:15px;"></span>%5$s</a></p>', 'https://docs.emdplugins.com/docs/wp-easy-events-community-documentation/?pk_campaign=wp-easy-events&pk_source=plugin&pk_medium=link&pk_content=notice', __('New To WP Easy Events? Review the documentation!', 'wpas') , __('&#187;', 'wpas') , esc_url(add_query_arg($this->option_name . '_adm_notice1', true)) , __('Dismiss', 'wpas'));
?>
</div>
<?php
			}
			if (current_user_can('manage_options') && get_option($this->option_name . '_setup_pages') == 1) {
				echo "<div id=\"message\" class=\"updated\"><p><strong>" . __('Welcome to WP Easy Events', 'wp-easy-events') . "</strong></p>
           <p class=\"submit\"><a href=\"" . add_query_arg('setup_wp_easy_events_pages', 'true', admin_url('index.php')) . "\" class=\"button-primary\">" . __('Setup WP Easy Events Pages', 'wp-easy-events') . "</a> <a class=\"skip button-primary\" href=\"" . add_query_arg('skip_setup_wp_easy_events_pages', 'true', admin_url('index.php')) . "\">" . __('Skip setup', 'wp-easy-events') . "</a></p>
         </div>";
			}
		}
		/**
		 * Setup pages for components and redirect to dashboard
		 *
		 * @since WPAS 4.0
		 *
		 */
		public function setup_pages() {
			if (!is_admin()) {
				return;
			}
			if (!empty($_GET['setup_' . $this->option_name . '_pages'])) {
				$shc_list = get_option($this->option_name . '_shc_list');
				emd_create_install_pages($this->option_name, $shc_list);
				delete_option($this->option_name . '_setup_pages');
				wp_redirect(admin_url('admin.php?page=' . $this->option_name . '_settings&wp-easy-events-installed=true'));
				exit;
			}
			if (!empty($_GET['skip_setup_' . $this->option_name . '_pages'])) {
				delete_option($this->option_name . '_setup_pages');
				wp_redirect(admin_url('admin.php?page=' . $this->option_name . '_settings'));
				exit;
			}
		}
		public function tinymce_fix($init) {
			$init['wpautop'] = false;
			return $init;
		}
	}
endif;
return new Wp_Easy_Events_Install_Deactivate();
