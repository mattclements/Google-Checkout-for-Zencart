<?php
/*
 * Created on 15/03/2007
 *
 * Coded by: Ropu
 * Globant - Buenos Aires, Argentina  - z-tests
 */
$errors = array();
$methods_duplicate = array();

$string = "<?php\n";

 $string  .= "/**\n";
 $string  .= "  * File: googlecheckout/shipping_methods.php file\n";
// $string  .= "  * Add this code to the correct properties\n";
 $string  .= "  */ \n";
 $string  .= '$mc_shipping_methods = array(' . "\n";
 foreach($_POST['code'] as $codekey => $codename) {
	 $string  .= "                        '" . $codename . "' => array(\n";
	 $string  .= "                                    'domestic_types' =>\n";
	 $string  .= "                                      array(\n";
	 foreach($_POST['d_m_code'][$codekey] as $key => $d_m_code) {
	   if($d_m_code != '') { 
       if($_POST['d_m_name'][$codekey][$key] == '') {
         $errors[] = $codename . ' <b>' . $d_m_code . '</b> has an empty "Method Fancy Name"';
       }

       if(isset($methods_duplicate[$_POST['d_m_name'][$codekey][$key]])) {
          $errors[] = $codename . ' <b>' . $d_m_code . '</b> "Method Fancy Name" is duplicated!';
       }
       $methods_duplicate[$_POST['d_m_name'][$codekey][$key]] = 1;
			 $string  .= "                                          '" . htmlentities($d_m_code) . "' => '" . htmlentities($_POST['d_m_name'][$codekey][$key])  . "',\n";
	   }
	 }
	 $string  .= "\n                                           ),\n";
	 $string  .= "\n                                    'international_types' =>\n";
	 $string  .= "                                      array(\n";
	 
	 foreach($_POST['i_m_code'][$codekey] as $key => $i_m_code) {
	   if($i_m_code != '') { 
	     if($_POST['i_m_name'][$codekey][$key] == '') {
	       $errors[] = $codename . ' <b>' . $i_m_code . '</b> has an empty "Method Fancy Name"';
	     }
       if(isset($methods_duplicate[$_POST['i_m_name'][$codekey][$key]])) {
          $errors[] = $codename . ' <b>' . $i_m_code . '</b> "Method Fancy Name" is duplicated!';
       }
       $methods_duplicate[$_POST['i_m_name'][$codekey][$key]] = 1;
           
		   $string  .= "                                          '" . htmlentities($i_m_code) . "' => '" . htmlentities($_POST['i_m_name'][$codekey][$key])  . "',\n";
	   }
	 }
	 $string  .= "\n                                           ),\n";
	 $string  .= "                                        ),\n";
	 
 }
 $string  .= "                                  );\n\n";



$string  .= '$mc_shipping_methods_names = array(' . "\n";
foreach($_POST['name'] as $codekey => $name){ 
	$string  .= "                                         '" . $_POST['code'][$codekey] . "' => '" . htmlentities($name) . "',\n";
}
$string  .= "                                        );";

$string  .= "\n?>";
if(!empty($errors)){
?>
  <table align="center" border="1" cellpadding="0" cellspacing="0">
    <tr>
      <th>Errors found</th>
    </tr>
<?php
  foreach($errors as $error) {
    echo "<tr><td> * $error</td></tr>";
  }
?>
  </table>
<?php
}else {
  highlight_string($string);
}

//					'fedex1' => array( 	'domestic_types' =>
//							           							array(
//															             '01' => 'Priority (by 10:30AM, later for rural)',
//															             '03' => '2 Day Air',
//															             '05' => 'Standard Overnight (by 3PM, later for rural)',
//															             '06' => 'First Overnight', 
//															             '20' => 'Express Saver (3 Day)',
//															             '90' => 'Home Delivery',
//															             '92' => 'Ground Service'
//															             ),
//															'international_types' =>	            
//							           							array(
//															             '01' => 'International Priority (1-3 Days)',
//															             '03' => 'International Economy (4-5 Days)',
//															             '06' => 'International First',
//															             '90' => 'International Home Delivery',
//															             '92' => 'International Ground Service'
//															             )
//							           					),

// echo "<xmp>";
// print_r($_POST);
// echo "</xmp>";
?>