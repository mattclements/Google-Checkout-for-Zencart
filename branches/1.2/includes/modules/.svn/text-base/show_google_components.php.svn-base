<?php	
  global $db;

	$status_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_GOOGLECHECKOUT_STATUS'");
	$status =  $status_query->RecordCount();
	if ($status == 1 && $status_query->fields['configuration_value'] == 'True' ) {
	  include('googlecheckout/gcheckout.php');
  }

?>