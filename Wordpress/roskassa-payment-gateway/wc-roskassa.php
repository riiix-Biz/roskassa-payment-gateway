<?php

/*

  Plugin Name:  Payment Gateway eCommerce for store with integration of Roskassa

  Plugin URI: https://riiix.com/

  Description:  Payment Gateway for your eCommerce business

  Tags: eCommerce, WooCommerce, WordPress, Gateways, Payments, Payment, Money, WooCommerce, WordPress, Plugin, Module, Store, Modules, Plugins, Payment system, Website, RosKassa

  Version: 1.0

  Author: RIIIX

  Author URI: https://riiix.com

  Copyright: © 2021 RIIIX.

*/

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
if (!defined('ABSPATH'))

exit;

add_action('plugins_loaded', 'woocommerce_roskassa', 0);

function woocommerce_roskassa()
{

    if (!class_exists('WC_Payment_Gateway'))

    return;

    if (class_exists('WC_RosKassa'))

    return;

    class WC_RosKassa extends WC_Payment_Gateway
    {

        var $outsumcurrency = '';

        var $lang;

        private $rk_eshopId;

        public function __construct()
        {

            $plugin_dir = plugin_dir_url(__FILE__);

            global $woocommerce;

            $this->id = 'roskassa';

            $this->icon = apply_filters('woocommerce_roskassa_icon_' . $this->id, '' . $plugin_dir . 'roskassa.svg');

            $this->has_fields = false;

            $this->init_form_fields();

            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('rk_name');

            $this->rk_eshopId = $this->get_option('rk_eshopId');

            $this->rk_secretKey = $this->get_option('rk_secretKey');

            $this->rk_url = $this->get_option('rk_url');

            // Actions
            add_action('woocommerce_receipt_' . $this->id, array(
                $this,
                'receipt_page'
            ));

            // Save options
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_' . $this->id, array(
                $this,
                'check_ipn_response'
            ));

            if (!$this->is_valid_for_use())
            {

                $this->enabled = false;

            }

        }

        function is_valid_for_use()
        {

            if (!in_array(get_option('woocommerce_currency') , array(
                'RUB',
                'USD'
            )))
            {

                return false;

            }

            return true;

        }

        public function admin_options()
        {

?>

            <h3><?php _e('Платежный модуль RosKassa', 'woocommerce'); ?></h3>

            <p><?php _e('Настройка модуля оплаты RosKassa', 'woocommerce'); ?></p>



            <?php if ($this->is_valid_for_use()): ?>

                <table class="form-table">



                    <?php
                $this->generate_settings_html();

?>

                </table>

                <script>

                    var el = document.getElementById('woocommerce_RosKassa_rk_resultUrl').setAttribute('disabled', 'disabled');

                </script>

                <?php
            else: ?>

                    <div class="inline error"><p><strong><?php _e('Способ оплаты отключен', 'woocommerce'); ?></strong>: <?php _e('Модуль оплаты не поддерживает валюты Вашего магазина.', 'woocommerce'); ?></p></div>

<?php
            endif;

        }

        function init_form_fields()
        {

            $this->form_fields = array(

                'enabled' => array(

                    'title' => __('Включить/Выключить', 'woocommerce') ,

                    'type' => 'checkbox',

                    'label' => __('Включен', 'woocommerce') ,

                    'default' => 'yes'

                ) ,

                'rk_name' => array(

                    'title' => __('Название', 'woocommerce') ,

                    'type' => 'text',

                    'description' => __('Название способа оплаты, которое будет отображаться при выборе', 'woocommerce') ,

                    'default' => 'Оплата через RosKassa',

                    'placeholder' => 'Оплата через RosKassa',

                    'css' => 'width: 500px;'

                ) ,

                'rk_url' => array(

                    'title' => __('URL мерчанта', 'woocommerce') ,

                    'type' => 'text',

                    'description' => __('url для оплаты в системе roskassa', 'woocommerce') ,

                    'default' => '//pay.roskassa.net/'

                ) ,

                'rk_description' => array(

                    'title' => __('Description', 'woocommerce') ,

                    'type' => 'textarea',

                    'description' => __('Описание способа оплаты, которое будет отображаться при выборе', 'woocommerce') ,

                    'default' => 'Оплата с помощью пластиковых карт Visa/Mastercard, СбербанкОнлайн, Яндекс.Деньги, Webmoney, Деньги@Mail.ru, терминалы оплаты, банковский перевод, почта России и т.д.',

                    'placeholder' => 'Оплата с помощью пластиковых карт Visa/Mastercard, СбербанкОнлайн, Яндекс.Деньги, Webmoney, Деньги@Mail.ru, терминалы оплаты, банковский перевод, почта России и т.д.',

                    'css' => 'width: 500px;'

                ) ,

                'rk_eshopId' => array(

                    'title' => __('eshopId (Номер магазина в системе RosKassa)', 'woocommerce') ,

                    'type' => 'text',

                    'description' => __('Обычно используется 510E5A04D8B8C4BF4F84B1AD158YA81J', 'woocommerce') ,

                    'placeholder' => '510E5A04D8B8C4BF4F84B1AD158YA81J',

                ) ,

                'rk_secretKey' => array(

                    'title' => __('Секретный ключ в системе RosKassa', 'woocommerce') ,

                    'type' => 'password',

                    'description' => __('Укажите секретный ключ, такой же, который вы указали в личном кабинете RosKassa', 'woocommerce') ,

                    'placeholder' => '67DSJdkd7q',

                    'css' => 'width: 500px;'

                ) ,

            );

        }

        function payment_fields()
        {

            if ($this->description)
            {

                echo wpautop(wptexturize($this->description));

            }

        }

        public function generate_form($order_id)
        {

            global $woocommerce;

            $order = new WC_Order($order_id);

            $recipientAmount = intval(number_format($order->order_total, 2, '.', ''));

            $currency = $order->get_currency();

            $email = $order->billing_email;

            $fields = array(

                'shop_id' => $this->rk_eshopId,

                'order_id' => $order_id,

                'amount' => $recipientAmount,

                'currency' => $currency

            );

            ksort($fields);

            $control_hash_str = http_build_query($fields);

            $control_hash = md5($control_hash_str . $this->rk_secretKey);

            $postData = array();

            foreach ($fields as $key => $value)
            {

                $postData[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';

            }

            return

            '<form id="rk_pay" action="' . $this->rk_url . '" method="POST">' . "\n" .

            implode("\n", $postData) . "\n" .

            '<input type="hidden" name="sign" value="' . $control_hash . '" />' .

            '<input type="submit" class="button alt" value="Оплатить" />' .

            '</form>';

        }

        function process_payment($order_id)
        {

            $order = new WC_Order($order_id);

            if (!version_compare(WOOCOMMERCE_VERSION, '2.1.0', '<'))

            return array(

                'result' => 'success',

                'redirect' => $order->get_checkout_payment_url(true)

            );

            return array(

                'result' => 'success',

                'redirect' => add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))

            );

        }

        function receipt_page($order)
        {

            echo '<p>' . __('Сейчас Вы будете перемещены на страницу оплаты', 'woocommerce') . '</p>';

            echo $this->generate_form($order);

        }

        function ob_exit($status = null)
        {

            if ($status)
            {

                ob_end_flush();

                exit($status);

            }
            else
            {

                ob_end_clean();

                header("HTTP/1.0 200 OK");

                echo "OK";

                exit();

            }

        }

        function check_ipn_response()
        {

            ob_start();

            $order = new WC_Order($_REQUEST['order_id']);

            // Проверка по контрольной подписи
            if (isset($_REQUEST['roskassa']) && $_REQUEST['roskassa'] == 'result')
            {

                $m_key = $this->rk_secretKey;

                $hash = $_REQUEST['sign'];

                $data = array(

                    'shop_id' => $_REQUEST['shop_id'],

                    'amount' => $_REQUEST['amount'],

                    'currency' => $_REQUEST['currency'],

                    'order_id' => $_REQUEST['order_id'],

                );

                ksort($data);

                $str = http_build_query($data);

                $sign_hash = md5($str . $m_key);

                if (($hash != $sign_hash) || !$hash)
                {

                    $err = "ERROR: HASH MISMATCH\n";

                    $err .= "Control hash: $sign_hash;\nhash: $hash;\n\n";

                    $order->update_status('failed', __('Платеж не оплачен', 'woocommerce'));

                    exit($_REQUEST["order_id"] . '|error');
                }
                else
                {

                    $order->update_status('completed', __('Платеж оплачен', 'woocommerce'));

                    exit('YES');
                }

            }
            else if (isset($_REQUEST['roskassa']) and $_REQUEST['roskassa'] == 'callfalse')
            {

                WC()
                    ->cart
                    ->empty_cart();

                $order = new WC_Order($_REQUEST["order_id"]);

                $order->update_status('failed', __('Платеж не оплачен', 'woocommerce'));

                wp_redirect($this->get_return_url($order));

            }
            else if (isset($_REQUEST['roskassa']) and $_REQUEST['roskassa'] == 'calltrue')
            {

                WC()
                    ->cart
                    ->empty_cart();

                $order = new WC_Order($_REQUEST["order_id"]);

                $order->update_status('processing', __('Платеж оплачен', 'woocommerce'));

                wp_redirect($this->get_return_url($order));

            }

        }

    }

    function add_RosKassa_gateway($methods)
    {

        $methods[] = 'WC_RosKassa';

        return $methods;

    }

    add_filter('woocommerce_payment_gateways', 'add_RosKassa_gateway');

}

?>