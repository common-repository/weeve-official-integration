<?php
/**
 * Weeve Official Integration
 *
 * Weeve Official Integration
 *
 * Plugin Name: Weeve Official Integration
 * Plugin URI: https://weeve.tt
 * Version: 1.1
 * Author: Weeve
 * Description: Weeve Official Integration for Merchants
 * Text Domain: weeve
 *
 * @author      Weeve
 * @version     v.1.1 (05/12/21)
 * @copyright   Copyright (c) 2021
 */

$points = 0;

class WPM_Rewards {

    // Variables for use in functions
    public $user_id;
    public $table_name;
    public $user_scores;
    public $points;
    public $user_rank;
    public $settings;
    public $categories;
    public $vouchers;
    public $rewards;

    public $user_name;
    public $phone;

    /**
     * WPM Rewards constructor.
     */
    public function __construct()
    {
        // Create DB Table
        register_activation_hook( __FILE__, [$this, 'sports_bench_create_db']);

		// Create Menu
		add_action('admin_menu', [$this, 'register_menu']);

        // Orders and cart
        add_action('woocommerce_order_status_completed', [$this, 'add_points_to_user'], 10, 1);
        add_action('woocommerce_before_calculate_totals', [$this, 'set_cart_items_price']);
        add_action('woocommerce_checkout_create_order', [$this, 'reset_session'], 20, 1);

        // Show how much Points user will get
        add_action( 'woocommerce_cart_totals_after_order_total', [$this, 'table_points_in_cart_checkout']);
        add_action( 'woocommerce_review_order_after_order_total', [$this, 'table_points_in_cart_checkout']);

        // User profile
        add_action('woocommerce_account_dashboard', [$this, 'rewards_dashboard_api']);

        // Init functions
        add_action('init', [$this, 'save_rewards_data']);
        add_action('init', [$this, 'load_user_points']);
        add_action('init', [$this, 'voucher_submit']);
        add_action('init', [$this, 'rewards_use']);

        // Include Styles and Scripts
        add_action('wp_enqueue_scripts', [$this, 'include_scripts_and_styles'], 99);

        // AJAX Block
        add_action('wp_ajax_wpm_get_user_balance', [$this, 'get_user_api_points']);
        add_action('wp_ajax_nopriv_wpm_get_user_balance', [$this, 'get_user_api_points']);
        add_action('wp_ajax_wpm_get_discount_api', [$this, 'get_discount_api'] );
        add_action('wp_ajax_nopriv_wpm_get_discount_api', [$this, 'get_discount_api']);

        add_action('wp_ajax_wpm_get_sku_api', [$this, 'get_discount_sku_api']);
        add_action('wp_ajax_nopriv_wpm_get_sku_api', [$this, 'get_discount_sku_api']);

        add_action('show_user_profile', [$this, 'show_points_history_profile_user']);
        add_action('edit_user_profile', [$this, 'show_points_history_profile_user']);
        add_action('woocommerce_cart_calculate_fees', [$this, 'add_fees_to_checkout']);
    }

    /**
     * Load Styles and Scripts
     */
    public function include_scripts_and_styles()
    {
        // Register scripts
        wp_register_script('wpm-rewards-script', plugins_url('templates/assets/js/script.js', __FILE__), array('jquery'), '1.0.5', 'all');
        wp_enqueue_script('wpm-rewards-script');
        wp_localize_script('wpm-rewards-script', 'admin',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
            )
        );

