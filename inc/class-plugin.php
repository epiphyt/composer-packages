<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages;

use epiphyt\Composer_Packages\action\Request;
use epiphyt\Composer_Packages\admin\Assets;
use epiphyt\Composer_Packages\admin\Settings;

/**
 * The main plugin class.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Plugin {
	/**
	 * Initialize functions.
	 */
	public static function init(): void {
		\add_action( 'init', [ self::class, 'load_textdomain' ], 0 );
		
		Assets::init();
		Post_Type::init();
		Request::init();
		Settings::init();
	}
	
	/**
	 * Load translations.
	 */
	public static function load_textdomain(): void {
		\load_plugin_textdomain( 'composer-packages', false, \dirname( \plugin_basename( \EPI_COMPOSER_PACKAGES_FILE ) ) . '/languages' );
	}
	
	/**
	 * Transform mixed to string.
	 * 
	 * @param	mixed	$data Mixed data
	 * @return	string Stringified data
	 */
	public static function mixed_to_string( mixed $data ): string {
		if ( ! \is_string( $data ) ) {
			return '';
		}
		
		return $data;
	}
}
