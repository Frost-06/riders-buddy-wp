<?php
/**
 * Setup Custom Login Dashboard plugin.
 *
 * @package Custom_Login_Dashboard
 */

namespace CustomLoginDashboard;

/**
 * Setup Better Admin Bar.
 */
class Setup {

	/**
	 * The class instance.
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Get instance of the class.
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Init the class setup.
	 */
	public static function init() {
		self::$instance = new self();

		add_action( 'plugins_loaded', array( self::$instance, 'setup' ) );
	}

	/**
	 * Setup action & filter hooks.
	 */
	public function __construct() {}

	/**
	 * Setup action & filters.
	 */
	public function setup() {

		add_action( 'init', array( $this, 'setup_text_domain' ) );
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 4 );
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ), 20 );

		// Process export-import.
		add_action( 'admin_init', array( $this, 'process_export' ) );
		add_action( 'admin_init', array( $this, 'process_import' ) );

		// Ajax handlers.
		new Ajax\Save_Settings();
		new Ajax\Reset_Settings();
		new Ajax\Load_Default_Settings();

	}

	/**
	 * Setup textdomain.
	 */
	public function setup_text_domain() {

		load_plugin_textdomain( 'erident-custom-login-and-dashboard', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	}

	/**
	 * Add settings link to plugin list page.
	 *
	 * @param array  $actions     An array of plugin action links.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                            'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 *
	 * @return array The modified plugin action links.
	 */
	public function add_settings_link( $actions, $plugin_file, $plugin_data, $context ) {

		if ( CUSTOM_LOGIN_DASHBOARD_PLUGIN_BASENAME === $plugin_file ) {
			$support_link = '<a href="https://wordpress.org/support/plugin/erident-custom-login-and-dashboard/" target="_blank">' . __( 'Support', 'erident-custom-login-and-dashboard' ) . '</a>';

			array_unshift( $actions, $support_link );

			$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=erident-custom-login-and-dashboard' ) ) . '">' . __( 'Settings', 'erident-custom-login-and-dashboard' ) . '</a>';

			array_unshift( $actions, $settings_link );
		}

		return $actions;

	}

	/**
	 * Add submenu under "Settings" menu item.
	 */
	public function add_submenu_page() {

		$page = add_options_page( __( 'Custom Login & Dashboard', 'erident-custom-login-and-dashboard' ), __( 'Custom Login & Dashboard', 'erident-custom-login-and-dashboard' ), 'administrator', 'erident-custom-login-and-dashboard', [ $this, 'page_output' ] );

	}

	/**
	 * Better Admin Bar page output.
	 */
	public function page_output() {

		$output = require __DIR__ . '/templates/settings-template.php';
		$output();

	}

	/**
	 * Enqueue admin styles & scripts.
	 */
	public function admin_scripts() {

		$current_screen = get_current_screen();

		if ( 'settings_page_erident-custom-login-and-dashboard' !== $current_screen->id ) {
			return;
		}

		if ( function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		} else {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
		}

		// CSS dependencies.

		// WP Color picker dependency.
		wp_enqueue_style( 'wp-color-picker' );

		// Settings page styling.
		wp_enqueue_style( 'heatbox', CUSTOM_LOGIN_DASHBOARD_PLUGIN_URL . '/assets/css/heatbox.css', array(), CUSTOM_LOGIN_DASHBOARD_PLUGIN_VERSION );

		// Custom Login Dashboard admin styling.
		wp_enqueue_style( 'custom-login-dashboard', CUSTOM_LOGIN_DASHBOARD_PLUGIN_URL . '/assets/css/admin.css', array(), CUSTOM_LOGIN_DASHBOARD_PLUGIN_VERSION );

		// JS dependencies.

		// Color picker alpha.
		wp_enqueue_script( 'wp-color-picker-alpha', CUSTOM_LOGIN_DASHBOARD_PLUGIN_URL . '/assets/js/wp-color-picker-alpha.js', array( 'jquery', 'wp-color-picker', 'wp-i18n' ), '2.1.3', true );

		// Settings page scripts.
		wp_enqueue_script( 'custom-login-dashboard', CUSTOM_LOGIN_DASHBOARD_PLUGIN_URL . '/assets/js/settings-page.js', array( 'wp-color-picker' ), CUSTOM_LOGIN_DASHBOARD_PLUGIN_VERSION, true );

		wp_localize_script(
			'custom-login-dashboard',
			'CustomLoginDashboard',
			array(
				'nonces'  => array(
					'saveSettings'        => wp_create_nonce( 'cldashboard_nonce_save_settings' ),
					'resetSettings'       => wp_create_nonce( 'cldashboard_nonce_reset_settings' ),
					'loadDefaultSettings' => wp_create_nonce( 'cldashboard_nonce_load_default_settings' ),
				),
				'dialogs' => array(
					'resetSettingsConfirmation'       => __( 'Are you sure you want to delete all settings?', 'erident-custom-login-and-dashboard' ),
					'loadDefaultSettingsConfirmation' => __( 'Are you sure you want to reset all settings?', 'erident-custom-login-and-dashboard' ),
				),
			)
		);

		// This handle enqueue already from v3.5.9, let's keep it just in case someone is using it.
		wp_enqueue_script( 'wp_erident_dashboard-script2' );
		wp_enqueue_script( 'wp_erident_dashboard-script' );

	}

	/**
	 * Modify admin body class.
	 *
	 * @param string $classes The class names.
	 */
	public function admin_body_class( $classes ) {

		$current_user = wp_get_current_user();
		$classes     .= ' custom-login-dashboard-user-' . $current_user->user_nicename;

		$roles = $current_user->roles;
		$roles = $roles ? $roles : array();

		foreach ( $roles as $role ) {
			$classes .= ' custom-login-dashboard-role-' . $role;
		}

		$screens = array(
			'settings_page_erident-custom-login-and-dashboard',
		);

		$screen = get_current_screen();

		if ( ! in_array( $screen->id, $screens, true ) ) {
			return $classes;
		}

		$classes .= ' heatbox-admin has-header';

		return $classes;

	}

	/**
	 * Process widget export.
	 */
	public function process_export() {

		if ( empty( $_POST['er_action'] ) || 'export_settings' != $_POST['er_action'] ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['er_export_nonce'], 'er_export_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$exporter = new Helpers\Export();

		$exporter->export();

	}

	/**
	 * Process widget import.
	 */
	public function process_import() {

		if ( empty( $_POST['er_action'] ) || 'import_settings' != $_POST['er_action'] ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['er_import_nonce'], 'er_import_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$importer = new Helpers\Import();

		$importer->import();

	}

}
