<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Example Membership integration
 *
 * @since 1.1.0
 */

class WPF_Example_Membership_Integration extends WPF_Integrations_Base {

	/**
	 * This identifies the integration internally and makes it available at
	 * wp_fusion()->integrations->{'my-plugin-slug'}
	 *
	 * @var  string
	 * @since 1.1.0
	 */

	public $slug = 'my-membership-plugin';

	/**
	 * The human-readable name of the integration.
	 *
	 * @var  string
	 * @since 1.1.0
	 */

	public $name = 'My Membership Plugin';

	/**
	 * Get things started.
	 *
	 * @since 1.1.0
	 */

	public function init() {

		// Registration / profile update

		add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );
		add_filter( 'wpf_user_update', array( $this, 'user_register' ), 10, 2 );
		add_filter( 'wpf_get_user_meta', array( $this, 'get_user_meta' ), 10, 2 );

		// Meta fields

		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );

		// Global settings

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

	}


	/**
	 * Filter the POSTed meta fields during registration or profile update.
	 *
	 * @since  1.1.0
	 *
	 * @link https://wpfusion.com/documentation/filters/wpf_user_register/
	 * @link https://wpfusion.com/documentation/filters/wpf_user_update/
	 *
	 * @param  array $post_data  The post data.
	 * @param  int   $user_id    The user ID.
	 * @return array  The registration / profile data.
	 */

	public function user_register( $post_data, $user_id ) {

		$field_map = array(
			'username'            => 'user_login',
			'password'            => 'user_pass',
			'email'               => 'user_email',
			'custom-posted-field' => 'meta_field_key',
			'custom-calc-field'   => 'pseudo_field_key',
			'vendor-telephone'    => 'phone_number',
		);

		// map_meta_fields() is a utility for copying POSTed values into
		// their corresponding field keys for sync.

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		// Do whatever else you need to with the submitted form data here.

		return $post_data;

	}


	/**
	 * Gets metadata for a user that's calculated or not in the wp_usermeta
	 * table.
	 *
	 * @since  1.1.0
	 *
	 * @link https://wpfusion.com/documentation/filters/wpf_get_user_meta/
	 *
	 * @param  array $post_data  The user meta.
	 * @param  int   $user_id    The user ID.
	 * @return array  The user meta.
	 */

	public function get_user_meta( $user_meta, $user_id ) {

		// Get data from other database tables.

		return $user_meta;

	}


	/**
	 * Registers the meta field group on the Contact Fields tab in the WP Fusion
	 * settings.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $field_groups The field groups.
	 * @return array The field groups.
	 */

	public function add_meta_field_group( $field_groups ) {

		$field_groups[ $this->slug ] = array(
			'title'  => 'My Plugin Name',
			'fields' => array(),
		);

		return $field_groups;

	}


	/**
	 * Register the custom meta fields for sync.
	 *
	 * @since  1.1.0
	 *
	 * @link   https://wpfusion.com/documentation/filters/wpf_meta_fields/
	 *
	 * @param  array $meta_fields The meta fields.
	 * @return array The meta fields.
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$custom_fields = get_option( 'my_plugin_custom_fields', array() ); // get your plugin's available custom fields

		foreach ( $custom_fields as $key => $field ) {

			$meta_fields[ $key ] = array(
				'label' => $field['name'],
				'type'  => $field['type'],
				'group' => $this->slug,
			);

		}

		// Or, list them manually:

		$meta_fields['meta_field_key'] = array(
			'label' => 'Meta field name',
			'type'  => 'text',
			'group' => $this->slug,
		);

		$meta_fields['pseudo_field_key'] = array(
			'label'  => 'Pseudo field name',
			'type'   => 'text',
			'group'  => $this->slug,
			'pseudo' => true, // Can be mapped and synced to the CRM but will not be loaded from the CRM
		);

		return $meta_fields;

	}


	/**
	 * Add a custom field to the Integrations tab in the WP Fusion settings.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $settings  The registered settings.
	 * @param  array $options   The options in the database.
	 * @return array  The registered settings.
	 */

	public function register_settings( $settings, $options ) {

		$settings['my_plugin_header'] = array(
			'title'   => __( 'My Plugin Name', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['my_plugin_checkbox'] = array(
			'title'   => __( 'Checkbox Option', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Check this checkbox to do something in %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		$settings['my_plugin_apply_tags'] = array(
			'title'   => __( 'Apply Tags', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Apply these tags in %s when something happens.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'type'    => 'assign_tags',
			'section' => 'integrations',
		);

		$settings['my_plugin_text'] = array(
			'title'   => __( 'Text Option', 'wp-fusion' ),
			'desc'    => __( 'This will be saved as text.', 'wp-fusion' ),
			'type'    => 'text',
			'section' => 'integrations',
		);

		return $settings;

	}

}

new WPF_Example_Membership_Integration();
