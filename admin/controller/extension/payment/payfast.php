<?php

/**
 * APPS PayFast Payment Extension for Opencart ver. 3.x
 **/

class ControllerExtensionPaymentPayfast extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('extension/payment/payfast');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_payfast', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['merchant_id'])) {
            $data['error_merchant_id'] = $this->error['merchant_id'];
        } else {
            $data['error_merchant_id'] = '';
        }
        
         if (isset($this->error['security_key'])) {
            $data['error_security_key'] = $this->error['security_key'];
        } else {
            $data['error_security_key'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/payfast', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/payfast', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_payfast_merchant_id'])) {
            $data['payment_payfast_merchant_id'] = $this->request->post['payment_payfast_merchant_id'];
        } else {
            $data['payment_payfast_merchant_id'] = $this->config->get('payment_payfast_merchant_id');
        }

        if (isset($this->request->post['payment_payfast_security_key'])) {
            $data['payment_payfast_security_key'] = $this->request->post['payment_payfast_security_key'];
        } else {
            $data['payment_payfast_security_key'] = $this->config->get('payment_payfast_security_key');
        }
        
         if (isset($this->request->post['payment_payfast_merchant_name'])) {
            $data['payment_payfast_merchant_name'] = $this->request->post['payment_payfast_merchant_name'];
        } else {
            $data['payment_payfast_merchant_name'] = $this->config->get('payment_payfast_merchant_name');
        }


        if (isset($this->request->post['payment_payfast_order_status_id'])) {
            $data['payment_payfast_order_status_id'] = $this->request->post['payment_payfast_order_status_id'];
        } else {
            $data['payment_payfast_order_status_id'] = $this->config->get('payment_payfast_order_status_id');
        }

        if (isset($this->request->post['payment_payfast_pending_status_id'])) {
            $data['payment_payfast_pending_status_id'] = $this->request->post['payment_payfast_pending_status_id'];
        } else {
            $data['payment_payfast_pending_status_id'] = $this->config->get('payment_payfast_pending_status_id');
        }

        if (isset($this->request->post['payment_payfast_canceled_status_id'])) {
            $data['payment_payfast_canceled_status_id'] = $this->request->post['payment_payfast_canceled_status_id'];
        } else {
            $data['payment_payfast_canceled_status_id'] = $this->config->get('payment_payfast_canceled_status_id');
        }

        if (isset($this->request->post['payment_payfast_failed_status_id'])) {
            $data['payment_payfast_failed_status_id'] = $this->request->post['payment_payfast_failed_status_id'];
        } else {
            $data['payment_payfast_failed_status_id'] = $this->config->get('payment_payfast_failed_status_id');
        }

        if (isset($this->request->post['payment_payfast_chargeback_status_id'])) {
            $data['payment_payfast_chargeback_status_id'] = $this->request->post['payment_payfast_chargeback_status_id'];
        } else {
            $data['payment_payfast_chargeback_status_id'] = $this->config->get('payment_payfast_chargeback_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_payfast_geo_zone_id'])) {
            $data['payment_payfast_geo_zone_id'] = $this->request->post['payment_payfast_geo_zone_id'];
        } else {
            $data['payment_payfast_geo_zone_id'] = $this->config->get('payment_payfast_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_payfast_status'])) {
            $data['payment_payfast_status'] = $this->request->post['payment_payfast_status'];
        } else {
            $data['payment_payfast_status'] = $this->config->get('payment_payfast_status');
        }

        if (isset($this->request->post['payment_payfast_sort_order'])) {
            $data['payment_payfast_sort_order'] = $this->request->post['payment_payfast_sort_order'];
        } else {
            $data['payment_payfast_sort_order'] = $this->config->get('payment_payfast_sort_order');
        }

        if (isset($this->request->post['payment_payfast_rid'])) {
            $data['payment_payfast_rid'] = $this->request->post['payment_payfast_rid'];
        } else {
            $data['payment_payfast_rid'] = $this->config->get('payment_payfast_rid');
        }

        if (isset($this->request->post['payment_payfast_custnote'])) {
            $data['payment_payfast_custnote'] = $this->request->post['payment_payfast_custnote'];
        } else {
            $data['payment_payfast_custnote'] = $this->config->get('payment_payfast_custnote');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/payfast', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/payfast')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_payfast_merchant_id']) {
            $this->error['merchant_id'] = $this->language->get('error_merchant_id');
        }
        
        if (!$this->request->post['payment_payfast_security_key']) {
            $this->error['security_key'] = $this->language->get('error_security_key');
        }

        return !$this->error;
    }

}
