<?php

class OFS_Uploader {


	/**
	 * @var integer
	 */
	var $user_id;


	/**
	 * @var integer
	 */
	var $replace_upload_dir = false;


	/**
	 * @var string
	 */
	var $field_key;


	/**
	 * @var string
	 */
	var $wp_upload_dir;


	/**
	 * @var string
	 */
	var $temp_upload_dir;


	/**
	 * @var string
	 */
	var $core_upload_dir;


	/**
	 * @var string
	 */
	var $core_upload_url;


	/**
	 * @var string
	 */
	var $upload_baseurl;


	/**
	 * @var string
	 */
	var $upload_basedir;


	/**
	 * @var string
	 */
	var $upload_user_baseurl;


	/**
	 * @var string
	 */
	var $upload_user_basedir;


	/**
	 * @var string
	 */
	var $upload_image_type;


	/**
	 * @var string
	 */
	var $upload_type;


	/**
	 * Uploader constructor.
	 */
	function __construct() {
		$this->core_upload_dir = DIRECTORY_SEPARATOR . 'ofs' . DIRECTORY_SEPARATOR;
		$this->core_upload_url = '/ofs/';
		$this->upload_image_type = 'profile_photo';
		$this->wp_upload_dir = wp_upload_dir();
		$this->temp_upload_dir = 'temp';

		add_filter( 'upload_dir', array( $this, 'set_upload_directory' ), 10, 1 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_upload' ) );

		add_action( 'init', array( $this, 'init' ) );
	}


	/**
	 * Init
	 */
	function init() {
		$this->user_id = get_current_user_id();
	}


	/**
	 * Get core temporary directory path
	 *
	 * @since 2.0.22
	 * @return string
	 */
	public function get_core_temp_dir() {
		return $this->get_upload_base_dir(). $this->temp_upload_dir;
	}


	/**
	 * Get core temporary directory URL
	 *
	 * @since 2.0.22
	 * @return string
	 */
	public function get_core_temp_url() {
		return $this->get_upload_base_url(). $this->temp_upload_dir;
	}


	/**
	 * Get core upload directory
	 *
	 * @since 2.0.22
	 * @return string
	 */
	public function get_core_upload_dir() {
		return $this->core_upload_dir;
	}


	/**
	 * Get core upload base url
	 *
	 * @since 2.0.22
	 * @return string
	 */
	public function get_upload_base_url() {
		$wp_baseurl = $this->wp_upload_dir['baseurl'];

		$this->upload_baseurl = set_url_scheme( $wp_baseurl . $this->core_upload_url );

		return $this->upload_baseurl;
	}


	/**
	 * Get core upload  base directory
	 *
	 * @since 2.0.22
	 * @return string
	 */
	public function get_upload_base_dir() {
		$wp_basedir = $this->wp_upload_dir['basedir'];

		$this->upload_basedir = $wp_basedir . $this->core_upload_dir;

		return $this->upload_basedir;
	}


	/**
	 * Get user upload base directory
	 *
	 * @param integer $user_id
	 * @param bool $create_dir
	 *
	 * @since 2.0.22
	 *
	 * @return string
	 */
	public function get_upload_user_base_dir( $user_id = null, $create_dir = false ) {
		if ( $user_id ) {
			$this->user_id = $user_id;
		}

		$this->upload_user_basedir = $this->get_upload_base_dir() . $this->user_id;

		if ( $create_dir ) {
			wp_mkdir_p( $this->upload_user_basedir );
		}

		return $this->upload_user_basedir;
	}


	/**
	 * Get user upload base url
	 *
	 * @param integer $user_id
	 * @since 2.0.22
	 * @return string
	 */
	public function get_upload_user_base_url( $user_id = null ) {
		if ( $user_id ) {
			$this->user_id = $user_id;
		}

		$this->upload_user_baseurl = $this->get_upload_base_url() . $this->user_id;

		return $this->upload_user_baseurl;
	}


	/**
	 * Validate file size
	 * @param  array $file
	 * @return array
	 */
	public function validate_upload( $file ) {
		$error = false;
		if ( 'image' == $this->upload_type ) {
			$error = $this->validate_image_data( $file['tmp_name'], $this->field_key );
		} elseif( 'file' == $this->upload_type ) {
			$error = $this->validate_file_data( $file['tmp_name'], $this->field_key );
		}

		if ( $error ) {
			$file['error'] = $error;
		}

		return $file;
	}


	/**
	 * Set upload directory
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function set_upload_directory( $args ) {
		$this->upload_baseurl = $args['baseurl'] . $this->core_upload_url;
		$this->upload_basedir = $args['basedir'] . $this->core_upload_dir;

		if ( 'image' == $this->upload_type && is_user_logged_in() ) {
			$this->user_id = get_current_user_id();
			$this->upload_user_baseurl = $this->upload_baseurl . $this->user_id;
			$this->upload_user_basedir = $this->upload_basedir . $this->user_id;
			
		} else {
			$this->upload_user_baseurl = $this->upload_baseurl . $this->temp_upload_dir;
			$this->upload_user_basedir = $this->upload_basedir . $this->temp_upload_dir;
		}

		list( $this->upload_user_baseurl, $this->upload_user_basedir ) = apply_filters( 'ofs_change_upload_user_path', array( $this->upload_user_baseurl, $this->upload_user_basedir ), $this->field_key, $this->upload_type );

		if ( $this->replace_upload_dir ) {
			$args['path'] = $this->upload_user_basedir;
			$args['url'] = $this->upload_user_baseurl;
		}

		return $args;
	}


	/**
	 *  Upload Image files
	 *
	 * @param array $uploadedfile
	 * @param int|null $user_id
	 * @param string $field_key
	 * @param string $upload_type
	 *
	 * @since  2.0.22
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function upload_image( $uploadedfile, $user_id = null, $field_key = '', $upload_type = 'profile_photo' ) {
		$response = array();

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		if ( empty( $field_key ) ) {
			$field_key = 'custom_field';
		}

		$this->field_key = $field_key;

		$this->upload_type = 'image';

		$this->upload_image_type = $upload_type;

		if ( $user_id && is_user_logged_in() ) {
			$this->user_id = $user_id;
		}

		if ( in_array( $field_key, array( 'profile_photo', 'cover_photo' ) ) ) {
			$this->upload_image_type = $field_key;
		}


		$field_allowed_file_types = apply_filters( 'ofs_uploader_image_default_filetypes', array( 'JPG', 'JPEG', 'PNG', 'GIF' ) );
		

		$allowed_image_mimes = array();

		foreach ( $field_allowed_file_types as $a ) {
			$atype = wp_check_filetype( "test.{$a}" );
			$allowed_image_mimes[ $atype['ext'] ] = $atype['type'];
		}

		$upload_overrides = array(
			'test_form'                 => false,
			'mimes'                     => apply_filters( 'ofs_uploader_allowed_image_mimes', $allowed_image_mimes ),
			'unique_filename_callback'  => array( $this, 'unique_filename' ),
		);

		$upload_overrides = apply_filters( "ofs_image_upload_handler_overrides__{$field_key}", $upload_overrides );

		$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

		if ( isset( $movefile['error'] ) ) {
			/*
		     * Error generated by _wp_handle_upload()
		     * @see _wp_handle_upload() in wp-admin/includes/file.php
		     */
			$response['error'] = $movefile['error'];
		} else {

			$movefile['url'] = set_url_scheme( $movefile['url'] );

			$movefile['file_info']['basename'] = wp_basename( $movefile['file'] );

			$file_type = wp_check_filetype( $movefile['file_info']['basename'] );

			$movefile['file_info']['name'] = $movefile['url'];
			$movefile['file_info']['original_name'] = $uploadedfile['name'];
			$movefile['file_info']['ext'] = $file_type['ext'];
			$movefile['file_info']['type'] = $file_type['type'];
			$movefile['file_info']['size'] = filesize( $movefile['file'] );
			$movefile['file_info']['size_format'] = size_format( $movefile['file_info']['size'] );
			$movefile['file'] = $movefile['file_info']['basename'];

			$filename = wp_basename( $movefile['url'] );

		}
		// Array
		// (
		//     [handle_upload] => Array
		//         (
		//             [file] => profile_photo.png
		//             [url] => http://wpapp.loc/wp-content/uploads/ofs/5/profile_photo.png
		//             [type] => image/png
		//             [file_info] => Array
		//                 (
		//                     [basename] => profile_photo.png
		//                     [name] => http://wpapp.loc/wp-content/uploads/ofs/5/profile_photo.png
		//                     [original_name] => win10-create-adhoc.PNG
		//                     [ext] => png
		//                     [type] => image/png
		//                     [size] => 42167
		//                     [size_format] => 41 KB
		//                 )

