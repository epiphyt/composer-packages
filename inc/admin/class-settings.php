<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages\admin;

use epiphyt\Composer_Packages\data\Product;
use epiphyt\Composer_Packages\Post_Type;

/**
 * Settings related functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Settings {
	/**
	 * Initialize functions.
	 */
	public static function init(): void {
		\add_action( 'admin_init', [ self::class, 'register' ] );
		\add_action( 'admin_menu', [ self::class, 'register_menu_links' ] );
	}
	
	/**
	 * Get the settings page content.
	 */
	public static function get_page(): void {
		?>
		<div class="wrap">
			<h1><?php \esc_html_e( 'Composer Packages Settings', 'composer-packages' ); ?></h1>
			
			<form method="post" action="options.php" novalidate>
				<div class="composer-packages__container">
					<?php \settings_fields( 'composer_packages_settings' ); ?>
					
					<table class="form-table" role="presentation">
						<?php \do_settings_fields( 'composer_packages_settings', 'composer_packages_settings_section' ); ?>
					</table>
					
					<?php self::get_product_list(); ?>
					
					<?php \submit_button(); ?>
				</div>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Get product list settings.
	 */
	public static function get_product_list(): void {
		$products = Product::get_list();
		?>
		<h2><?php \esc_html_e( 'Products', 'composer-packages' ); ?></h2>
		
		<table class="wp-list-table widefat fixed striped table-view-list">
			<thead>
				<tr>
					<th scope="col" style="width: 300px;"><?php \esc_html_e( 'Product Title', 'composer-packages' ); ?></th>
					<th scope="col" style="width: 300px;"><?php \esc_html_e( 'Product Name', 'composer-packages' ); ?></th>
					<th scope="col" style="width: 200px;"><?php \esc_html_e( 'Type', 'composer-packages' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Base Path', 'composer-packages' ); ?></th>
					<th scope="col" style="width: 120px;"><?php \esc_html_e( 'Actions', 'composer-packages' ); ?></th>
				</tr>
			</thead>
			
			<tbody id="composer-packages__products">
				<?php
				if ( ! empty( $products ) ) :
				foreach ( $products as $key => $product ) :
				?>
				<tr data-item-id="<?php echo \esc_attr( (string) $key ); ?>">
					<td><input type="text" name="composer_packages_products[<?php echo \esc_attr( (string) $key ); ?>][title]" value="<?php echo \esc_attr( $product['title'] ); ?>" class="large-text" data-input-type="title"></td>
					<td><input type="text" name="composer_packages_products[<?php echo \esc_attr( (string) $key ); ?>][name]" value="<?php echo \esc_attr( $product['name'] ); ?>" class="large-text" data-input-type="name"></td>
					<td><input type="text" name="composer_packages_products[<?php echo \esc_attr( (string) $key ); ?>][type]" value="<?php echo \esc_attr( $product['type'] ); ?>" class="large-text" data-input-type="type"></td>
					<td><input type="text" name="composer_packages_products[<?php echo \esc_attr( (string) $key ); ?>][base_path]" value="<?php echo \esc_attr( $product['base_path'] ); ?>" class="large-text" data-input-type="base_path"></td>
					<td><button type="button" class="button button-link-delete composer-packages__delete-button"><?php \esc_html_e( 'Delete package', 'composer-packages' ); ?></button></td>
				</tr>
				<?php
				endforeach;
				else :
				?>
				<tr class="composer-packages__products--not-available">
					<td colspan="5"><?php \esc_html_e( 'No products found.', 'composer-packages' ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
			
			<tfoot>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Product Title', 'composer-packages' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Product Name', 'composer-packages' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Type', 'composer-packages' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Base Path', 'composer-packages' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Actions', 'composer-packages' ); ?></th>
				</tr>
			</tfoot>
		</table>
		
		<p><button type="button" class="button button-secondary composer-packages__add-button"><?php \esc_html_e( 'Add package', 'composer-packages' ); ?></button></p>
		
		<script type="text/html" id="tmpl-product">
			<tr>
				<td><input type="text" name="composer_packages_products[__key][title]" value="" class="large-text" data-input-type="title"></td>
				<td><input type="text" name="composer_packages_products[__key][name]" value="" class="large-text" data-input-type="name"></td>
				<td><input type="text" name="composer_packages_products[__key][type]" value="" class="large-text" data-input-type="type"></td>
				<td><input type="text" name="composer_packages_products[__key][base_path]" value="" class="large-text" data-input-type="base_path"></td>
				<td><button type="button" class="button button-secondary composer-packages__delete-button"><?php \esc_html_e( 'Delete package', 'composer-packages' ); ?></button></td>
			</tr>
		</script>
		<?php
	}
	
	/**
	 * Register settings.
	 */
	public static function register(): void {
		\add_settings_section(
			'composer_packages_settings_section',
			\__( 'Composer Packages Settings', 'composer-packages' ),
			'__return_empty_string',
			'composer_packages_settings'
		);
		\add_settings_field(
			'composer_packages_namespace',
			\__( 'Namespace', 'composer-packages' ),
			[ Field::class, 'get_the_html' ],
			'composer_packages_settings',
			'composer_packages_settings_section',
			[
				'capability' => Post_Type::CAPABILITY,
				'classes' => [
					'regular-text',
				],
				'description' => \__( 'The namespace of your Composer packages, which is added as prefix to your package names.', 'composer-packages' ),
				'label_for' => 'composer_packages_namespace',
				'name' => 'composer_packages_namespace',
				'scope' => [ Post_Type::PACKAGE_NAME ],
				'type' => 'text',
			]
		);
		\register_setting( 'composer_packages_settings', 'composer_packages_namespace' );
		\add_settings_field(
			'composer_packages_products',
			\__( 'Products', 'composer-packages' ),
			[ self::class, 'get_product_list' ],
			'composer_packages_settings',
			'composer_packages_product_settings'
		);
		\register_setting( 'composer_packages_settings', 'composer_packages_products' );
	}
	
	/**
	 * Register menu links.
	 */
	public static function register_menu_links(): void {
		if ( \current_user_can( Post_Type::CAPABILITY ) ) {
			\add_submenu_page(
				'edit.php?post_type=' . Post_Type::PACKAGE_NAME,
				\__( 'Settings', 'composer-packages' ),
				\__( 'Settings', 'composer-packages' ),
				Post_Type::CAPABILITY,
				'composer_packages_settings',
				[ self::class, 'get_page' ]
			);
		}
	}
}
