<?php
/*
Plugin Name: WooCommerce Moolah Payment Gateway
Plugin URI: https://moolah.io
Description: Moolah payments plugin for WooCommerce
Version: 0.1
*/

add_action('plugins_loaded', 'woocommerce_moolah_init', 0);

define('IMGDIR', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/img/');

function woocommerce_moolah_init(){
    
    if(!class_exists('WC_Payment_Gateway')) return;

    class WC_Moolah extends WC_Payment_Gateway {

        public function __construct() {            
            
            $this -> id = 'moolah';
            $this -> medthod_title = 'Moolah';
            $this -> has_fields = false;

            $this -> init_form_fields();
            $this -> init_settings();

            $this -> merchant_id         = ""; // Merchant GUID;
            $this -> title = $this -> settings['title'];
            $this -> description = $this -> settings['description'];
            $this -> ipn_secret = $this -> settings['ipn_secret'];
            $this -> redirect_page_id = $this -> settings['redirect_page_id'];
            $this -> liveurl = 'https://moolah.io/';            
            $this -> api_url = 'https://moolah.io/api/pay';
            $this -> payment_url = "";
            
            $this -> salt = "";
            $this -> msg['message'] = "";
            $this -> msg['class'] = "";

            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );                
            } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
            }
            add_action('woocommerce_receipt_moolah', array($this, 'receipt_page'));            
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
            add_action('init', array($this, 'callback_handler'));            
        }
    
        function init_form_fields() {
           $this -> form_fields = array (
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'moolah'),
                        'type' => 'checkbox',
                        'label' => __('Enable Moolah Payment Module.', 'moolah'),
                        'default' => 'no'),
                    'title' => array(
                        'title' => __('Title:', 'moolah'),
                        'type'=> 'text',
                        'description' => __('This controls the title which the user sees during checkout.', 'moolah'),
                        'default' => __('Moolah', 'moolah')),
                    'description' => array(
                        'title' => __('Description:', 'moolah'),
                        'type' => 'textarea',
                        'description' => __('This controls the description which the user sees during checkout.', 'moolah'),
                        'default' => __('description here.', 'moolah')),
                    'bitcoin_guid' => array(
                        'title' => __('Bitcoin GUID', 'moolah'),
                        'type' => 'text' ),                         
                    'dogecoin_guid' => array(
                        'title' => __('Dogecoin GUID', 'moolah'),
                        'type' => 'text' ),
                    'litecoin_guid' => array(
                        'title' => __('Litecoin GUID', 'moolah'),
                        'type' => 'text' ),
                    'vertcoin_guid' => array(
                        'title' => __('Vertcoin GUID', 'moolah'),
                        'type' => 'text' ),
                    'auroracoin_guid' => array(
                        'title' => __('Auroracoin GUID', 'moolah'),
                        'type' => 'text' ),                        
                    'darkcoin_guid' => array(
                        'title' => __('Darkcoin GUID', 'moolah'),
                        'type' => 'text' ),
                    'maxcoin_guid' => array(
                        'title' => __('Maxcoin GUID', 'moolah'),
                        'type' => 'text' ),
                    'mintcoin_guid' => array(
                        'title' => __('Mintcoin GUID', 'moolah'),
                        'type' => 'text' ),
                    'ipn_secret' => array(
                        'title' => __('IPN Secret', 'moolah'),
                        'type' => 'text',
                        'description' =>  __('Given to Merchant by Moolah', 'moolah'),
                    ),
                    'redirect_page_id' => array(
                        'title' => __('Return Page'),
                        'type' => 'select',
                        'options' => $this -> get_pages('Select Page'),
                        'description' => "URL of success page"
                    )
           );
        }
        
        public function admin_options() {
            echo '<h3>'.__('Moolah Payment Gateway', 'moolah').'</h3>';
            echo '<p>'.__('description about payment gateway').'</p>';
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this -> generate_settings_html();
            echo '</table>';
        }
        
        /**
         *  There are no payment fields for moolah, but we want to show the description if set.
         **/
        function payment_fields() {
            if($this -> description) echo "<p style='margin-bottom:5px'>$this->description</p>";
            
?>
                <fieldset>
                    <div class="clear"></div>
                    <p class="form-row form-row-first">
                        <label for="moolah-currency"><?php _e("Type of currencies", 'moolah') ?> <span class="required">*</span></label>
                        <select id="moolah-currency" name="moolah-currency" class="woocommerce-select">
                            <option value=""><?php _e('Please choose a currency', 'moolah') ?></option>
                            <?php if ($this -> settings['bitcoin_guid'] != "") echo '<option value="' . $this -> settings['bitcoin_guid'] . '">Bitcoin</option>'; ?>
                            <?php if ($this -> settings['dogecoin_guid'] != "") echo '<option value="' . $this -> settings['dogecoin_guid'] . '">Dogecoin</option>'; ?>
                            <?php if ($this -> settings['litecoin_guid'] != "") echo '<option value="' . $this -> settings['litecoin_guid'] . '">Litecoin</option>'; ?>
                            <?php if ($this -> settings['vertcoin_guid'] != "") echo '<option value="' . $this -> settings['vertcoin_guid'] . '">Vertcoin</option>'; ?>
                            <?php if ($this -> settings['auroracoin_guid'] != "") echo '<option value="' . $this -> settings['auroracoin_guid'] . '">Auroracoin</option>'; ?>
                            <?php if ($this -> settings['darkcoin_guid'] != "") echo '<option value="' . $this -> settings['darkcoin_guid'] . '">Darkcoin</option>'; ?>
                            <?php if ($this -> settings['maxcoin_guid'] != "") echo '<option value="' . $this -> settings['maxcoin_guid'] . '">Maxcoin</option>'; ?>
                            <?php if ($this -> settings['mintcoin_guid'] != "") echo '<option value="' . $this -> settings['mintcoin_guid'] . '">Mintcoin</option>'; ?>
                        </select>
                    </p>
                </fieldset>
<?php            
        }
        
        function payment_scripts() {
            if ( ! is_checkout() )
                return;
                
            if (!is_admin()) {                
                wp_enqueue_script( 'woocommerce_moolah', plugins_url('/moolah.js', __FILE__), array( 'jquery' ),'1.0.0', true);
            }
        }

        /**
         * Receipt Page
         **/
        function receipt_page($order) {
            echo '<p>'.__('Thank you for your order, please click the button below to pay with Moolah.', 'moolah').'</p>';
            
            echo $this -> generate_moolah_form($order);
        }
        function get_response_json($url)
        {            
            $json = file_get_contents($url);
            return $json;
        }
        /**
        * Generate moolah button link
        **/
        function generate_moolah_form($order_id){

            global $woocommerce;
            $order = new WC_Order( $order_id );
            $txnid = $order_id.'_'.date("ymds");
            
            $redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);

            $productinfo = "Order_$order_id";

            $str = "$this->merchant_id|$txnid|$order->order_total|$productinfo|$order->billing_first_name|$order->billing_email|$order_id||||||||||$this->salt";
            $hash = strtolower(hash('sha512', $str));
            $this->merchant_id = $_SESSION["moolah"]["merchant_id"];
            $moolah_args = array(
                'key'               => $this->merchant_id,
                'hash'              => $hash,
                'txnid'             => $txnid,
                'amount'            => $order->order_total,
                'firstname'         => $order->billing_first_name,
                'email'             => $order->billing_email,
                'phone'             => $order->billing_phone,
                'productinfo'       => $productinfo,
                'surl'              => $redirect_url,
                'furl'              => $redirect_url,
                'lastname'          => $order->billing_last_name,
                'address1'          => $order->billing_address_1,
                'address2'          => $order->billing_address_2,
                'city'              => $order->billing_city,
                'state'             => $order->billing_state,
                'country'           => $order->billing_country,
                'zipcode'           => $order->billing_postcode,
                'curl'              => $redirect_url,
                'pg'                => 'NB',
                'udf1'              => $order_id,
                'service_provider'  => 'moolah'
            );
            $moolah_args_array = array();
            foreach($moolah_args as $key => $value){
                $moolah_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
            }
            
            $this -> payment_url = $_SESSION["moolah"]["payment_url"];
            return '    <form action="' . $this -> payment_url .  '" method="post" id="moolah_payment_form">
                  ' . implode('', $moolah_args_array) . '
                <input type="submit" class="button-alt" id="submit_moolah_payment_form" value="'.__('Pay via Moolah', 'kdc').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'kdc').'</a>
                    <script type="text/javascript">
                    jQuery(function(){
                    jQuery("body").block({
                        message: "'.__('Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', 'kdc').'",
                        overlayCSS: {
                            background        : "#fff",
                            opacity           : 0.6
                        },
                        css: {
                            padding           : 20,
                            textAlign         : "center",
                            color             : "#555",
                            border            : "3px solid #aaa",
                            backgroundColor   : "#fff",
                            cursor            : "wait",
                            lineHeight        : "32px"
                        }
                    });
                    
                    jQuery("#submit_moolah_payment_form").click();
                    });
                    </script>
                </form>';
        }
        /**
        * Process the payment and return the result
        **/
        function process_payment($order_id) {

            global $woocommerce;
            $order = new WC_Order( $order_id );            
            
            $this->merchant_id = $_REQUEST["moolah-currency"];
            $redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
            $productinfo = "Order_$order_id";
            
            $request_args = array (
                    'currency=' . 'USD',
                    'guid=' . $this -> merchant_id,
                    'amount=' . $order -> order_total,
                    'product=' . $productinfo,
                    'return=' . $redirect_url,
                    'ipn=' . get_site_url() . '/wc-api/' . get_class( $this ),
                    'extra=' . $order_id
            );
            
            $query_str = implode("&", $request_args);
            if ($this -> ipn_secret != "") $query_str .= "&secret={$this -> ipn_secret}";
            
            $hash = strtolower(hash('sha256', $query_str));
            $query_str .= "&hash={$hash}";
            
            $result = $this->get_response_json($this->api_url . "?" . $query_str);
            
            //echo $this->api_url . "?" . $query_str;
            //die();
                        
            $jsonArray = json_decode($result);
            $this -> payment_url = $jsonArray->url;
            
            $_SESSION["moolah"] = array("order_id"=>$order_id, "merchant_id"=>$this->merchant_id, "payment_url"=>$this->payment_url, "callback_url"=> get_site_url() . '/wc-api/' . get_class( $this ), "tx"=>$jsonArray->tx);
            
            if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) {
                /* 2.1.0 */
                $checkout_payment_url = $order->get_checkout_payment_url( true );
            } else {
                /* 2.0.0 */
                $checkout_payment_url = get_permalink( get_option ( 'woocommerce_pay_page_id' ) );
            }

            return array(
                'result' => 'success', 
                'redirect' => add_query_arg (
                    'order', 
                    $order->id, 
                    add_query_arg(
                        'key', 
                        $order->order_key, 
                        $checkout_payment_url                        
                    )
                )
            );
        }

        function callback_handler() {
            
            global $woocommerce;
            if(isset($_REQUEST['tx']) && isset($_REQUEST['ipn_secret'])) {
                //$order_id = $_SESSION["moolah"]["order_id"];
                $tx = $_SESSION["moolah"]["tx"];
                $order_id = $_REQUEST['extra'];
               
                if($order_id != '' && $_REQUEST['ipn_secret'] == $this -> ipn_secret && $_REQUEST['tx'] != "" )
                {
                    try {
                        $order = new WC_Order($order_id);                        
                        $status = $_REQUEST['status'];
                        $productinfo = "Order_$order_id";

                        $transauthorised = true;
                        if ($status == "complete") {
                            $this -> msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                            $this -> msg['class'] = 'woocommerce_message';
                            $order -> payment_complete();
                            $order -> add_order_note('Moolah payment successful<br/>Unique Id from Moolah: '.$_REQUEST['tx']);
                            $order -> add_order_note($this->msg['message']);
                            $woocommerce -> cart -> empty_cart();
                        } else {
                            $this -> msg['class'] = 'woocommerce_message';
                            $this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been cancelled.";                            
                        }
                    } catch (Exception $e) {
                        $transauthorised = false;
                        $this -> msg['class'] = 'woocommerce_error';
                        $this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                        $order -> add_order_note('Transaction Error: '.$_REQUEST['tx']);
                    }
                    
                    if($transauthorised == false) {
                        $order->update_status('failed');
                    }
                    else {
                        if ($status == "complete") $order->update_status('completed');
                        else $order->update_status('cancelled');
                    }
                    
                } else {
                    $this->msg['class'] = 'error';
                    $this->msg['message'] = "Security Error. Illegal access detected.";
                }
                    
                add_action('the_content', array($this, 'showMessage'));        
                $redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
                header('Location: ' . $redirect_url);
            }
        }
        
        function showMessage($content){
            return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
        }
        
        // get all pages
        function get_pages($title = false, $indent = true) {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title) $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while($has_parent) {
                        $prefix .=  ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
            }
        }
        /**
        * Add the Gateway to WooCommerce
        **/
        function woocommerce_add_moolah_gateway($methods) {
            $methods[] = 'WC_moolah';
            return $methods;
        }

        add_filter('woocommerce_payment_gateways', 'woocommerce_add_moolah_gateway' );
        
        $class = new WC_Moolah();
    }

         