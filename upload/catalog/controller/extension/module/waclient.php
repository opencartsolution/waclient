<?php
class ControllerExtensionModuleWaclient extends Controller
{
    public function status_change($route, $data)
    {
        $orderStatusId = $data[1];
        $orderId = $data[0];

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($orderId);
        $oldOrderStatusId = $order['order_status_id'];

        if ($oldOrderStatusId != $orderStatusId) {
            # get text for event
            $this->load->model('setting/setting');
            $message = $this->config->get('module_waclient_message_'.$orderStatusId);

            $replace = array(
                '{billing_first_name}'     => $order['payment_firstname'],
                '{billing_last_name}'      => $order['payment_lastname'],
                '{shipping_first_name}'    => $order['shipping_firstname'],
                '{shipping_last_name}'     => $order['shipping_lastname'],
                '{shipping_method}'        => $order['shipping_method'],

                '{payment_method}'         => $order['payment_method'],

                '{status_comment}'         => ((isset($data[2])) ? $data[2] : ""),
                '{store_name}'             => $order['store_name'],
                '{order_number}'           => $order['order_id'],
                '{order_date}'             => $order['date_added'],
                '{order_total}'            => round($order['total'] * $order['currency_value'], 2).' '.$order['currency_code']
            );
            foreach ($replace as $key => $value) {
                $message = str_replace($key, $value, $message);
            }
            
            # send sms
            $this->send_sms($order['telephone'], $message);
        }
    }

    public function send_sms($phone, $message, $type = 'order')
    {
        $this->load->model('setting/setting');
        $username = $this->config->get('module_waclient_username');
        $password = $this->config->get('module_waclient_password');
        $simulation = $this->config->get('module_waclient_simulation');
        if ($simulation) {
            $phone = $this->config->get('module_waclient_simulation_phone');
        }
        $phone = $this->validatePhone($phone);
        $message = $this->prepareMessageText($message);

        if (!empty($phone) && !empty($username) && !empty($password)) {

            $ch = curl_init();

            $timestamp_queued = date("Y-m-d H:i:s");

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $URL = 'https://waclient.com/api/send.php?number='.$phone.'&type=text&message='.urlencode($message).'&instance_id='.$password.'&access_token='.$username.'&version=OC_1.0';


            curl_setopt($ch, CURLOPT_URL, $URL);

            if (strpos($URL, "https://") !== false)
            {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $TimestampSent = date("Y-m-d H:i:s");

            $remote_status = 0;
            $remote_status_description = "Nici o stare";
            $remote_message_id = 0;

            $remote_response = curl_exec($ch);

            $curl_errno = curl_errno($ch);
            $curl_error = curl_error($ch);

            if ($curl_errno == 0)
            {
                $remote_responseparsed = explode(",",$remote_response);

                $remote_responsmsg = str_replace(['"','{','}','message:'], ['','','',''], $remote_response);

                $remote_responsmsg = explode(",",$remote_responsmsg);


                if ($remote_responseparsed[1] == '"message":"Success"') $remote_status = 1;
                    else $remote_status = 2;

            }
            else
            {
                $remote_status = 3;
                $remote_response = "CONNECTION ERROR;".$curl_errno.";".$curl_error;
            }

            switch($remote_status) {
                case 1:
                    $remote_status_description = "Submitted successfully";
                    break;
                case 2:
                    $remote_status_description = "Transmission error";
                    break;
                case 3:
                    $remote_status_description = "Connection error";
                    break;
            }

            $this->load->model('extension/waclient/history');
            $this->model_extension_waclient_history->addHistory(
                $remote_status_description,
                $message,
                $remote_responsmsg[1],
                $phone,
                $timestamp_queued,
                $remote_message_id
            );
        }
    }

    public function validatePhone($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (substr($phone, 0, 1) != '0' && strlen($phone) == 9) {
            $phone = '0'.$phone;
        } elseif (strlen($phone) == 13 && substr($phone, 0, 2) == '00') {
            $phone = substr($phone, 3);
        }

        return $phone;
    }

    public function prepareMessageText($string)
    {
        $search = array(
            "\xC4\x82",
            "\xC4\x83",
            "\xC3\x82",
            "\xC3\xA2",
            "\xC3\x8E",
            "\xC3\xAE",
            "\xC8\x98",
            "\xC8\x99",
            "\xC8\x9A",
            "\xC8\x9B",
            "\xC5\x9E",
            "\xC5\x9F",
            "\xC5\xA2",
            "\xC5\xA3",
            "\xC3\xA3",
            "\xC2\xAD",
            "\xe2\x80\x93");

        $replace = array("A", "a", "A", "a", "I", "i", "S", "s", "T", "t", "S", "s", "T", "t", "a", " ", "-");

        return str_replace($search, $replace, $string);
    }
}
