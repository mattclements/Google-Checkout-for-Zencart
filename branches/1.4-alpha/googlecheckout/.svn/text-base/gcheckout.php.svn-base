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

/* **GOOGLE CHECKOUT ** v1.4
 * @version $Id: gcheckout.php 5342 2007-06-04 14:58:57Z ropu $
 * Script invoked when Google Checkout payment option has been enabled
 * It uses phpGCheckout library so it can work with PHP4 and PHP5
 * Generates the cart xml, shipping and tax options and adds them as hidden fields
 * along with the Checkout button
 
 * A disabled button is displayed in the following cases:
 * 1. If merchant id or merchant key is not set 
 * 2. If there are multiple shipping options selected and they use different shipping tax tables
 *  or some dont use tax tables
 */
// error_reporting(E_ALL);
//require_once('admin/includes/configure.php');
//require_once('admin/includes/configure.php');
require('includes/languages/' .  $_SESSION['language'] . '/' .'modules/payment/googlecheckout.php');
require_once('includes/modules/payment/googlecheckout.php');

if (!function_exists('selfURL')) {
  function selfURL() { 
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
    $protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; 
    $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
    return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
  }
}
function strleft($s1, $s2) { 
  return substr($s1, 0, strpos($s1, $s2)); 
}

//Functions used to prevent SQL injection attacks
function makeSqlString($str) {
  return addcslashes(stripcslashes($str), "\"'\\\0..\37!@\@\177..\377");
}

function makeSqlInteger($val) {
  return ((settype($val, 'integer'))?($val):0); 
}

function makeSqlFloat($val) {
  return ((settype($val, 'float'))?($val):0); 
}

$googlepayment = new googlecheckout();
$cart = $_SESSION['cart'];
$total_weight = $cart->show_weight();
$total_count = $cart->count_contents();

require('googlecheckout/library/googlecart.php');
require('googlecheckout/library/googleitem.php');
require('googlecheckout/library/googleshipping.php');
require('googlecheckout/library/googletax.php');

$Gcart = new googlecart($googlepayment->merchantid,
                        $googlepayment->merchantkey,  
                        MODULE_PAYMENT_GOOGLECHECKOUT_MODE==
                          'https://sandbox.google.com/checkout/'
                          ?"sandbox":"production",
                        DEFAULT_CURRENCY);

$Gwarnings = array();
if(MODULE_PAYMENT_GOOGLECHECKOUT_VERSION != GOOGLECHECKOUT_FILES_VERSION) {
  $Gcart->SetButtonVariant(false);
  $Gwarnings[] = sprintf(GOOGLECHECKOUT_STRING_WARN_MIX_VERSIONS, 
                          MODULE_PAYMENT_GOOGLECHECKOUT_VERSION, 
                          GOOGLECHECKOUT_FILES_VERSION);
}

if( ($googlepayment->merchantid == '') || ($googlepayment->merchantkey == '')) {
  $Gcart->SetButtonVariant(false);
  $Gwarnings[] = GOOGLECHECKOUT_STRING_WARN_NO_MERCHANT_ID_KEY;
}

$products = $cart->get_products();
require_once(DIR_WS_CLASSES . 'order.php');
$order = new order;
$order_items = $order->products;
//die;

if(MODULE_PAYMENT_GOOGLECHECKOUT_VIRTUAL_GOODS == 'True' 
              && $cart->get_content_type() != 'physical' ) {
  $Gcart->SetButtonVariant(false);
  $Gwarnings[] = GOOGLECHECKOUT_STRING_WARN_VIRTUAL;
}  
if(sizeof($products) == 0) {
  $Gcart->SetButtonVariant(false);
  $Gwarnings[] = GOOGLECHECKOUT_STRING_WARN_EMPTY_CART;
}

$tax_array = array();
$tax_name_array = array();

