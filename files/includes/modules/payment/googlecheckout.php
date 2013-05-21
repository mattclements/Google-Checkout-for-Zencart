<?php
/*
  Copyright (C) 2007 Google Inc.

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


/* **GOOGLE CHECKOUT ** v1.5.1
  @version $Id: googlecheckout.php 7492 2007-10-08 14:58:57Z ropu $
 * Class provided in modules dir to add googlecheckout as a payment option
 * Member variables refer to currently set paramter values from the database
 */
define('GOOGLECHECKOUT_FILES_VERSION', 'v1.5.1');
class googlecheckout extends base {
  var $code, $title, $description, $merchantid, $merchantkey, $mode,
      $enabled, $shipping_support, $variant;
  var $schema_url, $base_url, $checkout_url, $checkout_diagnose_url, 
      $request_url, $request_diagnose_url;
  var $table_name = "google_checkout", $table_order = "google_orders";
  var $ot_ignore;
  var $mc_shipping_methods, $mc_shipping_methods_names; 
  var $cc_shipping_methods, $cc_shipping_methods_names;
  var $gc_order_states;

// class constructor
  function googlecheckout() {
    global $order,$messageStack;

    require_once(DIR_FS_CATALOG.'includes/languages/'. $_SESSION['language'] .
    '/modules/payment/googlecheckout.php');
    require(DIR_FS_CATALOG .'/googlecheckout/shipping_methods.php');
    require(DIR_FS_CATALOG .'/googlecheckout/shipping_methods_ccs.php');

    $this->code = 'googlecheckout';
    $this->title = MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_DESCRIPTION;
    $this->sort_order = MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER;
    $this->mode= MODULE_PAYMENT_GOOGLECHECKOUT_STATUS;
    if(MODULE_PAYMENT_GOOGLECHECKOUT_MODE=='https://sandbox.google.com/checkout/'){
      $this->merchantid = trim(MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID_SNDBOX);
      $this->merchantkey = trim(MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY_SNDBOX);
    }else {
      $this->merchantid = trim(MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID);
      $this->merchantkey = trim(MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY);
    }
    $this->mode = MODULE_PAYMENT_GOOGLECHECKOUT_MODE;
    $this->enabled = ((MODULE_PAYMENT_GOOGLECHECKOUT_STATUS == 'True') ? true : false);
    $this->continue_url = MODULE_PAYMENT_GOOGLECHECKOUT_CONTINUE_URL;
    // These are the flat shipping methods, add any other that is not merchant calculated 
    $this->shipping_support = array("flat", "item", "table",  'freeoptions', 'freeshipper', 'perweightunit', 'storepickup', 'itemnational', 'iteminternational');
     
    $this->schema_url = "http://checkout.google.com/schema/2";
    $this->base_url = $this->mode."cws/v2/Merchant/" . $this->merchantid;
    $this->checkout_url =  $this->base_url . "/checkout";
    $this->checkout_diagnose_url = $this->base_url . "/checkout/diagnose";
    $this->request_url = $this->base_url . "/request";
    $this->request_diagnose_url = $this->base_url . "/request/diagnose";
    $this->variant = 'text';
 	  // These are all the available methods for each shipping provider, 
    // see that you must set flat methods too!}
    // CONSTRAINT: Method's names MUST be UNIQUE
	// Script to create new shipping methods
	// http://ur-site/googlecheckot/shipping_generator/
  // to manually edit, /googlecheckout/shipping_methods.php
    $this->mc_shipping_methods = $mc_shipping_methods;
    $this->mc_shipping_methods_names = $mc_shipping_methods_names;
//    // Carrier Calculated shipping methods
    $this->cc_shipping_methods = $cc_shipping_methods;
    $this->cc_shipping_methods_names = $cc_shipping_methods_names;
  
  	$this->ot_ignore = array( 'ot_subtotal',
                      'ot_shipping',
                      'ot_coupon',
                      'ot_tax',
                      'ot_gv',
                      'ot_total',
                    );
    $this->hash = NULL;
//    FMyI
//    Refund y processing son lo mismo
//    shipped y cancelled tambien
    $this->gc_order_states = array( '100' => GOOGLECHECKOUT_CUSTOM_ORDER_STATE_NEW,
                                    '101' => GOOGLECHECKOUT_CUSTOM_ORDER_STATE_PROCESSING,
                                    '103' => GOOGLECHECKOUT_CUSTOM_ORDER_STATE_DIGITAL_PROCESSED,
                                    '105' => GOOGLECHECKOUT_CUSTOM_ORDER_STATE_SHIPPED,
                                    '107' => GOOGLECHECKOUT_CUSTOM_ORDER_STATE_REFUNDED,
                                    '109' => GOOGLECHECKOUT_CUSTOM_ORDER_STATE_SHIPPED_REFUNDED,
                                    '111' => GOOGLECHECKOUT_CUSTOM_ORDER_STATE_CANCELED
                                    );


    if(defined('IS_ADMIN_FLAG') && MODULE_PAYMENT_GOOGLECHECKOUT_MODE=='https://sandbox.google.com/checkout/'){
      $messageStack->add_session(GOOGLECHECKOUT_STRING_WARN_USING_SANDBOX, 'warning');
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

// class methods
  function update_status() {
  }

  function javascript_validation() {
    return false;
  }

  function selection() {
    return array('id' => $this->code,'module' => $this->title, 'noradio' => false);
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
    // To avoid using GC in Regular Checkout 
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
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

    $shipping_list = 'array(\'not\')';
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Google Checkout Module Version', 'MODULE_PAYMENT_GOOGLECHECKOUT_VERSION', '".GOOGLECHECKOUT_FILES_VERSION."', 'Version of the installed Module', '6', '0', 'zen_cfg_select_option(array(\'".GOOGLECHECKOUT_FILES_VERSION."\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable GoogleCheckout Module', 'MODULE_PAYMENT_GOOGLECHECKOUT_STATUS', 'True', 'Accepts payments through Google Checkout on your site', '6', '3', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
// Merchant id/key
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Select Mode of Operation', 'MODULE_PAYMENT_GOOGLECHECKOUT_MODE', 'https://sandbox.google.com/checkout/', 'Select either the Developer\'s Sandbox or live Production environment<br />Note that different Id/Key pair will be used depending on the environment selected', '6', '3', 'zen_cfg_select_option(array(\'https://sandbox.google.com/checkout/\', \'https://checkout.google.com/\'),',now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Production Merchant ID', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID', '', 'Your merchant ID is listed on the \"Integration\" page under the \"Settings\" tab', '6', '1', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Production Merchant Key', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY', '', 'Your merchant key is also listed on the \"Integration\" page under the \"Settings\" tab', '6', '2', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sandbox Merchant ID', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID_SNDBOX', '', 'Your merchant ID is listed on the \"Integration\" page under the \"Settings\" tab', '6', '1', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sandbox Merchant Key', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY_SNDBOX', '', 'Your merchant key is also listed on the \"Integration\" page under the \"Settings\" tab', '6', '2', now())");
// CGI
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('.htaccess Basic Authentication Mode', 'MODULE_PAYMENT_GOOGLECHECKOUT_CGI', 'False', 'Your site Site in installed with PHP over CGI? <br /> This configuration will <b>disable</b> PHP Basic Authentication that is NOT compatible with CGI used in the responsehandler.php to validate Google Checkout messages.<br />If setted True you MUST configure your .htaccess files <a href=\"htaccess.php\" target=\"_OUT\">here</a>.', '6', '4', 'zen_cfg_select_option(array(\'False\', \'True\'),',now())");
// Virtual Goods
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Disable Google Checkout for Virtual Goods?', 'MODULE_PAYMENT_GOOGLECHECKOUT_VIRTUAL_GOODS', 'False', 'If this configuration is enabled and there is any virtual good in the cart the Google Checkout button will be shown disabled.', '6', '4', 'zen_cfg_select_option(array(\'True\', \'False\'),',now())");	
//  $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('MultiSocket Shipping Quotes Retrieval', 'MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET', 'False', 'This configuration will enable a multisocket feature to parallelize Shipping Providers quotes. This should reduce the time this call take and avoid GC Merchant Calculation TimeOut. <a href=\"multisock.html\" target=\"_OUT\">More Info</a>.(Alfa Feature)', '6', '4', 'zen_cfg_select_option(array(\'True\', \'False\'),',now())");	
// Shipping configs
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Allow US PO BOX shipping?', 'MODULE_PAYMENT_GOOGLECHECKOUT_USPOBOX', 'True', 'Allow sending items to US PO Boxes?', '6', '4', 'zen_cfg_select_option(array(\'True\', \'False\'),',now())");	
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Select Merchant Calculation Mode of Operation', 'MODULE_PAYMENT_GOOGLECHECKOUT_MC_MODE', 'https', 'Merchant calculation URL for Sandbox environment. (Checkout production environemnt always requires HTTPS.)', '6', '4', 'zen_cfg_select_option(array(\'http\', \'https\'),',now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Default Values for Real Time Shipping Rates', 'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING', '', 'Default values for real time rates in case the webservice call fails.<br />Set <b>Default Value</b> to <b>0</b> to disable the method <br/><a href=\"../googlecheckout/shipping_generator/\">Shipping Generator</a>', '6', '0',\"zen_cfg_select_shipping($shipping_list, \",now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('GoogleCheckout Carrier Calculated Shipping', 'MODULE_PAYMENT_GOOGLECHECKOUT_CARRIER_CALCULATED_ENABLED', 'True', 'Do you want to use GC Carrier calculated shipping? This feature can be mixed with Flat Rate Shipping, but not Merchant Calculated.', '6', '4', 'zen_cfg_select_option(array(\'True\',\'False\'),',now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Carrier Calculater Shipping Configuration', 'MODULE_PAYMENT_GOOGLECHECKOUT_CARRIER_CALCULATED', '', 'Set Default Values, Fix and Variable charge<br />Set <b>Def. Value</b> to <b>0</b> to disable the method', '6', '5',\"zen_cfg_select_CCshipping(\",now())");

    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Rounding Policy Mode', 'MODULE_PAYMENT_GOOGLECHECKOUT_TAXMODE', 'HALF_EVEN', 'This configuration specifies the methodology that will be used to round values to two decimal places. <a href=\"http://code.google.com/apis/checkout/developer/Google_Checkout_Rounding_Policy.html\">More info</a>', '6', '4', 'zen_cfg_select_option(array(\'UP\',\'DOWN\',\'CEILING\',\'HALF_UP\',\'HALF_DOWN\', \'HALF_EVEN\'),',now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Rounding Policy Rule', 'MODULE_PAYMENT_GOOGLECHECKOUT_TAXRULE', 'PER_LINE', 'This configuration specifies when rounding rules should be applied to monetary values while Google Checkout is computing an order total.', '6', '4', 'zen_cfg_select_option(array(\'PER_LINE\',\'TOTAL\'),',now())");
//    Cart expiration
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Cart Expiration Time (Minutes)', 'MODULE_PAYMENT_GOOGLECHECKOUT_EXPIRATION', 'NONE', 'Set the time in minutes after which the cart will expire. NONE for no-expiration', '6', '1', now())");
    
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Also send notifications with Zencart', 'MODULE_PAYMENT_GOOGLECHECKOUT_USE_CART_MESSAGING', 'False', 'Do you also want to send notifications to buyers using Zencart\'s mailing system?', '6', '4', 'zen_cfg_select_option(array(\'True\',\'False\'),',now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Google Analytics Id', 'MODULE_PAYMENT_GOOGLECHECKOUT_ANALYTICS', 'NONE', 'Do you want to integrate the module with Google Analytics? Add your GA Id (UA-XXXXXX-X), NONE to disable. <br/> More info <a href=\'http://code.google.com/apis/checkout/developer/checkout_analytics_integration.html\'>here</a>', '6', '1', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('3rd Party Tracking', 'MODULE_PAYMENT_GOOGLECHECKOUT_3RD_PARTY_TRACKING', 'NONE', 'Do you want to integrate the module 3rd party tracking? Add the tracker URL, NONE to disable. <br/> More info <a href=\'http://code.google.com/apis/checkout/developer/checkout_pixel_tracking.html\'>here</a>', '6', '1', now())");

//    Restricted products
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Google Checkout restricted product categories', 'MODULE_PAYMENT_GOOGLECHECKOUT_RESTRICTED_CATEGORIES', '', 'Specify the ids separated by commas \',\' of the product categories for which the GC button should be disabled.<br />Check <a href=\"http://checkout.google.com/support/sell/bin/answer.py?answer=46174&topic=8681\">Google Policy</a>', '6', '8', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Continue shopping URL.', 'MODULE_PAYMENT_GOOGLECHECKOUT_CONTINUE_URL', 'checkout_success', 'Specify the page customers will be directed to if they choose to continue shopping after checkout.', '6', '8', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    $db->Execute("create table if not exists " . $this->table_name . " (customers_id int(11), buyer_id bigint(20) )");
    $db->Execute("create table if not exists " . $this->table_order ." (orders_id int(11), google_order_number bigint(20), order_amount decimal(15,4) )");

// Add GC custom order states
/*
 * New
 * Processing
 * Shipped
 * Refunded
 * Shipped and refunded
 * Canceled
 */
    $languages = zen_get_languages();
    foreach($this->gc_order_states as $orders_status_id => $orders_status_name) {
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        $language_id = $languages[$i]['id'];
        $order_status_id = $db->Execute("select orders_status_id from " . TABLE_ORDERS_STATUS . 
                                " where orders_status_id = '" . (int)$orders_status_id .
                                "' and language_id = '" . (int)$language_id . "'");
        
        $sql_data_array = array('orders_status_name' => zen_db_prepare_input($orders_status_name),
                                  'orders_status_id' => $orders_status_id,
                                  'language_id' => $language_id);

        if($order_status_id->RecordCount() == 0) {
          zen_db_perform(TABLE_ORDERS_STATUS, $sql_data_array);
        } else {
          zen_db_perform(TABLE_ORDERS_STATUS, $sql_data_array, 'update', 
                              "orders_status_id = '" . (int)$orders_status_id . 
                              "' and language_id = '" . (int)$language_id . "'");
        }
      }
    }
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
    return array('MODULE_PAYMENT_GOOGLECHECKOUT_VERSION',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_STATUS',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_MODE',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID_SNDBOX',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY_SNDBOX',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_CGI', 
                 'MODULE_PAYMENT_GOOGLECHECKOUT_MC_MODE',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_VIRTUAL_GOODS',
//               'MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_USPOBOX',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_CARRIER_CALCULATED_ENABLED',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_CARRIER_CALCULATED',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_TAXMODE',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_TAXRULE',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_EXPIRATION',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_USE_CART_MESSAGING',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_ANALYTICS',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_3RD_PARTY_TRACKING',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_RESTRICTED_CATEGORIES',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_CONTINUE_URL',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER');
  }
}
// ** END GOOGLE CHECKOUT **
?>