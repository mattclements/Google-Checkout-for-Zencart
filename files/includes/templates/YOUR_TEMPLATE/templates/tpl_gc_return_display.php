<?php
/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=GC_return.<br />
 * Displays products names and also purchased products
 *
 * @package templateSystem
 * @copyright Copyright 2007 google inc.
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: tpl_gc_return_display.php 1235 2007-04-23 01:35:04Z ropu $
 */
 //require(DIR_WS_MODULES . '/debug_blocks/product_info_prices.php');
 $zc_show_also_purchased = true;
?>

<div class="centerColumn" id="productGeneral">

<!--bof Product Name-->
<table class="cartContentsDisplay" width="100%" cellspacing="0" cellpadding="1" border="0">
  <tr valign="top">
    <td valign="top" align="center"><h2 id="productPrices" class="productGeneral"><?=TEXT_THANK_YOU;?></h2></td>
  </tr>
  <tr valign="top">
    <td valign="top" align="center"><img src="http://checkout.google.com/seller/images/google_checkout.gif" /></td>
  </tr>
  <tr valign="top" class="tableHeading">
    <td class="scQuantityHeading"><h2 id="productPrices" class="productGeneral"><?=TEXT_JUST_BOUGHT;?></h2></td>
  </tr>


<?php
foreach($products_names as $products_name) {
?>
  <tr valign="top">
    <td class="rowEven"><h1 id="productName" class="productGeneral"><?=$products_name;?></h1></td>
  </tr>
<?php } ?>
<!--eof Product Name-->
</table>
<br class="clearBoth" />

<!--bof also purchased products module-->




<?php 
if (isset($products) && SHOW_PRODUCT_INFO_COLUMNS_ALSO_PURCHASED_PRODUCTS > 0 && MIN_DISPLAY_ALSO_PURCHASED > 0) {
//echo $products;
  $also_purchased_products = $db->Execute("select p.products_id, p.products_image
                     from " . TABLE_ORDERS_PRODUCTS . " opa, " . TABLE_ORDERS_PRODUCTS . " opb, "
                            . TABLE_ORDERS . " o, " . TABLE_PRODUCTS . " p
                     where opa.products_id in (" . $products . ")
                     and opa.orders_id = opb.orders_id
                     and opb.products_id not in  (" .$products . ")
                     and opb.products_id = p.products_id
                     and opb.orders_id = o.orders_id
                     and p.products_status = 1
                     group by p.products_id
                     order by o.date_purchased desc
                     limit " . MAX_DISPLAY_ALSO_PURCHASED);

  $num_products_ordered = $also_purchased_products->RecordCount();

  $row = 0;
  $col = 0;
  $list_box_contents = array();
  $title = '';

  // show only when 1 or more and equal to or greater than minimum set in admin
  if ($num_products_ordered >= MIN_DISPLAY_ALSO_PURCHASED && $num_products_ordered > 0) {
    if ($num_products_ordered < SHOW_PRODUCT_INFO_COLUMNS_ALSO_PURCHASED_PRODUCTS) {
      $col_width = floor(100/$num_products_ordered);
    } else {
      $col_width = floor(100/SHOW_PRODUCT_INFO_COLUMNS_ALSO_PURCHASED_PRODUCTS);
    }

    while (!$also_purchased_products->EOF) {
      $also_purchased_products->fields['products_name'] = zen_get_products_name($also_purchased_products->fields['products_id']);
      $list_box_contents[$row][$col] = array('params' => 'class="centerBoxContentsAlsoPurch"' . ' ' . 'style="width:' . $col_width . '%;"',
      'text' => (($also_purchased_products->fields['products_image'] == '' and PRODUCTS_IMAGE_NO_IMAGE_STATUS == 0) ? '' : '<a href="' . zen_href_link(zen_get_info_page($also_purchased_products->fields['products_id']), 'products_id=' . $also_purchased_products->fields['products_id']) . '">' . zen_image(DIR_WS_IMAGES . $also_purchased_products->fields['products_image'], $also_purchased_products->fields['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</a><br />') . '<a href="' . zen_href_link(zen_get_info_page($also_purchased_products->fields['products_id']), 'products_id=' . $also_purchased_products->fields['products_id']) . '">' . $also_purchased_products->fields['products_name'] . '</a>');

      $col ++;
      if ($col > (SHOW_PRODUCT_INFO_COLUMNS_ALSO_PURCHASED_PRODUCTS - 1)) {
        $col = 0;
        $row ++;
      }
      $also_purchased_products->MoveNext();
    }
  }
  if ($also_purchased_products->RecordCount() > 0 && $also_purchased_products->RecordCount() >= MIN_DISPLAY_ALSO_PURCHASED) {
    $title = '<h2 class="centerBoxHeading">' . TEXT_ALSO_PURCHASED_PRODUCTS . '</h2>';
    $zc_show_also_purchased = true;
  }
}
//  include(DIR_WS_MODULES . zen_get_module_directory(FILENAME_ALSO_PURCHASED_PRODUCTS));
?>

<?php if ($zc_show_also_purchased == true) { ?>
<div class="centerBoxWrapper" id="alsoPurchased">
<?php
  require($template->get_template_dir('tpl_columnar_display.php',DIR_WS_TEMPLATE, $current_page_base,'common'). '/tpl_columnar_display.php');
?>
</div>
<?php } ?>

<!--eof also purchased products module-->

</div>