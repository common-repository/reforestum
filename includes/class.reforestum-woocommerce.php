<?php 
/**
 * Reforestum WooCommerce
 * 
 * @class	Reforestum_WC
 * @package	Reforestum/WooCommerce
 * @version	1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reforestum_WC Class
 */
class Reforestum_WC {

	/**
	 * Initialize class functions
	 */
	public static function init(){

		add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'single_product_carbon_amount' ) );

		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_cart_item_data' ), 10, 2 );
		//add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'create_order_line_item' ), 10, 4 );

		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_carbon_fee' ) );
		add_action( 'woocommerce_review_order_after_shipping', array( __CLASS__, 'display_shipping_carbon_amount' ) );
		add_action( 'woocommerce_review_order_before_order_total', array( __CLASS__, 'select_forest' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'checkout_update_order_meta' ) );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'checkout_order_processed' ), 10, 3 );

		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'before_checkout_form' ) );
	}

	/**
	 * Display product Co2 after the single product add to cart button
	 * 
	 * @since 1.0
	 */
	public static function single_product_carbon_amount(){
		$item_id				= get_the_ID();
		$product_amount			= self::get_amount( $item_id );
		$shipping_amount		= self::get_amount( $item_id, 'shipping' );
		$unit					= self::get_unit( $item_id );
		$unit_full				= self::get_unit_full( $unit );
		$display				= get_option( 'reforestum_display_in_product' );
		?>
		<?php if( $product_amount > 0 && $display == 'yes' ){ ?>
			<div class="reforestum-co2 reforestum-tooltip" data-tooltip="<?php echo __( 'CO₂ amount for each of this product', 'reforestum' ); ?>"><?php echo esc_html( number_format_i18n( $product_amount ) . ' ' . $unit . ' ' . __( 'CO₂ footprint', 'reforestum' ) ); ?></div> 
		<?php }
	}

	/**
	 * Add Cart Item Data
	 * 
	 * @param	array		$cart_item_data
	 * @param	int		$product_id
	 * @param	int		$variation_id
	 * @return	array
	 * @since	1.0
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ){
		$item_id 				= empty( $variation_id ) ? $product_id : $variation_id;
		$sales_contract			= self::get_contract( $item_id );
		$contract_detail		= Reforestum_API::get_contract( $sales_contract );
		$payer					= self::get_payer( $item_id, true );
		$product_amount			= self::get_amount( $item_id );
		$product_unit			= self::get_unit( $item_id );
		$shipping_amount		= self::get_amount( $item_id, 'shipping' );
		$shipping_unit			= self::get_unit( $item_id, 'shipping' );
		$project_restricted		= self::get_project_restricted( $item_id );
		$selected_forests		= self::get_selected_forests( $item_id );

		// Don't add meta data if no contract and product amount
		if( empty( $sales_contract ) )  //  || empty( $contract_detail->value ) || empty( $product_amount )
			return $cart_item_data;

		$cart_item_data['reforestum_payer']							= $payer['payer'];
		$cart_item_data['reforestum_product_amount'] 				= $product_amount;
		$cart_item_data['reforestum_product_unit']					= $product_unit;
		$cart_item_data['reforestum_shipping_amount'] 				= $shipping_amount;
		$cart_item_data['reforestum_shipping_unit']					= $shipping_unit;
		$cart_item_data['reforestum_project_restricted']			= $project_restricted;
		$cart_item_data['reforestum_sales_contract']				= $sales_contract;
		$cart_item_data['reforestum_sales_contract_amount']			= $contract_detail->value;
		$cart_item_data['reforestum_sales_contract_units']			= $contract_detail->units;
		$cart_item_data['reforestum_sales_contract_alias']			= $contract_detail->contract_alias;
		$cart_item_data['reforestum_sales_contract_forests']		= $contract_detail->forests; 

		if( $project_restricted == 'yes' ){
			$cart_item_data['reforestum_selected_forests']			= ! empty( $contract_detail->forests ) ? array_intersect( $contract_detail->forests, $selected_forests ) : $selected_forests;
		} else {
			$cart_item_data['reforestum_selected_forests']			= $contract_detail->forests;
		}

		return $cart_item_data;
	}

	/**
	 * Display Cart Item Data
	 * 
	 * @param	array		$cart_item_data
	 * @param	array		$cart_item
	 * @return	array
	 * @since	1.0
	 */
	public static function display_cart_item_data( $cart_item_data, $cart_item ){

		if ( empty( $cart_item['reforestum_product_amount'] ) )
			return $cart_item_data;

		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );
		} else {
			$post_data = $_POST; // fallback for final checkout (non-ajax)
		}

		$quantity			= $cart_item['quantity'];
		$payer				= $cart_item['reforestum_payer'];
		$product_amount		= $cart_item['reforestum_product_amount'];
		$product_unit		= $cart_item['reforestum_product_unit'];
		$shipping_amount	= $cart_item['reforestum_shipping_amount'];
		$shipping_unit		= self::get_unit_full( $cart_item['reforestum_shipping_unit'] );

		$total_product_co2			= $quantity*$product_amount;
		$total_shipping_co2			= $quantity*$shipping_amount;

		$display_in_cart				= get_option( 'reforestum_display_in_cart' );
		$display_in_checkout			= get_option( 'reforestum_display_in_checkout' );

		if( ( is_cart() && $display_in_cart == 'yes' ) || ( is_checkout() && $display_in_checkout == 'yes' ) ){
			$cart_item_data[] = array(
				'key'     => __( 'Carbon footprint', 'reforestum' ),
				'value'   => wc_clean( $total_product_co2 ),
				'display' => '<div class="reforestum-co2 reforestum-tooltip" data-tooltip="' . sprintf( __( 'CO₂ footprint amount = %s x%s', 'reforestum' ), number_format_i18n( $total_product_co2 ) . ' ' . $product_unit, $quantity ) . '">' . esc_html( number_format_i18n( $total_product_co2 ) . ' ' . $product_unit . ' '  . __( 'CO₂ footprint', 'reforestum' ) ) . '</div>',
			);
		}

		return $cart_item_data;
	}

	/**
	 * Create order line item
	 * 
	 * @param WC_Order_Item_Product $item
 	 * @param string                $cart_item_key
	 * @param array                 $values
	 * @param WC_Order              $order
	 */
	public static function create_order_line_item( $item, $cart_item_key, $values, $order ){
		if( empty( $values['reforestum_product_amount'] ) )
			return;
		
		$item->add_meta_data( __( 'Carbon footprint', 'reforestum' ), $values['reforestum_product_amount']*$values['quantity'] );
	}

	/**
	 * Add Co2 fee to the checkout
	 * 
	 * @since 1.0
	 */
	public static function add_carbon_fee(){
		global $woocommerce;

		if ( is_admin() && ! defined( 'DOING_AJAX' ) )
			return;

		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );
		} else {
			$post_data = $_POST; // fallback for final checkout (non-ajax)
		}

		if ( isset( $post_data['reforestum_forest'] ) ) {
			
			$selected_forest 		= $post_data['reforestum_forest'];

			$cart_items_co2			= self::cart_items_co2_calculation();
			$product_co2			= $cart_items_co2['total_product_co2'];
			$product_unit			= self::get_unit_full( $cart_items_co2['product_unit'] );
			$shipping_co2			= $cart_items_co2['total_shipping_co2'];
			$shipping_unit			= self::get_unit_full( $cart_items_co2['shipping_unit'] );
			$fa_product_co2			= $cart_items_co2['total_fa_product_co2'];
			$fa_shipping_co2		= $cart_items_co2['total_fa_shipping_co2'];
			$fa_co2					= $cart_items_co2['total_fa_co2'];
			$co2_to_request			= empty( $post_data['shipping_method'] ) ? $fa_product_co2 : $fa_co2;
			$total_contract_fee		= $cart_items_co2['total_contract_fee'];
			$available_forests		= $cart_items_co2['available_forests'];
			$forests 				= self::get_cart_forests( $co2_to_request );

			// If no available forests, stop function
			if( count( $available_forests ) == 0 )
				return;

			$amount_total 		= 0;
			$paid_by_merchant	= 0;

			// Get shipping offset method
			$shipping_method_offset = self::get_shipping_offset_method();
			$shipping_co2_distance_number = false;
			if($shipping_method_offset == 'd') {
				$delivery_address = self::get_shipping_address();
				if($delivery_address) {
					$shipping_co2_distance_number = self::calculate_shipping_co2_offset($delivery_address); 
				}
			}

			foreach( $cart_items_co2['items'] as $item ){
				// Shipping method available
				if( ! empty( $post_data['shipping_method'] ) ){
					if(empty( $item['contract_fee'] )) {
						if($shipping_method_offset == 'd' || $shipping_method_offset == 'n') {
							$amount = self::fee_by_forest_id( $selected_forest, $item['offset_product'] );
						} else {
							$amount = self::fee_by_forest_id( $selected_forest, $item['offset_total'] );
						}
					} else {
						if($shipping_method_offset == 'd' || $shipping_method_offset == 'n') {
							$amount = $item['contract_fee'];
						} else {
							$amount = self::fee_by_forest_id( $selected_forest, $item['offset_shipping'] )+$item['contract_fee'];
						}
					}
				} else {
					$amount	= empty( $item['contract_fee'] ) ? 
						self::fee_by_forest_id( $selected_forest, $item['offset_product'] ) : 
						$item['contract_fee'];
				}

				$amount_total = $amount_total+$amount;

				if( $item['payer'] == 'm' )
					$paid_by_merchant = $paid_by_merchant+$amount;
			}
			if($shipping_method_offset == 'd' && $shipping_co2_distance_number) {
				$amount_total = $amount_total+self::fee_by_forest_id( $selected_forest, self::get_cart_forests($shipping_co2_distance_number) );
			}

			if($amount_total < Reforestum_API::credit()) {

				if( $selected_forest == 'no' ){
					$woocommerce->cart->add_fee( __( 'CO₂ offset fee', 'reforestum' ), 0, true, '' );
				} else {
					$woocommerce->cart->add_fee( __( 'CO₂ offset fee', 'reforestum' ), $amount_total, true, '' );

					// Add amount of offset that paid by merchant
					if( $paid_by_merchant > 0 )
						$woocommerce->cart->add_fee( __( 'CO₂ offset fee paid by merchant', 'reforestum' ), 0-$paid_by_merchant, true, '' );

				}

			}

		}
	}

	/**
	 * Display shipping carbon amount
	 * 
	 * @since 1.0
	 */
	public static function display_shipping_carbon_amount(){
		global $woocommerce;
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );
		} else {
			$post_data = $_POST; // fallback for final checkout (non-ajax)
		}

		$cart_items_co2		= self::cart_items_co2_calculation();
		$shipping_co2			= $cart_items_co2['total_shipping_co2'];
		$shipping_unit			= $cart_items_co2['shipping_unit'];
		$shipping_method_offset = self::get_shipping_offset_method();

		$shipping_co2_value = false;
		switch($shipping_method_offset) {
			case 'n': 
				$shipping_co2_value = false;
				break;
			case 'f': 
				$shipping_co2_value = esc_html( sprintf( __( '%s CO₂', 'reforestum' ), number_format_i18n( $shipping_co2 ) . ' ' . $shipping_unit ) );
				break;
			case 'd': 
				$shipping_co2_value = false;
				$delivery_address = self::get_shipping_address();
				if($delivery_address) {
					$shipping_co2_number = self::calculate_shipping_co2_offset($delivery_address); 
					if(isset($shipping_co2_number) && $shipping_co2_number > 0) {
						$shipping_co2_value = esc_html( sprintf( __( '%s CO₂', 'reforestum' ), number_format_i18n( $shipping_co2_number ) . ' ' . $shipping_unit ) );
					}
				}
				break;
		}

		if($shipping_co2_value) {
			?>
			<tr>
				<th><?php echo __( 'Shipping CO₂ footprint', 'reforestum' ); ?></th>
				<td>
					<div class="reforestum-co2"><?php echo $shipping_co2_value; ?></div>
				</td>
			</tr>
			<?php
		}
	
	}

	/**
	 * Select forest from API
	 * 
	 * @since 1.0
	 */
	public static function select_forest(){ ?>
		<?php 
		global $woocommerce;
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );
		} else {
			if( is_array( $_POST ) ){
				$post_data = array_map( 'sanitize_text_field', $_POST );
			} else {
				$post_data = sanitize_text_field( $_POST );
			}
		}

		$cart_items_co2		= self::cart_items_co2_calculation();
		$product_co2			= $cart_items_co2['total_product_co2'];
		$product_unit			= ucfirst( $cart_items_co2['product_unit'] );
		$shipping_co2			= $cart_items_co2['total_shipping_co2'];
		$shipping_unit			= ucfirst( $cart_items_co2['shipping_unit'] );
		$fa_product_co2		= $cart_items_co2['total_fa_product_co2'];
		$fa_shipping_co2		= $cart_items_co2['total_fa_shipping_co2'];
		$fa_co2					= $cart_items_co2['total_fa_co2'];
		$co2_to_request		= empty( $post_data['shipping_method'] ) ? $fa_product_co2 : $fa_co2;
		$total_contract_fee	= $cart_items_co2['total_contract_fee'];
		$available_forests	= $cart_items_co2['available_forests'];
		$forests 				= self::get_cart_forests( $co2_to_request );

		// If no available forests, stop function
		if( count( $available_forests ) == 0 )
			return;

		$selected = 'no';

		// Get shipping offset method
		$shipping_method_offset = self::get_shipping_offset_method();
		$shipping_co2_distance_number = false;
		if($shipping_method_offset == 'd') {
			$delivery_address = self::get_shipping_address();
			if($delivery_address) {
				$shipping_co2_distance_number = self::calculate_shipping_co2_offset($delivery_address); 
			}
		}

		$show_forests = false;
		ob_start();
		?>
		<tr class="reforestum-select-forests">
			<td colspan="2">
				<div class="reforestum-select-forests__title"><?php echo __( 'Select your forest', 'reforestum' ); ?></div>
				<div class="reforestum-forests update_totals_on_change">
					<label for="no_offset" class="reforestum-forest">
						<input type="radio" id="no_offset" name="reforestum_forest" value="<?php echo esc_attr( 'no' ); ?>" <?php checked( 'no', $selected ); ?>>
						<div>
							<div class="reforestum-forest__img" style="background-image:url('<?php echo esc_url( REFORESTUM_PLUGIN_URI . '/public/images/icon-no-offset.png' ); ?>" alt="<?php echo __( 'No offset at all', 'reforestum' ); ?>"></div>
							<div class="reforestum-forest__detail">
								<div class="reforestum-forest__name"><?php echo __( 'No offset at all', 'reforestum' ); ?></div>
								<div class="reforestum-forest__price">
									<span><?php echo __( 'You will not be charged for the carbon offset', 'reforestum' ); ?></span>
									<?php echo esc_html( __( 'Free', 'reforestum' ) ); ?>
								</div>
							</div>
						</div>
					</label>
					<?php foreach( $available_forests as $id ){ ?>
						<?php 
						$amount_total 		= 0;
						foreach( $cart_items_co2['items'] as $item ){
							// Shipping method available
							if( ! empty( $post_data['shipping_method'] ) ){
								
								if(empty( $item['contract_fee'] )) {
									if($shipping_method_offset == 'd' || $shipping_method_offset == 'n') {
										$amount_total 	= $amount_total+self::fee_by_forest_id( $id, $item['offset_product'] );
									} else {
										$amount_total 	= $amount_total+self::fee_by_forest_id( $id, $item['offset_total'] );
									}
								} else {
									if($shipping_method_offset == 'd' || $shipping_method_offset == 'n') {
										$amount_total 	= $amount_total+$item['contract_fee'];
									} else {
										$amount_total 	= $amount_total+self::fee_by_forest_id( $id, $item['offset_shipping'] )+$item['contract_fee'];
									}
								}
								
							} else {
								$amount_total	= empty( $item['contract_fee'] ) ? $amount_total+self::fee_by_forest_id( $id, $item['offset_product'] ) : $amount_total+$item['contract_fee'];
							}
						}	
						if($shipping_method_offset == 'd' && $shipping_co2_distance_number) {
							$amount_total = $amount_total+self::fee_by_forest_id($id, self::get_cart_forests($shipping_co2_distance_number));
						}
						if($amount_total < Reforestum_API::credit()) {
							$show_forests = true;
							$forest		= self::forest_by_id( $id, $cart_items_co2['items'][0]['offset_total'] );
							$name			= $forest->name;
							$main_image	= $forest->main_image;
							$currency	= $forest->price->currency;

							if( empty( $post_data['reforestum_forest'] ) ){
								$selected = $available_forests[0];
							} else {
								$selected = $post_data['reforestum_forest'];
							}
							?>
							<label for="<?php echo 'forest_' . $id; ?>" class="reforestum-forest">
								<input type="radio" id="<?php echo esc_attr( 'forest_' . $id ); ?>" name="reforestum_forest" value="<?php echo esc_attr( $id ); ?>" <?php checked( $id, $selected ); ?>>
								<div>
									<div class="reforestum-forest__img" style="background-image:url('<?php echo esc_url( $main_image ); ?>" alt="<?php echo esc_attr( $name ); ?>');"></div>
									<div class="reforestum-forest__detail">
										<div class="reforestum-forest__name"><?php echo esc_html( $name ); ?></div>
										<div class="reforestum-forest__price">
											<span><?php echo __( 'Offset Price', 'reforestum' ); ?></span>
											<?php echo esc_html( self::currency_symbol( $currency ) . number_format_i18n( $amount_total, 2 ) ); ?>
										</div>
									</div>
								</div>
							</label>
							<?php
						}
						?>
					<?php } ?>
				</div>
			</td>
		</tr>
		<?php 
		$ob_res = ob_get_clean(); 
		if($show_forests) {
			echo $ob_res;
		}
	}

	/**
	 * Update order meta after checkout
	 * 
	 * @param	int	$order_id
	 * @since	1.0
	 */
	public static function checkout_update_order_meta( $order_id ){
		if ( ! empty( $_POST['reforestum_forest'] )  ) {
			$selected_forest = sanitize_text_field( $_POST['reforestum_forest'] );
			update_post_meta( $order_id, 'reforestum_forest', $selected_forest );
			$items_carbon 		= self::cart_items_co2_calculation();
			update_post_meta( $order_id, 'reforestum_co2_detail', $items_carbon );
		}
	}

	/**
	 * Checkout order processed
	 * 
	 * @param	int		$order_id
	 * @param	array		$posted_data
	 * @param	object	$order			Order detail
	 * @since	1.0
	 */
	public static function checkout_order_processed( $order_id, $posted_data, $order ){

		$order 				= wc_get_order( $order_id );
		$sandbox_mode		= get_option( 'reforestum_sandbox_mode', 'no' );
		$selected_forest	= get_post_meta( $order_id, 'reforestum_forest', true );
		$shipping_method 	= $order->get_shipping_method();

		if( $selected_forest && $selected_forest != 'no' ){
			$cart_items_co2		= get_post_meta( $order_id, 'reforestum_co2_detail', true );

			// Customer data
			$first_name			= $order->get_billing_first_name();
			$last_name			= $order->get_billing_last_name();
			$email				= $order->get_billing_email();
			
			// Request array
			$requests = [];
			foreach( $cart_items_co2['items'] as $item ){
				$sales_contract = $item['sales_contract'];
				$requests[$sales_contract][] = $item;
			}

			foreach( $requests as $key => $request ){
				$query = [ 
					'beneficiary_name'		=> $first_name,
					'beneficiary_surname'	=> $last_name,
					'beneficiary_email'		=> $email,
					'offset_type'				=> 'MISC',
					'forest_id'					=> $selected_forest
				];
				
				$total_co2		= 0;
				
				$shipping_method_offset = self::get_shipping_offset_method();
				$shipping_co2_distance_number = false;
				if($shipping_method_offset == 'd') {
					$delivery_address = self::get_shipping_address();
					if($delivery_address) {
						$shipping_co2_distance_number = self::calculate_shipping_co2_offset($delivery_address); 
					}
				}

				foreach( $request as $r_item ){
					$total_co2 	= $total_co2+( $r_item['product_co2'] );
					if( ! empty( $shipping_method ) ){
						if($shipping_method_offset != 'd' && $shipping_method_offset != 'n') { 
							$total_co2 	= $total_co2+( $r_item['shipping_co2'] );
						}
					}
				}

				if($shipping_method_offset == 'd') {
					$total_co2 = $total_co2+$shipping_co2_distance_number; 
				}

				$query['co2']	= $total_co2;

				$query['contract_id']		= $key;
				$query['offset_name']		= 'Order #' . $order_id . ' - ' . $key;

				add_post_meta( $order_id, 'reforestum_purchase_query_' . $key, $query );

				// Only send purchase on behalf API is sandbox mode is disabled
				// if( $sandbox_mode == 'no' )
					Reforestum_API::purchase_on_behalf( $order_id, $query );
			}
		}
	}

	/**
	 * Display sandbox message before the order review
	 * 
	 */
	public static function before_checkout_form(){
		$sandbox_mode		= get_option( 'reforestum_sandbox_mode', 'no' );
		if( $sandbox_mode == 'yes' )
			wc_add_notice( __( 'Offsets sandbox mode is enabled', 'reforestum' ), 'error' );
	}

	/**
	 * Get items and total carbon of the current cart
	 * 
	 * @return	array of cart items with the carbon detail
	 * @since	1.0
	 */
	public static function cart_items_co2_calculation(){
		global $woocommerce;
		$cart_items				= $woocommerce->cart->get_cart_contents();

		// Total amount
		$total_co2				= 0;
		$total_product_co2		= 0;
		$total_shipping_co2		= 0;
		
		// Total of free amount contract co2 will be used for `query-offset` calculation
		$total_fa_co2			= 0;
		$total_fa_product_co2	= 0;
		$total_fa_shipping_co2	= 0;

		$total_contract_fee		= 0;

		// Array of forests array of each item to be used to find available forests
		$items_forests			= array();

		foreach( $cart_items as $cart_item_key => $cart_item ){

			// Skip the loop if no sales contract of product amount
			if( ! isset( $cart_item['reforestum_sales_contract'] ) || ! isset( $cart_item['reforestum_product_amount'] ) )
				continue;

			$product_id				= empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];
			$product_name			= get_the_title( $product_id );
			$quantity				= $cart_item['quantity'];
			$payer 					= $cart_item['reforestum_payer'];
			$product_co2			= $cart_item['reforestum_product_amount'];
			$product_unit			= $cart_item['reforestum_product_unit'];
			$shipping_co2			= $cart_item['reforestum_shipping_amount'];
			$shipping_unit			= $cart_item['reforestum_shipping_unit'];
			$sales_contract			= $cart_item['reforestum_sales_contract'];
			$contract_fee			= $cart_item['reforestum_sales_contract_amount'];
			$contract_fee_unit		= $cart_item['reforestum_sales_contract_units'];
			$fa_product_co2			= empty( $contract_fee ) ? $product_co2 : 0;
			$fa_shipping_co2 		= $shipping_co2; // This can be used if decided to include shipping on the sales contract fee
			$fa_co2					= ($fa_product_co2+$fa_shipping_co2)*$quantity;
			$selected_forests		= $cart_item['reforestum_selected_forests'];

			$return['items'][] = array(
				'cart_item_key'		=> $cart_item_key,		
				'product_id'			=> $product_id,
				'product_name'			=> $product_name,
				'quantity'				=> $quantity,
				'payer'					=> $payer,
				'product_co2'			=> $product_co2*$quantity,
				'product_unit'			=> $product_unit,
				'shipping_co2'			=> $shipping_co2*$quantity,
				'shipping_unit'		=> $shipping_unit,
				'sales_contract'		=> $sales_contract,
				'contract_fee'			=> $contract_fee*$quantity,
				'contract_fee_unit'	=> $contract_fee_unit,
				'fa_product_co2'		=> $fa_product_co2*$quantity, 
				'fa_shipping_co2'		=> $fa_shipping_co2*$quantity,
				'fa_co2'					=> $fa_co2,
				'selected_forests'	=> $selected_forests,
				'offset_total'			=>	self::get_cart_forests( $fa_co2 ),
				'offset_product'		=> empty( $contract_fee ) ? self::get_cart_forests( $fa_product_co2*$quantity ) : null,
				'offset_shipping'		=> self::get_cart_forests( $fa_shipping_co2*$quantity )
			);
			// Add to array of available forests
			$item_forests[] = $selected_forests;

			$total_product_co2		= $total_product_co2+($product_co2*$quantity);
			
			$total_shipping_co2		= $total_shipping_co2+($shipping_co2*$quantity);
			$total_co2					= $total_co2+($product_co2*$quantity)+($shipping_co2*$quantity);

			$total_fa_product_co2	= $total_fa_product_co2+($fa_product_co2*$quantity);
			$total_fa_shipping_co2	= $total_fa_shipping_co2+($fa_shipping_co2*$quantity);
			$total_fa_co2				= $total_fa_co2+($fa_product_co2*$quantity)+($fa_shipping_co2*$quantity);

			$total_contract_fee		= $total_contract_fee+($contract_fee*$quantity);
		}

		$return['product_unit']				= 'kg';
		$return['shipping_unit']			= 'kg';

		// Return array of available forests of each item
		$return['available_forests']		= count( $item_forests ) == 1 ? $item_forests[0] : call_user_func_array( 'array_intersect', $item_forests );

		// Add totals to the array
		$return['total_product_co2']		= $total_product_co2;
		$return['total_shipping_co2']		= $total_shipping_co2;
		$return['total_co2']					= $total_co2;
		$return['total_fa_product_co2']	= $total_fa_product_co2;
		$return['total_fa_shipping_co2']	= $total_fa_shipping_co2;
		$return['total_fa_co2']				= $total_fa_co2;
		$return['total_contract_fee'] 	= $total_contract_fee;

		return $return;
	}

	/**
	 * Get cart forests
	 * Check if there is transient for specific offset request are still exists
	 * API result for specific carbon amount will be stored in transient for 2 hours
	 * 
	 * @return array of the forests from the API
	 */
	public static function get_cart_forests( $total_co2 ){
		$co2 			= sanitize_key( 'reforestum_forests_' . $total_co2 );
		$forests 	= get_transient( $co2 );
		if( empty( $forests ) ){
			$forests = Reforestum_API::query_offset( $total_co2 );
			set_transient( $co2, $forests, 2*HOUR_IN_SECONDS );
		}
		return $forests;
	}

	/**
	 * Get fee by forests id
	 * 
	 * @return int carbon offset fee of selected forest from the provided forests array
	 */
	public static function fee_by_forest_id( $selected_forest, $forests ){
		$key = array_search( $selected_forest, array_column( $forests, 'id' ) );
		return $forests[$key]->price->amount;
	}

	/**
	 * Get forest detail by id
	 * 
	 * @return array
	 */
	public static function forest_by_id( $selected_forests, $forests ){
		$key = array_search( $selected_forests, array_column( $forests, 'id' ) );
		return $forests[$key];
	}


	/**
	 * Convert currency code to symbol
	 * 
	 * @return string the translated currency symbol
	 */
	public static function currency_symbol( $currency ){
		if( $currency == 'EUR' ){
			$currency = '€';
		}
		return $currency;
	}

	/**
	 * Get Payer
	 * 
	 * @param	int		$product_id
	 * @param	boolean	$formatted
	 * @return	string
	 * @since	1.0
	 */
	public static function get_payer( $product_id, $formatted = false ){
		$product 				= wc_get_product( $product_id );
		$product_type			= $product->get_type();
		$parent_id				= $product->get_parent_id() ? $product->get_parent_id() : $product_id;

		$plugin_payer			= get_option( 'reforestum_payer' );
		$product_payer			= get_post_meta( $product_id, 'reforestum_payer', true );

		$product_categories	= wc_get_product_term_ids( $parent_id, 'product_cat' );
		$category				= count( $product_categories ) > 0 ? $product_categories[0] : null;
		$category_payer		= ! empty( $category ) ? get_term_meta( $category, 'reforestum_payer', true ) : $category;

		return empty( $product_payer ) ? ( empty( $category_payer ) ? array( 'payer' => $plugin_payer, 'level' => 'plugin' ) : array( 'payer' => $category_payer, 'level' => 'category' ) ) : array( 'payer' => $product_payer, 'level' => 'product' );
	}

	/**
	 * Get Payer Full
	 * 
	 * @param	string	$payer
	 * @return	string
	 * @since	1.0
	 */
	public static function get_payer_full( $payer ){
		return $payer == 'm' ? __( 'Merchant', 'reforestum' ) : __( 'Customer', 'reforestum' );
	}

	/**
	 * Get Amount
	 * 
	 * @param	int		$product_id
	 * @param	string	$type				Check unit for product or shipping
	 * @return	int		Co2 amount of the product
	 * @return	false		If no product amount are found	
	 * @since	1.0
	 */
	public static function get_amount( $product_id, $type = 'product' ){
		$product 				= wc_get_product( $product_id );
		$product_type			= $product->get_type();
		$parent_id				= $product->get_parent_id() ? $product->get_parent_id() : $product_id;

		$product_amount		= get_post_meta( $product_id, 'reforestum_' . $type . '_amount', true );

		$product_categories	= wc_get_product_term_ids( $parent_id, 'product_cat' );
		$category				= count( $product_categories ) > 0 ? $product_categories[0] : null;
		$category_amount		= ! empty( $category ) ? get_term_meta( $category, 'reforestum_' . $type . '_amount', true ) : $category;

		return empty( $product_amount ) ? ( empty( $category_amount ) ? 0 : $category_amount ) : $product_amount;
	}

	/**
	 * Get unit
	 * 
	 * Currently only support kg
	 * @return	string	Unit kg or sqm
	 * @since	1.0
	 */
	public static function get_unit( $product_id, $type = 'product' ){
		return 'kg';
	}

	/**
	 * Get unit in full
	 * 
	 * @param	string	$unit			Unit abbreviation
	 * @return 	string	$unit_full	Unit in full word
	 * @since	1.0
	 */
	public static function get_unit_full( $unit ){
		return $unit == 'kg' ? 'Kilograms' : 'M²';
	}

	/**
	 * Get project restricted
	 * 
	 * @param	int		$product_id
	 * @return	boolean
	 * @since	1.0
	 */
	public static function get_project_restricted( $product_id ){
		$product 							= wc_get_product( $product_id );
		$parent_id							= $product->get_parent_id() ? $product->get_parent_id() : $product_id;

		$plugin_project_restricted		= get_option( 'reforestum_project_restricted' );
		$product_project_restricted	= get_post_meta( $product_id, 'reforestum_project_restricted', true );

		$product_categories				= wc_get_product_term_ids( $parent_id, 'product_cat' );
		$category							= count( $product_categories ) > 0 ? $product_categories[0] : null;
		$category_project_restricted	= ! empty( $category ) ? get_term_meta( $category, 'reforestum_project_restricted', true ) : $category;

		return empty( $product_project_restricted ) ? ( empty( $category_project_restricted ) ? $plugin_project_restricted : $category_project_restricted ) : $product_project_restricted;
	}

	/**
	 * Get selected forests
	 * 
	 * @param	int		$product_id
	 * @return	boolean
	 * @since	1.0
	 */
	public static function get_selected_forests( $product_id ){
		$product 							= wc_get_product( $product_id );
		$product_type						= $product->get_type();
		$parent_id							= $product->get_parent_id() ? $product->get_parent_id() : $product_id;

		$plugin_selected_forests		= get_option( 'reforestum_selected_forests' );
		$product_selected_forests		= get_post_meta( $product_id, 'reforestum_selected_forests', true );

		$product_categories				= wc_get_product_term_ids( $parent_id, 'product_cat' );
		$category							= count( $product_categories ) > 0 ? $product_categories[0] : null;
		$category_selected_forests		= ! empty( $category ) ? get_term_meta( $category, 'reforestum_selected_forests', true ) : $category;

		return empty( $product_selected_forests ) ? ( empty( $category_selected_forests ) ? $plugin_selected_forests : $category_selected_forests ) : $product_selected_forests;
	}

	/**
	 * Get Contract
	 * 
	 * @param	int		$product_id
	 * @return	string
	 * @since	1.0
	 */
	public static function get_contract( $product_id ){
		$product 					= wc_get_product( $product_id );
		$parent_id					= $product->get_parent_id() ? $product->get_parent_id() : $product_id;

		$plugin_contract			= get_option( 'reforestum_sales_contract' );
		$product_contract			= get_post_meta( $product_id, 'reforestum_sales_contract', true );

		$product_categories		= wc_get_product_term_ids( $parent_id, 'product_cat' );
		$category					= count( $product_categories ) > 0 ? $product_categories[0] : null;
		$category_contract		= ! empty( $category ) ? get_term_meta( $category, 'reforestum_sales_contract', true ) : $category;

		return empty( $product_contract ) ? ( empty( $category_contract ) ? $plugin_contract : $category_contract ) : $product_contract;
	}

	/**
	 * Get Shipping Address
	 * 
	 * @return	string
	 */
	public static function get_shipping_address() {
		global $woocommerce;
		if( empty($woocommerce->customer->get_shipping_address()) || empty($woocommerce->customer->get_shipping_postcode()) || empty($woocommerce->customer->get_shipping_city()) || empty($woocommerce->customer->get_shipping_country()) ) {
			return false;
		} else {
			return $woocommerce->customer->get_shipping_address() . ', ' . $woocommerce->customer->get_shipping_address_2() . ', ' . $woocommerce->customer->get_shipping_postcode() . ' ' . $woocommerce->customer->get_shipping_city() . ', ' . $woocommerce->customer->get_shipping_country();	
		}
		return false;
	} 

	/**
	 * Get Shipping Offset Method
	 * 
	 * @return	string
	 */
	public static function get_shipping_offset_method(){
		return get_option( 'reforestum_shipping_offset_method', 'n' );
	} 

	/**
	 * Get Distance in km between two coordinates
	 * 
	 * @param, $lat1, $lon1, $lat2, $lon2, $unit
	 * @return	float
	 */
	function get_distance($lat1, $lon1, $lat2, $lon2, $unit) {
		if (($lat1 == $lat2) && ($lon1 == $lon2)) {
		  return 0;
		}
		else {
			$theta = $lon1 - $lon2;
			$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
			$dist = acos($dist);
			$dist = rad2deg($dist);
			$miles = $dist * 60 * 1.1515;
		
			if ($unit == "km") {
				return ($miles * 1.609344);
			} else {
				return $miles;
			}			
		}
	}

	/**
	 * Get coordinates from a postal address
	 * 
	 * WARNING : Coordinates returned are [longitude, latitude] and NOT [latitude, longitude]
	 * 
	 * @param, $address
	 * @return	array
	 */
	public static function get_coordinates($address) {

		$api_key = get_option('reforestum_openrouteservice_api_key');
		if(!$api_key) return false;
		
		$request = file_get_contents('https://api.openrouteservice.org/geocode/search?api_key=' . $api_key . '&text=' . urlencode($address));
		if($request) {
			$json = json_decode($request, true);
			if(isset($json['features'])) {
				if(isset($json['features'][0])) {
					if(isset($json['features'][0]['geometry'])) {
						if(isset($json['features'][0]['geometry']['coordinates'])) {
							return $json['features'][0]['geometry']['coordinates'];
						}
					}
				}
			}
		}

		return false;

	}

	/**
	 * Calculate shipping CO2 offset depending on distance
	 * 
	 * @param, $address
	 * @return	array
	 */
	public static function calculate_shipping_co2_offset($address) {
		
		$shop_address = get_option('woocommerce_store_address') . ', ' . get_option('woocommerce_store_address_2') . ', ' . get_option('woocommerce_store_city') . ', ' . get_option('woocommerce_store_postcode') . ', ' . get_option('woocommerce_default_country');
		$shop_coordinates = self::get_coordinates($shop_address);
		if(!$shop_coordinates) return false;
		
		$dest_coordinates = self::get_coordinates($address);
		if(!$dest_coordinates) return false;
		
		$shipping_footprint_per_km = get_option('reforestum_shipping_footprint_per_km');
		if(!$shipping_footprint_per_km) return false;

		$distance = self::get_distance($shop_coordinates[1], $shop_coordinates[0], $dest_coordinates[1], $dest_coordinates[0], 'km');
		if(!$distance) return false;

		return $shipping_footprint_per_km*$distance;

	}

}