        // Register styles
        wp_register_style('wpm-rewards-styles', plugins_url('templates/assets/styles.css', __FILE__), false, '1.0.0', 'all');
        wp_enqueue_style('wpm-rewards-styles');
    }

    /**
     * Add Discount Fee to Cart
     */
    public function add_fees_to_checkout($cart) {
        $is_discounted = WC()->session->get('discount-set');

        if($is_discounted == 1) {
            $discount = WC()->session->get('discount-price');
            $cart->add_fee(sanitize_text_field('Discount Voucher'), -$discount);
        }
    }

    /**
     * Show points history user
     */
    public function show_points_history_profile_user($user)
    {
        global $wpdb;

        // Settings and points
        $table = $wpdb->prefix.'wpm_rewards_points';
        $user_history = $wpdb->get_results("SELECT * FROM $table WHERE user_id = $user->ID AND DATE(date_expire) >= DATE(NOW()) ORDER BY id DESC");

        include('templates/profile_history.php');
    }

    /**
     * Reset session after CheckOut
     */
    public function reset_session()
    {
        WC()->session->set('discount-set', null);
        WC()->session->set('free-product', null);
    }

    /**
     * Get SKU product by API
     */
    public function get_discount_sku_api()
    {
        global $wpdb;

        $errors = [];

        // Prepare Data
        $is_discounted = WC()->session->get('discount-set');
        $phone = sanitize_text_field($_POST['phone']);
        $username = sanitize_text_field($_POST['name']);
        $sku = sanitize_text_field($_POST['sku']);

        // Validate Number
        if(is_numeric($phone)) {
            $this->phone = $phone;
        } else {
            $errors[] = 'Invalid Phone Number';
        }

        // Validate User Name
        if(!ctype_digit($username)) {
            $this->user_name = $username;
        } else {
            $errors[] = 'Invalid First Name';
        }

        if(!$is_discounted && $is_discounted != 1 && empty($errors)) {
            /// Redeem Product on API
            if($sku > 0) {

                // Header data
                $date = date("D j M Y h:i:s").'+5:30';
                $signature = $this->getApiSignature($this->phone, $date);
                $keyid = $this->settings['api_key'];

                // Signature
                $header_signature = 'Signature keyId="'.$keyid.'",algorithm="hmac-sha256",headers="currentdate accept content-type",signature="'.$signature.'"';
                $txn_id = $this->generateRandomString();

                $data_string = [
                    'phone' => $this->phone,
                    'product' => $sku,
                    'requestId' => $txn_id
                ];

                wp_remote_post('https://api.weeve.tt/api/v1/redeemProduct', array(
                        'method' => 'POST',
                        "timeout" => 45,
                        'headers' => [
                            "Content-type" => "application/json",
                            "Accept" => "application/json",
                            "authorization" => $header_signature,
                            "currentdate" => $date
                        ],
                        'body' => json_encode($data_string),
                    )
                );
            }
            $response = [
                'status' => 'completed',
                'message' => 'SKU product is Redeemed'
            ];
        } else {
            $response = [
                'status' => 'failed',
                'errors' => $errors,
                'message' => 'Check the data before take Discount'
            ];
        }

        echo json_encode($response);
    }

    /**
     * Get discount if balance is equal settings
     */
    public function get_discount_api()
    {
        global $wpdb;

        $errors = [];

        // Prepare Data
        $is_discounted = WC()->session->get('discount-set');
        $phone = sanitize_text_field($_POST['phone']);
        $username = sanitize_text_field($_POST['name']);
        $points = sanitize_text_field($_POST['points']);
        $discount = sanitize_text_field($_POST['discount']);
        $sku = sanitize_text_field($_POST['sku']);

        // Validate Number
        if(is_numeric($phone)) {
            $this->phone = $phone;
        } else {
            $errors[] = 'Invalid Phone Number';
        }

        // Validate User Name
        if(!ctype_digit($username)) {
            $this->user_name = $username;
        } else {
            $errors[] = 'Invalid First Name';
        }

        // Validate Points
        if(!is_numeric($points)) {
            $errors[] = 'Points is not Number';
        }

        // Validate Discount
        if(!is_numeric($discount)) {
            $errors[] = 'Discount is not Number';
        }

        // Get User Balance
        $api_data = $this->curlApi();
        $user_balance = sanitize_text_field($api_data['balance']);

        if($user_balance >= $points && !$is_discounted && $is_discounted != 1 && empty($errors)) {
            /// Redeem Product on API
            if($sku > 0) {
                // Check if Gift in the Cart or Add it
                $in_cart = null;
                foreach(WC()->cart->get_cart() as $value) {
                    $sku_product = $value['data']->get_sku();

                    if($sku == $sku_product) {
                        $in_cart = true;
                    }
                }

                if(!$in_cart) {
                    WC()->cart->add_to_cart(wc_get_product_id_by_sku($sku), 1);
                    WC()->session->set('free-product', $sku);
                }
            }

            if($discount > 0 || $sku > 0 && !$in_cart) {

                // Header data
                $date = date("D j M Y h:i:s").'+5:30';
                $signature = $this->getApiSignature($this->phone, $date);
                $keyid = $this->settings['api_key'];

                // Signature
                $header_signature = 'Signature keyId="'.$keyid.'",algorithm="hmac-sha256",headers="currentdate accept content-type",signature="'.$signature.'"';
                $txn_id = $this->generateRandomString();

                // Spend Points on API for Discount or SKU
                $data_string = [
                    "phone" => $this->phone,
                    "name" => $this->user_name,
                    "credits" => -$points,
                    "requestId" => $txn_id,
                    "receiptNumber" => rand(1000, 9999)
                ];

                wp_remote_post('https://api.weeve.tt/api/v1/issuePoints', array(
                        'method' => 'POST',
                        "timeout" => 45,
                        'headers' => [
                            "Content-type" => "application/json",
                            "Accept" => "application/json",
                            "authorization" => $header_signature,
                            "currentdate" => $date
                        ],
                        'body' => json_encode($data_string),
                    )
                );

                // Add points to DB with zero order_id
                $wpdb->insert($this->table_name, array(
                    'user_id' => $this->user_id,
                    'order_id' => -2,
                    'points' => -$points,
                    'date_start' => date('Y-m-d'),
                    'date_expire' => date('Y-m-d', strtotime(date('Y-m-d'). ' + '.$this->settings['expire'].' days')),
                ));
            }

            if($discount > 0) {
                WC()->session->set('discount-set', 1);
                WC()->session->set('discount-price', $discount);
            }
            $response = [
                'status' => 'completed',
                'message' => 'Discount Voucher is Used!'
            ];
        } else {
            $response = [
                'status' => 'failed',
                'errors' => $errors,
                'message' => 'Check the Data before get Discount'
            ];
        }

        echo json_encode($response);
    }

     /**
      * Points table in cart/checkout
      */
    public function table_points_in_cart_checkout()
    {
        // Check Discount Status
        $is_discounted = WC()->session->get('discount-set');

        // Prepare Signature data
        $date = date("D j M Y h:i:s").'+5:30';
        $signature = $this->getApiSignature($this->phone, $date);
        $keyid = $this->settings['api_key'];

        // Signature
        $header_signature = 'Signature keyId="'.$keyid.'",algorithm="hmac-sha256",headers="currentdate accept content-type",signature="'.$signature.'"';

        $response = wp_remote_post('https://api.weeve.tt/api/v1/products', array(
                'method' => 'GET',
                "timeout" => 45,
                'headers' => [
                    "Content-type" => "application/json",
                    "Accept" => "application/json",
                    "authorization" => $header_signature,
                    "currentdate" => $date
                ]
            )
        );

        $res = json_decode($response['body'], true);

        if(isset($res['results']) && !$is_discounted || isset($res['results']) && $is_discounted == 0) {
            foreach($res['results'] as $coupon) { if($this->points >= $coupon['redeemPoints'] && $coupon['status'] == 'active') { ?>
                <tr class="get-discount" style="display: none">
                    <th><?php if(isset($coupon['dollarValue'])) { echo '-'.esc_html($coupon['dollarValue']).get_woocommerce_currency_symbol().' discount'; } ?> <?php if(isset($coupon['SKU']) && isset($coupon['dollarValue'])) {echo '+';} ?> <?php if(isset($coupon['SKU'])) {echo 'GIFT';} ?> <span>(costs <?php echo esc_html($coupon['redeemPoints']) ?> points)</span></th>
                    <td><button class="discount-api" type="button" data-price="<?php if(isset($coupon['dollarValue'])) {echo esc_html($coupon['dollarValue']);} else {echo 0;} ?>" data-sku="<?php if(isset($coupon['SKU'])) {echo esc_html($coupon['SKU']);} else {echo 0;} ?>" data-points="<?php if(isset($coupon['redeemPoints'])) {echo esc_html($coupon['redeemPoints']);} else {echo 0;} ?>"><?php if(isset($coupon['SKU'])) {echo 'Redeem';} else {echo 'Get discount';} ?></button></td>
                </tr>
            <?php }} ?>
            <script>
                jQuery(document).ready(function($) {
                    $('.get-discount').insertBefore('.shop_table .cart-subtotal');
                });
            </script>
            <?php

        } else { ?>
            <script>
                jQuery(document).ready(function($) {
                    var phone = $('#billing_phone').val();
                    var name = $('#billing_first_name').val();

                    $.ajax({
                        type: "POST",
                        url: admin.ajaxurl,
                        data: {
                            action: "wpm_get_user_balance",
                            name: name,
                            phone: phone
                        },
                        success: function(response) {
                            if($(".points-api").length) {
                                $('.points-api').remove();
                            }
                            $('.woocommerce-checkout-review-order-table tfoot').prepend(response);
                            $('.get-discount').show();
                        }
                    });

                });
            </script>
        <?php }
    }

    /**
     * Convert data to Base64
     */
    public static function hex_to_base64($hex){
        $return = '';

        foreach(str_split($hex, 2) as $pair){
            $return .= chr(hexdec($pair));
        }

        return base64_encode($return);
    }

    /**
     * Get signature for API request
     */
    public function getApiSignature($phone, $date)
    {
        $signature = $this->hex_to_base64('no_api_key');

		if(isset($this->settings['api_secret'])) {
            $salt = "currentdate: ".$date."\naccept: application/json\ncontent-type: application/json";
            $hmac256   = hash_hmac('sha256',$salt, $this->settings['api_secret']);
            $signature = $this->hex_to_base64($hmac256);
        }

        return $signature;
    }

    /**
     * Rewards dashboard in user account api version
     */
    public function rewards_dashboard_api()
    {
        global $wpdb;

        // Get settings and discounts
        $settings = $this->settings;
        $user_rank = $this->user_rank;
        $points = $this->points;
        $rewards = $this->rewards;
        $user_id = get_current_user_id();

        // Create variables
        $currency = get_woocommerce_currency_symbol();
        $price_points = 100;
        $discount = [];
        $min_reward = 0;

        // Check Used Vouchers
        $used_vouchers = [];
        foreach ($rewards['code'] as $item => $voucher) {
            if(get_user_meta($this->user_id, 'used_voucher_'.$voucher, true)) {
                $used_vouchers[$item] = 1;
            } else {
                $used_vouchers[$item] = 0;
            }
        }

        $get_points = $settings['percent'];

		include 'templates/user_rewards_dashboard.php';

        // Settings and points
        $table = $wpdb->prefix.'wpm_rewards_points';
        $user_history = $wpdb->get_results("SELECT * FROM $table WHERE user_id = $user_id AND DATE(date_expire) >= DATE(NOW()) ORDER BY id DESC");

        include('templates/profile_history.php');
    }

	/**
	* Ajax GET balance points from API
	*/
	public function get_user_api_points()
	{
        $errors = [];

	    // Prepare Data
        $phone = sanitize_text_field($_POST['phone']);
        $username = sanitize_text_field($_POST['name']);

        // Validate Number
        if(is_numeric($phone)) {
            $this->phone = $phone;
        } else {
            $errors[] = 'Invalid Phone Number';
        }

        // Validate User Name
        if(!ctype_digit($username)) {
            $this->user_name = $username;
        } else {
            $errors[] = 'Invalid First Name';
        }

        if(empty($errors)) {
            $api_data = $this->curlApi();
            include('templates/ajax_get_points.php');
        } else {
            echo 'Please, fix Errors: '.implode(', ', $errors);
        }

		die;
	}

    /**
     * Check voucher is exist and not used to get points
     */
    public function voucher_submit()
    {
        if(isset($_POST) && isset($_POST['voucher'])) {
            global $wpdb;

            $vouchers = $this->vouchers;

            // Data which need before create points in DB
            $user_voucher = sanitize_text_field($_POST['voucher']);
            $result_message = "Voucher not found";

            if(isset($vouchers) && count($vouchers['code']) > 0) {
                foreach ($vouchers['code'] as $item => $voucher) {
                    if($voucher == $user_voucher && get_user_meta($this->user_id, 'used_voucher_'.$voucher, true) != 1 &&
                        strtotime($vouchers['date_expire'][$item]) > strtotime('now') && $vouchers['usings'][$item] > 0 && $vouchers['status'][$item] == 1) {

                        // Add points to DB with zero order_id
                        $wpdb->insert($this->table_name, array(
                            'user_id' => $this->user_id,
                            'order_id' => 0,
                            'points' => $vouchers['points'][$item],
                            'date_start' => date('Y-m-d'),
                            'date_expire' => date('Y-m-d', strtotime(date('Y-m-d'). ' + '.$this->settings['expire'].' days')),
                        ));

                        // Save updated vouchers data
                        $vouchers['usings'][$item] = $vouchers['usings'][$item] - 1;
                        update_option('wpm_points_vouchers', json_encode($vouchers));

                        // Save log about using voucher
                        update_user_meta($this->user_id, 'used_voucher_'.$voucher, 1);

                        // Prepare Signature data
                        $date = date("D j M Y h:i:s").'+5:30';
                        $signature = $this->getApiSignature($this->phone, $date);
                        $keyid = $this->settings['api_key'];

                        // Signature
                        $header_signature = 'Signature keyId="'.$keyid.'",algorithm="hmac-sha256",headers="currentdate accept content-type",signature="'.$signature.'"';
                        $txn_id = $this->generateRandomString();

                        // POST Body
                        $data_string = [
                            "phone" => $this->phone,
                            "name" => $this->user_name,
                            "credits" => intval($vouchers['points'][$item]),
                            "requestId" => $txn_id,
                            "receiptNumber" => rand(1000, 9999)
                        ];

                        wp_remote_post('https://api.weeve.tt/api/v1/issuePoints', array(
                                'method' => 'POST',
                                "timeout" => 45,
                                'headers' => [
                                    "Content-type" => "application/json",
                                    "Accept" => "application/json",
                                    "authorization" => $header_signature,
                                    "currentdate" => $date
                                ],
                                'body' => json_encode($data_string),
                            )
                        );

                        $result_message = 'Voucher is activated!';
                    } elseif($voucher == $user_voucher && get_user_meta($this->user_id, 'used_voucher_'.$voucher, true) == 1 &&
                        strtotime($vouchers['date_expire'][$item]) > strtotime('now') && $vouchers['usings'][$item] > 0 && $vouchers['status'][$item] == 1) {
                        $result_message = 'Voucher is already used';
                    }
                }
            }

            wp_redirect(get_permalink(wc_get_page_id('myaccount')).'?response='.$result_message);

            exit;
        }
    }

    /**
     * Check voucher rewards if its already used and add points to API
     */
    public function rewards_use()
    {
        if(isset($_GET) && isset($_GET['get-rewards'])) {
            global $wpdb;

            // Get Settings
            $vouchers = $this->rewards;
            $points = $this->points;

            // Data which need before create points in DB
            $user_voucher = sanitize_text_field($_GET['get-rewards']);
            $result_message = "Voucher not found";

            if(isset($vouchers) && count($vouchers['code']) > 0) {
                foreach ($vouchers['code'] as $item => $voucher) {
                    if($voucher == $user_voucher && get_user_meta($this->user_id, 'used_voucher_'.$voucher, true) != 1 && $vouchers['status'][$item] == 1 && $vouchers['need'][$item] <= $points) {

                        // Add points to DB with zero order_id
                        $wpdb->insert($this->table_name, array(
                            'user_id' => $this->user_id,
                            'order_id' => 0,
                            'points' => $vouchers['points'][$item],
                            'date_start' => date('Y-m-d'),
                            'date_expire' => date('Y-m-d', strtotime(date('Y-m-d'). ' + '.$this->settings['expire'].' days')),
                        ));

                        // Save log about using voucher
                        update_user_meta($this->user_id, 'used_voucher_'.$voucher, 1);

                        // Prepare Signature data
                        $date = date("D j M Y h:i:s").'+5:30';
                        $signature = $this->getApiSignature($this->phone, $date);
                        $keyid = $this->settings['api_key'];

                        // Signature
                        $header_signature = 'Signature keyId="'.$keyid.'",algorithm="hmac-sha256",headers="currentdate accept content-type",signature="'.$signature.'"';
                        $txn_id = $this->generateRandomString();

                        // POST Body
                        $data_string = [
                            "phone" => $this->phone,
                            "name" => $this->user_name,
                            "credits" => intval($vouchers['points'][$item]),
                            "requestId" => $txn_id,
                            "receiptNumber" => rand(1000, 9999)
                        ];

                        wp_remote_post('https://api.weeve.tt/api/v1/issuePoints', array(
                                'method' => 'POST',
                                "timeout" => 45,
                                'headers' => [
                                    "Content-type" => "application/json",
                                    "Accept" => "application/json",
                                    "authorization" => $header_signature,
                                    "currentdate" => $date
                                ],
                                'body' => json_encode($data_string),
                            )
                        );

                        $result_message = 'Voucher is activated!';
                    } elseif($voucher == $user_voucher && get_user_meta($this->user_id, 'used_voucher_'.$voucher, true) == 1 && $vouchers['status'][$item] == 1) {
                        $result_message = 'Voucher is already used';
                    } elseif($voucher == $user_voucher && $vouchers['need'][$item] > $points && $vouchers['status'][$item] == 1
                        && get_user_meta($this->user_id, 'used_voucher_'.$voucher, true) != 1) {
                        $result_message = 'Not enough points to take reward';
                    }
                }
            }

            wp_redirect(get_permalink(wc_get_page_id('myaccount')).'?response='.$result_message);

            exit;
        }
    }

    /**
     * Load all settings and scores if user logged
     */
    public function load_user_points()
    {
        if(is_user_logged_in()) {
            global $wpdb;

            $this->user_id = get_current_user_id();
            $this->phone = get_user_meta($this->user_id, 'billing_phone', true);
            $this->user_name = get_user_meta($this->user_id, 'billing_first_name', true);
            $this->table_name = $wpdb->prefix.'wpm_rewards_points';

            // Get settings WPM Rewards
            $this->settings = json_decode(get_option('wpm_points_settings'), true);
            $this->vouchers = json_decode(get_option('wpm_points_vouchers'), true);
            $this->rewards = json_decode(get_option('rewards_vouchers'), true);

            // User Rewards data
            $this->points = 0;
            $this->user_rank = [];

            // Get Scores
            if($this->phone && $this->user_name) {
                $api_data = $this->curlApi();
                $this->points = $api_data['balance'];
            }

            // Find user rank by scores
            foreach($this->settings['rank'] as $item => $rank) {
                if($this->settings['points'][$item] <= $this->points) {
                    $this->user_rank = [
                        'rank' => $rank,
                        'rank_id' => $item
                    ];
                }
            }
        }
    }

    /**
     * Change price on products in cart by rank user
     */
    public function set_cart_items_price($cart_object)
    {
        $free_product = WC()->session->get('free-product');

        if($free_product != 0) {
            foreach ($cart_object->get_cart() as $hash => $value) {
                $sku = $value['data']->get_sku();

                if($sku == $free_product) {
                    $value['data']->set_price(0);
                }
            }
        }
    }

    /**
     * Rewards dashboard in user account
     */
    public function add_points_to_user($order_id)
    {
        global $wpdb;

        // Get order details
        $order = new WC_Order($order_id);
        $total = 0;

        // Get Settings
        $settings = $this->settings;

        // User Rewards data
        $user_id = $order->get_user_id();
        $this->phone = get_user_meta($user_id, 'billing_phone', true);
        $this->user_name = get_user_meta($user_id, 'billing_first_name', true);

        // Get Scores
        if($this->phone && $this->user_name) {
            $api_data = $this->curlApi();
            $this->points = $api_data['balance'];
        }

        foreach ($order->get_items() as $item_id => $item) {
            $price = $item->get_subtotal();
            $discount = $settings['percent'];

            // Calculate result price
            if($discount > 0) {
                $price = $price - ($price - ($price * ($discount / 100)));
            }

            $total += round($price, 2);
        }

        // Insert points to DB
        $table_name = $wpdb->prefix.'wpm_rewards_points';
        $wpdb->insert($table_name, array(
            'user_id' => $user_id,
            'order_id' => $order_id,
            'points' => $total,
            'date_start' => date('Y-m-d'),
            'date_expire' => date('Y-m-d', strtotime(date('Y-m-d'). ' + '.$this->settings['expire'].' days')),
        ));

        // Header data
        $date = date("D j M Y h:i:s").'+5:30';
        $signature = $this->getApiSignature($this->phone, $date);
        $keyid = $this->settings['api_key'];

        // Signature
        $header_signature = 'Signature keyId="'.$keyid.'",algorithm="hmac-sha256",headers="currentdate accept content-type",signature="'.$signature.'"';
        $txn_id = $this->generateRandomString();

        // POST Body
        $data_string = [
            "phone" => $order->get_billing_phone(),
            "name" => $order->get_billing_first_name(),
            "credits" => $total,
            "requestId" => $txn_id,
            "receiptNumber" => $order_id
        ];

        wp_remote_post('https://api.weeve.tt/api/v1/issuePoints', array(
                'method' => 'POST',
                "timeout" => 45,
                'headers' => [
                    "Content-type" => "application/json",
                    "Accept" => "application/json",
                    "authorization" => $header_signature,
                    "currentdate" => $date
                ],
                'body' => json_encode($data_string),
            )
        );
    }

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    /**
     * Create new menu and page in navigation
     */
    public function register_menu()
    {
        add_menu_page('Weeve', 'Weeve', 'edit_others_posts', 'wpm_points');
        add_submenu_page('wpm_points', 'Settings Rewards', 'Settings', 'manage_options', 'wpm_points', function () {
            $settings = $this->settings;

            include 'templates/settings_page.php';
        });
        add_submenu_page('wpm_points', 'Vouchers Points', 'Vouchers', 'manage_options', 'wpm_points_vouchers', function () {
            $vouchers_ranks = $this->rewards;

            include 'templates/vouchers_page.php';
        });
    }

    /**
     * Save data from WPM Rewards pages to DB Options
     */
    public function save_rewards_data()
    {
        if(isset($_POST['rewards_vouchers']) && is_array($_POST['rewards_vouchers'])) {
            $data = [
                'code' => array_map('sanitize_text_field', $_POST['rewards_vouchers']['code']),
                'name' => array_map('sanitize_text_field', $_POST['rewards_vouchers']['name']),
                'need' => array_map('sanitize_text_field', $_POST['rewards_vouchers']['need']),
                'points' => array_map('sanitize_text_field', $_POST['rewards_vouchers']['points']),
            ];

            update_option('rewards_vouchers', json_encode($data));
        }

        if(isset($_POST['wpm_points_settings']) && is_array($_POST['wpm_points_settings'])) {
            $data = [
                'rank' => array_map('sanitize_text_field', $_POST['wpm_points_settings']['rank']),
                'points' => array_map('sanitize_text_field', $_POST['wpm_points_settings']['points']),
                'percent' => sanitize_text_field($_POST['wpm_points_settings']['percent']),
                'price' => sanitize_text_field($_POST['wpm_points_settings']['price']),
                'expire' => sanitize_text_field($_POST['wpm_points_settings']['expire']),
                'api_key' => sanitize_text_field($_POST['wpm_points_settings']['api_key']),
                'api_secret' => sanitize_text_field($_POST['wpm_points_settings']['api_secret'])
            ];

            update_option('wpm_points_settings', json_encode($data));
        }
    }

    /**
     * Connection to API for Register user or get Points
     */
    public function curlApi()
    {
        if(isset($this->settings['api_key']) && isset($this->settings['api_secret'])) {
            return false;
        }

        // Header data
        $date = date("D j M Y h:i:s").'+5:30';
        $signature = $this->getApiSignature($this->phone, $date);

        $keyid = '';
        if(isset($this->settings['api_key'])) {
            $keyid = $this->settings['api_key'];
        }

        // Signature
        $header_signature = 'Signature keyId="'.$keyid.'",algorithm="hmac-sha256",headers="currentdate accept content-type",signature="'.$signature.'"';

        // POST Body
        $data_string = [
            "phone" => $this->phone,
            "name" => $this->user_name
        ];

        // Try to add new user
        $response = wp_remote_post('https://api.weeve.tt/api/v1/users/', array(
                'method' => 'POST',
                "timeout" => 45,
                'headers' => [
                    "Content-type" => "application/json",
                    "Accept" => "application/json",
                    "Authorization" => $header_signature,
                    "currentdate" => $date
                ],
                'body' => json_encode($data_string),
            )
        );

        if(is_wp_error($response)) {
            return false;
        }

        $res = json_decode($response['body'], true);

        // Check if user exist - get balance
        if(isset($res['success']) && $res['success'] == "") {
            $response = wp_remote_post("https://api.weeve.tt/api/v1/users/?phone=".$this->phone."", array(
                    'method' => 'GET',
                    "timeout" => 45,
                    'headers' => [
                        "Content-type" => "application/json",
                        "Accept" => "application/json",
                        "authorization" => $header_signature,
                        "currentdate" => $date
                    ]
                )
            );

            if(is_wp_error($response)) {
                return false;
            }

            $res = json_decode($response['body'], true);
        }

        return $res;
    }

    /**
     * Create DB Table with Points and Vouchers
     */
    public function sports_bench_create_db()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH.'wp-admin/includes/upgrade.php');

        // Save default settings to options
        $data = [
            'rank' => ['Beginner', 'Advanced', 'Legend'],
            'points' => [0, 100, 300],
            'percent' => 0,
            'expire' => 365
        ];

        update_option('wpm_points_settings', json_encode($data));

        // Create table Points
        $table_name = $wpdb->prefix.'wpm_rewards_points';
        $sql = "CREATE TABLE $table_name (
         id INTEGER NOT NULL AUTO_INCREMENT,
         user_id INTEGER(10) NOT NULL,
         order_id INTEGER(10) NOT NULL,
         points INTEGER(10) NOT NULL,
         date_start DATE NOT NULL,
         date_expire DATE NOT NULL,
         PRIMARY KEY (id)
        ) $charset_collate;";
            dbDelta($sql);
    }

}

new WPM_Rewards();