<?php

function ofs_get_lender($user=null) {
	if($user==null) {
		$user = wp_get_current_user();
	}
	return new OFS_Lender($user);
}

function ofs_message($msg, $code='danger') {
	if($msg!='') {
	?>
	<div class="ofs-message ofs-message-<?=esc_attr($code)?>"><?=$msg?></div>
	<?php
	}
}
/* borrower */
function ofs_get_borrower($post) {
	return new OFS_Borrower($post);
}

function ofs_get_current_borrower() {
	return ofs_get_borrower(ofs_get_session('ofs_borrower'));
}

function ofs_is_borrower_logged_in() {
	$current_borrower = ofs_get_current_borrower();
	
	if($current_borrower->get_id()) {
		return true;
	} else {
		return false;
	}
}

function ofs_borrower_login($credentials = array()) {
	if ( empty( $credentials ) ) {
		$credentials = array(
			'logname' => '',
			'display_name' => ''
		);

		if ( ! empty( $_POST['logname'] ) ) {
			$credentials['logname'] = sanitize_key($_POST['logname']);
		}
		if ( ! empty( $_POST['display_name'] ) ) {
			$credentials['display_name'] = sanitize_text_field($_POST['display_name']);
		}
		
	}

	$borrower = ofs_authenticate(null, $credentials['logname'], $credentials['display_name']);

	if($borrower instanceof OFS_Borrower && $borrower->get_id()) {
		//$_SESSION['ofs_borrower'] = $borrower;
		ofs_set_session('ofs_borrower', $borrower);
		return true;
	} else {
		ofs_delete_session('ofs_borrower');
	}

	return false;
}

function ofs_authenticate($borrower, $logname, $display_name) {
	if ( $borrower instanceof OFS_Borrower && $borrower->get_id() ) {
		return $borrower;
	}

	$borrower = null;

	$logname = strtolower(sanitize_key( $logname ));
	$display_name = sanitize_text_field( $display_name );

	if(!empty($logname)) {
		$_post = ofs_borrower_exists($logname);
		if ( empty($_post) ) {	
			$id = wp_insert_post( array(
				'post_type' => 'borrower',
				'post_status' => 'publish',
				'post_title' => $logname,
				'post_excerpt' => $display_name
			) );
			if($id) {
				$_post = get_post($id);
			}
		} else {
			//print_r($_post);die;
			if($_post->post_excerpt!=$display_name && $display_name!='') {
				wp_update_post( array( 'ID'=>$_post->ID, 'post_excerpt'=>$display_name ) );
			}
			$_post = get_post($_post->ID);
		}

		
		$borrower = ofs_get_borrower($_post);
	}

	return $borrower;

}

function ofs_borrower_exists($logname) {
	global $wpdb;

	if(!empty($logname)) {
		$_post = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_title='%s' AND post_status='publish' AND post_type='borrower' LIMIT 1", strtolower($logname) ) );
		if ( $_post ) {
			return $_post;
		}
	}

	return null;
}

function ofs_borrower_logout() {
	ofs_delete_session('ofs_borrower');
}

/* session functions */
function ofs_get_session($key, $default='') {
	if(isset($_SESSION[$key])) {
		return $_SESSION[$key];
	} else {
		return $default;
	}
}

function ofs_set_session($key, $value) {
	$_SESSION[$key] = $value;
}

function ofs_delete_session($key) {
	if(isset($_SESSION[$key])) {
		unset($_SESSION[$key]);
	}
}

function ofs_clean_session() {
	session_destroy();
}

/* ------------------------------------- */
function ofs_get_conditions() {
	$condition_posts = get_posts(array(
		'post_type' => 'condition',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	));
	$conditions = [];
	if(!empty($condition_posts)) {
		foreach ($condition_posts as $key => $value) {
			$conditions[$value->ID] = ofs_get_condition($value);
		}
	}

	return $conditions;
}

function ofs_get_condition($post) {
	return new OFS_Condition($post);
}

function ofs_model() {
	return OFS_Model::get_instance();
}

function ofs_get_model() {
	return OFS_Model::get_instance();
}

function ofs_debug($var) {
	echo '<pre>'.print_r($var,true).'</pre>';
}

function ofs_log($var) {
	error_log(print_r($var, true));
}

function ofs_current_url() {
	static $url = null;
	if ( $url === null ) {
		if ( is_multisite() && ! ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) ) {
			switch_to_blog( 1 );
			$url = get_option( 'home' );
			restore_current_blog();
		} else {
			$url = get_option( 'home' );
		}

		//Remove the "//" before the domain name
		$url = ltrim( preg_replace( '/^[^:]+:\/\//', '//', $url ), '/' );

		//Remove the ulr subdirectory in case it has one
		$split = explode( '/', $url );

		//Remove end slash
		$url = rtrim( $split[0], '/' );

		$url .= '/' . ltrim( $_SERVER['REQUEST_URI'], '/' );
		$url = set_url_scheme( '//' . $url ); // https fix
	}

	return $url;
}

function ofs_format_content($content) {
	global $wp_embed;

	$content = wp_kses_post( $content );
	$content = wptexturize($content);
	$content = convert_chars($content);
	$wp_embed->run_shortcode($content);
	$content = $wp_embed->autoembed($content);
	$content = convert_smilies($content);
	$content = wpautop($content);
	$content = shortcode_unautop($content);
	$content = prepend_attachment($content);
	$content = wp_make_content_images_responsive($content);
	$content = capital_P_dangit($content);
	$content = do_shortcode($content);
	
	$content = apply_filters('theme_format_content', $content);

	return $content;
}