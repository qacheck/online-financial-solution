<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_User_Profile {

	private static $instance = null;

	private $uploader = null;

	private function __construct() {
		add_action('admin_head', array($this, 'admin_color_scheme'));

		add_action('deleted_user', array($this, 'remove_lender_data'));
		
		//add_action('admin_init', array($this, 'admin_init_remove_hooks'));
		add_action('admin_init', array($this, 'admin_init_add_hooks'));

		add_filter('pre_option_show_avatars', array($this, 'hide_default_avatar'));

		add_action('show_user_profile', array($this, 'lender_user_custom_fields'), 10, 1);
		add_action('wp_ajax_profile_photo_upload', array($this, 'profile_photo_upload'));

		add_filter('user_contactmethods', array($this, 'user_contactmethods'), 10, 1);

		add_action('personal_options_update', array($this, 'ofs_save_custom_user_profile_fields'));
		
	}

	public function user_contactmethods($methods) {
		$wp_user = wp_get_current_user();

		if(!in_array( 'administrator', (array) $wp_user->roles )) {
			$methods['user_phone'] = __( 'Phone number' );
		}
		return $methods;
	}

	public function admin_color_scheme() {
		global $_wp_admin_css_colors;
		$current_user = wp_get_current_user();
		if(!in_array( 'administrator', (array) $current_user->roles )) {
			$_wp_admin_css_colors = 0;
		}
	}

	public function ofs_save_custom_user_profile_fields() {
		/*
		$user_id = get_current_user_id();
		if(!current_user_can( 'edit_user',  $user_id)) {
			return;
		}
		$nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';

		if(wp_verify_nonce($nonce, 'ofs-edit-user')) {
			$user_phone = isset($_POST['user_phone']) ? sanitize_text_field( $_POST['user_phone'] ) : '';
			if($user_phone!='') {
				update_user_meta( $user_id, 'user_phone', $user_phone );
			}
		}
		*/
		$wp_user = wp_get_current_user();
		if(in_array( 'lender', (array) $wp_user->roles ) && $wp_user->description!=$_POST['description']) {
			$mailer = new OFS_Mailer(get_bloginfo('admin_email'));
			$mailer->set_cc(OFS_ADDITION_ADMIN_EMAIL);
			$mailer->set_subject( '['.$wp_user->ID.']'.esc_html($wp_user->user_login).' cập nhật tiểu sử' );
			$mailer->set_body( esc_html($_POST['description']) );
			$mailer->send();
		}
	}

	public function profile_photo_upload() {
		$profile_photo = isset($_FILES['profile_photo']) ? $_FILES['profile_photo'] : '';

		if($profile_photo) {
			$this->uploader->replace_upload_dir = true;
			$profile_photo_file = $this->uploader->upload_image($profile_photo, null, 'profile_photo', 'profile_photo');
			$this->uploader->replace_upload_dir = false;
			//print_r($profile_photo_file);
			if(isset($profile_photo_file['error'])) {
				print_r($profile_photo_file['error']);
			} else if(isset($profile_photo_file['handle_upload'])) {
				update_user_meta( $this->uploader->user_id, 'profile_photo', $profile_photo_file['handle_upload']['file']);
					//print_r($profile_photo_file['handle_upload']['url']);
				?>
				<img src="<?=$profile_photo_file['handle_upload']['url']?>" alt="Profile image">
				<?php
			}
			//print_r($profile_photo_file);
		}

		die;
	}

	public function lender_user_custom_fields($profileuser) {
		$profile_photo_file = get_user_meta( $profileuser->ID, 'profile_photo', true );
		//$user_phone = get_user_meta( $profileuser->ID, 'user_phone', true );
		?>
		<table class="form-table" role="presentation">
			<!-- <tr class="user-phone">
				<th><?php //_e( 'Phone number' ); ?></th>
				<td>
					<input type="text" name="user_phone" value="<?php //esc_attr($user_phone); ?>" class="regular-text" pattern="[0-9]{10}">
				</td>
			</tr> -->
			<tr class="user-profile-picture">
				<th><?php _e( 'Profile Picture' ); ?></th>
				<td>
					<div id="user-profile-picture-select">
					<?php echo ($profile_photo_file!='')?'<img src="'.esc_url($this->uploader->get_upload_user_base_url($profileuser->ID).'/'.$profile_photo_file).'" alt="Profile Picture">':''; ?>
					</div>
					<input type="file" id="user-profile-photo">
					<button type="button" class="button" id="user-profile-photo-upload">Upload</button>
				</td>
			</tr>
		</table>
		<?php
		wp_nonce_field( 'ofs-edit-user', 'nonce', true, true );
	}

	public function hide_default_avatar($show_avatars) {
		// $user = wp_get_current_user();
		// if(in_array( 'lender', (array) $user->roles )) {
			$show_avatars = 0;
		// }
		return $show_avatars;
	}

	public function admin_init_add_hooks() {
		$this->uploader = new OFS_Uploader;
		add_action('admin_head', array($this, 'disable_user_edit_color_scheme'));
	}

	public function admin_init_remove_hooks() {
		remove_action( 'admin_head', 'wp_color_scheme_settings' );
	}

	public function disable_user_edit_color_scheme() {
		global $_wp_admin_css_colors;
		$user = wp_get_current_user();
		if(in_array( 'lender', (array) $user->roles )) {
			$_wp_admin_css_colors = array();
		}
	}

	/*
	 Xoa cac du lieu neu xoa lender
	 */
	function remove_lender_data($id) {
		ofs_model()->remove_required($id);
		ofs_model()->remove_connection_by_lender($id);
	}

	public function __clone() {}

	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}
OFS_Admin_User_Profile::get_instance();