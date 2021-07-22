<?php

/*
Plugin Name: WP Fusion - Custom Integration
Description: Boostrap for adding a new plugin integration module to WP Fusion
Plugin URI: https://wpfusion.com/
Version: 1.1.0
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

/**
 * Include the integration classes after WP Fusion has loaded.
 *
 * @since 1.0.2
 */

function wpf_include_custom_integration() {

	if ( class_exists( 'My/PluginDependencyClass' ) ) {
		include_once dirname( __FILE__ ) . '/includes/class-example-ecommerce-integration.php';
	}

	if ( class_exists( 'MyFormsPlugin' ) ) {
		include_once dirname( __FILE__ ) . '/includes/class-example-forms-integration.php';
	}

	if ( class_exists( 'MyMembershipPlugin' ) ) {
		include_once dirname( __FILE__ ) . '/includes/class-example-membership-integration.php';
	}

}

add_action( 'wp_fusion_init', 'wpf_include_custom_integration' );
