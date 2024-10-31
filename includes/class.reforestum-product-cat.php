<?php 
/**
 * Reforestum Product Category
 * 
 * @class	Reforestum_Product_Cat
 * @package	Reforestum\Classes
 * @version	1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reforestum_Product_Cat Class
 */
class Reforestum_Product_Cat {

	/**
	 * Initialize class functions
	 * 
	 * @since 1.0
	 */
	public static function init(){
		
		add_action( 'product_cat_add_form_fields', array( __CLASS__, 'custom_fields' ), 90, 1);
		add_action( 'product_cat_edit_form_fields', array( __CLASS__, 'custom_fields' ), 90, 1);
	
		add_action( 'edited_product_cat', array( __CLASS__, 'save' ), 10, 1 );
		add_action( 'create_product_cat', array( __CLASS__, 'save' ), 10, 1 );

	}

	/**
	 * Custom Fields
	 * 
	 * Display product category custom field
	 * 
	 * @param	mixed		$term object or id of the current term
	 * @since	1.0
	 */
	public static function custom_fields( $term ){

		// Default settings
		$payer 					= '';
		$product_amount		= '';
		$shipping_amount		= '';
		$project_restricted	= '';
		$selected_forests		= '';
		$sales_contract		= '';

		// Get general options
		$shipping_offset_method = Reforestum_WC::get_shipping_offset_method();

		// If edit, load value
		if( is_object( $term ) ){
			$term_id = $term->term_id;
			$payer 					= get_term_meta( $term_id, 'reforestum_payer', true );
			$product_amount		= get_term_meta( $term_id, 'reforestum_product_amount', true );
			$shipping_amount		= get_term_meta( $term_id, 'reforestum_shipping_amount', true );
			$project_restricted	= get_term_meta( $term_id, 'reforestum_project_restricted', true );
			$selected_forests		= get_term_meta( $term_id, 'reforestum_selected_forests', true );
			$sales_contract		= get_term_meta( $term_id, 'reforestum_sales_contract', true );
		}
		

		echo '<tr class="form-field"><td colspan="2">';
		
		echo '<div class="reforestum-variation-fields">';
		echo '<h3>' . __( 'Reforestum CO₂ offsetting options', 'reforestum' ) . '</h3>';

		echo '<p>' . __( 'These settings will affect all products assigned to this category and therefore, if set, will override the general settings configured for the plugin. 
		Note that in case a product has multiple categories, the configuration of the first category found will be used.', 'reforestum' ) . '</p>';


		$plugin_payer = get_option( 'reforestum_payer' );
		$plugin_payer = $plugin_payer == 'c' ? __( '(End Customer)', 'reforestum' ) : __( '(Merchant)', 'reforestum' );

		woocommerce_wp_select(
			array(
				'id'					=> 'reforestum_payer',
				'label'				=> __( 'Who pays for Carbon Offset', 'reforestum' ),
				'options'			=> array(
					''					=> sprintf( __( 'Use plugin settings %s', 'reforestum' ), $plugin_payer ),
					'm'				=> __( 'Merchant', 'reforestum' ),
					'c'				=> __( 'End Customer', 'reforestum' )
				),
				'value'				=> $payer
			)
		);

		woocommerce_wp_text_input( 
			array( 
				'id'					=> 'reforestum_product_amount', 
				'label'				=> __( 'Product CO₂ amount', 'reforestum' ), 
				'description'		=> __( 'Amount in kilograms', 'reforestum' ),
				'required'			=> true,
				'class'				=> 'wc_input_price',
				'custom_attributes' => array(
					'step' 	=> 'any',
					'min'		=> '0',
				),
				'value'				=> $product_amount
			)
		);

		if($shipping_offset_method == 'f') {
			woocommerce_wp_text_input( 
				array( 
					'id'             	=> 'reforestum_shipping_amount', 
					'label'          	=> __( 'Shipping CO₂ amount', 'reforestum' ), 
					'description'		=> __( 'Amount in kilograms', 'reforestum' ),
					'required'			=> true,
					'class'	=> 'wc_input_price',
					'custom_attributes' => array(
						'step' 	=> 'any',
						'min'		=> '0',
					),
					'value'				=> $shipping_amount
				)
			);
		}

		woocommerce_wp_checkbox(
			array(
				'id'				=> 'reforestum_project_restricted',
				'label'			=> __( 'Project choice restrictions', 'reforestum' ),
				'description'	=> __( 'Activate project constraints', 'reforestum' ),
				'value'			=> $project_restricted
			)
		);

		reforestum_wc_multi_checkbox(
			array(
				'id'						=> 'reforestum_selected_forests',
				'name'					=> 'reforestum_selected_forests[]',
				'label'					=> __( 'Select forest(s)', 'reforestum' ),
				'description'			=> __( 'Select forest(s) that will be available for this product', 'reforestum' ), 
				'desc_tip'				=> false,
				'options'				=> Reforestum_API::forests_options( 'category' ),
				'value'					=> $selected_forests
			)
		);

		woocommerce_wp_select(
			array(
				'id'						=> 'reforestum_sales_contract',
				'label'					=> __( 'Sales Contract specifically applied for this category (if any)', 'reforestum' ),
				'options'				=> Reforestum_API::contracts_option( 'category' ),
				'value'					=> $sales_contract
			)
		);

		echo '</div>';
		
		echo '</td></tr>';

	}

	/**
	 * Save Custom Fields
	 * 
	 * Save product category custom fields
	 * 
	 * @param	int	$term_id	ID of saved term
	 * @since	1.0
	 */
	public static function save( $term_id ){
	
		if( isset( $_POST['reforestum_payer'] ) ) 
			update_term_meta( $term_id, 'reforestum_payer', sanitize_text_field( $_POST['reforestum_payer'] ) );

		if( isset( $_POST['reforestum_product_amount'] ) ) 
			update_term_meta( $term_id, 'reforestum_product_amount', sanitize_text_field( $_POST['reforestum_product_amount'] ) );

		if( isset( $_POST['reforestum_shipping_amount'] ) ) 
			update_term_meta( $term_id, 'reforestum_shipping_amount', sanitize_text_field( $_POST['reforestum_shipping_amount'] ) );

		if( isset( $_POST['reforestum_project_restricted'] ) ){
			update_term_meta( $term_id, 'reforestum_project_restricted', sanitize_text_field( $_POST['reforestum_project_restricted'] ) );
		} else {
			update_term_meta( $term_id, 'reforestum_project_restricted', 'no' );
		}

		if( isset( $_POST['reforestum_sales_contract'] ) ) 
			update_term_meta( $term_id, 'reforestum_sales_contract', sanitize_text_field( $_POST['reforestum_sales_contract'] ) );

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
			update_term_meta( $term_id, 'reforestum_selected_forests', $sanitize_data );
	  	}

	}

}