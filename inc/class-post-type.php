<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages;

use epiphyt\Composer_Packages\admin\Post_Fields;
use epiphyt\Composer_Packages\data\Product;

/**
 * Post type related functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Post_Type {
	public const CAPABILITY = 'manage_options';
	public const PACKAGE_NAME = 'composer_package';
	
	/**
	 * Initialize functions.
	 */
	public static function init(): void {
		\add_action( 'add_meta_boxes', [ self::class, 'register_meta_boxes' ] );
		\add_action( 'do_meta_boxes', [ self::class, 'remove_default_fields' ] );
		\add_action( 'init', [ self::class, 'register_post_meta' ] );
		\add_action( 'init', [ self::class, 'register_post_type' ] );
		\add_filter( 'wp_insert_post_data', [ self::class, 'set_post_title' ] );
	}
	
	/**
	 * Add custom meta boxes.
	 */
	public static function register_meta_boxes(): void {
		\add_meta_box(
			'composer-packages-meta-fields',
			\__( 'Data', 'composer-packages' ),
			[ Post_Fields::class, 'get_html' ],
			self::PACKAGE_NAME,
			'normal',
			'high'
		);
	}
	
	/**
	 * Register post meta fields.
	 */
	public static function register_post_meta(): void {
		\register_post_meta(
			self::PACKAGE_NAME,
			'name',
			[
				'label' => \__( 'Package name', 'composer-packages' ),
				'show_in_rest' => true,
			]
		);
		\register_post_meta(
			self::PACKAGE_NAME,
			'version',
			[
				'label' => \__( 'Package version', 'composer-packages' ),
				'show_in_rest' => true,
			]
		);
	}
	
	/**
	 * Register post type.
	 */
	public static function register_post_type(): void {
		\register_post_type(
			self::PACKAGE_NAME,
			[
				'capability_type' => self::CAPABILITY,
				'exclude_from_search' => true,
				'has_archive' => false,
				'hierarchical' => false,
				'labels' => [
					'add_new' => \__( 'Add Package', 'composer-packages' ),
					'add_new_item' => \__( 'Add Package', 'composer-packages' ),
					'all_items' => \__( 'All Packages', 'composer-packages' ),
					'archives' => \__( 'Package Archive', 'composer-packages' ),
					'attributes' => \__( 'Package Attributes', 'composer-packages' ),
					'edit_item' => \__( 'Edit Package', 'composer-packages' ),
					'featured_image' => \__( 'Featured Image', 'composer-packages' ),
					'filter_items_list' => \__( 'Filter Packages list', 'composer-packages' ),
					'insert_into_item' => \__( 'Insert into Package', 'composer-packages' ),
					'items_list' => \__( 'Packages list', 'composer-packages' ),
					'items_list_navigation' => \__( 'Packages list navigation', 'composer-packages' ),
					'menu_name' => \__( 'Packages', 'composer-packages' ),
					'name' => \_x( 'Packages', 'Post Type General Name', 'composer-packages' ),
					'name_admin_bar' => \__( 'Packages', 'composer-packages' ),
					'new_item' => \__( 'New Package', 'composer-packages' ),
					'not_found' => \__( 'Not found', 'composer-packages' ),
					'not_found_in_trash' => \__( 'Not found in Trash', 'composer-packages' ),
					'parent_item_colon' => '',
					'remove_featured_image' => \__( 'Remove featured image', 'composer-packages' ),
					'search_items' => \__( 'Search Package', 'composer-packages' ),
					'set_featured_image' => \__( 'Set featured image', 'composer-packages' ),
					'singular_name' => \_x( 'Package', 'Post Type Singular Name', 'composer-packages' ),
					'update_item' => \__( 'Update Package', 'composer-packages' ),
					'uploaded_to_this_item' => \__( 'Uploaded to this Package', 'composer-packages' ),
					'use_featured_image' => \__( 'Use as featured image', 'composer-packages' ),
					'view_item' => \__( 'View Package', 'composer-packages' ),
					'view_items' => \__( 'View Packages', 'composer-packages' ),
				],
				'map_meta_cap' => true,
				'menu_icon' => 'dashicons-archive',
				'public' => false,
				'publicly_queryable' => false,
				'rest_base' => self::PACKAGE_NAME,
				'show_in_menu' => \current_user_can( self::CAPABILITY ),
				'show_in_nav_menus' => false,
				'show_in_rest' => false,
				'show_ui' => \current_user_can( self::CAPABILITY ),
				'supports' => [
					'custom-fields',
					'title',
				],
			]
		);
	}
	
	/**
	 * Remove default meta box "Custom Fields”.
	 */
	public static function remove_default_fields(): void {
		foreach ( [ 'normal', 'advanced', 'side' ] as $context ) {
			\remove_meta_box( 'postcustom', self::PACKAGE_NAME, $context );
		}
	}
	
	/**
	 * Set post title with package information.
	 * 
	 * @param	string[]	$post_data Current post data
	 * @return	string[] Updated post data
	 */
	public static function set_post_title( array $post_data ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! empty( $post_data['post_title'] ) || empty( $_POST['name'] ) ) {
			return $post_data;
		}
		
		$name = \sanitize_text_field( \wp_unslash( $_POST['name'] ) );
		$product = Product::get_by_name( $name );
		$version = \sanitize_text_field( \wp_unslash( $_POST['version'] ?? \_x( 'unknown', 'unknown version number', 'composer-packages' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		
		if ( ! empty( $product['title'] ) ) {
			$post_data['post_title'] = \trim( \sprintf( '%1$s v%2$s', $product['title'], $version ) );
		}
		
		return $post_data;
	}
}
