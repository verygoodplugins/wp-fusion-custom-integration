<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Example Ecommerce Integration
 *
 * @since 1.1.0
 */

class WPF_Example_Ecommerce_Integration extends WPF_Integrations_Base {

	/**
	 * This identifies the integration internally and makes it available at
	 * wp_fusion()->integrations->{'my-plugin-slug'}
	 *
	 * @var  string
	 * @since 1.1.0
	 */

	public $slug = 'my-ecommerce-plugin';

	/**
	 * The human-readable name of the integration.
	 *
	 * @var  string
	 * @since 1.1.0
	 */

	public $name = 'My Ecommerce Plugin';

	/**
	 * Get things started.
	 *
	 * @since 1.1.0
	 */

	public function init() {

		// Handle checkouts

		add_action( 'my_plugin_checkout_completed', array( $this, 'process_order' ) );
		add_action( 'my_plugin_order_refunded', array( $this, 'order_refunded' ) );

		// Meta fields

		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );

		// Global settings

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		// Export options
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_example_ecommerce_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_example_ecommerce', array( $this, 'batch_step' ) );

	}

	/**
	 * Runs when a checkout has processed in your plugin. Syncs customer data to
	 * the CRM and applies configured tags.
	 *
	 * @since  1.1.0
	 *
	 * @see    get_contact_edit_url()
	 *
	 * @param  int         $order_id The order ID
	 * @return bool|string The contact ID or false.
	 */

	public function process_order( $order_id ) {

		$order = new Example_Ecommerce_Order( $order_id );

		// Create customer

		$contact_id = $this->create_update_customer( $order );

		if ( false === $contact_id ) {
			return false; // If creating the contact failed for some reason.
		}

		// Apply tags for the order

		$apply_tags = wp_fusion()->settings->get( 'my_plugin_apply_tags', array() ); // global tags

		// Maybe apply tags for the products

		foreach ( $order->get_items() as $product_id => $product ) {

			$settings = get_post_meta( $product_id, 'wpf_settings_product', true );

			if ( ! empty( $settings ) && ! empty( $settings['apply_tags'] ) ) {
				$apply_tags = array_merge( $apply_tags, $settings['apply_tags'] );
			}
		}

		$apply_tags = array_filter( array_unique( $apply_tags ) );

		/**
		 * Modify the tags to be applied to the customer before they're sent to
		 * the CRM.
		 *
		 * @since 1.1.0
		 *
		 * @link  https://wpfusion.com/documentation/filters/wpf_woocommerce_apply_tags_checkout/
		 *
		 * @param array  $apply_tags An array of tag IDs to be applied to the customer.
		 * @param object $order      The order object.
		 */

		$apply_tags = apply_filters( "wpf_{$this->slug}_apply_tags_checkout", $apply_tags, $order );

		$user_id  = $order->get_user_id();
		$order_id = $order->get_id();

		// Apply the tags
		if ( ! empty( $apply_tags ) ) {

			if ( empty( $user_id ) ) {

				// Guest checkout
				wpf_log( 'info', 0, 'Applying tags to guest checkout for contact ID ' . $contact_id . ': ', array( 'tag_array' => $apply_tags ) );
				$result = wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

			} else {

				// Registered users
				$result = wp_fusion()->user->apply_tags( $apply_tags, $user_id );

			}

			if ( is_wp_error( $result ) ) {

				// Handle errors

				$order->add_order_note( 'Error applying tags for order ID: ' . $order_id . '. ' . $result->get_error_message() );
				wpf_log( 'error', $user_id, 'Error <strong>' . $result->get_error_message() . '</strong> while applying tags: ', array( 'tag_array' => $apply_tags ) );
				return false;

			}
		}

		// Denotes that the WPF actions have already run for this order
		update_post_meta( $order_id, '_wpf_complete', true );

		/**
		 * Payment complete.
		 *
		 * Indicates that WP Fusion has finished processing the order. Could be
		 * used to bind additional functionality to the new contact record (for
		 * example marking an abandoned cart as recovered, or creating an
		 * invoice in the CRM).
		 *
		 * @since 1.1.0
		 *
		 * @link  https://wpfusion.com/documentation/actions/wpf_woocommerce_payment_complete/
		 *
		 * @param int    $order_id   The order ID.
		 * @param string $contact_id The contact ID in the CRM.
		 */

		do_action( "wpf_{$this->slug}_payment_complete", $order_id, $contact_id );

		// Get the link to edit the contact in the CRM

		$edit_url = wp_fusion()->crm_base->get_contact_edit_url( $contact_id );
		$note     = sprintf( __( 'WP Fusion order actions completed (contact ID <a href="%1$s" target="blank">#%2$s</a>).', 'wp-fusion' ), $edit_url, $contact_id );

		$order->add_order_note( $note );

		return $contact_id;

	}


	/**
	 * Creates or updates a customer in the CRM for an order.
	 *
	 * @since  3.37.25
	 *
	 * @param  object      $order  The order.
	 * @return bool|string The contact ID created in the CRM.
	 */

	public function create_update_customer( $order ) {

		/**
		 * Allows for overriding the email address used for duplicate checking
		 * when creating/updating the CRM contact record.
		 *
		 * @since 1.1.0
		 *
		 * @param string $email_address The customer's email address.
		 * @param object $order         The order.
		 */

		$email = apply_filters( "wpf_{$this->slug}_billing_email", $order->get_billing_email(), $order );

		/**
		 * Allows for overriding the user ID for the order.
		 *
		 * @since 1.1.0
		 *
		 * @link  https://wpfusion.com/documentation/filters/wpf_woocommerce_user_id/
		 *
		 * @param int    $user_id The customer's user ID.
		 * @param object $order   The order.
		 */

		$user_id  = apply_filters( "wpf_{$this->slug}_user_id", $order->get_user_id(), $order );
		$order_id = $order->get_id();

		if ( empty( $email ) && empty( $user_id ) ) {

			wpf_log( 'error', 0, 'No email address specified for order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>. Aborting' );

			// Denotes that the WPF actions have already run for this order
			update_post_meta( $order_id, '_wpf_complete', true );

			return false;

		}

		if ( user_can( $user_id, 'manage_options' ) ) { // debug notice
			wpf_log( 'notice', $user_id, 'You\'re currently logged into the site as an administrator. This checkout will update your existing contact ID #' . $contact_id . ' in ' . wp_fusion()->crm->name . '. If you\'re testing checkouts, it\'s recommended to use an incognito browser window.' );
		}

		// Get the customer data

		$customer_data = $this->get_customer_data( $order );

		// Sync it to the CRM

		if ( ! empty( $user_id ) ) {

			// Registered users

			wp_fusion()->user->push_user_meta( $user_id, $customer_data );

			$contact_id = wp_fusion()->user->get_contact_id( $user_id ); // we'll use this in the next step

		} else {

			// Helper function for creating/updating contact in the CRM from a guest checkout.

			$contact_id = $this->guest_registration( $email, $order_data );

		}

		if ( false !== $contact_id ) {

			$order->add_order_note( 'Customer synced to contact ID ' . $contact_id . ' in ' . wp_fusion()->crm->name );

			update_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', $contact_id ); // save it to the order meta in case we need it later

		}

		return $contact_id;

	}

	/**
	 * Gets an array of customer data from an order.
	 *
	 * @since  1.1.0
	 *
	 * @param  Order $order  The order.
	 * @return array The customer data.
	 */

	public function get_customer_data( $order ) {

		// Billing fields, etc

		$customer_data = $order->get_customer_data();

		// Special fields

		$customer_data['order_date']           = $order->get_date_paid();
		$customer_data['order_total']          = $order->get_total();
		$customer_data['order_id']             = $order->get_order_number();
		$customer_data['order_notes']          = $order->get_customer_note();
		$customer_data['order_payment_method'] = $order->get_payment_method_title();

		// Coupons
		if ( method_exists( $order, 'get_coupon_codes' ) ) {

			$coupons = $order->get_coupon_codes();

			if ( ! empty( $coupons ) ) {
				$customer_data['coupon_code'] = $coupons[0];
			}
		}

		/**
		 * Allows for modifying the customer data before it's synced to the CRM.
		 *
		 * @since 1.1.0
		 *
		 * @link  https://wpfusion.com/documentation/filters/wpf_woocommerce_customer_data/
		 *
		 * @param array  $customer_data The customer data.
		 * @param object $order         The order.
		 */

		return apply_filters( "wpf_{$this->slug}_customer_data", $customer_data, $order );

	}

	/**
	 * Runs when an order is refunded and removes the tags from the customer.
	 *
	 * @since 1.1.1
	 *
	 * @param int   $order_id The order ID
	 */

	public function order_refunded( $order_id ) {

		$order = new Example_Ecommerce_Order( $order_id );

		$remove_tags = array();

		// Get tags to remove

		foreach ( $order->get_items() as $product_id => $product ) {

			$settings = get_post_meta( $product_id, 'wpf_settings_product', true );

			if ( ! empty( $settings ) && ! empty( $settings['apply_tags'] ) ) {
				$remove_tags = array_merge( $remove_tags, $settings['apply_tags'] );
			}
		}

		$remove_tags = array_filter( array_unique( $remove_tags ) );

		if ( ! empty( $remove_tags ) ) {

			$user_id = $order->get_user_id();

			if ( $user_id ) {

				// Registered users

				wp_fusion()->user->remove_tags( $remove_tags, $user_id );

			} else {

				// Guests

				$contact_id = get_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', true );

				if ( $contact_id ) {
					wpf_log( 'info', 0, 'Removing tags from guest customer ' . $contact_id . ': ', array( 'tag_array' => $remove_tags ) );
					$result = wp_fusion()->crm->remove_tags( $remove_tags, $contact_id );
				}
			}
		}

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
	 * @param  array $settings The registered settings.
	 * @param  array $options  The options in the database.
	 * @return array The registered settings.
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
			'title'   => __( 'Apply Tags to Customers', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Apply these tags in %s when someone makes a purchase.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'type'    => 'assign_tags',
			'section' => 'integrations',
		);

		return $settings;

	}


	/**
	 * Registers an orders export operation.
	 *
	 * @since  1.1.0
	 *
	 * @link https://wpfusion.com/documentation/advanced-developer-tutorials/registering-custom-batch-operations/
	 *
	 * @param  array $options The options.
	 * @return array The options.
	 */

	public function export_options( $options ) {

		$options['example_ecommerce'] = array(
			'label'   => __( 'Example Ecommerce Orders', 'wp-fusion' ),
			'title'   => 'Orders',
			'tooltip' => __( 'Finds Example orders that have not been processed by WP Fusion, and adds/updates contacts while applying tags based on the products purchased.', 'wp-fusion' ),
		);

		return $options;

	}

	/**
	 * Get the list of order IDs to be exported.
	 *
	 * @since  1.1.0
	 *
	 * @return array Order IDs.
	 */

	public function batch_init() {

		$args = array(
			'post_type'      => 'example_orders_post_type',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( // optional: only sync orders that haven't been processed yet
				array(
					'key'     => '_wpf_complete',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		return get_posts( $args );

	}

	/**
	 * Processes orders one at a time.
	 *
	 * @since 1.1.0
	 *
	 * @param int   $order_id The order ID.
	 */

	public function batch_step( $order_id ) {

		$this->process_order( $order_id );

	}

}

new WPF_Example_Ecommerce_Integration();
