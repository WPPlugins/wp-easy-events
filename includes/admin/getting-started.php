<?php
/**
 * Getting Started
 *
 * @package WP_EASY_EVENTS
 * @since WPAS 5.3
 */
if (!defined('ABSPATH')) exit;
add_action('wp_easy_events_getting_started', 'wp_easy_events_getting_started');
/**
 * Display getting started information
 * @since WPAS 5.3
 *
 * @return html
 */
function wp_easy_events_getting_started() {
	global $title;
	list($display_version) = explode('-', WP_EASY_EVENTS_VERSION);
?>
<style>
div.comp-feature {
    font-weight: 400;
    font-size:20px;
}
.edition-com {
    display: none;
}
.green{
color: #008000;
font-size: 30px;
}
#nav-compare:before{
    content: "\f179";
}
#emd-about .nav-tab-wrapper a:before{
    position: relative;
    box-sizing: content-box;
padding: 0px 3px;
color: #4682b4;
    width: 20px;
    height: 20px;
    overflow: hidden;
    white-space: nowrap;
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
font-family: dashicons;
}
#nav-getting-started:before{
content: "\f102";
}
#nav-whats-new:before{
content: "\f348";
}
#nav-resources:before{
content: "\f118";
}
#emd-about .embed-container { 
	position: relative; 
	padding-bottom: 56.25%;
	height: 0;
	overflow: hidden;
	max-width: 100%;
	height: auto;
	} 

#emd-about .embed-container iframe,
#emd-about .embed-container object,
#emd-about .embed-container embed { 
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	}
#emd-about ul li:before{
    content: "\f522";
    font-family: dashicons;
    font-size:25px;
 }
#gallery {
	margin: auto;
}
#gallery .gallery-item {
	float: left;
	margin-top: 10px;
	margin-right: 10px;
	text-align: center;
	width: 48%;
        cursor:pointer;
}
#gallery img {
	border: 2px solid #cfcfcf; 
height: 405px;  
}
#gallery .gallery-caption {
	margin-left: 0;
}
#emd-about .top{
text-decoration:none;
}
#emd-about .toc{
    background-color: #fff;
    padding: 25px;
    border: 1px solid #add8e6;
    border-radius: 8px;
}
#emd-about h3,
#emd-about h2{
    margin-top: 0px;
    margin-right: 0px;
    margin-bottom: 0.6em;
    margin-left: 0px;
}
#emd-about p,
#emd-about .emd-section li{
font-size:18px
}
#emd-about a.top:after{
content: "\f342";
    font-family: dashicons;
    font-size:25px;
text-decoration:none;
}
#emd-about .toc a,
#emd-about a.top{
vertical-align: top;
}
#emd-about li{
list-style-type: none;
line-height: normal;
}
#emd-about ol li {
    list-style-type: decimal;
}
#emd-about .quote{
    background: #fff;
    border-left: 4px solid #088cf9;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    margin-top: 25px;
    padding: 1px 12px;
}
#emd-about .tooltip{
    display: inline;
    position: relative;
}
#emd-about .tooltip:hover:after{
    background: #333;
    background: rgba(0,0,0,.8);
    border-radius: 5px;
    bottom: 26px;
    color: #fff;
    content: 'Click to enlarge';
    left: 20%;
    padding: 5px 15px;
    position: absolute;
    z-index: 98;
    width: 220px;
}
</style>

<?php add_thickbox(); ?>
<div id="emd-about" class="wrap about-wrap">
<div id="emd-header" style="padding:10px 0" class="wp-clearfix">
<div style="float:right"><img src="https://emd-plugins.s3.amazonaws.com/wpee-logo-250x150.gif"></div>
<div style="margin: .2em 200px 0 0;padding: 0;color: #32373c;line-height: 1.2em;font-size: 2.8em;font-weight: 400;">
<?php printf(__('Welcome to WP Easy Events Community %s', 'wp-easy-events') , $display_version); ?>
</div>

<p class="about-text">
<?php printf(__("Easy-to-use yet beautiful and powerful  event management system for successful events", 'wp-easy-events') , $display_version); ?>
</p>

