<?php
/*
 * Created on 12/02/2007
 *
 * Coded by: Ropu for Google Checkout API
 * Buenos Aires, Argentina  - z-tests
 */
 
//include_once('googlecheckout/xmlparser.php');
//include_once('googlecheckout/xmlbuilder.php');
//require_once('googlecheckout/googlemerchantcalculations.php');
//require_once('googlecheckout/googleresult.php');

function process_merchant_calculation_callback($root, $data, $message_log, $timeout = 2.7, $debug=false){
  global $merchant_id, $merchant_key;

	$urls = array();
	$url = selfURL();

	$headers = array();
	$header = array();
	$header[] = "Authorization: Basic ".base64_encode($merchant_id.':'.$merchant_key);
	$header[] = "Content-Type: application/xml;charset=UTF-8";
	$header[] = "Accept: application/xml;charset=UTF-8";

	$xml = get_arr_result($data);
//	Change the XML root
	$new_root = 'merchant-calculation-callback-single';
	$xml[0][$new_root] = $xml[0][$root];
	unset($xml[0][$root]); 
	$root = $new_root;
	
	$shippers = array();
	if(isset($xml[0][$root]['calculate']['shipping'])) {
	  $shippers = array();
		$shipping = get_arr_result($xml[0][$root]['calculate']['shipping']['method']);
		foreach($shipping as $curr_ship) {
			$name = $curr_ship['name'];
		  list($shipping_name, $method_name) = explode(': ',$name);
		  $shippers[$shipping_name][] = $method_name;
		}
	}
	$all_results = array();
	$data = array();
	$addresses = get_arr_result($xml[0][$root]['calculate']['addresses']['anonymous-address']);
	foreach($addresses as $curr_address) {
		$xml[0][$root]['calculate']['addresses'] = array('anonymous-address' => $curr_address);
		
		foreach($shippers as $shipping_name => $methods){
//			print_r($curr_address);
			$gcheck = new XmlBuilder();
			$xml[0][$root]['calculate']['shipping']['method'] = array();
			foreach($methods as $method) {
				$xml[0][$root]['calculate']['shipping']['method'][] = array('name' => $shipping_name . ": " . $method); 
				$all_results[$curr_address['id']][] = $shipping_name . ": " . $method;
			}
			do_xml($gcheck, $xml[0]);
			$urls[] = $url;
			$data[] = $gcheck->GetXML();
			$headers[] = $header;
		}
	}
	
// parallelize shipping requests 
	$results = multisocket_send($urls, $data, $headers, $timeout, $debug);
	if($debug){
		print_r($results);
	}
	
	$merchant_calc = new GoogleMerchantCalculations();
	foreach($results as $result){
		$xmlp = new XmlParser(trim($result));
		$root = $xmlp->getRoot();
		$data = $xmlp->getData();  
		$rs = get_arr_result($data[$root]['results']['result']);
		foreach($rs as $r){
		  $merchant_result = new GoogleResult($r['address-id']);
		  $merchant_result->SetShippingDetails(	$r['shipping-name'], 
																					  $r['shipping-rate']['VALUE'], 
																					  $r['shipping-rate']['currency'], 
																					  $r['shippable']['VALUE']);
	  
		  if(isset($r['total-tax'])) {
		  	$merchant_result->SetTaxDetails($r['total-tax']['VALUE'], 
		  																	$r['total-tax']['currency']);
		  }
		  if(isset($r['merchant-code-results'])){
		    $crs = get_arr_result($r['merchant-code-results']['coupon-result']);
		    foreach($crs as $cr){
				  $coupons = new GoogleCoupons(	$cr['valid']['VALUE'], 
																			  $cr['code']['VALUE'], 
																			  $cr['calculated-amount']['VALUE'], 
																			  $cr['calculated-amount']['currency'], 
																			  $cr['message']['VALUE']);
				  $merchant_result->AddCoupons($coupons);
		    }
		  }
		  if(isset($r['gift-certificate-result'])){
		    $crs = get_arr_result($r['merchant-code-results']['gift-certificate-result']);
		    foreach($crs as $cr){
				  $coupons = new GoogleGiftcerts(	$cr['valid']['VALUE'], 
																			  $cr['code']['VALUE'], 
																			  $cr['calculated-amount']['VALUE'], 
																			  $cr['calculated-amount']['currency'], 
																			  $cr['message']['VALUE']);
				  $merchant_result->AddGiftCertificates($coupons);
		    }
		  }
			$merchant_calc->AddResult($merchant_result);
			$all_results[$r['address-id']] = array_diff($all_results[$r['address-id']], array($r['shipping-name']));
		}
	}
	// Timed out results marked as shippable False
	foreach($all_results as $id => $results){
	  foreach($results as $result) {
		  $merchant_result = new GoogleResult($id);
			$merchant_result->SetShippingDetails($result, 9999.01, 'USD', 'false');
		  $merchant_calc->AddResult($merchant_result);
	  }
	}
	echo $merchant_calc->getXML();
}

function do_xml(&$gcheck, $xml) {
  if(is_array($xml)) {
	  foreach($xml as $key => $value) { 
			$params = array();
			$element = array();
	
			if(!is_associative_array($value)){
				foreach($value as $child_key => $child_value){
					$child_value = array($key => $value[$child_key]);
					do_xml($gcheck, $child_value);
				}
			}
			else {
				foreach($value as $child_key => $child_value){
					if(!is_array($child_value)){
				  	if($child_key == 'VALUE'){
					    $element[$child_key] = $child_value;
					  }else {
					  	$params[$child_key] = $child_value;
					  }
					}
				}
				$childs = array_diff($value, $params, $element);
				if(!empty($element)){
					$gcheck->element($key, $element['VALUE'], $params);			
				}
				else {
		 			$gcheck->push($key, $params);
					do_xml($gcheck, $childs);
					$gcheck->pop($key);
				}
			}
		}
  }else {
    $gcheck->EmptyElement($xml);
  } 
}

