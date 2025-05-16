<?php
declare(strict_types = 1);

namespace epiphyt\Composer_Packages\admin;

use epiphyt\Composer_Packages\Post_Type;
use WP_Screen;

/**
 * Field-related functionality.
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Composer_Packages
 */
final class Field {
	/**
	 * Get field HTML.
	 * 
	 * @param	array{classes?: string[], description?: string, max?: int, min?: int, multiple?: bool, name: string, option_type?: string, options?: string[][], scope: string[], title: string, type: string}	$field Field data
	 */
	public static function get_the_html( array $field ): void {
		$output = false;
		
		foreach ( $field['scope'] as $scope ) {
			if ( $scope === Post_Type::PACKAGE_NAME ) {
				$output = true;
				break;
			}
		}
		
		// check if field type exists
		if ( empty( $field['type'] ) ) {
			$output = false;
		}
		
		// continue with next field if we shouldn't output this field
		if ( ! $output ) {
			return;
		}
		
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		switch ( $field['type'] ) {
			case 'select':
				echo self::get_select( $field );
				break;
			case 'textarea':
				echo self::get_type_textarea( $field );
				break;
			case 'text':
			default:
				echo self::get_type_text( $field );
				break;
		}
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	
	/**
	 * Get a new field value on 'new post' page.
	 * 
	 * @param	string	$current_value Current value
	 * @param	array{classes?: string[], description?: string, max?: int, min?: int, multiple?: bool, name: string, option_type?: string, options?: string[][], scope: string[], title: string, type: string}	$field Field data
	 * @return	string Updated field value
	 */
	public static function get_new_post_value( string $current_value, array $field ): string {
		$screen = \get_current_screen();
		
		if ( ! $screen instanceof WP_Screen ) {
			return $current_value;
		}
		
		if ( $screen->action === 'add' && $screen->id === Post_Type::PACKAGE_NAME ) {
			if ( $field['name'] === 'authentication_required' ) {
				$current_value = 'yes';
			}
		}
		
		return $current_value;
	}
	
	/**
	 * Get HTML for field type 'select'.
	 * 
	 * @param	array{classes?: string[], description?: string, max?: int, min?: int, multiple?: bool, name: string, option_type?: string, options?: string[][], scope: string[], title: string, type: string}	$field Field data
	 * @return	string Field HTML
	 */
	private static function get_select( array $field ): string {
		if ( empty( $field['option_type'] ) || $field['option_type'] === 'option' ) {
			$current_value = \get_option( $field['name'], '' );
		}
		else {
			global $post;
			
			$current_value = \get_post_meta( $post->ID, $field['name'], true );
		}
		
		$options = $field['options'] ?? [];
		
		if ( empty( $field['multiple'] ) && ! \is_string( $current_value ) ) {
			$current_value = '';
		}
		else if ( ! empty( $field['multiple'] ) && ! \is_array( $current_value ) ) {
			$current_value = [];
		}
		
		\ob_start();
		?>
		<select id="<?php echo \esc_attr( $field['name'] ); ?>" name="<?php echo \esc_attr( $field['name'] ); ?><?php echo ! empty( $field['multiple'] ) ? '[]' : ''; ?>"<?php echo ! empty( $field['classes'] ) ? ' class="' . \implode( ' ', \array_map( 'sanitize_html_class', $field['classes'] ) ) . '"' : ''; ?><?php echo ! empty( $field['multiple'] ) ? ' multiple' : '' ?>>
			<?php if ( empty( $field['multiple'] ) ) : ?><option label="<?php \esc_attr_e( '– Please select –', 'composer-packages' ); ?>"></option><?php endif; ?>
			<?php
			foreach ( $options as $option ) :
			if ( empty( $field['multiple'] ) ) {
				$selected_attribute = \selected( $current_value, $option['value'], false );
			}
			else {
				$selected_attribute = \selected( \in_array( $option['value'], (array) $current_value, true ), true, false );
			}
			?>
			<option value="<?php echo \esc_attr( $option['value'] ); ?>"<?php echo $selected_attribute; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo \esc_html( $option['label'] ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		if ( ! empty( $field['description'] ) ) {
			echo '<p>' . \esc_html( $field['description'] ) . '</p>';
		}
		
		return (string) \ob_get_clean();
	}
	
	/**
	 * Get HTML for field type 'text'.
	 * 
	 * @param	array{classes?: string[], description?: string, max?: int, min?: int, multiple?: bool, name: string, option_type?: string, options?: string[][], scope: string[], title: string, type: string}	$field Field data
	 * @return	string Field HTML
	 */
	private static function get_type_text( array $field ): string {
		if ( empty( $field['option_type'] ) || $field['option_type'] === 'option' ) {
			$current_value = \get_option( $field['name'], '' );
		}
		else {
			global $post;
			
			$current_value = \get_post_meta( $post->ID, $field['name'], true );
		}
		
		if ( ! \is_string( $current_value ) ) {
			$current_value = '';
		}
		
		$checked = '';
		$step = '';
		$value = ' value=""';
		
		/**
		 * Filter the current field value.
		 * 
		 * @param	string	$current_value Current field value
		 * @param	array $field Current field
		 */
		$current_value = (string) \apply_filters( 'composer_packages_field_current_value', $current_value, $field );
		
		if ( ! empty( $current_value ) ) {
			$value = ' value="' . \esc_attr( $current_value ) . '"';
		}
		
		switch ( $field['type'] ) {
			case 'checkbox':
				$checked = \checked( $current_value === 'yes', true, false );
				$value = ' value="yes"';
				break;
			case 'number':
				$step = ' step="any"';
				break;
			case 'radio':
				$checked = \checked( $current_value === 'yes', true, false );
				break;
		}
		
		\ob_start();
		?>
		<input
			type="<?php echo \esc_attr( $field['type'] ); ?>"
			id="<?php echo \esc_attr( $field['name'] ); ?>"
			name="<?php echo \esc_attr( $field['name'] ); ?>"
			<?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo ! empty( $field['classes'] ) ? ' class="' . \implode( ' ', \array_map( 'sanitize_html_class', $field['classes'] ) ) . '"' : ''; ?>
			<?php echo ! empty( $field['min'] ) ? ' min="' . \esc_attr( (string) $field['min'] ) . '"' : ''; ?>
			<?php echo ! empty( $field['max'] ) ? ' max="' . \esc_attr( (string) $field['max'] ) . '"' : ''; ?>
			<?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $step; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
		<?php
		if ( $field['type'] === 'checkbox' || $field['type'] === 'radio' ) {
			?>
			<label for="<?php echo \esc_attr( $field['name'] ); ?>"><?php echo \esc_html( $field['title'] ); ?></label>
			<?php
		}
		
		if ( ! empty( $field['description'] ) ) {
			echo '<p>' . \esc_html( $field['description'] ) . '</p>';
		}
		
		return (string) \ob_get_clean();
	}
	
	/**
	 * Get HTML for field type 'textarea'.
	 * 
	 * @param	array{classes?: string[], description?: string, max?: int, min?: int, multiple?: bool, name: string, option_type?: string, options?: string[][], scope: string[], title: string, type: string}	$field Field data
	 * @return	string Field HTML
	 */
	private static function get_type_textarea( array $field ): string {
		if ( empty( $field['option_type'] ) || $field['option_type'] === 'option' ) {
			$current_value = \get_option( $field['name'], '' );
		}
		else {
			global $post;
			
			$current_value = \get_post_meta( $post->ID, $field['name'], true );
		}
		
		if ( ! \is_string( $current_value ) ) {
			$current_value = '';
		}
		
		\ob_start();
		?>
		<textarea id="<?php echo \esc_attr( $field['name'] ); ?>" name="<?php echo \esc_attr( $field['name'] ); ?>"<?php echo ! empty( $field['classes'] ) ? ' class="' . \implode( ' ', \array_map( 'sanitize_html_class', $field['classes'] ) ) . '"' : ''; ?>><?php
		echo \wp_kses_post( \htmlentities( $current_value ) );
		?></textarea>
		<?php
		if ( ! empty( $field['description'] ) ) {
			echo '<p>' . \esc_html( $field['description'] ) . '</p>';
		}
		
		return (string) \ob_get_clean();
	}
}
