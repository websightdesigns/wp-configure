<?php
/**
* @package WP_Configure
* @version 0.5
*/
/*
Plugin Name: WP-Configure
Description: This plugin allows you to activate the plugin for new sites to automatically configure the wordpress settings.
Author: WebSight Designs
Version: 0.1
Author URI: http://websightdesigns.com/
License: GPL2
*/

/*
Copyright 2013  WebSight Designs  (email : http://websightdesigns.com/contact/)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Runs when plugin is activated */
register_activation_hook(__FILE__,'wp_configure_install');

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'wp_configure_remove' );

function wp_configure_install() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function wp_configure_remove() {

}



/* Configure */
$timezone = 'America/Denver';

// Find and delete the WP default post 'Hello world!'
$defaultPost = get_page_by_path('hello-world',OBJECT,'post');
if ($defaultPost){
	wp_delete_post($defaultPost->ID,true);
}

// Find and delete the WP default 'Sample Page'
$defaultPage = get_page_by_title( 'Sample Page' );
if ($defaultPage) {
	wp_delete_post( $defaultPage->ID );
}

// Create a 'Home' page if it doesn't exist, and execute any initial settings once
if(!get_page_by_title('Home')) {
	global $wpdb;
	// First Page
	$first_page = get_site_option( 'first_page', $first_page );
	$first_post_guid = get_option('home') . '/?page_id=1';
	$wpdb->insert( $wpdb->posts, array(
		'post_date' => $now,
		'post_date_gmt' => $now_gmt,
		'post_content' => '',
		'post_excerpt' => '',
		'post_title' => __( 'Home' ),
		/* translators: Default page slug */
		'post_name' => __( 'home' ),
		'post_modified' => $now,
		'post_modified_gmt' => $now_gmt,
		'guid' => $first_post_guid,
		'post_type' => 'page',
		'to_ping' => '',
		'pinged' => '',
		'comment_status' => 'closed',
		'post_content_filtered' => ''
	));

	$wpdb->insert( $wpdb->postmeta, array( 'post_id' => 1, 'meta_key' => '_wp_page_template', 'meta_value' => 'default' ) );


	// Setup Theme to use a static front page
	$about = get_page_by_title( 'Home' );
	update_option( 'page_on_front', $about->ID );
	update_option( 'show_on_front', 'page' );

	// Setup a Menu
	$menu_name = "Primary Menu";
	$menu_id = wp_create_nav_menu($menu_name);

	// Get rid of 'Uncategorised' category and replace with 'Blog' as default
	wp_update_term(1, 'category', array(
		'name' => 'Blog',
		'slug' => 'blog',
		'description' => 'Blog'
	));

	// Set avatars to hidden
	update_option( 'show_avatars', 0);

	// Set Timezone
	update_option( 'timezone_string', $timezone );

	// Start of the Week
	// 0 is Sunday, 1 is Monday and so on
	update_option( 'start_of_week', 1 );

	// Increase the Size of the Post Editor
	update_option( 'defaultPost_edit_rows', 40 );

	// Don't Organize Uploads by Date
	update_option('uploads_use_yearmonth_folders',0);

	// Update Permalinks
	update_option( 'selection','custom' );
	update_option( 'permalink_structure','/%postname%/' );


	update_option( 'comment_moderation', 1 );

	/** Before a comment appears the comment author must have a previously approved comment: false */
	update_option( 'comment_whitelist', 0 );


	/** Allow people to post comments on new articles (this setting may be overridden for individual articles): false */
	update_option( 'default_comment_status', 0 );


	// Disable Smilies
	update_option( 'use_smilies', 0 );

	//Activate once - set my default theme/childtheme
	define( 'WP_DEFAULT_THEME', 'customtheme-child' );
	update_option('template', 'customtheme');
	update_option('stylesheet', 'customtheme-child');
}

// Hide welcome panels (doesn't seem to work often)
// update_user_meta( $user_id, 'show_welcome_panel', 0 );


// Remove the default dashboard widgets
function remove_dashboard_meta() {
	remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal'); // Removes the 'incoming links' widget
	remove_meta_box('dashboard_plugins', 'dashboard', 'normal'); // Removes the 'plugins' widget
	remove_meta_box('dashboard_primary', 'dashboard', 'normal'); // Removes the 'WordPress News' widget
	remove_meta_box('dashboard_secondary', 'dashboard', 'normal'); // Removes the secondary widget
	remove_meta_box('dashboard_quick_press', 'dashboard', 'side'); // Removes the 'Quick Draft' widget
	remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side'); // Removes the 'Recent Drafts' widget
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); // Removes the 'Activity' widget
	remove_meta_box('dashboard_right_now', 'dashboard', 'normal'); // Removes the 'At a Glance' widget
	remove_meta_box('dashboard_activity', 'dashboard', 'normal'); // Removes the 'Activity' widget (since 3.8)
}
add_action('admin_init', 'remove_dashboard_meta');

