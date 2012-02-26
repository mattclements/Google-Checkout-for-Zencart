<?php
/*
  Copyright (C) 2007 Google Inc.

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
/* **GOOGLE CHECKOUT ** v1.4.6
  @version $Id: googlecheckout.php 5634 2007-10-08 14:58:57Z ropu $
*/

 //** GOOGLE CHECKOUT **
  define('MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_TITLE', 'GoogleCheckout');
  define('MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_DESCRIPTION', 'GoogleCheckout');
  define('MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_OPTION', '- Or use -');
  define('GOOGLECHECKOUT_STRING_WARN_USING_SANDBOX', 'GC is set to use SANDBOX. Order will be processed but NOT charged.');
  define('GOOGLECHECKOUT_STRING_WARN_NO_MERCHANT_ID_KEY', 'Google Checkout Merchant Id or Key has not been setted up');
  define('GOOGLECHECKOUT_STRING_WARN_VIRTUAL', 'Some download products in your cart are currently not available via Google Checkout.');
  define('GOOGLECHECKOUT_STRING_WARN_EMPTY_CART', 'The Cart is empty');
  define('GOOGLECHECKOUT_STRING_WARN_OUT_OF_STOCK', 'Some products are Out of Stock');
  define('GOOGLECHECKOUT_STRING_WARN_MULTIPLE_SHIP_TAX', 'There are multiple shipping options selected and they use different shipping tax tables or some dont use tax tables');
  define('GOOGLECHECKOUT_STRING_WARN_MIX_VERSIONS', 'The Version of the installed module in the Admin UI is %s and the one of the package is %s, Remove/Reinstall the module');
  define('GOOGLECHECKOUT_STRING_WARN_WRONG_SHIPPING_CONFIG', 'DIR_FS_CATALOG and DIR_WS_MODULES may be wrong configured in includes/configure.php file. This dir doens\'t exists: %s');
  define('GOOGLECHECKOUT_STRING_WARN_RESTRICTED_CATEGORY', 'Some items are in GC restricted category.');

