<?php
// to check whether accessed directly
if (!defined('ABSPATH')) {
	exit;
}


class Elex_CM_Price_Discount_Admin
{

	public function __construct($execute = true)
	{

		$this->sales_method = get_option('eh_product_choose_sale_regular', 'sale');
		if (true == $execute) {


			add_action('woocommerce_single_product_summary', array($this, 'elex_cm_product_page_remove_add_to_cart_option')); //function to remove add to cart at product page

			add_filter('woocommerce_loop_add_to_cart_link', array($this, 'elex_cm_shop_remove_add_to_cart'), 100, 2); // function to remove add to cart from shop page
			// add_action('wp_head', array($this, 'custom_css_for_add_to_cart'));
			add_filter('woocommerce_get_price_html', array($this, 'elex_cm_get_price_html'), 99, 2);
			add_filter('woocommerce_is_purchasable', array(&$this, 'elex_cm_is_product_purchasable'), 99, 2); //to hide add to cart button when price is hidden
			add_filter('woocommerce_loop_add_to_cart_link', array($this, 'elex_cm_add_to_cart_text_url_replace'), 1, 2); //to replace add to cart with user defined url
			add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'elex_cm_add_to_cart_text_content_replace'), 1, 1); //to replace add to cart with user defined placeholder text for product page