// Display a dashboard widget with my own welcome message
function example_dashboard_widget_function() {
	echo '<p>Welcome to your new wordpress website.</p>
	<p>Wordpress is a powerful platform for you to manage your content.</p>
	<p>Use the links to the left to manage parts of your site.</p>
	<p>If you need technical support, <a href="http://websightdesigns.com/contact/" target="_blank">contact us</a>.</p>';
}

// Create the dashboard widget above so it can be selected
function example_add_dashboard_widgets() {
	wp_add_dashboard_widget('example_dashboard_widget', 'Welcome To Your Wordpress CMS', 'example_dashboard_widget_function');
}
add_action('wp_dashboard_setup', 'example_add_dashboard_widgets' );


/**
 * Rewrite theme directory paths
 * NOTE: You must click "Save" in Settings > Permalinks to write these new rules to .htaccess
 */
add_action('generate_rewrite_rules', 'themes_dir_add_rewrites');
function themes_dir_add_rewrites() {
	$theme_parts = explode( '/themes/', get_stylesheet_directory() );
	$theme_name = $theme_parts[1];
	global $wp_rewrite;
	$new_non_wp_rules = array(
		'css/(.*)'     => 'wp-content/themes/'. $theme_name . '/css/$1',
		'js/(.*)'      => 'wp-content/themes/'. $theme_name . '/js/$1',
		'scripts/(.*)' => 'wp-content/themes/'. $theme_name . '/scripts/$1',
		'images/(.*)'  => 'wp-content/themes/'. $theme_name . '/img/$1',
	);
	$wp_rewrite->non_wp_rules += $new_non_wp_rules;
}


// // Remove detailed login errors so people can't get hints (i.e don't tell them if it was a wrong password vs wrong username)
// if (!function_exists('login_error_message')) {
// 	add_filter('login_errors','login_error_message');
// 	function login_error_message($error){
// 		$error = "<strong>ERROR:</strong> Your login details are incorrect.";
// 		return $error;
// 	}
// }


// // This is how I activate my ideal plugins.  They're already installed along.  Simply customise the list to activate
// $plugins = FALSE;
// $plugins = get_option('active_plugins'); // get active plugins
// if ( $plugins ) {
// 	// plugins to active

// 	// $plugins_to_active = array(
// 	// 	'bb-plugin/fl-builder.php',
// 	// 	'better-wp-security/better-wp-security.php',
// 	// 	'contact-form-7/wp-contact-form-7.php',
// 	// 	'duplicator/duplicator.php',
// 	// 	'ewww-image-optimizer/ewww-image-optimizer.php',
// 	// 	'wordpress-seo/wp-seo.php',
// 	// 	'wp-super-cache/wp-cache.php'
// 	// );

// 	$plugins_to_active = array(
// 		'WP-Configure/WP-Configure.php'
// 	);

// 	foreach ( $plugins_to_active as $plugin ) {
// 		if ( ! in_array( $plugin, $plugins ) ) {
// 			array_push( $plugins, $plugin );
// 			update_option( 'active_plugins', $plugins );
// 		}
// 	}
// } // end if $plugins


// // Remove upgrade notification
// function no_update_notification() {
// 	remove_action('admin_notices', 'update_nag', 3);
// }
// add_action('admin_notices', 'no_update_notification', 1);


// // This loads custom CSS on the login-page.  I've got no image and just a white background atm.
// function new_login_styles() {
// 	echo '<style type="text/css">
// 	h1 a {background-image: none !important;}
// 	body {background-color: white;}
// </style>';
// }
// add_action('login_head','new_login_styles');


// // This loads custom CSS on the theme-select page.
// // Since I have just child theme & template theme, I set it to hide the template theme
// // and some other various css tweaks for front-end friendliness.
// function new_theme_styles() {
// 	echo '<style type="text/css">
// 	h3#bb-theme-name {visibility: hidden !important;}
// 	h3#bb-theme-name:before {content: "Core Template Files" !important; visibility: visible;}
// 	.theme:nth-child(2){display: none; visibility: hidden;}
// 	.theme.active {pointer-events: none;}
// 	a.button.button-primary.customize.load-customize.hide-if-no-customize{display: none;}
// </style>';

// }
// add_action( 'load-themes.php', 'new_theme_styles' );