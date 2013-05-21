<?php
/**
 * Page Template
 *
 * Displays simple "product not found" message if the selected product's details cannot be located in the database
 *
 * @package templateSystem
 * @copyright Copyright 2007 google inc.
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: tpl_gc_return_noproduct.php 1235 2007-04-23 01:35:04Z ropu $
 */
?>
<div class="centerColumn" id="productInfoNoProduct">

<div id="productInfoNoProductMainContent" class="content"><?php echo TEXT_PRODUCT_NOT_FOUND; ?></div>

<div class="buttonRow back"><?php zen_back_link() . zen_image_button(BUTTON_IMAGE_CONTINUE, BUTTON_CONTINUE_ALT) . '</a>'; ?></div>

<?php
//// bof: missing
$show_display_category = $db->Execute(SQL_SHOW_PRODUCT_INFO_MISSING);

while (!$show_display_category->EOF) {
?>

<?php
  if ($show_display_category->fields['configuration_key'] == 'SHOW_PRODUCT_INFO_MISSING_FEATURED_PRODUCTS') {
/**
 * display the featured product center box
 */
    include(DIR_WS_MODULES . zen_get_module_directory(FILENAME_FEATURED_PRODUCTS_MODULE));
  }
?>

<?php
  if ($show_display_category->fields['configuration_key'] == 'SHOW_PRODUCT_INFO_MISSING_SPECIALS_PRODUCTS') {
/**
 * display the special product center box
 */
    include(DIR_WS_MODULES . zen_get_module_directory(FILENAME_SPECIALS_INDEX));
  }
?>

<?php
  if ($show_display_category->fields['configuration_key'] == 'SHOW_PRODUCT_INFO_MISSING_NEW_PRODUCTS') {
/**
 * display the new product center box
 */
    include(DIR_WS_MODULES . zen_get_module_directory(FILENAME_NEW_PRODUCTS));
  }
?>

<?php
  if ($show_display_category->fields['configuration_key'] == 'SHOW_PRODUCT_INFO_MISSING_UPCOMING') {
/**
 * display the upcoming product center box
 */
    include(DIR_WS_MODULES . zen_get_module_directory(FILENAME_UPCOMING_PRODUCTS));
  }
?>
<?php
  $show_display_category->MoveNext();
} //// eof: missing
?>
</div>