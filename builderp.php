<?php
/**
 * Plugin bootstrap for BuildErp.
 *
 * @package BuildErp
 */

/**
 * Plugin Name: BuildErp - Construction ERP
 * Plugin URI: https://example.com/builderp
 * Description: A comprehensive WordPress-native construction ERP system for managing employees, attendance, payroll, expenses, quotations, invoices, and site profitability.
 * Version: 1.0.0
 * Author: Your Company Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aic_builderp
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants.
 */
define( 'BERP_VERSION', '1.0.0' );
define( 'BERP_PLUGIN_FILE', __FILE__ );
define( 'BERP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BERP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BERP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load core class.
require_once BERP_PLUGIN_DIR . 'includes/class-builderp.php';
require_once BERP_PLUGIN_DIR . 'includes/class-berp-activator.php';
require_once BERP_PLUGIN_DIR . 'includes/class-berp-deactivator.php';

/**
 * Activation hook.
 */
register_activation_hook( __FILE__, array( 'BERP_Activator', 'activate' ) );

/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, array( 'BERP_Deactivator', 'deactivate' ) );

// Start the plugin.
add_action(
	'plugins_loaded',
	static function () {
		$GLOBALS['builderp'] = BuildErp::instance();
		$GLOBALS['builderp']->run();
	}
);
