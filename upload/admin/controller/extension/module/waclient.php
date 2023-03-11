<?php
class ControllerExtensionModuleWaclient extends Controller {

    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/waclient');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('design/layout');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_waclient', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/module/waclient', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_username'] = $this->language->get('entry_username');
        $data['entry_password'] = $this->language->get('entry_password');
        $data['entry_label'] = $this->language->get('entry_label');
        $data['entry_simulation'] = $this->language->get('entry_simulation');
        $data['entry_simulation_phone'] = $this->language->get('entry_simulation_phone');
        $data['entry_message'] = $this->language->get('entry_message');
        $data['entry_yes'] = $this->language->get('entry_yes');
        $data['entry_no'] = $this->language->get('entry_no');
        $data['entry_characters_left'] = $this->language->get('entry_characters_left');
        $data['entry_available_vars'] = $this->language->get('entry_available_vars');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        # get list of order statuses
        $this->load->model('localisation/order_status');
        $statuses = new ModelLocalisationOrderStatus($this->registry);
        $statuses = $statuses->getOrderStatuses();
        $data['statuses'] = $statuses;

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/waclient', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/waclient', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        # form
        if (isset($this->request->post['module_waclient_status'])) {
            $data['module_waclient_status'] = $this->request->post['module_waclient_status'];
        } else {
            $data['module_waclient_status'] = $this->config->get('module_waclient_status');
        }
        if (isset($this->request->post['module_waclient_username'])) {
            $data['waclient_username'] = $this->request->post['module_waclient_username'];
        } elseif ($this->config->get('module_waclient_username')) {
            $data['waclient_username'] = $this->config->get('module_waclient_username');
        } else {
            $data['waclient_username'] = '';
        }
        if (isset($this->request->post['module_waclient_password'])) {
            $data['waclient_password'] = $this->request->post['module_waclient_password'];
        } elseif ($this->config->get('module_waclient_password')) {
            $data['waclient_password'] = $this->config->get('module_waclient_password');
        } else {
            $data['waclient_password'] = '';
        }
        if (isset($this->request->post['module_waclient_simulation'])) {
            $data['waclient_simulation'] = $this->request->post['module_waclient_simulation'];
        } elseif ($this->config->get('module_waclient_simulation')) {
            $data['waclient_simulation'] = $this->config->get('module_waclient_simulation');
        } else {
            $data['waclient_simulation'] = '';
        }
        if (isset($this->request->post['module_waclient_simulation_phone'])) {
            $data['waclient_simulation_phone'] = $this->request->post['module_waclient_simulation_phone'];
        } elseif ($this->config->get('module_waclient_simulation_phone')) {
            $data['waclient_simulation_phone'] = $this->config->get('module_waclient_simulation_phone');
        } else {
            $data['waclient_simulation_phone'] = '';
        }
        foreach ($statuses as $status) {
            if (isset($this->request->post['module_waclient_message_'.$status['order_status_id']])) {
                $data['waclient_message_'.$status['order_status_id']] = $this->request->post['module_waclient_message_'.$status['order_status_id']];
            } elseif ($this->config->get('module_waclient_message_'.$status['order_status_id'])) {
                $data['waclient_message_'.$status['order_status_id']] = $this->config->get('module_waclient_message_'.$status['order_status_id']);
            } else {
                $data['waclient_message_'.$status['order_status_id']] = '';
            }
        }

        $data['history_link'] = $this->url->link('extension/module/waclient/history', 'user_token=' . $this->session->data['user_token'], true);
        $data['contact_link'] = $this->url->link('extension/module/waclient/contact', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/waclient', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/waclient')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function install()
    {
        $this->load->model('extension/waclient/history');
        $this->model_extension_waclient_history->createSchema();
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent('waclient', 'catalog/model/checkout/order/addOrderHistory/before', 'extension/module/waclient/status_change');
    }

    public function uninstall()
    {
        $this->load->model('extension/waclient/history');
        $this->model_extension_waclient_history->deleteSchema();
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('waclient');
    }

}