// BOF - define value for languages_id - added by colosports
$attributes = $db->Execute("select languages_id
                                    from " . TABLE_LANGUAGES . " 
                                    where name = '".$_SESSION['language']."'
                                    ");
$languages_id = $attributes->fields['languages_id'];                                          
// EOF - define value for languages_id - added by colosports
$flagAnyOutOfStock = false;
$product_list = '';

//print_r($order_items);
// Zencart's special attribute types
$look4Attr = array('TEXT');
for ($i=0, $n=sizeof($products); $i<$n; $i++) {
  if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes']))  {
    while (list($option, $value) = each($products[$i]['attributes'])) {
      $attributes = $db->Execute("select popt.products_options_name, 
                                poval.products_options_values_name,
                                pa.options_values_price, pa.price_prefix
                                from " . TABLE_PRODUCTS_OPTIONS . " popt, " .
                                TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " .
                                TABLE_PRODUCTS_ATTRIBUTES . " pa
                                where pa.products_id = '".makeSqlInteger($products[$i]['id']).
                                "'and pa.options_id = '" . makeSqlString($option) . "'
                                and pa.options_id = popt.products_options_id
                                and pa.options_values_id = '" . makeSqlString($value) . "'
                                and pa.options_values_id = poval.products_options_values_id
                                and popt.language_id = '" . $languages_id . "'
                                and poval.language_id = '" . $languages_id . "'");
      $attr_value = $attributes->fields['products_options_values_name'];
      $products[$i][$option]['products_options_name'] = $attributes->fields['products_options_name'];
      $products[$i][$option]['options_values_id'] = $value;
      $products[$i][$option]['products_options_values_name'] = in_array($attr_value, $look4Attr)?$products[$i]['attributes_values'][$option]:$attr_value;
//      $products[$i][$option]['products_options_values'] = $products[$i]['attributes_values'][$option];
      $products[$i][$option]['options_values_price'] = $attributes->fields['options_values_price'];
      $products[$i][$option]['price_prefix'] = $attributes->fields['price_prefix'];
    }
  }
  $products_name = $products[$i]['name'];
  $products_description = $db->Execute("select products_description 
                                        from ".TABLE_PRODUCTS_DESCRIPTION. " 
                                        where products_id = '" . $products[$i]['id'] . "' 
                                        and language_id = '". $languages_id ."'");
  $attribute_name ='';
  $tax = $db->Execute("select tax_class_title 
                       from " . TABLE_TAX_CLASS . " 
                       where tax_class_id = " . 
                       makeSqlInteger($products[$i]['tax_class_id']) );
  $tt = @$tax->fields['tax_class_title'];
  if(!empty($tt) && !in_array($products[$i]['tax_class_id'], $tax_array)) {
    $tax_array[] = $products[$i]['tax_class_id'];
    $tax_name_array[] = $tt;
  }
  if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
    reset($products[$i]['attributes']);
    while (list($option, $value) = each($products[$i]['attributes'])) {
      $attribute_name .= '[' . 
          $products[$i][$option]['products_options_name'] . ':' .
          $products[$i][$option]['products_options_values_name'] . '] ';
    }
  }
    $products_description = $attribute_name;    
// Replace the product description with the product's attribute. Uncomment the 
// line below to append the description after the attributes. - added by colosports
// $products_description = $products_description->fields['products_description']; 
  $Gitem = new GoogleItem($products_name,
                          $products_description,
                          $products[$i]['quantity'], 
                          $currencies->get_value(DEFAULT_CURRENCY) * $products[$i]['final_price']);
  $Gitem->SetMerchantPrivateItemData(
          new MerchantPrivateItemData(array(
//                              'item_old' =>  base64_encode(serialize($products[$i])),
                                'item' =>
                                base64_encode(serialize($order_items[$i])))));


  $Gitem->SetMerchantItemId($products[$i]['id']);
  if(!empty($tt)) {
    $Gitem->SetTaxTableSelector($tt);
  }
  
  $product_query = "select products_virtual
                      from " . TABLE_PRODUCTS . "
                      where products_id = '" . (int)$products[$i]['id'] . "'";

  $product_virtual = $db->Execute($product_query);
  if($product_virtual->fields['products_virtual'] == 1) {
//    $Gitem->SetEmailDigitalDelivery('true');
    $digital_url = str_replace("&amp;", "&", 
                               zen_href_link('checkout_success', '', 'NONSSL'));
    $Gitem->SetURLDigitalContent($digital_url, '', $products_name . " " .
                                                         $products_description);
  }

  $Gcart->AddItem($Gitem);
// Stock Check
  if (STOCK_CHECK == 'true') {
    if (zen_check_stock($products[$i]['id'], $products[$i]['quantity'])) {
      $flagAnyOutOfStock = true;
    }
  }
  $product_list .= ";".(int)$products[$i]['id'];
}

// Coustom Order Totals
// ver el tema del tax...

//$_POST['dc_redeem_code'] = 'ROPU';

require_once(DIR_WS_CLASSES . 'order_total.php');
$order_total_modules = new order_total;
$order_total_modules->collect_posts();
$order_total_modules->pre_confirmation_check();
$order_totals = $order_total_modules->process();
//print_r($order_totals);

$tax_address = zen_get_tax_locations();

$ot_used = false;
foreach($order_totals as $order_total){
  if(!in_array($order_total['code'], $googlepayment->ot_ignore)){

// Cant used this since the OT is passed as an item, and tax cant be calculated
    $tax_class_id = @constant("MODULE_ORDER_TOTAL_" . substr(strtoupper($order_total['code']), 3) . "_TAX_CLASS");
    $tax = $db->Execute("select tax_class_title 
                         from " . TABLE_TAX_CLASS . " 
                         where tax_class_id = " . 
                         makeSqlInteger($tax_class_id) );
    $tt = @$tax->fields['tax_class_title'];
    if(!empty($tt) && !in_array($tax_class_id, $tax_array)) {
      $tax_array[] = $tax_class_id;
      $tax_name_array[] = $tt;
    }
    $ot_value = $order_total['value'] * (strrpos($order_total['text'], '-')===false?1:-1);//($order_total['text']{0}=='-'?-1:1);
    $Gitem = new GoogleItem($order_total['title'],
                            '',
                            '1', 
//                            number_format(($amount) * $currencies->get_value($my_currency), $currencies->get_decimal_places($my_currency))
                            $currencies->get_value(DEFAULT_CURRENCY) * $ot_value);
    $Gitem->SetMerchantPrivateItemData(
            new MerchantPrivateItemData(array('order_total' =>
                             base64_encode(serialize($order_total)))));
//print_r($order_total);
    if(!empty($tt)) {
      $Gitem->SetTaxTableSelector($tt);
    }
// TaxTable with 0% Rate
//    $Gitem->SetTaxTableSelector('_OT_cero_tax');

//  This is a hack to avoid showing shipping when cart is virtual and an OT is added
    if($cart->get_content_type() == 'virtual') {
      $Gitem->SetEmailDigitalDelivery('true');
    }

    $Gcart->AddItem($Gitem);
    $ot_used = true;
  }
}

//if($ot_used) {
//  $GAtaxTable_OT = new GoogleAlternateTaxTable('_OT_cero_tax');
//  $GAtaxRule = new GoogleAlternateTaxRule('0');
//  $GAtaxRule->SetWorldArea();
//  $GAtaxTable_OT->AddAlternateTaxRules($GAtaxRule);
//  $Gcart->AddAlternateTaxTables($GAtaxTable_OT);  
//}
// Out of Stock
if ( (STOCK_ALLOW_CHECKOUT != 'true') && ($flagAnyOutOfStock == true) ) {
  $Gcart->SetButtonVariant(false);
  $Gwarnings[] = GOOGLECHECKOUT_STRING_WARN_OUT_OF_STOCK;
}


$private_data = zen_session_id().';'.zen_session_name();
$parameters = ($googlepayment->continue_url=='GC_return')?'products_ids=' . 
                implode(',', explode(';', !empty($product_list)?
                  trim($product_list,';'):'-1')):'';
$continue_shopping_url = str_replace("&amp;", "&", 
        zen_href_link($googlepayment->continue_url, $parameters, 'NONSSL'));
$edit_cart_url = zen_href_link(FILENAME_SHOPPING_CART, '', 'NONSSL'); 


$Gcart->SetMerchantPrivateData(new MerchantPrivateData(
                                  array('session-data' => $private_data,
                                        'ip-address' => $_SERVER['REMOTE_ADDR'])));
$Gcart->AddRoundingPolicy(MODULE_PAYMENT_GOOGLECHECKOUT_TAXMODE, 
                          MODULE_PAYMENT_GOOGLECHECKOUT_TAXRULE);
$Gcart->SetEditCartUrl($edit_cart_url);
$Gcart->SetContinueShoppingUrl($continue_shopping_url);
$Gcart->SetRequestBuyerPhone('true');

$tax_class = array ();
$shipping_arr = array ();
$tax_class_unique = array ();

// Start Shipping
if($cart->get_content_type() != 'virtual') {
  //Add each shipping option to the options array
  $options = explode(", ", MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING);
  // i get the properties of the shipping methods
  $module_directory = DIR_FS_CATALOG . DIR_WS_MODULES . 'shipping/';
  
  $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
  $directory_array = array ();
  if ($dir = @ dir($module_directory)) {
  	while ($file = $dir->read()) {
  
  		if (!is_dir($module_directory . $file)) {
  			if (substr($file, strrpos($file, '.')) == $file_extension) {
  				$directory_array[] = $file;
  			}
  		}
  	}
  	sort($directory_array);
  	$dir->close();
  }
  $check_query = $db->Execute("select countries_iso_code_2 
                               from " . TABLE_COUNTRIES . " 
                               where countries_id = 
                               '" . SHIPPING_ORIGIN_COUNTRY . "'");
  $shipping_origin_iso_code_2 = $check_query->fields['countries_iso_code_2'];
  
  $module_info = array ();
  $module_info_enabled = array();
  for ($i = 0, $n = sizeof($directory_array); $i < $n; $i++) {
  	$file = $directory_array[$i];
  
  	include_once (DIR_FS_CATALOG .DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/shipping/' . $file);
  	include_once ($module_directory . $file);
  
  	$class = substr($file, 0, strrpos($file, '.'));
  	$module = new $class;
    $curr_ship = strtoupper($module->code);
    switch($curr_ship){
      case 'FEDEXGROUND':
        $curr_ship = 'FEDEX_GROUND';
        break; 
      case 'FEDEXEXPRESS':
        $curr_ship = 'FEDEX_EXPRESS';
        break; 
      case 'UPSXML':
        $curr_ship = 'UPSXML_RATES';
        break; 
      case 'DHLAIRBORNE':
        $curr_ship = 'AIRBORNE';
        break; 
      default:
        break;
    }
//    $check_query = $db->Execute("select configuration_value 
//                                 from " . TABLE_CONFIGURATION . " 
//                                 where configuration_key = 
//                                 'MODULE_SHIPPING_" . $curr_ship . "_STATUS'");
  	if (@constant('MODULE_SHIPPING_' . $curr_ship . '_STATUS') == 'True') {
      $module_info_enabled[$module->code] = array('enabled' => true);
  	}
  //	if ($module->enabled == true) {
  	if ($module->check() == true) {
  		$module_info[$module->code] = array (
  			'code' => $module->code,
  			'title' => $module->title,
  			'description' => $module->description,
  			'status' => $module->check());
  
  	}
  }
  
  // check if there is a shipping module activated that is not flat rate
  // to enable Merchan Calculations
  // if there are flat and MC, both will be MC
  $ship_calculation_mode = (count(array_keys($module_info_enabled)) 
                          > count(array_intersect($googlepayment->shipping_support
                          , array_keys($module_info_enabled)))) ? true : false;
  
  // campare name:vale and returns value for configurations parameters
  function compare($key, $data)
  {
  	foreach($data as $value) {
  		list($key2, $valor) = explode("_VD:", $value);
  		if($key == $key2)		
  			return $valor;
  	}
  	return '0';
  }
  $key_values = explode( ", ", MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING);
  
  $shipping_config_errors = '';
  foreach ($module_info as $key => $value) {
  	// check if the shipping method is activated
  	$module_name = $module_info[$key]['code'];
  	$curr_ship = strtoupper($module_name);
  	// patch to non standard common strings
    switch($curr_ship){
      case 'FEDEXGROUND':
        $curr_ship = 'FEDEX_GROUND';
        break; 
      case 'FEDEXEXPRESS':
        $curr_ship = 'FEDEX_EXPRESS';
        break; 
      case 'UPSXML':
        $curr_ship = 'UPSXML_RATES';
        break; 
      case 'DHLAIRBORNE':
        $curr_ship = 'AIRBORNE';
        break; 
      default:
        break;
    }
//  	$check_query = $db->Execute("select configuration_key,configuration_value 
//  								                                   from " . TABLE_CONFIGURATION . "
//  								                                   where configuration_key LIKE
//  								                                   'MODULE_SHIPPING_" . $curr_ship . "_%' ");
//  	$num_rows = $check_query->RecordCount($check_query);
//  	$data_arr = array ();
//  	$handling = 0;
//  	$table_mode = '';
//  
//  	for ($j = 0; $j < $num_rows; $j++) {
//  		$data_arr[$check_query->fields['configuration_key']] = $check_query->fields['configuration_value'];
//  		$check_query->MoveNext();
//  	}
//  	$common_string = "MODULE_SHIPPING_" . $curr_ship . "_";
//  	$zone = @$data_arr[$common_string . "ZONE"];
//  	$enable = $data_arr[$common_string . "STATUS"];
//  	$curr_tax_class = $data_arr[$common_string . "TAX_CLASS"];
//  	$price = @$data_arr[$common_string . "COST"];
//  	$handling = @$data_arr[$common_string . "HANDLING"];
//  	$table_mode = @$data_arr[$common_string . "MODE"];
//    
    $common_string = "MODULE_SHIPPING_" . $curr_ship . "_";
    @$zone =  constant($common_string . "ZONE");
    @$enable =  constant($common_string . "STATUS");
    @$curr_tax_class =  constant($common_string . "TAX_CLASS");
    @$price =  constant($common_string . "COST");
    @$handling =  constant($common_string . "HANDLING");
    @$table_mode =  constant($common_string . "MODE");
    
    $allowed_restriction_state = $allowed_restriction_country = array();
    
    // Exception for enabling shipping modules
    if(defined('MODULE_SHIPPING_FREESHIPPER_STATUS') 
        && MODULE_SHIPPING_FREESHIPPER_STATUS == "True"){
      switch ($curr_ship) {
        case 'FREESHIPPER':
          if($cart->free_shipping_items() != $cart->count_contents()){
            $enable = "False";
          }
          else {
            $enable = "True";
          }
          break;
        default:
          if($cart->free_shipping_items() == $cart->count_contents()){
            $enable = "False";
          }
          break;
      }
    }
    
    if ($enable == "True") {
    	if ($zone != '') {
    		$zone_result = $db->Execute("select countries_name, coalesce(zone_code, 'All Areas') zone_code, countries_iso_code_2
                                     from " . TABLE_GEO_ZONES . " as gz " .
                                     " inner join " . TABLE_ZONES_TO_GEO_ZONES . " as ztgz on gz.geo_zone_id = ztgz.geo_zone_id " .
                                     " inner join " . TABLE_COUNTRIES . " as c on ztgz.zone_country_id = c.countries_id " .
                                     " left join " . TABLE_ZONES . " as z on ztgz.zone_id=z.zone_id
                                     where gz.geo_zone_id= '" .  $zone ."'");
    		// i get all the alowed shipping zones
    		while(!$zone_result->EOF){
    			$allowed_restriction_state[] = $zone_result->fields['zone_code'];
    			$allowed_restriction_country[] = array($zone_result->fields['countries_name'],
                                            $zone_result->fields['countries_iso_code_2']);
    			$zone_result->MoveNext();
    		}
    	}
    	
    	if ($curr_tax_class != 0 && $curr_tax_class != '') {
    		$tax_class[] = $curr_tax_class;
    		if (!in_array($curr_tax_class, $tax_class_unique))
    			$tax_class_unique[] = $curr_tax_class;
    	}
  
      if (is_array($googlepayment->mc_shipping_methods[$key])) {
    		foreach($googlepayment->mc_shipping_methods[$key] as $type => $shipping_type){
    			foreach($shipping_type as $method => $name){
            $total_weight = $cart->show_weight();
            $total_count = $cart->count_contents();
            // merchant calculated shipping
    				if ($ship_calculation_mode == 'True') {
              $shipping_name = $googlepayment->mc_shipping_methods_names[$module_info[$key]['code']] . ': ' . $name;
    				} 
    			// flat rate shipping 
    				else {
              $shipping_name = $googlepayment->mc_shipping_methods_names[$module_info[$key]['code']] . ': ' . $name;
    				}	
    	//			merchant calculated - use defaults
    				if(!in_array($module_info[$key]['code'], $googlepayment->shipping_support)) {
              $shipping_price = $currencies->get_value(DEFAULT_CURRENCY) * compare($module_info[$key]['code'].$method . $type ,$key_values);
    				}
    				// flat rate shipping
    				else {
    					$module = new $module_name;
    					$quote = $module->quote($method);
    					$price = $quote['methods'][0]['cost'];
              $shipping_price = $currencies->get_value(DEFAULT_CURRENCY) * ($price>=0?$price:0);
    				}
            $Gfilter = new GoogleShippingFilters();
    				if(MODULE_PAYMENT_GOOGLECHECKOUT_USPOBOX == 'False') {
              $Gfilter->SetAllowUsPoBox('false');
    				}			
    				if(!empty($allowed_restriction_country)){
    						foreach($allowed_restriction_state as $state_key => $state) {
    							if($allowed_restriction_country[$state_key][1] == 'US') {
    								if($state == 'All Areas') {
                      $Gfilter->SetAllowedCountryArea('ALL');
    								}
    								else {
    	                $Gfilter->AddAllowedStateArea($state);
      							}
    							}
    							else {
    							  // TODO here should go the non us area (not implemented in GC)
    							  // now just the country
                    $Gfilter->AddAllowedPostalArea($allowed_restriction_country[$state_key][1]);
    							}
    						}			 
    				}
    				else {
              switch($type) {
                case 'domestic_types':
                  if('US' == $shipping_origin_iso_code_2) {
                    $Gfilter->SetAllowedCountryArea('ALL');
                  }else{
                    $Gfilter->AddAllowedPostalArea($shipping_origin_iso_code_2);
                  }
                 break;
                case 'international_types':
                    $Gfilter->SetAllowedWorldArea(true);
                  if('US' == SHIPPING_ORIGIN_COUNTRY) {
                    $Gfilter->SetExcludedCountryArea('ALL');
                  }else{
                    $Gfilter->AddExcludedPostalArea($shipping_origin_iso_code_2);
                  }
                 break;
                default:
                // shoud never reach here!
                  $Gfilter->SetAllowedWorldArea(true);
                 break;
    				  }
            }
    				if ($ship_calculation_mode == 'True') {
              $Gshipping = new GoogleMerchantCalculatedShipping($shipping_name, $shipping_price);
              $Gshipping->AddShippingRestrictions($Gfilter);
              $Gshipping->AddAddressFilters($Gfilter);
    				} 
    				else {
              $Gshipping = new GoogleFlatRateShipping($shipping_name, $shipping_price);
              $Gshipping->AddShippingRestrictions($Gfilter);
    				}
            $Gcart->AddShipping($Gshipping);
    			}
    		}
      }
      else {
        $shipping_config_errors .= $key ." (ignored)<br />";
      }
  	}
  }
}
// end shipping methods
// merchant calculation url  
//if($ship_calculation_mode == 'True') {
//}
  if (MODULE_PAYMENT_GOOGLECHECKOUT_MODE == 'https://sandbox.google.com/checkout/'
            && MODULE_PAYMENT_GOOGLECHECKOUT_MC_MODE == 'http') {
    $url = HTTP_SERVER . DIR_WS_CATALOG . 'googlecheckout/responsehandler.php';
  } else {
    $url = HTTPS_SERVER . DIR_WS_CATALOG . 'googlecheckout/responsehandler.php';
  }
  /** @params
  * merchant_calculations_url
  * merchant_calculated_tax
  * accept_merchant_coupons
  * accept_gift_certificates 
  */
  $Gcart->SetMerchantCalculations($url, 'false', 'true', 'false');


if(MODULE_PAYMENT_GOOGLECHECKOUT_3RD_PARTY_TRACKING != 'NONE') {
// Third party tracking 
  $tracking_attr_types = array(
                              'buyer-id' => 'buyer-id',
                              'order-id' => 'order-id',
                              'order-subtotal' => 'order-subtotal',
                              'order-subtotal-plus-tax' => 'order-subtotal-plus-tax',
                              'order-subtotal-plus-shipping' => 'order-subtotal-plus-shipping',
                              'order-total' => 'order-total',
                              'tax-amount' => 'tax-amount',
                              'shipping-amount' => 'shipping-amount',
                              'coupon-amount' => 'coupon-amount',
                              'coupon-amount' => 'coupon-amount',
                              'billing-city' => 'billing-city',
                              'billing-region' => 'billing-region',
                              'billing-postal-code' => 'billing-postal-code',
                              'billing-country-code' => 'billing-country-code',
                              'shipping-city' => 'shipping-city',
                              'shipping-region' => 'shipping-region',
                              'shipping-postal-code' => 'shipping-postal-code',
                              'shipping-country-code' => 'shipping-country-code',
                            );
  $Gcart->AddThirdPartyTracking(MODULE_PAYMENT_GOOGLECHECKOUT_3RD_PARTY_TRACKING,
                                                          $tracking_attr_types);
}
//Tax options	

if(sizeof($tax_class_unique) == 1 && sizeof($module_info_enabled) == sizeof($tax_class)) {
  $tax_result =  $db->Execute( "select countries_name, coalesce(zone_code, 'All Areas') zone_code, tax_rate, countries_iso_code_2
                               from " . TABLE_TAX_RATES . " as tr " .
                               " inner join " . TABLE_ZONES_TO_GEO_ZONES . " as ztgz on tr.tax_zone_id = ztgz.geo_zone_id " .
                               " inner join " . TABLE_COUNTRIES . " as c on ztgz.zone_country_id = c.countries_id " .
                               " left join " . TABLE_ZONES . " as z on ztgz.zone_id=z.zone_id
                               where tr.tax_class_id= '" .  $tax_class_unique[0] ."'");
  $num_rows = $tax_result->RecordCount();
  $tax_rule = array();

  for($j=0; $j<$num_rows; $j++) {
    $tax_result->MoveNext();
    $rate = ((double) ($tax_result->fields['tax_rate']))/100.0;
    $GDtaxRule = new GoogleDefaultTaxRule($rate, 'true');
		if($tax_result->fields['countries_iso_code_2'] == 'US') {
			if($tax_result->fields['zone_code'] == 'All Areas') {
        $GDtaxRule->SetCountryArea('ALL');
			}
			else {
        $GDtaxRule->SetStateAreas($tax_result->fields['zone_code']);
			}
		}
		else {
      $GDtaxRule->AddPostalArea($tax_result->fields['countries_iso_code_2']);
		}      			
    $Gcart->AddDefaultTaxRules($GDtaxRule);
  }
} else {
  $GDtaxRule = new GoogleDefaultTaxRule(0, 'false');
  $GDtaxRule->SetWorldArea(true);
}

if(sizeof($tax_class_unique) > 1 || (sizeof($tax_class_unique) == 1 && 
   sizeof($module_info_enabled) != sizeof($tax_class) ))  {
  $Gcart->SetButtonVariant(false);
  $Gwarnings[] = GOOGLECHECKOUT_STRING_WARN_MULTIPLE_SHIP_TAX;
}

$i=0;
$tax_tables = array();

foreach($tax_array as $tax_table)  {
		                                 
   $tax_result =  $db->Execute("select countries_name, coalesce(zone_code, 'All Areas') zone_code, tax_rate, countries_iso_code_2
                               from " . TABLE_TAX_RATES . " as tr " .
                               " inner join " . TABLE_ZONES_TO_GEO_ZONES . " as ztgz on tr.tax_zone_id = ztgz.geo_zone_id " .
                               " inner join " . TABLE_COUNTRIES . " as c on ztgz.zone_country_id = c.countries_id " .
                               " left join " . TABLE_ZONES . " as z on ztgz.zone_id=z.zone_id
                               where tr.tax_class_id= '" . $tax_array[$i] ."'");	                            
  $num_rows = $tax_result->RecordCount();
  $tax_rule = array();
  $GAtaxTable = new GoogleAlternateTaxTable((!empty($tax_name_array[$i])?$tax_name_array[$i]:'none'), 'false');

  for($j=0; $j<$num_rows; $j++) {
    $tax_result->MoveNext();
    $rate = ((double)($tax_result->fields['tax_rate']))/100.0;
    $GAtaxRule = new GoogleAlternateTaxRule($rate);
		if($tax_result->fields['countries_iso_code_2'] == 'US') {

			if($tax_result->fields['zone_code'] == 'All Areas') {
        $GAtaxRule->SetCountryArea('ALL');
			}
			else {
        $GAtaxRule->SetStateAreas($tax_result->fields['zone_code']);
			}
		}
		else {
		  // TODO here should go the non use area
      $GAtaxRule->AddPostalArea($tax_result->fields['countries_iso_code_2']);
		}      
    			
    $GAtaxTable->AddAlternateTaxRules($GAtaxRule);
  }
  $i++;
  $Gcart->AddAlternateTaxTables($GAtaxTable);  
}
?>
<div align="right">
 <B><?php echo MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_OPTION ?></B>
</div>
<div align="right">
    <?php
    echo $Gcart->CheckoutButtonCode();
    ?>
    <?php
      foreach($Gwarnings as $Gwarning) {
        echo '<div class="warning"> * ' . $Gwarning . '</div>';
      }
      if($shipping_config_errors != ''){
        echo '<div class="warning"><b>' . GOOGLECHECKOUT_STRING_ERR_SHIPPING_CONFIG . '</b><br />';
        echo $shipping_config_errors;
        echo '</div>';
      }
    ?>
</div>
<?php
//echo $Gcart->CheckoutHTMLButtonCode();
//echo "<xmp>".$Gcart->GetXML()."</xmp>";
?>
<!-- ** END GOOGLE CHECKOUT ** -->