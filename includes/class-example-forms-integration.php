<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Example Forms integration
 *
 * @since 1.1.0
 */

class WPF_Example_Forms_Integration extends WPF_Integrations_Base {

	/**
	 * This identifies the integration internally and makes it available at
	 * wp_fusion()->integrations->{'my-plugin-slug'}
	 *
	 * @var  string
	 * @since 1.1.0
	 */

	public $slug = 'my-forms-plugin';

	/**
	 * The human-readable name of the integration.
	 *
	 * @var  string
	 * @since 1.1.0
	 */

	public $name = 'My Forms Plugin';

	/**
	 * Get things started.
	 *
	 * @since 1.1.0
	 */

	public function init() {

		// Add the settings

		add_action( 'my_form_settings_panel', array( $this, 'form_settings_panel' ), 10, 2 );

		// Process the form submission

		add_action( 'my_form_submission', array( $this, 'form_submission' ), 10, 3 );

	}


	/**
	 * Form settings panel.
	 *
	 * Add the WP Fusion feed settings to your form settings panel. This will
	 * vary depending on how your form plugin registers and stores its settings.
	 * The example here is borrowed from WP Fusion's Gravity Forms integration.
	 *
	 * @since 1.1.0
	 *
	 * @see wpf_render_crm_field_select()
	 * @see wpf_render_tags_multiselect()
	 *
	 * @param int   $form_id The form ID.
	 * @param int   $feed_id The feed ID.
	 * @return mixed HTML output.
	 */

	public function form_settings_panel( $form_id, $feed_id ) {

		$defaults = array(
			'fields_map' => array(),
			'apply_tags' => array(),
			'add_only'   => false,
		);

		$settings = wp_parse_args( get_post_meta( $feed_id, 'wpf_feed_settings', true ), $defaults );

		// Field mapping

		echo '<table class="settings-field-map-table wpf-field-map" cellspacing="0" cellpadding="0">';

		echo '<tbody>';

		echo '<tr><td><strong><br />' . __( 'Form Fields', 'wp-fusion' ) . '</strong></td></tr>';

		foreach ( my_get_form_fields( $form_id ) as $field ) {

			if ( ! isset( $settings['fields_map'][ $field->id ] ) ) {
				$settings['fields_map'][ $field->id ] = array(
					'crm_field' => false,
					'type'      => false,
				);
			}

			echo '<tr>';
			echo '<td><label>' . $field->label . '<label></td>';
			echo '<td>';

			wpf_render_crm_field_select( $settings['fields_map'][ $field->id ]['crm_field'], 'fields_map', $field->id ); // This renders the CRM field select dropdown with name="fields_map[$field_id][crm_field]"

			echo '<input type="hidden" name="fields_map[' . $field->id . '][type]" value="' . $field->type . '" />'; // It can be helpful to keep track of field types

			echo '</td>';
			echo '</tr>';

		}

		echo '</tbody></table>';

		// Apply tags

		echo '<label for"apply_tags">' . __( 'Apply tags', 'wp-fusion' ) . '</label>';

		$args = array(
			'setting'   => $settings['apply_tags'],
			'meta_name' => 'apply_tags',
		);

		wpf_render_tag_multiselect( $args ); // This renders the CRM tag multiselect

		echo '<span class="description">' . sprintf( __( 'The selected tags will be applied to the contact in %s when the form is submitted', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';

		// Add only?
		//
		// This is optional but lets you specify whether WP Fusion should be
		// allowed to update existing contacts (by email address match), vs only
		// creating new records.

		echo '<label for"add_only">' . __( 'Add Only', 'wp-fusion' ) . '</label>';

		echo '<input type="checkbox" id="add_only" name="add_only" value="1" ' . checked( $settings['add_only'], true, false ) . ' />';

		echo '<span class="description">' . __( 'Only add new contacts, don\'t update existing ones.', 'wp-fusion' ) . '</span>';

	}

	/**
	 * Form submission.
	 *
	 * Process the submitted data and pass it on to
	 * WPF_Forms_Helper::process_form_data() for syncing to the CRM.
	 *
	 * @since 1.1.0
	 *
	 * @see   WPF_Forms_Helper::process_form_data()
	 * @see   wpf_format_field_value
	 *
	 * @param int   $form_id        The form ID.
	 * @param int   $feed_id        The feed ID.
	 * @param int   $entry_id       The entry ID.
	 * @param array $submitted_data The submitted form data.
	 */

	public function form_submission( $form_id, $feed_id, $entry_id, $submitted_data ) {

		$defaults = array(
			'fields_map' => array(),
			'apply_tags' => array(),
			'add_only'   => false,
		);

		$settings = wp_parse_args( get_post_meta( $feed_id, 'wpf_feed_settings', true ), $defaults );

		$update_data   = array(); // data to sync to the CRM
		$email_address = false; // the primary email used for contact lookups

		foreach ( $settings['fields_map'] as $field_id => $field ) {

			if ( isset( $submitted_data[ $field_id ] ) ) {

				/**
				 * Formats the value for the CRM based on the field type.
				 *
				 * @since 1.1.0
				 *
				 * @link  https://wpfusion.com/documentation/filters/wpf_format_field_value/
				 *
				 * @param mixed  $value     The field value.
				 * @param string $type      The field type.
				 * @param string $crm_field The field to sync the data to in the CRM.
				 */
				$value = apply_filters( 'wpf_format_field_value', $submitted_data[ $field_id ], $field['type'], $field['crm_field'] );

				$update_data[ $field['crm_field'] ] = $value;

				// For determining the email address, we'll try to find a field
				// mapped to the main lookup field in the CRM, but if not we'll take
				// the first email address on the form.

				if ( is_email( $value ) && wpf_get_lookup_field() == $field['crm_field'] ) {
					$email_address = $value;
				} elseif ( false == $email_address && 'email' == $field['type'] && is_email( $value ) ) {
					$email_address = $value;
				}
			}
		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => isset( $settings['apply_tags'] ) ? $settings['apply_tags'] : array(),
			'add_only'         => isset( $settings['add_only'] ) ? true : false,
			'integration_slug' => $this->slug,
			'integration_name' => $this->name,
			'form_id'          => $form_id,
			'form_title'       => get_the_title( $form_id ),
			'form_edit_link'   => admin_url( 'post.php?post=' . $form_id . '&action=edit' ),
		);

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

		if ( is_wp_error( $contact_id ) ) {

			// Handle the error

		} else {

			// Mark the feed as complete, optionally add a note with the status

			update_post_meta( $entry_id, '_wpf_complete', true );

			update_post_meta( $entry_id, '_wpf_contact_id', $contact_id );

		}

	}

}

new WPF_Example_Forms_Integration();