<?php
	$tabs['getting-started'] = __('Getting Started', 'wp-easy-events');
	$tabs['whats-new'] = __('What\'s New', 'wp-easy-events');
	$tabs['resources'] = __('Resources', 'wp-easy-events');
	$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'getting-started';
	echo '<h2 class="nav-tab-wrapper wp-clearfix">';
	foreach ($tabs as $ktab => $mytab) {
		$tab_url[$ktab] = esc_url(add_query_arg(array(
			'tab' => $ktab
		)));
		$active = "";
		if ($active_tab == $ktab) {
			$active = "nav-tab-active";
		}
		echo '<a href="' . esc_url($tab_url[$ktab]) . '" class="nav-tab ' . $active . '" id="nav-' . $ktab . '">' . $mytab . '</a>';
	}
	echo '</h2>';
	echo '<div class="tab-content" id="tab-getting-started"';
	if ("getting-started" != $active_tab) {
		echo 'style="display:none;"';
	}
	echo '>';
?>
<div style="height:25px" id="rtop"></div><div class="toc"><h3 style="color:#0073AA;text-align:left;">Quickstart</h3><ul><li><a href="#gs-sec-71">WP Easy Events Community Introduction</a></li>
<li><a href="#gs-sec-73">EMD CSV Import Export Extension helps you get your data in and out of WordPress quickly, saving you ton of time</a></li>
<li><a href="#gs-sec-82">EMD QR Code Extension for easy and fast ticket processing</a></li>
<li><a href="#gs-sec-72">EMD Advanced Filters and Columns Extension for finding what's important faster</a></li>
<li><a href="#gs-sec-84">WP Easy Events Easy Digital Downloads Extension for integrated event ticket sales</a></li>
<li><a href="#gs-sec-85">WP Easy Events WooCommerce Extension for integrated event ticket sales</a></li>
<li><a href="#gs-sec-83">WP Easy Events Pro for all-in-one event management and ticketing system for successful events</a></li>
<li><a href="#gs-sec-140">EMD MailChimp Extension - A powerful way to promote your future events to the very people who already attended one of yours</a></li>
</ul></div><div class="quote">
<p class="about-description">The secret of getting ahead is getting started - Mark Twain</p>
</div>
<div class="getting-started emd-section changelog getting-started getting-started-71" style="margin:0;background-color:white;padding:10px"><div style="height:40px" id="gs-sec-71"></div><h2>WP Easy Events Community Introduction</h2><div class="emd-yt" data-youtube-id="jO2VopUTBhI" data-ratio="16:9">loading...</div><div class="sec-desc"><p>Watch WP Easy Events Community introduction video to learn about the plugin features and configuration.</p></div></div><div style="margin-top:15px"><a href="#rtop" class="top">Go to top</a></div><hr style="margin-top:40px"><div class="getting-started emd-section changelog getting-started getting-started-73" style="margin:0;background-color:white;padding:10px"><div style="height:40px" id="gs-sec-73"></div><h2>EMD CSV Import Export Extension helps you get your data in and out of WordPress quickly, saving you ton of time</h2><div class="emd-yt" data-youtube-id="yoSyp-zgrVA" data-ratio="16:9">loading...</div><div class="sec-desc"><p>This extension is included in the pro edition.</p>
<p>EMD CSV Import Export Extension helps bulk import, export, update entries from/to CSV files. You can also reset(delete) all data and start over again without modifying database. The export feature is also great for backups and archiving old or obsolete data.</p>
<p><a href="https://emdplugins.com/plugins/emd-csv-import-export-extension/?pk_campaign=emdimpexp-buybtn&pk_kwd=wp-easy-events-resources"><img src="https://emd-plugins.s3.amazonaws.com/button_buy-now.png"></a></p></div></div><div style="margin-top:15px"><a href="#rtop" class="top">Go to top</a></div><hr style="margin-top:40px"><div class="getting-started emd-section changelog getting-started getting-started-82" style="margin:0;background-color:white;padding:10px"><div style="height:40px" id="gs-sec-82"></div><h2>EMD QR Code Extension for easy and fast ticket processing</h2><div class="emd-yt" data-youtube-id="AfPAiXseYZY" data-ratio="16:9">loading...</div><div class="sec-desc"><p>This extension is included in the pro edition.</p>
<p>Creates a QR codes based check-in, check out system for the community edition of WP Easy Events WordPress plugin.</p>
<ul>
<li>Enable QR code processing in event ticket pages to check in attendees</li>
<li>Only authorized logged-in users, admins and users who belong to event staff role, can process check-ins</li>
<li>Any QR code reader app, available freely most app stores, can be used for processing</li>
<li>Once attendee checks in, subsequent check-ins are not allowed</li>
</ul>
<div style="margin:25px"><a href="https://emdplugins.com/plugins/emd-qr-code-extension/?pk_campaign=emd-qr-buybtn&pk_kwd=wp-easy-events-resources"><img src="https://emd-plugins.s3.amazonaws.com/button_buy-now.png"></a></div></div></div><div style="margin-top:15px"><a href="#rtop" class="top">Go to top</a></div><hr style="margin-top:40px"><div class="getting-started emd-section changelog getting-started getting-started-72" style="margin:0;background-color:white;padding:10px"><div style="height:40px" id="gs-sec-72"></div><h2>EMD Advanced Filters and Columns Extension for finding what's important faster</h2><div class="emd-yt" data-youtube-id="GXcKKRzzsdw" data-ratio="16:9">loading...</div><div class="sec-desc"><p>This extension is included in the pro edition.</p>
<p>EMD Advanced Filters and Columns Extension for WP Easy Events Community edition helps you:</p>
<ul><li>Filter entries quickly to find what you're looking for</li>
<li>Save your frequently used filters so you do not need to create them again</li>
<li>Sort quote request columns to see what's important faster</li>
<li>Change the display order of columns </li>
<li>Enable or disable columns for better and cleaner look </li>
<li>Export search results to PDF or CSV for custom reporting</li></ul><div style="margin:25px"><a href="https://emdplugins.com/plugins/emd-advanced-filters-and-columns-extension/?pk_campaign=emd-afc-buybtn&pk_kwd=wp-easy-events-resources"><img src="https://emd-plugins.s3.amazonaws.com/button_buy-now.png"></a></div></div></div><div style="margin-top:15px"><a href="#rtop" class="top">Go to top</a></div><hr style="margin-top:40px"><div class="getting-started emd-section changelog getting-started getting-started-84" style="margin:0;background-color:white;padding:10px"><div style="height:40px" id="gs-sec-84"></div><h2>WP Easy Events Easy Digital Downloads Extension for integrated event ticket sales</h2><div class="emd-yt" data-youtube-id="RwFo2DXWtfE" data-ratio="16:9">loading...</div><div class="sec-desc"><p>Easy Digital Downloads Extension allows to sell event tickets using Easy Digital Downloads. This video shows how to create and configure Event tickets as downloads in EDD and link them to the related events to make them available for purchase.</p><p>Features Summary:</p><ul><li>Collect ticket payments using Easy Digital Downloads WordPress plugin</li><li>Connect events to tickets easily</li><li>Ajax powered, smooth, fully integrated checkout process</li><li>Supports simple, grouped and variable priced tickets</li><li>Integrated ticket inventory management system through EDD</li><li>All EDD ticket orders are linked to events after order is completed.</li><li>Sell tickets and other products at the same time</li><li>After Easy Digital Downloads order completed, attendee gets <a href="/plugins/wp-easy-events-professional/#advanced-attendee-management" title="WP Easy Events ProfessionalWordPress Plugin Easy Digital Downloads">fully customizable email notification</a> with a link to event ticket</li></ul><div style="margin:25px"><a href="https://emdplugins.com/plugins/wp-easy-events-easy-digital-downloads-extension/?pk_campaign=wpee-edd-buybtn&pk_kwd=wp-easy-events-resources"><img src="https://emd-plugins.s3.amazonaws.com/button_buy-now.png"></a></div></div></div><div style="margin-top:15px"><a href="#rtop" class="top">Go to top</a></div><hr style="margin-top:40px"><div class="getting-started emd-section changelog getting-started getting-started-85" style="margin:0;background-color:white;padding:10px"><div style="height:40px" id="gs-sec-85"></div><h2>WP Easy Events WooCommerce Extension for integrated event ticket sales</h2><div class="emd-yt" data-youtube-id="nJxFFQdEFb8" data-ratio="16:9">loading...</div><div class="sec-desc"><p>WooCommerce Extension allows to sell event tickets using WooCommerce . This video shows how to configure tickets as WooCommerce products and link events to display add to cart buttons in event pages.The WooCommerce Extension can also be used in WP Easy Events Pro WordPress Plugin.</p><p>Features Summary:</p><ul><li>Collect ticket payments using WooCommerce WordPress plugin</li><li>Connect events to tickets easily</li><li>Ajax powered, smooth, fully integrated checkout process</li><li>Supports simple, grouped and variable priced tickets</li><li>Integrated ticket inventory management system through WooCommerce</li><li>All WooCommerce ticket orders are linked to events after order is completed.</li><li>Sell tickets and other products at the same time</li><li>After WooCommerce order completed, attendee gets <a href="/plugins/wp-easy-events-professional/#advanced-attendee-management" title="WP Easy Events ProfessionalWordPress Plugin WooCommerce extension">fully customizable email notification</a> with a link to event ticket</li></ul><div style="margin:25px"><a href="https://emdplugins.com/plugins/wp-easy-events-woocommerce-extension/?pk_campaign=wpee-woo-buybtn&pk_kwd=wp-easy-events-resources"><img src="https://emd-plugins.s3.amazonaws.com/button_buy-now.png"></a></div></div></div><div style="margin-top:15px"><a href="#rtop" class="top">Go to top</a></div><hr style="margin-top:40px"><div class="getting-started emd-section changelog getting-started getting-started-83" style="margin:0;background-color:white;padding:10px"><div style="height:40px" id="gs-sec-83"></div><h2>WP Easy Events Pro for all-in-one event management and ticketing system for successful events</h2><div class="emd-yt" data-youtube-id="TqpkUi3p7ik" data-ratio="16:9">loading...</div><div class="sec-desc"><p>WP Easy Events WordPress plugin offers fully featured event registration and ticketing system that allows promotion, management and hosting of successful events all in one package.</p>
<p>Feature summary:</p>
<ul>
<li>Create beautiful, customized event registration pages and ticketing experience for successful events</li>
<li>Create and manage venues, organizers, performers and attendees</li>
<li>Promote events and engage attendees using integrated powerful social media sharing and rating system</li>
<li>Collect payments online using WooCommerce or Easy Digital Downloads ecommerce plugins (sold separately) or simply process registrations</li>
<li>Fully responsive interface that matches your brand perfectly</li>
<li>Advanced custom reporting and real-time analytics to get the insights you need to increase attendance</li>
</ul>
<div style="margin:25px"><a href="https://emdplugins.com/plugins/wp-easy-events-professional/?pk_campaign=wpeepro-buybtn&pk_kwd=wp-easy-events-resources"><img src="https://emd-plugins.s3.amazonaws.com/button_buy-now.png"></a></div></div></div><div style="margin-top:15px"><a href="#rtop" class="top">Go to top</a></div><hr style="margin-top:40px"><div class="getting-started emd-section changelog getting-started getting-started-140" style="margin:0;background-color:white;padding:10px"><div style="height:40px" id="gs-sec-140"></div><h2>EMD MailChimp Extension - A powerful way to promote your future events to the very people who already attended one of yours</h2><div class="emd-yt" data-youtube-id="Oi_c-0W1Sdo" data-ratio="16:9">loading...</div><div class="sec-desc"><p>MailChimp is an email marketing service to send email campaigns. EMD MailChimp Extension allows you to build email list based on your event registrations.</p><div style="margin:25px"><a href="https://emdplugins.com/plugins/emd-mailchimp-extension/?pk_campaign=emd-mailchimp-buybtn&pk_kwd=wp-easy-events-resources"><img src="https://emd-plugins.s3.amazonaws.com/button_buy-now.png"></a></div></div></div><div style="margin-top:15px"><a href="#rtop" class="top">Go to top</a></div><hr style="margin-top:40px">

