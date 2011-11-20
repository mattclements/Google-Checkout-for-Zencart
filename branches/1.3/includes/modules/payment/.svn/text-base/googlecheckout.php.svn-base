<?php
/*
  Copyright (C) 2006 Google Inc.

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


/* GOOGLE CHECKOUT
 * Class provided in modules dir to add googlecheckout as a payment option
 * Member variables refer to currently set paramter values from the database
 */

class googlecheckout extends base {
  var $code, $title, $description, $merchantid, $merchantkey, $mode,
      $enabled, $shipping_support, $variant;
  var $schema_url, $base_url, $checkout_url, $checkout_diagnose_url, 
      $request_url, $request_diagnose_url;
  var $table_name = "google_checkout", $table_order = "google_orders";

// class constructor
  function googlecheckout() {
    global $order;

    require_once(DIR_FS_CATALOG.'includes/languages/'. $_SESSION['language'] .
    '/modules/payment/googlecheckout.php');

    $this->code = 'googlecheckout';
    $this->title = MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_DESCRIPTION;
    $this->sort_order = MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER;
    $this->mode= MODULE_PAYMENT_GOOGLECHECKOUT_STATUS;
    $this->merchantid = trim(MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID);
    $this->merchantkey = trim(MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY);
    $this->mode = MODULE_PAYMENT_GOOGLECHECKOUT_MODE;
    $this->enabled = ((MODULE_PAYMENT_GOOGLECHECKOUT_STATUS == 'True') ? true : false);

    $this->shipping_support = array("flat", "item", "table",  'freeoptions', 'freeshipper', 'perweightunit', 'storepickup', 'itemnational', 'iteminternational');
     
    $this->shipping_display = array(GOOGLECHECKOUT_FLAT_RATE_SHIPPING, GOOGLECHECKOUT_ITEM_RATE_SHIPPING, GOOGLECHECKOUT_TABLE_RATE_SHIPPING, 'freeoptions', 'freeshipper', 'perweightunit', 'storepickup');
    $this->ship_flat_ui = "Standard flat-rate shipping";

    $this->schema_url = "http://checkout.google.com/schema/2";
    $this->base_url = $this->mode."cws/v2/Merchant/" . $this->merchantid;
    $this->checkout_url =  $this->base_url . "/checkout";
    $this->checkout_diagnose_url = $this->base_url . "/checkout/diagnose";
    $this->request_url = $this->base_url . "/request";
    $this->request_diagnose_url = $this->base_url . "/request/diagnose";
    $this->variant = 'text';

	// this are all the available methods for each shipping provider, 
	// see that you must set flat methods too!
	// CONSTRAINT: Method's names MUST be UNIQUE
	// Script to create new shipping methods
	// http://demo.globant.com/~brovagnati/tools -> Shipping Method Generator
	$this->mc_shipping_methods = array(
                        'usps' => array(
                                    'domestic_types' =>
                                      array(
                                          'Express' => 'Express Mail',
                                          'First Class' => 'First-Class Mail',
                                          'Priority' => 'Priority Mail',
                                          'Parcel' => 'Parcel Post',
                                          'BPM' => 'Bound Printed Material',
                                          'Library' => 'Library'
                                           ),

                                    'international_types' =>
                                      array(
                                          'GXG Document' => 'Global Express Guaranteed Document Service',
                                          'GXG Non-Document' => 'Global Express Guaranteed Non-Document Service',
                                          'Express' => 'Global Express Mail (EMS)',
                                          'Priority Lg' => 'Global Priority Mail - Flat-rate Envelope (large)',
                                          'Priority Sm' => 'Global Priority Mail - Flat-rate Envelope (small)',
                                          'Priority Var' => 'Global Priority Mail - Variable Weight Envelope (single)',
                                          'Airmail Letter' => 'Airmail Letter Post',
                                          'Airmail Parcel' => 'Airmail Parcel Post',
                                          'Surface Letter' => 'Economy (Surface) Letter Post',
                                          'Surface Post' => 'Economy (Surface) Parcel Post'
                                           ),
                                        ),
                        'fedex1' => array(
                                    'domestic_types' =>
                                      array(
                                          '01' => 'Priority (by 10:30AM, later for rural)',
                                          '03' => '2 Day Air',
                                          '05' => 'Standard Overnight (by 3PM, later for rural)',
                                          '06' => 'First Overnight',
                                          '20' => 'Express Saver (3 Day)',
                                          '90' => 'Home Delivery',
                                          '92' => 'Ground Service'
                                           ),

                                    'international_types' =>
                                      array(
                                          '01' => 'International Priority (1-3 Days)',
                                          '03' => 'International Economy (4-5 Days)',
                                          '06' => 'International First',
                                          '90' => 'International Home Delivery',
                                          '92' => 'International Ground Service'
                                           ),
                                        ),
                        'ups' => array(
                                    'domestic_types' =>
                                      array(
                                          '1DM' => 'Next Day Air Early AM',
                                          '1DML' => 'Next Day Air Early AM Letter',
                                          '1DA' => 'Next Day Air',
                                          '1DAL' => 'Next Day Air Letter',
                                          '1DAPI' => 'Next Day Air Intra (Puerto Rico)',
                                          '1DP' => 'Next Day Air Saver',
                                          '1DPL' => 'Next Day Air Saver Letter',
                                          '2DM' => '2nd Day Air AM',
                                          '2DML' => '2nd Day Air AM Letter',
                                          '2DA' => '2nd Day Air',
                                          '2DAL' => '2nd Day Air Letter',
                                          '3DS' => '3 Day Select',
                                          'GND' => 'Ground',
                                          'GNDCOM' => 'Ground Commercial',
                                          'GNDRES' => 'Ground Residential',
                                          'STD' => 'Canada Standard',
                                          'XPR' => 'Worldwide Express',
                                          'XPRL' => 'worldwide Express Letter',
                                          'XDM' => 'Worldwide Express Plus',
                                          'XDML' => 'Worldwide Express Plus Letter',
                                          'XPD' => 'Worldwide Expedited'
                                           ),

                                    'international_types' =>
                                      array(

                                           ),
                                        ),
                        'zones' => array(
                                    'domestic_types' =>
                                      array(
                                          'zones' => 'Zones Rates'
                                           ),

                                    'international_types' =>
                                      array(
                                          'zones' => 'Zones Rates intl'
                                           ),
                                        ),
                        'fedexexpress' => array(
                                    'domestic_types' =>
                                      array(
                                          '01' => 'FedEx Priority Overnight',
                                          '03' => 'FedEx 2Day',
                                          '05' => 'FedEx Standard Overnight',
                                          '06' => 'FedEx First Overnight',
                                          '20' => 'FedEx Express Saver'
                                           ),

                                    'international_types' =>
                                      array(

                                           ),
                                        ),
                        'fedexground' => array(
                                    'domestic_types' =>
                                      array(
                                          '92' => 'FedEx Ground Service'
                                           ),

                                    'international_types' =>
                                      array(

                                           ),
                                        ),
                        'freeoptions' => array(
                                    'domestic_types' =>
                                      array(
                                          'freeoptions' => 'Free Options'
                                           ),

                                    'international_types' =>
                                      array(
                                          'freeoptions' => 'Free Options intl'
                                           ),
                                        ),
                        'freeshipper' => array(
                                    'domestic_types' =>
                                      array(
                                          'freeshipper' => 'Free Shipper'
                                           ),

                                    'international_types' =>
                                      array(
                                          'freeshipper' => 'Free Shipper intl'
                                           ),
                                        ),
                        'perweightunit' => array(
                                    'domestic_types' =>
                                      array(
                                          'perweightunit' => 'Perweight Unit'
                                           ),

                                    'international_types' =>
                                      array(
                                          'perweightunit' => 'Perweight Unit intl'
                                           ),
                                        ),
                        'storepickup' => array(
                                    'domestic_types' =>
                                      array(
                                          'storepickup' => 'Store Pickup'
                                           ),

                                    'international_types' =>
                                      array(
                                          'storepickup' => 'Store Pickup intl'
                                           ),
                                        ),
                        'flat' => array(
                                    'domestic_types' =>
                                      array(
                                          'flat' => 'Flat Rate Per Order'
                                           ),

                                    'international_types' =>
                                      array(
                                          'flat' => 'Flat Rate Per Order intl'
                                           ),
                                        ),
                        'item' => array(
                                    'domestic_types' =>
                                      array(
                                          'item' => 'Flat Rate Per Item'
                                           ),

                                    'international_types' =>
                                      array(
                                          'item' => 'Flat Rate Per Item intl'
                                           ),
                                        ),
                        'table' => array(
                                    'domestic_types' =>
                                      array(
                                          'table' => 'Vary by Weight/Price'
                                           ),

                                    'international_types' =>
                                      array(
                                          'table' => 'Vary by Weight/Price intl'
                                           ),
                                        ),
                        'itemnational' => array(
                                    'domestic_types' =>
                                      array(
                                          'itemnational' => 'Item National',

                                           ),

                                    'international_types' =>
                                      array(

                                           ),
                                        ),
                        'iteminternational' => array(
                                    'domestic_types' =>
                                      array(

                                           ),

                                    'international_types' =>
                                      array(
                                          'iteminternational' => 'Item International',

                                           ),
                                        ),                                        
                                  );

	$this->mc_shipping_methods_names = array(	
                                         'usps' => 'USPS',
                                         'fedex1' => 'FedEx',
                                         'ups' => 'UPS',
                                         'zones' => 'Zones',
                                         'fedexexpress' => 'Fedex Express',
                                         'fedexground' => 'Fedex Ground',
                                         'freeoptions' => 'Free Options',
                                         'freeshipper' => 'Free Shipper',
                                         'perweightunit' => 'Perweight Unit',
                                         'storepickup' => 'Store Pickup',
                                         'flat' => 'Flat Rate',
                                         'item' => 'Item',
                                         'table' => 'Table',
                                         'itemnational' => 'Per Item National',
                                         'iteminternational' => 'Per Item International',
                                        );	

	$this->hash = NULL;

    if ((int)MODULE_PAYMENT_GOOGLECHECKOUT_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_GOOGLECHECKOUT_ORDER_STATUS_ID;
    }
  }
  function getMethods(){
  	if($this->hash == NULL) {
		$rta = array();
  		$this->_gethash($this->mc_shipping_methods, $rta);
  		$this->hash = $rta;
  	}
	return $this->hash;

  }

