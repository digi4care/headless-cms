<?php
/**
 * Plugin Name: Enhanced Headless CMS for WooCommerce & WordPress 
 * Description: A WordPress plugin designed to enhance the use of WordPress as a headless CMS for any front-end environment, supporting both REST API and GraphQL. This plugin is versatile, catering not only to standard WordPress sites but also to WooCommerce-powered e-commerce platforms, providing a range of features for efficient and flexible integration.
 * Plugin URI:  https://github.com/digi4care/headless-cms/
 * Author:      Imran Sayed
 * Author URI:  https://codeytek.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version:     2.0.5
 * Text Domain: headless-cms
 *
 * @package headless-cms
 */

define( 'HEADLESS_CMS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'HEADLESS_CMS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'HEADLESS_CMS_BUILD_URI', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets/build' );
define( 'HEADLESS_CMS_BUILD_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/assets/build' );
define( 'HEADLESS_CMS_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );

// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
require_once HEADLESS_CMS_PATH . '/inc/helpers/autoloader.php';
require_once HEADLESS_CMS_PATH . '/inc/helpers/custom-functions.php';
// phpcs:enable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant

/**
 * To load plugin manifest class.
 *
 * @return void
 */
function headless_cms_features_plugin_loader() {
	\Headless_CMS\Features\Inc\Plugin::get_instance();
}

headless_cms_features_plugin_loader();
