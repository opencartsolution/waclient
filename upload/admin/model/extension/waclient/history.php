<?php
class ModelExtensionWaclientHistory extends Model
{
    public function createSchema()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."waclient_history` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `message_status` varchar(20) DEFAULT NULL,
              `message_text` varchar(255) DEFAULT NULL,
              `remote_response` longtext,
              `remote_message_id` bigint(20) NOT NULL,
              `timestamp` datetime NOT NULL,
              `phone` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8;
        ");
    }

    public function deleteSchema()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "waclient_history`");
    }

    public function addHistory($status, $message, $response, $phone, $timestamp, $message_id)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "waclient_history SET message_status = '" . $this->db->escape($status) . "', message_text = '" . $this->db->escape($message) . "', remote_response = '" . $this->db->escape($response) . "', remote_message_id = '" . $this->db->escape($message_id) . "', phone = '" . $this->db->escape($phone) . "', timestamp = '" . $this->db->escape($timestamp) . "'");
    }

    public function getTotalHistory($data = array())
    {
        $sql = "SELECT COUNT(DISTINCT id) AS total FROM " . DB_PREFIX . "waclient_history WHERE 1=1";

        if (isset($data['filter_status']) && !empty($data['filter_status'])) {
            $sql .= " AND message_status LIKE '%" . $this->db->escape($data['filter_status']) . "%'";
        }

        if (isset($data['filter_message']) && !empty($data['filter_message'])) {
            $sql .= " AND message_text LIKE '%" . $this->db->escape($data['filter_message']) . "%'";
        }

        if (isset($data['filter_details']) && !empty($data['filter_details'])) {
            $sql .= " AND remote_response LIKE '%" . $this->db->escape($data['filter_response']) . "%'";
        }

        if (isset($data['filter_phone']) && !empty($data['filter_phone'])) {
            $sql .= " AND phone LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }

        if (isset($data['filter_date']) && !empty($data['filter_date'])) {
            $sql .= " AND timestamp LIKE '%" . $this->db->escape($data['filter_date']) . "%'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getHistory($data = array())
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "waclient_history WHERE 1=1";

        if (isset($data['filter_status']) && !empty($data['filter_status'])) {
            $sql .= " AND message_status LIKE '%" . $this->db->escape($data['filter_status']) . "%'";
        }

        if (isset($data['filter_message']) && !empty($data['filter_message'])) {
            $sql .= " AND message_text LIKE '%" . $this->db->escape($data['filter_message']) . "%'";
        }

        if (isset($data['filter_details']) && !empty($data['filter_details'])) {
            $sql .= " AND remote_response LIKE '%" . $this->db->escape($data['filter_response']) . "%'";
        }

        if (isset($data['filter_phone']) && !empty($data['filter_phone'])) {
            $sql .= " AND phone LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }

        if (isset($data['filter_date']) && !empty($data['filter_date'])) {
            $sql .= " AND timestamp LIKE '%" . $this->db->escape($data['filter_date']) . "%'";
        }

        $sort_data = array(
            'message_status',
            'message_text',
            'remote_response',
            'phone',
            'timestamp'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY id";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
}