  function _gethash($arr, &$rta, $path =array()) {
	if(is_array($arr)){
		foreach($arr as $key => $val){
			$this->_gethash($arr[$key], $rta, array_merge(array($key), $path));
		}
	} else {
		$rta[$arr] = $path;
	}
  }

//Function used from Google sample code to sign the cart contents with the merchant key 		
  function CalcHmacSha1($data) {
    $key = $this->merchantkey;
    $blocksize = 64;
    $hashfunc = 'sha1';
    if (strlen($key) > $blocksize) {
      $key = pack('H*', $hashfunc($key));
    }
    $key = str_pad($key, $blocksize, chr(0x00));
    $ipad = str_repeat(chr(0x36), $blocksize);
    $opad = str_repeat(chr(0x5c), $blocksize);
    $hmac = pack(
                    'H*', $hashfunc(
                            ($key^$opad).pack(
                                    'H*', $hashfunc(
                                            ($key^$ipad).$data
                                    )
                            )
                    )
                );
    return $hmac; 
  }

//Decides the shipping name to be used
// May not call this if the same name is to be used
// Useful if some one wants to map to Google checkout shoppign types(flat, pickup or merchant calculate)
//  function getShippingType($shipping_option) {
//    switch($shipping_option) {
//      case GOOGLECHECKOUT_FLAT_RATE_SHIPPING: return $this->ship_flat_ui."- Flat Rate"; 
//      case GOOGLECHECKOUT_ITEM_RATE_SHIPPING: return $this->ship_flat_ui."- Item Rate";
//      case GOOGLECHECKOUT_TABLE_RATE_SHIPPING: return $this->ship_flat_ui."- Table Rate";
//      default: return "";
//    }
//  }

// Function used to compute the actual price for shipping depending upon the shipping type
// selected
//  function getShippingPrice($ship_option, $cart, $actual_price, $handling=0, $table_mode="") {
//    switch($ship_option) {
//      case GOOGLECHECKOUT_FLAT_RATE_SHIPPING: {
//        return $actual_price;	
//      }
//      case GOOGLECHECKOUT_ITEM_RATE_SHIPPING: {
//        return ($actual_price * $cart->count_contents()) + $handling ;
//      }
//      case GOOGLECHECKOUT_TABLE_RATE_SHIPPING: {
////Check the mode to be used for pricing the shipping
//        if($table_mode == "price")
//          $table_size = $cart->show_total();
//        else if ($table_mode == "weight")
//          $table_size = $cart->show_weight();
//
//// Parse the price (value1:price1,value2:price2)
//        $tok = strtok($actual_price, ",");
//        $tab_data = array();
//        while($tok != FALSE) {
//          $tab_data[] = $tok;
//          $tok = strtok(",");
//        }  
//        $initial_val=0;	  
//        foreach($tab_data as $curr) {
//          $final_val = strtok($curr, ":");
//          $pricing = strtok(":"); 
//          if($table_size >= $initial_val && $table_size <= $final_val) {
//            $price = $pricing + $handling;
//            break;  
//          }
//          $initial_val = $final_val;
//        }
//        return $price;
//      }
//      default: return 0.0001;
//    }
//  }

// class methods
  function update_status() {
  }

