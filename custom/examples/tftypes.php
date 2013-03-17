<?php

/* * ******************************************************************* */
/* custom/tftypes.php                                                 */
/* This file includes all custom TfType php classes for fields.       */
/* Called from: inc/tf.inc.php                                        */
/* * ******************************************************************* */

/*
  In this example my system have products and members, and every member can `like` any product.
  Tables: products, likes, members
  On the members page, the admin want to be able to tell which products a member like.
  On the products page, the admin want to be able to tell which members liked liked each product.
  On the products page, the admin want to see how many likes each product have, near the product name.

  First install and configure TableField.
  Now go to tft admin and add all the existing fields and permissions.
  On the products table, add 'members' as sub-table, and vice-versa.
  On tft admin add a field to `products` that is called 'likes' and set its class to 'NumLoved'.
  Now create the file custom/tftypes.php and add a new PHP class.
  This class overrun the method `view` like that:
 */
global $tf;

class TfTypeNumLoved extends TFTypeFictive {

	function view() {
		if (empty($this->curid))
			return '';
		$res = mysql_query("SELECT COUNT(*) FROM `likes` WHERE product_id=$this->curid");
		if (!$res)
			return '';
		$row = mysql_fetch_row($res);
		return $row[0];
	}

	/*
	  Now let's say that I want to display this number as a rating of stars, *=0-5 likes, **=6-20, ***=over 20 likes
	  I overrun the method htmlView:
	 */

	function htmlView($frm, $more = '') {
		global $tf;
		$value = $this->view();
		if ($value === '')
			return '';
		$stars = '*';
		if ($value > 5)
			$stars = '**';
		if ($value > 20)
			$stars = '***';
		return "<span class='liked' id='liked_" . $this->curid . "' title='$value' $more>$stars</span>";
	}

}

