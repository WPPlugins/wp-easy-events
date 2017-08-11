<?php
/**
 * Settings Functions
 *
 * @package     EMD
 * @copyright   Copyright (c) 2014,  Emarket Design
 * @since       WPAS 4.4
 */
if (!defined('ABSPATH')) exit;

add_action('emd_ext_register','emd_glob_register_settings');
add_action('emd_show_settings_page','emd_show_settings_page',1);
/**
 * Show settings page for global variables
 *
 * @param string $app
 * @since WPAS 4.4
 *
 * @return html page content
 */
if (!function_exists('emd_show_settings_page')) {
	function emd_show_settings_page($app){
		global $title;
		?>
		<div class="wrap">
		<h2><?php echo $title; ?></h2>
		<?php	
		$tabs['entity'] = __('Entities', 'emd_plugins');
		$new_tax_list = Array();
		$tax_list = get_option($app . '_tax_list');
		$shc_list = get_option($app . '_shc_list');
		if(!empty($tax_list)){
			foreach($tax_list as $tax_ent => $tax){
				foreach($tax as $tax_key => $set_tax){
					if($set_tax['type'] != 'builtin'){
						$new_tax_list[$tax_ent][$tax_key] = $set_tax;			
					}
				}
			}
		}
		echo '<p>' . settings_errors($app . '_ent_map_list') . '</p>';
		if(!empty($new_tax_list)){	
			echo '<p>' . settings_errors($app . '_tax_settings') . '</p>';
			$tabs['taxonomy'] = __('Taxonomies', 'emd_plugins');
		}
		$tabs = apply_filters('emd_add_settings_tab',$tabs,$app);
		$tabs['tools'] = __('Tools', 'emd_plugins');
		$active_tab = isset($_GET['tab']) ? (string) $_GET['tab'] : 'entity';
		if(isset($_GET['settings-updated']) && $_GET['settings-updated'] == true){
			echo '<div id="message" class="updated">' . __('Settings Saved.','emd-plugins') . '</div>';
		}
		echo '<h2 class="nav-tab-wrapper">';
		foreach ($tabs as $ktab => $mytab) {
			$turl = remove_query_arg(array('action','_wpnonce'));
			$tab_url[$ktab] = esc_url(add_query_arg(array(
							'tab' => $ktab
							),$turl));
			$active = "";
			if ($active_tab == $ktab) {
				$active = "nav-tab-active";
			}
			echo '<a href="' . esc_url($tab_url[$ktab]) . '" class="nav-tab ' . $active . '" id="nav-' . $ktab . '">' . $mytab . '</a>';
		}
		echo '</h2>';
		emd_ent_map_tab($app,$active_tab,$shc_list);
		emd_tax_tab($app,$active_tab,$new_tax_list,$shc_list);
		do_action('emd_show_settings_tab',$app,$active_tab);
		emd_tools_tab($app,$active_tab,$shc_list);	
		echo '</div>';
	}
}
if (!function_exists('emd_glob_register_settings')) {
	function emd_glob_register_settings($app){
		register_setting($app . '_ent_map_list', $app . '_ent_map_list', 'emd_ent_map_sanitize');
		register_setting($app . '_tax_settings', $app . '_tax_settings', 'emd_tax_settings_sanitize');
		register_setting($app . '_tools', $app . '_tools', 'emd_tools_sanitize');
	}
}
if (!function_exists('emd_ent_map_tab')) {
	function emd_ent_map_tab($app,$active_tab,$shc_list){
		$ent_map_list = get_option($app .'_ent_map_list',Array());
		?>
			<div class='tab-content' id='tab-entity' <?php if ( 'entity' != $active_tab ) { echo 'style="display:none;"'; } ?>>
			<?php	echo '<form method="post" action="options.php">';
		settings_fields($app .'_ent_map_list');
		//show entity rewrite url
		$ent_map_variables = Array();
		$attr_list = get_option($app . '_attr_list');
		$ent_list = get_option($app . '_ent_list');
		foreach($attr_list as $ent => $attr){
			foreach($attr as $kattr => $vattr){
				if($vattr['display_type'] == 'map'){
					$ent_map_variables[$kattr] = Array('ent'=>$ent,'label'=>$vattr['label'], 'ent_label'=>$ent_list[$ent]['label']);
				}
			}
		}
		$map_ents = Array();
		if(!empty($ent_map_variables)){
			foreach($ent_map_variables as $mkey => $mval){
				$map_ents[$mval['ent']]['label'] = $mval['ent_label'];
				$map_ents[$mval['ent']]['attrs'][] = $mkey;
			}
		}
		if(!empty($ent_list)){
			$inline_ent_list = get_option('emd_inline_ent_list', Array());
			foreach($ent_list as $kent => $vent){
				if(empty($vent['rating_ent']) && !in_array($kent,array_keys($inline_ent_list))){
					if(empty($map_ents[$kent])){
						$map_ents[$kent]['label'] = $vent['label'];
					}
					$map_ents[$kent]['rewrite'] = '';
					if(!empty($vent['rewrite'])){
						$map_ents[$kent]['rewrite'] = $vent['rewrite'];
					}
				}
			}
		}
		echo '<input type="hidden" name="' . esc_attr($app) . '_ent_map_list[app]" id="' . esc_attr($app) . '_ent_map_list_app" value="' . $app . '">';
		echo '<div id="map-list" class="accordion-container"><ul class="outer-border">';
		foreach($map_ents as $kent => $myent){
			$is_ent_public = get_post_type_object($kent)->public;
			echo '<li id="' . esc_attr($kent) . '" class="control-section accordion-section ';
			echo (count($map_ents) == 1) ? 'open' : '';
			echo '">';
			echo '<h3 class="accordion-section-title hndle" tabindex="0">' . $myent['label'] . '</h3>';
			echo '<div class="accordion-section-content"><div class="inside">';
			echo '<table class="form-table"><tbody>';
			if($is_ent_public !== false){
				echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_rewrite'>";
				echo __('Base slug','emd-plugins');
				echo '</label></th><td>';
				$rewrite = isset($ent_map_list[$kent]['rewrite']) ? $ent_map_list[$kent]['rewrite'] : $myent['rewrite'];
				echo "<input id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_rewrite' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][rewrite]' type='text' value='" . $rewrite ."'></input><p class='description'>" . sprintf(__('Sets the custom base slug for single and archive %s. After you update,  flush the rewrite rules by going to the Permalink Settings page. This works only if post name based permalink structure is selected.','emd-plugins'),strtolower($myent['label'])) . "</p></td></tr>";
				echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_single_temp'>";
				echo __('Single template','emd-plugins');
				echo '</label></th><td>';
				$single_temp = isset($ent_map_list[$kent]['single_temp']) ? $ent_map_list[$kent]['single_temp'] : 'right';
				echo "<select id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_single_temp' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][single_temp]'>";
				$temp_options = Array('right' => __('Right Sidebar','emd-plugins'),'left' => __('Left Sidebar','emd-plugins'), 'full' => __('Full Width','emd-plugins'));
				foreach($temp_options as $ktemp => $vtemp){
					echo "<option value='" . $ktemp . "'";
					if($single_temp == $ktemp){
						echo " selected";
					}
					echo ">" . $vtemp . "</option>";
				}
				echo "</select><p class='description'>" . sprintf(__('Sets the template for single %s.','emd-plugins'),strtolower($myent['label'])) . "</p></td></tr>";
				echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_hide_prev_next'>";
				echo __('Hide previous next links','emd-plugins');
				echo '</label></th><td>';
				echo "<input type='checkbox' id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_hide_prev_next' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][hide_prev_next]' value=1";
				if(isset($ent_map_list[$kent]['hide_prev_next'])){
					echo " checked";
				}
				echo ">";
				echo "<p class='description'>" . sprintf(__('Hides the previous and next %s links on the frontend for single %s.','emd-plugins'),strtolower($myent['label']),strtolower($myent['label'])) . "</p></td></tr>";
			}
			if($ent_list[$kent]['archive_view']){	
				echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_archive_temp'>";
				echo __('Archive template','emd-plugins');
				echo '</label></th><td>';
				$archive_temp = isset($ent_map_list[$kent]['archive_temp']) ? $ent_map_list[$kent]['archive_temp'] : 'right';
				echo "<select id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_archive_temp' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][archive_temp]'>";
				foreach($temp_options as $ktemp => $vtemp){
					echo "<option value='" . $ktemp . "'";
					if($archive_temp == $ktemp){
						echo " selected";
					}
					echo ">" . $vtemp . "</option>";
				}
				echo "</select><p class='description'>" . sprintf(__('Sets the template for archive %s.','emd-plugins'),strtolower($myent['label'])) . "</p></td></tr>";
				echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_hide_archive_page_nav'>";
				echo __('Hide page navigation','emd-plugins');
				echo '</label></th><td>';
				echo "<input type='checkbox' id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_hide_archive_page_nav' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][hide_archive_page_nav]' value=1";
				if(isset($ent_map_list[$kent]['hide_archive_page_nav'])){
					echo " checked";
				}
				echo ">";
				echo "<p class='description'>" . sprintf(__('Hides the page navigation links on the frontend for archive %s.','emd-plugins'),strtolower($myent['label'])) . "</p></td></tr>";
			}
			if($shc_list['remove_vis'] != 1){
				//Show all attributes and ability to disable them
				$fields_table = '';
				if($is_ent_public !== false){
					$fields_options = Array('show' => __('Enable','emd-plugins'),
							'hide' => __('Disable','emd-plugins'),
							'hide_frontend' => __('Hide on FrontEnd Pages','emd-plugins')
					);
				}
				else {
					$fields_options = Array('show' => __('Enable','emd-plugins'),
							'hide' => __('Disable','emd-plugins'),
					);
				}
				if(!empty($ent_list[$kent]['blt_list'])){
					foreach($ent_list[$kent]['blt_list'] as $blt_attr => $blt_label){
						$fields_table .= "<tr><td style='padding:8px;'>" . $blt_label . "</td>";
						$fields_table .= "<td style='padding:8px;'><select id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_attrs' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][attrs][" . $blt_attr . "]'>";
						foreach($fields_options as $fkey => $fval){
							$fields_table .= "<option value='" . $fkey . "'";
							if(!empty($ent_map_list[$kent]['attrs'][$blt_attr]) && $ent_map_list[$kent]['attrs'][$blt_attr] == $fkey){
								$fields_table .= " selected";
							}
							$fields_table .= ">" . $fval . "</option>";
						}
						$fields_table .= "</select></td></tr>";
					}
				}
				if(!empty($ent_list[$kent]['featured_img'])){
					$fields_table .= "<tr><td style='padding:8px;'>" . __('Featured Image','emd-plugins') . "</td>";
					$fields_table .= "<td style='padding:8px;'><select id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_attrs' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][attrs][featured_img]'>";
					foreach($fields_options as $fkey => $fval){
						$fields_table .= "<option value='" . $fkey . "'";
						if(!empty($ent_map_list[$kent]['attrs']['featured_img']) && $ent_map_list[$kent]['attrs']['featured_img'] == $fkey){
							$fields_table .= " selected";
						}
						$fields_table .= ">" . $fval . "</option>";
					}
					$fields_table .= "</select></td></tr>";
				}
				foreach($attr_list[$kent] as $kattr => $vattr){
					if(!preg_match('/^wpas_/',$kattr)){
						$fields_table .= "<tr><td style='padding:8px;'>" . $vattr['label'] . "</td>";
						$fields_table .= "<td style='padding:8px;'><select id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_attrs' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][attrs][" . $kattr . "]'>";
						if($is_ent_public === false){
							$fields_options = Array('show' => __('Enable','emd-plugins'),
									'hide' => __('Disable','emd-plugins'),
							);
						}
						else {
							if(empty($vattr['uniqueAttr']) && empty($vattr['required'])){
								$fields_options = Array('show' => __('Enable','emd-plugins'),
									'hide' => __('Disable','emd-plugins'),
									'hide_frontend' => __('Hide on FrontEnd Pages','emd-plugins')
								);
							}
							else {	
								$fields_options = Array('show' => __('Enable','emd-plugins'),
									'hide_frontend' => __('Hide on FrontEnd Pages','emd-plugins')
								);
							}
						}
						foreach($fields_options as $fkey => $fval){
							$fields_table .= "<option value='" . $fkey . "'";
							if(!empty($ent_map_list[$kent]['attrs'][$kattr]) && $ent_map_list[$kent]['attrs'][$kattr] == $fkey){
								$fields_table .= " selected";
							}
							$fields_table .= ">" . $fval . "</option>";
						}
						$fields_table .= "</select></td></tr>";
					}
				}
				if(!empty($fields_table)){
					echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_attrs'>";
					echo __('Attributes','emd-plugins');
					echo '</label></th><td>';
					echo "<p class='description'>" . __('Enable: Display this attribute on everywhere</br>Disable: Remove this attribute from everywhere</br>Hide on FrontEnd Pages: This attribute is still enabled on admin area. If you want to hide this attribute on the frontend forms go to Forms tab.','emd-plugins') . "</p></br>";
					echo "<table class='widefat striped' style='width:320px;'>" . $fields_table . "</table>";
					echo "</td></tr>";
				}
			}
			
			//check if this entity supports custom fields and show all cust fields attached
			if(post_type_supports($kent,'custom-fields')){
				$ent_cname = str_replace(" ", "_",ucwords(str_replace("_"," ",$kent)));
				$ent_obj = new $ent_cname;
				if(!empty($ent_obj)){
					$cust_fields = $ent_obj->get_cust_fields(Array() , $kent);
					if(!empty($cust_fields)){
						echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_cust_fields'>";
						echo __('Hide custom fields','emd-plugins');
						echo '</label></th><td>';
						foreach($cust_fields as $kcust => $ent_cust_field){
							echo "<input id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_cust_fields' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][cust_fields][" . $kcust . "]' type='checkbox' value=1";
							if(isset($ent_map_list[$kent]['cust_fields'][$kcust])){
								echo " checked";
							}
							echo ">" . $ent_cust_field . "</input></br>";
						}
						echo "<p class='description'>" . __('Check the custom fields you would like to hide on the frontend.','emd-plugins') . "</p></td></tr>";
					}
				}
			}
			if($shc_list['remove_vis'] != 1){
				//hide relationships
				$rel_list = get_option($app . '_rel_list');
				$rels = Array();
				$rel_attrs = Array();
				if(!empty($rel_list)){
					foreach($rel_list as $rkey => $rval){
						if($rval['from'] == $kent){
							$rels[$rkey] = $rkey;
							if(!empty($rval['attrs'])){
								foreach($rval['attrs'] as $rakey => $raval){
									$rel_attrs[$rakey] = $raval;
								}
							}
						}
					}
				}
				if(!empty($rels)){
					echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_hide_rels'>";
					echo __('Relationships','emd-plugins');
					echo '</label></th><td>';
					echo "<p class='description'>" . __('Enable: Display this relationship on everywhere</br>Disable: Remove this relationship from everywhere</br>Hide on FrontEnd Pages: This relationship is still enabled on admin area. If you want to hide this relationship on the frontend forms go to Forms tab.','emd-plugins') . "</p></br>";
					echo "<table class='widefat striped' style='width:320px;'>";
					foreach($rels as $myrel){
						echo "<tr><td style='padding:8px;'>" . $rel_list[$myrel]['from_title'] . "</td>";
						echo "<td style='padding:8px;'><select id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_hide_rels' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][hide_rels][" . $myrel . "]'>";
						$rel_options = Array('show' => __('Enable','emd-plugins'),
									'hide' => __('Disable','emd-plugins'),
									'hide_frontend' => __('Hide on FrontEnd Pages','emd-plugins')
								);
						foreach($rel_options as $fkey => $fval){
							echo "<option value='" . $fkey . "'";
							if(!empty($ent_map_list[$kent]['hide_rels'][$myrel]) && $ent_map_list[$kent]['hide_rels'][$myrel] == $fkey){
								echo " selected";
							}
							echo ">" . $fval . "</option>";
						}
						echo "</select></td></tr>";
					}
					echo "</table>";
					echo "</td></tr>";
					if(!empty($rel_attrs)){
						echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_hide_rel_attrs'>";
						echo __('Relationship attributes','emd-plugins');
						echo '</label></th><td>';
						echo "<p class='description'>" . __('Enable: Display this relationship attribute on everywhere</br>Disable: Remove this relationship attribute from everywhere</br>Hide on FrontEnd Pages: This relationship attribute is still enabled on admin area.','emd-plugins') . "</p></br>";
						echo "<table class='widefat striped' style='width:320px;'>";
						foreach($rel_attrs as $krattr => $myrelattr){
							echo "<tr><td style='padding:8px;'>" . $myrelattr['label'] . "</td>";
							echo "<td style='padding:8px;'><select id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_hide_rel_attrs' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][hide_rel_attrs][" . $krattr . "]'>";
							$rel_options = Array('show' => __('Enable','emd-plugins'),
										'hide' => __('Disable','emd-plugins'),
										'hide_frontend' => __('Hide on FrontEnd Pages','emd-plugins')
									);
							foreach($rel_options as $fkey => $fval){
								echo "<option value='" . $fkey . "'";
								if(!empty($ent_map_list[$kent]['hide_rel_attrs'][$krattr]) && $ent_map_list[$kent]['hide_rel_attrs'][$krattr] == $fkey){
									echo " selected";
								}
								echo ">" . $fval . "</option>";
							}
							echo "</select></td></tr>";
						}
						echo "</table>";
						echo "</td></tr>";
					}
				}
			}
			$is_ent_limitby = false;
			$has_limitby_auth_type = get_option($app ."_limitby_auth_caps");
			if (!empty($has_limitby_auth_type[$kent])){
				foreach ($has_limitby_auth_type[$kent] as $limit) {
					if (preg_match('/limitby_author_frontend/', $limit)) {
						$is_ent_limitby = true;
					}
				}
				if($is_ent_limitby === true && $is_ent_public !== false){
					echo "<tr><th scope='row'><label for='ent_map_list_" . $kent . "_make_visitor_visible'>";
					echo __('Remove access restrictions on frontend','emd-plugins');
					echo '</label></th><td>';
					echo "<input type='checkbox' id='" . esc_attr($app) . "_ent_map_list_" . $kent . "_make_visitor_visible' name='" . esc_attr($app) . "_ent_map_list[" . $kent . "][make_visitor_visible]' value=1";
					if(isset($ent_map_list[$kent]['make_visitor_visible'])){
						echo " checked";
					}
					echo ">";
					echo "<p class='description'>" . sprintf(__('Shows all %s to logged-in and non-logged-in users regardless of content access permissions assigned to their user role.','emd-plugins'),strtolower($myent['label'])) . "</p></td></tr>";
				}	
			}
			if(!empty($myent['attrs'])){
				emd_show_map_attrs($app,$myent,$ent_map_variables,$ent_map_list);	
			}
			echo '</tbody></table>';
			echo '</div></div></li>';
		}
		echo '</ul></div>';
		submit_button(); 
		echo '</form></div>';
	}
}
if (!function_exists('emd_show_map_attrs')) {
	function emd_show_map_attrs($app,$myent,$ent_map_variables,$ent_map_list){
		foreach($myent['attrs'] as $mattr){
			$mattr_key = $mattr;
			$mattr_val = $ent_map_variables[$mattr_key];
			echo '<tr>
				<th scope="row">
				<label for="' . $mattr_key . '">';
			echo $mattr_val['label']; 
			echo '</label>
				</th>
				<td>';
			$width = isset($ent_map_list[$mattr_key]['width']) ? $ent_map_list[$mattr_key]['width'] : '';
			$height = isset($ent_map_list[$mattr_key]['height']) ? $ent_map_list[$mattr_key]['height'] : '';
			$zoom = isset($ent_map_list[$mattr_key]['zoom']) ? $ent_map_list[$mattr_key]['zoom'] : '14';
			$marker = isset($ent_map_list[$mattr_key]['marker']) ? 'checked' : '';
			$load_info = isset($ent_map_list[$mattr_key]['load_info']) ? 'checked' : '';
			$map_type = isset($ent_map_list[$mattr_key]['map_type']) ? $ent_map_list[$mattr_key]['map_type'] : '';
			echo "<tr><th scope='row'></th><td><table><th scope='row'><label>" . __('Frontend map settings','emd-plugins') . "</th><td></td></tr>
				<th scope='row'><label for='ent_map_list_" . $mattr_key . "_width'>" . __('Width','emd-plugins') . "</th><td><input id='" . esc_attr($app) . "_ent_map_list_" . $mattr_key . "_width' name='" . esc_attr($app) . "_ent_map_list[" . $mattr_key . "][width]' type='text' value='" . $width . "'></input><p class='description'>" . __('Sets the map width.You can use \'%\' or \'px\'. Default is 100%.','emd-plugins') . "</p></td></tr>";
			echo "<tr><th scope='row'><label for='ent_map_list_" . $mattr_key . "_height'>" . __('Height','emd-plugins') . "</th><td><input id='" . esc_attr($app) . "_ent_map_list_" . $mattr_key . "_height' name='" . esc_attr($app) . "_ent_map_list[" . $mattr_key . "][height]' type='text' value='" . $height ."'></input><p class='description'>" . __('Sets the map height. You can use \'px\'. Default is 480px.','emd-plugins') . "</p></td></tr>";
			echo "<tr><th scope='row'><label for='ent_map_list_" . $mattr_key . "_zoom'>" . __('Zoom','emd-plugins') . "</th><td><select id='" . esc_attr($app) . "_ent_map_list_" . $mattr_key . "_zoom' name='" . esc_attr($app) . "_ent_map_list[" . $mattr_key . "][zoom]'>";
			for($i=20;$i >=1;$i--){
				echo "<option value='" . $i . "'";
				if($zoom == $i){
					echo " selected";
				}
				echo ">" . $i . "</option>";
			}
			echo "</select></td></tr>";
			echo "<tr><th scope='row'><label for='ent_map_list_" . $mattr_key . "_map_type'>" . __('Type','emd-plugins') . "</th><td><select id='" . esc_attr($app) . "_ent_map_list_" . $mattr_key . "_map_type' name='" . esc_attr($app) . "_ent_map_list[" . $mattr_key . "][map_type]'>";
			$map_types = Array("ROADMAP","SATELLITE","HYBRID","TERRAIN");
			foreach($map_types as $mtype){
				echo "<option value='" . $mtype . "'";
				if($map_type == $mtype){
					echo " selected";
				}
				echo ">" . $mtype . "</option>";
			}
			echo "</select></td></tr>";
			echo "<tr><th scope='row'><label for='ent_map_list_" . $mattr_key . "_marker'>" . __('Marker','emd-plugins') . "</th><td><input id='" . esc_attr($app) . "_ent_map_list_" . $mattr_key . "_marker' name='" . esc_attr($app) . "_ent_map_list[" . $mattr_key . "][marker]' type='checkbox' value=1 $marker></input></td></tr>";
			echo "<tr><th scope='row'><label for='ent_map_list_" . $mattr_key . "_load_info'>" . __('Display info window on page load','emd-plugins') . "</th><td><input id='" . esc_attr($app) . "_ent_map_list_" . $mattr_key . "_load_info' name='" . esc_attr($app) . "_ent_map_list[" . $mattr_key . "][load_info]' type='checkbox' value=1 $load_info></input></td></tr>";
			echo "</div></td></tr></table></td></tr>";
			echo '</td>
				</tr>';
		}
	}
}
if (!function_exists('emd_ent_map_sanitize')) {
	function emd_ent_map_sanitize($input){
		if(empty($input['app'])){
			return $input;
		}
		$ent_map_list = get_option($input['app'] . '_ent_map_list');
		$attr_list = get_option($input['app'] . '_attr_list');
		$rel_list = get_option($input['app'] . '_rel_list');
		$forms_list = get_option($input['app'] . '_glob_forms_list');
		$map_keys = Array('hide_archive_page_nav','hide_prev_next','hide_rels','hide_rel_attrs','attrs','single_temp','archive_temp','make_visitor_visible','rewrite','cust_fields','width','height','zoom','map_type','marker','load_info');
		foreach($input as $ikey => $vkey){
			if($ikey != 'app'){
				foreach($map_keys as $mkey){
					if(isset($vkey[$mkey])){
						$ent_map_list[$ikey][$mkey] = $vkey[$mkey];
					}
					elseif(!empty($ent_map_list[$ikey][$mkey])){
						unset($ent_map_list[$ikey][$mkey]);    
					}
				}
				if(!empty($forms_list)){
					$shc_list = get_option($input['app'] . '_shc_list');
					foreach($forms_list as $fkey => $fval){
						foreach($fval as $fattrkey => $fattrval){
							if(is_array($fattrval) && isset($fattrval['show']) && 
								isset($attr_list[$ikey][$fattrkey]) &&
								empty($attr_list[$ikey][$fattrkey]['uniqueAttr']) &&
								empty($attr_list[$ikey][$fattrkey]['required'])
							){
								if($vkey['attrs'][$fattrkey] == 'hide'){
									$forms_list[$fkey][$fattrkey]['show'] = 0;
								}
								else {
									$forms_list[$fkey][$fattrkey]['show'] = 1;
								}
							}
							elseif(is_array($fattrval) && isset($fattrval['show']) && 
								preg_match('/^blt_/',$fattrkey) && 
								!empty($vkey['attrs'][$fattrkey])&& 
								!empty($shc_list['forms'][$fkey]) &&
								$shc_list['forms'][$fkey]['ent'] == $ikey
							){
									if($vkey['attrs'][$fattrkey] == 'hide'){
										$forms_list[$fkey][$fattrkey]['show'] = 0;
									}
									else {
										$forms_list[$fkey][$fattrkey]['show'] = 1;
									}
							}
							elseif(is_array($fattrval) && isset($fattrval['show']) &&
								isset($rel_list[$fattrkey])
							){
								if(isset($vkey['hide_rels'][$fattrkey]) && $vkey['hide_rels'][$fattrkey] == 'hide'){
									$forms_list[$fkey][$fattrkey]['show'] = 0;
								}
								else {
									$forms_list[$fkey][$fattrkey]['show'] = 1;
								}
							}
						}
					}
				}
			}
		}
		update_option($input['app'] . '_glob_forms_list', $forms_list);
		return $ent_map_list;
	}
}
if (!function_exists('emd_get_attr_map')) {
	function emd_get_attr_map($app,$key,$marker_title,$info_window,$post_id=''){
		$ent_map_list = get_option(str_replace("-","_",$app) . '_ent_map_list');
		$args = Array();
		$marker = (!empty($ent_map_list[$key]['marker'])) ? true : false;
		$load_info = (!empty($ent_map_list[$key]['load_info'])) ? true : false;
		$zoom = (!empty($ent_map_list[$key]['zoom'])) ? (int) $ent_map_list[$key]['zoom'] : 14;
		$map_type = (!empty($ent_map_list[$key]['map_type'])) ? $ent_map_list[$key]['map_type'] : 'ROADMAP';
		$width = (!empty($ent_map_list[$key]['width'])) ? $ent_map_list[$key]['width'] : '100%'; // Map width, default is 640px. You can use '%' or 'px'
		$height = (!empty($ent_map_list[$key]['height'])) ? $ent_map_list[$key]['height'] : '480px'; // Map height, default is 480px. You can use '%' or 'px'
		
		$args = array(
				'app'		=> str_replace("-","_",$app),
				'type'	       => 'map',
				'zoom'         => $zoom,  // Map zoom, default is the value set in admin, and if it's omitted - 14
				'width'        => $width,
				'height'       => $height,
				// Map type, see https://developers.google.com/maps/documentation/javascript/reference#MapTypeId
				'mapTypeId'    => $map_type,
				'marker'       => $marker, // Display marker? Default is 'true',
				'load_info'    => $load_info
			);
		if($marker !== false && !empty($marker_title)){
			if($marker_title == 'emd_blt_title'){
				$args['marker_title'] = get_the_title($post_id); // Marker title when hover
			}
			else {	
				$args['marker_title'] = emd_mb_meta($marker_title,'',$post_id); // Marker title when hover
			}
		}
		if($marker !== false && !empty($info_window)){
			if($info_window == 'emd_blt_title'){
				$args['info_window'] = get_the_title($post_id); // Info window content, can be anything. HTML allowed.
			}
			else {
				$args['info_window'] = emd_mb_meta($info_window,'',$post_id); // Info window content, can be anything. HTML allowed.
			}
		}
		return emd_mb_meta($key,$args,$post_id);
	}
}
if (!function_exists('emd_tax_tab')) {
	function emd_tax_tab($app,$active_tab,$tax_list,$shc_list){
		if(!empty($tax_list)){
			$tax_settings = get_option($app .'_tax_settings',Array());
	?>
	<div class='tab-content' id='tab-taxonomy' <?php if ( 'taxonomy' != $active_tab ) { echo 'style="display:none;"'; } ?>>
		<?php	echo '<form method="post" action="options.php">';
			settings_fields($app .'_tax_settings');
			//show taxonomy rewrite url
			if(!empty($tax_list)){
				$inline_ent_list = get_option('emd_inline_ent_list', Array());
				foreach($tax_list as $tent => $vtax){
					if(!in_array($tent,array_keys($inline_ent_list))){
						foreach($vtax as $ktax => $valtax){
							$tax_list_vals[$ktax]['rewrite'] = $ktax;
							if(!empty($valtax['rewrite'])){
								$tax_list_vals[$ktax]['rewrite'] = $valtax['rewrite'];
							}
							$tax_list_vals[$ktax]['label'] = $valtax['label'];
							$tax_list_vals[$ktax]['archive_view'] = $valtax['archive_view'];
						}
					}
				}
			}
			echo '<input type="hidden" name="' . esc_attr($app) . '_tax_settings[app]" id="' . esc_attr($app) . '_tax_settings_app" value="' . $app . '">';
			echo '<div id="tax-settings" class="accordion-container"><ul class="outer-border">';
			foreach($tax_list_vals as $ktax => $mytax){
				$rewrite = isset($tax_settings[$ktax]['rewrite']) ? $tax_settings[$ktax]['rewrite'] : $mytax['rewrite'];
				echo '<li id="' . esc_attr($ktax) . '" class="control-section accordion-section ';
				echo (count($tax_list_vals) == 1) ? 'open' : '';
				echo '">';
				echo '<h3 class="accordion-section-title hndle" tabindex="0">' . $mytax['label'] . ' (' . $rewrite . ')' . '</h3>';
				echo '<div class="accordion-section-content"><div class="inside">';
				echo '<table class="form-table"><tbody>';
				if($shc_list['remove_vis'] != 1){
					echo "<tr><th scope='row'><label for='tax_settings_" . $ktax . "_hide'>";
					echo __('Availability','emd-plugins');
					echo '</label></th><td>';
					echo "<select id='" . esc_attr($app) . "_tax_settings_" . $ktax . "_hide' name='" . esc_attr($app) . "_tax_settings[" . $ktax . "][hide]'>";
					$tax_options = Array('show' => __('Enable','emd-plugins'),
							'hide' => __('Disable','emd-plugins'),
							'hide_frontend' => __('Hide on FrontEnd Pages','emd-plugins')
							);
					foreach($tax_options as $tkey => $tval){
						echo "<option value='" . $tkey . "'";
						if(!empty($tax_settings[$ktax]['hide']) && $tax_settings[$ktax]['hide'] == $tkey){
							echo " selected";
						}
						echo ">" . $tval . "</option>";
					}
					echo "</select>";
					echo "<p class='description'>" . __('Enable: Display this taxonomy on everywhere</br>Disable: Remove this taxonomy from everywhere</br>Hide on FrontEnd Pages: This taxonomy is still enabled on admin area. If you want to hide this taxonomy on the frontend forms go to Forms tab.','emd-plugins') . "</p></td></tr>";
				}
				echo "<tr><th scope='row'><label for='tax_settings_" . $ktax . "_rewrite'>";
				echo __('Base slug','emd-plugins');
				echo '</label></th><td>';
				echo "<input id='" . esc_attr($app) . "_tax_settings_" . $ktax . "_rewrite' name='" . esc_attr($app) . "_tax_settings[" . $ktax . "][rewrite]' type='text' value='" . $rewrite ."'></input><p class='description'>" . __('Sets the custom base slug for this taxonomy. After you update,  flush the rewrite rules by going to the Permalink Settings page.','emd-plugins') . "</p></td></tr>";
				if(!empty($mytax['archive_view'])){	
					echo "<tr><th scope='row'><label for='tax_settings_" . $ktax . "_temp'>";
					echo __('Template','emd-plugins');
					echo '</label></th><td>';
					$tax_temp = isset($tax_settings[$ktax]['temp']) ? $tax_settings[$ktax]['temp'] : 'right';
					echo "<select id='" . esc_attr($app) . "_tax_settings_" . $ktax . "_temp' name='" . esc_attr($app) . "_tax_settings[" . $ktax . "][temp]'>";
					$temp_options = Array('right' => __('Right Sidebar','emd-plugins'),'left' => __('Left Sidebar','emd-plugins'), 'full' => __('Full Width','emd-plugins'));
					foreach($temp_options as $ktemp => $vtemp){
						echo "<option value='" . $ktemp . "'";
						if($tax_temp == $ktemp){
							echo " selected";
						}
						echo ">" . $vtemp . "</option>";
					}
					echo "</select><p class='description'>" . __('Sets the template for the posts which belong to this taxonomy.','emd-plugins') . "</p></td></tr>";
					echo "<tr><th scope='row'><label for='tax_settings_" . $ktax . "_hide_page_nav'>";
					echo __('Hide page navigation','emd-plugins');
					echo '</label></th><td>';
					echo "<input type='checkbox' id='" . esc_attr($app) . "_tax_settings_" . $ktax . "_hide_page_nav' name='" . esc_attr($app) . "_tax_settings[" . $ktax . "][hide_page_nav]' value=1";
					if(isset($tax_settings[$ktax]['hide_page_nav'])){
						echo " checked";
					}
					echo ">";
					echo "<p class='description'>" . __('Hides the page navigation links on the frontend for archive posts.','emd-plugins') . "</p></td></tr>";
				}
				echo '</tbody></table>';
				echo '</div></div></li>';
			}
			echo '</ul></div>';
			submit_button(); 
			echo '</form></div>';
		}
	}
}
if (!function_exists('emd_tax_settings_sanitize')) {
	function emd_tax_settings_sanitize($input){
		if(empty($input['app'])){
			return $input;
		}
		$tax_settings = get_option($input['app'] . '_tax_settings');
		$keys = Array('rewrite','temp','hide','hide_page_nav');
		foreach($input as $ikey => $vkey){
			if($ikey != 'app'){
				foreach($keys as $mkey){
					if(isset($vkey[$mkey])){
						$tax_settings[$ikey][$mkey] = $vkey[$mkey];
					}
					elseif(!empty($tax_settings[$ikey][$mkey])){
						unset($tax_settings[$ikey][$mkey]);    
					}
				}
			}
		}
		$tax_list = get_option($input['app'] . '_tax_list');
		$forms_list = get_option($input['app'] . '_glob_forms_list');
		if(!empty($tax_list)){
			foreach($tax_list as $tent => $vtax){
				foreach($vtax as $ktax => $valtax){
					$new_tax_list[$ktax]= $ktax;
				}
			}
		}
		if(!empty($forms_list)){
			foreach($forms_list as $fkey => $fval){
				foreach($fval as $ftaxkey => $ftaxval){
					if(is_array($ftaxval) && isset($ftaxval['show']) && !empty($new_tax_list[$ftaxkey])){
						if(isset($input[$ftaxkey]['hide']) && $input[$ftaxkey]['hide'] == 'hide'){
							$forms_list[$fkey][$ftaxkey]['show'] = 0;
						}
						else {
							$forms_list[$fkey][$ftaxkey]['show'] = 1;
						}
					}
				}
			}
		}
		update_option($input['app'] . '_glob_forms_list',$forms_list);
		return $tax_settings;
	}
}
if (!function_exists('emd_tools_tab')) {
	function emd_tools_tab($app,$active_tab,$shc_list){
		if ( ! empty( $_GET['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'install_pages_action' ) ) {
			emd_create_install_pages($app,$shc_list);
			echo '<div class="updated inline"><p>' . __( 'All missing pages were installed successfully.', 'emd-plugins' ) . '</p></div>';
		}
			
		$tools = get_option($app .'_tools',Array());
		?>
		<div class='tab-content' id='tab-tools' <?php if ( 'tools' != $active_tab ) { echo 'style="display:none;"'; } ?>>
		<?php	echo '<form method="post" action="options.php">';
		settings_fields($app .'_tools');
		echo '<input type="hidden" name="' . esc_attr($app) . '_tools[app]" id="' . esc_attr($app) . '_tools_app" value="' . $app . '">';
		echo '<table class="form-table"><tbody>';
		echo "<tr><th scope='row'><label for='tools_install_pages'>";
		echo __('Install pages','emd-plugins');
		echo '</label></th><td>';
		echo '<a href="' .  wp_nonce_url( admin_url('admin.php?page=' . $app . '_settings&tab=tools&action=install_pages'), 'install_pages_action' ) . '" class="button install_pages">' . __( 'Install pages', 'emd-plugins' ) . '</a>';
		echo "<p class='description'>" . sprintf(__('This tool will install all the missing %s pages. Pages already defined and set up will not be replaced.','emd-plugins'),$shc_list['app']) . "</p></td></tr>";
		echo "<tr><th scope='row'><label for='tools_disable_emd_templates'>";
		echo __('Disable EMD Templating System','emd-plugins');
		echo '</label></th><td>';
		echo "<input type='checkbox' id='" . esc_attr($app) . "_tools_disable_emd_templates' name='" . esc_attr($app) . "_tools[disable_emd_templates]' value=1";
		if(isset($tools['disable_emd_templates'])){
			echo " checked";
		}
		echo ">";
		echo "<p class='description'>" . __('Check this if you experience theme related issues. This will disable EMD Templating System and use your themes templates instead. It will also disable all the templating related options in Entities and Taxonomies tabs. If you still experience theme related issues after checking this option, read the plugin documentation\'s resolving theme related issues section.','emd-plugins') . "</p></td></tr>";
		echo "<tr><th scope='row'><label for='tools_remove_settings'>";
		echo __('Delete all settings','emd-plugins');
		echo '</label></th><td>';
		echo "<input type='checkbox' id='" . esc_attr($app) . "_tools_remove_settings' name='" . esc_attr($app) . "_tools[remove_settings]' value=1";
		if(isset($tools['remove_settings'])){
			echo " checked";
		}
		echo ">";
		echo "<p class='description'>" . __('This tool will delete all settings/options data including setup assistant created plugin pages when using the "Delete" link on the plugins screen.','emd-plugins') . "</p></td></tr>";
		echo "<tr><th scope='row'><label for='tools_remove_data'>";
		echo __('Delete all data','emd-plugins');
		echo '</label></th><td>';
		echo "<input type='checkbox' id='" . esc_attr($app) . "_tools_remove_data' name='" . esc_attr($app) . "_tools[remove_data]' value=1";
		if(isset($tools['remove_data'])){
			echo " checked";
		}
		echo ">";
		//get ent labels
		$ent_list = get_option($app . '_ent_list');
		foreach($ent_list as $myent){
			$ent_labels_arr[] = $myent['label'];
		}
		$ent_labels = implode($ent_labels_arr," , ");
		echo "<p class='description'>" . sprintf(__('This tool will delete all %s data and related taxonomies including setup assistant created plugin pages when using the "Delete" link on the plugins screen. As a best practice we recommend you to backup your database before selecting this option.','emd-plugins'),$ent_labels) . "</p></td></tr>";
		echo "<tr><th scope='row'><label for='tools_custom_css'>";
                echo __('Custom CSS','emd-plugins');
                echo "</label></th><td>";
                $custom_css = isset($tools['custom_css']) ? $tools['custom_css'] : '';
                echo "<textarea cols='70' rows='30' id='" . esc_attr($app) . "_tools_custom_css' name='" . esc_attr($app) . "_tools[custom_css]' >" .  esc_html($custom_css) . "</textarea>";
                echo "<p class='description'>" . __('Custom CSS allows you to add your own styles or override the default CSS of this plugin. The CSS code written here is only applied to this plugin\'s frontend pages.','emd-plugins') . "</p></td></tr>";
		echo '</tbody></table>';
		submit_button(); 
		echo '</form></div>';
		echo '<script language="javascript">
                        jQuery( document ).ready( function() {
                                var editor = CodeMirror.fromTextArea(document.getElementById("' . esc_attr($app) . '_tools_custom_css"), {lineNumbers: true, lineWrapping: true} );
                        });
                </script>';
	}
}
if (!function_exists('emd_create_install_pages')) {
	function emd_create_install_pages($app,$shc_list){
		global $wpdb;
		$shc_list = apply_filters('emd_ext_chart_list', $shc_list, $app);
		$types = Array(
			'forms',
			'charts',
			'shcs',
			'datagrids',
			'integrations'
		);
		foreach ($types as $shc_type) {
			if (!empty($shc_list[$shc_type])) {
				foreach ($shc_list[$shc_type] as $keyshc => $myshc) {
					if (isset($myshc['page_title'])) {
						$pages[$keyshc] = $myshc;
					}
				}
			}
		}
		$setup_pages_list = get_option($app . '_setup_pages_list',Array());
		foreach ($pages as $key => $page) {
			$found = "";
			$page_content = "[" . $key . "]";
			$found = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . $wpdb->posts . " WHERE post_type='page' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%"));
			if ($found != "") {
				$setup_pages_list[$key] = $found;
				continue;
			}
			$page_data = array(
				'post_status' => 'publish',
				'post_type' => 'page',
				'post_author' => get_current_user_id() ,
				'post_title' => $page['page_title'],
				'post_content' => $page_content,
				'comment_status' => 'closed'
			);
			$page_id = wp_insert_post($page_data);
			$setup_pages_list[$key] = $page_id;
		}
		if(!empty($setup_pages_list)){
			update_option($app . '_setup_pages_list',$setup_pages_list);
		}
	}
}
if (!function_exists('emd_tools_sanitize')) {
	function emd_tools_sanitize($input){
		if(empty($input['app'])){
			return $input;
		}
		$tools = get_option($input['app'] . '_tools');
		$keys = Array('disable_emd_templates','remove_settings','remove_data','custom_css');
		foreach($keys as $mkey){
			if(isset($input[$mkey])){
				$tools[$mkey] = $input[$mkey];
			}
			elseif(!empty($tools[$mkey])){
				unset($tools[$mkey]);    
			}
		}
		return $tools;
	}
}
