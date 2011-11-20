/**
 * add_sibling
 * @param {} tr 
 */
 function add_sibling(table_id,tr_id) {
 	var table = document.getElementById(table_id);
 	var tr = document.getElementById(tr_id).cloneNode(true);
	table.appendChild(tr);
 }
 
 var help_texts = Array(
 												'The Shipping Code is the name of the shipping class you are going to install. Is the same as the file name.',
 												'The Shipping Fancy Name is the name for the shipping provider you want the buyer to see in the shipping combo in the GC page.<br />(ie. <b>Provider</b>: Method)',
 												'The Method Code is the internal code that each shipping module uses.',
 												'The Method Fancy Name is the name for the shipping method you want the buyer to see in the shipping combo in the GC page. Remember this name <i>MUST</i> be Unique <br />(ie. Provider: <b>Method</b>)',
 												'Double check "Method Fancy Name", duplicated ones will have a "_#" added to the end. Some methods may not appear, as some shippers don\'t offer them for these shipping addresses',
 												'The recomended Default value is the cost that is recommended to setup in the Admin UI -> Modules -> Payment -> GC, Default Values for Real Time Shipping Rates.'
 												);
 
 /**
  * show_help
  * @param {int} help_index 
  */
  function show_help(help_index) {
  	var help_div = document.getElementById('help');
  	var help_text = document.getElementById('help_text');
  	help_div.style.top = window.scrollY;
  	help_text.innerHTML = help_texts[help_index];
  	help_div.style.display = 'block';
  }
  var tabs = Array ('shipper_code', 'methdos');
  /**
   * showtab
   * @param {table} tabname
   */
   function showtab(tabname) {
   	for(var i=0; i<tabs.length; i++) {
   		document.getElementById(tabs[i]).style.display = 'none';
   	}
   	document.getElementById(tabname).style.display = '';
   }