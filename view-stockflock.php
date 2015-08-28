<?php
/**
 * Stockflock View
 *
 * @package TablePress
 * @subpackage Views
 * @author stockflock
 *
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Stockflock View class
 * @package TablePress
 * @subpackage Views
 * @author stockflock
 *
 */
class TablePress_Stockflock_View extends TablePress_View {

	protected $stockflock_config;

	/**
	 * Set up the view with data and do things that are specific for this view.
	 *
	 * @param string $action Action for this view.
	 * @param array $data Data for this view.
	 */
	public function setup( $action, array $data ) {
		$params = array(
			'option_name' => 'tablepress_stockflock_config',
			'default_value' => array(),
		);
		$this->stockflock_config = TablePress::load_class( 'TablePress_WP_Option', 'class-wp_option.php', 'classes', $params );

		parent::setup( $action, $data );

		$this->add_header_message( '<span>' . __( 'Please contact stockflock if you get any issue with this plugin.', 'tablepress' ) . '</span>', 'notice-info' );

		$this->process_action_messages( array(
			'success_save' => __( 'Stockflock Changes saved successfully.', 'tablepress' ),
			'error_save' => __( 'Error: Stockflock Changes could not be saved.', 'tablepress' )
		) );
	
		$this->add_meta_box( 'stockflock-options', __( 'Stockflock Options', 'tablepress' ), array( $this, 'stockflock_plugin_options' ), 'normal' );
		$this->add_text_box( 'submit', array( $this, 'textbox_submit_button' ), 'submit' );
	}

	/**
	 * Display the post box for the Plugin Options field.
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function stockflock_plugin_options( $data, $box ) {
		$companies = $this->stockflock_config->get('companies');
		$data_points = $this->stockflock_config->get('data_points');

		//set default value for checkbox's
		if ( is_null($data_points) ){
			$data_points = array(
				'short_name' => "true",
				'price_value' => "true",
				'industry_name' => "true",
				'dividend_yield' => "true",
				'earning_value' => "true",
				'book_value' => "true",
				'debt_assets' => "true"
			);
		}

		?>
		<table class="tablepress-postbox-table tablepress-stockflock-table fixed">
			<tbody>
				<tr>
					<th class="column-1" scope="row" style="vertical-align: top;"><?php _e( 'Companies', 'tablepress' ); ?>:</th>
					<td class="column-2">
						<textarea name="stockflock[companies]" id="sf-option-companies" class="large-text" rows="8"><?php echo esc_textarea( $companies ); ?></textarea>
						<p class="description"><?php _e( 'use "," to separate each desired company.', 'tablepress' ); ?></p>
					</td>
				</tr>
				<tr>
					<th class="column-1" scope="row" style="vertical-align: top;"><?php _e( 'Data Points', 'tablepress' ); ?>:</th>
					<td class="column-2">
						<table>
							<tbody>
								<tr>
									<td>
										<label for="sf-option-data-point-short-name">
											<input type="checkbox" id="sf-option-data-point-short-name" name="stockflock[data_points][short_name]" value="true"<?php checked( $data_points['short_name'] , "true", "false"); ?> /> <?php _e( 'Short name', 'tablepress' ); ?>
										</label>
									</td>
									<td>
										<label for="sf-option-data-point-price-quote">
											<input type="checkbox" id="sf-option-data-point-price-quote" name="stockflock[data_points][price_value]" value="true"<?php checked( $data_points['price_value'] , "true", "false"); ?> /> <?php _e( 'Price quote', 'tablepress' ); ?>
										</label>
									</td>
									<td>
										<label for="sf-option-data-point-industry">
											<input type="checkbox" id="sf-option-data-point-industry" name="stockflock[data_points][industry_name]" value="true"<?php checked( $data_points['industry_name'] , "true", "false"); ?> /> <?php _e( 'Industry', 'tablepress' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<td>
										<label for="sf-option-data-point-dividend-yield">
											<input type="checkbox" id="sf-option-data-point-dividend-yield" name="stockflock[data_points][dividend_yield]" value="true"<?php checked( $data_points['dividend_yield'] , "true", "false"); ?> /> <?php _e( 'Dividend Yield', 'tablepress' ); ?>
										</label>
									</td>
									<td>
										<label for="sf-option-data-point-price-earning">
											<input type="checkbox" id="sf-option-data-point-price-earning" name="stockflock[data_points][earning_value]" value="true"<?php checked( $data_points['earning_value'] , "true", "false"); ?> /> <?php _e( 'Price-earnings ratio', 'tablepress' ); ?>
										</label>
									</td>
									<td>
										<label for="sf-option-data-point-price-book">
											<input type="checkbox" id="sf-option-data-point-price-book" name="stockflock[data_points][book_value]" value="true"<?php checked( $data_points['book_value'] , "true", "false"); ?> /> <?php _e( 'Price-book ratio', 'tablepress' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<td>
										<label for="sf-option-data-point-debt-asset">
											<input type="checkbox" id="sf-option-data-point-debt-asset" name="stockflock[data_points][debt_assets]" value="true"<?php checked( $data_points['debt_assets'] , "true", "false"); ?> /> <?php _e( 'Debt-asset ratio', 'tablepress' ); ?>
										</label>
									</td>
									<td>
										<label for="sf-option-data-point-gearing-ratio">
											<input type="checkbox" id="sf-option-data-point-gearing-ratio" name="stockflock[data_points][gearing_ratio]" value="true"<?php checked( $data_points['gearing_ratio'] , "true", "false"); ?> /> <?php _e( 'Gearing ratio', 'tablepress' ); ?>
										</label>
									</td>
									<td>
										<label for="sf-option-data-point-net-asset-value">
											<input type="checkbox" id="sf-option-data-point-net-asset-value" name="stockflock[data_points][net_asset_value]" value="true"<?php checked( $data_points['net_asset_value'] , "true", "false"); ?> /> <?php _e( 'Net Asset Value', 'tablepress' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<td>
										<label for="sf-option-data-point-property-yield-ratio">
											<input type="checkbox" id="sf-option-data-point-property-yield-ratio" name="stockflock[data_points][property_yield_ratio]" value="true"<?php checked( $data_points['property_yield_ratio'] , "true", "false"); ?> /> <?php _e( 'Property Yield ratio', 'tablepress' ); ?>
										</label>
									</td>
									<td>
										<label for="sf-option-data-point-dividend-per-share">
											<input type="checkbox" id="sf-option-data-point-dividend-per-share" name="stockflock[data_points][dividend_per_share]" value="true"<?php checked( $data_points['dividend_per_share'] , "true", "false"); ?> /> <?php _e( 'Dividend Per Share', 'tablepress' ); ?>
										</label>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

} // class TablePress_Debug_View
