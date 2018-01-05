<?php
/*
 Plugin Name: IP to Currency Adapter
 Plugin URI: https://numbervine.github.io
 Description: A Wordpress plugin to map client IP address to local currency
 Author: Thomas Varghese
 Version: 1.1
 Author URI: https://numbervine.github.io
 */

global $ip2c_records_table_name_suffix;
$ip2c_records_table_name_suffix = 'ip2c_records';
$ip2c_db_version = '0.1';

global $ip2c_db_filname;
$ip2c_db_filname='ip-to-country.bin';

global $ip2currency_default_price;
$ip2currency_default_price = 99.99;

global $ip2c_country_code_currency_map;
$ip2c_country_code_currency_map = array(
   'MY' => array( 'CountyCode' => 'MY',
		  'CountryName' => 'Malaysia',
		  'CurrencyCode' => 'MYR',
		  'CurrencyName' => 'Malaysian Ringgit'
		),
   'US' => array('CountyCode' => 'US',
		  'CountryName' => 'United States',
		  'CurrencyCode' => 'USD',
		  'CurrencyName' => 'US Dollar'
        ),
   'GB' => array( 'CountyCode' => 'GB',
		  'CountryName' => 'United Kingdom',
		  'CurrencyCode' => 'GBP',
		  'CurrencyName' => 'British Pound'
        ),
   'ID' => array( 'CountyCode' => 'ID',
		  'CountryName' => 'Indonesia',
		  'CurrencyCode' => 'IDR',
		  'CurrencyName' => 'Indonesian Rupiah'
		),
   'TH' => array( 'CountyCode' => 'TH',
		  'CountryName' => 'Thailand',
		  'CurrencyCode' => 'THB',
		  'CurrencyName' => 'Thailand Baht'
		),
   'AE' => array( 'CountyCode' => 'AE',
		  'CountryName' => 'United Arab Emirates',
		  'CurrencyCode' => 'INR',
		  'CurrencyName' => 'Emirati Dirham'
		),
   'AU' => array( 'CountyCode' => 'AU',
		  'CountryName' => 'Australia',
		  'CurrencyCode' => 'AUD',
		  'CurrencyName' => 'Australian Dollar'
		),
   'SG' => array( 'CountyCode' => 'SG',
		  'CountryName' => 'Singapore',
		  'CurrencyCode' => 'SGD',
		  'CurrencyName' => 'Singapore Dollar'
		),
   'IN' => array( 'CountyCode' => 'IN',
		  'CountryName' => 'India',
		  'CurrencyCode' => 'INR',
		  'CurrencyName' => 'Indian Rupee'
		)
);


global $ip2c_product_names;
$ip2c_product_names = array(
	'1 day course',
  	'5 day course'
);


