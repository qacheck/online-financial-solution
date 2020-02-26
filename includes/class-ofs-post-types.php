<?php
if (!defined('ABSPATH')) exit;

class OFS_Post_Types {
	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action('init', array( __CLASS__, 'register_post_types' ), 5);
		add_action('ofs_after_register_post_type', array(__CLASS__, 'maybe_flush_rewrite_rules'));
	}

	/**
	 * Get capabilities for WooCommerce - these are assigned to admin/shop manager during installation or reset.
	 *
	 * @return array
	 */
	public static function get_capabilities() {
		$capabilities = array();

		$capability_types = array( 'condition', 'borrower' );

		foreach ( $capability_types as $capability_type ) {

			$capabilities[ $capability_type ] = array(
				// Post type.
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				/*
				// Terms.
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",
				*/
			);
		}

		return $capabilities;
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'condition' ) ) {
			return;
		}

		register_post_type(
			'condition',
			array(
				'labels'              => array(
					'name'                  => __( 'Conditions', 'ofs' ),
					'singular_name'         => __( 'condition', 'ofs' ),
					'all_items'             => __( 'All Conditions', 'ofs' ),
					'menu_name'             => _x( 'Conditions', 'Admin menu name', 'ofs' ),
					'add_new'               => __( 'Add New', 'ofs' ),
					'add_new_item'          => __( 'Add new condition', 'ofs' ),
					'edit'                  => __( 'Edit', 'ofs' ),
					'edit_item'             => __( 'Edit condition', 'ofs' ),
					'new_item'              => __( 'New condition', 'ofs' ),
					'view_item'             => __( 'View condition', 'ofs' ),
					'view_items'            => __( 'View Conditions', 'ofs' ),
					'search_items'          => __( 'Search Conditions', 'ofs' ),
					'not_found'             => __( 'No Conditions found', 'ofs' ),
					'not_found_in_trash'    => __( 'No Conditions found in trash', 'ofs' ),
					'parent'                => __( 'Parent condition', 'ofs' ),
					'featured_image'        => __( 'condition image', 'ofs' ),
					'set_featured_image'    => __( 'Set condition image', 'ofs' ),
					'remove_featured_image' => __( 'Remove condition image', 'ofs' ),
					'use_featured_image'    => __( 'Use as condition image', 'ofs' ),
					'insert_into_item'      => __( 'Insert into condition', 'ofs' ),
					'uploaded_to_this_item' => __( 'Uploaded to this condition', 'ofs' ),
					'filter_items_list'     => __( 'Filter Conditions', 'ofs' ),
					'items_list_navigation' => __( 'Conditions navigation', 'ofs' ),
					'items_list'            => __( 'Conditions list', 'ofs' ),
				),
				'public'              => true,
				'show_ui'             => true,
				'menu_icon'          => 'dashicons-feedback',
				'capability_type'     => 'condition',
				'map_meta_cap'        => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
				'rewrite'             => array(
					'slug'       => 'condition',
					'with_front' => false,
					'feeds'      => false,
				),
				'query_var'           => true,
				'supports'            => array( 'title', 'thumbnail' ),
				'has_archive'         => false,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => false,
			)
		);

		register_post_type( 'borrower',
			array(
				'labels'              => array(
					'name'                  => __( 'Borrowers', 'ofs' ),
					'singular_name'         => __( 'Borrower', 'ofs' ),
					'all_items'             => __( 'All Borrowers', 'ofs' ),
					'menu_name'             => _x( 'Borrowers', 'Admin menu name', 'ofs' ),
					'add_new'               => __( 'Add New', 'ofs' ),
					'add_new_item'          => __( 'Add new Borrower', 'ofs' ),
					'edit'                  => __( 'Edit', 'ofs' ),
					'edit_item'             => __( 'Edit Borrower', 'ofs' ),
					'new_item'              => __( 'New Borrower', 'ofs' ),
					'view_item'             => __( 'View Borrower', 'ofs' ),
					'view_items'            => __( 'View Borrowers', 'ofs' ),
					'search_items'          => __( 'Search Borrowers', 'ofs' ),
					'not_found'             => __( 'No Borrowers found', 'ofs' ),
					'not_found_in_trash'    => __( 'No Borrowers found in trash', 'ofs' ),
					'parent'                => __( 'Parent Borrower', 'ofs' ),
					'featured_image'        => __( 'Borrower image', 'ofs' ),
					'set_featured_image'    => __( 'Set Borrower image', 'ofs' ),
					'remove_featured_image' => __( 'Remove Borrower image', 'ofs' ),
					'use_featured_image'    => __( 'Use as Borrower image', 'ofs' ),
					'insert_into_item'      => __( 'Insert into Borrower', 'ofs' ),
					'uploaded_to_this_item' => __( 'Uploaded to this Borrower', 'ofs' ),
					'filter_items_list'     => __( 'Filter Borrowers', 'ofs' ),
					'items_list_navigation' => __( 'Borrowers navigation', 'ofs' ),
					'items_list'            => __( 'Borrowers list', 'ofs' ),
				),
				'public'             => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'publicly_queryable' => false,
				/* queries can be performed on the front end */
				'has_archive'        => false,
				'exclude_from_search' => true,
				'rewrite'            => false,
				'menu_position'      => 4,
				'show_in_nav_menus'  => false,
				'menu_icon'          => 'dashicons-businessman',
				'hierarchical'       => false,
				'query_var'          => false,
				'show_in_rest' 		 => false,
				'supports'           => array('title', 'excerpt'),
				'capability_type'    => 'borrower',
				'map_meta_cap'		 => true
			)
		);
		
		do_action('ofs_after_register_post_type');
	}

	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'ofs_queue_flush_rewrite_rules' ) ) {
			update_option( 'ofs_queue_flush_rewrite_rules', 'no' );
			flush_rewrite_rules();
		}
	}
}

OFS_Post_Types::init();
