<?php
/*
Plugin Name: Indigestion+
Plugin URI: http://journal.goblindegook.net/development/wp/indigestion-plus/
Description: Generates a periodic digest from different customisable feed sources.  Supports Delicious, Google Reader, Flickr, Picasa and more.
Version: 0.5
Author: Luís Rodrigues
Author URI: http://goblindegook.net
*/

/*  Copyright 2009  Luís Rodrigues  (email: goblindegook@goblindegook.net)
	
	Based on Indigestion by Evelio Tarazona Cáceres:
	http://clearfix.net/labs/indigestion
	
	Based on Digest Post by Frederic Wenzel:
	http://wordpress.org/extend/plugins/digest-post/
	
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once('indigestion-plus-main.php');

$iPlusMain = new IPlusMain;

register_activation_hook    ( __FILE__, array(&$iPlusMain, 'activate') );
register_deactivation_hook  ( __FILE__, array(&$iPlusMain, 'deactivate') );

add_action( 'indigestion_plus_run', array(&$iPlusMain, 'run') );

if ( is_admin() ){
    // admin actions
    add_action( 'admin_menu', array(&$iPlusMain, 'admin_menu') );
    add_action( 'admin_init', array(&$iPlusMain, 'admin_init') );
    
    add_action( 'update_option_indigestion_plus_fetch_time',
        array(&$iPlusMain, 'refresh_schedule') );
        
} else {
    // non-admin enqueues, actions, and filters
    
}

/* plugin action links */

function iplus_plugin_actions( $links, $file ) {
 	if( $file == 'indigestion-plus/indigestion-plus.php' && function_exists( "admin_url" ) ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=indigestion_plus' ) . '">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}
	return $links;
}

add_filter( 'plugin_action_links', 'iplus_plugin_actions', 10, 2 );

