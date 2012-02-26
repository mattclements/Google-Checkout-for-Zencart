<?php
/*
 **GOOGLE CHECKOUT ** v1.4.5
 * @version $Id: shipping_methods_ccs.php 5862 2007-09-19 14:58:57Z ropu $
 * Is recommended you DONT change this file unless you know what you are doing
 */
	$cc_shipping_methods = array(
                        'fedex' => array(
                                    'domestic_types' =>
                                      array(
                                          'Ground' => 'Ground',
                                          'Home Delivery' => 'Home Delivery',
                                          'Express Saver' => 'Express Saver',
                                          '2Day' => '2Day',
                                          'Standard Overnight' => 'Standard Overnight',
                                          'Priority Overnight' => 'Priority Overnight',
                                          'First Overnight' => 'First Overnight',
                                           ),

                                    'international_types' =>
                                      array(

                                           ),
                                        ),
                        'ups' => array(
                                    'domestic_types' =>
                                      array(
                                          'Ground' => 'Ground',
                                          '3 Day Select' => '3 Day Select',
                                          '2nd Day Air' => '2nd Day Air',
//                                          '2nd Day Air AM' => ' 2nd Day Air AM',
                                          'Next Day Air Saver' => 'Next Day Air Saver',
                                          'Next Day Air' => 'Next Day Air',
                                          'Next Day Air Early AM' => 'Next Day Air Early AM',
                                           ),

                                    'international_types' =>
                                      array(
   
                                           ),
                                        ),
                        'usps' => array(
                                    'domestic_types' =>
                                      array(
                                          'Media Mail' => 'Media Mail',
                                          'Parcel Post' => 'Parcel Post',
                                          'Express Mail' => 'Express Mail',
//                                          'Priority Mail' => 'Priority Mail',
                                           ),

                                    'international_types' =>
                                      array(

                                           ),
                                        )
                                    );     
                                                                                       
  $cc_shipping_methods_names = array(    'fedex' => 'FedEx',
                                         'ups' => 'UPS',
                                         'usps' => 'USPS',
                                        );
?>