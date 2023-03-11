<?php
class ModelExtensionWaclientHistory extends Model {
    public function addHistory($status, $message, $response, $phone, $timestamp, $message_id)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "waclient_history SET message_status = '" . $this->db->escape($status) . "', message_text = '" . $this->db->escape($message) . "', remote_response = '" . $this->db->escape($response) . "', remote_message_id = '" . $this->db->escape($message_id) . "', phone = '" . $this->db->escape($phone) . "', timestamp = '" . $this->db->escape($timestamp) . "'");
    }
}
