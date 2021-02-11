<?php



class WC_Roskassa extends WC_Payment_Gateway {



  /**

   * Current WooCommerce version

   *

   * @var

   */

  

  public $wc_version;

  

  /**

   * Current currency

   *

   * @var string

   */

  

  public $currency;

  

  /**

   * All support currency

   *

   * @var array

   */

  

  public $currency_all = array('RUB', 'USD');

  

  /**

   * @var mixed

   */



  public $enabled;



  /**

   * @var mixed

   */



  public $enable_icon;



  /**

   * @var mixed

   */



  public $title;



  /**

   * @var mixed

   */



  public $button;



  /**

   * @var mixed

   */



  public $description;



  /**

   * @var mixed

   */



  public $merchant_url;



  /**

   * @var mixed

   */



  public $merchant_shop_id;



  /**

   * @var mixed

   */



  public $merchant_secret_key;



  /**

   * @var mixed

   */



  public $debug;

  

  /**

   * Logger

   *

   * @var WC_Gatework_Logger

   */



  public $logger;

  

  /**

   * Logger path

   *

   * array

   * (

   *  'dir' => 'C:\path\to\wordpress\wp-content\uploads\logname.log',

   *  'url' => 'http://example.com/wp-content/uploads/logname.log'

   * )

   *

   * @var array

   */



  public $logger_path;



  /**

   * @var mixed

   */



  public $ip_filter;



  /**

   * @var mixed

   */



  public $email_error;

  

  /**

   * WC_Megakassa constructor

   */



  public function __construct(){



    /**

     * Logger?

     */



    $wp_dir = wp_upload_dir();



    $this->logger_path = array(

      'dir' => $wp_dir['basedir'] . '/wc-roskassa.txt',

      'url' => $wp_dir['baseurl'] . '/wc-roskassa.txt'

    );

    

    $this->logger = new WC_Gatework_Logger($this->logger_path['dir'], $this->get_option('logger'));



    /**

     * Get currency

     */



    $this->currency = gatework_get_wc_currency();

    

    /**

     * Logger debug

     */



    $this->logger->addDebug('Current currency: ' . $this->currency);



    /**

     * Set WooCommerce version

     */



    $this->wc_version = gatework_wc_get_version_active();



    /**

     * Logger debug

     */



    $this->logger->addDebug('WooCommerce version: ' . $this->wc_version);



    /**

     * Set unique id

     */



    $this->id = 'roskassa';



    /**

     * What?

     */



    $this->has_fields = false;

    

    /**

     * Load settings

     */



    $this->init_form_fields();

    $this->init_settings();



    /**

     * Gateway enabled?

     */



    if ($this->get_option('enabled') !== 'yes'){



      $this->enabled = false;

      

      /**

       * Logger notice

       */



      $this->logger->addNotice('Gateway is NOT enabled.');



    }

    

    /**

     * Set icon

     */



    if($this->get_option('enable_icon') === 'yes'){



      $this->icon = apply_filters('woocommerce_roskassa_icon', WC_ROSKASSA_URL . '/assets/img/roskassa_logo_blue.png');



    }

    

    /**

     * Admin title

     */



    $this->method_title = __('wp3-Roskassa', 'wc-roskassa');

    

    /**

     * Admin method description

     */



    $this->method_description = __('Оплата через Roskassa.', 'wc-roskassa');

    

    /**

     * Load other options

     */



    foreach(array('title', 'description', 'button', 'merchant_url', 'merchant_shop_id', 'merchant_secret_key', 'debug', 'ip_filter', 'email_error') as $option_name){



      $this->{$option_name} = $this->get_option($option_name);



    }



    /**

     * Save admin options

     */



    if(current_user_can('manage_options')){



      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

      

      /**

       * Logger notice

       */



      $this->logger->addDebug('Manage options is allow.');



    }

    

    /**

     * Receipt page

     */



    add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

    

    /**

     * Payment listener/API hook

     */



    add_action('woocommerce_api_wc_' . $this->id, array($this, 'check_ipn'));

    

    /**

     * Gate allow?

     */



    if($this->is_valid_for_use()){



      /**

       * Logger notice

       */



      $this->logger->addInfo('Is valid for use.');



    }else{



      $this->enabled = false;

      

      /**

       * Logger notice

       */



      $this->logger->addInfo('Is NOT valid for use.');



    }



  }