		//         )

		// )
		$response['handle_upload'] = $movefile;

		return $response;
	}


	/**
	 * Upload Files
	 *
	 * @param $uploadedfile
	 * @param int|null $user_id
	 * @param string $field_key
	 *
	 * @since  2.0.22
	 *
	 * @return array
	 */
	public function upload_file( $uploadedfile, $user_id = null, $field_key = '' ) {
		$response = array();

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$this->field_key = $field_key;

		if ( $user_id && is_user_logged_in() ) {
			$this->user_id = $user_id;
		}

		$this->upload_type = 'file';

		$field_allowed_file_types = array(
				'pdf'   => 'PDF',
				'txt'   => 'Text',
				'csv'   => 'CSV',
				'doc'   => 'DOC',
				'docx'  => 'DOCX',
				'odt'   => 'ODT',
				'ods'   => 'ODS',
				'xls'   => 'XLS',
				'xlsx'  => 'XLSX',
				'zip'   => 'ZIP',
				'rar'   => 'RAR',
				'mp3'   => 'MP3',
				'jpg'   => 'JPG',
				'jpeg'  => 'JPEG',
				'png'   => 'PNG',
				'gif'   => 'GIF',
				'eps'   => 'EPS',
				'psd'   => 'PSD',
				'tif'   => 'TIF',
				'tiff'  => 'TIFF',
			);

		$allowed_file_mimes = array();

		foreach ( $field_allowed_file_types as $a ) {
			$atype = wp_check_filetype( "test.{$a}" );
			$allowed_file_mimes[ $atype['ext'] ] = $atype['type'];
		}

		$upload_overrides = array(
			'test_form'                 => false,
			'mimes'                     => apply_filters( 'ofs_uploader_allowed_file_mimes', $allowed_file_mimes ),
			'unique_filename_callback'  => array( $this, 'unique_filename' ),
		);

		$upload_overrides = apply_filters( "ofs_file_upload_handler_overrides__{$field_key}", $upload_overrides );

		$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

		if ( isset( $movefile['error'] ) ) {
			/*
		     * Error generated by _wp_handle_upload()
		     * @see _wp_handle_upload() in wp-admin/includes/file.php
		     */
			$response['error'] = $movefile['error'];
		} else {

			$file_type = wp_check_filetype( $movefile['file'] );

			$movefile['url'] = set_url_scheme( $movefile['url'] );

			$movefile['file_info']['name'] = $movefile['url'];
			$movefile['file_info']['original_name'] = $uploadedfile['name'];
			$movefile['file_info']['basename'] = wp_basename( $movefile['file'] );
			$movefile['file_info']['ext'] = $file_type['ext'];
			$movefile['file_info']['type'] = $file_type['type'];
			$movefile['file_info']['size'] = filesize( $movefile['file'] );
			$movefile['file_info']['size_format'] = size_format( $movefile['file_info']['size'] );

			$filename = wp_basename( $movefile['url'] );

			// $transient = set_transient( "ofs_{$filename}", $movefile['file_info'], 2 * HOUR_IN_SECONDS );
			// if ( empty( $transient ) ) {
			// 	update_user_meta( $this->user_id, "{$field_key}_metadata_temp", $movefile['file_info'] );
			// }
		}

		$response['handle_upload'] = $movefile;

		return $response;
	}


	/**
	 * Check image upload and handle errors
	 *
	 * @param $file
	 * @param $field
	 *
	 * @return null|string
	 */
	public function validate_image_data( $file, $field_key ) {
		$error = null;

		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$image = wp_get_image_editor( $file );
		if ( is_wp_error( $image ) ) {
			$error = sprintf( __( 'Your image is invalid!', 'ultimate-member' ) );
			return $error;
		}

		$image_sizes = $image->get_size();
		$image_info['width'] = $image_sizes['width'];
		$image_info['height'] = $image_sizes['height'];
		$image_info['ratio'] = $image_sizes['width'] / $image_sizes['height'];

		$image_info['quality'] = $image->get_quality();

		$image_type = wp_check_filetype( $file );
		$image_info['extension'] = $image_type['ext'];
		$image_info['mime']= $image_type['type'];
		$image_info['size'] = filesize( $file );


		$data = array(
			'min_size' => 5*KB_IN_BYTES,
			'max_file_size' => 1*MB_IN_BYTES,
			'min_width' => '60',
			'min_height' => '60',
			'max_width' => '450',
			'max_height' => '450',
			'max_file_size_error' => 'Kích thước file quá lớn'
		);

		if ( isset( $image_info['invalid_image'] ) && $image_info['invalid_image'] == true ) {
			$error = sprintf(__('Your image is invalid or too large!') );
		} elseif ( isset($data['min_size']) && ( $image_info['size'] < $data['min_size'] ) ) {
			$error = $data['min_size_error'];
		} elseif ( isset($data['max_file_size']) && ( $image_info['size'] > $data['max_file_size'] ) ) {
			$error = $data['max_file_size_error'];
		} elseif ( isset($data['min_width']) && ( $image_info['width'] < $data['min_width'] ) ) {
			$error = sprintf(__('Your photo is too small. It must be at least %spx wide.'), $data['min_width']);
		} elseif ( isset($data['min_height']) && ( $image_info['height'] < $data['min_height'] ) ) {
			$error = sprintf(__('Your photo is too small. It must be at least %spx wide.'), $data['min_height']);
		} elseif ( isset($data['max_width']) && ( $image_info['width'] > $data['max_width'] ) ) {
			$error = sprintf(__('Your photo is too large. It must be at largest %spx wide.'), $data['max_width']);
		} elseif ( isset($data['max_height']) && ( $image_info['height'] > $data['max_height'] ) ) {
			$error = sprintf(__('Your photo is too large. It must be at largest %spx wide.'), $data['max_height']);
		}

		return $error;
	}


	/**
	 * Check file upload and handle errors
	 *
	 * @param $file
	 * @param $field
	 *
	 * @return null|string
	 */
	public function validate_file_data( $file, $field_key ) {
		$error = null;

		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$file_type = wp_check_filetype( $file );
		$file_info = array();
		$file_info['extension'] = $file_type['ext'];
		$file_info['mime']= $file_type['type'];
		$file_info['size'] = filesize( $file );

		$data = array('max_file_size' => 3000);

		if ( isset( $data['max_file_size'] ) && ( $file_info['size'] > $data['max_file_size'] ) ) {
			$error = $data['max_file_size_error'];
		}

		return $error;
	}



	/**
	 * Make unique filename
	 *
	 * @param  string $dir
	 * @param  string $filename
	 * @param  string $ext
	 * @return string $filename
	 *
	 * @since  2.0.22
	 */
	public function unique_filename( $dir, $filename, $ext ) {

		if ( empty( $ext ) ) {
			$image_type = wp_check_filetype( $filename );
			$ext = strtolower( trim( $image_type['ext'], ' \/.' ) );
		} else {
			$ext = strtolower( trim( $ext, ' \/.' ) );
		}

		if ( 'image' == $this->upload_type ) {

			switch ( $this->upload_image_type ) {

				case 'profile_photo':
				case 'cover_photo':
					$filename = "{$this->upload_image_type}.{$ext}";
					break;

			}

		} elseif ( 'file' == $this->upload_type ) {
			$hashed = hash('ripemd160', time() . mt_rand( 10, 1000 ) );
			$filename = "file_{$hashed}.{$ext}";
		}

		$this->delete_existing_file( $filename, $ext, $dir );

		return $filename;
	}


	/**
	 * Delete file
	 * @param  string $filename
	 * @param  string $ext
	 * @param  string $dir
	 *
	 * @since 2.0.22
	 */
	public function delete_existing_file( $filename, $ext = '', $dir = '' ) {
		if ( file_exists( $this->upload_user_basedir . DIRECTORY_SEPARATOR . $filename  ) && ! empty( $filename ) ) {
			unlink( $this->upload_user_basedir . DIRECTORY_SEPARATOR . $filename );
		}
	}

}

