<?php
if (!defined('ABSPATH')) exit;

final class OFS_Field_Factory {

	public static function load_fields() {
		global $ofs_condition_fields;

		do_action('ofs_load_fields');

		$fields = array();
		$fields_path = OFS_PATH.'/fields';
		$dir = opendir( $fields_path );
		while ( $type = readdir( $dir ) ) {
			if ( $type == "." || $type == "..") {
				continue;
			}

			if ( is_dir( $fields_path.'/'.$type ) ) {
				$fields[$type] = 'OFS_'.ucfirst($type).'_Field';
				require_once $fields_path.'/'.$type.'/class-ofs-'.$type.'-field.php';
			}
		}
		$ofs_condition_fields = apply_filters('ofs_condition_fields', $fields);

		closedir($dir);
	}

	public static function get_fields() {
		global $ofs_condition_fields;
		return $ofs_condition_fields;
	}

	public static function get_field(String $type=''): OFS_Field {
		$fields = self::get_fields();
		
		$type = (!in_array($type, array_keys($fields))) ? '' : $type;

		if(class_exists($fields[$type])) {
			return new $fields[$type];
		} else {
			throw new Exception('Không có loại trường '.$type);
		}
	}
}
