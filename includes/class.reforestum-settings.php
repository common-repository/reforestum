<?php 
/**
 * Reforestum Settings
 * 
 * Extends WC_Settings_Page to add new tab in WooCommerce settings
 * @package	Reforestum\Settings
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'Reforestum_Settings' ) ) {

	function reforestum_settings() {

		/**
		 * Reforestum_Settings Class
		 * 
		 * @since 1.0
		 */
		class Reforestum_Settings extends WC_Settings_Page {
			
			public function __construct() {
				$this->id    = 'reforestum';
				$this->label = __( 'Reforestum', 'reforestum' );
				add_filter( 'woocommerce_settings_tabs_array',        array( $this, 'add_settings_page' ), 20 );
				add_action( 'woocommerce_settings_' . $this->id,      array( $this, 'output' ) );
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			}

			/**
			 * Get settings array
			 *
			 * @since 1.0.0
			 * @param string $current_section Optional. Defaults to empty string.
			 * @return array Array of settings
			 */
			public function get_settings( $current_section = '' ) {

				$api_connection 				= get_option( 'reforestum_api_connected' );
				
				// Plugin settings & default settings
				$plugin_payer 						= get_option( 'reforestum_payer', 'm' );
				$plugin_product_unit				= get_option( 'reforestum_product_unit', 'kg' );
				$plugin_shipping_unit			= get_option( 'reforestum_shipping_unit', 'kg' );
				$plugin_contracts					= get_option( 'reforestum_sales_contracts' );
				$plugin_project_restricted		= get_option( 'reforestum_project_restricted' );
				$plugin_forests					= get_option( 'reforestum_selected_forests' );
				$plugin_sales_contract			= get_option( 'reforestum_sales_contract' );
				$plugin_display_in_product		= get_option( 'reforestum_display_in_product', 'yes' );
				$plugin_display_in_cart			= get_option( 'reforestum_display_in_cart', 'yes' );
				$plugin_display_in_checkout	= get_option( 'reforestum_display_in_checkout', 'yes' );
				$plugin_shipping_offset_method = get_option( 'reforestum_shipping_offset_method', 'n' );

				$settings = apply_filters( 'reforestum_settings', array(

					/**
					 * API settings
					 */

					 // Enter your API username and password to enable the Reforestum’s API service. The connection test will be performed after saving these settings. More instructions on how to set your account here.
					array(
						'name'     => __( 'API Credentials', 'reforestum' ),
						'type'     => 'title',
						'desc'     => __( 'Enter your API username and password to enable the Reforestum\'s API service. The connection test will be performed after saving these settings.<br />More instructions on how to set your account <a href="https://api.reforestum.com/faq" target="_blank">here</a>.', 'reforestum' ),
						'id'       => 'reforestum_section_api'
					),
					array(
						'name' 		=> __( 'Username', 'reforestum' ),
						'type' 		=> 'text',
						'id'   		=> 'reforestum_api_username'
					),
					array(
						'name' 		=> __( 'Password', 'reforestum' ),
						'type' 		=> 'password',
						'id'   		=> 'reforestum_api_password',
						'desc'		=> ($api_connection ? '<span class="reforestum-api-status reforestum-api-status--success">' . __( 'Connection successful', 'reforestum' ) . '</span>' : '<span class="reforestum-api-status reforestum-api-status--error">' . __( 'Connection failed', 'reforestum' ) . '</span>')
					),
					array(
						'name'		=> __( 'Sandbox mode', 'reforestum' ),
						'desc'		=> __( 'Enable Sandbox Mode', 'reforestum' ),
						'desc_tip'	=> __( 'While sandbox is enabled, all offsetting transactions will be simulated', 'reforestum' ),
						'type'		=> 'checkbox',
						'id'			=> 'reforestum_sandbox_mode',
					),
					array(
						'name'		=> __( 'Credit', 'reforestum' ),
						'type'		=> 'text',
						'value'		=> Reforestum_API::credit(),
						'custom_attributes' => array('readonly' => 'readonly'),
					),
					array(
						'type'		=> 'sectionend',
						'id'			=> 'reforestum_section_api'
					),

					/**
					 * Shipping settings
					 */
					array(
						'name'		=> __( 'Shipping Settings', 'reforestum' ),
						'type'		=> 'title',
						'id'			=> 'reforestum_section_shipping',
						'desc'		=> __( '
							Some settings regarding the shipping aspect of the process. When the method is set to dynamic, an attempt at calculting the distance between seller and buyer will be performed. If it is unsuccesful, the method will switch to None.
							When using the dynamic method, we recommend that you make sure your shop address is as precise as it can be and that you check the lat/lng coordinates with the "Test the connection" button.
						', 'reforestum' )
					),

					array(
						'name'		=> __( 'Shipping offset method', 'reforestum' ),
						'desc'		=> __( 'Select what is the shipping offset method in checkout page', 'reforestum' ),
						'type'		=> 'select',
						'id'		=> 'reforestum_shipping_offset_method',
						'options'	=> array(
							'n'		=> __( 'None - No shipping offset', 'reforestum' ),
							'f'		=> __( 'Fixed - Based on a flat rate on a per-product basis', 'reforestum' ),
							'd'		=> __( 'Dynamic - Calculation based on the shipping distance', 'reforestum' )
						),
						'value'		=> $plugin_shipping_offset_method
					),
					array(
						'name' 		=> __( 'Shipping footprint per km', 'reforestum' ),
						'type' 		=> 'text',
						'desc'		=> __( 'Only used if the shipping offset method is set to dynamic<br>To be set in kg', 'reforestum' ),
						'id'   		=> 'reforestum_shipping_footprint_per_km'
					),
					array(
						'name' 		=> __( 'Openrouteservice api key', 'reforestum' ),
						'type' 		=> 'text',
						'desc'		=> __( 'Only used if the shipping offset method is set to dynamic<br>Get a free API key <a href="https://openrouteservice.org/dev/#/signup" target="_blank">here</a>. The Openrouteservice API is used to get coordinates from a postal address<br><button class="button button-secondary" id="openrouteservice-test">Test the connection</button><div id="openrouteservice-test-response" data-success="Coordinates have been found for your shop. Longitude LON and latitude LAT. [gmaps_link]Check on google maps[/gmaps_link]"></div>', 'reforestum' ),
						'id'   		=> 'reforestum_openrouteservice_api_key'
					),

					array(
						'type'		=> 'sectionend',
						'id'			=> 'reforestum_section_shipping'
					),


					/**
					 * General settings
					 */
					array(
						'name'		=> __( 'General Settings', 'reforestum' ),
						'type'		=> 'title',
						'id'			=> 'reforestum_section_general',
						'desc'		=> __( 'Note these general settings are taken as default options and that some of them can be overridden at the product category level and/or product level configurations.', 'reforestum' )
					),
					array(
						'name'		=> __( 'Carbon offset display', 'reforestum' ),
						'desc'		=> __( 'Display carbon footprint in product page', 'reforestum' ),
						'type'		=> 'checkbox',
						'id'			=> 'reforestum_display_in_product',
						'value'		=> $plugin_display_in_product
					),
					array(
						'name'		=> '',
						'desc'		=> __( 'Display carbon footprint in cart page', 'reforestum' ),
						'type'		=> 'checkbox',
						'id'			=> 'reforestum_display_in_cart',
						'value'		=> $plugin_display_in_cart
					),
					array(
						'name'		=> '',
						'desc'		=> __( 'Display carbon footprint and offset in checkout page', 'reforestum' ),
						'type'		=> 'checkbox',
						'id'			=> 'reforestum_display_in_checkout',
						'value'		=> $plugin_display_in_checkout
					),
					array(
						'name'		=> __( 'Who pays for Carbon Offset', 'reforestum' ),
						'desc_tip'	=> __( 'Select who pays for the offset. If it\'s paid by the end customer, the option will be offered to them to calculate the price of the CO2 offset and automatically add it to the final price in the checkout process.', 'reforestum' ),
						'type'		=> 'radio',
						'id'			=> 'reforestum_payer',
						'options'	=> array(
							'm'		=> __( 'Merchant', 'reforestum' ),
							'c'		=> __( 'End Customer', 'reforestum' )
						),
						'value'		=> $plugin_payer
					),
					array(
						'name'		=> __( 'Project choice restrictions', 'reforestum' ),
						'desc'		=> __( 'Activate project constraints', 'reforestum' ),
						'type'		=> 'checkbox',
						'id'			=> 'reforestum_project_restricted',
					),
					array(
						'name'		=> __( 'Select forest(s)', 'reforestum' ),
						'desc'		=> __( 'Select forests that will be available for your customers when project restriction is enabled.', 'reforestum' ),
						'type'		=> 'multi_checkbox',
						'id'			=> 'reforestum_selected_forests',
						'options'	=> Reforestum_API::forests_options(),
						'value'		=> $plugin_forests,
					),
					array(
						'name'		=> __( 'Default Sales Contract', 'reforestum' ),
						'desc'		=> __( 'Select the default Sales Contract. This setting can be overridden at the category and product level.', 'reforestum' ),
						'type'		=> 'select',
						'id'			=> 'reforestum_sales_contract',
						'options'	=> Reforestum_API::contracts_option( 'plugin' ),
					),
					array(
						'type' => 'sectionend',
						'id' => 'reforestum_section_general'
					),
				) );

				/**
				 * Filter Settings
				 *
				 * @since 1.0.0
				 * @param array $settings Array of the plugin settings
				 */
				return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section ); 
			}

			/**
			 * Output the settings
			 *
			 * @since 1.0
			 */
			public function output() {
			
				global $current_section;

				echo '<div class="reforestum-branding"><img src="' . REFORESTUM_PLUGIN_URI . '/public/images/reforestum-logo.png' . '" alt="Reforestum"></div>';

				$settings = $this->get_settings( $current_section );
				WC_Admin_Settings::output_fields( $settings );

				$contracts = get_option( 'reforestum_sales_contracts' );

				echo '<h2>' . __( 'Sales contract attached to your Reforestum’s account', 'reforestum' ) . '</h2>';
				?>

				<table class="fixed widefat striped reforestum-table">
					<thead>
						<tr>
							<th><?php echo __( 'Contract Description', 'reforestum' ); ?></th>
							<th><?php echo __( 'Key', 'reforestum' ); ?></th>
							<th><?php echo __( 'Contract Type', 'reforestum' );  ?></th>
							<th width="25%"><?php echo __( 'Available forest(s)', 'reforestum' ); ?></th>
							<th><?php echo __( 'Default Value', 'reforestum' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if( ! empty( $contracts ) ){ ?>
							<?php foreach( $contracts as $contract ){ ?>
								<?php 
								$alias 	= empty( $contract->contract_alias ) ?  $contract->contract_key : $contract->contract_alias;

								if( $contract->contract_type == 'fixed_bc_2_ec' ){
									$contract_type = __( 'Predefined project and/or amount selected', 'reforestum' );
								} elseif( $contract->contract_type == 'free_bc_2_ec' ) {
									$contract_type = __( 'Variable Amount set manually by end customer', 'reforestum' );
								}
								?>
								<tr>
									<td><?php echo $alias; ?></td>
									<td><?php echo $contract->contract_key; ?></td>
									<td><?php echo $contract_type; ?></td>
									<td>
										<?php 
										if( empty( $contract->forests ) ){
											echo __( 'Any', 'reforestum' );
										} else {
											echo '<ul>';
											foreach( $contract->forests as $forest_id ){
												$forest	= Reforestum_API::get_forest_by_id( $forest_id );
												echo '<li>' . $forest->name . ', ' . $forest->location_desc . '</li>';
											}
											echo '</ul>';
										}
										?>
									</td>
									<td>
										<?php 
										if( empty( $contract->value ) ){
											echo __( 'Free amount', 'reforestum' );
										} else {
											echo $contract->value . ' ' . $contract->units;
										}
										?>
									</td>
								</tr>
							<?php } ?>
						<?php } else { ?>
							<tr>
								<td colspan="5" align="center"><?php echo __( 'You don´t have any sales contract defined yet. More instructions on how to set your account <a href="https://api.reforestum.com/faq" target="_blank">here</a>.', 'reforestum' ); ?></td>
							</tr>
						<?php } ?>
						
					</tbody>
				</table>

			<?php }
			
			
			/**
			 * Save settings
			*
			* @since 1.0
			*/
			public function save() {
			
				global $current_section;

				$settings = $this->get_settings( $current_section );

				WC_Admin_Settings::save_fields( $settings );

				// Check API connection
				Reforestum_API::authentication();

				// Check sales contracts
				Reforestum_API::sales_contracts();

				// Check forests
				Reforestum_API::forests();

			}

		}
		return new Reforestum_Settings();
	}

	add_filter( 'woocommerce_get_settings_pages', 'reforestum_settings', 15 );

}