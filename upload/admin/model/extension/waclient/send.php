<?php
class ModelExtensionWaclientSend extends Model
{
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
        //$phone='201011511089';
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

            $remote_message_id = 0;

            $remote_response = curl_exec($ch);

            

            $curl_errno = curl_errno($ch);
            $curl_error = curl_error($ch);
            $response = array();
      
            if ($curl_errno == 0){
                $response = json_decode($remote_response,true);
            }else {
                $response['status'] = 'error';
                $response['message'] = "CONNECTION ERROR;".$curl_errno.";".$curl_error;
            }
            if(!isset($response['message'])){
                $response['message'] = '';
            }
            if(!isset($response['status'])){
                $response['status'] = '';
            }

            $this->load->model('extension/waclient/history');
            $this->model_extension_waclient_history->addHistory(
                $response['message'],
                $message,
                $response['status'],
                $phone,
                $timestamp_queued,
                $remote_message_id
            );
            return $response;
        } else {
            return array();
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
