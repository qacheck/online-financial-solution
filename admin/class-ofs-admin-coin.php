<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_Coin {

	private static $instance = null;

	private function __construct() {
		add_action('admin_bar_menu', array($this, 'admin_bar_menu'));

		add_action('edit_user_profile', array($this, 'manage_lender_coin'), 5, 1);
		add_action('edit_user_profile_update', array($this, 'update_lender_coin'), 10, 1);
	}

	public function update_lender_coin($user_id) {
		$lender_user = get_user_by( 'id', $user_id );
		//ofs_debug($user);
		if( in_array('lender', (array) $lender_user->roles) ) {
			$_coin = isset($_POST['_coin']) ? absint(preg_replace('/\W/', '', $_POST['_coin'])) : 0;
			update_user_meta( $user_id, '_coin', $_coin );
		}
	}

	public function manage_lender_coin($profileuser) {
		$user = wp_get_current_user();
		//ofs_debug($user);
		if( in_array('administrator', (array) $user->roles) && current_user_can('edit_user', $profileuser->ID) && in_array('lender', (array) $profileuser->roles)) {
			$coin = absint(get_user_meta( $profileuser->ID, '_coin', true ));
			?>
			<table class="form-table" role="presentation">
				<tr class="user-coin">
					<th><?php _e( 'Money' ); ?></th>
					<td>
						<input type="text" id="user_coin" name="_coin" value="<?=$coin?>" class="regular-text"> <?=OFS_CURRENCY_UNIT?>
					</td>
				</tr>
			</table>
			<?php
		}
	}

	public function admin_bar_menu($wp_admin_bar) {
		//ofs_debug($wp_admin_bar->user);
		$user = wp_get_current_user();
		if($user->ID && in_array('lender', (array) $user->roles)) {
			$coin = absint(get_user_meta( $user->ID, '_coin', true ) );
			$wp_admin_bar->add_node(array(
				'id' => 'user-coin',
				'title' => sprintf('<strong style="color:#ff0;">%s</strong> %s', number_format($coin,0,',','.'), OFS_CURRENCY_UNIT),
				'parent' => 'top-secondary',
				'href' => '#',
			));
		}
		
	}

	public function __clone() {}

	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}
OFS_Admin_Coin::get_instance();