  function javascript_validation() {
    return false;
  }

  function selection() {
    return array('id' => $this->code,'module' => $this->title, 'noradio' => true);
  }

  function pre_confirmation_check() {
    return false;
  }

  function confirmation() {
    return false;
  }

  function process_button() {
  }

  function before_process() {
    return false;
  }

  function after_process() {
    return false;
  }

  function output_error() {
    return false;
  }

  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_GOOGLECHECKOUT_STATUS'");
      $this->_check =  $check_query->RecordCount();
    }
    return $this->_check;
  }

  function install() {
    global $db;
    $language = $_SESSION['language'];
    require_once(DIR_FS_CATALOG.'includes/languages/'. $language . '/modules/payment/googlecheckout.php');
    $shipping_list .= "array(";
    foreach($this->shipping_display as $ship) {
      $shipping_list .= "\'".$ship."\',";
    }
    $shipping_list = substr($shipping_list,0,strlen($shipping_list)-1);
    $shipping_list .= ")";

    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable GoogleCheckout Module', 'MODULE_PAYMENT_GOOGLECHECKOUT_STATUS', 'True', 'Accepts payments through Google Checkout on your site', '6', '3', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('.htaccess Basic Authentication Mode', 'MODULE_PAYMENT_GOOGLECHECKOUT_CGI', 'False', 'Your site Site in installed with PHP over CGI? <br /> This configuration will <b>disable</b> PHP Basic Authentication that is NOT compatible with CGI used in the responsehandler.php to validate Google Checkout messages.<br />If setted True you MUST configure your .htaccess files <a href=\"htaccess.php\" target=\"_OUT\">here</a>.', '6', '4', 'zen_cfg_select_option(array(\'False\', \'True\'),',now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID', '', 'Your merchant ID is listed on the \"Integration\" page under the \"Settings\" tab', '6', '1', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant Key', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY', '', 'Your merchant key is also listed on the \"Integration\" page under the \"Settings\" tab', '6', '2', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Select Mode of Operation', 'MODULE_PAYMENT_GOOGLECHECKOUT_MODE', 'https://sandbox.google.com/checkout/', 'Select either the Developer\'s Sandbox or live Production environment', '6', '3', 'zen_cfg_select_option(array(\'https://sandbox.google.com/checkout/\', \'https://checkout.google.com/\'),',now())");
	  $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Disable Google Checkout for Virtual Goods?', 'MODULE_PAYMENT_GOOGLECHECKOUT_VIRTUAL_GOODS', 'False', 'If this configuration is enabled and there is any virtual good in the cart the Google Checkout button will be shown disabled.', '6', '4', 'zen_cfg_select_option(array(\'True\', \'False\'),',now())");	
//		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('MultiSocket Shipping Quotes Retrieval', 'MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET', 'False', 'This configuration will enable a multisocket feature to parallelize Shipping Providers quotes. This should reduce the time this call take and avoid GC Merchant Calculation TimeOut. <a href=\"multisock.html\" target=\"_OUT\">More Info</a>.(Alfa Feature)', '6', '4', 'zen_cfg_select_option(array(\'True\', \'False\'),',now())");	
	  $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Allow US PO BOX shipping?', 'MODULE_PAYMENT_GOOGLECHECKOUT_USPOBOX', 'True', 'Allow sending items to US PO Boxes?', '6', '4', 'zen_cfg_select_option(array(\'True\', \'False\'),',now())");	
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Select Merchant Calculation Mode of Operation', 'MODULE_PAYMENT_GOOGLECHECKOUT_MC_MODE', 'https', 'Merchant calculation URL for Sandbox environment. (Checkout production environemnt always requires HTTPS.)', '6', '4', 'zen_cfg_select_option(array(\'http\', \'https\'),',now())");
	  $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Rounding Policy Mode', 'MODULE_PAYMENT_GOOGLECHECKOUT_TAXMODE', 'HALF_EVEN', 'This configuration specifies the methodology that will be used to round values to two decimal places. <a href=\"http://code.google.com/apis/checkout/developer/Google_Checkout_Rounding_Policy.html\">More info</a>', '6', '4', 'zen_cfg_select_option(array(\'UP\',\'DOWN\',\'CEILING\',\'HALF_UP\',\'HALF_DOWN\', \'HALF_EVEN\'),',now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Rounding Policy Rule', 'MODULE_PAYMENT_GOOGLECHECKOUT_TAXRULE', 'PER_LINE', 'This configuration specifies when rounding rules should be applied to monetary values while Google Checkout is computing an order total.', '6', '4', 'zen_cfg_select_option(array(\'PER_LINE\',\'TOTAL\'),',now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Google Analytics Id', 'MODULE_PAYMENT_GOOGLECHECKOUT_ANALYTICS', 'NONE', 'Do you want to integrate the module with Google Analytics? Add your GA Id (UA-XXXXXX-X), NONE to disable. <br/> More info <a href=\'http://code.google.com/apis/checkout/developer/checkout_analytics_integration.html\'>here</a>', '6', '1', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Default Values for Real Time Shipping Rates', 'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING', '', 'Default values for real time rates in case the webservice call fails.', '6', '0',\"zen_cfg_select_shipping($shipping_list, \",now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    $db->Execute("create table if not exists " . $this->table_name . " (customers_id int(11), buyer_id bigint(20) )");
    $db->Execute("create table if not exists " . $this->table_order ." (orders_id int(11), google_order_number bigint(20), order_amount decimal(15,4) )");

  }

// If it is requried to delete these tables on removing the module, the two lines below
// could be uncommented
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    //$db->Execute("drop table " . $this->table_name);
    //$db->Execute("drop table " . $this->table_order);
  }

  function keys() {
    return array('MODULE_PAYMENT_GOOGLECHECKOUT_STATUS',
    					   'MODULE_PAYMENT_GOOGLECHECKOUT_CGI', 
					       'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_MODE',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_MC_MODE',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_VIRTUAL_GOODS',
//					       'MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_USPOBOX',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_TAXMODE',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_TAXRULE',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_ANALYTICS',
								 'MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER');
  }
}
// ** END GOOGLE CHECKOUT **
?>
