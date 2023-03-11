<?php
class ControllerExtensionModuleWaclientContact extends Controller {
	private $error = array();

	public function index() {

		$this->load->language('extension/module/waclient');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['user_token'] = $this->session->data['user_token'];

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

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_contact'),
			'href' => $this->url->link('extension/module/waclient/contact', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['back'] = $this->url->link('extension/module/waclient', 'user_token=' . $this->session->data['user_token'], true);

		$data['history_link'] = $this->url->link('extension/module/waclient/history', 'user_token=' . $this->session->data['user_token'], true);

		$this->load->model('setting/store');

		$data['stores'] = $this->model_setting_store->getStores();

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/waclient/contact', $data));
	}

	public function send() {
		$this->load->language('extension/module/waclient');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if (!$this->user->hasPermission('modify', 'marketing/contact')) {
				$json['error']['warning'] = $this->language->get('error_permission');
			}

			if(!$this->config->get('module_waclient_status')){
				$json['error']['warning'] = $this->language->get('error_status');
			}

			if (!$this->request->post['message']) {
				$json['error']['message'] = $this->language->get('error_message');
			}

			if (!$json) {
				$this->load->model('setting/store');
				$this->load->model('setting/setting');
				$this->load->model('customer/customer');
				$this->load->model('sale/order');

				$store_info = $this->model_setting_store->getStore($this->request->post['store_id']);

				if ($store_info) {
					$store_name = $store_info['name'];
				} else {
					$store_name = $this->config->get('config_name');
				}

				if (isset($this->request->get['page'])) {
					$page = (int)$this->request->get['page'];
				} else {
					$page = 1;
				}

				$telephone_total = 0;

				$telephones = array();

				switch ($this->request->post['to']) {
					case 'newsletter':
						$customer_data = array(
							'filter_newsletter' => 1,
							'start'             => ($page - 1) * 10,
							'limit'             => 10
						);

						$telephone_total = $this->model_customer_customer->getTotalCustomers($customer_data);

						$results = $this->model_customer_customer->getCustomers($customer_data);

						foreach ($results as $result) {
							$telephones[] = $result['telephone'];
						}
						break;
					case 'customer_all':
						$customer_data = array(
							'start' => ($page - 1) * 10,
							'limit' => 10
						);

						$telephone_total = $this->model_customer_customer->getTotalCustomers($customer_data);

						$results = $this->model_customer_customer->getCustomers($customer_data);

						foreach ($results as $result) {
							$telephones[] = $result['telephone'];
						}
						break;
					case 'customer_group':
						$customer_data = array(
							'filter_customer_group_id' => $this->request->post['customer_group_id'],
							'start'                    => ($page - 1) * 10,
							'limit'                    => 10
						);

						$telephone_total = $this->model_customer_customer->getTotalCustomers($customer_data);

						$results = $this->model_customer_customer->getCustomers($customer_data);

						foreach ($results as $result) {
							$telephones[$result['customer_id']] = $result['telephone'];
						}
						break;
					case 'customer':
						if (!empty($this->request->post['customer'])) {
							$customers = array_slice($this->request->post['customer'], ($page - 1) * 10, 10);

							foreach ($customers as $customer_id) {
								$customer_info = $this->model_customer_customer->getCustomer($customer_id);

								if ($customer_info) {
									$telephones[] = $customer_info['telephone'];
								}
							}

							$telephone_total = count($telephones);
						}
						break;
					case 'affiliate_all':
						$affiliate_data = array(
							'filter_affiliate' => 1,
							'start'            => ($page - 1) * 10,
							'limit'            => 10
						);

						$telephone_total = $this->getTotalAffiliates();
						$results = $this->getAffiliates($affiliate_data);

						foreach ($results as $result) {
							$telephones[] = $result['telephone'];
						}
						break;
					case 'affiliate':
						if (!empty($this->request->post['affiliate'])) {
							$affiliates = array_slice($this->request->post['affiliate'], ($page - 1) * 10, 10);

							foreach ($affiliates as $affiliate_id) {
								$affiliate_info = $this->model_customer_customer->getCustomer($affiliate_id);

								if ($affiliate_info) {
									$telephones[] = $affiliate_info['telephone'];
								}
							}

							$telephone_total = count($this->request->post['affiliate']);
						}
						break;
					case 'product':
						if (isset($this->request->post['product'])) {
							$telephone_total = $this->getTotalTelephonesByProductsOrdered($this->request->post['product']);

							$results = $this->getTelephonesByProductsOrdered($this->request->post['product'], ($page - 1) * 10, 10);

							foreach ($results as $result) {
								$telephones[] = $result['telephone'];
							}
						}
						break;
					case 'number':
						if (isset($this->request->post['number'])) {
							$telephones = explode(',', $this->request->post['number']);
						}
						break;
				}

				if ($telephones) {
					$json['success'] = $this->language->get('text_success_contact');

					$start = ($page - 1) * 10;
					$end = $start + 10;

					if($page == 1 && $telephone_total < 10) {
						$json['success'] = sprintf($this->language->get('text_sent'), $telephone_total, $telephone_total);
					} else if($page == 1 && $telephone_total > 10) {
						$json['success'] = sprintf($this->language->get('text_sent'), 10, $telephone_total);
					} else if($page > 1 && $telephone_total < ($page * 10)) {
						$json['success'] = sprintf($this->language->get('text_sent'), $telephone_total, $telephone_total);
					} else {
						$json['success'] = sprintf($this->language->get('text_sent'), $page * 10, $telephone_total);
					}

					if ($end < $telephone_total) {
						$json['next'] = str_replace('&amp;', '&', $this->url->link('extension/module/waclient/contact/send', 'user_token=' . $this->session->data['user_token'] . '&page=' . ($page + 1), true));
					} else {
						$json['next'] = '';
					}

					if (isset($this->request->post['number']) && $this->request->post['number']) {
						$json['success'] = $this->language->get('text_success_contact');
						$json['next'] = '';
					}

					$message  = $this->request->post['message'];
					$this->load->model('extension/waclient/send');
					$response = '';
					foreach ($telephones as $telephone) {
            			$response = $this->model_extension_waclient_send->send_sms(trim($telephone), $message, 'text');
					}
					// if($response){
					// 	if($response['status'] == 'error'){
					// 		$json['error']['warning'] = $response['message'];
					// 		$json['success'] = '';
					// 	}
					// } else {
					// 	$json['error']['warning'] = 'Error: Please check configuration.';
					// 	$json['success'] = '';
					// }
				} else {
					$json['error']['warning'] = $this->language->get('error_phone');
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getTelephonesByProductsOrdered($products, $start, $end) {
		$implode = array();

		foreach ($products as $product_id) {
			$implode[] = "op.product_id = '" . (int)$product_id . "'";
		}

		$query = $this->db->query("SELECT DISTINCT telephone FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id) WHERE (" . implode(" OR ", $implode) . ") AND o.order_status_id <> '0' LIMIT " . (int)$start . "," . (int)$end);

		return $query->rows;
	}

	public function getTotalTelephonesByProductsOrdered($products) {
		$implode = array();

		foreach ($products as $product_id) {
			$implode[] = "op.product_id = '" . (int)$product_id . "'";
		}

		$query = $this->db->query("SELECT COUNT(DISTINCT telephone) AS total FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id) WHERE (" . implode(" OR ", $implode) . ") AND o.order_status_id <> '0'");

		return $query->row['total'];
	}

	public function getAffiliates($data = array()) {
		$sql = "SELECT DISTINCT *, CONCAT(c.firstname, ' ', c.lastname) AS name FROM " . DB_PREFIX . "customer_affiliate ca LEFT JOIN " . DB_PREFIX . "customer c ON (ca.customer_id = c.customer_id)";
		
		$implode = array();

		if (!empty($data['filter_name'])) {
			$implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}		
		
		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}
		
		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
						
		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalAffiliates($data = array()) {
		$sql = "SELECT DISTINCT COUNT(*) AS total FROM " . DB_PREFIX . "customer_affiliate ca LEFT JOIN " . DB_PREFIX . "customer c ON (ca.customer_id = c.customer_id)";
		
		$implode = array();

		if (!empty($data['filter_name'])) {
			$implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}		
		
		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}
}
