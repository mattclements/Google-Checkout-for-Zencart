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

/* **GOOGLE CHECKOUT ** v1.4.1
 * @version $Id: responsehandler.php 6942 2007-08-24 14:58:57Z ropu $
 * Script invoked for any callback notfications from the Checkout server
 * Can be used to process new order notifications, order state changes and risk notifications
 */

// 1. Setup the log file
// 2. Parse the http header to verify the source
// 3. Parse the XML message
// 4. Trasfer control to appropriate function
error_reporting(E_ALL);

// temporal disable of multisocket 
define('MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET', 'False');


define('GC_STATE_NEW', 100);
define('GC_STATE_PROCESSING', 101);
define('GC_STATE_SHIPPED', 102);
define('GC_STATE_REFUNDED', 103);
define('GC_STATE_SHIPPED_REFUNDED', 104);
define('GC_STATE_CANCELED', 105);

chdir('./..');
$curr_dir = getcwd();
define('API_CALLBACK_ERROR_LOG', $curr_dir . "/googlecheckout/logs/response_error.log");
define('API_CALLBACK_MESSAGE_LOG', $curr_dir . "/googlecheckout/logs/response_message.log");

require_once ($curr_dir . '/googlecheckout/library/googlemerchantcalculations.php');
require_once ($curr_dir . '/googlecheckout/library/googleresult.php');
require_once ($curr_dir . '/googlecheckout/library/googlerequest.php');
require_once ($curr_dir . '/googlecheckout/library/googleresponse.php');

$Gresponse = new GoogleResponse();
//Setup the log files
$Gresponse->SetLogFiles(API_CALLBACK_ERROR_LOG, API_CALLBACK_MESSAGE_LOG, L_ALL);

// Retrieve the XML sent in the HTTP POST request to the ResponseHandler
$xml_response = isset($HTTP_RAW_POST_DATA)?
                    $HTTP_RAW_POST_DATA:file_get_contents("php://input");
if (get_magic_quotes_gpc()) {
  $xml_response = stripslashes($xml_response);
}
list ($root, $data) = $Gresponse->GetParsedXML($xml_response);
if (isset ($data[$root]['shopping-cart']['merchant-private-data']['session-data']['VALUE'])) {
  list ($sess_id, $sess_name) = 
      explode(";", $data[$root]['shopping-cart']['merchant-private-data']['session-data']['VALUE']);
  //If session management is supported by this PHP version
  if (function_exists('session_id'))
    session_id($sess_id);
  if (function_exists('session_name'))
    session_name($sess_name);
}
include ('includes/application_top.php');
include ('includes/modules/payment/googlecheckout.php');

