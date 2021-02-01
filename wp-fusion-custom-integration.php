<?php

/*
Plugin Name: WP Fusion - Custom Integration
Description: Boostrap for adding a new plugin integration module to WP Fusion
Plugin URI: https://verygoodplugins.com/
Version: 1.0.1
Author: Very Good Plugins
Author URI: https://verygoodplugins.com/
*/

/**
 * @copyright Copyright (c) 2021. All rights reserved.
 *
 * @license   Released under the GPL license http://www.opensource.org/licenses/gpl-license.php
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

// deny direct access
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


if ( ! class_exists( 'WPF_My_Plugin_Name' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-my-plugin-slug.php';
}

/**
 * Register integration.
 *
 * Add our custom integration class to the list of registered integrations.
 *
 * @since  1.0.0
 *
 * @param  array $integrations The array of registered CRM modules.
 * @return array $integrations The array of registered CRM modules.
 */

function wpf_register_integration( $integrations ) {

	$integrations['my-plugin-slug'] = 'My/PluginDependencyClass';

	return $integrations;

}

add_filter( 'wpf_integrations', 'wpf_register_integration' );
