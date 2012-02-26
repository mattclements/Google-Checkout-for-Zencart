<?php
/*
 **GOOGLE CHECKOUT ** v1.5.0
 * @version $Id: shipping_methods.php 5852 2007-12-14 14:58:57Z ropu $
 * 
 */
  // this are all the available methods for each shipping provider, 
  // see that you must set flat methods too!
  // CONSTRAINT: Method's names MUST be UNIQUE
  // Script to create new shipping methods
  // http://demo.globant.com/~brovagnati/tools -> Shipping Method Generator
 
  $mc_shipping_methods = array(
                        'usps' => array(
                                    'domestic_types' =>
                                      array(
                                          'EXPRESS' => 'Express Mail',
                                          'FIRST CLASS' => 'First-Class Mail',
                                          'PRIORITY' => 'Priority Mail',
                                          'PARCEL' => 'Parcel Post',
                                          'BPM' => 'Bound Printed Material',
                                          'LIBRARY' => 'Library'
                                           ),
                                    'international_types' =>
                                      array(
                                          'Global Express Guaranteed' => 'Global Express Guaranteed (1 - 3 days)',
                                          'Global Express Guaranteed Non-Document Rectangular' => 'Global Express Guaranteed Non-Document Rectangular (1 - 3 days)',
                                          'Global Express Guaranteed Non-Document Non-Rectangular' => 'Global Express Guaranteed Non-Document Non-Rectangular (1 - 3 days)',
                                          'Express Mail International (EMS)' => 'Express Mail International (EMS) (3 - 5 days)',
                                          'Express Mail International (EMS) Flat Rate Envelope' => 'Express Mail International (EMS) Flat Rate Envelope (3 - 5 days)',
                                          'Priority Mail International' => 'Priority Mail International (6 - 10 days)',
                                          'Priority Mail International Flat Rate Box' => 'Priority Mail International Flat Rate Box (6 - 10 days)',
                                          'Priority Mail International Flat Rate Envelope' => 'Priority Mail International Flat Rate Envelope',
                                          'First-Class Mail International' => 'First-Class Mail International',
                                          
                                          ),                                        
                                        ),

                        'fedexexpress' => array(
                                    'domestic_types' =>
                                      array(
                                          '06' => 'FedEx First Overnight',
                                          '01' => 'FedEx Priority Overnight',
                                          '05' => 'FedEx Standard Overnight',
                                          '03' => 'FedEx 2Day',
                                          '20' => 'FedEx Express Saver',

                                           ),

                                    'international_types' =>
                                      array(
                                          '01' => 'FedEx International Priority',
                                          '03' => 'FedEx International Economy',

                                           ),
                                        ),
                        'fedexground' => array(
                                    'domestic_types' =>
                                      array(
                                          '90' => 'FedEx Home Delivery',

                                           ),

                                    'international_types' =>
                                      array(

                                           ),
                                        ),
                        'fedex1' => array(
                                    'domestic_types' =>
                                      array(
                                          '01' => 'Priority (by 10:30AM, later for rural)',
                                          '03' => '2 Day Air',
                                          '05' => 'Standard Overnight (by 3PM, later for rural)',
                                          '06' => 'First Overnight',
                                          '20' => 'Express Saver (3 Day)',
                                          '90' => 'Home Delivery',
                                          '92' => 'Ground Service'
                                           ),

                                    'international_types' =>
                                      array(
                                          '01' => 'International Priority (1-3 Days)',
                                          '03' => 'International Economy (4-5 Days)',
                                          '06' => 'International First',
                                          '90' => 'International Home Delivery',
                                          '92' => 'International Ground Service'
                                           ),
                                        ),
                        'ups' => array(
                                    'domestic_types' =>
                                      array(
                                          '1DM' => 'Next Day Air Early AM',
                                          '1DML' => 'Next Day Air Early AM Letter',
                                          '1DA' => 'Next Day Air',
                                          '1DAL' => 'Next Day Air Letter',
                                          '1DAPI' => 'Next Day Air Intra (Puerto Rico)',
                                          '1DP' => 'Next Day Air Saver',
                                          '1DPL' => 'Next Day Air Saver Letter',
                                          '2DM' => '2nd Day Air AM',
                                          '2DML' => '2nd Day Air AM Letter',
                                          '2DA' => '2nd Day Air',
                                          '2DAL' => '2nd Day Air Letter',
                                          '3DS' => '3 Day Select',
                                          'GND' => 'Ground',
                                          'GNDCOM' => 'Ground Commercial',
                                          'GNDRES' => 'Ground Residential',
                                          'STD' => 'Canada Standard',
                                          'XPR' => 'Worldwide Express',
                                          'XPRL' => 'worldwide Express Letter',
                                          'XDM' => 'Worldwide Express Plus',
                                          'XDML' => 'Worldwide Express Plus Letter',
                                          'XPD' => 'Worldwide Expedited'
                                           ),

                                    'international_types' =>
                                      array(

                                           ),
                                        ),
                        'zones' => array(
                                    'domestic_types' =>
                                      array(
                                          'zones' => 'Zones Rates'
                                           ),

                                    'international_types' =>
                                      array(
                                          'zones' => 'Zones Rates intl'
                                           ),
                                        ),
                        'freeoptions' => array(
                                    'domestic_types' =>
                                      array(
                                          'freeoptions' => 'Free Options'
                                           ),

                                    'international_types' =>
                                      array(
                                          'freeoptions' => 'Free Options intl'
                                           ),
                                        ),
                        'freeshipper' => array(
                                    'domestic_types' =>
                                      array(
                                          'freeshipper' => 'Free Shipper'
                                           ),

                                    'international_types' =>
                                      array(
                                          'freeshipper' => 'Free Shipper intl'
                                           ),
                                        ),
                        'perweightunit' => array(
                                    'domestic_types' =>
                                      array(
                                          'perweightunit' => 'Perweight Unit'
                                           ),

                                    'international_types' =>
                                      array(
                                          'perweightunit' => 'Perweight Unit intl'
                                           ),
                                        ),
                        'storepickup' => array(
                                    'domestic_types' =>
                                      array(
                                          'storepickup' => 'Store Pickup'
                                           ),

                                    'international_types' =>
                                      array(
                                          'storepickup' => 'Store Pickup intl'
                                           ),
                                        ),
                        'flat' => array(
                                    'domestic_types' =>
                                      array(
                                          'flat' => 'Flat Rate Per Order'
                                           ),

                                    'international_types' =>
                                      array(
                                          'flat' => 'Flat Rate Per Order intl'
                                           ),
                                        ),
                        'item' => array(
                                    'domestic_types' =>
                                      array(
                                          'item' => 'Flat Rate Per Item'
                                           ),

                                    'international_types' =>
                                      array(
                                          'item' => 'Flat Rate Per Item intl'
                                           ),
                                        ),
                        'table' => array(
                                    'domestic_types' =>
                                      array(
                                          'table' => 'Vary by Weight/Price'
                                           ),

                                    'international_types' =>
                                      array(
                                          'table' => 'Vary by Weight/Price intl'
                                           ),
                                        ),
                        'itemnational' => array(
                                    'domestic_types' =>
                                      array(
                                          'itemnational' => 'Item National',

                                           ),

                                    'international_types' =>
                                      array(

                                           ),
                                        ),
                        'iteminternational' => array(
                                    'domestic_types' =>
                                      array(

                                           ),

                                    'international_types' =>
                                      array(
                                          'iteminternational' => 'Item International',

                                           ),
                                        ),                                        
                                  );

  $mc_shipping_methods_names = array( 
                                         'usps' => 'USPS',
                                         'fedex1' => 'FedEx',
                                         'ups' => 'UPS',
                                         'zones' => 'Zones',
                                         'fedexexpress' => 'Fedex Express',
                                         'fedexground' => 'Fedex Ground',
                                         'freeoptions' => 'Free Options',
                                         'freeshipper' => 'Free Shipper',
                                         'perweightunit' => 'Perweight Unit',
                                         'storepickup' => 'Store Pickup',
                                         'flat' => 'Flat Rate',
                                         'item' => 'Item',
                                         'table' => 'Table',
                                         'itemnational' => 'Per Item National',
                                         'iteminternational' => 'Per Item International',
                                        );  
?>