function get_client_ip()
{
	$v='';
	$v= (!empty($_SERVER['REMOTE_ADDR']))?$_SERVER['REMOTE_ADDR'] :((!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR']: @getenv('REMOTE_ADDR'));
	if(isset($_SERVER['HTTP_CLIENT_IP']))
	$v=$_SERVER['HTTP_CLIENT_IP'];
	return htmlspecialchars($v,ENT_QUOTES);
}

function ip2currency_get_price($product_id = 0, $idx = 0)
{
	global $wpdb;

	$result = '';

	global $ip2c_product_names;

	if (($product_id<1 || $product_id>count($ip2c_product_names)) || ($idx<1 || $idx>5))
	{
		$result = '[error: Invalid arguments]';
		return $result;
	}

	$client_country_code = ip2c_get_client_country_code();

	$product_id_C_style = $product_id - 1;

	global $ip2c_records_table_name_suffix;
	$sql = "SELECT product_id, countrycode, price1, price2, price3, price4, price5
		FROM " . $wpdb->prefix . $ip2c_records_table_name_suffix;

	$condition = " WHERE countrycode='" . $client_country_code . "' AND product_id='" . $product_id_C_style ."'";

	if ($client_country_code)
	{
		if (ip2currency_record_count($condition)==1)
		{
			$sql .= $condition;
		}
		else
		{
			$sql .= " WHERE id='" . $product_id . "'";
		}
	}
	else
	{
		// use default
		$sql .= " WHERE id='" . $product_id . "'";
	}

	$ip2crecord = $wpdb->get_row($sql, ARRAY_A);

	foreach($ip2crecord as $key => $value)
		$ip2crecord[$key] = $ip2crecord[$key];
	extract($ip2crecord);
	$product_id = htmlspecialchars($product_id);
	$countrycode = htmlspecialchars($countrycode);
	$price1 = htmlspecialchars($price1);
	$price2 = htmlspecialchars($price2);
	$price3 = htmlspecialchars($price3);
	$price4 = htmlspecialchars($price4);
	$price5 = htmlspecialchars($price5);

	global $ip2c_country_code_currency_map;

	switch ($idx)
	{
		case 1:
			$result = $price1;
			break;
		case 2:
			$result = $price2;
			break;
		case 3:
			$result = $price3;
			break;
		case 4:
			$result = $price4;
			break;
		case 5:
			$result = $price5;
			break;
		default:
			$result = 'null';
	}
	$result .= " " . $ip2c_country_code_currency_map[$countrycode]['CurrencyCode'];

	return $result;
}

function ip2currency_get_price_func( $atts ) {
	extract( shortcode_atts( array(
		'product_id' => '',
		'idx' => ''
	), $atts ) );

	$result = ip2currency_get_price($product_id,$idx);

	return $result;
}
add_shortcode( 'ip2currency_get_price', 'ip2currency_get_price_func' );



function ip2c_get_client_country_code()
{
	require_once('ip2c.php');

	$caching = false;

	global $ip2c_db_filname;

	$ip2cdb_filename_complete = plugin_dir_path(__FILE__) . $ip2c_db_filname;
	$ip2c = new ip2country($ip2cdb_filename_complete,$caching);

	$res = $ip2c->get_country(get_client_ip());
	$result = '';
	if ($res)
	{
//	  $o2c = $res['id2'];
//	  $o3c = $res['id3'];
//	  $oname = $res['name'];
//    echo "$o2c $o3c $oname"; // will output IL ISR ISRAEL
		$result = $res['id2'];
	}

	return $result;
}


function ip2c_admin_actions() {

	add_options_page("IP2Currency", "IP2Currency", 'manage_options', basename(__FILE__), 'ip2c_admin');

}

add_action('admin_menu', 'ip2c_admin_actions');



function ip2currency_install() {
	global $wpdb;
	$table_name = $wpdb->prefix;

	global $ip2c_records_table_name_suffix;
	$table_name .= $ip2c_records_table_name_suffix;

	if(!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
		$db_charset = 'utf8';
	$db_charset = "CHARACTER SET ".$db_charset;
	if(defined('DB_COLLATE') && $db_collate = DB_COLLATE)
		$db_collate = "COLLATE ".$db_collate;

	// if table name already exists
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$wpdb->query("ALTER TABLE `{$table_name}` {$db_charset} {$db_collate}");

	}
	else {
		//Creating the table ... fresh!
		$sql = "CREATE TABLE " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			product_id mediumint(9),
			countrycode VARCHAR(2),
			price1 FLOAT,
			price2 FLOAT,
			price3 FLOAT,
			price4 FLOAT,
			price5 FLOAT,
			time_added datetime NOT NULL,
			time_updated datetime,
			PRIMARY KEY  (id)
	) {$db_charset} {$db_collate};";
		$results = $wpdb->query( $sql );

		if(FALSE === $results) {
			return __('There was an error in the MySQL query', 'ip2currency');
		}

		global $ip2currency_default_price;
		$default_price = $ip2currency_default_price;

		global $ip2c_country_code_currency_map;

		reset($ip2c_country_code_currency_map);
		$default_countrycode = key($ip2c_country_code_currency_map);

		$default_countrycode = trim( stripslashes($default_countrycode) );
		$default_price = trim( stripslashes($default_price) );

		$default_countrycode = "'".$wpdb->escape($default_countrycode)."'";
		$default_price = $default_price?"'".$wpdb->escape($default_price)."'":"NULL";

		global $ip2c_product_names;

		for ($default_product_id = 0; $default_product_id<count($ip2c_product_names); ++$default_product_id)
		{
			$sql = "INSERT INTO " . $table_name;
			$sql .= " (product_id, countrycode, price1, price2, price3, price4, price5, time_added) ";
			$sql .= "VALUES ({$default_product_id}, {$default_countrycode}, {$default_price}, {$default_price}, {$default_price}, {$default_price},{$default_price}, NOW())";
			$result = $wpdb->query( $sql );

			if(FALSE === $results) {
				return __('There was an error in the MySQL query', 'ip2currency');
			}
		}
	}

	global $ip2c_db_version;
	$options = get_option('ip2currency');
	$options['db_version'] = $ip2c_db_version;
	update_option('ip2currency', $options);


//	if(FALSE === $results)
//		return __('There was an error in the MySQL query', 'ip2currency');
//	else
//		return __('Record added', 'ip2currency');
}

register_activation_hook( __FILE__, 'ip2currency_install' );


function ip2currency_uninstall() {

	global $wpdb;
	$table_name = $wpdb->prefix;

	global $ip2c_records_table_name_suffix;
	$table_name .= $ip2c_records_table_name_suffix;

	$table_name = $wpdb->prefix.$ip2c_records_table_name_suffix;

	// if table name already exists
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$wpdb->query("DROP TABLE `{$table_name}`");
	}

	$options = get_option('ip2currency');
	$options['db_version'] = '';
	update_option('ip2currency', $options);
}
register_deactivation_hook( __FILE__, 'ip2currency_uninstall' );

