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
  define('STATE_PENDING', "1");
  define('STATE_PROCESSING', "2");
  define('STATE_DELIVERED', "3");
 
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
    $header_string_2 = "Content-Type: application/xml;charset=UTF-8";
    $header_string_3 = "Accept: application/xml;charset=UTF-8";
	
//    fwrite($message_log, sprintf("\r\n%s %s %s\n",$header_string_1, $header_string_2, $header_string_3));
    // Set the POST options.
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_HTTPHEADER, array($header_string_1, $header_string_2, $header_string_3));
    curl_setopt($session, CURLOPT_POSTFIELDS, $postargs);
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    // Do the POST and then close the session
    $response = curl_exec($session);
	if (curl_errno($session)) {
		die(curl_error($session));
	} else {
	    curl_close($session);
	}

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
        die('Error: ' . $status_code[0]);
    }
  }
  
  function google_checkout_state_change($check_status, $status, $oID, $cust_notify, $notify_comments) {
    // If status update is from Pending -> Processing on the Admin UI
    // this invokes the processing-order and charge-order commands
    // 1->Pending, 2-> Processing
      global $db;

      define('API_CALLBACK_MESSAGE_LOG', DIR_FS_CATALOG . "/googlecheckout/response_message.log");
      define('API_CALLBACK_ERROR_LOG', DIR_FS_CATALOG. "/googlecheckout/response_error.log");

      include_once(DIR_FS_CATALOG . '/includes/modules/payment/googlecheckout.php');
      $googlepay = new googlecheckout();

      //Setup the log file
      if (!$message_log = fopen(API_CALLBACK_MESSAGE_LOG, "a")) {
        error_func("Cannot open " . API_CALLBACK_MESSAGE_LOG . " file.\n", 0);
        exit(1);
      }
      $google_answer = $db->Execute("select google_order_number, order_amount from " . $googlepay->table_order . " where orders_id = " . (int)$oID );
      $google_order = $google_answer->fields['google_order_number'];  
      $amt = $google_answer->fields['order_amount'];  

    if($check_status->fields['orders_status'] == STATE_PENDING &&  $status == STATE_PROCESSING) {
      if($google_order != '') {					
        $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <charge-order xmlns=\"".$googlepay->schema_url."\" google-order-number=\"". $google_order. "\">
                    <amount currency=\"USD\">" . $amt . "</amount>
                    </charge-order>";
        fwrite($message_log, sprintf("\r\n%s\n",$postargs));
        send_google_req($googlepay->request_url, $googlepay->merchantid, $googlepay->merchantkey, 
                        $postargs, $message_log); 
        
        $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <process-order xmlns=\"".$googlepay->schema_url    ."\" google-order-number=\"". $google_order. "\"/> ";
        fwrite($message_log, sprintf("\r\n%s\n",$postargs));
        send_google_req($googlepay->request_url, $googlepay->merchantid, $googlepay->merchantkey, 
                        $postargs, $message_log); 
      }
    }
    
    // If status update is from Processing -> Delivered on the Admin UI
    // this invokes the deliver-order and archive-order commands
    // 2->Processing, 3-> Delivered
    if($check_status->fields['orders_status'] == STATE_PROCESSING &&  $status == STATE_DELIVERED) {
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
      $send_mail = "false";
      if($cust_notify == 1) 
        $send_mail = "true";
      $postargs =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <send-buyer-message xmlns=\"http://checkout.google.com/schema/2\" google-order-number=\"". $google_order. "\">
                   <send-email> " . $send_mail . "</send-email>
                   <message>". strip_tags($notify_comments) . "</message>
                   </send-buyer-message>";  
      fwrite($message_log, sprintf("\r\n%s\n",$postargs));
      send_google_req($googlepay->request_url, $googlepay->merchantid, $googlepay->merchantkey,
                      $postargs, $message_log);
        
    }
  }
  // ** END GOOGLE CHECKOUT ** 



////
// Alias function for Store configuration values in the Administration Tool
// ** GOOGLE CHECKOUT **
// Added to process check boxes in the admin UI

