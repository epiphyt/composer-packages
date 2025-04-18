<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages\data;

/**
 * PRoduct data functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Product {
	/**
	 * Get a product by name.
	 * 
	 * @param	string	$name Package name
	 * @return	array{}|array{base_path: string, name: string, title: string, type: string} Product data
	 */
	public static function get_by_name( string $name ): array {
		foreach ( self::get_list() as $product ) {
			if ( $product['name'] === $name ) {
				return $product;
			}
		}
		
		return [];
	}
	
	/**
	 * Get product list.
	 * 
	 * @return	array{}|array{array{base_path: string, name: string, title: string, type: string}} Product list
	 */
	public static function get_list(): array {
		$products = \get_option( 'composer_packages_products', [] );
		
		if ( ! \is_array( $products ) ) {
			$products = [];
		}
		
		return $products;
	}
}
