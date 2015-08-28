<?php
/*
Plugin Name: TablePress Extension: TablePress with Stockflock
Plugin URI: 
Description: Custom Extension for TablePress to fetch data from Stockflock. <strong>Before enabling this plugin you need to install <a href="https://tablepress.org">TablePress</a> plugin first</strong>.
Version: 0.1.2
Author: Stockflock
Author URI: http://www.stockflock.co
*/

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

// Check for Plugin dependency 
if ( ! class_exists( 'WC_CPInstallCheck' ) ) {
  class WC_CPInstallCheck {
		static function install() {
			// check whether TablePress plugin active
			if ( !in_array( 'tablepress/tablepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				
				// Deactivate the plugin
				deactivate_plugins(__FILE__);
				
				// Throw an error in the wordpress admin console
				$error_message = __('<p>This plugin requires <a href="https://tablepress.org/">TablePress</a> plugin to be active!</p>', 'tablepress');
				die($error_message);
				
			}
		}
	}
}

register_activation_hook( __FILE__, array('WC_CPInstallCheck', 'install') );

class TablePress_Stockflock_Controller
{
	/**
	 * A number of allowed limit for querying company data.
	 * @var integer $company_limit
	 */
	public $company_limit = 40;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		// Hook into TablePress, if we are in the Admin Controller loading process.
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			add_action( 'tablepress_run', array( $this, 'run' ) );
		}
	}

	/**
	 * Start-up the TablePress Stockflock Controller, which is run when TablePress is run.
	 * 
	 */
	public function run() {
		// Only allow access to the Debug Extension for Admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_filter( 'tablepress_load_file_full_path', array( $this, 'change_stockflock_view_full_path' ), 10, 3 ); //done
		add_filter( 'tablepress_admin_view_actions', array( $this, 'add_view_action_stockflock' ) ); //done
		add_action( 'admin_post_tablepress_stockflock', array( $this, 'handle_post_action_stockflock' ) ); //done

		// Turn off "WordPress should correct invalidly nested XHTML automatically" to not mess with saving.
		add_filter( 'pre_option_use_balanceTags', '__return_zero' );
	}

	/**
	 * Adjust the path from which the class PHP file is loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param string $full_path Full path of the class file.
	 * @param string $file      File name of the class file.
	 * @param string $folder    Folder name of the class file.
	 * @return string Modified full path.
	 */
	public function change_stockflock_view_full_path( $full_path, $file, $folder ) {
		if ( 'view-stockflock.php' === $file ) {
			$full_path = plugin_dir_path( __FILE__ ) . $file;
		}
		return $full_path;
	}

	/**
	 * Add the Stockflock view to the list of views in TablePress.
	 *
	 * @since 1.0.0
	 *
	 * @param array $view_actions List of views.
	 * @return array Modified list of views.
	 */
	public function add_view_action_stockflock( $view_actions ) {
		$view_actions['stockflock'] = array(
			'show_entry' => true,
			'page_title' => 'Stockflock',
			'admin_menu_title' => 'Stockflock',
			'nav_tab_title' => 'Stockflock',
			'required_cap' => 'manage_options', // Only grant access to the Stockflock area for admins.
		);
		// If user can not access an action view by default, at least grant access to admins.
		foreach ( $view_actions as $action => $action_details ) {
			if ( ! current_user_can( $action_details['required_cap'] ) ) {
				$view_actions[ $action ]['required_cap'] = 'manage_options';
			}
		}

		return $view_actions;
	}

	/**
	 * Save changes on the Stockflock screen.
	 *
	 */
	public function handle_post_action_stockflock() {
		if ( ! isset( $_POST['stockflock'] ) ) {
			return;
		}

		TablePress::check_nonce( 'stockflock' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		if ( empty( $_POST['stockflock'] ) || ! is_array( $_POST['stockflock'] ) ) {
			TablePress::redirect( array( 'action' => 'stockflock', 'message' => 'error_save' ) );
		} else {
			$stockflock = stripslashes_deep( $_POST['stockflock'] );
		}

		$params = array(
			'option_name' => 'tablepress_stockflock_config',
			'default_value' => array(),
		);
		$stockflock_config = TablePress::load_class( 'TablePress_WP_Option', 'class-wp_option.php', 'classes', $params );

		//trim and restrict array length to be not more than 20
		$raw_companies = explode(',', $stockflock['companies']);
		$valid_companies = array_slice(array_map('trim', $raw_companies), 0, $this->company_limit);
		$stockflock['companies'] = strtoupper(implode(',', $valid_companies));

		//give default value to data_points
		$data_point_list = ['short_name', 'price_value', 'industry_name', 'dividend_yield', 'earning_value', 'dividend_per_share', 
			'book_value', 'debt_assets', 'net_asset_value', 'gearing_ratio', 'property_yield_ratio'];
		if ( is_array($stockflock['data_points']) ) {
			foreach ($data_point_list as $key => $value) {
				if ( ! array_key_exists($value, $stockflock['data_points'])) {
					$stockflock['data_points'][$value] = "false";
				}
			}
		}

		//store to wp_options
		$stockflock_config->update($stockflock);

		TablePress::redirect( array( 'action' => 'stockflock', 'message' => 'success_save' ) );
	}

} // class TablePress_Debug_Controller

