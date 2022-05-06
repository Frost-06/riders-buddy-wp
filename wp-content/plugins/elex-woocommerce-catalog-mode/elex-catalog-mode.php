<?php
/*
Plugin Name: ELEX WooCommerce Catalog Mode
Plugin URI: https://elextensions.com/plugin/
Description:  Hide Add to Cart option. Also, turn your shop into catalog mode.
Version: 1.2.6
WC requires at least: 2.6.0
WC tested up to: 6.4
Author: ELEXtensions
Author URI: https://elextensions.com 
Developer: ELEXtensions
Developer URI: https://elextensions.com
Text Domain: elex-catmode-rolebased-price
*/

// to check wether accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// for Required functions
if ( ! function_exists( 'elex_cm_is_woocommerce_active' ) ) {
	require_once( 'elex-includes/elex-functions.php' );
}

// to check woocommerce is active
if ( ! ( elex_cm_is_woocommerce_active() ) ) {
	add_action( 'admin_notices', 'elex_cm_premium_prices_woocommerce_inactive_notice' );
	return;
}
function elex_cm_premium_prices_woocommerce_inactive_notice() {
	?>
<div id="message" class="error">
	<p>
	<?php	print_r( __( '<b>WooCommerce</b> plugin must be active for <b>WooCommerce Catalog Mode, Wholesale & Role Based Pricing</b> to work. ', 'elex-catmode-rolebased-price' ) ); ?>
	</p>
</div>
<?php
}

if ( ! defined( 'ELEX_CATALOG_MODE_MAIN_URL_PATH' ) ) {
	define( 'ELEX_CATALOG_MODE_MAIN_URL_PATH', plugin_dir_url( __FILE__ ) );
}

//to check if basic version is active
function elex_cm_pricing_pre_activation_check() {
	//check if basic version is there
	if ( is_plugin_active( 'elex-catmode-rolebased-price/elex-catmode-rolebased-price.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html_e( "Oops! You tried installing the Basic version without deactivating and deleting the Premium version. Kindly deactivate and delete ELEX WooCommerce Role-based Pricing Plugin & WooCommerce Catalog Mode and then try again. For any issues, kindly contact our <a target='_blank' href='https://elextensions.com/support/'>support</a>.", 'elex-catmode-rolebased-price' ), '', array( 'back_link' => 1 ) );
	}
		
}

register_activation_hook( __FILE__, 'elex_cm_pricing_pre_activation_check' );

//show message for the first installation
add_action( 'admin_notices', 'elex_cm_plugin_admin_notices' );
function elex_cm_plugin_admin_notices() {
	if ( ! get_option( 'elex_first_installation_msg' ) ) {
		if ( in_array( 'elex-catmode-rolebased-price/elex-catmode-rolebased-price.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			echo "<div class='updated'><strong>ELEX WooCommerce Catalog Mode, Wholesale & Role Based Pricing</strong> is activated. Go to <a href=" . esc_url( admin_url( 'admin.php?page=wc-settings&tab=elex_catalog_mode' ) ) . '>Settings</a> to configure.</div>';
		}
		update_option( 'elex_first_installation_msg', 'true' );
	}
}

if ( ! class_exists( 'Elex_CM_Pricing_Discounts_By_User_Role_WooCommerce' ) ) {
	class Elex_CM_Pricing_Discounts_By_User_Role_WooCommerce {
		
		// initializing the class
		public function __construct() {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) , array( $this, 'elex_cm_pricing_discount_action_links' ) ); //to add settings, doc, etc options to plugins base
			add_action( 'init', array( $this, 'elex_cm_pricing_discount_admin_menu' ) ); //to add pricing discount settings options on woocommerce shop
			add_action( 'admin_menu', array( $this, 'elex_cm_pricing_discount_admin_menu_option' ) ); //to add pricing discount settings menu to main menu of woocommerce
		}
		
		// function to add settings link to plugin view
		public function elex_cm_pricing_discount_action_links( $links ) {
			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=elex_catalog_mode' ) . '">' . __( 'Settings', 'elex-catmode-rolebased-price' ) . '</a>',
				'<a href="https://elextensions.com/documentation/#elex-woocommerce-catalog-mode" target="_blank">' . __( 'Documentation', 'elex-catmode-rolebased-price' ) . '</a>',
				'<a href="https://elextensions.com/support/" target="_blank">' . __( 'Support', 'elex-catmode-rolebased-price' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
		}
		
		// function to add menu in woocommerce
		public function elex_cm_pricing_discount_admin_menu() {
			 require_once( 'includes/elex-catalog-mode-admin.php' );
			require_once( 'includes/elex-catalog-mode-settings.php' );
		}
		
		public function elex_cm_pricing_discount_admin_menu_option() {
			global $pricing_discount_settings_page;
			$pricing_discount_settings_page = add_submenu_page( 'woocommerce', __( 'Catalog Mode', 'elex-catmode-rolebased-price' ) , __( 'Catalog Mode', 'elex-catmode-rolebased-price' ) , 'manage_woocommerce', 'admin.php?page=wc-settings&tab=elex_catalog_mode' );
		}
	}

	new Elex_CM_Pricing_Discounts_By_User_Role_WooCommerce();
}
