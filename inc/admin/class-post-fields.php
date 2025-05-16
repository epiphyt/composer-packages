<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages\admin;

use epiphyt\Composer_Packages\data\Product;
use epiphyt\Composer_Packages\Post_Type;

/**
 * Post fields related functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Post_Fields {
	public const NONCE_ACTION = 'composer_packages_fields';
	public const NONCE_NAME = 'composer_packages_fields_fields_wpnonce';
	
	/**
	 * Initialize functionality.
	 */
	public static function init(): void {
		\add_action( 'save_post_' . Post_Type::PACKAGE_NAME, [ self::class, 'save' ] );
		\add_filter( 'composer_packages_field_current_value', [ Field::class, 'get_new_post_value' ], 10, 2 );
	}
	
	/**
	 * Get all settings fields.
	 * 
	 * @return	array{
	 * 	array{
	 * 		classes?: string[],
	 * 		description?: string,
	 * 		name: string,
	 * 		option_type?: string,
	 * 		options?: string[][],
	 * 		scope: string[],
	 * 		title: string,
	 * 		type: string,
	 * 	}
	 * } Settings fields
	 */
	public static function get(): array {
		$products = Product::get_list();
		$product_options = \array_map( static function( array $product ) {
			return [
				'label' => $product['title'],
				'value' => $product['name'],
			];
		}, $products );
		
		return [
			[
				'name' => 'name',
				'options' => $product_options,
				'option_type' => 'postmeta',
				'scope' => [ Post_Type::PACKAGE_NAME ],
				'title' => \__( 'Product', 'composer-packages' ),
				'type' => 'select',
			],
			[
				'name' => 'version',
				'option_type' => 'postmeta',
				'scope' => [ Post_Type::PACKAGE_NAME ],
				'title' => \__( 'Version', 'composer-packages' ),
				'type' => 'text',
			],
			[
				'name' => 'authentication_required',
				'option_type' => 'postmeta',
				'scope' => [ Post_Type::PACKAGE_NAME ],
				'title' => \__( 'Authentication required', 'composer-packages' ),
				'type' => 'checkbox',
			],
		];
	}
	
	/**
	 * Get fields HTML.
	 */
	public static function get_html(): void {
		\wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<?php
				foreach ( self::get() as $field ) {
					?>
					<tr class="form-field">
						<th scope="row">
							<?php if ( $field['type'] !== 'checkbox' && $field['type'] !== 'radio' ) : ?>
							<label for="<?php echo \esc_attr( $field['name'] ); ?>"><?php echo \esc_html( $field['title'] ); ?></label>
							<?php endif; ?>
						</th>
						<td><?php Field::get_the_html( $field ); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}
	
	/**
	 * Sanitize values.
	 * 
	 * @param	string[][]|string[]|string	$value Value to sanitize
	 * @param	string						$field_name Name of the field to sanitize
	 * @return	mixed[]|string Sanitized value
	 */
	private static function sanitize( array|string $value, string $field_name ): array|string {
		if ( ! \is_array( $value ) ) {
			if ( $field_name === 'html' ) {
				$sanitized_value = \trim( \wp_kses_post( $value ) );
			}
			else if ( \str_contains( $value, \PHP_EOL ) ) {
				$sanitized_value = \trim( \sanitize_textarea_field( \wp_unslash( $value ) ) );
			}
			else {
				$sanitized_value = \trim( \sanitize_text_field( \wp_unslash( $value ) ) );
			}
		}
		else {
			$sanitized_value = [];
			
			foreach ( $value as $key => $items ) {
				$sanitized_value[ $key ] = self::sanitize( $items, $field_name );
			}
		}
		
		return $sanitized_value;
	}
	
	/**
	 * Save the fields as post meta.
	 * 
	 * @param	int			$post_id The ID of the post
	 */
	public static function save( int $post_id ): void {
		// verify nonce
		if (
			empty( $_POST[ self::NONCE_NAME ] )
			|| ! \is_string( $_POST[ self::NONCE_NAME ] )
			|| ! \wp_verify_nonce( \sanitize_key( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION )
		) {
			new \WP_Error( 400, \__( 'Invalid nonce.', 'composer-packages' ) );
		}
		
		// verify capability
		if ( ! \current_user_can( Post_Type::CAPABILITY, $post_id ) ) {
			new \WP_Error( 403, \__( 'You are not allowed to edit a package.', 'composer-packages' ) );
		}
		
		// e.g. on trash this gets fired but without POST data
		// this would den result in deleting the post meta data
		if (
			( ! isset( $_POST['action'] ) || $_POST['action'] !== 'editpost' ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			&& ( ! \defined( 'REST_REQUEST' ) || ( \defined( 'REST_REQUEST' ) && ! \REST_REQUEST ) )
		) {
			return;
		}
		
		// store fields
		foreach ( self::get() as $field ) {
			$post_value = ( ! empty( $_POST[ $field['name'] ] ) ? \wp_unslash( $_POST[ $field['name'] ] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			
			if ( ! empty( $post_value ) && ( \is_string( $post_value ) || \is_array( $post_value ) ) ) {
				$value = self::sanitize( $post_value, $field['name'] );
				
				\update_post_meta( $post_id, $field['name'], $value );
			}
			else {
				\delete_post_meta( $post_id, $field['name'] );
			}
		}
	}
}
