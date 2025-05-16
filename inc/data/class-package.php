<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages\data;

use epiphyt\Composer_Packages\Plugin;
use epiphyt\Composer_Packages\Post_Type;
use WP_Query;

/**
 * Package data functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Package {
	/**
	 * Get a package.
	 * 
	 * @param	string	$name Package name
	 * @param	string	$version Package version
	 * @return	bool Whether the package has been found and downloaded
	 */
	public static function get( string $name, string $version ): bool {
		$data = self::get_data( $name, $version );
		
		if ( ! empty( $data['file'] ) && \file_exists( $data['file'] ) ) {
			\header( 'Content-Type: application/zip' );
			\header( 'Content-Transfer-Encoding: Binary' );
			\header( 'Content-Disposition: attachment; filename="' . \basename( $data['file'] ) . '"' );
			\readfile( $data['file'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get package data
	 * 
	 * @param	string	$name Package name
	 * @param	string	$version Package version
	 * @return	array{}|array{authentication_required: bool, file: string, name: string, version: string} Package data
	 */
	public static function get_data( string $name, string $version ): array {
		$query = new WP_Query( [
			'fields' => 'ids',
			'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query, SlevomatCodingStandard.Arrays.DisallowPartiallyKeyed.DisallowedPartiallyKeyed
				'relation' => 'AND',
				// phpcs:disable Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey, Universal.Arrays.MixedKeyedUnkeyedArray.Found
				[
					'key' => 'name',
					'value' => $name,
				],
				[
					'key' => 'version',
					'value' => $version,
				],
				// phpcs:enable Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey, Universal.Arrays.MixedKeyedUnkeyedArray.Found
			],
			'no_found_posts' => true,
			'posts_per_page' => 1,
			'post_type' => Post_Type::PACKAGE_NAME,
		] );
		$post_ids = $query->get_posts();
		
		if ( empty( $post_ids ) ) {
			return [];
		}
		
		/** @var int $post_id */
		$post_id = \reset( $post_ids );
		$file = '';
		$name = Plugin::mixed_to_string( \get_post_meta( $post_id, 'name', true ) );
		$authentication_required = Plugin::mixed_to_string( \get_post_meta( $post_id, 'authentication_required', true ) ) === 'yes';
		$version = Plugin::mixed_to_string( \get_post_meta( $post_id, 'version', true ) );
		
		if ( ! empty( $name ) ) {
			$product = Product::get_by_name( $name );
			$path = \rtrim( $product['base_path'] ?? '', '/' );
			
			if ( ! empty( $path ) && $path !== '/' && ! \str_contains( $path, '..' ) ) {
				$file = \sprintf( '%1$s/%2$s-v%3$s.zip', $path, $name, $version );
			}
		}
		
		return [
			'authentication_required' => $authentication_required,
			'file' => $file,
			'name' => $name,
			'version' => $version,
		];
	}
	
	/**
	 * Get list of package data.
	 * 
	 * @return	array{}|array{array{name: string, url: array{package: string, version: string}, version: string}} List of package data
	 */
	public static function get_data_list(): array {
		$query = new WP_Query( [
			'fields' => 'ids',
			'no_found_posts' => true,
			'posts_per_page' => -1,
			'post_type' => Post_Type::PACKAGE_NAME,
		] );
		$packages = [];
		$post_ids = $query->get_posts();
		
		if ( empty( $post_ids ) ) {
			return [];
		}
		
		/** @var int $post_id */
		foreach ( $post_ids as $post_id ) {
			$packages[] = [
				'name' => Plugin::mixed_to_string( \get_post_meta( $post_id, 'name', true ) ),
				'url' => \home_url( '?' . \http_build_query( [
					'name' => Plugin::mixed_to_string( \get_post_meta( $post_id, 'name', true ) ),
					'version' => Plugin::mixed_to_string( \get_post_meta( $post_id, 'version', true ) ),
				] ) ),
				'version' => Plugin::mixed_to_string( \get_post_meta( $post_id, 'version', true ) ),
			];
		}
		
		return $packages;
	}
}
