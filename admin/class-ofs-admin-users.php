<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_Users {

	public function __construct() {
		add_filter('manage_users_columns', array($this, 'users_columns_header'));
		add_filter('manage_users_custom_column', array($this, 'users_custom_column_output'), 10, 3);

		add_action('wp_ajax_ofs_change_user_status', array($this, 'change_user_status'));
	}

	public function change_user_status() {
		check_ajax_referer('ofs-admin-ajax-nonce', 'nonce');

		$uid = isset($_POST['uid']) ? absint($_POST['uid']) : 0;
		if($uid>0 && current_user_can('edit_users')) {
			$user = get_userdata($uid);
			if(in_array('lender', (array)$user->roles)) {
				$is_active = absint($user->active);

				$updated = update_user_meta( $uid, 'active', ($is_active===0)?1:0 );

				if($updated) {
					$user = get_userdata($uid);
					if(absint($user->active)===1) {
						echo '<span class="dashicons dashicons-visibility" style="color:#00b59c"></span>';
					} else {
						echo '<span class="dashicons dashicons-hidden" style="color:#999"></span>';
					}
				}
			}
		}
		
		die;
	}

	public function users_custom_column_output($output, $column, $user_id) {
		$user = get_userdata($user_id);
		if($column=='status' && !in_array('administrator',(array)$user->roles)) {
			//$status = absint(get_user_meta( $user_id, 'active', true );
			$output = '<a href="javascript:;" class="ofs-user-toggle-status" data-user="'.$user_id.'">';
			if(absint($user->active)==0) {
				$output .= '<span class="dashicons dashicons-hidden" style="color:#999"></span>';
			} else {
				$output .= '<span class="dashicons dashicons-visibility" style="color:#00b59c"></span>';
			}
			$output .= '</a>';
		}

		if($column=='thumbnail') {
			$uploader = new OFS_Uploader;
			$profile_photo_file = get_user_meta($user_id, 'profile_photo', true);
			if($profile_photo_file) {
				$output .= ($profile_photo_file!='')?'<div class="thumbnail-wrap"><img src="'.esc_url($uploader->get_upload_user_base_url($user_id).'/'.$profile_photo_file).'" alt="Profile Picture"></div>':'';
			}
			
		}
		return $output;
	}

	public function users_columns_header($columns) {
		$cb = $columns['cb'];
		unset($columns['cb']);
		$new_columns = array(
			'cb' => $cb,
			'thumbnail' => __('Avatar', 'ofs')
		);

		$columns['status'] = __('Status', 'ofs');
		return array_merge($new_columns, $columns);
	}

}

new OFS_Admin_Users;