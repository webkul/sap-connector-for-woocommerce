=== SAP Connector for WooCommerce ===
Contributors: webkul
Tags: WordPress SAP, WooCommerce SAP, SAP connector, WooCommerce SAP integration, WooCommerce SAP connector
Requires at least: 6.7
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
Tested up to PHP: 8.3
WC requires at least: 10.0
WC tested up to: 10.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A powerful WooCommerce extension to sync data with a SAP B1 instance—automatically or manually.

== Description ==

This is a WordPress WooCommerce extension that lets you connect with any SAP instance and export related data with complete auto-sync and manual sync functionality.
Our WooCommerce & SAP Business One Connector delivers integrations that are fast, simple, and affordable.

For the paid version [Click here] https://store.webkul.com/SAP-WordPress-WooCommerce-Connector.html

=== Features ===

* Able to export Customer (in paid version product, category, order).
* Real-time export option is available for all the existing export options.
* Option to set a prefix for maintaining store uniqueness at the end of SAP.
* Option to select desired or default currency for product export (in paid version).
* Option to select the default warehouse while product synchronization, and real-time inventory update at the SAP B1 end (in paid version).
* Option to use a prefix for Customer and (Product sync in paid version).
* Option to select desired Business Partner, Sales Person, and Order Delivery Date for order export (in paid version).
* Able to import Product, Category from SAP to WooCommerce (in paid version).
* Able to send the Tax and Discount details of the order to the SAP end (in paid version).
* Able to send the shipping method data with selected mapping of the SAP freight field on Order Sync (in paid version).
* Option to use the Default Debtor account or enter one manually (in paid version).
* Option to add Order fee in SAP freight (in paid version).
* User-friendly alerts powered by SweetAlert JS for easy notifications and confirmations throughout the plugin.

= Pre-requisite Selections =

* It is mandatory to choose available accounts to be able to create a category at SAP.
* It is mandatory to choose a business Partner to be able to create an order at SAP.
* User has to enter the license Key, which is provided by the Seller(Webkul).

= Current Mapping =

| Syntax   | Description           |
| -------- | --------------------- |
| Product  | Items + Goods Receipt | Available in paid version
| Category | ItemsGroups           | Available in paid version
| User     | BusinessPartners      |
| Order    | Orders                | Available in paid version

= Privacy notices =

For showing extensions and the support and services menu, we are using our Webkul Services. So, confirming the privacy policies is recommended.

* Extensions [Webkul Policy](https://webkul.com/privacy-policy/)

JavaScript Libraries Used:

SweetAlert JS: Used for custom alert and confirmation messages to enhance user experience in the plugin interface.
Date Range Picker: Used for custom alert and confirmation messages to enhance user experience in the plugin interface.

= Developer Resources =

SAP Connector for WooCommerce is open-source software and is made to be extended. To reduce zip size and optimize the code, we have added minified css and js assets but developers can find non-minified (un-compressed) sources at our public here.

For showing alert and confirmation messages we have used ([SweetAlert JS ](https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.19/sweetalert2.all.min.js))
For showing Date Range Picker we have used ([Date Range Picker JS ](https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.js))
For showing Date Range Picker we have used ([Date Range Picker CSS ](https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.css))
For performing action in SAP Connector for WooCommerce we have used ([JS Script](sap-connector-for-woocommerce/assets/build/js/wksap-js-script.js))
For performing user configuration in SAP Connector for WooCommerce we have used ([JS Script](sap-connector-for-woocommerce/assets/build/js/user-config.js))

== External Service ==

These Webkul services:
- Operate in read-only mode
- Do not collect user/website data
- Only display information from Webkul servers

Service provider: Webkul Software Pvt Ltd
- [Webkul Privacy Policy](https://webkul.com/privacy-policy/)

== Installation ==

= Minimum Requirements =

* WordPress 6.7 or greater
* WooCommerce 10.0 or greater
* PHP version 7.4 or greater
* MySQL version 5.0 or greater
* WordPress Memory limit of 64 MB or greater (128 MB or higher is preferred)

1. Upload the `sap-connector-for-woocommerce` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin using the 'SAP Connector' menu
4. When the connector is installed, we have to fill in all the credentials of the SAP to connect with the org.
5. When providing the service layer URL in the connection establishment page, don't use a URL with '/b1s/v1/'.
6. When the connection is established, we have the tab of Connection Setting, Product& Category, and Order.

== Screenshots ==
1. SAP Business One configuration tab
2. SAP Business One details tab
3. Users configuration tab
4. Synchronize users page

== Frequently Asked Questions ==

= No questions asked yet =

Feel free to do so.

For any Query, please generate a ticket at [https://support.uvdesk.com/](https://support.uvdesk.com/)

== Changelog ==

= Version 1.0.0 =

- Provided options to enable and disable the use of the SAP prefix while syncing (products in paid version) and customers from WooCommerce to SAP.
- Provided an option to choose product matching criteria (Product SKU or Product ID) while syncing Products from WooCommerce to SAP (in paid version).
- Provided an option to export the Breakdown of SAP freight in the SalesOrder remarks field (in paid version).
- Provided an option to export the Order fee as SAP freight (in paid version).
- Provided an option to use the Default Debtors account or enter one manually (in paid version).

== Upgrade Notice ==

= 1.0.0 (05/09/2025) =
Initial release