function ip2currency_record_count($condition = "")
{
	global $wpdb;
	global $ip2c_records_table_name_suffix;

	$sql = "SELECT COUNT(*) FROM " . $wpdb->prefix . $ip2c_records_table_name_suffix . " " .$condition;
	$count = $wpdb->get_var($sql);
	return $count;
}

function ip2currency_pagenav($total, $current = 1, $format = 0, $paged = 'paged', $url = "")
{
	if($total == 1 && $current == 1) return "";

	if(!$url) {
		$url = 'http';
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$url .= "s";}
		$url .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["PHP_SELF"];
		} else {
			$url .= $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"];
		}
		if($query_string = $_SERVER['QUERY_STRING']) {
			$parms = explode('&', $query_string);
			$y = '?';
			foreach($parms as $parm) {
				$x = explode('=', $parm);
				if($x[0] == $paged) {
					$query_string = str_replace($y.$parm, '', $query_string);
				}
				else $y = '&';
			}
			if($query_string) {
				$url .= '?'.$query_string;
				$a = '&';
			}
			else $a = '?';
		}
		else $a = '?';
	}
	else {
		$a = '?';
		if(strpos($url, '?')) $a = '&';
	}

	if(!$format || $format > 2 || $format < 0 || !is_numeric($format)) {
		if($total <= 8) $format = 1;
		else $format = 2;
	}


	if($current > $total) $current = $total;
		$pagenav = "";

	if($format == 2) {
		$first_disabled = $prev_disabled = $next_disabled = $last_disabled = '';
		if($current == 1)
			$first_disabled = $prev_disabled = ' disabled';
		if($current == $total)
			$next_disabled = $last_disabled = ' disabled';

		$pagenav .= "<a class=\"first-page{$first_disabled}\" title=\"".__('Go to the first page', 'ip2currency')."\" href=\"{$url}\">&laquo;</a>&nbsp;&nbsp;";
		$pagenav .= "<a class=\"prev-page{$prev_disabled}\" title=\"".__('Go to the previous page', 'ip2currency')."\" href=\"{$url}{$a}{$paged}=".($current - 1)."\">&#139;</a>&nbsp;&nbsp;";
		$pagenav .= '<span class="paging-input">'.$current.' of <span class="total-pages">'.$total.'</span></span>';
		$pagenav .= "&nbsp;&nbsp;<a class=\"next-page{$next_disabled}\" title=\"".__('Go to the next page', 'ip2currency')."\" href=\"{$url}{$a}{$paged}=".($current + 1)."\">&#155;</a>";
		$pagenav .= "&nbsp;&nbsp;<a class=\"last-page{$last_disabled}\" title=\"".__('Go to the last page', 'ip2currency')."\" href=\"{$url}{$a}{$paged}={$total}\">&raquo;</a>";

	}
	else {
		$pagenav = __("Goto page:", 'ip2currency');
		for( $i = 1; $i <= $total; $i++ ) {
			if($i == $current)
				$pagenav .= "&nbsp<strong>{$i}</strong>";
			else if($i == 1)
				$pagenav .= "&nbsp;<a href=\"{$url}\">{$i}</a>";
			else
				$pagenav .= "&nbsp;<a href=\"{$url}{$a}{$paged}={$i}\">{$i}</a>";
		}
	}
	return $pagenav;
}


