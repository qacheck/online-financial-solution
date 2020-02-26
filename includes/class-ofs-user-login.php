<?php
if (!defined('ABSPATH')) exit;

class OFS_User_Login {

	public function __construct() {
		load_plugin_textdomain( 'ofs', false, dirname( OFS_BASE ).'/languages' );

		add_action('login_enqueue_scripts', array($this, 'login_enqueue_scripts'));

		add_filter('login_link_separator', array($this, 'login_link_separator'));

		add_filter('register', array($this, 'registration_url'));

		add_action('register_new_user', array($this, 'ofs_donate_register'));

	}

	public function ofs_donate_register($user_id) {
		$user = get_userdata( $user_id );
		if(in_array('lender', (array)$user->roles)) {
			update_user_meta( $user_id, '_coin', OFS_DONATE );
		}
	}

	public function registration_url($registration_url) {
		$registration_url = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Register', 'ofs' ) );
		return $registration_url;
	}

	public function login_link_separator() {
		return '';
	}

	public function login_enqueue_scripts() {
		wp_enqueue_style('ofs-login', OFS_URL.'/assets/css/ofs-login.css', array(), '');
		wp_enqueue_script('ofs-login', OFS_URL.'/assets/js/ofs-login.js', array('jquery'), '', true);
	}

}

new OFS_User_Login;