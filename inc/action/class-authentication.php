<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages\action;

use epiphyt\Composer_Packages\data\Product;

/**
 * Authentication action functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Authentication {
	/**
	 * Check, whether the request is authenticated.
	 * 
	 * @param	string	$username Authentication username
	 * @param	string	$password Authentication password
	 * @return	bool Wether the request is authenticated
	 */
	public static function is_authenticated( string $username, string $password ): bool {
		/**
		 * Filter whether authentication is required.
		 * 
		 * @param	bool	$requires_authentication Wether authentication is required
		 */
		$requires_authentication = \apply_filters( 'composer_packages_authentication_required', true );
		
		if ( ! $requires_authentication ) {
			return true;
		}
		
		if ( empty( $username ) || empty( $password ) ) {
			return false;
		}
		
		return self::has_valid_credentials( $username, $password );
	}
	
	/**
	 * Check, whether credentials are valid.
	 * 
	 * @param	string	$username Authentication username
	 * @param	string	$password Authentication password
	 * @return	bool Whether credentials are valid
	 */
	private static function has_valid_credentials( string $username, string $password ): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$package = \sanitize_text_field( \wp_unslash( $_GET['name'] ) );
		$product = Product::get_by_name( $package );
		$version = \sanitize_text_field( \wp_unslash( $_GET['version'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		
		if ( empty( $package ) || empty( $version ) || empty( $product ) ) {
			return false;
		}
		
		$data = [
			'password' => $password,
			'product' => $product,
			'username' => $username,
			'version' => $version,
		];
		
		foreach ( [ 1, 5 ] as $blog_id ) {
			if ( self::has_active_software_license( $blog_id, $data ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Check for an active software license in WooCommerce Software Add-on.
	 * 
	 * @param	int		$blog_id Blog ID to search in
	 * @param	array{password: string, product: array{base_path: string, name: string, title: string, type: string}, username: string, version: string}	$data Data to check for
	 * @return	bool Whether an active software license is available
	 */
	private static function has_active_software_license( int $blog_id, array $data ): bool {
		$has_license = false;
		
		\switch_to_blog( $blog_id );
		
		global $wpdb;
		
		/** @var \wpdb $wpdb */
		/** @var array{}|array{object{key_id: string, order_id: string, activation_email: string, license_key: string, software_product_id: string, software_version: string, activations_limit: string, created: string}} $licenses */
		$licenses = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT		*
				FROM		%i
				WHERE		activation_email = %s
				AND			license_key = %s
				AND			software_product_id = %s",
				"{$wpdb->prefix}woocommerce_software_licenses",
				$data['username'],
				$data['password'],
				$data['product']['title']
			)
		);
		
		foreach ( $licenses as $license ) {
			if ( \version_compare( $license->software_version . '.999', $data['version'] ) !== -1 ) {
				$has_license = true;
				break;
			}
		}
		
		\restore_current_blog();
		
		return $has_license;
	}
}