			//------------
			add_filter('woocommerce_product_is_on_sale', array($this, 'elex_cm_product_is_on_sale'), 99, 2);
			add_filter('woocommerce_product_add_to_cart_text', array($this, 'elex_cm_view_product_text'), 99, 2);
			//add_filter('woocommerce_product_is_visible', array($this, 'get_product_visibility'), 100, 2);
		}
		//----------
		add_action('wp', array($this, 'elex_cm_hide_cart_checkout_pages'));
		$this->init_fields();
	}

	public function elex_cm_get_price_html($price, $product)
	{
		if (!$product->is_purchasable()) {
			if ($this->elex_cm_is_hide_price($product)) {
				if ($this->elex_cm_is_price_hidden_in_product_meta($product)) {
					$price = $this->elex_cm_get_placeholder_text_product_hide_price($product);
				} else {
					$price = $this->elex_cm_get_placeholder_text($product, $price);
				}
			}
		}
		return $price;
	}
	public function elex_cm_hide_cart_checkout_pages()
	{
		$hide = false;
		if ('yes' == get_option('eh_pricing_discount_cart_catalog_mode_remove_cart_checkout')) {
			if (!(get_option('eh_pricing_discount_price_catalog_mode_exclude_admin') == 'yes' && 'administrator' == $this->current_user_role)) {
				$hide = true;
			}
		}
		$cart     = is_page(wc_get_page_id('cart'));
		$checkout = is_page(wc_get_page_id('checkout'));

		wp_reset_query();
		if ($hide && ($cart || $checkout)) {

			wp_redirect(home_url());
			exit;
		}
	}

	public function elex_cm_shop_remove_add_to_cart($args, $product)
	{
		$product_id = $this->elex_cm_get_product_id($product);
		$add_to_cart_link = $args;

		if (('yes' == get_post_meta($product_id, 'product_adjustment_hide_addtocart_catalog', true)) && ('yes' == get_post_meta($product_id, 'product_adjustment_hide_addtocart_catalog_shop', true) || '' == get_post_meta($product_id, 'product_adjustment_hide_addtocart_catalog_shop', true))) {
			if (!(get_post_meta($product_id, 'product_adjustment_exclude_admin_catalog', true) == 'yes' && 'administrator' == $this->current_user_role)) {
				$add_to_cart_link = '';
				$place_holder = get_post_meta($product_id, 'product_adjustment_hide_addtocart_placeholder_catalog', true);
				if (!empty($place_holder)) {
					$place_holder = $this->elex_cm_return_wpml_string($place_holder, 'Remove Add-to-cart - Shop');
					return $place_holder;
				}
			}
		} elseif ('yes' == get_option('eh_pricing_discount_cart_catalog_mode') && 'yes' == get_option('elex_catalog_remove_addtocart_shop')) {
			if (!(get_option('eh_pricing_discount_price_catalog_mode_exclude_admin') == 'yes' && 'administrator' == $this->current_user_role)) {
				$add_to_cart_link = '';
				$place_holder = get_option('eh_pricing_discount_cart_catalog_mode_text');
				if (!empty($place_holder)) {
					$place_holder = $this->elex_cm_return_wpml_string($place_holder, 'Remove Add-to-cart - Shop');
					return $place_holder;
				}
			}
		}

		return $add_to_cart_link;
	}


	public function elex_cm_view_product_text($text, $product)
	{

		if ($this->elex_cm_is_hide_price($product) === true) {

			$text = $this->elex_cm_return_wpml_string('Read More', 'Remove Add-to-cart - Shop');
		}
		return $text;
	}

	public function elex_cm_product_is_on_sale($on_sale, $product)
	{
		if ($this->elex_cm_is_hide_price($product) === true) {
			$on_sale = false;
		} else {
			if ($this->elex_cm_get_product_type($product) != 'grouped') {
				$regular_price = $product->get_regular_price();
				$sale_price = $product->get_price();
				if (empty($sale_price)) {
					$on_sale = false;
				} else {
					if ($sale_price < $regular_price) {
						$on_sale = true;
					}
				}
			}
		}
		return $on_sale;
	}

	public function elex_cm_get_add_to_cart_placeholder_text()
	{
		if (is_user_logged_in()) {
			$add_to_cart_text = get_option('eh_pricing_discount_cart_user_role_text');
		} else {
			$add_to_cart_text = get_option('eh_pricing_discount_cart_unregistered_user_text');
		}

		if (!empty($add_to_cart_text)) {
			$add_to_cart_text = $this->elex_cm_return_wpml_string($add_to_cart_text, 'Remove Add-to-cart - Product');
			return $add_to_cart_text;
		}
	}
	public function elex_cm_get_add_to_cart_product_placeholder_text($product_id)
	{
		if (is_user_logged_in()) {
			$add_to_cart_text = get_post_meta($product_id, 'product_adjustment_hide_addtocart_placeholder_role', true);
		} else {
			$add_to_cart_text = get_post_meta($product_id, 'product_adjustment_hide_addtocart_placeholder_unregistered', true);
		}

		if (!empty($add_to_cart_text)) {
			$add_to_cart_text = $this->elex_cm_return_wpml_string($add_to_cart_text, 'Remove Add-to-cart - Product');
			return $add_to_cart_text;
		}
	}

	public function elex_cm_product_page_remove_add_to_cart_option()
	{
		global $product;
		$product_id = $this->elex_cm_get_product_id($product);

		//individual catalog mode

		if (('yes' == get_post_meta($product_id, 'product_adjustment_hide_addtocart_catalog', true)) && (('yes' == get_post_meta($product_id, 'product_adjustment_hide_addtocart_catalog_product', true)) || ('' == get_post_meta($product_id, 'product_adjustment_hide_addtocart_catalog_product', true)))) {
			if (!(get_post_meta($product_id, 'product_adjustment_exclude_admin_catalog', true) == 'yes' && 'administrator' == $this->current_user_role)) {
				$this->elex_cm_remove_add_to_cart_action_product_page($product);
				$place_holder = get_post_meta($product_id, 'product_adjustment_hide_addtocart_placeholder_catalog', true);
				if (!empty($place_holder)) {
					$place_holder = $this->elex_cm_return_wpml_string($place_holder, 'Remove Add-to-cart - Product');
					echo wp_kses_post($place_holder);
				}
			}
		} elseif ('yes' == get_post_meta($product_id, 'product_adjustment_customize_addtocart_catalog', true)) {

			$url_product_page = get_post_meta($product_id, 'product_adjustment_customize_addtocart_btn_url_catalog', true);
			$button_text_product_page = get_post_meta($product_id, 'product_adjustment_customize_addtocart_prod_btn_text_catalog', true);
			if (('' != $url_product_page) && ('' != $button_text_product_page)) {
				if (!(get_post_meta($product_id, 'product_adjustment_exclude_admin_catalog', true) == 'yes' && 'administrator' == $this->current_user_role)) {
					$this->elex_cm_remove_add_to_cart_action_product_page($product);
					$this->elex_cm_redirect_addtocart_product_page($url_product_page, $button_text_product_page);
				}
			}
		} else if ('yes' == get_option('eh_pricing_discount_cart_catalog_mode') && 'yes' == get_option('elex_catalog_remove_addtocart_product')) {
			if (!(get_option('eh_pricing_discount_price_catalog_mode_exclude_admin') == 'yes' && 'administrator' == $this->current_user_role)) {
				$this->elex_cm_remove_add_to_cart_action_product_page($product);
				$place_holder = get_option('eh_pricing_discount_cart_catalog_mode_text');

				if (!empty($place_holder)) {
					$place_holder = $this->elex_cm_return_wpml_string($place_holder, 'Remove Add-to-cart - Product');
					echo wp_kses_post($place_holder);
				}
			}
		} elseif ('yes' == get_option('eh_pricing_discount_replace_cart_catalog_mode')) {
			if ('' != $this->replace_add_to_cart_button_url_shop_catalog && '' != $this->replace_add_to_cart_button_text_product_catalog) {
				if (!(get_option('eh_pricing_discount_price_catalog_mode_exclude_admin') == 'yes' && 'administrator' == $this->current_user_role)) {
					$this->elex_cm_remove_add_to_cart_action_product_page($product);
					$this->elex_cm_redirect_addtocart_product_page($this->replace_add_to_cart_button_url_shop_catalog, $this->replace_add_to_cart_button_text_product_catalog);
				}
			}
		}
	}

	public function elex_cm_remove_add_to_cart_action_product_page($product)
	{
		if ($this->elex_cm_get_product_type($product) == 'variable') {
			remove_action('woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20);
		} else {
			remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
		}
		// To hide the add-to-cart but some themes which couldn't hide using above actions
?>
		<style>
			.single_add_to_cart_button {
				display: none !important;
			}
		</style>
	<?php
	}

	public function elex_cm_redirect_addtocart_product_page($url_product_page, $button_text_product_page)
	{
		$button_text_product_page = $this->elex_cm_return_wpml_string($button_text_product_page, 'Replace Add-to-cart - Product');
		$secure = strpos($url_product_page, 'https://');
		$url_product_page = str_replace('https://', '', $url_product_page);
		$url_product_page = str_replace('http://', '', $url_product_page);
		$suff = (false === $secure) ? 'http://' : 'https://';
	?>
		<div id="elex_prod_div">
			<button id="elex_prod_btn" class="btn btn-success" onclick=" window.open('<?php echo esc_html($suff) . esc_html($url_product_page); ?>','_self')"><?php echo esc_html($button_text_product_page); ?></button>
		</div>
	<?php
		//Misalignment  of add-to-cart button in some themes
		add_action('woocommerce_single_product_summary', array($this, 'elex_cm_extra_button_on_product_page'), 30);
	}
	public function elex_cm_extra_button_on_product_page()
	{
	?>
		<div id='elex_prod_new_div'></div>
		<script>
			var dom = jQuery('#elex_prod_div').html();
			jQuery('#elex_prod_btn').remove();
			jQuery("#elex_prod_new_div").append(dom);
		</script>
<?php
	}

	public function init_fields()
	{
		$this->role_price_adjustment = get_option('eh_pricing_discount_price_adjustment_options', array());
		$this->current_user_role = $this->elex_cm_get_priority_user_role(wp_get_current_user()->roles);
		$this->replace_add_to_cart_catalog = get_option('eh_pricing_discount_replace_cart_catalog_mode') == 'yes' ? true : false;
		$this->replace_add_to_cart_button_text_product_catalog = get_option('eh_pricing_discount_replace_cart_catalog_mode_text_product', '');
		$this->replace_add_to_cart_button_text_shop_catalog = get_option('eh_pricing_discount_replace_cart_catalog_mode_text_shop', '');
		$this->replace_add_to_cart_button_url_shop_catalog = get_option('eh_pricing_discount_replace_cart_catalog_mode_url_shop', '');
	}

	//function to determine the user role to use in case of multiple user roles for one user
	public function elex_cm_get_priority_user_role($user_roles)
	{
		if (is_user_logged_in()) {
			return $user_roles;
		} else {
			return 'unregistered_user';
		}
	}

	//function to replace add to cart with another url for user role and unregistered user 
	public function elex_cm_add_to_cart_text_url_replace($link, $product)
	{
		$temp_data = $this->elex_cm_get_product_type($product);
		$product_id = $this->elex_cm_get_product_id($product);
		$cart_text_content = $link;
		if ('simple' === $temp_data || 'variable' === $temp_data || 'grouped' === $temp_data) {
			if ('yes' == get_post_meta($product_id, 'product_adjustment_customize_addtocart_catalog', true)) {
				$url_product_page = get_post_meta($product_id, 'product_adjustment_customize_addtocart_btn_url_catalog', true);
				$button_text_shop_page = get_post_meta($product_id, 'product_adjustment_customize_addtocart_shop_btn_text_catalog', true);
				if ('' != $button_text_shop_page) {
					if (!(get_post_meta($product_id, 'product_adjustment_exclude_admin_catalog', true) == 'yes' && 'administrator' == $this->current_user_role)) {
						if ('' == $url_product_page) {
							$cart_text_content = $this->elex_cm_replace_add_cart_text_shop($cart_text_content, $button_text_shop_page);
						} else {
							$cart_text_content = $this->elex_cm_replace_add_cart_text_shop_with_url($cart_text_content, $button_text_shop_page, $url_product_page);
						}
					}
				}
			} else if ($this->replace_add_to_cart_catalog && '' != $this->replace_add_to_cart_button_text_shop_catalog) {
				if (!(get_option('eh_pricing_discount_price_catalog_mode_exclude_admin') == 'yes' && 'administrator' == $this->current_user_role)) {
					if (empty($this->replace_add_to_cart_button_url_shop_catalog)) {
						$cart_text_content = $this->elex_cm_replace_add_cart_text_shop($cart_text_content, $this->replace_add_to_cart_button_text_shop_catalog);
					} else {
						$cart_text_content = $this->elex_cm_replace_add_cart_text_shop_with_url($cart_text_content, $this->replace_add_to_cart_button_text_shop_catalog, $this->replace_add_to_cart_button_url_shop_catalog);
					}
				}
			}
		}
		return $cart_text_content;
	}
	public function elex_cm_replace_add_cart_text_shop($cart_text_content, $shop_addtocart_text)
	{
		$cart_text_content = str_replace('Add to cart', $shop_addtocart_text, $cart_text_content);
		$cart_text_content = str_replace('Select options', $shop_addtocart_text, $cart_text_content);
		$cart_text_content = str_replace('View products', $shop_addtocart_text, $cart_text_content);
		$cart_text_content = $this->elex_cm_return_wpml_string($cart_text_content, 'Replace Add-to-cart - Shop');
		return $cart_text_content;
	}
	public function elex_cm_replace_add_cart_text_shop_with_url($cart_text_content, $shop_addtocart_text, $url)
	{
		$shop_addtocart_text = $this->elex_cm_return_wpml_string($shop_addtocart_text, 'Replace Add-to-cart - Shop');
		$secure = strpos($url, 'https://');
		$url = str_replace('https://', '', $url);
		$url = str_replace('http://', '', $url);
		$suff = (false === $secure) ? 'http://' : 'https://';
		$cart_text_content = '<a href="' . $suff . $url . '" class="button alt">' . $shop_addtocart_text . '</a>';
		return $cart_text_content;
	}



	//function to edit add to cart text of product page with placeholder text when replace add to cart button is selected

	public function elex_cm_add_to_cart_text_content_replace($text)
	{
		$cart_text_content = $text;
		global $product;
		$product_id = $this->elex_cm_get_product_id($product);
		$button_text_product_page = get_post_meta($product_id, 'product_adjustment_customize_addtocart_prod_btn_text_catalog', true);
		$product_button_text_checkbox = get_post_meta($product_id, 'product_adjustment_customize_addtocart_catalog', true);

		if ('yes' == $product_button_text_checkbox && '' != $button_text_product_page) {
			if (!(get_post_meta($product_id, 'product_adjustment_exclude_admin_catalog', true) == 'yes' && 'administrator' == $this->current_user_role)) {
				$cart_text_content = $button_text_product_page;
			}
		} else if ($this->replace_add_to_cart_catalog && '' != $this->replace_add_to_cart_button_text_product_catalog) {
			if (!(get_option('eh_pricing_discount_price_catalog_mode_exclude_admin') == 'yes' && 'administrator' == $this->current_user_role)) {
				$cart_text_content = $this->replace_add_to_cart_button_text_product_catalog;
			}
		}
		$cart_text_content = $this->elex_cm_return_wpml_string($cart_text_content, 'Replace Add-to-cart - Product');
		return $cart_text_content;
	}



	public function elex_cm_is_hide_price($product)
	{

		$hide = false;
		$product_id = $this->elex_cm_get_product_id($product);
		$temp_data = $this->elex_cm_get_product_type($product);
		if ('variation' == $temp_data) {
			$product_id = $this->elex_cm_get_product_parent_id($product);
		}
		if ('yes' == get_post_meta($product_id, 'product_adjustment_hide_price_catalog', true)) {
			if (get_post_meta($product_id, 'product_adjustment_exclude_admin_catalog', true) == 'yes' && 'administrator' == $this->current_user_role) {
				$hide = false;
			} else {
				$hide = true;
			}
		} elseif (get_option('eh_catalog_pricing_discount_price_catalog_mode') == 'yes') {
			if (get_option('eh_pricing_discount_price_catalog_mode_exclude_admin') == 'yes' && 'administrator' == $this->current_user_role) {
				$hide = false;
			} else {
				$hide = true;
			}
		}

		return $hide;
	}


	public function elex_cm_is_product_purchasable($is_purchasable, $product)
	{
		if ($this->elex_cm_is_hide_price($product) === true || !$is_purchasable) {
			return false;
		} else {
			return true;
		}
	}

	public function elex_cm_is_price_hidden_in_product_meta($product)
	{
		$product_id = $this->elex_cm_get_product_id($product);

		if ($this->elex_cm_get_product_type($product) == 'variation') {
			$product_id = $this->elex_cm_get_product_parent_id($product);
		}
		if (is_user_logged_in()) {
			$remove_product_price_roles = get_post_meta($product_id, 'eh_pricing_adjustment_product_price_user_role', true);
			if (is_array($remove_product_price_roles) && in_array($this->current_user_role, $remove_product_price_roles)) {
				return true;
			} else {
				return false;
			}
		} else {
			$remove_product_price_roles = get_post_meta($product_id, 'product_adjustment_hide_price_unregistered', true);
			if ('yes' == $remove_product_price_roles) {
				return true;
			} else {
				return false;
			}
		}
	}

	public function elex_cm_get_placeholder_text($product, $price)
	{
		$placeholder = '';
		$product_id = $this->elex_cm_get_product_id($product);
		$temp_data = $this->elex_cm_get_product_type($product);
		if ('variation' == $temp_data) {
			$product_id = $this->elex_cm_get_product_parent_id($product);
		}
		if ($this->elex_cm_is_hide_price($product) == true) {
			if ('yes' == get_post_meta($product_id, 'product_adjustment_hide_price_catalog', true)) {
				$placeholder = get_post_meta($product_id, 'product_adjustment_hide_price_placeholder_catalog', true);
			} elseif (get_option('eh_catalog_pricing_discount_price_catalog_mode') == 'yes') {
				$placeholder = get_option('eh_catalog_pricing_discount_price_catalog_mode_text');
			}
			$placeholder = $this->elex_cm_return_wpml_string($placeholder, 'Price placeholder - Global');

			echo wp_kses_post($placeholder);
		} else {
			return $price;
		}
	}
	public function elex_cm_get_placeholder_text_product_hide_price($product)
	{
		$placeholder = '';
		$prod_id = $this->elex_cm_get_product_id($product);
		if (is_user_logged_in()) {
			$placeholder = get_post_meta($prod_id, 'product_adjustment_hide_price_placeholder_role', true);
		} else {
			$placeholder = get_post_meta($prod_id, 'product_adjustment_hide_price_placeholder_unregistered', true);
		}
		$placeholder = $this->elex_cm_return_wpml_string($placeholder, 'Price placeholder - Individual');
		echo wp_kses_post($placeholder);
	}
	public function elex_cm_return_wpml_string($string_to_translate, $name)
	{
		do_action('wpml_register_single_string', 'elex-catmode-rolebased-price', $name, $string_to_translate);
		$ret_string = apply_filters('wpml_translate_single_string', $string_to_translate, 'elex-catmode-rolebased-price', $name);
		return $ret_string;
	}

	public function elex_cm_get_product_type($product)
	{
		if (empty($product)) {
			return 'not a valid object';
		}
		if (WC()->version < '2.7.0') {
			$product_type = $product->product_type;
		} else {
			$product_type = $product->get_type();
		}
		return $product_type;
	}

	public function elex_cm_get_product_id($product)
	{
		if (empty($product)) {
			return 'not a valid object';
		}
		if (WC()->version < '2.7.0') {
			$product_id = $product->post->id;
		} else {
			$product_id = $product->get_id();
		}
		return $product_id;
	}

	public function elex_cm_get_product_parent_id($product)
	{
		if (empty($product)) {
			return 'not a valid object';
		}
		if (WC()->version < '2.7.0') {
			$product_parent_id = $product->parent->id;
		} else {
			$product_parent_id = $product->get_parent_id();
		}
		return $product_parent_id;
	}
}

new Elex_CM_Price_Discount_Admin();
