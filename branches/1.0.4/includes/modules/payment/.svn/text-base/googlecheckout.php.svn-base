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
    $this->merchantid = MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID;
    $this->merchantkey = MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY;
    $this->mode = MODULE_PAYMENT_GOOGLECHECKOUT_MODE;
    $this->enabled = ((MODULE_PAYMENT_GOOGLECHECKOUT_STATUS == 'True') ? true : false);

    $this->shipping_support = array("flat", "item", "table");
    $this->shipping_display = array(GOOGLECHECKOUT_FLAT_RATE_SHIPPING, GOOGLECHECKOUT_ITEM_RATE_SHIPPING, GOOGLECHECKOUT_TABLE_RATE_SHIPPING);
    $this->ship_flat_ui = "Standard flat-rate shipping";

    $this->schema_url = "http://checkout.google.com/schema/2";
    $this->base_url = $this->mode."cws/v2/Merchant/" . $this->merchantid;
    $this->checkout_url =  $this->base_url . "/checkout";
    $this->checkout_diagnose_url = $this->base_url . "/checkout/diagnose";
    $this->request_url = $this->base_url . "/request";
    $this->request_diagnose_url = $this->base_url . "/request/diagnose";
    $this->variant = 'text';

    if ((int)MODULE_PAYMENT_GOOGLECHECKOUT_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_GOOGLECHECKOUT_ORDER_STATUS_ID;
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
  function getShippingType($shipping_option) {
    switch($shipping_option) {
      case GOOGLECHECKOUT_FLAT_RATE_SHIPPING: return $this->ship_flat_ui."- Flat Rate"; 
      case GOOGLECHECKOUT_ITEM_RATE_SHIPPING: return $this->ship_flat_ui."- Item Rate";
      case GOOGLECHECKOUT_TABLE_RATE_SHIPPING: return $this->ship_flat_ui."- Table Rate";
      default: return "";
    }
  }

// Function used to compute the actual price for shipping depending upon the shipping type
// selected
  function getShippingPrice($ship_option, $cart, $actual_price, $handling=0, $table_mode="") {
    switch($ship_option) {
      case GOOGLECHECKOUT_FLAT_RATE_SHIPPING: {
        return $actual_price;	
      }
      case GOOGLECHECKOUT_ITEM_RATE_SHIPPING: {
        return ($actual_price * $cart->count_contents()) + $handling ;
      }
      case GOOGLECHECKOUT_TABLE_RATE_SHIPPING: {
//Check the mode to be used for pricing the shipping
        if($table_mode == "price")
          $table_size = $cart->show_total();
        else if ($table_mode == "weight")
          $table_size = $cart->show_weight();

// Parse the price (value1:price1,value2:price2)
        $tok = strtok($actual_price, ",");
        $tab_data = array();
        while($tok != FALSE) {
          $tab_data[] = $tok;
          $tok = strtok(",");
        }  
        $initial_val=0;	  
        foreach($tab_data as $curr) {
          $final_val = strtok($curr, ":");
          $pricing = strtok(":"); 
          if($table_size >= $initial_val && $table_size <= $final_val) {
            $price = $pricing + $handling;
            break;  
          }
          $initial_val = $final_val;
        }
        return $price;
      }
      default: return 0;
    }
  }

// class methods
  function update_status() {
  }

  function javascript_validation() {
    return false;
  }

  function selection() {
    return array('id' => $this->code,'module' => $this->title);
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
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID', '', 'Your merchant ID is listed on the \"Integration\" page under the \"Settings\" tab', '6', '1', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant Key', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY', '', 'Your merchant key is also listed on the \"Integration\" page under the \"Settings\" tab', '6', '2', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Select Mode of Operation', 'MODULE_PAYMENT_GOOGLECHECKOUT_MODE', 'https://sandbox.google.com/checkout/', 'Select either the Developer\'s Sandbox or live Production environment', '6', '3', 'zen_cfg_select_option(array(\'https://sandbox.google.com/checkout/\', \'https://checkout.google.com/\'),',now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Select shipping options.', 'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING', '', 'Select your shipping option(s), and make sure to configure them under the Shipping Modules section', '6', '0',\"zen_cfg_select_multioption($shipping_list, \",now())");
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
    return array('MODULE_PAYMENT_GOOGLECHECKOUT_STATUS', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY', 'MODULE_PAYMENT_GOOGLECHECKOUT_MODE','MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING','MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER');
  }
}
// ** END GOOGLE CHECKOUT **
?>
