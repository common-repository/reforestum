<?php 
/**
 * Functions for admin
 * 
 * Functions used in admin area
 * 
 * @package Reforestum\Functions
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Custom WooCommerce multi checkbox
 * 
 * @param 	array $field configurations of the fields
 * @return	mixed
 */
function reforestum_wc_multi_checkbox( $field ) {
	$screen = get_current_screen();
	if( is_object( $screen ) ){
		if( $screen->post_type == 'product' && empty( $screen->taxonomy ) ){
			global $thepostid, $post;
			$field['value'] 	= get_post_meta( $thepostid, $field['id'], true );
			$thepostid			= empty( $thepostid ) ? $post->ID : $thepostid;
		}
	}

	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : array();
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

	echo '<fieldset class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
	<legend>' . wp_kses_post( $field['label'] ) . '</legend>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		 echo wc_help_tip( $field['description'] );
	}

	echo '<ul class="wc-radios">';

	foreach ( $field['options'] as $key => $value ) {

		if( ! empty( $field['value'] ) ){
			$checked = ( in_array( $key, $field['value'] ) ? 'checked="checked"' : '' );
		} else {
			$checked = '';
		}

		 echo '<li><label><input
					name="' . esc_attr( $field['name'] ) . '"
					value="' . esc_attr( $key ) . '"
					type="checkbox"
					class="' . esc_attr( $field['class'] ) . '"
					style="' . esc_attr( $field['style'] ) . '"
					' . $checked . ' /> ' . esc_html( $value ) . '</label>
		 </li>';
	}
	echo '</ul>';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		 echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</fieldset>';
}

/**
 * WooCommerce multi checkbox for settings page
 * 
 * @param	array	$field
 */
function reforestum_wc_settings_multi_checkbox( $field ){
	$option_value = $field['value']; 
	?>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
		</th>
		<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>">

			<ul class="wc-radios">
				<?php
				foreach ( $field['options'] as $key => $value ) {

					if( ! empty( $field['value'] ) ){
						$checked = ( in_array( $key, $field['value'] ) ? 'checked="checked"' : '' );
					} else {
						$checked = '';
					}

					echo '<li><label><input
								name="' . esc_attr( $field['id'] ) . '[]"
								value="' . esc_attr( $key ) . '"
								type="checkbox"
								class="' . esc_attr( $field['class'] ) . '"
								' . $checked . ' /> ' . esc_html( $value ) . '</label>
					</li>';
				}
				?>
			</ul>
			<?php 
			if ( ! empty( $field['desc'] ) && false === $field['desc_tip'] ) {
				echo '<p class="description">' . wp_kses_post( $field['desc'] ) . '</p>';
		  	}
			?>			
		</td>
	</tr>
<?php }
add_action( 'woocommerce_admin_field_multi_checkbox', 'reforestum_wc_settings_multi_checkbox' );

/**
 * Save multi_checkbox
 * 
 * @param array	$option
 * @since 1.0
 */
function reforestum_wc_settings_multi_checkbox_save( $option ){
	if ( is_null( $data ) ) {
		$data = $_POST; // WPCS: input var okay, CSRF ok.
	}
	if ( empty( $data ) ) {
		return false;
	}

	$option_name  	= $option['id'];
	$setting_name 	= '';
	$raw_value    	= isset( $data[ $option_name ] ) ? wp_unslash( $data[ $option_name ] ) : null;
	$value 			= array_filter( array_map( 'wc_clean', (array) $raw_value ) );

	update_option( $option_name, $value );
}
add_action( 'woocommerce_update_option_multi_checkbox', 'reforestum_wc_settings_multi_checkbox_save' );

/**
 * AJAX for testing the openrouteservice api
 * 
 */
function reforestum_ajax_openrouteservice_test() {

	if(isset($_POST['api_key'])) {
		$api_key = $_POST['api_key'];
	} else {
		$api_key = get_option('reforestum_openrouteservice_api_key');
	}

	if(!$api_key) wp_send_json_error( 'Please provide a valid Openrouteservice API Key' );

	$data = array();
	$shop_address = get_option('woocommerce_store_address') . ', ' . get_option('woocommerce_store_address_2') . ', ' . get_option('woocommerce_store_city') . ', ' . get_option('woocommerce_store_postcode') . ', ' . get_option('woocommerce_default_country');
	$data = Reforestum_WC::get_coordinates($shop_address);
	if(!$data) wp_send_json_error( 'There was a problem while trying to find coordinates for your shop. Please check the shop address in the WooCommerce General Settings.' );

	wp_send_json_success($data);

}
add_action( 'wp_ajax_openrouteservice_test', 'reforestum_ajax_openrouteservice_test' );