function ip2currency_addrecord($product_id, $countrycode, $price1 = "", $price2 = "", $price3 = "", $price4 = "", $price5 = "")
{
	if(!$countrycode) return __('Nothing added to the database.', 'ip2currency');
	global $wpdb;
	$table_name = $wpdb->prefix;

	global $ip2c_records_table_name_suffix;
	$table_name .= $ip2c_records_table_name_suffix;

	$condition='WHERE countrycode="'.$countrycode.'" AND product_id="' . $product_id .'"';

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		return __('Database table not found', 'ip2currency');
	} else if (ip2currency_record_count($condition)>0) {
		return __('Record with same product_id and country code already exists.', 'ip2currency');
	} else if ($countrycode=='XX')
	{
		return __('Invalid Country.', 'ip2currency');
	}
	else //Add the countrycode data to the database
	{
		$product_id = trim( stripslashes($product_id) );
		$countrycode = trim( stripslashes($countrycode) );
		$price1 = trim( stripslashes($price1) );
		$price2 = trim( stripslashes($price2) );
		$price3 = trim( stripslashes($price3) );
		$price4 = trim( stripslashes($price4) );
		$price5 = trim( stripslashes($price5) );


		$product_id = "'".$wpdb->escape($product_id)."'";
		$countrycode = "'".$wpdb->escape($countrycode)."'";
		$price1 = $price1?"'".$wpdb->escape($price1)."'":"NULL";
		$price2 = $price2?"'".$wpdb->escape($price2)."'":"NULL";
		$price3 = $price3?"'".$wpdb->escape($price3)."'":"NULL";
		$price4 = $price4?"'".$wpdb->escape($price4)."'":"NULL";
		$price5 = $price5?"'".$wpdb->escape($price5)."'":"NULL";

		$insert = "INSERT INTO " . $table_name .
			"(product_id, countrycode, price1, price2, price3, price4, price5, time_added)" .
			"VALUES ({$product_id}, {$countrycode}, {$price1}, {$price2}, {$price3}, {$price4},{$price5}, NOW())";
		$results = $wpdb->query( $insert );
		if(FALSE === $results)
			return __('There was an error in the MySQL query', 'ip2currency');
		else
			return __('Record added', 'ip2currency');
   }
}

function ip2currency_editrecord($id, $product_id, $countrycode, $price1 = "", $price2 = "", $price3 = "", $price4 = "", $price5 = "")
{
	if(!$countrycode || !$product_id) return __('Record not updated.', 'ip2currency');
//	if(!$id) return ip2currency_addrecord($product_id, $countrycode, $price1, $price2, $price3, $price4, $price5);
	global $wpdb;
	$table_name = $wpdb->prefix;

	global $ip2c_records_table_name_suffix;
	$table_name .= $ip2c_records_table_name_suffix;

	$condition='WHERE product_id="' .$product_id . '" AND countrycode="'.$countrycode.'" AND id!="'.$id.'"';

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
	{
		return __('Database table not found', 'ip2currency');
	} else if (ip2currency_record_count($condition)>0) {
		return __('Record with same product_id and country code already exists.', 'ip2currency');
	} else if ($countrycode=='XX')
	{
		return __('Invalid Country.', 'ip2currency');
	}
	else //Update database
	{
		$product_id = trim( stripslashes($product_id) );
		$countrycode = trim( stripslashes($countrycode) );
		$price1 = trim( stripslashes($price1) );
		$price2 = trim( stripslashes($price2) );
		$price3 = trim( stripslashes($price3) );
		$price4 = trim( stripslashes($price4) );
		$price5 = trim( stripslashes($price5) );


	  	$product_id = "'".$wpdb->escape($product_id)."'";
	  	$countrycode = "'".$wpdb->escape($countrycode)."'";
		$price1 = $price1?"'".$wpdb->escape($price1)."'":"NULL";
		$price2 = $price2?"'".$wpdb->escape($price2)."'":"NULL";
		$price3 = $price3?"'".$wpdb->escape($price3)."'":"NULL";
		$price4 = $price4?"'".$wpdb->escape($price4)."'":"NULL";
		$price5 = $price5?"'".$wpdb->escape($price5)."'":"NULL";

		$update = "UPDATE " . $table_name . "
			SET product_id = {$product_id},
				countrycode = {$countrycode},
				price1 = {$price1},
				price2 = {$price2},
				price3 = {$price3},
				price4 = {$price4},
				price5 = {$price5},
				time_updated = NOW()
			WHERE id = $id";
		$results = $wpdb->query( $update );
		if(FALSE === $results)
			return __('There was an error in the MySQL query', 'ip2currency');
		else
			return __('Changes saved', 'ip2currency');
   }
}

