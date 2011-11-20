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
 * Script invoked when Google Checkout payment option has been enabled
 * It uses phpGCheckout library so it can work with PHP4 and PHP5
 * Generates the cart xml, shipping and tax options and adds them as hidden fields
 * along with the Checkout button
 
 * A disabled button is displayed in the following cases:
 * 1. If merchant id or merchant key is not set 
 * 2. If there are multiple shipping options selected and they use different shipping tax tables
 *  or some dont use tax tables
 */
  
  require_once('admin/includes/configure.php');
  require('includes/languages/' .  $_SESSION['language'] . '/' .'modules/payment/googlecheckout.php');
  require_once('includes/modules/payment/googlecheckout.php');

  function selfURL() { 
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
    $protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; 
    $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
    return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
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
  global $db;
  $current_checkout_url = $googlepayment->checkout_url;
  //tep_session_register('current_checkout_url');

  if( ($googlepayment->merchantid == '') || ($googlepayment->merchantkey == '')) {
    $googlepayment->variant = "disabled";
    $current_checkout_url = selfURL();
  }

  //Create a cart and add items to it  
  require('googlecheckout/xmlbuilder.php');
  $gcheck = new XmLBuilder();

  $gcheck->push('checkout-shopping-cart', 
      array('xmlns' => "http://checkout.google.com/schema/2"));
  $gcheck->push('shopping-cart');
  $gcheck->push('items');

  $products = $cart->get_products();
  $tax_array = array();
  $tax_name_aray = array();

  if( sizeof($products) == 0) {
    $googlepayment->variant = "disabled";
    $current_checkout_url = selfURL();
  }

  for ($i=0, $n=sizeof($products); $i<$n; $i++) {
    if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes']))  {
      while (list($option, $value) = each($products[$i]['attributes'])) {
        $attributes = $db->Execute("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
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
        $products[$i][$option]['products_options_values_name'] = $attr_value;
        $products[$i][$option]['options_values_price'] = $attributes->fields['options_values_price'];
        $products[$i][$option]['price_prefix'] = $attributes->fields['price_prefix'];
      }
    }
    $products_name = $products[$i]['name'];
    $products_description = $db->Execute("select products_description 
                                          from ".TABLE_PRODUCTS_DESCRIPTION. " 
                                          where products_id = '" . $products[$i]['id'] . "' 
                                          and language_id = '". $languages_id ."'");
    $products_description = $products_description->fields['products_description'];

    $tax = $db->Execute("select tax_class_title 
                         from " . TABLE_TAX_CLASS . " 
                         where tax_class_id = " . 
                         makeSqlInteger($products[$i]['tax_class_id']) );
    $tt = $tax->fields['tax_class_title'];
    if(!in_array($products[$i]['tax_class_id'], $tax_array)) {
      $tax_array[] = $products[$i]['tax_class_id'];
      $tax_name_array[] = $tt;
    }
    if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
      reset($products[$i]['attributes']);
      while (list($option, $value) = each($products[$i]['attributes'])) {
        $products_name .= "\n" .'- ' . 
            $products[$i][$option]['products_options_name'] . ' ' .
            $products[$i][$option]['products_options_values_name'] . '';
      }
    }
    $gcheck->push('item');
    $gcheck->element('item-name', $products_name);
    $gcheck->element('item-description', $products_description);
    $gcheck->element('unit-price', $products[$i]['final_price'], array('currency'=> 'USD'));
    $gcheck->element('quantity', $products[$i]['quantity']);
    $gcheck->element('tax-table-selector', $tt);
    $gcheck->pop('item');
  }
  $gcheck->pop('items'); 

  $private_data = zen_session_id().';'.zen_session_name();
  $product_list = '';
  for ($i=0, $n=sizeof($products); $i<$n; $i++) {
    $product_list .= ";".(int)$products[$i]['id'];
  }
  $gcheck->push('merchant-private-data');
  $gcheck->element('session-data', $private_data);
  $gcheck->element('product-data', $product_list);
  $gcheck->pop('merchant-private-data');

  $gcheck->pop('shopping-cart');

  //Add the starting index file as the return url for the buyer.
  // This can be added as an option during the module installation
  $cont_shopping_cart = zen_href_link(FILENAME_DEFAULT);
  $gcheck->push('checkout-flow-support');
  $gcheck->push('merchant-checkout-flow-support');
  $gcheck->element('continue-shopping-url', $cont_shopping_cart);

  //Shipping options
  $gcheck->push('shipping-methods');
  $shipping_array = $db->Execute("select configuration_value 
                                  from " . TABLE_CONFIGURATION . " 
                                  where configuration_key = 
                                  'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING' ");
  $ship =$shipping_array->fields['configuration_value'];
  $tax_class = array();
  $shipping_arr = array();
  $tax_class_unique = array();
  //Add each shipping option to the options array
  $options = explode( ", ", $ship);

  for($i=0; $i< sizeof($googlepayment->shipping_display); $i++) {
    if(in_array($googlepayment->shipping_display[$i], $options))  {
      $curr_ship = strtoupper($googlepayment->shipping_support[$i]);
      $check_query = $db->Execute("select configuration_key,configuration_value 
                                   from " . TABLE_CONFIGURATION . " 
                                   where configuration_key LIKE 
                                   'MODULE_SHIPPING_" . $curr_ship . "_%'");
      $num_rows = $check_query->RecordCount();
      $name = $googlepayment->getShippingType(
          $googlepayment->shipping_display[$i]);
      $data_arr = array();
      $handling = 0;
      $table_mode = '';

      for($j=0; $j < $num_rows; $j++)  {
        $data_arr[$check_query->fields['configuration_key']]=
            $check_query->fields['configuration_value'];
        $check_query->MoveNext();
      }
      $common_string = "MODULE_SHIPPING_".$curr_ship."_";
      $zone = $data_arr[$common_string."ZONE"]; 	
      $enable = $data_arr[$common_string."STATUS"];
      $curr_tax_class = $data_arr[$common_string."TAX_CLASS"];
      $price = $data_arr[$common_string."COST"];
      $handling = $data_arr[$common_string."HANDLING"];
      $table_mode = $data_arr[$common_string."MODE"];
      $price = $googlepayment->getShippingPrice(
          $googlepayment->shipping_display[$i], $cart, 
          $price, $handling, $table_mode);

      if($zone != '') {
        $zone_answer = $db->Execute("select countries_name, zone_code 
                                     from " . TABLE_GEO_ZONES . " as gz ,
                                     " . TABLE_ZONES_TO_GEO_ZONES . " as ztgz,
                                     " . TABLE_ZONES . " as z, ". TABLE_COUNTRIES . " as c 
                                     where gz.geo_zone_id = " . $zone. " 
                                     and gz.geo_zone_id = ztgz.geo_zone_id 
                                     and ztgz.zone_id = z.zone_id 
                                     and z.zone_country_id = c.countries_id ");
        $allowed_restriction_state = $zone_answer->fields['zone_code'];
        $allowed_restriction_country = $zone_answer->fields['countries_name'];
      }
      if($enable == "True") {
        if($curr_tax_class != 0 && $curr_tax_class != '') {
          $tax_class[] = $curr_tax_class;
            if(!in_array($curr_tax_class, $tax_class_unique))
              $tax_class_unique[] = $curr_tax_class;  	
        } 
        $gcheck->push('flat-rate-shipping', array('name' => $name));
        $gcheck->element('price', $price, array('currency' => 'USD'));
        $gcheck->push('shipping-restrictions');
        $gcheck->push('allowed-areas');
        if($allowed_restriction_country == '')
          $gcheck->element('us-country-area','', array('country-area' => 'ALL'));
        else { 
          $gcheck->push('us-state-area');
          $gcheck->element('state', $allowed_restriction_state);
          $gcheck->pop('us-state-area');
        }
        $gcheck->pop('allowed-areas');
        $gcheck->pop('shipping-restrictions');
        $gcheck->pop('flat-rate-shipping');
      }
    }
  }
  $gcheck->pop('shipping-methods');

  //Tax options	
  $gcheck->push('tax-tables');
  $gcheck->push('default-tax-table');
  $gcheck->push('tax-rules');

  if(sizeof($tax_class_unique) == 1  && 
      sizeof($options) == sizeof($tax_class)) {
    $tax_result =  $db->Execute("select countries_name, zone_code, tax_rate 
                                 from " . TABLE_TAX_RATES . " as tr, 
                                 " . TABLE_ZONES_TO_GEO_ZONES . " as ztgz, 
                                 " . TABLE_ZONES . " as z, 
                                 " . TABLE_COUNTRIES . " as c 
                                 where tr.tax_class_id= " . $tax_class_unique[0] . " 
                                 and tr.tax_zone_id = ztgz.geo_zone_id 
                                 and ztgz.zone_id=z.zone_id 
                                 and ztgz.zone_country_id = c.countries_id");
    $num_rows = $tax_result->RecordCount();
    $tax_rule = array();

    for($j=0; $j<$num_rows; $j++) {
      $tax_result->MoveNext();
      $rate = ((double) ($tax_result->fields['tax_rate']))/100.0;

      $gcheck->push('default-tax-rule');			
      $gcheck->element('shipping-taxed', 'true');
      $gcheck->element('rate', $rate);
      $gcheck->push('tax-area');			
      $gcheck->push('us-state-area');
      $gcheck->element('state', $tax_result->fields['zone_code']);
      $gcheck->pop('us-state-area');			
      $gcheck->pop('tax-area');			
      $gcheck->pop('default-tax-rule');			
    }
  } else {
    $gcheck->push('default-tax-rule');			
    $gcheck->element('rate', 0);
    $gcheck->push('tax-area');			
    $gcheck->element('us-country-area','', array('country-area'=>'ALL'));
    $gcheck->pop('tax-area');			
    $gcheck->pop('default-tax-rule');			
  }
  $gcheck->pop('tax-rules');
  $gcheck->pop('default-tax-table');

  if(sizeof($tax_class_unique) > 1 || 
    (sizeof($tax_class_unique) == 1 && 
     sizeof($options) != sizeof($tax_class) ))  {
    $googlepayment->variant = "disabled";	
    $current_checkout_url = selfURL();
  }
	
  $i=0;
  $tax_tables = array();
  $gcheck->push('alternate-tax-tables');
	
  foreach($tax_array as $tax_table)  {
    $tax_result =  $db->Execute("select countries_name, zone_code, tax_rate 
                                 from " . TABLE_TAX_RATES . " as tr, 
                                 " . TABLE_ZONES_TO_GEO_ZONES . " as ztgz, 
                                 " . TABLE_ZONES . " as z, 
                                 " . TABLE_COUNTRIES . " as c 
                                 where tr.tax_class_id= " . $tax_array[$i]. " 
                                 and tr.tax_zone_id = ztgz.geo_zone_id 
                                 and ztgz.zone_id=z.zone_id and 
                                 ztgz.zone_country_id = c.countries_id");	
    $num_rows = $tax_result->RecordCount();
    $tax_rule = array();

    $gcheck->push('alternate-tax-table',array('name' => $tax_name_array[$i]));
    $gcheck->push('alternate-tax-rules');
    for($j=0; $j<$num_rows; $j++) {
      $tax_result->MoveNext();
      $rate = ((double)($tax_result->fields['tax_rate']))/100.0;
      $gcheck->push('alternate-tax-rule');			
      $gcheck->element('rate', $rate);
      $gcheck->push('tax-area');			
      $gcheck->push('us-state-area');
      $gcheck->element('state', $tax_result->fields['zone_code']);
      $gcheck->pop('us-state-area');			
      $gcheck->pop('tax-area');			
      $gcheck->pop('alternate-tax-rule');			
    }
    $gcheck->pop('alternate-tax-rules');
    $gcheck->pop('alternate-tax-table');
    $i++;
  }
  $gcheck->pop('alternate-tax-tables');
  $gcheck->pop('tax-tables');

  $gcheck->pop('merchant-checkout-flow-support');
  $gcheck->pop('checkout-flow-support');
  $gcheck->pop('checkout-shopping-cart');
	
?>

<table border="0" width="98%" cellspacing="1" cellpadding ="1"> 
<tr><br>
<td align="right" valign="middle" class = "main">
 <B><?php echo MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_OPTION ?> </B>
</td></tr>
</table> 
  
<table  border="0" width="100%" class="table-1" cellspacing="1" cellpadding="1"> 
  <!-- Print Error message if the shopping cart XML is invalid -->

  <!-- Print the Google Checkout button in a form containing the shopping cart data -->
  <tr><td align="right" valign="middle" class = "main">
    <p><form method="POST" action="<?php echo $current_checkout_url; ?>">
     <input type="hidden" name="cart" value="<?php echo base64_encode($gcheck->getXml());?>">
     <input type="hidden" name="signature" value="<?php echo base64_encode($googlepayment->CalcHmacSha1($gcheck->getXml()));?>"> 
	   <input type="image" name="Checkout" alt="Checkout" 
            src="<?php echo $googlepayment->mode;?>buttons/checkout.gif?merchant_id=<? echo $googlepayment->merchantid;?>&w=180&h=46&style=white&variant=<? echo $googlepayment->variant;?>&loc=en_US" 
            height="46" width="180">
        </form></p>
    </td></tr>
</table>

<!-- ** END GOOGLE CHECKOUT ** -->