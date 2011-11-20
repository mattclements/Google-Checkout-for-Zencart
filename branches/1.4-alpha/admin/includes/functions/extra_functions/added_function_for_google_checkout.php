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
/* **GOOGLE CHECKOUT ** v1.4
  @version $Id: added_functions_for_google_checkout.php 5342 2007-06-04 14:58:57Z ropu $
*/

 /* ** GOOGLE CHECKOUT **/
  define('STATE_PENDING', "1");
  define('STATE_PROCESSING', "2");
  define('STATE_DELIVERED', "3");
  
  function google_checkout_state_change($check_status, $status, $oID, 
                                              $cust_notify, $notify_comments) {
      global $db,$messageStack;

      define('API_CALLBACK_ERROR_LOG', 
                       DIR_FS_CATALOG. "/googlecheckout/logs/response_error.log");
      define('API_CALLBACK_MESSAGE_LOG',
                       DIR_FS_CATALOG . "/googlecheckout/logs/response_message.log");

      include_once(DIR_FS_CATALOG.'/includes/modules/payment/googlecheckout.php');
      include_once(DIR_FS_CATALOG.'/googlecheckout/library/googlerequest.php');

      $googlepayment = new googlecheckout();
      
      $Grequest = new GoogleRequest($googlepayment->merchantid, 
                                    $googlepayment->merchantkey, 
                                    MODULE_PAYMENT_GOOGLECHECKOUT_MODE==
                                      'https://sandbox.google.com/checkout/'
                                      ?"sandbox":"production",
                                    DEFAULT_CURRENCY);
      $Grequest->SetLogFiles(API_CALLBACK_ERROR_LOG, API_CALLBACK_MESSAGE_LOG);

      $google_answer = $db->Execute("select google_order_number, order_amount ".
                                " from " . $googlepayment->table_order . " " .
                                " where orders_id = " . (int)$oID );
      $google_order = $google_answer->fields['google_order_number'];  
      $amount = $google_answer->fields['order_amount'];  

    // If status update is from Pending -> Processing on the Admin UI
    // this invokes the processing-order and charge-order commands
    // 1->Pending, 2-> Processing
    if($check_status->fields['orders_status'] == STATE_PENDING 
               && $status == STATE_PROCESSING && $google_order != '') {
// Tell google witch is the Zencart's internal order Number        
      list($status,) = $Grequest->SendMerchantOrderNumber($google_order, $oID);
      if($status != 200) {
        $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_MERCHANT_ORDER_NUMBER, 'error');
      }
      else {
        $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_MERCHANT_ORDER_NUMBER, 'success');          
      }

      list($status,) = $Grequest->SendChargeOrder($google_order, $amount);
      if($status != 200) {
        $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_CHARGE_ORDER, 'error');
      }
      else {
        $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_CHARGE_ORDER, 'success');          
      }
      list($status,) = $Grequest->SendProcessOrder($google_order);
      if($status != 200) {
        $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_PROCESS_ORDER, 'error');
      }
      else {
        $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_PROCESS_ORDER, 'success');          
      }
    }
    
    // If status update is from Processing -> Delivered on the Admin UI
    // this invokes the deliver-order and archive-order commands
    // 2->Processing, 3-> Delivered
    if($check_status->fields['orders_status'] == STATE_PROCESSING 
                    && $status == STATE_DELIVERED && $google_order != '') {
      $carrier = $tracking_no = "";
      // Add tracking Data
      if(isset($_POST['carrier_select']) &&  ($_POST['carrier_select'] != 'select') 
           && isset($_POST['tracking_number']) && !empty($_POST['tracking_number'])) {
        $carrier = $_POST['carrier_select'];
        $tracking_no = $_POST['tracking_number'];
        $comments = GOOGLECHECKOUT_STATE_STRING_TRACKING ."\n" .
                    GOOGLECHECKOUT_STATE_STRING_TRACKING_CARRIER . $_POST['carrier_select'] ."\n" .
                    GOOGLECHECKOUT_STATE_STRING_TRACKING_NUMBER . $_POST['tracking_number'] . "";
        $db->Execute("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
                    (orders_id, orders_status_id, date_added, customer_notified, comments)
                    values ('" . (int)$oID . "',
                    '" . zen_db_input($status) . "',
                    now(),
                    '" . zen_db_input($cust_notify) . "',
                    '" . zen_db_input($comments)  . "')");
         
      }
      
      list($status,) = $Grequest->SendDeliverOrder($google_order, $carrier,
                              $tracking_no, ($cust_notify==1)?"true":"false");
      if($status != 200) {
        $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_DELIVER_ORDER, 'error');
      }
      else {
        $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_DELIVER_ORDER, 'success');          
      }
      list($status,) = $Grequest->SendArchiveOrder($google_order);
      if($status != 200) {
        $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_ARCHIVE_ORDER, 'error');
      }
      else {
        $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_ARCHIVE_ORDER, 'success');          
      }
    }
    
    // Send Buyer's message
    if($cust_notify==1 && isset($notify_comments) && !empty($notify_comments)) {
      list($status,) = $Grequest->sendBuyerMessage($google_order, 
                           $notify_comments, "true");
      if($status != 200) {
        $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_MESSAGE_ORDER, 'error');
        $cust_notify_ok = '0';
      }
      else {
        $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_MESSAGE_ORDER, 'success');          
        $cust_notify_ok = '1';
      }
      if(strlen(htmlentities(strip_tags($notify_comments))) > GOOGLE_MESSAGE_LENGTH) {
        $messageStack->add_session(
        sprintf(GOOGLECHECKOUT_WARNING_CHUNK_MESSAGE, GOOGLE_MESSAGE_LENGTH), 'warning');          
      }
      // Cust notified
      return $cust_notify_ok;
    }
    // Cust notified
    return '0';
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
          if (is_array($googlepayment->mc_shipping_methods[$select_array[$i]['code']])) {
            foreach($googlepayment->mc_shipping_methods[$select_array[$i]['code']] as $type => $methods) {
              if (is_array($methods) && !empty($methods)) {
                $string .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'. $type .'</b>';            
                foreach($methods as $method => $method_name) {
                  $string .= '<br>';
                  
                  // default value 
                  $value = compare($select_array[$i]['code'] . $method. $type , $key_values);
                $string .= '<input size="5"  onBlur="VD_blur(this, \'' . $select_array[$i]['code']. $method . $type . '\', \'hid_' . $select_array[$i]['code'] . $method . $type . '\' );" onFocus="VD_focus(this, \'' . $select_array[$i]['code'] . $method . $type . '\' , \'hid_' . $select_array[$i]['code'] . $method . $type .'\');" type="text" name="no_use' . $method . '" value="' . $value . '"';
                  $string .= '>';
                $string .= '<input size="10" id="hid_' . $select_array[$i]['code'] . $method . $type . '" type="hidden" name="' . $name . '" value="' . $select_array[$i]['code'] . $method . $type . '_VD:' . $value . '"';      
                    $string .= '>'."\n";
                    $string .= $method_name;
                }
              }
            }
          }
          else {
            $string .= $select_array[$i]['code'] .GOOGLECHECKOUT_MERCHANT_CALCULATION_NOT_CONFIGURED;
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