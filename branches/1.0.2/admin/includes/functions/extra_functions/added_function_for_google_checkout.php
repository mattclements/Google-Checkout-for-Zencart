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

 /* ** GOOGLE CHECKOUT **/
  define('STATE_PENDING', 'Pending');
  define('STATE_PROCESSING', 'Processing');
  define('STATE_DELIVERED', 'Delivered');

 /*
  * Function which posts a request to the specified url.
  * @param url Url where request is to be posted
  * @param merid The merchant ID used for HTTP Basic Authentication
  * @param merkey The merchant key used for HTTP Basic Authentication
  * @param postargs The post arguments to be sent
  * @param message_log An opened log file poitner for appending logs
  */
  function send_google_req($url, $merid, $merkey, $postargs, $message_log) {
    // Get the curl session object
    $session = curl_init($url);

    $header_string_1 = "Authorization: Basic ".base64_encode($merid.':'.$merkey);
    $header_string_2 = "Content-Type: application/xml";
    $header_string_3 = "Accept: application/xml";

    fwrite($message_log, sprintf("\r\n%s %s %s\n",$header_string_1, $header_string_2, $header_string_3));
    // Set the POST options.
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_HTTPHEADER, array($header_string_1, $header_string_2, $header_string_3));
    curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    // Do the POST and then close the session
    $response = curl_exec($session);
    curl_close($session);

    fwrite($message_log, sprintf("\r\n%s\n",$response));

    // Get HTTP Status code from the response
    $status_code = array();
    preg_match('/\d\d\d/', $response, $status_code);

    fwrite($message_log, sprintf("\r\n%s\n",$status_code[0]));
    // Check for errors
    switch( $status_code[0] ) {
      case 200:
      // Success
        break;
      case 503:
        die('Error 503: Service unavailable. An internal problem prevented us from returning data to you.');
	      break;
      case 403:
        die('Error 403: Forbidden. You do not have permission to access this resource, or are over your rate limit.');
        break;
      case 400:
        die('Error 400: Bad request. The parameters passed to the service did not match as expected. The exact error is returned in the XML response.');
        break;
      default:
        die('Error :' . $status_code[0]);
    }
  }

  function google_checkout_state_change($check_status, $status, $oID, $cust_notify, $notify_comments) {
    // If status update is from Pending -> Processing on the Admin UI
    // this invokes the processing-order and charge-order commands
    // 1->Pending, 2-> Processing
      global $db;

      $curr_dir = getcwd();
      define('API_CALLBACK_MESSAGE_LOG', "googlecheckout/response_message.log");
      define('API_CALLBACK_ERROR_LOG', "googlecheckout/response_error.log");

      include_once('includes/modules/payment/googlecheckout.php');
      $googlepay = new googlecheckout();

      //Setup the log file
      if (!$message_log = fopen(API_CALLBACK_MESSAGE_LOG, "a")) {
        error_func("Cannot open " . API_CALLBACK_MESSAGE_LOG . " file.\n", 0);
        exit(1);
      }
      $google_answer = $db->Execute("select google_order_number, order_amount from " . $googlepay->table_order . " where orders_id = " . (int)$oID );
      $google_order = $google_answer->fields['google_order_number'];
      $amt = $google_answer->fields['order_amount'];

    if($check_status['orders_status'] == STATE_PENDING &&  $status == STATE_PROCESSING) {
      if($google_order != '') {
        $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <charge-order xmlns=\"".$googlepay->schema_url."\" google-order-number=\"". $google_order. "\">
                    <amount currency=\"USD\">" . $amt . "</amount>
                    </charge-order>";
        fwrite($message_log, sprintf("\r\n%s\n",$postargs));
        send_google_req($googlepay->request_url, $googlepay->merchantid, $googlepay->merchantkey,
                        $postargs, $message_log);

        if($cust_notify == 1) {
          $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <process-order xmlns=\"".$googlepay->schema_url    ."\" google-order-number=\"". $google_order. "\"/> ";
          fwrite($message_log, sprintf("\r\n%s\n",$postargs));
          send_google_req($googlepay->request_url, $googlepay->merchantid, $googlepay->merchantkey,
                      $postargs, $message_log);
        }
      }
    }

    // If status update is from Processing -> Delivered on the Admin UI
    // this invokes the deliver-order and archive-order commands
    // 2->Processing, 3-> Delivered
    if($check_status['orders_status'] == STATE_PROCESSING &&  $status == STATE_DELIVERED) {
      $send_mail = "false";
      if($cust_notify == 1)
        $send_mail = "true";
      if($google_order != '') {
        $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                     <deliver-order xmlns=\"".$googlepay->schema_url    ."\" google-order-number=\"". $google_order. "\">
                     <send-email> " . $send_mail . "</send-email>
                     </deliver-order> ";
        fwrite($message_log, sprintf("\r\n%s\n",$postargs));
        send_google_req($googlepay->request_url, $googlepay->merchantid, $googlepay->merchantkey,
	              $postargs, $message_log);

        $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                     <archive-order xmlns=\"".$googlepay->schema_url."\" google-order-number=\"". $google_order. "\"/>";
        fwrite($message_log, sprintf("\r\n%s\n",$postargs));
        send_google_req($googlepay->request_url, $googlepay->merchantid, $googlepay->merchantkey,
                        $postargs, $message_log);
      }
    }

    if(isset($notify_comments)) {
      $postargs =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <send-buyer-message xmlns=\"http://checkout.google.com/schema/2\" google-order-number=\"". $google_order. "\">
                   <message>". $notify_comments . "</message>
                   </send-buyer-message>";
    }
  }
  // ** END GOOGLE CHECKOUT **



////
// Alias function for Store configuration values in the Administration Tool
// ** GOOGLE CHECKOUT **
// Added to process check boxes in the admin UI
if (!function_exists('zen_cfg_multi_select_option')) {
  function zen_cfg_multi_select_option($select_array, $key_value, $key = '') {
    $string = '';
    $options = array();
    $tok = strtok($key_value," ");
    while($tok != FALSE) {
      $options[] = $tok;
      $tok = strtok(" ");
    }
    for ($i=0, $n=sizeof($select_array); $i<$n; $i++) {
      $name = ((zen_not_null($key)) ? 'configuration[' . $key .';'.$select_array[$i]. ']': 'configuration_value'.';'.$select_array[$i]);
      $string .= '<br><input type="hidden" name="' . $name . '" value="0"';
      $string .= '<br><input type="checkbox" name="' . $name . '" value="' . $select_array[$i] . '"';
      if(in_array($select_array[$i],$options))
        $string .= ' CHECKED';
      $string .= '> ' . $select_array[$i];
    }
    return $string;
  }
}
// ** END GOOGLE CHECKOUT**

?>