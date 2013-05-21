<?php
/**
 * GC_return header_php.php 
 *
 * @package page
 * @copyright Ropu 2007
 * @copyright Portions Copyright 2003-2005 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: header_php.php 2671 2007-07-03 08:01:00Z ropu $
 */

  // This should be first line of the script:
  $zco_notifier->notify('NOTIFY_HEADER_START_GC_RETURN');

  require(DIR_WS_MODULES . zen_get_module_directory('require_languages.php'));
  require_once(DIR_WS_MODULES. '/payment/googlecheckout.php');
  $googlepayment = new googlecheckout();
  $url_query = parse_url($_SERVER['HTTP_REFERER']);
//  print_r($_SERVER);
  
  if(ereg('t=([0-9]*)', @$url_query['query'], $args)){
//    print_r($args);
    $google_order = $db->Execute("SELECT orders_id from " .
        "" . $googlepayment->table_order . " where google_order_number = " .
        "'". zen_db_input($args[1]) ."'");
    if($google_order->RecordCount() != 0) {
      zen_redirect(zen_href_link('checkout_success', '', 'NONSSL'));
//      echo $google_order->fields['orders_id'];
    }
  } 
  $_SESSION['cart']->reset(TRUE);
//  print_r($args); 
  
  // This should be last line of the script:
  $zco_notifier->notify('NOTIFY_HEADER_END_GC_RETURN');
?>