function selfURL($ip=false) {
	$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
	$protocol = strleft(strtolower($_SERVER['SERVER_PROTOCOL']), '/') . $s;
	$port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':'. $_SERVER['SERVER_PORT']);
	return $protocol . '://' . ($ip?$_SERVER['SERVER_ADDR']:$_SERVER['SERVER_NAME']) . $port . $_SERVER['REQUEST_URI'];
}
function strleft($s1, $s2) {
	return substr($s1, 0, strpos($s1, $s2));
}

function multisocket_send($urls=array(), $postargs=array(), $headers=array(array()), $timeout=30, $debug=false) {
	list($start_m, $start_s) = explode(' ', microtime());
	$start = $start_m + $start_s;

	$cant_sockets = 0;
	$hosts = $ports = $paths = array();
	foreach($urls as $url){
	  $parsed_url = parse_url($url);
	  $hosts[] = $parsed_url['host'];
	  $ports[] = isset($parsed_url['port'])?$parsed_url['port']:($parsed_url['scheme']=='https'?'443':'80');
	  $paths[] = $parsed_url['path'];
	  $cant_sockets++;
	}
	// Create a new socket
	$sockets = array();
	for($i=0; $i<$cant_sockets; $i++) {
		$sockets[$i] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	// Connect to destination address
		socket_set_nonblock($sockets[$i]);
		@socket_connect($sockets[$i], $hosts[$i], $ports[$i]);
		socket_set_block($sockets[$i]);		
		socket_set_option($sockets[$i], SOL_SOCKET, SO_RCVTIMEO, array("sec" => 0, "usec" => 100000));

//		To avoid connection Timeout, 0.1 sec window to connect
		switch(socket_select($r = array($sockets[$i]), $write = array($sockets[$i]), $except = array($sockets[$i]), 0 , 50000)){
       case 1:
       				if($debug){
               	echo $i . " [+] Connected\n";
       				}
              break;
       case 2:
       case 0:
       default:
       				if($debug){
              	echo $i . " [-] Connection Error\n";
       				}
              continue 2;
		}
		
	
		$ReqHeader = 	"POST " . $paths[$i] . " HTTP/1.1\n".
								"Host: " . $hosts[$i] . "\n".
								"User-Agent: RopuSoft - GC MultiSocket v0.1\n";
		foreach($headers[$i] as $header){
		  $ReqHeader .= $header . "\n" ;
		}
		$ReqHeader .=	"Connection: close\n". // Important HEADER!!! to avoid delays when doing do{}while(reading)! 
								"Content-Length:  ". strlen($postargs[$i]) ."\n\n". // without this one, post wont work
								$postargs[$i] . "\n";
		socket_write($sockets[$i], $ReqHeader);
	}
	$responses = array();
	list($mid_m, $mid_s) = explode(' ', microtime());
	$mid = $mid_m + $mid_s;
	while($mid-$start < $timeout){
		// If all sockets are read, don't wait for the timeOut.
		if(count($sockets) < 1) {
			break;
		}
		$new_socks = $sockets;
		// Calculate the remaing time before timeOut
		$working = $timeout - ($mid-$start);
		$rsec = (int)($working);
		$rusec = (int)(($working - $rsec) * 1000000);
		
		// Watch for activity in any socket
		$num_changed_sockets = socket_select($new_socks, $write = NULL, $except = NULL, $rsec , $rusec);
		for($i=0; $i<count($new_socks); $i++) {
			// Read all info in the socket
			$readed = '';
			while(($read = socket_read($new_socks[$i], 2048)) && !empty($read)){
				$readed .= $read;
			}
			$rta = strstr("\r\n\r\n", $readed);
			list(,$responses[]) = paser_http($readed);
		}
		// Discard used sockets
		foreach($new_socks as $new_sock){
			socket_shutdown($new_sock);
			socket_close($new_sock);
		}
		$sockets = array_diff($sockets, $new_socks);
		// Calculate actual time
		list($mid_m, $mid_s) = explode(' ', microtime());
		$mid = $mid_m + $mid_s;
	}
	foreach($sockets as $new_sock){
		socket_shutdown($new_sock);
		socket_close($new_sock);
	}
	
	if($debug){  
//	 debug
		list($end_m, $end_s) = explode(' ', microtime());
		$end = $end_m + $end_s;
		echo "\n\nMultisocket took: ". ($end-$start) ." secs\n"; 
//	 end debug
	}
	return $responses;
}

function paser_http($response, $format=0){
	$fp = explode("\r\n", $response);
  for($i=0, $cant = count($fp); $i<$cant;$i++){
    if($fp[$i] == "") {
      $eoheader = true;
      break;
    } else {
      $header = trim($fp[$i]);
    }

    if($format == 1) {
      $key = array_shift(explode(':',$fp[$i]));
      if($key == $fp[$i]) {
        $headers[] = $fp[$i];
      } else {
        $headers[$key]=substr($fp[$i],strlen($key)+2);
      }
      unset($key);
    } else {
     $headers[] = $fp[$i];
    }
  }
  
  return array($headers, implode("\r\n", array_diff($fp, $headers)));
}
?>