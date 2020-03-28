<?php
if (!defined('ABSPATH')) exit;

final class Online_Financial_Solution {

	private static $instance = null;

	private function __construct() {
		$this->includes();

		$this->hooks();
	}

	private function includes() {
		require_once OFS_PATH.'/includes/class-wp-async-request.php';
		require_once OFS_PATH.'/includes/class-wp-background-process.php';

		require_once OFS_PATH.'/includes/class-ofs-background-update-connection-status.php';
		require_once OFS_PATH.'/includes/captcha/captcha.php';
		require_once OFS_PATH.'/includes/ofs-functions.php';
		require_once OFS_PATH.'/includes/class-ofs-post-types.php';
		require_once OFS_PATH.'/includes/class-ofs-install.php';
		require_once OFS_PATH.'/includes/class-ofs-field.php';
		require_once OFS_PATH.'/includes/class-ofs-field-factory.php';

		require_once OFS_PATH.'/includes/class-ofs-model.php';
		require_once OFS_PATH.'/includes/class-ofs-condition.php';
		require_once OFS_PATH.'/includes/class-ofs-borrower.php';
		require_once OFS_PATH.'/includes/class-ofs-lender.php';
		require_once OFS_PATH.'/includes/class-ofs-shortcodes.php';
		require_once OFS_PATH.'/includes/class-ofs-mailer.php';
		require_once OFS_PATH.'/includes/class-ofs-user-login.php';
		require_once OFS_PATH.'/includes/class-ofs-schedules.php';

		if(is_admin()) {
			require_once OFS_PATH.'/admin/class-ofs-admin.php';
		}
	}

	private function hooks() {
		register_activation_hook( OFS_PLUGIN_FILE, array( 'OFS_Install', 'install' ) );
		register_deactivation_hook( OFS_PLUGIN_FILE, array( 'OFS_Install', 'uninstall' ) );
		add_action('plugins_loaded', array($this, 'load_textdomain'));

		//OFS_Install::update_roles();
		//OFS_Install::create_schedules();

		OFS_Field_Factory::load_fields();

		add_action('plugins_loaded', array($this, 'session_start') );

		add_action('wp_loaded', array($this, 'init_viewas') );

		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 999 );

		add_filter('wp_new_user_notification_email', array($this, 'custom_new_user_notification_email'), 10, 3);

	}

	public function custom_new_user_notification_email($wp_new_user_notification_email, $user, $blogname) {
		$pattern = '#\s+'.preg_quote(wp_login_url()).'\s+#';
		$wp_new_user_notification_email = preg_replace($pattern,'', $wp_new_user_notification_email);
		return $wp_new_user_notification_email;
	}

	public function init_viewas() {
		$viewas = isset($_GET['viewas']) ? sanitize_key($_GET['viewas']) : '';
		if(!in_array($viewas, ['borrower','lender'])) {
			if(is_user_logged_in()) {
				$viewas = 'lender';
			}
			if(ofs_is_borrower_logged_in()) {
				$viewas = 'borrower';
			}
		}
		ofs_set_session('viewas', $viewas);
	}

	public function enqueue_scripts() {
		OFS_Field::register_scripts();

		$ofs_fields = OFS_Field_Factory::get_fields();
		foreach ($ofs_fields as $type => $ofs_field) {
			if(class_exists($ofs_field)) {
				$ofs_field::enqueue_scripts();
			}
		}

		wp_enqueue_style('ofs', OFS_URL.'/assets/css/ofs.css');
		
		wp_enqueue_script('ofs', OFS_URL.'/assets/js/ofs.js', array('jquery'), '1.0', true);
		$data = array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ofs-ajax'),
			'msg_phone_number' => __('Phone number is required!', 'ofs'),
			'processing' => __('Processing...', 'ofs')
		);
		wp_localize_script('ofs', 'ofs', $data);
	}

	public function session_start() {
		session_start();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'ofs', false, dirname( OFS_BASE ).'/languages' );

		if(defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['locale'])){
			remove_filter('determine_locale', array($this, 'ajax_locale'));
			add_filter('determine_locale', array($this, 'ajax_locale'));
		}
	}

	public function ajax_locale($locale) {
		$locale = $_REQUEST['locale'];
		return $locale;
	}

	public function __clone() {}

	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}