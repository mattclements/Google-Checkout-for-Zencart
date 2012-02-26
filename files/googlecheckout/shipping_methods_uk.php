<?php
/*
 **GOOGLE CHECKOUT ** v1.4.1
 * @version $Id: shipping_methods_uk.php 5947 2007-08-24 14:58:57Z pablof $
 * 
 */
  // this are all the available methods for each shipping provider, 
  // see that you must set flat methods too!
  // CONSTRAINT: Method's names MUST be UNIQUE
  // Script to create new shipping methods
  // http://demo.globant.com/~brovagnati/tools -> Shipping Method Generator
 
  $mc_shipping_methods = array(
                               'international_datapost' => array(
                                  'domestic_types' =>
                                    array(),
                                  'international_types' =>
                                    array(
                                          'table1' => 'International Datapost'
                                         )
                                   ),
                               'international_standard' => array(
                                  'domestic_types' =>
                                    array(),
                                  'international_types' =>
                                    array(
                                          'table1' => 'International Standard'
                                         )
                                   ),
                               'rmstdparcels' => array(
                                  'domestic_types' =>
                                    array(
                                          'rmstdparcels' => 'Standard Parcels'
                                         ),
                                  'international_types' =>
                                    array()
                                   ),
                               'rmstdparcels100' => array(
                                  'domestic_types' =>
                                    array(
                                          'rmstdparcels100' => 'Standard Parcels (to &pound;100 insurance)'
                                         ),
                                  'international_types' =>
                                    array()
                                   ),
                               'rmstdparcels250' => array(
                                  'domestic_types' =>
                                    array(
                                          'rmstdparcels250' => 'Standard Parcels (to &pound;250 insurance)'
                                         ),
                                  'international_types' =>
                                    array()
                                   ),
                               'rmstdparcels500' => array(
                                  'domestic_types' =>
                                    array(
                                          'rmstdparcels500' => 'Standard Parcels (to &pound;500 insurance)'
                                         ),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm1stlargeletter' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm1stlargeletter' => '1st Class "Large Letter"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm1stlargelettersf' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm1stlargelettersf' => '1st Class Recorded Signed For "Large Letter"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm1stletter' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm1stletter' => '1st Class "Letter"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm1stlettersf' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm1stlettersf' => '1st Class Recorded Signed For "Letter"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm1stpacket' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm1stpacket' => '1st Class "Packet"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm1stpacketsf' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm1stpacketsf' => '1st Class Recorded Signed For "Packet"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm2ndlargeletter' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm2ndlargeletter' => '2nd Class "Large Letter"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm2ndlargelettersf' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm2ndlargelettersf' => '2nd Class Recorded Signed For "Large Letter"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm2ndletter' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm2ndletter' => '2nd Class "Letter"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm2ndlettersf' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm2ndlettersf' => '2nd Class Recorded Signed ForTM "Letter"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm2ndpacket' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm2ndpacket' => '2nd Class "Packet"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rm2ndpacketsf' => array(
                                  'domestic_types' =>
                                    array(
                                          'rm2ndpacketsf' => '2nd Class Recorded Signed ForTM "Packet"',),
                                  'international_types' =>
                                    array()
                                   ),
                               'rmamsmallpacket' => array(
                                  'domestic_types' =>
                                    array(),
                                  'international_types' =>
                                    array(
                                          'rmamsmallpacket' => 'Airmail delivery in 3-5 working days.',)
                                   ),
                               'rmamisfsmallpacket' => array(
                                  'domestic_types' =>
                                    array(),
                                  'international_types' =>
                                    array(
                                          'rmamisfsmallpacket' => 'International Signed For "Small Packet" Airmail delivery in 3-5 working days.',)
                                   ),
                               'rmamassmallpacket' => array(
                                  'domestic_types' =>
                                    array(),
                                  'international_types' =>
                                    array(
                                          'rmamassmallpacket' => 'Airsure "Small Packet" Airmail Express delivery in 2-4 working days.',)
                                   ),
                               'rmamisfsmallpacket500' => array(
                                  'domestic_types' =>
                                    array(),
                                  'international_types' =>
                                    array(
                                          'rmamisfsmallpacket500' => 'International Signed For "Small Packet" Extra insurance Airmail delivery in 3-5 working days.',)
                                   ),
                               'rmamassmallpacket500' => array(
                                  'domestic_types' =>
                                    array(),
                                  'international_types' =>
                                    array(
                                          'rmamassmallpacket500' => 'Airsure "Small Packet" extra insurance Airmail Express delivery in 2-4 working days. __',)
                                   ),
                               'specialdelivery' => array(
                                   'domestic_types' =>
                                    array(
                                          'specialdelivery' => 'specialdelivery next day'
                                         ),
                                   'international_types' =>
                                    array()
                                   ),
                               'specialdelivery9am' => array(
                                  'domestic_types' =>
                                    array(
                                          'specialdelivery9am' => 'specialdelivery 9:00am',),
                                  'international_types' =>
                                    array()
                                   ),
                               'zones' => array(
                                  'domestic_types' =>
                                    array(
                                          'zones' => 'Zones Rates'
                                         ),
                                  'international_types' =>
                                    array(
                                          'zones' => 'Zones Rates intl'
                                         )
                                   ),
                               'freeoptions' => array(
                                  'domestic_types' =>
                                    array(
                                          'freeoptions' => 'Free Options'
                                         ),
                                  'international_types' =>
                                    array(
                                          'freeoptions' => 'Free Options intl'
                                         )
                                   ),
                               'freeshipper' => array(
                                  'domestic_types' =>
                                    array(
                                          'freeshipper' => 'Free Shipper'
                                         ),
                                  'international_types' =>
                                    array(
                                          'freeshipper' => 'Free Shipper intl'
                                         )
                                   ),
                               'perweightunit' => array(
                                  'domestic_types' =>
                                    array(
                                          'perweightunit' => 'Perweight Unit'
                                         ),
                                  'international_types' =>
                                    array(
                                          'perweightunit' => 'Perweight Unit intl'
                                         )
                                   ),
                               'storepickup' => array(
                                  'domestic_types' =>
                                    array(
                                          'storepickup' => 'Store Pickup'
                                         ),
                                  'international_types' =>
                                    array(
                                          'storepickup' => 'Store Pickup intl'
                                         )
                                   ),
                               'flat' => array(
                                  'domestic_types' =>
                                    array(
                                          'flat' => 'Flat Rate Per Order'
                                         ),
                                  'international_types' =>
                                    array(
                                          'flat' => 'Flat Rate Per Order intl'
                                         )
                                   ),
                               'item' => array(
                                  'domestic_types' =>
                                    array(
                                          'item' => 'Flat Rate Per Item'
                                         ),
                                  'international_types' =>
                                    array(
                                          'item' => 'Flat Rate Per Item intl'
                                         )
                                   ),
                               'table' => array(
                                  'domestic_types' =>
                                    array(
                                          'table' => 'Vary by Weight/Price'
                                         ),
                                  'international_types' =>
                                    array(
                                          'table' => 'Vary by Weight/Price intl'
                                         )
                                   ),
                               );

  $mc_shipping_methods_names = array(
                                     'rm1stpacketsf' => 'Royal Mail',
                                     'international_datapost' => 'Royal Mail',
                                     'international_standard' => 'Royal Mail',
                                     'rm1stlargeletter' => 'Royal Mail',
                                     'rm1stlargelettersf' => 'Royal Mail',
                                     'rm1stletter' => 'Royal Mail',
                                     'rm1stlettersf' => 'Royal Mail',
                                     'rm1stpacket' => 'Royal Mail',
                                     'rm2ndlargeletter' => 'Royal Mail',
                                     'rm2ndlargelettersf' => 'Royal Mail',
                                     'rm2ndletter' => 'Royal Mail',
                                     'rm2ndlettersf' => 'Royal Mail',
                                     'rm2ndpacket' => 'Royal Mail',
                                     'rm2ndpacketsf' => 'Royal Mail',
                                     'rmamassmallpacket' => 'Royal Mail',
                                     'rmamassmallpacket500' => 'Royal Mail',
                                     'rmamisfsmallpacket' => 'Royal Mail',
                                     'rmamisfsmallpacket500' => 'Royal Mail',
                                     'rmamsmallpacket' => 'Royal Mail',
                                     'rmstdparcels' => 'Royal Mail',
                                     'rmstdparcels100' => 'Royal Mail',
                                     'rmstdparcels250' => 'Royal Mail',
                                     'rmstdparcels500' => 'Royal Mail',
                                     'specialdelivery' => 'Royal Mail',
                                     'specialdelivery9am' => 'Royal Mail',
                                     'freeoptions' => 'Free Options',
                                     'freeshipper' => 'Free Shipper',
                                     'perweightunit' => 'Perweight Unit',
                                     'storepickup' => 'Store Pickup',
                                     'flat' => 'Flat Rate',
                                     'item' => 'Item',
                                     'table' => 'Table',
                                     );  
?>