// Custom Function to store configuration values (shipping default values)  
	if (!function_exists('compare')) {
		function compare($key, $data)
		{
			foreach($data as $value) {
				list($key2, $valor) = explode("_VD:", $value);
				if($key == $key2)		
					return $valor;
			}
			return '0';
		}
	}
	// perhaps this function must be moved to googlecheckout class, is not too general
	if (!function_exists('zen_cfg_select_shipping')) {
	  function zen_cfg_select_shipping($select_array, $key_value, $key = '') {
	
		//add ropu
		// i get all the shipping methods available!
		global $PHP_SELF,$module_type;
		
		$module_directory = DIR_FS_CATALOG_MODULES . 'shipping/';
		
		$file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
		$directory_array = array();
		if ($dir = @dir($module_directory)) {
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
		
		  $installed_modules = array();
		  $select_array = array();
		  for ($i=0, $n=sizeof($directory_array); $i<$n; $i++) {
		    $file = $directory_array[$i];
			
		    include_once(DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] . '/modules/shipping/' . $file);
		    include_once($module_directory . $file);
		    $class = substr($file, 0, strrpos($file, '.'));
		    //if (tep_class_exists($class)) {
		      $module = new $class;
		      //print_R($module);
		      //$class;
		      if ($module->check() > 0) {
	
		        $select_array[$module->code] = array('code' => $module->code,
		                             'title' => $module->title,
		                             'description' => $module->description,
		                             'status' => $module->check());
		      }
		    //}
		  }
		require_once (DIR_FS_CATALOG . 'includes/modules/payment/googlecheckout.php');
		$googlepayment = new googlecheckout();
		//print_r($googlepayment);
		$ship_calcualtion_mode = (count(array_keys($select_array)) > count(array_intersect($googlepayment->shipping_support, array_keys($select_array)))) ? true : false;
		if(!$ship_calcualtion_mode) {
			return '<br/><i>'. GOOGLECHECKOUT_TABLE_NO_MERCHANT_CALCULATION . '</i>';
		}
	
		$javascript = "<script language='javascript'>
							
						function VD_blur(valor, code, hid_id){
							var hid = document.getElementById(hid_id);
							valor.value = isNaN(parseFloat(valor.value))?'':parseFloat(valor.value);
							if(valor.value != ''){ 
								hid.value = code + '_VD:' + valor.value;
						//		valor.value = valor.value;	
						//		hid.disabled = false;
							}else {		
								hid.value = code + '_VD:0';
								valor.value = '0';			
							}
				
				
						}
				
						function VD_focus(valor, code, hid_id){
							var hid = document.getElementById(hid_id);		
						//	valor.value = valor.value.substr((code  + '_VD:').length, valor.value.length);
							hid.value = valor.value.substr((code  + '_VD:').length, valor.value.length);				
						}
		
						</script>";
		
		
	  	$string .= $javascript;
	  	
	  	$key_values = explode( ", ", $key_value);
	    
	    foreach($select_array as $i => $value){
	      if ( $select_array[$i]['status'] && !in_array($select_array[$i]['code'], $googlepayment->shipping_support) ) {
		      $name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
		      $string .= "<br><b>" . $select_array[$i]['title'] . "</b>"."\n";
		      foreach($googlepayment->mc_shipping_methods[$select_array[$i]['code']]['domestic_types'] as $method => $method_name) {
			      $string .= '<br>';
			      
			      // default value 
			      $value = compare($select_array[$i]['code'] . $method, $key_values);
				  $string .= '<input size="5"  onBlur="VD_blur(this, \'' . $select_array[$i]['code']. $method . '\', \'hid_' . $select_array[$i]['code'] . $method . '\' );" onFocus="VD_focus(this, \'' . $select_array[$i]['code'] . $method . '\' , \'hid_' . $select_array[$i]['code'] . $method .'\');" type="text" name="no_use' . $method . '" value="' . $value . '"';
			      $string .= '>';
				  $string .= '<input size="10" id="hid_' . $select_array[$i]['code'] . $method . '" type="hidden" name="' . $name . '" value="' . $select_array[$i]['code'] . $method . '_VD:' . $value . '"';		  
		      	  $string .= '>'."\n";
		      	  $string .= $method_name;
		      }
	      }
	    }
	    return $string;
	  }
	}


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