function ip2currency_deleterecord($id)
{
	global $ip2c_product_names;
	if($id) {
		if ($id>count($ip2c_product_names))
		{
		global $wpdb;
		global $ip2c_records_table_name_suffix;
		$sql = "DELETE from " . $wpdb->prefix . $ip2c_records_table_name_suffix .
			" WHERE id = " . $id;
		if(FALSE === $wpdb->query($sql))
			return __('There was an error in the MySQL query', 'ip2currency');
		else
			return __('Record deleted', 'ip2currency');
		}
		else
		{
			return __('Cannot delete default price record.', 'ip2currency');
		}
	}
	else return __('The record cannot be deleted', 'ip2currency');
}


function ip2currency_get_ip2c_data($id)
{
	global $wpdb;
	global $ip2c_records_table_name_suffix;
	$sql = "SELECT id, product_id, countrycode, price1, price2, price3, price4, price5
		FROM " . $wpdb->prefix . $ip2c_records_table_name_suffix . "
		WHERE id = {$id}";
	$ip2c_data = $wpdb->get_row($sql, ARRAY_A);
	return $ip2c_data;
}

function ip2c_admin()
{
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	global $ip2c_db_version;
	$options = get_option('ip2currency');
	$display = $msg = $ip2crecords_list = $alternate = "";

	if($options['db_version'] != $ip2c_db_version )
		ip2currency_install();

	if(isset($_REQUEST['submit'])) {
		if($_REQUEST['submit'] == __('Add Record', 'ip2currency')) {
			extract($_REQUEST);
			$msg = ip2currency_addrecord($product_id,$countrycode, $price1, $price2, $price3, $price4, $price5);
		}
		else if($_REQUEST['submit'] == __('Save changes', 'ip2currency')) {
			extract($_REQUEST);
			$msg = ip2currency_editrecord($id, $product_id, $countrycode, $price1, $price2, $price3, $price4, $price5);
		}
	}
	else if(isset($_REQUEST['action'])) {
		if($_REQUEST['action'] == 'editip2crecord') {
			$display .= "<div class=\"wrap\">\n<h2>Country specific prices &raquo; ".__('Edit record', 'ip2currency')."</h2>";
			$display .=  ip2currency_editform($_REQUEST['id']);
			$display .= "</div>";
			echo $display;
			return;
		}
		else if($_REQUEST['action'] == 'delip2crecord') {
			$msg = ip2currency_deleterecord($_REQUEST['id']);
		}
	}

	$display .= "<div class=\"wrap\">";

	if($msg)
		$display .= "<div id=\"message\" class=\"updated fade\"><p>{$msg}</p></div>";

	$display .= "<h2>Product Country Price Matrix <a href=\"#addnew\" class=\"add-new-h2\">".__('Add new record', 'ip2currency')."</a></h2>";

	$num_ip2crecords = ip2currency_record_count();

	if(!$num_ip2crecords) {
		$display .= "<p>".__('No price records in the database', 'ip2currency')."</p>";

		$display .= "</div>";

		$display .= "<div id=\"addnew\" class=\"wrap\">\n<h2>".__('Add new record', 'ip2currency')."</h2>";
		$display .= ip2currency_editform();
		$display .= "</div>";

		echo $display;
		return;
	}

	global $wpdb;
	global $ip2c_records_table_name_suffix;

	$sql = "SELECT id, product_id, countrycode, price1, price2, price3, price4, price5
		FROM " . $wpdb->prefix . $ip2c_records_table_name_suffix;

	$option_selected = array (
		'id' => '',
		'product_id' => '',
		'countrycode' => '',
		'price1' => '',
		'price2' => '',
		'price3' => '',
		'price4' => '',
		'price5' => '',
		'time_added' => '',
		'time_updated' => '',
		'ASC' => '',
		'DESC' => '',
	);
	if(isset($_REQUEST['orderby'])) {
		$sql .= " ORDER BY " . $_REQUEST['orderby'] . " " . $_REQUEST['order'];
		$option_selected[$_REQUEST['orderby']] = " selected=\"selected\"";
		$option_selected[$_REQUEST['order']] = " selected=\"selected\"";
	}
	else {
		$sql .= " ORDER BY id ASC";
		$option_selected['id'] = " selected=\"selected\"";
		$option_selected['ASC'] = " selected=\"selected\"";
	}

	if(isset($_REQUEST['paged']) && $_REQUEST['paged'] && is_numeric($_REQUEST['paged']))
		$paged = $_REQUEST['paged'];
	else
		$paged = 1;

	$limit_per_page = 20;
	$total_pages = ceil($num_ip2crecords / $limit_per_page);

	if($paged > $total_pages) $paged = $total_pages;

	$admin_url = get_bloginfo('wpurl'). "/wp-admin/options-general.php?page=ip2currency";
	if(isset($_REQUEST['orderby']))
		$admin_url .= "&orderby=".$_REQUEST['orderby']."&order=".$_REQUEST['order'];

	$page_nav = ip2currency_pagenav($total_pages, $paged, 2, 'paged', $admin_url);

	$start = ($paged - 1) * $limit_per_page;

	$sql .= " LIMIT {$start}, {$limit_per_page}";

	// Get all the records from the database
	$ip2crecords = $wpdb->get_results($sql);


	global $ip2c_country_code_currency_map;
	global $ip2c_product_names;

	foreach($ip2crecords as $ip2c_record_data) {
		if($alternate) $alternate = "";
		else $alternate = " class=\"alternate\"";
		$ip2crecords_list .= "<tr{$alternate}>";


		$ip2crecords_list .= "<td>";
		$ip2crecords_list .= wptexturize(nl2br(make_clickable( $ip2c_product_names[$ip2c_record_data->product_id])));
    	$ip2crecords_list .= "<div class=\"row-actions\"><span class=\"edit\"><a href=\"{$admin_url}&action=editip2crecord&amp;id=".$ip2c_record_data->id."\" class=\"edit\">".__('Edit', 'ip2currency')."</a></span> | <span class=\"trash\"><a href=\"{$admin_url}&action=delip2crecord&amp;id=".$ip2c_record_data->id."\" onclick=\"return confirm( '".__('Are you sure you want to delete this record?', 'ip2currency')."');\" class=\"delete\">".__('Delete', 'ip2currency')."</a></span></div>";

		$ip2crecords_list .= "</td>";

		$ip2crecords_list .= "<td>";

		$ip2crecords_list .= wptexturize(nl2br(make_clickable( $ip2c_country_code_currency_map[$ip2c_record_data->countrycode]['CountryName'])));

		$ip2crecords_list .= "</td>";
		if ($ip2c_record_data->price1)
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price1) . "  ". $ip2c_country_code_currency_map[$ip2c_record_data->countrycode]['CurrencyCode'] ." </td>";
		else
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price1) ."</td>";

		if ($ip2c_record_data->price2)
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price2) . "  ". $ip2c_country_code_currency_map[$ip2c_record_data->countrycode]['CurrencyCode'] ." </td>";
		else
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price2) ."</td>";

		if ($ip2c_record_data->price3)
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price3) . "  ". $ip2c_country_code_currency_map[$ip2c_record_data->countrycode]['CurrencyCode'] ." </td>";
		else
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price3) ."</td>";

		if ($ip2c_record_data->price4)
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price4) . "  ". $ip2c_country_code_currency_map[$ip2c_record_data->countrycode]['CurrencyCode'] ." </td>";
		else
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price4) ."</td>";

		if ($ip2c_record_data->price5)
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price5) . "  ". $ip2c_country_code_currency_map[$ip2c_record_data->countrycode]['CurrencyCode'] ." </td>";
		else
			$ip2crecords_list .= "<td>" . make_clickable($ip2c_record_data->price5) ."</td>";

		$ip2crecords_list .= "</tr>";
	}

	if($ip2crecords_list) {
		$ip2records_count = ip2currency_record_count();

		$display .= "<form id=\"ip2currencywidget\" method=\"post\" action=\"".get_bloginfo('wpurl')."/wp-admin/options-general.php?page=ip2currency\">";
		$display .= "<div class=\"tablenav\">";

		$display .= '<div class="tablenav-pages"><span class="displaying-num">'.sprintf(_n('%d records', '%d records', $ip2records_count, 'ip2currency'), $ip2records_count).'</span><span class="pagination-links">'. $page_nav. "</span></div>";
		$display .= "<div class=\"clear\"></div>";
		$display .= "</div>";

		$display .= "<table class=\"widefat\">";
		$display .= "<thead><tr>
			<!--th class=\"check-column\"><input type=\"checkbox\" onclick=\"ip2currencywidget_checkAll(document.getElementById('ip2currencywidget'));\" /></th>
			<th>ID</th--><th>".__('Product', 'ip2currency')."</th>
			<th>".__('Country', 'ip2currency')."</th>
			<th>".__('Price 1', 'ip2currency')."</th>
			<th>".__('Price 2', 'ip2currency')."</th>
			<th>".__('Price 3', 'ip2currency')."</th>
			<th>".__('Price 4', 'ip2currency')."</th>
			<th>".__('Price 5', 'ip2currency')."</th>
		</tr></thead>";
		$display .= "<tbody id=\"the-list\">{$ip2crecords_list}</tbody>";
		$display .= "</table>";

		$display .= "<div class=\"tablenav\">";
		$display .= '<div class="tablenav-pages"><span class="displaying-num">'.sprintf(_n('%d records', '%d records', $ip2records_count, 'ip2currency'), $ip2records_count).'</span><span class="pagination-links">'. $page_nav. "</span></div>";
		$display .= "<div class=\"clear\"></div>";
		$display .= "</div>";

		$display .= "</form>";
		$display .= "<br style=\"clear:both;\" />";

	}
	else
		$display .= "<p>".__('No price records in the database', 'ip2currency')."</p>";

	$display .= "</div>";

	$display .= "<div id=\"addnew\" class=\"wrap\">\n<h2>".__('Add new record', 'ip2currency')."</h2>";
	$display .= ip2currency_editform();
	$display .= "</div>";


	echo $display;
}