  /**

   * Check if this gateway is enabled and available in the user's country

   */



  public function is_valid_for_use(){



    $return = true;

    

    /**

     * Check allow currency

     */



    if(!in_array($this->currency, $this->currency_all, false)){



      $return = false;



      /**

       * Logger notice

       */



      $this->logger->addInfo('Currency not support: ' . $this->currency);



    }



    /**

     * Check test mode and admin rights

     */



    if($this->debug === '1' && !current_user_can('manage_options')){



      $return = false;

      

      /**

       * Logger notice

       */



      $this->logger->addNotice('Test mode only admins.');



    }

    

    return $return;



  }



  /**

   * Initialise Gateway Settings Form Fields

   *

   * @access public

   * @return void

   */



  public function init_form_fields(){



    $this->form_fields = array(  



      'interface' => array(

        'title' => __('Интерфейс', 'wc-roskassa'),

        'type' => 'title',

        'description' => ''

      ),



      'enabled' => array(

        'title' => __('Включить/Выключить', 'wc-roskassa'),

        'type' => 'checkbox',

        'label' => __('Включен', 'wc-roskassa'),

        'default' => 'off'

      ),

      'enable_icon' => array(

        'title' => __('Показывать иконку?', 'wc-roskassa'),

        'type' => 'checkbox',

        'label' => __('Показывать', 'wc-roskassa'),

        'default' => 'yes'

      ),

      'title' => array(

        'title' => __('Название метода оплаты', 'wc-roskassa'),

        'type' => 'text',

        'description' => __('Это название, которое пользователь видит во время выбора способа оплаты.', 'wc-roskassa'),

        'default' => __('Roskassa', 'wc-roskassa')

      ),

      'description' => array(

        'title' => __('Описание метода оплаты', 'wc-roskassa'),

        'type' => 'textarea',

        'description' => __('Описанием метода оплаты которое клиент будет видеть на вашем сайте.', 'wc-roskassa'),

        'default' => 'Оплата с помощью Roskassa'

      ),



      'merchant_url' => array(

        'title' => __('URL мерчанта', 'wc-roskassa'),

        'type' => 'text',

        'description' => __('url для оплаты в системе Roskassa', 'wc-roskassa'),

        'default' => 'https://pay.roskassa.net/'

      ),

      'merchant_shop_id' => array(

        'title' => __('Идентификатор магазина', 'wc-roskassa'),

        'type' => 'text',

        'description' => __('Идентификатор магазина, зарегистрированного в системе "Roskassa".<br/>Узнать его можно в <a href="https://megakassa.ru/panel/shops/">аккаунте Roskassa</a>: "Мои магазины -> Мой магазин ID".', 'wc-roskassa'),

        'default' => ''

      ),

      'merchant_secret_key' => array(

        'title' => __('Секретный ключ', 'wc-roskassa'),

        'type' => 'password',

        'description' => __('Секретный ключ оповещения о выполнении платежа,<br/>который используется для проверки целостности полученной информации<br/>и однозначной идентификации отправителя.<br/>Должен совпадать с секретным ключем, указанным в <a href="https://megakassa.ru/panel/shops/">аккаунте Roskassa</a>: "Мои магазины -> Мой магазин -> Настройки".', 'wc-roskassa'),

        'default' => ''

      ), 



      'additional' => array(

        'title' => __('Дополнительно', 'wc-roskassa'),

        'type' => 'title',

        'description' => ''

      ),



      'logger' => array(

        'title' => __('Включить логирование?', 'wc-roskassa'),

        'type' => 'select',

        'description' => __('Вы можете указать уровень, при котором информация об ошибке будет логироваться. Данные будут сохраняться в файл http://example.com/wp-content/uploads/wc-roskassa.txt', 'wc-roskassa'),

        'default' => '400',

        'options' => array(

          '' => __('Выключено', 'wc-roskassa'),

          '100' => 'Отладка',

          '200' => 'Информация',

          '250' => 'Уведомление',

          '300' => 'Предупреждение',

          '400' => 'Ошибка',

          '500' => 'Критическая ошибка',

          '550' => 'Тревога',

          '600' => 'Чрезвычайная ситуация'

        )

      ),

      'ip_filter' => array(

        'title' => __('IP фильтр', 'wc-roskassa'),

        'type' => 'text',

        'description' => __('Список доверенных ip адресов, можно указать маску', 'wc-roskassa'),

        'default' => ''

      ),

      'email_error' => array(

        'title' => __('E-mail для ошибок', 'wc-roskassa'),

        'type' => 'text',

        'description' => __('Email для отправки ошибок оплаты', 'wc-roskassa'),

        'default' => ''

      )



    );



  }

  