//BOF - define value for languages_id//define home page  - added by colosports
$attributes = $db->Execute("select languages_id
                                      from " . TABLE_LANGUAGES . "
                                      where name = '" . $_SESSION['language'] . "'
                                      ");
$languages_id = $attributes->fields['languages_id'];
//EOF - define value for languages_id//define home page  - added by colosports

//  zen_session_start();
if (isset ($_SESSION['cart']) && is_object($_SESSION['cart'])) {
  $cart = $_SESSION['cart'];
  $cart->restore_contents();
} else {
  $Gresponse->SendServerErrorStatus("Shopping cart not obtained from session.");
}
$googlepayment = new googlecheckout();
$Gresponse->SetMerchantAuthentication($googlepayment->merchantid, $googlepayment->merchantkey);

// Check if is CGI install, if so .htaccess is needed
if (MODULE_PAYMENT_GOOGLECHECKOUT_CGI != 'True') {
  $Gresponse->HttpAuthentication();
}
switch ($root) {
  case "request-received": {
      process_request_received_response($Gresponse);
      break;
    }
  case "error": {
      process_error_response($Gresponse);
      break;
    }
  case "diagnosis": {
      process_diagnosis_response($Gresponse);
      break;
    }
  case "checkout-redirect": {
      process_checkout_redirect($Gresponse);
      break;
    }
  case "merchant-calculation-callback" :
    {
//      if (MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET == 'True') {
//        include_once ($curr_dir . '/googlecheckout/multisocket.php');
//        process_merchant_calculation_callback($Gresponse, 2.7, false);
//        break;
//      }
//    }
//  case "merchant-calculation-callback-single" :
//    {
      // 			set_time_limit(5); 
      process_merchant_calculation_callback_single($Gresponse);
      break;
    }
  case "new-order-notification" :
    {
      //	    $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEGIN');
      /*
       * 1. check if the users email exists
       *    1.a if not, create the user, and log in
       * 2. Check if exists as a GC user
       *    2.aAdd it the the google_checkout table to match buyer_id customer_id
       * 
       * 2. add the order to the logged user
       * 
       */
//    Check if the order was already processed
      $google_order = $db->Execute("select orders_id ".
                                " from " . $googlepayment->table_order . " " .
                                " where google_order_number = " . 
                                $data[$root]['google-order-number']['VALUE'] );
      if($google_order->RecordCount() != 0) {
//       Order already processed, send ACK http 200 to avoid notification resend
        $Gresponse->log->logError(sprintf(GOOGLECHECKOUT_ERR_DUPLICATED_ORDER,
                                   $data[$root]['google-order-number']['VALUE'],
                                   $google_order->fields['orders_id']));
        $Gresponse->SendAck(); 
      }
//    Check if the email exists
      $customer_exists = $db->Execute("select customers_id from " .
      TABLE_CUSTOMERS . " where customers_email_address = '" .
      makeSqlString($data[$root]['buyer-billing-address']['email']['VALUE']) . "'");

//    Check if the GC buyer id exists
      $customer_info = $db->Execute("select gct.customers_id from " .
          $googlepayment->table_name . " gct " .
          " inner join " .TABLE_CUSTOMERS . " tc on gct.customers_id = tc.customers_id ".
          " where gct.buyer_id = " .
          makeSqlString($data[$root]['buyer-id']['VALUE']));

      $new_user = false;
//    Ignore session to avoid mix of Cart-GC sessions/emails
//    GC email is the most important one
//    if ((isset($_SESSION['customer_id']) && $_SESSION['customer_id'] != '')
//                                    || $customer_exists->RecordCount() != 0) {
      if ($customer_exists->RecordCount() != 0) {
        $_SESSION['customer_id'] = $customer_exists->fields['customers_id'];
      }
      else if($customer_info->RecordCount() != 0){
        $_SESSION['customer_id'] = $customer_info->fields['customers_id'];
      }
      else {
        list ($firstname, $lastname) = 
            explode(' ', makeSqlString($data[$root]['buyer-billing-address']['contact-name']['VALUE']), 2);
        $sql_data_array = array (
          'customers_firstname' => $firstname,
          'customers_lastname' => $lastname,
          'customers_email_address' => $data[$root]['buyer-billing-address']['email']['VALUE'],
          'customers_nick' => '',
          'customers_telephone' => $data[$root]['buyer-billing-address']['phone']['VALUE'],
          'customers_fax' => $data[$root]['buyer-billing-address']['fax']['VALUE'],
          'customers_default_address_id' => 0,
          'customers_password' => zen_encrypt_password(makeSqlString($data[$root]['buyer-id']['VALUE'])),
          'customers_newsletter' => $data[$root]['buyer-marketing-preferences']['email-allowed']['VALUE']=='true'?1:0
        );
        if (ACCOUNT_DOB == 'true') {
          $sql_data_array['customers_dob'] = 'now()';
        }
        zen_db_perform(TABLE_CUSTOMERS, $sql_data_array);
        $_SESSION['customer_id'] = $db->Insert_ID();
        $db->Execute("insert into " . TABLE_CUSTOMERS_INFO . "
                                      (customers_info_id, customers_info_number_of_logons,
                                       customers_info_date_account_created)
                                 values ('" . (int) $_SESSION['customer_id'] . "', '0', now())");
        $db->Execute("insert into " . $googlepayment->table_name . " " .
                      " values ( " . $_SESSION['customer_id'] . ", " .
                      $data[$root]['buyer-id']['VALUE'] . ")");
        $new_user = true;
      }
      //      The user exists and is logged in
      //      Check database to see if the address exist.
      $address_book = $db->Execute("select address_book_id, entry_country_id, entry_zone_id from " . TABLE_ADDRESS_BOOK . "
                      where  customers_id = '" . $_SESSION['customer_id'] . "'
                        and entry_street_address = '" . makeSqlString($data[$root]['buyer-shipping-address']['address1']['VALUE']) . "'
                          and entry_suburb = '" . makeSqlString($data[$root]['buyer-shipping-address']['address2']['VALUE']) . "'
                          and entry_postcode = '" . makeSqlString($data[$root]['buyer-shipping-address']['postal-code']['VALUE']) . "'
                          and entry_city = '" . makeSqlString($data[$root]['buyer-shipping-address']['city']['VALUE']) . "'
                        ");
      //      If not, add the addr as default one
      if ($address_book->RecordCount() == 0) {
        $buyer_state = $data[$root]['buyer-shipping-address']['region']['VALUE'];
        $zone_answer = $db->Execute("select zone_id, zone_country_id from " .
        TABLE_ZONES . " where zone_code = '" . $buyer_state . "'");
        list ($firstname, $lastname) = 
            explode(' ', makeSqlString($data[$root]['buyer-shipping-address']['contact-name']['VALUE']), 2);
        $sql_data_array = array (
          'customers_id' => $_SESSION['customer_id'],
          'entry_gender' => '',
          'entry_company' => $data[$root]['buyer-shipping-address']['company-name']['VALUE'],
          'entry_firstname' => $firstname,
          'entry_lastname' => $lastname,
          'entry_street_address' => $data[$root]['buyer-shipping-address']['address1']['VALUE'],
          'entry_suburb' => $data[$root]['buyer-shipping-address']['address2']['VALUE'],
          'entry_postcode' => $data[$root]['buyer-shipping-address']['postal-code']['VALUE'],
          'entry_city' => $data[$root]['buyer-shipping-address']['city']['VALUE'],
          'entry_state' => $buyer_state,
          'entry_country_id' => $zone_answer->fields['zone_country_id'],
          'entry_zone_id' => $zone_answer->fields['zone_id']
        );
        zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

        $address_id = $db->Insert_ID();
        $db->Execute("update " . TABLE_CUSTOMERS . "
                                  set customers_default_address_id = '" . (int) $address_id . "'
                                  where customers_id = '" . (int) $_SESSION['customer_id'] . "'");
        $_SESSION['customer_default_address_id'] = $address_id;
        $_SESSION['customer_country_id'] = $zone_answer->fields['zone_country_id'];
        $_SESSION['customer_zone_id'] = $zone_answer->fields['zone_id'];
      } else {
        $_SESSION['customer_default_address_id'] = $address_book->fields['address_book_id'];
        $_SESSION['customer_country_id'] = $address_book->fields['entry_country_id'];
        $_SESSION['customer_zone_id'] = $address_book->fields['entry_zone_id'];
      }
      $_SESSION['customer_first_name'] = $data[$root]['buyer-billing-address']['contact-name']['VALUE'];

      if (isset ($data[$root]['order-adjustment']['shipping']['merchant-calculated-shipping-adjustment']['shipping-name']['VALUE'])) {
        $shipping = $data[$root]['order-adjustment']['shipping']['merchant-calculated-shipping-adjustment']['shipping-name']['VALUE'];
        $ship_cost = $data[$root]['order-adjustment']['shipping']['merchant-calculated-shipping-adjustment']['shipping-cost']['VALUE'];
        $methods_hash = $googlepayment->getMethods();
        list ($a, $method_name) = explode(': ', $shipping, 2);
        $shipping_name = $methods_hash[$method_name][0];//name
        $shipping_code = $methods_hash[$method_name][2];//code
      } else if (isset ($data[$root]['order-adjustment']['shipping']['flat-rate-shipping-adjustment']['shipping-name']['VALUE'])) {
        $shipping = $data[$root]['order-adjustment']['shipping']['flat-rate-shipping-adjustment']['shipping-name']['VALUE'];
        $ship_cost = $data[$root]['order-adjustment']['shipping']['flat-rate-shipping-adjustment']['shipping-cost']['VALUE'];
        $methods_hash = $googlepayment->getMethods();
        list ($a, $method_name) = explode(': ', $shipping, 2);
        $shipping_name = $methods_hash[$method_name][0];//name
        $shipping_code = $methods_hash[$method_name][2];//code
      } else if (isset ($data[$root]['order-adjustment']['shipping']['carrier-calculated-shipping-adjustment']['shipping-name']['VALUE'])) {
        $shipping = $data[$root]['order-adjustment']['shipping']['carrier-calculated-shipping-adjustment']['shipping-name']['VALUE'];
        $ship_cost = $data[$root]['order-adjustment']['shipping']['carrier-calculated-shipping-adjustment']['shipping-cost']['VALUE'];
        $shipping_name = $shipping;
        $shipping_code = 'GCCarrierCalculated';//code
      } else {
        $shipping = 'GC Digital Delivery';
        $ship_cost = 0;
        $shipping_name = $shipping;//name
        $shipping_code = 'FreeGCDigital';//code
      }
      $tax_amt = $data[$root]['order-adjustment']['total-tax']['VALUE'];
      //      $order_total = $data[$root]['order-total']['VALUE'];

      require (DIR_WS_CLASSES . 'order.php');
      $order = new order();
      // load the selected shipping module
      //    Set up order info
      $payment_method = $googlepayment->title;
      if(MODULE_PAYMENT_GOOGLECHECKOUT_MODE=='https://sandbox.google.com/checkout/'){
        $payment_method .= " - <font color=red>SANDBOX</font>";
      }
      list ($order->customer['firstname'], $order->customer['lastname']) =
          explode(' ', $data[$root]['buyer-billing-address']['contact-name']['VALUE'], 2);
      $order->customer['company'] = $data[$root]['buyer-billing-address']['company-name']['VALUE'];
      $order->customer['street_address'] = $data[$root]['buyer-billing-address']['address1']['VALUE'];
      $order->customer['suburb'] = $data[$root]['buyer-billing-address']['address2']['VALUE'];
      $order->customer['city'] = $data[$root]['buyer-billing-address']['city']['VALUE'];
      $order->customer['postcode'] = $data[$root]['buyer-billing-address']['postal-code']['VALUE'];
      $order->customer['state'] = $data[$root]['buyer-billing-address']['region']['VALUE'];
      $order->customer['country']['title'] = $data[$root]['buyer-billing-address']['country-code']['VALUE'];
      $order->customer['telephone'] = $data[$root]['buyer-billing-address']['phone']['VALUE'];
      $order->customer['email_address'] = $data[$root]['buyer-billing-address']['email']['VALUE'];
      $order->customer['format_id'] = 2;
      list ($order->delivery['firstname'], $order->delivery['lastname']) = 
          explode(' ', $data[$root]['buyer-shipping-address']['contact-name']['VALUE'], 2);
      $order->delivery['company'] = $data[$root]['buyer-shipping-address']['company-name']['VALUE'];
      $order->delivery['street_address'] = $data[$root]['buyer-shipping-address']['address1']['VALUE'];
      $order->delivery['suburb'] = $data[$root]['buyer-shipping-address']['address2']['VALUE'];
      $order->delivery['city'] = $data[$root]['buyer-shipping-address']['city']['VALUE'];
      $order->delivery['postcode'] = $data[$root]['buyer-shipping-address']['postal-code']['VALUE'];
      $order->delivery['state'] = $data[$root]['buyer-shipping-address']['region']['VALUE'];
      $order->delivery['country']['title'] = $data[$root]['buyer-shipping-address']['country-code']['VALUE'];
      $order->delivery['format_id'] = 2;
      list ($order->billing['firstname'], $order->billing['lastname']) = 
          explode(' ', $data[$root]['buyer-billing-address']['contact-name']['VALUE'], 2);
      $order->billing['company'] = $data[$root]['buyer-billing-address']['company-name']['VALUE'];
      $order->billing['street_address'] = $data[$root]['buyer-billing-address']['address1']['VALUE'];
      $order->billing['suburb'] = $data[$root]['buyer-billing-address']['address2']['VALUE'];
      $order->billing['city'] = $data[$root]['buyer-billing-address']['city']['VALUE'];
      $order->billing['postcode'] = $data[$root]['buyer-billing-address']['postal-code']['VALUE'];
      $order->billing['state'] = $data[$root]['buyer-billing-address']['region']['VALUE'];
      $order->billing['country']['title'] = $data[$root]['buyer-billing-address']['country-code']['VALUE'];
      $order->billing['format_id'] = 2;
      $order->info['payment_method'] = $payment_method;
      $order->info['payment_module_code'] = $googlepayment->code;
      $order->info['shipping_method'] = $shipping_name;
      $order->info['shipping_module_code'] = $shipping_code;
      $order->info['cc_type'] = '';
      $order->info['cc_owner'] = '';
      $order->info['cc_number'] = '';
      $order->info['cc_expires'] = '';
      $order->info['order_status'] = GC_STATE_NEW;
      $order->info['tax'] = $tax_amt;
      $order->info['currency'] = $data[$root]['order-total']['currency'];
      $order->info['currency_value'] = 1;
      $_SESSION['customers_ip_address'] = $data[$root]['shopping-cart']['merchant-private-data']['ip-address']['VALUE'];
      $order->info['comments'] = GOOGLECHECKOUT_STATE_NEW_ORDER_NUM .
        $data[$root]['google-order-number']['VALUE'] . "\n" .
        GOOGLECHECKOUT_STATE_NEW_ORDER_MC_USED .
        ((@$data[$root]['order-adjustment']['merchant-calculation-successful']['VALUE'] == 'true')?'True':'False') .
        ($new_user ? ("\n" . GOOGLECHECKOUT_STATE_NEW_ORDER_BUYER_USER .
        $data[$root]['buyer-billing-address']['email']['VALUE'] . "\n" .
        GOOGLECHECKOUT_STATE_NEW_ORDER_BUYER_PASS . $data[$root]['buyer-id']['VALUE']):'');

      $coupons = get_arr_result(@$data[$root]['order-adjustment']['merchant-codes']['coupon-adjustment']);
//      $gift_cert = get_arr_result(@$data[$root]['order-adjustment']['merchant-codes']['gift-certificate-adjustment']);
      $items = get_arr_result($data[$root]['shopping-cart']['items']['item']);

      // Get Coustoms OT
      $ot_customs_total = 0;
      $ot_customs = array ();
      $order->products = array ();
      foreach ($items as $item) {
        if (isset ($item['merchant-private-item-data']['item']['VALUE'])) {
          $order->products[] = unserialize(base64_decode($item['merchant-private-item-data']['item']['VALUE']));
        } else
          if ($item['merchant-private-item-data']['order_total']['VALUE']) {
            $ot = unserialize(base64_decode($item['merchant-private-item-data']['order_total']['VALUE']));
            $ot_customs[] = $ot;
            $ot_value = $ot['value'] * (strrpos($ot['text'], '-') === false ? 1 : -1);
            $ot_customs_total += $currencies->get_value($data[$root]['order-total']['currency']) * $ot_value;
          } else {
            // For Invoices!
            // Happy BDay ropu, 07/03
            $order->products[] = array (
              'qty' => $item['quantity']['VALUE'],
              'name' => $item['item-name']['VALUE'],
              'model' => $item['item-description']['VALUE'],
              'tax' => 0,
              'tax_description' => @$item['tax-table-selector']['VALUE'],
              'price' => $item['unit-price']['VALUE'],
              'final_price' => $item['unit-price']['VALUE'],
              'onetime_charges' => 0,
              'weight' => 0,
              'products_priced_by_attribute' => 0,
              'product_is_free' => 0,
              'products_discount_type' => 0,
              'products_discount_type_from' => 0,
              'id' => @$item['merchant-item-id']['VALUE']
            );
          }
      }

      // Update values so that order_total modules get the correct values
      $order->info['total'] = $data[$root]['order-total']['VALUE'];
      $order->info['subtotal'] = $data[$root]['order-total']['VALUE'] - 
                                ($ship_cost + $tax_amt) + 
                                @$coupons[0]['applied-amount']['VALUE'] - 
                                $ot_customs_total;
      $order->info['coupon_code'] = @$coupons[0]['code']['VALUE'];
      $order->info['shipping_method'] = $shipping;
      $order->info['shipping_cost'] = $ship_cost;
      $order->info['tax_groups']['tax'] = $tax_amt;
      $order->info['currency'] = $data[$root]['order-total']['currency'];
      $order->info['currency_value'] = 1;

      require (DIR_WS_CLASSES . 'order_total.php');
      $order_total_modules = new order_total();
      // Disable OT sent as items in the GC cart
      foreach ($order_total_modules->modules as $ot_code => $order_total) {
        if (!in_array(substr($order_total, 0, strrpos($order_total, '.')), $googlepayment->ot_ignore)) {
          unset ($order_total_modules->modules[$ot_code]);
        }
      }
      $order_totals = $order_total_modules->process();
      //    Not necessary, OT already disabled 
      //      foreach($order_totals as $ot_code => $order_total){
      //        if(!in_array($order_total['code'], $googlepayment->ot_ignore)){
      //          unset($order_totals[$ot_code]);
      //        }
      //      }

      //    Merge all OT
      $order_totals = array_merge($order_totals, $ot_customs);
      if (isset ($data[$root]['order-adjustment']['merchant-codes']['coupon-adjustment'])) {
        $order_totals[] = array (
          'code' => 'ot_coupon',
          'title' => "<b>" . MODULE_ORDER_TOTAL_COUPON_TITLE .
          " " . @$coupons[0]['code']['VALUE'] . ":</b>",
          'text' => $currencies->format(@$coupons[0]['applied-amount']['VALUE']*-1, 
                        false,@$coupons[0]['applied-amount']['currency'])
          ,
          'value' => @$coupons[0]['applied-amount']['VALUE'],
          'sort_order' => 280
        );
      }

      function OT_cmp($a, $b) {
        if ($a['sort_order'] == $b['sort_order'])
          return 0;
        return ($a['sort_order'] < $b['sort_order']) ? -1 : 1;
      }
      usort($order_totals, "OT_cmp");
      // Orders managed by ZC modules
      $insert_id = $order->create($order_totals, 2);
//      $order_total_modules = new order_total();
      // store the product info to the order
      $order->create_add_products($insert_id);
      $_SESSION['order_number_created'] = $insert_id;
      //      Add coupon to redeem track
      if (isset ($data[$root]['order-adjustment']['merchant-codes']['coupon-adjustment'])) {
        $sql = "select coupon_id
                                from " . TABLE_COUPONS . "
                                where coupon_code= :couponCodeEntered
                                and coupon_active='Y'";
        $sql = $db->bindVars($sql, ':couponCodeEntered', $coupons[0]['code']['VALUE'], 'string');

        $coupon_result = $db->Execute($sql);
//        $_SESSION['cc_id'] = $coupon_result->fields['coupon_id'];
        $cc_id = $coupon_result->fields['coupon_id'];

        $db->Execute("insert into " . TABLE_COUPON_REDEEM_TRACK . "
                                    (coupon_id, redeem_date, redeem_ip, customer_id, order_id)
                                    values ('" . (int) $cc_id . "', now(), '" .
        $data[$root]['shopping-cart']['merchant-private-data']['ip-address']['VALUE'] .
        "', '" . (int) $_SESSION['customer_id'] . "', '" . (int) $insert_id . "')");
        $_SESSION['cc_id'] = "";
      }

      //Add the order details to the table
      // This table could be modified to hold the merchant id and key if required
      // so that different mids and mkeys can be used for different orders
      $db->Execute("insert into " . $googlepayment->table_order . " values (" . $insert_id . ", " .
      makeSqlString($data[$root]['google-order-number']['VALUE']) . ", " .
      makeSqlFloat($data[$root]['order-total']['VALUE']) . ")");

      $_SESSION['cart']->reset(TRUE);
      $Gresponse->SendAck();
      break;
    }
  case "order-state-change-notification": {
      process_order_state_change_notification($Gresponse, $googlepayment);
      break;
    }
  case "charge-amount-notification": {
      process_charge_amount_notification($Gresponse, $googlepayment);
      break;
    }
  case "chargeback-amount-notification": {
      process_chargeback_amount_notification($Gresponse);
      break;
    }
  case "refund-amount-notification": {
  process_refund_amount_notification($Gresponse, $googlepayment);
      break;
    }
  case "risk-information-notification": {
      process_risk_information_notification($Gresponse, $googlepayment);
      break;
    }
  default: {
      $Gresponse->SendBadRequestStatus("Invalid or not supported Message");
      break;
    }
}
exit (0);

function process_request_received_response($Gresponse) {
}
function process_error_response($Gresponse) {
}
function process_diagnosis_response($Gresponse) {
}
function process_checkout_redirect($Gresponse) {
}

function calculate_coupons($Gresponse, & $merchant_result, $price = 0) {
  global $order, $db, $googlepayment;
  list ($root, $data) = $Gresponse->GetParsedXML();
  require_once (DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_general.php');
  $currencies = new currencies();
  require_once (DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'] . '/discount_coupon.php');
  $codes = get_arr_result($data[$root]['calculate']['merchant-code-strings']['merchant-code-string']);
  //print_r($codes);

  $customer_exists = $db->Execute("select customers_id from " .
      $googlepayment->table_name . " where buyer_id = " .
      makeSqlString($data[$root]['buyer-id']['VALUE']));
  if ($customer_exists->RecordCount() != 0) {
    $customer_id = $customer_exists->fields['customers_id'];
  }
  $first_coupon = true;
  foreach ($codes as $curr_code) {
    $text_coupon_help = '';

    //Update this data as required to set whether the coupon is valid, the code and the amount
    // Check for valid zone...   
    $sql = "select coupon_id, coupon_amount, coupon_type, coupon_minimum_order, uses_per_coupon, uses_per_user,
                      restrict_to_products, restrict_to_categories, coupon_zone_restriction, coupon_code
                      from " . TABLE_COUPONS . "
                      where coupon_code= '" . zen_db_input($curr_code['code']) . "'
                      and coupon_active='Y'";
    //      $sql = $db->bindVars($sql, ':couponIDEntered', , 'string');

    $coupon_result = $db->Execute($sql);
    $foundvalid = true;
    $check_flag = false;
    $check = $db->Execute("select zone_id, zone_country_id from " .
    TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" .
    $coupon_result->fields['coupon_zone_restriction'] . "' and zone_country_id = '" .
    $order->delivery['country']['id'] . "' order by zone_id");

    if ($coupon_result->fields['coupon_zone_restriction'] > 0) {
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        }
        elseif ($check->fields['zone_id'] == $order->delivery['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }
      $foundvalid = $check_flag;
    }
    $coupon_count = $db->Execute("select coupon_id from " . TABLE_COUPON_REDEEM_TRACK . "
                                    where coupon_id = '" . (int)$coupon_result->fields['coupon_id']."'");

    $coupon_count_customer = $db->Execute("select coupon_id from " . TABLE_COUPON_REDEEM_TRACK . "
                                           where coupon_id = '" . $coupon_result->fields['coupon_id']."' and
                                           customer_id = '" . (int)$customer_id . "'");

    //  added code here to handle coupon product restrictions
    // look through the items in the cart to see if this coupon is valid for any item in the cart
//    $items = get_arr_result($data[$root]['shopping-cart']['items']['item']);
//    $products = array ();
//    foreach ($items as $item) {
//      if (isset ($item['merchant-private-item-data']['item']['VALUE'])) {
//        $products[] = unserialize(base64_decode($item['merchant-private-item-data']['item']['VALUE']));
//      }
//    }
    if ($foundvalid == true) {
      $foundvalid = false;
      $products = $order->products;
      for ($i = 0; $i < sizeof($products); $i++) {
        if (is_product_valid($products[$i]['id'], $coupon_result->fields['coupon_id'])) {
          $foundvalid = true;
          continue;
        }
      }
    }

    $coupon = $db->Execute("select * from " . TABLE_COUPONS . " where coupon_code = '" .
    zen_db_input($curr_code['code']) . "' and  coupon_type != 'G'");

    if (!$foundvalid || !$first_coupon || $coupon->RecordCount() < 1) {
      // invalid discount coupon code or more than one entered!
      $text_coupon_help = $first_coupon ? sprintf(TEXT_COUPON_FAILED, $curr_code['code']) : GOOGLECHECKOUT_COUPON_ERR_ONE_COUPON;
      $coupons = new GoogleCoupons("false", $curr_code['code'], 0, $text_coupon_help);
      $merchant_result->AddCoupons($coupons);
      // BBG Start - Invalid discount coupon if coupon minimum order is over 0 and the order total doesn't meet the minimum     
    } else if ($coupon->fields['coupon_minimum_order'] > 0 && $order->info['total'] < $coupon->fields['coupon_minimum_order']) {
        $text_coupon_help = GOOGLECHECKOUT_COUPON_ERR_MIN_PURCHASE;
        $coupons = new GoogleCoupons("false", $curr_code['code'], 0, $text_coupon_help);
        $merchant_result->AddCoupons($coupons);
        // BBG End
    }
    else if ($coupon_count->RecordCount() >= $coupon_result->fields['uses_per_coupon'] && $coupon_result->fields['uses_per_coupon'] > 0) {
      $text_coupon_help = TEXT_INVALID_USES_COUPON . $coupon_result->fields['uses_per_coupon'] . TIMES ;
      $coupons = new GoogleCoupons("false", $curr_code['code'], 0, $text_coupon_help);
      $merchant_result->AddCoupons($coupons);
      
    }
    else if ($coupon_count_customer->RecordCount() >= $coupon_result->fields['uses_per_user'] && $coupon_result->fields['uses_per_user'] > 0) {
      $text_coupon_help = sprintf(TEXT_INVALID_USES_USER_COUPON,  $curr_code['code']) . $coupon_result->fields['uses_per_user'] . ($coupon_result->fields['uses_per_user'] == 1 ? TIME : TIMES);
      $coupons = new GoogleCoupons("false", $curr_code['code'], 0, $text_coupon_help);
      $merchant_result->AddCoupons($coupons);
    }
    else {
      // valid discount coupon code
      $lookup_coupon_id = $coupon->fields['coupon_id'];
      $coupon_desc = $db->Execute("select * from " . TABLE_COUPONS_DESCRIPTION .
      " where coupon_id = '" . (int) $lookup_coupon_id . "' " .
      " and language_id = '" . (int) $_SESSION['languages_id'] . "'");
      $coupon_amount = $coupon->fields['coupon_amount'];
      switch ($coupon->fields['coupon_type']) {
        case 'F' :
          $text_coupon_help = GOOGLECHECKOUT_COUPON_DISCOUNT . $curr_code['code'];
          break;
        case 'P' :
          $text_coupon_help = GOOGLECHECKOUT_COUPON_DISCOUNT . $curr_code['code'];
          $coupon_amount = $coupon_amount * $order->info['total'] / 100;
          break;
        case 'S' :
          $text_coupon_help = GOOGLECHECKOUT_COUPON_FREESHIP . $curr_code['code'];
          $coupon_amount = $price;
          break;
        default :
          }
      $get_result = $db->Execute("select * from " . TABLE_COUPON_RESTRICT . " " .
      "where coupon_id='" . (int) $lookup_coupon_id . "' and category_id !='0'");
      $cats = '';
      while (!$get_result->EOF) {
        if ($get_result->fields['coupon_restrict'] == 'N') {
          $restrict = TEXT_CAT_ALLOWED;
        } else {
          $restrict = TEXT_CAT_DENIED;
        }
        $result = $db->Execute("SELECT * FROM " . TABLE_CATEGORIES . " c, " .
        TABLE_CATEGORIES_DESCRIPTION . " cd WHERE c.categories_id = cd.categories_id " .
        "and cd.language_id = '" . (int) $_SESSION['languages_id'] . "' " .
        "and c.categories_id='" . $get_result->fields['category_id'] . "'");
        $cats .= '<br />' . $result->fields["categories_name"] . $restrict;
        $get_result->MoveNext();
      }
      if ($cats == '')
        $cats = TEXT_NO_CAT_RESTRICTIONS;
      $get_result = $db->Execute("select * from " . TABLE_COUPON_RESTRICT .
      " where coupon_id='" . (int) $lookup_coupon_id . "' and product_id !='0'");

      while (!$get_result->EOF) {
        if ($get_result->fields['coupon_restrict'] == 'N') {
          $restrict = TEXT_PROD_ALLOWED;
        } else {
          $restrict = TEXT_PROD_DENIED;
        }
        $result = $db->Execute("SELECT * FROM " . TABLE_PRODUCTS . " p, " .
        TABLE_PRODUCTS_DESCRIPTION . " pd WHERE p.products_id = pd.products_id " .
        "and pd.language_id = '" . (int) $_SESSION['languages_id'] . "' " .
        "and p.products_id = '" . $get_result->fields['product_id'] . "'");
        $prods .= '<br />' . $result->fields['products_name'] . $restrict;
        $get_result->MoveNext();
      }
      if ($prods == '') {
        $prods = TEXT_NO_PROD_RESTRICTIONS;
      }
      $coupons = new GoogleCoupons("true", $curr_code['code'], $currencies->get_value(DEFAULT_CURRENCY) * $coupon_amount, $text_coupon_help);
      $merchant_result->AddCoupons($coupons);
      $first_coupon = false;
    }
  }
}
function process_merchant_calculation_callback_single($Gresponse) {
  global $googlepayment, $order, $db, $total_weight, $total_count;
  list ($root, $data) = $Gresponse->GetParsedXML();
  $currencies = new currencies();

  $cart = $_SESSION['cart'];
  $methods_hash = $googlepayment->getMethods();
  require (DIR_WS_CLASSES . 'order.php');
  $order = new order;

  // Register a random ID in the session to check throughout the checkout procedure
  // against alterations in the shopping cart contents.
  //  if (!tep_session_is_registered('cartID')) {
  //  tep_session_register('cartID');
  // }
//  $cartID = $cart->cartID;
  $items = get_arr_result($data[$root]['shopping-cart']['items']['item']);
  $products = array ();
  foreach ($items as $item) {
    if (isset ($item['merchant-private-item-data']['item']['VALUE'])) {
      $products[] = unserialize(base64_decode($item['merchant-private-item-data']['item']['VALUE']));
    }
  }
  $order->products = $products;
  $total_weight = $cart->show_weight();
  $total_count = $cart->count_contents();

  // Create the results and send it
  $merchant_calc = new GoogleMerchantCalculations(DEFAULT_CURRENCY);

  // Loop through the list of address ids from the callback.
  $addresses = get_arr_result($data[$root]['calculate']['addresses']['anonymous-address']);
  // Get all the enabled shipping methods.
  require (DIR_WS_CLASSES . 'shipping.php');

  // Required for some shipping methods (ie. USPS).
  require_once ('includes/classes/http_client.php');
  foreach ($addresses as $curr_address) {
    // Set up the order address.
    $curr_id = $curr_address['id'];
    $country = $curr_address['country-code']['VALUE'];
    $city = $curr_address['city']['VALUE'];
    $region = $curr_address['region']['VALUE'];
    $postal_code = $curr_address['postal-code']['VALUE'];
    $countr_query = $db->Execute("select * 
                     from " . TABLE_COUNTRIES . " 
                     where countries_iso_code_2 = '" . makeSqlString($country) . "'");

    $row = $countr_query->fields;
    $order->delivery['country'] = array (
      'id' => $row['countries_id'],
      'title' => $row['countries_name'],
      'iso_code_2' => $country,
      'iso_code_3' => $row['countries_iso_code_3']
    );

    $order->delivery['country_id'] = $row['countries_id'];
    $order->delivery['format_id'] = $row['address_format_id'];

    $zone_query = $db->Execute("select * 
        		                               from " . TABLE_ZONES . "
        		                               where zone_code = '" . makeSqlString($region) . "'");

    $row = $zone_query->fields;
    $order->delivery['zone_id'] = $row['zone_id'];
    $order->delivery['state'] = $row['zone_name'];
    $order->delivery['city'] = $city;
    $order->delivery['postcode'] = $postal_code;
    $shipping_modules = new shipping();

    // Loop through each shipping method if merchant-calculated shipping
    // support is to be provided
    //print_r($data[$root]['calculate']['shipping']['method']);
    if (isset ($data[$root]['calculate']['shipping']['method'])) {
      $shipping = get_arr_result($data[$root]['calculate']['shipping']['method']);

//      if (MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET == 'True') {
//        // Single
//        // i get all the enabled shipping methods  
//        $name = $shipping[0]['name'];
//        //            Compute the price for this shipping method and address id
//        list ($a, $method_name) = explode(': ', $name);
//        if ((($order->delivery['country']['id'] == SHIPPING_ORIGIN_COUNTRY)
//              && ($methods_hash[$method_name][1] == 'domestic_types'))
//           || (($order->delivery['country']['id'] != SHIPPING_ORIGIN_COUNTRY)
//              && ($methods_hash[$method_name][1] == 'international_types'))) {
//          //								reset the shipping class to set the new address
//          if (class_exists($methods_hash[$method_name][2])) {
//            $GLOBALS[$methods_hash[$method_name][2]] = new $methods_hash[$method_name][2];
//          }
//        }
//        $quotes = $shipping_modules->quote('', $methods_hash[$method_name][2]);
//      } else {
        // Standard
        foreach ($shipping as $curr_ship) {
          $name = $curr_ship['name'];
          //            Compute the price for this shipping method and address id
          list ($a, $method_name) = explode(': ', $name, 2);
          if ((($order->delivery['country']['id'] == SHIPPING_ORIGIN_COUNTRY) 
                && ($methods_hash[$method_name][1] == 'domestic_types')) 
              || (($order->delivery['country']['id'] != SHIPPING_ORIGIN_COUNTRY) 
                && ($methods_hash[$method_name][1] == 'international_types'))) {
            //								reset the shipping class to set the new address
            if (class_exists($methods_hash[$method_name][2])) {
              $GLOBALS[$methods_hash[$method_name][2]] = new $methods_hash[$method_name][2];
            }
          }
        }
        $quotes = $shipping_modules->quote();
//      }
      reset($shipping);
      foreach ($shipping as $curr_ship) {
        $name = $curr_ship['name'];
        //            Compute the price for this shipping method and address id
        list ($a, $method_name) = explode(': ', $name, 2);
        unset ($quote_povider);
        unset ($quote_method);
        if ((($order->delivery['country']['id'] == SHIPPING_ORIGIN_COUNTRY) 
            && ($methods_hash[$method_name][1] == 'domestic_types')) 
          || (($order->delivery['country']['id'] != SHIPPING_ORIGIN_COUNTRY) 
            && ($methods_hash[$method_name][1] == 'international_types'))) {
          foreach ($quotes as $key_provider => $shipping_provider) {
            // privider name (class)
            if ($shipping_provider['id'] == $methods_hash[$method_name][2]) {
              // method name			
              $quote_povider = $key_provider;
              if (is_array($shipping_provider['methods']))
                foreach ($shipping_provider['methods'] as $key_method => $shipping_method) {
                  if ($shipping_method['id'] == $methods_hash[$method_name][0]) {
                    $quote_method = $key_method;
                    break;
                  }
                }
              break;
            }
          }
        }
        //if there is a problem with the method, i mark it as non-shippable
        if( isset($quotes[$quote_povider]['error']) ||
            !isset($quotes[$quote_povider]['methods'][$quote_method]['cost'])) {
          $price = "9999.09";
          $shippable = "false";
        } else {
          $price = $quotes[$quote_povider]['methods'][$quote_method]['cost'];
          $shippable = "true";
        }
        // fix for item shipping function bug if called more than once in a session. 
        $price = ($price >= 0 ? $price : 0);
        $merchant_result = new GoogleResult($curr_id);
        $merchant_result->SetShippingDetails($name, $currencies->get_value(DEFAULT_CURRENCY) * $price, $shippable);

        if ($data[$root]['calculate']['tax']['VALUE'] == "true") {
          //Compute tax for this address id and shipping type
          $amount = 15; // Modify this to the actual tax value
          $merchant_result->SetTaxDetails($currencies->get_value(DEFAULT_CURRENCY) * $amount);
        }
        ////							 start cupons and gift processing (working)
        //								// only one coupon per order is valid!
        //                $_POST['dc_redeem_code'] = 'ROPU';
        //
        ////                require(DIR_WS_CLASSES . 'order.php');
        ////                $order = new order;
        //                require_once(DIR_WS_CLASSES . 'order_total.php');
        //                $order_total_modules = new order_total;
        ////                $order_total_modules->collect_posts();
        ////                $order_total_modules->pre_confirmation_check();
        //                
        ////                print_r($order_total_modules);
        //                   $order_totals = $order_total_modules->process();
        ////                print_r($order_totals);
        //                                

        calculate_coupons($Gresponse, $merchant_result, $price);
        // end cupons		            
        $merchant_calc->AddResult($merchant_result);
      }
    } else {
      $merchant_result = new GoogleResult($curr_id);
      if ($data[$root]['calculate']['tax']['VALUE'] == "true") {
        //Compute tax for this address id and shipping type
        $amount = 15; // Modify this to the actual tax value
        $merchant_result->SetTaxDetails($currencies->get_value(DEFAULT_CURRENCY) * $amount);
      }
      calculate_coupons($Gresponse, $merchant_result);
      $merchant_calc->AddResult($merchant_result);
    }
  }
  $Gresponse->ProcessMerchantCalculations($merchant_calc);
}


function process_order_state_change_notification($Gresponse, $googlepayment) {
  global $db;
  list ($root, $data) = $Gresponse->GetParsedXML();
  $new_financial_state = $data[$root]['new-financial-order-state']['VALUE'];
  $new_fulfillment_order = $data[$root]['new-fulfillment-order-state']['VALUE'];

  $previous_financial_state = $data[$root]['previous-financial-order-state']['VALUE'];
  $previous_fulfillment_order = $data[$root]['previous-fulfillment-order-state']['VALUE'];

  $google_order_number = $data[$root]['google-order-number']['VALUE'];
  $google_order = $db->Execute("SELECT orders_id from " .
    "" . $googlepayment->table_order . " where google_order_number = " .
    "'" . makeSqlString($google_order_number) . "'");
  $update = false;
  if ($previous_financial_state != $new_financial_state)
    switch ($new_financial_state) {
      case 'REVIEWING' :
        {
          break;
        }
      case 'CHARGEABLE' :
        {
          $update = true;
          $orders_status_id = GC_STATE_NEW;
          $comments = GOOGLECHECKOUT_STATE_STRING_TIME . $data[$root]['timestamp']['VALUE'] . "\n" .
          GOOGLECHECKOUT_STATE_STRING_NEW_STATE . $new_financial_state . "\n" .
          GOOGLECHECKOUT_STATE_STRING_ORDER_READY_CHARGE;
          $customer_notified = 0;
          break;
        }
      case 'CHARGING' :
        {
          break;
        }
      case 'CHARGED' :
        {
          $update = true;
          $orders_status_id = GC_STATE_PROCESSING;
          $comments = GOOGLECHECKOUT_STATE_STRING_TIME . $data[$root]['timestamp']['VALUE'] . "\n" .
          GOOGLECHECKOUT_STATE_STRING_NEW_STATE . $new_financial_state;
          $customer_notified = 0;
          break;
        }

      case 'PAYMENT-DECLINED' :
        {
          $update = true;
          $orders_status_id = GC_STATE_NEW;
          $comments = GOOGLECHECKOUT_STATE_STRING_TIME . $data[$root]['timestamp']['VALUE'] . "\n" .
          GOOGLECHECKOUT_STATE_STRING_NEW_STATE . $new_financial_state .
          GOOGLECHECKOUT_STATE_STRING_PAYMENT_DECLINED;
          $customer_notified = 1;
          break;
        }
      case 'CANCELLED' :
        {
          $update = true;
          $orders_status_id = GC_STATE_CANCELED;
          $customer_notified = 1;
          $comments = GOOGLECHECKOUT_STATE_STRING_TIME . $data[$root]['timestamp']['VALUE'] . "\n" .
          GOOGLECHECKOUT_STATE_STRING_NEW_STATE . $new_financial_state . "\n" .
          GOOGLECHECKOUT_STATE_STRING_ORDER_CANCELED;
          break;
        }
      case 'CANCELLED_BY_GOOGLE' :
        {
          $update = true;
          $orders_status_id = GC_STATE_CANCELED;
          $comments = GOOGLECHECKOUT_STATE_STRING_TIME . $data[$root]['timestamp']['VALUE'] . "\n" .
          GOOGLECHECKOUT_STATE_STRING_NEW_STATE . $new_financial_state . "\n" .
          GOOGLECHECKOUT_STATE_STRING_ORDER_CANCELED_BY_GOOG;
          $customer_notified = 1;
          break;
        }
      default :
        break;
    }

  if ($update) {
    $sql_data_array = array (
      'orders_id' => $google_order->fields['orders_id'],
      'orders_status_id' => $orders_status_id,
      'date_added' => 'now()',
      'customer_notified' => $customer_notified,
      'comments' => $comments
    );
    zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    $db->Execute("UPDATE " . TABLE_ORDERS . " SET orders_status = " .
    "'" . $orders_status_id . "' WHERE orders_id = " .
    "'" . makeSqlInteger($google_order->fields['orders_id']) . "'");
  }

  $update = false;
  if ($previous_fulfillment_order != $new_fulfillment_order)
    switch ($new_fulfillment_order) {
      case 'NEW' :
        {
          break;
        }
      case 'PROCESSING' :
        {
          $Gresponse->SendAck(false);
          $Grequest = new GoogleRequest($googlepayment->merchantid, 
                                        $googlepayment->merchantkey, 
                                        MODULE_PAYMENT_GOOGLECHECKOUT_MODE==
                                          'https://sandbox.google.com/checkout/'
                                          ?"sandbox":"production",
                                        DEFAULT_CURRENCY);
          $Grequest->SetLogFiles(API_CALLBACK_ERROR_LOG, API_CALLBACK_MESSAGE_LOG);
          $google_answer = $db->Execute("SELECT go.google_order_number, go.order_amount, o.customers_email_address, gc.buyer_id, o.customers_id
                                          FROM " . $googlepayment->table_order . " go 
                                          inner join " . TABLE_ORDERS . " o on go.orders_id = o.orders_id
                                          inner join " . $googlepayment->table_name . " gc on gc.customers_id = o.customers_id
                                          WHERE go.orders_id = '" . (int)$google_order->fields['orders_id'] ."'
                                          group by o.customers_id order by o.orders_id desc");
    
          $first_order = $db->Execute("SELECT customers_id, count(*) cant_orders
                                        FROM  " . TABLE_ORDERS . " 
                                        WHERE customers_id = '".$google_answer->fields['customers_id']."'
                                        group by customers_id");
    // Send buyers email and password if new user and first buy with GC in the site
          if($first_order->fields['cant_orders'] == 1) {
            list($status,) = $Grequest->sendBuyerMessage($google_answer->fields['google_order_number'],
                                      sprintf(GOOGLECHECKOUT_NEW_CREDENTIALS_MESSAGE,
                                              STORE_NAME,
                                              $google_answer->fields['customers_email_address'], 
                                              $google_answer->fields['buyer_id']), "true", 2);

            $comments = GOOGLECHECKOUT_STATE_STRING_TIME . $data[$root]['timestamp']['VALUE'] . "\n" .
            GOOGLECHECKOUT_STATE_STRING_NEW_STATE . $new_fulfillment_order. "\n";
    
            if($status != 200) {
              $comments .= "\n" . GOOGLECHECKOUT_ERR_SEND_NEW_USER_CREDENTIALS . "\n";
              $customer_notified = '0';
            }
            else {
              $comments .= GOOGLECHECKOUT_SUCCESS_SEND_NEW_USER_CREDENTIALS . "\n";
              $customer_notified = '1';
            }
            $comments .=  "Messsage:\n" . sprintf(GOOGLECHECKOUT_NEW_CREDENTIALS_MESSAGE,
                                    STORE_NAME,
                                    $google_answer->fields['customers_email_address'], 
                                    $google_answer->fields['buyer_id']);
            
            $update = true;
            $orders_status_id = GC_STATE_PROCESSING;
          }
          
          // Tell google witch is the Zencart's internal order Number        
          $Grequest->SendMerchantOrderNumber($google_answer->fields['google_order_number'],
                                             $google_order->fields['orders_id'],
                                             2);
          break;
        }
      case 'DELIVERED' :
        {
          $check_status = $db->Execute("select orders_status from " . TABLE_ORDERS . "
                      where orders_id = '" . $google_order->fields['orders_id'] . "'");

          switch($check_status->fields['orders_status']){
            case GC_STATE_REFUNDED:
              $orders_status_id = GC_STATE_SHIPPED_REFUNDED;
            break;
            case GC_STATE_PROCESSING:
            default;
              $orders_status_id = GC_STATE_SHIPPED;
            break;
          }

          $update = true;
          $comments = GOOGLECHECKOUT_STATE_STRING_TIME . $data[$root]['timestamp']['VALUE'] . "\n" .
          GOOGLECHECKOUT_STATE_STRING_NEW_STATE . $new_fulfillment_order . "\n" .
          GOOGLECHECKOUT_STATE_STRING_ORDER_DELIVERED . "\n";
          $customer_notified = 1;
          break;
        }
      case 'WILL_NOT_DELIVER' :
        {
          $update = false;
          $orders_status_id = GC_STATE_CANCELED;
          $customer_notified = 1;
          $comments = GOOGLECHECKOUT_STATE_STRING_TIME . $data[$root]['timestamp']['VALUE'] . "\n" .
          GOOGLECHECKOUT_STATE_STRING_NEW_STATE . $new_fulfillment_order . "\n" .
          GOOGLECHECKOUT_STATE_STRING_ORDER_CANCELED;
          break;
        }
      default :
        break;
    }

  if ($update) {
    $sql_data_array = array (
      'orders_id' => $google_order->fields['orders_id'],
      'orders_status_id' => $orders_status_id,
      'date_added' => 'now()',
      'customer_notified' => $customer_notified,
      'comments' => $comments
    );
//    print_r($sql_data_array);
    zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    $db->Execute("UPDATE " . TABLE_ORDERS . " SET orders_status = " .
    "'" . $orders_status_id . "' WHERE orders_id = " .
    "'" . makeSqlInteger($google_order->fields['orders_id']) . "'");
  }

  $Gresponse->SendAck();
}
function process_charge_amount_notification($Gresponse, $googlepayment) {
  global $db, $currencies;
  list ($root, $data) = $Gresponse->GetParsedXML();
  $google_order_number = $data[$root]['google-order-number']['VALUE'];
  $google_order = $db->Execute("SELECT orders_id from " .
  "" . $googlepayment->table_order . " where " .
  " google_order_number = '" . makeSqlString($google_order_number) . "'");

  //   fwrite($message_log,sprintf("\n%s\n", $google_order->fields['orders_id']));

  $sql_data_array = array (
    'orders_id' => $google_order->fields['orders_id'],
    'orders_status_id' => GC_STATE_PROCESSING,
    'date_added' => 'now()',
    'customer_notified' => 0,
    'comments' => GOOGLECHECKOUT_STATE_STRING_LATEST_CHARGE .
      $currencies->format($data[$root]['latest-charge-amount']['VALUE'], 
                       false, $data[$root]['latest-charge-amount']['currency']).
    "\n" .
    GOOGLECHECKOUT_STATE_STRING_TOTAL_CHARGE .
      $currencies->format($data[$root]['total-charge-amount']['VALUE'], 
                       false, $data[$root]['total-charge-amount']['currency'])
  );
  zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
  $db->Execute("UPDATE " . TABLE_ORDERS . " SET orders_status = '" . GC_STATE_PROCESSING . "' " .
  "WHERE orders_id = '" . makeSqlInteger($google_order->fields['orders_id']) . "'");
  $Gresponse->SendAck();
}
function process_chargeback_amount_notification($Gresponse) {
  $Gresponse->SendAck();
}
function process_refund_amount_notification($Gresponse, $googlepayment) {
  global $db, $currencies;
  list ($root, $data) = $Gresponse->GetParsedXML();
  $google_order_number = $data[$root]['google-order-number']['VALUE'];
  $google_order = $db->Execute("SELECT orders_id from " .
  "" . $googlepayment->table_order . " where google_order_number = " .
  "'" . makeSqlString($google_order_number) . "'");

  //   fwrite($message_log,sprintf("\n%s\n", $google_order->fields['orders_id']));
  $check_status = $db->Execute("select orders_status from " . TABLE_ORDERS . "
                          where orders_id = '" . $google_order->fields['orders_id'] . "'");

  switch($check_status->fields['orders_status']){
    case GC_STATE_PROCESSING:
    case GC_STATE_REFUNDED:
      $orders_status_id = GC_STATE_REFUNDED;
    break;
    case GC_STATE_SHIPPED:
    case GC_STATE_SHIPPED_REFUNDED:
    default;
      $orders_status_id = GC_STATE_SHIPPED_REFUNDED;
    break;
  }

  $sql_data_array = array (
    'orders_id' => $google_order->fields['orders_id'],
    'orders_status_id' => $orders_status_id,
    'date_added' => 'now()',
    'customer_notified' => 1,
    'comments' => GOOGLECHECKOUT_STATE_STRING_TIME .
    $data[$root]['timestamp']['VALUE'] . "\n" .
    GOOGLECHECKOUT_STATE_STRING_LATEST_REFUND .
      $currencies->format($data[$root]['latest-refund-amount']['VALUE'], 
                 false, $data[$root]['latest-refund-amount']['currency']). "\n".
    GOOGLECHECKOUT_STATE_STRING_TOTAL_REFUND .
      $currencies->format($data[$root]['total-refund-amount']['VALUE'], 
                 false, $data[$root]['total-refund-amount']['currency'])
  );
  zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

  $db->Execute("UPDATE " . TABLE_ORDERS . " SET orders_status = '" . $orders_status_id . "' " .
  "WHERE orders_id = '" . makeSqlInteger($google_order->fields['orders_id']) . "'");


  $sql_data_array = array (
    'orders_id' => $google_order->fields['orders_id'],
    'title' => GOOGLECHECKOUT_STATE_STRING_GOOGLE_REFUND,
    'text' => '<font color="#800000">' .
      $currencies->format($data[$root]['latest-refund-amount']['VALUE'] * -1, 
                 false, $data[$root]['latest-refund-amount']['currency']). "\n".
    '</font>',
    'value' => $data[$root]['latest-refund-amount']['VALUE'],
    'class' => 'ot_goog_refund',
    'sort_order' => 1001
  );

  zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);

  $total = $db->Execute("SELECT orders_total_id, text, value from " .
  "" . TABLE_ORDERS_TOTAL . " where orders_id = " .
  "'" . $google_order->fields['orders_id'] . "' AND class = 'ot_total'");

  $net_rev = $db->Execute("SELECT orders_total_id, text, value from " .
  "" . TABLE_ORDERS_TOTAL . " where orders_id = " .
  "'" . $google_order->fields['orders_id'] . "' AND class = 'ot_goog_net_rev'");

  $sql_data_array = array (
    'orders_id' => $google_order->fields['orders_id'],
    'title' => '<b>' . GOOGLECHECKOUT_STATE_STRING_NET_REVENUE . '</b>',
    'text' => '<b>' .
      $currencies->format(($total->fields['value'] - 
                      ((double) $data[$root]['total-refund-amount']['VALUE'])), 
                 false, $data[$root]['total-refund-amount']['currency']). 
    '</b>', 'value' => ($total->fields['value'] - 
      ((double) $data[$root]['total-refund-amount']['VALUE'])), 
    'class' => 'ot_goog_net_rev',
    'sort_order' => 1010);

  if ($net_rev->RecordCount() == 0) {
    zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
  } else {
    zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array, 'update', "orders_total_id = '" .
    $net_rev->fields['orders_total_id'] . "'");
  }

  $Gresponse->SendAck();
}
function process_risk_information_notification($Gresponse, $googlepayment) {
  global $db;
  list ($root, $data) = $Gresponse->GetParsedXML();
  $google_order_number = $data[$root]['google-order-number']['VALUE'];
  $google_order = $db->Execute("SELECT orders_id from " .
  "" . $googlepayment->table_order . " where google_order_number = " .
  "'" . makeSqlString($google_order_number) . "'");

  //   fwrite($message_log,sprintf("\n%s\n", $google_order->fields['orders_id']));

  $sql_data_array = array (
    'orders_id' => $google_order->fields['orders_id'],
    'orders_status_id' => GC_STATE_NEW,
    'date_added' => 'now()',
    'customer_notified' => 0,
    'comments' => GOOGLECHECKOUT_STATE_STRING_RISK_INFO . "\n" .
    GOOGLECHECKOUT_STATE_STRING_RISK_ELEGIBLE .
    $data[$root]['risk-information']['eligible-for-protection']['VALUE'] . "\n" .
    GOOGLECHECKOUT_STATE_STRING_RISK_AVS .
    $data[$root]['risk-information']['avs-response']['VALUE'] . "\n" .
    GOOGLECHECKOUT_STATE_STRING_RISK_CVN .
    $data[$root]['risk-information']['cvn-response']['VALUE'] . "\n" .
    GOOGLECHECKOUT_STATE_STRING_RISK_CC_NUM .
    $data[$root]['risk-information']['partial-cc-number']['VALUE'] . "\n" .
    GOOGLECHECKOUT_STATE_STRING_RISK_ACC_AGE .
    $data[$root]['risk-information']['buyer-account-age']['VALUE'] . "\n"
  );
  zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
  $db->Execute("UPDATE " . TABLE_ORDERS . " SET orders_status = '" . GC_STATE_NEW . "' " .
  "WHERE orders_id = '" . makeSqlInteger($google_order->fields['orders_id']) . "'");
  $Gresponse->SendAck();
}

//Functions to prevent SQL injection attacks
function makeSqlString($str) {
  return zen_db_input($str);
  //    return addcslashes(stripcslashes($str), "'\"\\\0..\37!@\@\177..\377");
}

function makeSqlInteger($val) {
  return ((settype($val, 'integer')) ? ($val) : 0);
}

function makeSqlFloat($val) {
  return ((settype($val, 'float')) ? ($val) : 0);
}
/* In case the XML API contains multiple open tags
 with the same value, then invoke this function and
 perform a foreach on the resultant array.
 This takes care of cases when there is only one unique tag
 or multiple tags.
 Examples of this are "anonymous-address", "merchant-code-string"
 from the merchant-calculations-callback API
*/
function get_arr_result($child_node) {
  $result = array ();
  if (isset ($child_node)) {
    if (is_associative_array($child_node)) {
      $result[] = $child_node;
    } else {
      foreach ($child_node as $curr_node) {
        $result[] = $curr_node;
      }
    }
  }
  return $result;
}

/* Returns true if a given variable represents an associative array */
function is_associative_array($var) {
  return is_array($var) && !is_numeric(implode('', array_keys($var)));
}
// ** END GOOGLE CHECKOUT **
?>