<?php
class WalletModel extends CI_Model
{
    public function saveAccount($data)
    {
        try {
            $this->db->insert('m_account', $data);

            if ($this->db->affected_rows() > 0) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            log_message('error', 'Error inserting data: ' . $e->getMessage());
            return false;
        }
    }

    public function isCxidExist($cxid)
    {
        $this->db->select('id, owned_by customer_xid')
                ->where('owned_by', $cxid);
        
        return $this->db->get('m_account')->result_array(0) ?? null;
    }

    public function getAccount($id, $cxid)
    {
        $query = [
            'id',
            'owned_by',
            'status',
            'enabled_at',
            'disabled_at',
            'balance'
        ];
        
        $this->db->select($query)
                ->where('id', $id)
                ->where('owned_by', $cxid);

        return (array)$this->db->get('m_account')->row(0) ?? null;
    }

    public function checkAccount($data)
    {
        $this->db->select('id, owned_by')
                ->where('id', $data['id'])
                ->where('owned_by', $data['customer_xid']);
                
        return !empty($this->db->get('m_account')->result_array()) ? true : false;
    }

    public function updateWalletStatus($id, $cxid, $isEnable = true)
    {
        $data = [
            'status' => $isEnable,
            'enabled_at' => $isEnable === true ? date('Y-m-d H:i:s') : null,
            'disabled_at' => $isEnable !== true ? date('Y-m-d H:i:s') : null
        ];

        $this->db->where('id', $id)
                ->where('owned_by', $cxid)
                ->update('m_account', $data);

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function assertCreateAccount($uuid, $cxid)
    {
        return [
            'id' => $uuid,
            'owned_by' => $cxid
        ];
    }
}