  /**

   * There are no payment fields for sprypay, but we want to show the description if set.

   **/



  public function payment_fields(){



    if($this->description){



      echo wpautop(wptexturize($this->description));



    }



  }



  /**

   * @param $statuses

   * @param $order

   * @return mixed

   */



  public static function valid_order_statuses_for_payment($statuses, $order){



    if($order->payment_method !== 'roskassa'){



      return $statuses;



    }

    

    $option_value = get_option('woocommerce_payment_status_action_pay_button_controller', array());

    

    if(!is_array($option_value)){



      $option_value = array(

        'pending',

        'failed'

      );



    }

    

    if(is_array($option_value) && !in_array('pending', $option_value, false)){



      $pending = array(

        'pending'

      );



      $option_value = array_merge($option_value, $pending);



    }



    return $option_value;



  }



  /**

   * Generate payments form

   *

   * @param $order_id

   *

   * @return string Payment form

   **/



  public function generate_form($order_id){



    $order = wc_get_order($order_id);

    $items = $order->get_items();

    $this->currency = $order->get_currency();



    $amount = number_format($order->get_total(), 2, '.', '');

    $client_email = $order->get_billing_email();

    $currency = $this->currency == 'RUR' ? 'RUB' : $this->currency;

    $description = __('Order number №' . $order_id, 'wc-roskassa');

    $data = array(
      'shop_id' => $this->merchant_shop_id,
      'amount' => $amount,
      'order_id' => $order_id,
      'currency' => $currency
    );

    ksort($data);

    $str = http_build_query($data);
    $signature = md5($str . $this->merchant_secret_key);

    $form = '<form action="' . esc_url($this->merchant_url) . '" method="POST" id="roskassa_payment_form" accept-charset="utf-8">
            <input type="hidden" name="shop_id" value="' . $this->merchant_shop_id . '">';

    $i = 0;
    foreach ( $items as $item ) {

      $form .= '<input type="hidden" name="receipt[items]['.$i.'][name]" value="'.$item['name'].'">';
      $form .= '<input type="hidden" name="receipt[items]['.$i.'][count]" value="'.$item['quantity'].'">';
      $form .= '<input type="hidden" name="receipt[items]['.$i.'][price]" value="'.$item['total'].'">';

    }

    $form .= '<input type="hidden" name="amount" value="' . $amount . '" />
    <input type="hidden" name="currency" value="' . $currency . '" />
    <input type="hidden" name="order_id" value="' . $order_id . '" />
    <input type="hidden" name="sign" value="' . $signature . '" />
    <input type="submit" class="button alt" id="submit_roskassa_payment_form" value="' . __('Оплатить', 'wc-roskassa') . '" />
    <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Вернуться в корзину', 'wc-roskassa') . '</a>
    </form>';

    return $form;

  }

  

  /**

   * Get signature

   *

   * @param $string

   * @param $method

   *

   * @return string

   */

  

  public function get_signature($vars = array(), $method = 'md5'){



    $signature = '';



    switch($method){



      default:        

      $signature = md5($this->merchant_secret_key . md5(join(':', $vars)));

      break;



    }



    return $signature;



  }



  /**

   * Process the payment and return the result

   *

   * @param int $order_id

   *

   * @return array

   */



  public function process_payment($order_id){



    $order = wc_get_order($order_id);



    $order->add_order_note(__('The client started to pay.', 'wc-roskassa'));



    $this->logger->addNotice('The client started to pay.');



    return array(

      'result' => 'success',

      'redirect' => $order->get_checkout_payment_url(true)

    );



  }



  /**

   * receipt_page

   */



  public function receipt_page($order){



    echo '<p>' . __('Thank you for your order, please press the button below to pay.', 'wc-roskassa') . '</p>';



    echo $this->generate_form($order);



  }



  /**

   * Send error to email

   */



  public function send_error($message){



    if(!empty($this->email_error)){



      $message = "Не удалось провести платёж через систему Roskassa по следующим причинам:\n\n" . $message . "\n\n" . var_export($_REQUEST, true);

      $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" . "Content-type: text/plain; charset=utf-8 \r\n";



      mail($this->email_error, 'Roskassa Payment Error', $message, $headers);



    }



  }