<?php echo '</div>';
	echo '<div class="tab-content" id="tab-whats-new"';
	if ("whats-new" != $active_tab) {
		echo 'style="display:none;"';
	}
	echo '>';
?>
<p class="about-description">WP Easy Events Community V3.1.0 offers many new features, bug fixes and improvements.</p>


<h3 style="font-size: 18px;font-weight:700;color: white;background: #708090;padding:5px 10px;width:155px;border: 2px solid #fff;border-radius:4px;text-align:center">3.1.0 changes</h3>
<div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-225" style="margin:0">
<h3 style="font-size:18px;" class="fix"><div  style="font-size:110%;color:#c71585"><span class="dashicons dashicons-admin-tools"></span> FIX</div>
WP Sessions security vulnerability</h3>
<div ></a></div></div></div><hr style="margin:30px 0"><div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-224" style="margin:0">
<h3 style="font-size:18px;" class="new"><div style="font-size:110%;color:#00C851"><span class="dashicons dashicons-megaphone"></span> NEW</div>
Added support for EMD MailChimp extension</h3>
<div ></a></div></div></div><hr style="margin:30px 0">
<h3 style="font-size: 18px;font-weight:700;color: white;background: #708090;padding:5px 10px;width:155px;border: 2px solid #fff;border-radius:4px;text-align:center">3.0.0 changes</h3>
<div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-87" style="margin:0">
<h3 style="font-size:18px;" class="tweak"><div  style="font-size:110%;color:#33b5e5"><span class="dashicons dashicons-admin-settings"></span> TWEAK</div>
Many minor fixes and improvements</h3>
<div ></a></div></div></div><hr style="margin:30px 0"><div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-86" style="margin:0">
<h3 style="font-size:18px;" class="new"><div style="font-size:110%;color:#00C851"><span class="dashicons dashicons-megaphone"></span> NEW</div>
EMD CSV Import Export Extension for bulk importing/exporting events, organizers, venues, attendees and the relationship data among each other</h3>
<div ></a></div></div></div><hr style="margin:30px 0"><div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-85" style="margin:0">
<h3 style="font-size:18px;" class="new"><div style="font-size:110%;color:#00C851"><span class="dashicons dashicons-megaphone"></span> NEW</div>
Added featured image for organizers and venues</h3>
<div ></a></div></div></div><hr style="margin:30px 0"><div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-84" style="margin:0">
<h3 style="font-size:18px;" class="new"><div style="font-size:110%;color:#00C851"><span class="dashicons dashicons-megaphone"></span> NEW</div>
Ability to limit event forms to logged-in users only from plugin settings.</h3>
<div ></a></div></div></div><hr style="margin:30px 0"><div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-83" style="margin:0">
<h3 style="font-size:18px;" class="new"><div style="font-size:110%;color:#00C851"><span class="dashicons dashicons-megaphone"></span> NEW</div>
Ability enable/disable any field and taxonomy from backend and/or frontend</h3>
<div ></a></div></div></div><hr style="margin:30px 0"><div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-82" style="margin:0">
<h3 style="font-size:18px;" class="new"><div style="font-size:110%;color:#00C851"><span class="dashicons dashicons-megaphone"></span> NEW</div>
EMD Widget area to include sidebar widgets in plugin pages</h3>
<div ></a></div></div></div><hr style="margin:30px 0"><div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-81" style="margin:0">
<h3 style="font-size:18px;" class="new"><div style="font-size:110%;color:#00C851"><span class="dashicons dashicons-megaphone"></span> NEW</div>
Ability to set page template for event, venue, organizer pages. Options are sidebar on left, sidebar on right or full width</h3>
<div ></a></div></div></div><hr style="margin:30px 0"><div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-80" style="margin:0">
<h3 style="font-size:18px;" class="new"><div style="font-size:110%;color:#00C851"><span class="dashicons dashicons-megaphone"></span> NEW</div>
Added ability to use EMD or theme templating system</h3>
<div ></a></div></div></div><hr style="margin:30px 0"><div class="wp-clearfix"><div class="changelog emd-section whats-new whats-new-88" style="margin:0">
<h3 style="font-size:18px;" class="tweak"><div  style="font-size:110%;color:#33b5e5"><span class="dashicons dashicons-admin-settings"></span> TWEAK</div>
Added From name and address to RSVP notifications</h3>
<div ></a></div></div></div><hr style="margin:30px 0">
<?php echo '</div>';
	echo '<div class="tab-content" id="tab-resources"';
	if ("resources" != $active_tab) {
		echo 'style="display:none;"';
	}
	echo '>';