function ip2currency_editform($id = 0)
{
	$submit_value = __('Add Record', 'ip2currency-add');
	$form_name = "addrecord";

	$action_url = get_bloginfo('wpurl')."/wp-admin/options-general.php?page=ip2currency#addnew";
	$product_id = $countrycode = $price1 = $price2 = $price3 = $price4 = $price5 = $hidden_input = $back = "";

	global $ip2c_country_code_currency_map;
	$select_options = '';
	$select_product_options = '';

	global $ip2c_product_names;

	if($id) {
		$form_name = "editip2crecord";
		$ip2c_record_data = ip2currency_get_ip2c_data($id);
		foreach($ip2c_record_data as $key => $value)
			$ip2c_record_data[$key] = $ip2c_record_data[$key];
		extract($ip2c_record_data);
		$product_id = htmlspecialchars($product_id);
		$countrycode = htmlspecialchars($countrycode);
		$price1 = htmlspecialchars($price1);
		$price2 = htmlspecialchars($price2);
		$price3 = htmlspecialchars($price3);
		$price4 = htmlspecialchars($price4);
		$price5 = htmlspecialchars($price5);

		$hidden_input = "<input type=\"hidden\" name=\"id\" value=\"{$id}\" />";

		$submit_value = __('Save changes', 'ip2currency');
		$back = "<input type=\"submit\" name=\"submit\" value=\"".__('Back', 'ip2currency')."\" />&nbsp;";

		$action_url = get_bloginfo('wpurl')."/wp-admin/options-general.php?page=ip2currency";

		$select_product_options .= '<option value="XX">' . __('Select', 'ip2currency'). '</option>';
		for ($i=0; $i < count($ip2c_product_names);  ++$i)
		{
			if ($product_id==$i)
			{
				$select_product_options .= '<option value="'. $i .'" selected="selected">' . __($ip2c_product_names[$i], 'ip2currency'). '</option>';
			}
			else
			{
				$select_product_options .= '<option value="'. $i .'" >' . __($ip2c_product_names[$i], 'ip2currency'). '</option>';
			}
		}


		$select_options .= '<option value="XX">' . __('Select', 'ip2currency'). '</option>';
		foreach ($ip2c_country_code_currency_map as $country_key =>$country_data)
		{
			if ($countrycode==$country_key)
			{
				$select_options .= '<option value="'. $country_key .'" selected="selected">' . __($country_data['CountryName'], 'ip2currency'). '</option>';
			}
			else
			{
				$select_options .= '<option value="'. $country_key .'" >' . __($country_data['CountryName'], 'ip2currency'). '</option>';
			}
		}
	}
	else
	{
		$select_product_options .= '<option value="XX" selected="selected">' . __('Select', 'ip2currency'). '</option>';
		for ($i=0; $i < count($ip2c_product_names);  ++$i)
		{
			$select_product_options .= '<option value="'. $i .'" >' . __($ip2c_product_names[$i], 'ip2currency'). '</option>';
		}

		$select_options .= '<option value="XX" selected="selected">' . __('Select', 'ip2currency'). '</option>';
		foreach ($ip2c_country_code_currency_map as $country_key =>$country_data)
		{
			$select_options .= '<option value="'. $country_key .'" >' . __($country_data['CountryName'], 'ip2currency'). '</option>';
		}
	}

	$product_id_label = __('Product', 'ip2currency');
	$country_label = __('Country', 'ip2currency');
	$price1_label = __('Price 1', 'ip2currency');
	$price2_label = __('Price 2', 'ip2currency');
	$price3_label = __('Price 3', 'ip2currency');
	$price4_label = __('Price 4', 'ip2currency');
	$price5_label = __('Price 5', 'ip2currency');


	$optional_text = __('optional', 'ip2currency');
	$url_text = __('email address or website URL', 'ip2currency');
	$comma_separated_text = __('comma separated', 'ip2currency');

	$display =<<< EDITFORM
<form name="{$form_name}" method="post" action="{$action_url}" onsubmit="return validateFormOnSubmit(this)">
	{$hidden_input}
	<table class="form-table" cellpadding="5" cellspacing="2" width="100%">
		<tbody>

		<tr>
			<td>
			<label for="ip2currencywidget_product_id">{$product_id_label}</label></br>

			<select id="ip2currencywidget_product_id" name="product_id">
				{$select_product_options}
			</select>
			</td>

			<td>
			<label for="ip2currencywidget_countrycode">{$country_label}</label></br>

			<select id="ip2currencywidget_countrycode" name="countrycode">
				{$select_options}
			</select>
			</td>

			<td>
			<label for="ip2currencywidget_price1">{$price1_label}</label></br>
			<input type="text" id="ip2currencywidget_price1" name="price1" size="10" value="{$price1}" />
			</td>

			<td>
			<label for="ip2currencywidget_price2">{$price2_label}</label></br>
			<input type="text" id="ip2currencywidget_price2" name="price2" size="10" value="{$price2}" />
			</td>

			<td>
			<label for="ip2currencywidget_price3">{$price3_label}</label></br>
			<input type="text" id="ip2currencywidget_price3" name="price3" size="10" value="{$price3}" />
			</td>

			<td>
			<label for="ip2currencywidget_price4">{$price4_label}</label></br>
			<input type="text" id="ip2currencywidget_price4" name="price4" size="10" value="{$price4}" />
			</td>

			<td>
			<label for="ip2currencywidget_price5">{$price5_label}</label></br>
			<input type="text" id="ip2currencywidget_price5" name="price5" size="10" value="{$price5}" />
			</td>
		</tr>

		</tbody>
	</table>
	<p class="submit"><input name="submit" value="{$submit_value}" type="submit" class="button button-primary" />{$back}</p>
</form>
EDITFORM;
	return $display;
}


function ip2currency_admin_footer()
{
	?>
<script type="text/javascript">

function isNumber(fld) {
   var error="";
   var inp = fld.value;
   var name = fld.name;

  if (!(!isNaN(parseFloat(inp)) && isFinite(inp)))
  {
  	error=name+ " : Invalid, please enter a numeric value.\n";
  }

  return error;
}

function isValidCountry(fld) {
   var error="";
   var inp = fld.value;
   var name = fld.name;

  if (inp=="XX")
  {
  	error=name+ " : Invalid, please select an option.\n";
  }

  return error;
}

function validateFormOnSubmit(theForm) {
var reason = "";

  reason += isValidCountry(theForm.product_id);
  reason += isValidCountry(theForm.countrycode);
  reason += isNumber(theForm.price1);
  reason += isNumber(theForm.price2);
  reason += isNumber(theForm.price3);
  reason += isNumber(theForm.price4);
  reason += isNumber(theForm.price5);

  if (reason != "") {
    alert("Some fields need correction:\n" + reason);
    return false;
  }

  return true;
}

</script>

	<?php
}

add_action('admin_footer', 'ip2currency_admin_footer');


?>
