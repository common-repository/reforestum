<?php 
/**
 * Reforestum Product
 * 
 * @class	Reforestum_Product
 * @package	Reforestum\Classes
 * @version	1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reforestum_Product Class
 */
class Reforestum_Product {
	public static function init(){
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'product_co2_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'product_co2_tab_content' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_custom_fields' ) );
		add_action( 'woocommerce_variation_options_pricing', array( __CLASS__, 'variation_custom_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variation_custom_fields' ), 10, 2 );
	}

	/**
	 * Register custom product data tab
	 * 
	 * @param	array	$product_data_tabs	array of registered product datas
	 * @return	array	Array of registered product datas
	 * @since	1.0
	 */
	public static function product_co2_tab( $product_data_tabs ) {
		$product_data_tabs['co2_offseting'] = array(
			'label' 			=> __( 'CO₂ offseting options', 'reforestum' ),
			'target' 		=> 'co2_offseting_content',
			'class'     	=> array( 'show_if_simple' ),
		);
		return $product_data_tabs;
	}
	  
	/**
	 * Display custom product data tab content
	 * 
	 * @since	1.0
	 */
	public static function product_co2_tab_content(){
		global $post; ?>
		<div id="co2_offseting_content" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php self::custom_fields(); ?>
			</div>
		</div>
		<?php 
	}

	/**
	 * Add custom fields to custom product data tab
	 * 
	 * @since	1.0
	 */
	public static function custom_fields(){

		$get_payer 		= Reforestum_WC::get_payer( get_the_ID() );

		if( $get_payer['level'] != 'product' )
			$inherited_payer	= $get_payer['payer'] == 'c' ? __( '(End Customer)', 'reforestum' ) : __( '(Merchant)', 'reforestum' );
		
		$shipping_offset_method = Reforestum_WC::get_shipping_offset_method();

		woocommerce_wp_select(
			array(
				'id'					=> 'reforestum_payer',
				'label'				=> __( 'Who pays for Carbon Offset', 'reforestum' ),
				'options'			=> array(
					''					=> __( 'Use inherited value from category or general settings', 'reforestum' ) . ' ' . $inherited_payer,
					'm'				=> __( 'Merchant', 'reforestum' ),
					'c'				=> __( 'End Customer', 'reforestum' )
				)
			)
		);

		woocommerce_wp_text_input( 
			array( 
				'id'					=> 'reforestum_product_amount', 
				'label'				=> __( 'Product CO₂ amount', 'reforestum' ), 
				'description'		=> __( 'Amount in kilograms', 'reforestum' ),
				'desc_tip'			=> true,
				'placeholder'		=> __( 'Leave blank to use inherited value from category or general settings', 'reforestum' ),
				'class'				=> 'wc_input_price',
				'custom_attributes' => array(
					'step' 	=> 'any',
					'min'		=> '0',
				) 
			)
		);

		if($shipping_offset_method == 'f') {
			woocommerce_wp_text_input( 
				array( 
					'id'                => 'reforestum_shipping_amount', 
					'label'             => __( 'Shipping CO₂ amount', 'reforestum' ), 
					'description'		=> __( 'Amount in kilograms', 'reforestum' ),
					'desc_tip'			=> true,
					'placeholder'			=> __( 'Leave blank to use inherited value from category or general settings', 'reforestum' ),
					'class'	=> 'wc_input_price',
					'custom_attributes' => array(
						'step' 	=> 'any',
						'min'		=> '0',
					) 
				)
			);
		}
		 
		woocommerce_wp_checkbox(
			array(
				'id'				=> 'reforestum_project_restricted',
				'label'			=> __( 'Project choice restrictions', 'reforestum' ),
				'description'	=> __( 'Activate project constraints', 'reforestum' )
			)
		);

		reforestum_wc_multi_checkbox(
			array(
				'id'						=> 'reforestum_selected_forests',
				'name'					=> 'reforestum_selected_forests[]',
				'label'					=> __( 'Select forest(s)', 'reforestum' ),
				'description'			=> __( 'Select forest(s) that will be available for this product', 'reforestum' ), 
				'desc_tip'				=> true,
				'options'				=> Reforestum_API::forests_options(),
			)
		);

		woocommerce_wp_select(
			array(
				'id'						=> 'reforestum_sales_contract',
				'label'					=> __( 'Sales Contract specifically applied for this category (if any)', 'reforestum' ),
				'options'				=> Reforestum_API::contracts_option()
			)
		);
	}

	/**
	 * Save custom fields
	 * 
	 * @param	int	$post_id	The post ID
	 * @since	1.0
	 */
	public static function save_custom_fields( $post_id ) {
		$product = wc_get_product( $post_id );
		$payer 					= isset( $_POST['reforestum_payer'] ) ? $_POST['reforestum_payer'] : '';
		$product_amount 		= isset( $_POST['reforestum_product_amount'] ) ? $_POST['reforestum_product_amount'] : '';
		$shipping_amount 		= isset( $_POST['reforestum_shipping_amount'] ) ? $_POST['reforestum_shipping_amount'] : '';
		$project_restricted 	= isset( $_POST['reforestum_project_restricted'] ) ? $_POST['reforestum_project_restricted'] : '';
		$sales_contract 		= isset( $_POST['reforestum_sales_contract'] ) ? $_POST['reforestum_sales_contract'] : '';

		$product->update_meta_data( 'reforestum_payer', sanitize_text_field( $payer ) );
		$product->update_meta_data( 'reforestum_product_amount', sanitize_text_field( $product_amount ) );
		$product->update_meta_data( 'reforestum_shipping_amount', sanitize_text_field( $shipping_amount ) );
		$product->update_meta_data( 'reforestum_project_restricted', sanitize_text_field( $project_restricted ) );
		$product->update_meta_data( 'reforestum_sales_contract', sanitize_text_field( $sales_contract ) );

		// Save forests multiple checkbox
		if( isset( $_POST['reforestum_selected_forests'] ) ){
			$post_data = $_POST['reforestum_selected_forests'];
			// Data sanitization
			$sanitize_data = array();
			if( is_array($post_data) && sizeof($post_data) > 0 ){
				 foreach( $post_data as $value ){
					  $sanitize_data[] = sanitize_text_field( $value );
				 }
			}
			update_post_meta( $post_id, 'reforestum_selected_forests', $sanitize_data );
	  	}

		$product->save();
	}

	/**
	 * Variation custom fields
	 * 
	 * @param	int		$loop					Current instance of the loop
	 * @param	object	$variation_data	Data of current variation
	 * @param	object	$variation			Detail of the variation
	 * @since	1.0
	 */
	public static function variation_custom_fields( $loop, $variation_data, $variation ){
		echo '<div class="reforestum-variation-fields">';
		
		echo '<h3>' . __( 'Reforestum CO₂ offseting options', 'reforestum' ) . '</h3>';

		$get_payer 		= Reforestum_WC::get_payer( $variation->ID );

		if( $get_payer['level'] != 'product' )
			$inherited_payer	= $get_payer['payer'] == 'c' ? __( '(End Customer)', 'reforestum' ) : __( '(Merchant)', 'reforestum' );

		$shipping_offset_method = Reforestum_WC::get_shipping_offset_method();

		woocommerce_wp_select(
			array(
				'id'					=> 'reforestum_payer[' . $loop . ']',
				'label'				=> __( 'Who pays for Carbon Offset', 'reforestum' ),
				'options'			=> array(
					''					=> __( 'Use category or plugin settings', 'reforestum' ) . ' ' . $inherited_payer,
					'm'				=> __( 'Merchant', 'reforestum' ),
					'c'				=> __( 'End Customer', 'reforestum' )
				),
				'value'				=> get_post_meta( $variation->ID, 'reforestum_payer', true ),
				'wrapper_class'	=> 'form-row'
			)
		);

		woocommerce_wp_text_input( 
			array( 
				'id'						=> 'reforestum_product_amount[' . $loop . ']', 
				'label'					=> __( 'Product CO₂ amount', 'reforestum' ), 
				'description'		=> __( 'Amount in kilograms', 'reforestum' ),
				'desc_tip'			=> true,
				'placeholder'			=> __( 'Leave blank to use inherited value from category or general settings', 'reforestum' ),
				'wrapper_class'		=> 'form-row form-row-first',
				'class'					=> 'wc_input_price',
				'custom_attributes' 	=> array(
					'step' 	=> 'any',
					'min'		=> '0',
				),
				'value'				=> get_post_meta( $variation->ID, 'reforestum_product_amount', true )
			)
		);

		if($shipping_offset_method == 'f') {
			woocommerce_wp_text_input( 
				array( 
					'id'						=> 'reforestum_shipping_amount[' . $loop . ']', 
					'label'					=> __( 'Shipping CO₂ amount', 'reforestum' ), 
					'description'		=> __( 'Amount in kilograms', 'reforestum' ),
					'desc_tip'			=> true,
					'placeholder'			=> __( 'Leave blank to use inherited value from category or general settings', 'reforestum' ),
					'class'					=> 'wc_input_price',
					'wrapper_class'		=> 'form-row form-row-last',
					'custom_attributes' 	=> array(
						'step' 	=> 'any',
						'min'		=> '0',
					),
					'value'					=> get_post_meta( $variation->ID, 'reforestum_shipping_amount', true )
				)
			);
		}
		 
		woocommerce_wp_checkbox(
			array(
				'id'						=> 'reforestum_project_restricted[' . $loop . ']',
				'label'					=> __( 'Activate project constraints', 'reforestum' ),
				'value'					=> get_post_meta( $variation->ID, 'reforestum_project_restricted', true ),
				'wrapper_class'		=> 'form-row reforestum_project_restricted_variation'
			)
		);

		reforestum_wc_multi_checkbox(
			array(
				'id'						=> 'reforestum_selected_forests[' . $loop . ']',
				'name'					=> 'reforestum_selected_forests[' . $loop . '][]',
				'label'					=> __( 'Select forest(s)', 'reforestum' ),
				'description'			=> __( 'Select forest(s) that will be available for this product', 'reforestum' ), 
				'desc_tip'				=> true,
				'options'				=> Reforestum_API::forests_options(),
				'value'					=> get_post_meta( $variation->ID, 'reforestum_selected_forests', true ),
				'wrapper_class'		=> 'form-row reforestum_selected_forests_variation'
			)
		);

		woocommerce_wp_select(
			array(
				'id'						=> 'reforestum_sales_contract[' . $loop . ']',
				'label'					=> __( 'Sales Contract specifically applied for this category (if any)', 'reforestum' ),
				'options'				=> Reforestum_API::contracts_option(),
				'value'					=> get_post_meta( $variation->ID, 'reforestum_sales_contract', true ),
				'wrapper_class'		=> 'form-row reforestum_sales_contract_variation'
			)
		);

		echo '</div>';
	}

	/**
	 * Save variation custom fields
	 * 
	 * @param	int	$variation_id
	 * @param	int	$i					Index of current instance
	 */
	public static function save_variation_custom_fields( $variation_id, $i ){
		if( isset( $_POST['reforestum_payer'][$i] ) ) 
			update_post_meta( $variation_id, 'reforestum_payer', sanitize_text_field( $_POST['reforestum_payer'][$i] ) );

		if( isset( $_POST['reforestum_product_amount'][$i] ) ) 
			update_post_meta( $variation_id, 'reforestum_product_amount', sanitize_text_field( $_POST['reforestum_product_amount'][$i] ) );

		if( isset( $_POST['reforestum_shipping_amount'][$i] ) ) 
			update_post_meta( $variation_id, 'reforestum_shipping_amount', sanitize_text_field( $_POST['reforestum_shipping_amount'][$i] ) );

		if( isset( $_POST['reforestum_project_restricted'][$i] ) ) 
			update_post_meta( $variation_id, 'reforestum_project_restricted', sanitize_text_field( $_POST['reforestum_project_restricted'][$i] ) );

		if( isset( $_POST['reforestum_sales_contract'][$i] ) ) 
			update_post_meta( $variation_id, 'reforestum_sales_contract', sanitize_text_field( $_POST['reforestum_sales_contract'][$i] ) );

		// Save forests multiple checkbox
		if( isset( $_POST['reforestum_selected_forests'][$i] ) ){
			$post_data = $_POST['reforestum_selected_forests'][$i];
			// Data sanitization
			$sanitize_data = array();
			if( is_array($post_data) && sizeof($post_data) > 0 ){
				 foreach( $post_data as $value ){
					  $sanitize_data[] = sanitize_text_field( $value );
				 }
			}
			update_post_meta( $variation_id, 'reforestum_selected_forests', $sanitize_data );
	  	}
	}
}