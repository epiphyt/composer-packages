<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages\action;

use epiphyt\Composer_Packages\data\Composer;
use epiphyt\Composer_Packages\data\Package;

/**
 * Request action functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Request {
	/**
	 * Initialize functions.
	 */
	public static function init(): void {
		\add_action( 'init', [ self::class, 'process' ], 100 );
	}
	
	/**
	 * Get URL parts of home URL.
	 * 
	 * @return	array{scheme: string, host: string, port: int<0, 65535>, user: string, pass: string, path: string, query: string, fragment: string} URL parts
	 */
	private static function get_home_url_parts(): array {
		$home_url_parts = \wp_parse_url( \home_url() );
		
		if ( $home_url_parts === false ) {
			$home_url_parts = [];
		}
		
		$home_url_parts = \wp_parse_args(
			$home_url_parts,
			[
				'fragment' => '',
				'host' => '',
				'pass' => '',
				'path' => '/',
				'port' => 0,
				'query' => '',
				'scheme' => '',
				'user' => '',
			]
		);
		
		return $home_url_parts; // @phpstan-ignore return.type
	}
	
	/**
	 * Get a JSON response.
	 * 
	 * @param	mixed	$data Data to return as JSON
	 * @return	string JSON response string
	 */
	private static function get_response( mixed $data ): string {
		return \wp_json_encode( $data, \JSON_PRETTY_PRINT ) ?: '{}';
	}
	
	/**
	 * Check, whether the user is authenticated to download.
	 * 
	 * @return	bool Whether the user is authenticated
	 */
	private static function is_authenticated(): bool {
		$user = \sanitize_text_field( \wp_unslash( $_SERVER['PHP_AUTH_USER'] ?? '' ) );
		$password = \sanitize_text_field( \wp_unslash( $_SERVER['PHP_AUTH_PW'] ?? '' ) );
		
		/**
		 * Filter whether to bypass the builtin authentication.
		 * 
		 * @param	bool	$bypass Whether to bypass the authentication
		 * @param	string	$user HTTP auth username
		 * @param	string	$password HTTP auth password
		 */
		$bypass = (bool) \apply_filters( 'composer_packages_authentication_bypass', false, $user, $password );
		
		if ( $bypass ) {
			return $bypass;
		}
		
		return Authentication::is_authenticated( $user, $password );
	}
	
	/**
	 * Process request.
	 */
	public static function process(): void {
		if ( \is_admin() || \is_login() || \wp_doing_ajax() || \wp_doing_cron() ) {
			return;
		}
		
		\header( 'Content-Type: application/json; charset=UTF-8' );
		
		$home_url_parts = self::get_home_url_parts();
		$home_url_parts['path'] = \trailingslashit( $home_url_parts['path'] );
		$request_uri = \wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		if ( \str_contains( $request_uri, '?' ) ) {
			$request_uri = \substr( $request_uri, 0, \strpos( $request_uri, '?' ) ); // @phpstan-ignore argument.type
		}
		
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.NonceVerification.Recommended
		if ( $request_uri === $home_url_parts['path'] . 'packages.json' ) {
			echo self::get_response( Composer::get_packages() );
			exit;
		}
		
		if ( ! empty( $_GET['name'] ) && ! empty( $_GET['version'] ) ) {
			if ( ! self::is_authenticated() ) {
				\http_response_code( 401 );
				echo self::get_response( [
					'code' => 'invalid_auth',
					'message' => \__( 'Please provide a valid basic auth file via auth.json, using your email address as the username, and your license key as the password.', 'composer-packages' ),
				] );
				exit;
			}
			
			$package = \sanitize_text_field( \wp_unslash( $_GET['name'] ) );
			$version = \sanitize_text_field( \wp_unslash( $_GET['version'] ) );
			
			if ( ! Package::get( $package, $version ) ) {
				\http_response_code( 404 );
				echo self::get_response( [
					'code' => 'file_not_found',
					'message' => \__( 'The requested file could not be found. Please verify that the product in this version exists.', 'composer-packages' ),
				] );
			}
			exit;
		}
		
		if ( $request_uri === $home_url_parts['path'] ) {
			echo self::get_response( [
				'name' => \__( 'Epiphyt Packages API', 'composer-packages' ),
			] );
		}
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
