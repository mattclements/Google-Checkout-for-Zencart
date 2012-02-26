<?php
/*
 * Created on 28/03/2007
 *
 * Coded by: Ropu
 * Globant - Buenos Aires, Argentina  - z-tests_atx
 * Version 0.1
 */

/*
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* ** tool to test WS response time
 */

// NOTE: This script MUST be placed in googlecheckout/shipping_metrics/ directory

// Set the shippers code you want to test
$shippers = array();
$shippers[] = "fedexexpress";
$shippers[] = "fedexground";

  error_reporting(E_ALL);
  chdir('./../..');
  $curr_dir = getcwd();
   
  include_once('includes/application_top.php');
  // serialized cart, to avoid needing one in session
  $cart = unserialize('O:12:"shoppingcart":9:{s:9:"observers";a:0:{}s:8:"contents";a:1:{i:19;a:1:{s:3:"qty";d:1;}}s:5:"total";d:49.99000000000000198951966012828052043914794921875;s:6:"weight";d:7;s:6:"cartID";s:5:"26519";s:12:"content_type";b:0;s:18:"free_shipping_item";i:0;s:20:"free_shipping_weight";i:0;s:19:"free_shipping_price";i:0;}');
//  print_r($cart);
//  
//  $cart->total = $_POST['price'];
//  $cart->weight = $_POST['weight'];
//  $cart->contents[19]['qty'] = $_POST['cant'];

  $_SESSION['cart'] = $cart;

//  print_r($cart);
//die;
  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;
  
  $cartID = $cart->cartID;
  $total_weight = $cart->show_weight();
  $total_count = $cart->count_contents();
  
  // Get all the enabled shipping methods.
  require(DIR_WS_CLASSES .'shipping.php');
  
  // Required for some shipping methods (ie. USPS).
  require_once('includes/classes/http_client.php');
$cartID = $cart->cartID;

$total_weight = $cart->show_weight();
$total_count = $cart->count_contents();

list($start_m, $start_s) = explode(' ', microtime());
$start = $start_m + $start_s;

    // Set up the order address.
// Domestic
$country = 'US';
$city = 'Miami';
$region = 'FL';
$postal_code = '33102';
  
//  $row = tep_db_fetch_array(tep_db_query("select * from ". TABLE_COUNTRIES ." where countries_iso_code_2 = '". $country ."'"));
  $countr_query = $db->Execute("select * from " . TABLE_COUNTRIES . " where countries_iso_code_2 = '" . $country ."'");
  $row = $countr_query->fields;

  $order->delivery['country'] = array('id' => $row['countries_id'], 
                                      'title' => $row['countries_name'], 
                                      'iso_code_2' => $country, 
                                      'iso_code_3' => $row['countries_iso_code_3']);
  $order->delivery['country_id'] = $row['countries_id'];
  $order->delivery['format_id'] = $row['address_format_id'];
  
  $zone_query = $db->Execute("select * from " . TABLE_ZONES . " where zone_code = '" . $region ."'");
  $row = $zone_query->fields;

  $order->delivery['zone_id'] = $row['zone_id'];
  $order->delivery['state'] = $row['zone_name'];
  
  $order->delivery['city'] = $city;
  $order->delivery['postcode'] = $postal_code;
  $shipping_modules = new shipping();
//  print_r($shipping_modules);
//  $quotes =  $shipping_modules->quote();

foreach($shippers as $shipper) {	
	list($start_m, $start_s) = explode(' ', microtime());
	$start = $start_m + $start_s;
	$quotes =  $shipping_modules->quote('', $shipper);
	list($end_m, $end_s) = explode(' ', microtime());
	$end = $end_m + $end_s;
 	echo $shipper." took ".(number_format($end-$start, 5))." Secs\n";
}

list($start_m, $start_s) = explode(' ', microtime());
$start = $start_m + $start_s;
$quotes =  $shipping_modules->quote();
list($end_m, $end_s) = explode(' ', microtime());
$end = $end_m + $end_s;
echo "All quotes took ".(number_format($end-$start, 5))." Secs\n";

?>