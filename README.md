!!! Discontinued !!!
====================
Due to Google retiring Google Checkout this module is now unsupported.
Read more about the retirement [here](https://support.google.com/checkout/sell/answer/3080449)


---









GOOGLE CHECKOUT PLUGIN FOR ZEN CART v1.5.1
Released: 2012-03-09

INTRODUCTION
============
The Google Checkout module for Zen Cart adds Google Checkout as a Checkout 
 Module within Zen Cart.
This will allow merchants using Zen Cart to offer Google Checkout as an 
 alternate checkout method.
The plugin provides Level 2 integration of Google Checkout with Zen Cart.

Plugin features include:
1. Posting shopping carts to Google Checkout
2. Shipping support with Merchant Calculated Shipping Rates and 
    Carrier Calculation Shipping
3. Tax support
4. User and order updates within Zen Cart
5. Order processing using Zen Cart Admin UI 
6. Order Totals Support
7. Digital Delivery support

REQUIREMENTS
============
* Zen Cart:
    * v1.5.0
    * v1.3.9a-h
    * v1.3.8
* PHP:
    * 3, 4 & 5 with cURL(libcurl) installed and enabled

INSTALLATION NOTES
==================
1. Follow instructions contained in the INSTALLATION file.
2. Verify the installation from the Admin site and selecting MODULES->PAYMENTS 
    and checking if Google Checkout is listed as a payment option.
3. Set the file attribute to 777 for /googlecheckout/logs/response_error.log and 
    /googlecheckout/logs/response_message.log files.
4. Go to http://<url-site-url>/googlecheckout/responsehandler.php
    If you get a 'Invalid or not supported Message', 'Fail to get HTTP Authentication',
    or a request for User and Password, go to the next section.
    If you get any errors,  you must correct all errors before proceeding.  
    Refer to the troubleshooting section below or go to the support forum for help.


SETUP ON ADMIN UI
=================
Select and install the Google Checkout payment module. The following are some 
of the fields you can update:

0. Installed GC module version
1. Enable/Disable: Enable this to use Google Checkout for your site.

2. Operation Mode: Test your site with Google Checkout's sandbox server before 
   migrating to production. You will need it signup for a separate Google Checkout 
   sandbox account at http://sandbox.google.com/checkout/sell. Your sandbox account
   will have a different Merchant ID and Merchant Key. When you are ready to run 
   against the production server, remember to update your merchant ID and key 
   when migrating.
   
3. Merchant ID and Merchant Key:(Mandatory) If any of these are not set and the 
   module is enabled, a disabled (gray) Checkout button appears on the Checkout 
   page. Set these values from your seller Google account under the 
   Settings->Integration tab. Separate Sandbox and Production.
   
4. .htaccess Basic Authentication Mode with PHP over CGI? If your site is 
   installed on a PHP CGI you must disable Basic Authentication over PHP. 
   To avoid spoofed messages (only if this feature is enabled) reaching 
   responsehandler.php, set the .htaccess file with the script linked 
   (http://your-site/admin/htaccess.php). 
   Set permission 777 for http://your-site/googlecheckout/ before running 
   the script. Remember to turn back permissions after creating the files.
   
5. Merchant Calculation Mode of Operation: Sets Merchant calculation URL for 
   Sandbox environment. Could be HTTP or HTTPS. (Checkout production environment 
   always requires HTTPS.)
   
6. Disable Google Checkout for Virtual Goods?: This configuration is enabled and
    there is any virtual good in the cart the Google Checkout button will be
    shown disabled.
    (double check http://checkout.google.com/seller/policies.html#4)
    
7. Allow US PO BOX shipping. Setted to false, you won't ship to any PO address
    in the US.

8. Default Values for Real Time Shipping Rates: Set your default values for 
   all merchant calculated shipping rates. This values will be used if for any 
   reason Google Checkout cannot reach your API callback to calculate the 
   shipping price.

9. GoogleCheckout Carrier Calculated Shipping. Set if you want or not to use CCS.
    Note that if you enable CCS, all Merchant Calculation Modules will be IGNORED
    for GC orders. Only Flat Rate shippings will be included with CCS.

10. Carrier Calculater Shipping Configuration. Set Default Values, Fix and 
    Variable charge for each specific CCS method. 
    The DV contains the default shipping cost for a carrier-calculated-shipping 
     option. If Google is unable to obtain the carrier's shipping rate for a 
     shipping option, the buyer will still have the option of selecting that 
     shipping option and paying the DV value to have the order shipped.
     http://code.google.com/apis/checkout/developer/Google_Checkout_XML_API_Carrier_Calculated_Shipping.html#tag_price
    The Fix value allows you to specify a fixed charge that will be added to the
     total cost of an order.
     http://code.google.com/apis/checkout/developer/Google_Checkout_XML_API_Carrier_Calculated_Shipping.html#tag_additional-fixed-charge
    The Variable Charge pecifies a percentage amount by which a carrier-calculated
     shipping rate will be adjusted. The tag's value may be positive or negative.
     http://code.google.com/apis/checkout/developer/Google_Checkout_XML_API_Carrier_Calculated_Shipping.html#tag_additional-variable-charge-percent
    
    Set Def. Value to 0 to disable the method

10. Rounding Policy Mode and Rounding Policy Rule: Determines how Google Checkout
     will do rounding in prices.
     US default: rounding rule TOTAL, rounding mode is HALF_EVEN
     UK default: rounding rule PER_LINE, rounding mode is HALF_UP

     More info:
     http://code.google.com/apis/checkout/developer/Google_Checkout_Rounding_Policy.html

11. Cart Expiration Time: if different from NONE, this postive integrer will set 
     an expiration time from the creation of the GC button to X minutes. Where X
     is the value set. This will prevent buyers to use carts that may be deprecated
     or with wrong settings. Default is NONE. Take in care that the time in your
     server may not be syncronized with GC servers, so if you set short expiration
     times this may not be so accurate.     

12. Also send Notification with ZenCart, if enabled, will send an email using the 
     ZC internal email system if the comment in an order is larger than 254 
     chars, limit for a google send message. If this happens a warning will be 
     shown in the Admin UI. It will also send emails to the merchant account 
     when orders states are changed in the Admin UI.

13. Google Analytics Id: Add google analytics to your e-commerce. Now there is a 
     feature in GA to integrate easily with any e-commerce with GoogleCheckout.
     More info: See below "Enabling E-Commerce Reporting for Google Analytics".

14. 3rd Party Tracking: Do you want to integrate the module 3rd party tracking? 
     Add the tracker URL, NONE to disable.
    More info:
    http://code.google.com/apis/checkout/developer/checkout_pixel_tracking.html

15. Google Checkout restricted product categories: Insert here the Ids of all the 
     product categories that you want to exclude from the Google Checkout. 
     Separate them using commas (ie. "1, 3,56 , 32").
     Double check Google Policy:
     http://checkout.google.com/support/sell/bin/answer.py?answer=46174&topic=8681
     http://checkout.google.com/seller/policies.html#4

16. Continue shopping URL: The URL customers will be redirected to if they 
     follow the link back to your site after checkout. 
     (http://your-site/zencart_dir/index.php?main_page=<input data>)
     Note:Use GC_return for special page that will show all the purchased items 
      with Google Checkout

Your Google Checkout setup page is correct if, upon viewing it, a non-disabled 
Google Checkout button appears. Double check the INSTALLATION file for more info

Enabling E-Commerce Reporting for Google Analytics
==================================================
To track Google Checkout orders, you must enable e-commerce reporting for your 
website in your Google Analytics account. The following steps explain how you 
enable e-commerce reporting for your website:

   1. Log in to your Google Analytics account.
   2. Click the Edit link next to the profile you want to enable. This link 
      appears in the Settings column.
   3. On the Profile Settings page, click the Edit link in the Main Website 
      Profile Information box.
   4. Change the selected E-Commerce Website radio button from No to Yes.
More info: 
http://code.google.com/apis/checkout/developer/checkout_analytics_integration.html

Note for 3rd party tracking: Actual configuration supports just one 3rd party 
 tracking Co. And some modification maybe needed to do in the code for 
 specific trakers.
 Read:
 http://code.google.com/apis/checkout/developer/checkout_pixel_tracking.html
 And change the code here:
  googlecheckout/gcheckout.php
 Maping works this way:
  $tracking_attr_types = array(
                              'GC_attr_type1' => '3rd_attr_name1',
                              'GC_attr_type2' => '3rd_attr_name2',
                              ...
                              );
  Will be traduced to:
  <parameters>
    <url-parameter name="3rd_attr_name1" type="GC_attr_type1"/>
    <url-parameter name="3rd_attr_name2" type="GC_attr_type2"/>
    ...
  </parameters>
  
DIGITAL DELIVERY
================
All products marked as maked as "Product is Virtual" will be included in the 
 digital delivery API. This means that at the end of the GC transaction a link
 to the ZC checkout_success page will be shown as well as a description of the 
 product.
If the whole cart is virtual, no shipping to: will be shown in the GC Place 
 Order Page. 
Note: If the cart is virtual but has some custom order_totals (low_orderFee, 
 group_discount, etc), a notice saying an "Email will sent" will be show for each
 order total in the GC confirmation page. This is a limitation of GC API for
 supporting custom OT and digital delivery.

MERCHANT CALCULATED SHIPPING
============================
In order to use this module you must have some Real Time Shipping provider,
 such as USPS or FedEx. This Module must be activated and configured in
 Modules->Shipping. For each enabled module you'll have to set the default
 values in the Google Checkout Admin UI.
This Value will be used if for any reason Google Checkout cannot reach your
 API callback to calculate the shipping price. 

The available shipping methods for each shipping provider must be configured
 in /googlecheckout/shipping_methods.php in the mc_shipping_methods
 variable. If you want to disable one or more methods, just comment them out.
Be aware that if you mix flat rate and real time rates, both will be taken
 as merchant-calculated-shipping. 

Script to create new shipping methods
	http://your-site/zencart_dir/googlecheckout/shipping_generator
More Info: 
	http://your-site/zencart_dir/googlecheckout/shipping_generator/README

CARRIER CALCULATED SHIPPING
===========================
This feature allows merchants to get real time quotes without any effort. All
 calculation are done by the GC servers. You only need to specify the shipping
 methods you want to provide. 
 Google supports the carrier-calculated shipping feature for three carriers: 
 FedEx, UPS and the U.S. Postal Service (USPS)

You cannot offer carrier-calculated shipping methods and merchant-calculated
 shipping methods for the same order. However, you can offer carrier-calculated
 shipping methods and still use the Merchant Calculations API to calculate taxes 
 and adjustments for coupons and gift certificates.
 
Note: Only US Domestic shipping address are allowed right now. If shipping 
       address is Int'l only Flat Rate shippings will be shown.

More info:
 http://code.google.com/apis/checkout/developer/Google_Checkout_XML_API_Carrier_Calculated_Shipping.html#Process

COUPONS
=======
You can now use coupons with this module. Just add coupons in the Admin UI,
 and the user can addlogs/ them in his order. This is restinged to just one
 coupon per order as Zen cart standards, aldo GC supports many. 

TRACKING USERS AND ORDERS
=========================
In order to provide this support (as required for Level 2 integration), update
 the API callback request field in the seller account to 
 https://<url-site-url>/googlecheckout/responsehandler.php . 
 Note that the production Checkout server requires an SSL callback site.
The Sandbox server accepts non SSL callback URLs.

Within the Google Checkout panel, go to Settings > Integration. Set the
Callback contents to Notification as XML, rather than Notification Serial
Number.

View your Google Checkout customers and their orders in the Reports tab. 
For each order, the default starting state is Google New. 
GC Orders States in ZC Admin

(the instructions below assume you have enabled "Automatically authorize and
charge the buyer's credit card" in your GC merchant account Settings >
Preferences).

1. When an order for a regular (non-download) product is submitted  through
GC the order state in ZC admin will be auto set to "Google_Processing". When
following-up on (shipping) these orders you will need to change the order
status to "Google_Shipped".

2. When an order for a download product is submitted via GC, the order
status in ZC admin will be auto set to "Google_Shipped" (even if customer
has attempted to download or not). When following-up on download orders keep
the
status "Google_Shipped", no need to change the state.

3.. Refunding/canceling "total" GC orders works from within ZC admin.

a. If you have not yet changed a non-download order from "Google_Processing"
to "Google_Shipped" you can directly cancel/refund the TOTAL order by
selecting "Google_Canceled" (but do NOT set to "Google_Refunded").

Changing the state to "Google_Cancelled" will auto refund 100% of
transaction AND cancel the order at the same time. So there is no need to
login to GC Merchant account to process the TOTAL refund.

b. Also after an item is shipped you can refund the TOTAL amount of order by
 choosing "Google_Canceled" (but as stated above do NOT set to
"Google_Refunded").

c. However if you want to only refund a PORTION of the order amount (for
example if customer ordered two items and want to refund amount for only one
item) then you will need to log in to your GC Merchant amount and process
the partial refund. (Then Google will automatically change the order status
in ZC admin to "Google_Refunded" or "Google_Shipped and
Refunded", and you won't have to change the state in ZC admin.

d. Understanding the above then you should not ever need to apply
"Google_Refunded" or "Google_Shipped and Refunded". These states are usually
reserved for Google use only.

You can add a Tracking Number in the state change from GoogleProcessing to
GoogleShipped
A text field and a combo with the shipping providers will be show when the order
is in the GoogleProcessing state.

Any comments added during state change will be sent to the buyer account page
 if you have selected the Append Comments option.

All statechanges are added as notes in the Admin UI
All request and response messages will be logged to the file
googlecheckout/logs/response_message.log.

Refunds are added as new order totals in each order as well as part of the
history of the order.

The same for cancellations

Check states.jpg for the states machine diagram. 

PROJECT HOME
============
To check out the latest release or to report issues, go to
 https://github.com/mattclements/Google-Checkout-for-Zencart


GROUP DISCUSSIONS
=================
To meet other developers and merchants who have integrated Google Checkout
 with Zen Cart and to discuss any topics related to integration of Google 
 Checkout with Zen Cart, go to 
 https://github.com/mattclements/Google-Checkout-for-Zencart/issues


MOST COMMON MISTAKES
====================
1. Make sure you set the file attribute to 777 for 
    /googlecheckout/logs/response_error.log and /googlecheckout/logs/response_message.log files.
2. Set your Google callback url to https://<url-site-url>/googlecheckout/responsehandler.php
   In Sandbox, HTTPS is not required.
   In Production mode, HTTPS is required.
   Set the correct option in the Google Checkout Admin UI
  Links for supported SSL certificates:
    http://www.google.com/checkout/ssl-certificates
    http://checkout.google.com/support/sell/bin/answer.py?answer=57856
3. Make sure you are using the correct combination of Merchant ID and 
   Merchant Key. Remember that Sandbox and Production Mode have different ones.
4. The folder YOUR_TEMPLATE in the package refers to the folder that contains
 your templates, you must put the files in /includes/templates/YOUR_TEMPLATE/ 
 in your own templates dir there you should see the GC buy button.
	
TROUBLESHOOTING
===============
1. Problem: Fatal error: Call to undefined function: getallheaders() error.
	Solution: You webhosting company does not have the function getallheaders()
	 enable on your webserver. Fixed in v1.0.5
2. Problem: /public_html/googlecheckout/logs/response_message.log) [function.fopen]:
    failed to open stream: Permission denied.
	Solution: Set the file attribute to 777 for /googlecheckout/logs/response_error.log
	 and /googlecheckout/logs/response_message.log files.
3. Problem: Test order shows up in Google but not admin.
	Solution: There is an error somewhere in the file /googlecheckout/responsehandler.php
4. Problem: <error-message>Malformed URL component: 
   expected id: (\d{10})|(\d{15}), but got 8***********4 </error-message>
	Solution: You have an extra space after your Google merchant id. Go to
	 Admin->payment.  Edit Googlecheckout module.  Extra space will disappear.
	 Click update button.
5. Problem: <error-message>No seller found with id 7************8</error-message>
	Solution: Wrong merchant id.  Sandbox merchant id can only be use with sandbox
	 accounts.  Sandbox and Live mode use different merchant id. 
6. Problem: sun.security.validator.ValidatorException: PKIX path building
    failed: sun.security.provider.certpath.SunCertPathBuilderException: unable
    to find valid certification path to requested target
	Solution: Your SSL certificate is not accepted by Google Checkout.
	Links for supported SSL certificates:
    http://www.google.com/checkout/ssl-certificates
    http://checkout.google.com/support/sell/bin/answer.py?answer=57856
7. Problem: <error-message>Bad Signature on Cart</error-message>
	Solution: Incorrect Merchant key.
8. Problem: (/public_html/googlecheckout/logs/response_error.log) 
    Tue Nov 28 8:56:21 PST 2006:- Shopping cart not obtained from session. 
   Solution: Set to False admin->configuration->session->Prevent Spider
    Sessions configuration (Thx dawnmariegifts, beta tester)
   Side effects: You'll see spiders as active users.
   Solution 2 (Recommended): Remove any string like 'jakarta' in the includes/spider.txt
9. Problem: (Fixed in new versions of the Module)
    Warning: main(admin/includes/configure.php) [function.main]: failed to open 
     stream: No such file or directory in /public_html/googlecheckout/gcheckout.php
     on line 33
		Fatal error: main() [function.require]: Failed opening required 
     'admin/includes/configure.php' (include_path='.:/usr/lib/php:/usr/local/lib/php')
     in /public_html/googlecheckout/gcheckout.php on line 33
   Solution:
			Change googlecheckout/gcheckout.php Line 33 'admin' for the modified admin
			 directory
      require_once('admin/includes/configure.php');   
10. IIS Note::  For HTTP Authentication to work with IIS, 
		the PHP directive cgi.rfc2616_headers must be set to 0 (the default value).
		Will use the $_SERVER['HTTP_AUTHORIZATION'] header
11. No shipping is shown in the Cart.
     Have you checked that this constant have the correct value set in the includes/configure.php?
     DIR_FS_CATALOG and DIR_WS_MODULES
     Try adding an echo in googlecheckout/gcheckout.php
       echo $module_directory = DIR_FS_CATALOG . DIR_WS_MODULES . 'shipping/';
     and in the shopping_cart.php page see if the string u see is the correct 
     dir where the shipping file are.
12. "The address that you are shipping from is invalid. Please send a correct US address"
	Evaluate the XML with the section `<ship-from id="Store_origin">`. If this only holds the Post Code,
	then change the Store Address and Phone on Admin -> Configuration -> My Store to your full address.
KNOWN BUGS -
==========
(Report bugs at 
 https://github.com/mattclements/Google-Checkout-for-Zencart/issues)

CHANGELOG
=========
See CHANGELOG file. 	 	