// Initialize the Debug Extensions.
new TablePress_Stockflock_Controller();


// Hook filters
add_filter( 'tablepress_table_not_found_message', 'tablepress_render_stockflock_table', 10, 2);

function tablepress_render_stockflock_table($message, $table_id) {
	if ( 'stockflock' === $table_id) {
		$css_url = plugins_url( "css/tablepress-stockflock.css", __FILE__ );
		$logo_url = plugins_url( "images/logo.png", __FILE__);

		//TODO : need to find right way to register script
		$datatables_url = plugins_url( "../tablepress/js/jquery.datatables.min.js", __FILE__);

		wp_enqueue_style( 'tablepress-stockflock', $css_url, array( 'tablepress-default' ), '1.2' );
		wp_enqueue_script( 'tablepress-datatables', $datatables_url, array( 'jquery' ), TablePress::version, true );

		$responsive_css_url = plugins_url( "css/tablepress-responsive.css", __FILE__ );
		wp_enqueue_style( 'tablepress-responsive', $responsive_css_url, array( 'tablepress-default' ), '1.2' );
		// Wrap the <link> tag in a conditional comment to only use the CSS in non-IE browsers.
		echo "<!--[if !IE]><!-->\n";
		wp_print_styles( 'tablepress-responsive' );
		echo "<!--<![endif]-->\n";

		$html = "<table id=\"{$table_id}\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"tablepress tablepress-responsive-tablet\"></table>";
		$html.= "<div class=\"brand\"><div>Powered by</div><a href=\"http://www.stockflock.co\" target=\"_blank\" class=\"brand-link\">";
		$html.= "<div class=\"img-wrapper\"><img src=\"" . $logo_url . "\" class=\"brand-logo\" /><p>stockflock</p><div class=\"clearfix\"></div></div></a></div>";

		$params = array(
			'option_name' => 'tablepress_stockflock_config',
			'default_value' => array(),
		);
		$stockflock_config = TablePress::load_class( 'TablePress_WP_Option', 'class-wp_option.php', 'classes', $params );

		$tmp = array();
		$data_points = $stockflock_config->get('data_points');
		if ( ! empty($data_points) ) {
			foreach ($stockflock_config->get('data_points') as $key => $value) {
				if ( "false" !== $value ) {
					$tmp[] = $key;		
				}	
			}
		}
		//format array key ordering
		$data_pointer = implode(",", $tmp);
		$response = wp_remote_get( 'http://www.stockflock.co/api/company/pluginQuery?company_filter=' . $stockflock_config->get('companies') . '&data_pointer=' . $data_pointer , array( 'timeout' => 120));
		if ( is_wp_error( $response ) ) {
				return "Error: Cannot get response from remote server, maybe the system is busy please try again later.";
		}
		$data = wp_remote_retrieve_body( $response );
		if ( is_wp_error( $data ) ) {
			return "Error: there something wrong with the data, please try again.";
		}
		_enable_datatable( $data, $data_points );

		return $html;
	}else {
		return $message;
	}
}

