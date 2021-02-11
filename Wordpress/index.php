<?php



/*



	Plugin Name: Payment Gateway eCommerce with integration of RosKassa

	Plugin URI: https://crmbees.com/

	Description:  The Leading Global Payment Processor with service of RosKassa.

	Tags: WooCommerce, WordPress, Gateways, Payments, Payment, Money, WooCommerce, WordPress, Plugin, Module, Store, Modules, 	Plugins, Payment system, Website, RosKassa, crmbees

	Version: 0.2.0

	Author: crmbees

	Author URI: https://crmbees.com

	Copyright: © 2021 crmbees

    License: GNU General Public License v3.0
    License URI: http://www.gnu.org/licenses/gpl-3.0.html

*/



defined('ABSPATH') or exit;



/**

 * Run

 *

 * @action woocommerce_roskassa_gateway_init

 */



add_action('plugins_loaded', 'woocommerce_roskassa_gateway_init', 0);



/**

 * Init plugin gateway

 */



function woocommerce_roskassa_gateway_init(){



	/**

	 * Main check

	 */



	if(!class_exists('WC_Payment_Gateway') || class_exists('WC_Roskassa')){



		return;



	}



	/**

	 * Define plugin url

	 */



	define('WC_ROSKASSA_URL', plugin_dir_url(__FILE__));



	/**

	 * GateWork

	 */



	include_once __DIR__ . '/gatework/init.php';



	/**

	 * Gateway main class

	 */



	include_once __DIR__ . '/class-wc-roskassa.php';



	/**

	 * Load language

	 *

	 * todo: optimize load

	 */



	load_plugin_textdomain('wc-roskassa', false, dirname(plugin_basename( __FILE__ )) . '/languages');



	/**

	 * Add the gateway to WooCommerce

	 *

	 * @param $methods

	 *

	 * @return array

	 */



	function woocommerce_roskassa_gateway_add($methods){



		$methods[] = 'WC_Roskassa';



		return $methods;



	}



	/**

	 * Add payment method

	 *

	 * @filter woocommerce_roskassa_gateway_add

	 */



	add_filter('woocommerce_payment_gateways', 'woocommerce_roskassa_gateway_add');



}



/**

 * Plugin links right

 */



add_filter('plugin_row_meta',  'wc_roskassa_register_plugins_links_right', 10, 2);



function wc_roskassa_register_plugins_links_right($links, $file){



	$base = plugin_basename(__FILE__);



	if($file === $base){



		$links[] = '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_roskassa').'">Настройки</a>';



	}



	return $links;



}



/**

 * Plugin links left

 */



add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_roskassa_register_plugins_links_left');



function wc_roskassa_register_plugins_links_left($links){



	return array_merge(array('settings' => '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_roskassa').'">Настройки</a>'), $links);



}