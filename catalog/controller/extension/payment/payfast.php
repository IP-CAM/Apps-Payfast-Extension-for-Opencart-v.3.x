<?php

class ControllerExtensionPaymentPayfast extends Controller {

    public function index() {
        $this->load->model('checkout/order');

        $this->load->language('extension/payment/payfast');

        $order_id = $this->session->data['order_id'];

        /**
         * get token first
         */
        $merchantid = $this->config->get('payment_payfast_merchant_id');
        $securitykey = $this->config->get('payment_payfast_security_key');
        $authtoken = $this->getPayFastAuthToken($merchantid, $securitykey);

        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['action'] = 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/PostTransaction';

        $data['MERCHANT_ID'] = $this->config->get('payment_payfast_merchant_id');
        $data['MERCHANT_NAME'] = $this->config->get('payment_payfast_merchant_name');
        $data['TOKEN'] = $authtoken;
        $data['BASKET_ID'] = $this->session->data['order_id'];

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $data['CUSTOMER_EMAIL_ADDRESS'] = $order_info['email'];
        $data['CUSTOMER_MOBILE_NUMBER'] = $order_info['telephone'];
        $data['TXNAMT'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

        $signature = md5($merchantid . ":" . $securitykey . ":" . $order_info['total'] . ":" . $this->session->data['order_id']);
        $data['SIGNATURE'] = $signature;
        $data['PROCCODE'] = 00;
		$data['APP_PLUGIN'] = "OPENCART";
        $data['VERSION'] = 'OPENCART-APPS-PAYMENT-0.9';
        $data['TXNDESC'] = "Product Purchased From: " . $this->config->get('config_name');
        $data['ORDER_DATE'] = date('Y-m-d H:i:s', time());
        $data['SUCCESS_URL'] = urlencode($this->url->link('extension/payment/payfast/callback') . "&redirect=Y&signature=" . $signature . "&basket_id=" . $order_id);
        $data['FAILURE_URL'] = urlencode($this->url->link('extension/payment/payfast/callback') . "&redirect=Y&signature=" . $signature . "&basket_id=" . $order_id);
        $data['CHECKOUT_URL'] = urlencode($this->url->link('extension/payment/payfast/callback') . "&signature=" . $signature . "&basket_id=" . $order_id);
        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'), true);
        return $this->load->view('extension/payment/payfast', $data);
    }

    public function failure() {
        $this->load->language('extension/payment/payfast');

        $data = [];
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['txn_details'] = $this->language->get('txn_details');
        $this->response->setOutput($this->load->view('extension/payment/payfast_failure', $data));
    }

    public function callback() {

        $this->log->write('[APPS PayFast: Transaction Response]: ' . json_encode($_REQUEST));
        
        $order_id = null;
        $redirect = $this->request->get['redirect'];

        if (isset($this->request->get['basket_id'])) {
            $order_id = $this->request->get['basket_id'];
        } else {
            $order_id = 0;
        }

        if (!$order_id) {
            
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            exit;
        }

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {

            $order_status_id = $order_info['order_status_id'];

            if ($order_status_id != $this->config->get('config_order_status_id')) {
                if ($redirect == "Y") {
                    
                    $this->response->redirect($this->url->link('checkout/checkout', '', true));
                }
                exit;
            }


            $merchantid = $this->config->get('payment_payfast_merchant_id');
            $securitykey = $this->config->get('payment_payfast_security_key');

            $signature = md5($merchantid . ":" . $securitykey . ":" . $order_info['total'] . ":" . $order_id);

            if ($signature != $this->request->get['signature']) {
                if ($redirect == "Y") {
                    
                    $this->response->redirect($this->url->link('checkout/checkout', '', true));
                }
                exit;
            }

            $apps_status_msg = $this->request->get['err_msg'];
            $apps_transactionid = $this->request->get['transaction_id'];
            $apps_statuscode = $this->request->get['err_code'];
            $apps_rdv_key = $this->request->get['Rdv_Message_Key'];

            $comment_message = "PayFast Transaction ID: " . $apps_transactionid . "<br>";
            $comment_message .= "RDV Message Key: " . $apps_rdv_key . "<br>";
            $comment_message .= "Message: " . $apps_status_msg . "<br>";

            if ($apps_statuscode == "000") {
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_payfast_pending_status_id'), $comment_message, true);
                if ($redirect == "Y") {
                    $this->response->redirect($this->url->link('checkout/success', '', true));
                }
                exit;
            }

            switch ($apps_statuscode) {
                case "529":
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_payfast_canceled_status_id'), $comment_message, true);
                    break;
                default:
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_payfast_failed_status_id'), $comment_message, true);
                    break;
            }

            if ($redirect == "Y") {
                $this->response->redirect($this->url->link('extension/payment/payfast/failure', '', true));
            }
            exit;
        }
    }

    private function getPayFastAuthToken($merchant_id, $security_key) {

        $token_url = "https://ipguat.apps.net.pk/Ecommerce/api/Transaction/GetAccessToken?MERCHANT_ID=" . $merchant_id . "&SECURED_KEY=" . $security_key;
        $response = $this->payfastCurlRequest($token_url);
        $response_decode = json_decode($response);
        if (isset($response_decode->ACCESS_TOKEN)) {
            return $response_decode->ACCESS_TOKEN;
        }

        return null;
    }

    private function payfastCurlRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'application/json; charset=utf-8    '
        ));
		curl_setopt($ch,CURLOPT_USERAGENT,'OpenCart 3 APPS PayFast Plugin');
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

}
