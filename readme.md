# WP Fusion - Custom Integration

Boostrap for creating a custom integration module for WP Fusion

## Getting Started

This plugin can be customized to allow [WP Fusion](https://wpfusion.com/) to sync data from other plugins bidirectionally with [50+ CRMs and marketing automation tools]((https://wpfusion.com/documentation/faq/crm-compatibility-table/)).

More info in our [Contributing Integration Modules tutorial](https://wpfusion.com/documentation/advanced-developer-tutorials/contributing-integration-modules/).

![Custom Integration Settings](https://wpfusion.com/wp-content/uploads/2021/07/custom-integration-settings-panel.jpg)

### Prerequisites

Requires [WP Fusion](https://wpfusion.com/) or [WP Fusion Lite](https://wordpress.org/plugins/wp-fusion-lite/)

### Contents

#### class-example-membership-integration.php ####

This is an example of how WP Fusion integrates with membership and/or user profile editor plugins.

* Adds a field group and meta fields to the WP Fusion [contact fields list](https://wpfusion.com/documentation/getting-started/syncing-contact-fields/).
* Registers few global settings on the main WP Fusion settings page.
* Filters POSTed form data during a user registration or profile update before the data is synced to the CRM.
* [Extracts user metadata from a custom database table](https://wpfusion.com/documentation/advanced-developer-tutorials/detecting-and-syncing-additional-fields/) when exporting users.

#### class-example-ecommerce-integration.php ####

This is an example of how WP Fusion integrates with ecommerce plugins, like [WooCommerce](https://wpfusion.com/documentation/ecommerce/woocommerce/) (complex) or [WP Simple Pay](https://wpfusion.com/documentation/ecommerce/wp-simple-pay/) (simple).

* Adds a field group and meta fields to the WP Fusion [contact fields list](https://wpfusion.com/documentation/getting-started/syncing-contact-fields/).
* Registers a global setting on the main WP Fusion settings page.
* Detects a new ecommerce order, and creates a customer in the CRM, while applying tags.
* Supports checkouts by registered users or guests.
* Includes an [order export tool](https://wpfusion.com/documentation/advanced-developer-tutorials/registering-custom-batch-operations/) for syncing historical data to the CRM.

#### class-example-forms-integration.php ####

This is an example of how WP Fusion integrates with form plugins, like [Gravity Forms](https://wpfusion.com/documentation/lead-generation/gravity-forms/).

* Registers a settings panel for mapping form fields with CRM fields.
* Detects a form submission and passes the submitted data to the active CRM.


### Installing

Upload to your /wp-content/plugins/ directory, update the examples with methods from your plugin, stir, and serve 🍸.

## Changelog

### 1.1.0 - 7/22/2021
* Added example classes for membership, ecommerce, and forms.

### 1.0.0 - 2/1/2021
* Initial release.

## Authors

* **Jack Arturo** - *Initial work* - [Very Good Plugins](https://github.com/verygoodplugins)

## License

This project is licensed under the GPL License - see the [LICENSE.md](LICENSE.md) file for details