function getTableColumn($data_points) {
	$column = "";
	if ( ! empty($data_points) ) {
		$formated = sortByFormat($data_points);
		foreach ($formated as $key => $value) {
			if ( "false" !== $value ) {
				if( "short_name" === $key ) 
					$column .= '{ "title": "Company", "mDataProp": "short_name" },';
				if( "industry_name" === $key ) 
					$column .= '{ "title": "Industry", "mDataProp": "industry_name" },';
				if( "earning_value" === $key ) 
					$column .= '{ "title": "P/E", "mDataProp": "earning_value" },';
				if( "book_value" === $key ) 
					$column .= '{ "title": "P/B", "mDataProp": "book_value" },';
				if( "price_value" === $key ) 
					$column .= '{ "title": "Share Price", "mDataProp": "price_value", "mRender": function(data, type, full){ return "$" + data; } },';
				if( "dividend_yield" === $key ) 
					$column .= '{ "title": "Dividend Yield", "mDataProp": "dividend_yield", "mRender": function(data, type, full){ return data + "%"; } },';
				if( "debt_assets" === $key ) 
					$column .= '{ "title": "Debt Assets", "mDataProp": "debt_assets" },';
				if( "shares_outstanding" === $key ) 
					$column .= '{ "title": "Shares Outstanding", "mDataProp": "shares_outstanding", "mRender": function(data, type, full){ return "$" + data; } },';
				if( "net_asset_value" === $key ) 
					$column .= '{ "title": "Net Asset Value", "mDataProp": "net_asset_value", "mRender": function(data, type, full){ return "$" + data; } },';
				if( "gearing_ratio" === $key ) 
					$column .= '{ "title": "Gearing Ratio", "mDataProp": "gearing_ratio", "mRender": function(data, type, full){ return data + "%"; } },';
				if( "property_yield_ratio" === $key ) 
					$column .= '{ "title": "Property Yield", "mDataProp": "property_yield_ratio", "mRender": function(data, type, full){ return data + "%"; } },';
				if( "dividend_per_share" === $key ) 
					$column .= '{ "title": "Dividend Per Share", "mDataProp": "dividend_per_share", "mRender": function(data, type, full){ return "$" + data; } },';
			}	
		}
	}
	return $column;
}

function sortByFormat($lists) {
	$orderingFormat = ['short_name', 'industry_name', 'price_value', 'dividend_per_share', 'dividend_yield', 'property_yield_ratio', 
		'gearing_ratio', 'net_asset_value', 'book_value', 'earning_value', 'debt_assets', 'shares_outstanding'];
	$formated = [];
	foreach ($orderingFormat as $idx => $col) {
		foreach ($lists as $key => $value) {
			if ($col == $key) {
				$formated[$col] = $value;
			}
		}
	}
	return $formated;
}

function _enable_datatable($json_string, $data_points) {
	$columns = getTableColumn($data_points);
	echo <<<JSSCRIPT
<script>
jQuery(document).ready( function($) {
	var json = {$json_string};

	jQuery('#stockflock').dataTable( {
		"bProcessing": true,
	    "aaData": json.data,
	    "bLengthChange": false,
	    "bFilter": false,
	    "bInfo": false,
	    "bPaginate": false,
	    "responsive": true,
	    "aoColumns": [{$columns}]
	});
});
</script>
JSSCRIPT;
}
