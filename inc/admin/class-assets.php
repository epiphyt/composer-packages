<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages\admin;

use WP_Screen;

/**
 * Admin assets related functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Assets {
	/**
	 * Initialize functions.
	 */
	public static function init(): void {
		\add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue' ] );
	}
	
	/**
	 * Enqueue admin assets.
	 */
	public static function enqueue(): void {
		$screen = \get_current_screen();
		
		if ( ! $screen instanceof WP_Screen || $screen->id !== 'composer_package_page_composer_packages_settings' ) {
			return;
		}
		
		$is_debug = \defined( 'WP_DEBUG' ) && \WP_DEBUG || \defined( 'SCRIPT_DEBUG' ) && \SCRIPT_DEBUG;
		$scripts = [
			[
				'dependencies' => [ 'jquery', 'wp-util' ],
				'name' => 'repeater',
			],
			[
				'dependencies' => [],
				'name' => 'validation',
			],
		];
		$suffix = $is_debug ? '' : '.min';
		
		foreach ( $scripts as $script ) {
			$file_path = \EPI_COMPOSER_PACKAGES_DIR . 'assets/js/' . ( $is_debug ? '' : 'build/' ) . $script['name'] . $suffix . '.js';
			$file_url = \EPI_COMPOSER_PACKAGES_URL . 'assets/js/' . ( $is_debug ? '' : 'build/' ) . $script['name'] . $suffix . '.js'; // @phpstan-ignore constant.notFound
			$file_version = $is_debug ? (string) \filemtime( $file_path ) : \EPI_COMPOSER_PACKAGES_VERSION;
			
			\wp_enqueue_script( 'composer-packages-' . $script['name'], $file_url, $script['dependencies'], $file_version, true );
		}
		
		\wp_localize_script( 'composer-packages-validation', 'composerPackages', [
			'i18n' => [
				'errors' => [
					'empty' => \esc_js( \__( 'The field must not be empty.', 'composer-packages' ) ),
				],
				'noDataFound' => \esc_js( \__( 'No products found.', 'composer-packages' ) ),
			],
		] );
		
		$file_path = \EPI_COMPOSER_PACKAGES_DIR . 'assets/style/build/admin' . $suffix . '.css';
		$file_url = \EPI_COMPOSER_PACKAGES_URL . 'assets/style/build/admin' . $suffix . '.css'; // @phpstan-ignore constant.notFound
		$file_version = $is_debug ? (string) \filemtime( $file_path ) : \EPI_COMPOSER_PACKAGES_VERSION;
		
		\wp_enqueue_style( 'composer-packages-admin-style', $file_url, [], $file_version );
	}
}
