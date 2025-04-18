<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages\data;

/**
 * Composer data functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Composer {
	/**
	 * Get the Composer package namespace.
	 * 
	 * @return	string Composer package namespace
	 */
	public static function get_namespace(): string {
		$option = \get_option( 'composer_packages_namespace', '' );
		
		if ( ! \is_string( $option ) ) {
			$option = '';
		}
		
		return $option;
	}
	
	/**
	 * Get all packages.
	 * 
	 * @return	array{packages: array{}|array{array{dist: array{type: string, url: string}, name: string, type: string, version: string}}} Composer packages
	 */
	public static function get_packages(): array {
		$namespace = self::get_namespace();
		$packages = [];
		$packages_data = Package::get_data_list();
		
		foreach ( $packages_data as $data ) {
			$package_name = $namespace . '/' . $data['name'];
			$packages[ $package_name ][ $data['version'] ] = [
				'dist' => [
					'type' => 'zip',
					'url' => $data['url'],
				],
				'name' => $package_name,
				'type' => 'wordpress-plugin',
				'version' => $data['version'],
			];
		}
		
		return [
			'packages' => $packages,
		];
	}
}