// This string will be added after the product name and description in the yellow box in the GC confirmation page for all Digital Goods.
  define('GOOGLECHECKOUT_STRING_EXTRA_DIGITAL_CONTENT', 'Allow 2-5 minutes to get all the transaction processed.');
  
  define('GOOGLECHECKOUT_STRING_ERR_SHIPPING_CONFIG', ' Error: Shipping Methods not configured ');
    
  define ('GOOGLECHECKOUT_FLAT_RATE_SHIPPING', 'Flat Rate Per Order');
  define ('GOOGLECHECKOUT_ITEM_RATE_SHIPPING', 'Flat Rate Per Item');
  define ('GOOGLECHECKOUT_TABLE_RATE_SHIPPING', 'Vary by Weight/Price');

  define ('GOOGLECHECKOUT_TABLE_NO_MERCHANT_CALCULATION', 'No merchant calculation shipping selected');
  define ('GOOGLECHECKOUT_MERCHANT_CALCULATION_NOT_CONFIGURED', ' not configured!<br />');
  
  define ('GOOGLECHECKOUT_ERR_REGULAR_CHECKOUT', 'Google Checkout Can not be used in regular checkout, click in the Google Checkout Button Below');
  
  define ('GOOGLECHECKOUT_ERR_DUPLICATED_ORDER', 'Duplicated NewOrderNotification #%s Cart order #%s');
  
  // Google Request Success messages
  define('GOOGLECHECKOUT_SUCCESS_SEND_CHARGE_ORDER', 'Sent Google Charge Order Command');
  define('GOOGLECHECKOUT_SUCCESS_SEND_PROCESS_ORDER', 'Sent Google Process Order Command');
  define('GOOGLECHECKOUT_SUCCESS_SEND_DELIVER_ORDER', 'Sent Google Deliver Order Command');
  define('GOOGLECHECKOUT_SUCCESS_SEND_ARCHIVE_ORDER', 'Sent Google Archive Order Command');
  define('GOOGLECHECKOUT_SUCCESS_SEND_REFUND_ORDER', 'Sent Google Full Refund Order Command');
  define('GOOGLECHECKOUT_SUCCESS_SEND_CANCEL_ORDER', 'Sent Google Cancel Order Command');
  define('GOOGLECHECKOUT_SUCCESS_SEND_MESSAGE_ORDER', 'Sent Google Message to the Buyer');
  define('GOOGLECHECKOUT_SUCCESS_SEND_NEW_USER_CREDENTIALS', 'Sent New Buyer Credentials to the Buyer');
  
  define('GOOGLECHECKOUT_SUCCESS_SEND_MERCHANT_ORDER_NUMBER', 'Sent Merchant Order Number');
  define('GOOGLECHECKOUT_SUCCESS_SEND_ADMIN_COPY_EMAIL', 'Sent Status Change Message to Admin email');

  // Google Request warning Messages
  define('GOOGLECHECKOUT_WARNING_CHUNK_MESSAGE', 'Google Message was longer than %s, it was chunked when sent to the buyer.');
  define('GOOGLECHECKOUT_WARNING_SYSTEM_EMAIL_SENT', 'A regular email was sent to the buyer with the full message');

  // Google Request Error Messages
  define('GOOGLECHECKOUT_ERR_SEND_CHARGE_ORDER', 'Error sending Google Charge Order, see error logs');
  define('GOOGLECHECKOUT_ERR_SEND_PROCESS_ORDER', 'Error sending Google Process Order, see error logs');
  define('GOOGLECHECKOUT_ERR_SEND_DELIVER_ORDER', 'Error sending Google Deliver Order, see error logs');
  define('GOOGLECHECKOUT_ERR_SEND_ARCHIVE_ORDER', 'Error sending Google Archive Order, see error logs');
  define('GOOGLECHECKOUT_ERR_SEND_REFUND_ORDER', 'Error sending Google Refund Order, see error logs');
  define('GOOGLECHECKOUT_ERR_SEND_CANCEL_ORDER', 'Error sending Google Cancel Order, see error logs');
  define('GOOGLECHECKOUT_ERR_SEND_MESSAGE_ORDER', 'Error sending Google Message, see error logs');
  define('GOOGLECHECKOUT_ERR_SEND_NEW_USER_CREDENTIALS', 'Error sending New Buyer Credentials, see error logs');
  define('GOOGLECHECKOUT_ERR_SEND_MERCHANT_ORDER_NUMBER', 'Error sending Merchant Order Number, see error logs');
  define('GOOGLECHECKOUT_ERR_INVALID_STATE_TRANSITION', 'Invalid Google Checkout State transition: %s => %s, revert to %s and try again. See README');
  
  