  /**

   * Check instant payment notification

   */



  public function check_ipn(){


    $this->logger->addDebug(print_r($_REQUEST, true));


    if($_GET['wc-api'] === 'wc_roskassa'){



      $order_id = $_REQUEST['order_id'];



      $order = wc_get_order($order_id);



      if($order === false){



        $this->logger->addNotice('Order not found.');



        wp_die(__('Order not found.', 'wc-roskassa'), 'Payment error', array(

          'response' => '503'

        ));



      }

      

      if($_GET['roskassa'] === 'calltrue'){



        $order->add_order_note(__('Client return to success page.', 'wc-roskassa'));



        $this->logger->addInfo('Client return to success page.');

        $order->update_status('processing');

        WC()->cart->empty_cart();



        wp_redirect($this->get_return_url($order));



        die();



      }

      

      if($_GET['roskassa'] === 'callfalse'){



        $order->add_order_note(__('The order has not been paid.', 'wc-roskassa'));



        $this->logger->addInfo('The order has not been paid.');



        $order->update_status('failed');



        wp_redirect(str_replace('&amp;', '&', $order->get_cancel_order_url()));



        die();



      }



      foreach(array('shop_id', 'amount', 'currency', 'payment_system', 'date_created', 'date_payed', 'status', 'sign') as $field){



        if(empty($_REQUEST[$field])){



          $this->send_error(__('IPN Request Failure.', 'wc-roskassa'));



          wp_die(__('IPN Request Failure.', 'wc-roskassa'), 'Payment error', array(

            'response' => '503'

          ));



        }



      }



      $uid = (int) $_REQUEST['shop_id'];

      $amount = (double) $_REQUEST['amount'];

      $currency = $_REQUEST['currency'];

      $payment_method_id = (int) $_REQUEST['payment_system'];

      $creation_time = $_REQUEST['date_created'];

      $payment_time = $_REQUEST['date_payed'];

      $status = $_REQUEST['status'];

      $signature = $_REQUEST['sign'];



      if(!in_array($currency, array('RUB', 'USD'), true)){



        $this->send_error(__('Currency Failure.', 'wc-roskassa'));



        wp_die(__('Currency Failure.', 'wc-roskassa'), 'Payment error', array(

          'response' => '503'

        ));



      }


      $data = $_POST;
      unset($data['sign']);
      ksort($data);

      $str = http_build_query($data);
      $local_signature = md5($str . $this->merchant_secret_key);

      $order->add_order_note(sprintf(__('Roskassa request received. Amount: %1$s Signature: %2$s Remote signature: %3$s', 'wc-roskassa'), $amount, $local_signature, $signature));



      if($local_signature !== $signature){



        $order->add_order_note(sprintf(__('Validate hash error. Local: %1$s Remote: %2$s', 'wc-roskassa'), $local_signature, $signature));



        $this->logger->addError('Validate secret key error. Local hash != remote hash.');



        $this->send_error(sprintf(__('Validate hash error. Local: %1$s Remote: %2$s', 'wc-roskassa'), $local_signature, $signature));



        wp_die(__('Signature Failure.', 'wc-roskassa'), 'Payment error', array(

          'response' => '503'

        ));



      }



      $this->logger->addInfo('Roskassa request success.');



      if($_GET['roskassa'] === 'result'){



        $this->logger->addInfo('Result Validated success.');



        if($debug == '1'){



          $order->add_order_note(__('Order successfully paid (TEST MODE).', 'wc-roskassa'));



          $this->logger->addNotice('Order successfully paid (TEST MODE).');



        }else{



          $order->add_order_note(__('Order successfully paid.', 'wc-roskassa'));            



          $this->logger->addNotice('Order successfully paid.');



        }



        $this->logger->addInfo('Payment complete.');



        $order->payment_complete();

        

        die('ok');



      }



    }



    $this->logger->addNotice('Api request error. Action not found.');



    $this->send_error(__('Api request error. Action not found.', 'wc-roskassa'));



    wp_die(__('Api request error. Action not found.', 'wc-roskassa'), 'Payment error', array(

      'response' => '503'

    ));



  }



  /**

   * Check if the gateway is available for use.

   *

   * @since 1.0.0.1

   *

   * @return bool

   */



  public function is_available(){



    return parent::is_available();



  }



} 