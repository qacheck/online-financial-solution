<?php
if (!defined('ABSPATH')) exit;

class OFS_Lender {

	private $ID = 0;

	private $wp_user = null;

	public function __construct($user) {
		$user_id = 0;
		if( $user instanceof self ) {
			$user_id = $user->get_id();
		} else if( $user instanceof WP_User ) {
			$user_id = $user->ID;
		} else {
			$user_id = absint($user);
		}

		$wp_user = new WP_User($user_id);
		//ofs_log($wp_user);
		if( $wp_user instanceof WP_User && in_array( 'lender', (array) $wp_user->roles ) && absint($wp_user->active)===1 ) {
			$this->wp_user = $wp_user;
			$this->ID = absint($wp_user->ID);
		} else {
			$this->wp_user = null;
			$this->ID = 0;
		}

	}

	public function __get($name) {
		return isset($this->$name) ? $this->$name : $this->wp_user->$name;
	}

	public function is_connected($borrower_id) {
		$conn_status = ofs_model()->connect_status(absint($borrower_id), $this->ID);
		return ($conn_status=='connected');
	}

	public function display_filter_profile($borrower_id,$condition_id=0) {
		if(!$this->ID) return;
		
		$profile_photo = get_user_meta( $this->ID, 'profile_photo', true );

		$conn_status = ofs_model()->connect_status($borrower_id, $this->ID);
		?>
		<div class="osf-lender-profile">
			<div class="profile-photo"><span><?php echo $this->get_profile_photo(); ?></span></div>
			<div class="profile-info">
				<div class="lender-name"><?=esc_html($this->get_display_name())?></div>
				<div class="lender-bio">
					<?php echo ofs_format_content($this->wp_user->description); ?>
				</div>
				<div class="action">
					<button type="button" class="toggle-lender-bio-mobile">Hồ sơ</button>
					<button type="button" class="register<?php echo ($conn_status!='')?' '.$conn_status:''; ?>" data-lender-id="<?=$this->ID?>" data-condition-id="<?=absint($condition_id)?>"<?php disabled( in_array($conn_status,['pending','connected']), true, true ); ?>><?php
					//echo (in_array($conn_status,['pending','connected']))?'Đã đăng ký':'Đăng ký vay';
					if($conn_status=='pending') {
						echo 'Chờ duyệt';
					} else if($conn_status=='connected') {
						echo 'Đã được duyệt';
					} else {
						echo 'Đăng ký vay';
					}
					?></button>
				</div>
			</div>
			<div class="lender-bio-mobile">
				<?php echo ofs_format_content($this->wp_user->description); ?>
			</div>
		</div>
		<?php
	}

	public function get_profile_photo() {
		$profile_photo = get_user_meta( $this->ID, 'profile_photo', true );
		$image = '';
		if($profile_photo!='') {
			$wp_upload_dir = wp_upload_dir();
			$image = '<img src="'.esc_url(set_url_scheme( $wp_upload_dir['baseurl'] . '/ofs/' ).$this->ID.'/'.$profile_photo).'" alt="'.esc_attr($this->get_display_name()).'">';
		}
		return $image;
	}

	public function get_id() {
		return $this->ID;
	}

	public function get_display_name() {
		if($this->ID) {
			return $this->wp_user->display_name;
		}
		return '';
	}

	public function get_login() {
		if($this->ID) {
			return $this->wp_user->user_login;
		}
		return '';
	}

	public function get_wp_user() {
		return $this->wp_user;
	}

}