// Remember max chars in 255, included that store name, email and pass that will replace the %s
  define('GOOGLECHECKOUT_NEW_CREDENTIALS_MESSAGE', 'These are your Credentials to log into %s site. User: %s Pass: %s Please change your password after logging in - Change it in "MyAccount" area.');
  
  // Coupons
  define('GOOGLECHECKOUT_COUPON_ERR_ONE_COUPON', 'Sorry, only one coupon per order');
  define('GOOGLECHECKOUT_COUPON_ERR_MIN_PURCHASE', 'Sorry, the minimum purchase hasn\'t been reached to use this coupon');

  define('GOOGLECHECKOUT_COUPON_DISCOUNT', 'Discount Coupon: ');
  define('GOOGLECHECKOUT_COUPON_FREESHIP', 'Free Shipping Coupon: ');

  // New Orders
  define('GOOGLECHECKOUT_STATE_NEW_ORDER_NUM', 'Google Checkout Order No: ');
  define('GOOGLECHECKOUT_STATE_NEW_ORDER_MC_USED', 'Merchant Calculations used: ');
  define('GOOGLECHECKOUT_STATE_NEW_ORDER_BUYER_USER', 'NEW Buyer\'s User: ');
  define('GOOGLECHECKOUT_STATE_NEW_ORDER_BUYER_PASS', 'Buyer\'s Password: ');
  
  // States
  define('GOOGLECHECKOUT_STATE_STRING_TIME', 'Time: ');
  define('GOOGLECHECKOUT_STATE_STRING_NEW_STATE', 'New State: ');

  define('GOOGLECHECKOUT_STATE_STRING_ORDER_READY_CHARGE', 'Order ready to be charged!');
  define('GOOGLECHECKOUT_STATE_STRING_PAYMENT_DECLINED', 'Payment was declined!');
  define('GOOGLECHECKOUT_STATE_STRING_ORDER_CANCELED', 'Order was canceled.');
  define('GOOGLECHECKOUT_STATE_STRING_ORDER_CANCELED_REASON', 'Reason: ');
  define('GOOGLECHECKOUT_STATE_STRING_ORDER_CANCELED_BY_GOOG', 'Order was canceled by Google.');
  define('GOOGLECHECKOUT_STATE_STRING_ORDER_DELIVERED', 'Order was Shipped.');

  define('GOOGLECHECKOUT_STATE_STRING_TRACKING', 'Shipping Tracking Data: ');
  define('GOOGLECHECKOUT_STATE_STRING_TRACKING_CARRIER', 'Carrier: ');
  define('GOOGLECHECKOUT_STATE_STRING_TRACKING_NUMBER', 'Tracking Number: ');

  define('GOOGLECHECKOUT_STATE_STRING_LATEST_CHARGE', 'Latest charge amount: ');
  define('GOOGLECHECKOUT_STATE_STRING_TOTAL_CHARGE', 'Total charge amount: ');
  
  define('GOOGLECHECKOUT_STATE_STRING_LATEST_REFUND', 'Latest refund amount: ');
  define('GOOGLECHECKOUT_STATE_STRING_TOTAL_REFUND', 'Total Order refund amount: ');
  define('GOOGLECHECKOUT_STATE_STRING_GOOGLE_REFUND', 'Google Refund: ');
  define('GOOGLECHECKOUT_STATE_STRING_NET_REVENUE', 'Net revenue: ');

  define('GOOGLECHECKOUT_STATE_STRING_RISK_INFO', 'Risk Information: ');
  define('GOOGLECHECKOUT_STATE_STRING_RISK_ELEGIBLE', ' Elegible for Protection: ');
  define('GOOGLECHECKOUT_STATE_STRING_RISK_AVS', ' Avs Response: ');
  define('GOOGLECHECKOUT_STATE_STRING_RISK_CVN', ' Cvn Response: ');
  define('GOOGLECHECKOUT_STATE_STRING_RISK_CC_NUM', ' Partial CC number: ');
  define('GOOGLECHECKOUT_STATE_STRING_RISK_ACC_AGE', ' Buyer account age: ');

// Custom GC order states names  
  define('GOOGLECHECKOUT_CUSTOM_ORDER_STATE_NEW', 'Google New');
  define('GOOGLECHECKOUT_CUSTOM_ORDER_STATE_PROCESSING', 'Google Processing');
  define('GOOGLECHECKOUT_CUSTOM_ORDER_STATE_DIGITAL_PROCESSED', 'Google Digital Processed');
  define('GOOGLECHECKOUT_CUSTOM_ORDER_STATE_SHIPPED', 'Google Shipped');
  define('GOOGLECHECKOUT_CUSTOM_ORDER_STATE_REFUNDED', 'Google Refunded');
  define('GOOGLECHECKOUT_CUSTOM_ORDER_STATE_SHIPPED_REFUNDED', 'Google Shipped and Refunded');
  define('GOOGLECHECKOUT_CUSTOM_ORDER_STATE_CANCELED', 'Google Canceled');
  
  // ** END GOOGLE CHECKOUT **
?>