?>
<div style="height:25px" id="ptop"></div><div class="toc"><h3 style="color:#0073AA;text-align:left;">Upgrade your game for better results</h3><ul><li><a href="#gs-sec-70">Extensive documentation is available</a></li>
<li><a href="#gs-sec-74">How to resolve theme related issues</a></li>
</ul></div><div class="emd-section changelog resources resources-70" style="margin:0"><div style="height:40px" id="gs-sec-70"></div><h2>Extensive documentation is available</h2><div id="gallery" class="wp-clearfix"></div><div class="sec-desc"><a href="https://docs.emdplugins.com/docs/wp-easy-events-community">WP Easy Events Community Documentation</a></div></div><div style="margin-top:15px"><a href="#ptop" class="top">Go to top</a></div><hr style="margin-top:40px"><div class="emd-section changelog resources resources-74" style="margin:0"><div style="height:40px" id="gs-sec-74"></div><h2>How to resolve theme related issues</h2><div id="gallery" class="wp-clearfix"><div class="sec-img gallery-item"><a class="thickbox tooltip" rel="gallery-74" href="https://emdsnapshots.s3.amazonaws.com/emd_templating_system.png"><img src="https://emdsnapshots.s3.amazonaws.com/emd_templating_system.png"></a></div></div><div class="sec-desc"><p>If your theme is not coded based on WordPress theme coding standards, does have an unorthodox markup or its style.css is messing up how WP Easy Events Community pages look and feel, you will see some unusual changes on your site such as sidebars not getting displayed where they are supposed to or random text getting displayed on headers etc. after plugin activation.</p>
<p>The good news is WP Easy Events Community plugin is designed to minimize theme related issues by providing two distinct templating systems:</p>
<ul>
<li>The EMD templating system is the default templating system where the plugin uses its own templates for plugin pages.</li>
<li>The theme templating system where WP Easy Events Community uses theme templates for plugin pages.</li>
</ul>
<p>The EMD templating system is the recommended option. If the EMD templating system does not work for you, you need to check "Disable EMD Templating System" option at Settings > Tools tab and switch to theme based templating system.</p>
<p>Please keep in mind that when you disable EMD templating system, you loose the flexibility of modifying plugin pages without changing theme template files.</p>
<p>If none of the provided options works for you, you may still fix theme related conflicts following the steps in <a href="https://docs.emdplugins.com/docs/wp-easy-events-community">WP Easy Events Community Documentation - Resolving theme related conflicts section.</a></p>

<div class="quote">
<p>If you’re unfamiliar with code/templates and resolving potential conflicts, <a href="https://emdplugins.com/open-a-support-ticket/?pk_campaign=raq-hireme&ticket_topic=pre-sales-questions"> do yourself a favor and hire us</a>. Sometimes the cost of hiring someone else to fix things is far less than doing it yourself. We will get your site up and running in no time.</p>
</div></div></div><div style="margin-top:15px"><a href="#ptop" class="top">Go to top</a></div><hr style="margin-top:40px">
<?php echo '</div>'; ?>

<?php echo '</div>';
}
