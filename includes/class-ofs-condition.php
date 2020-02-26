<?php
if (!defined('ABSPATH')) exit;

class OFS_Condition {

	private $ID = 0;

	private $post = null;

	private $fields = array();

	private $required = array();

	public function __construct($post) {
		if($post instanceof self) {
			$post = $post->get_post();
		}
		
		$condition_post = get_post($post);

		if($condition_post && $condition_post->post_type=='condition') {
			$this->post = $condition_post;
			$this->ID = absint($condition_post->ID);

			$this->load_fields();

		} else {
			$this->ID = 0;
		}
	}

	public function get_thumbnail_image($size='thumbnail') {
		return '<img src="'.esc_url($this->get_thumbnail_src($size)).'" alt="'.esc_attr($this->get_title()).'">';
	}

	public function get_thumbnail_src($size='thumbnail') {
		$src = wp_get_attachment_image_src( $this->get_thumbnail_id(), $size, true );
		return isset($src[0]) ? $src[0]: '';
	}

	public function get_thumbnail_id() {
		return get_post_thumbnail_id($this->post);
	}

	public function get_filter_url() {
		return get_permalink($this->get_id());
	}

	public function get_fields() {
		return $this->fields;
	}

	public function get_id() {
		return $this->ID;
	}

	public function get_title() {
		if($this->ID) {
			return $this->post->post_title;
		}
		return '';
	}

	public function get_post() {
		return $this->post;
	}

	private function load_fields() {
		$this->fields = ofs_get_model()->get_condition_fields($this->ID);
	}


}