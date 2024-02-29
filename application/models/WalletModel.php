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
            'balance',
            'enabled_at',
            'disabled_at'
        ];

        $this->db->select($query)
            ->where('id', $id)
            ->where('owned_by', $cxid);

        return (array)$this->db->get('m_account')->row(0) ?? null;
    }

    public function isReferenceIdExist($referenceId, $transactionType = 'deposit')
    {
        $this->db->select('reference_id')
            ->where('reference_id', $referenceId);
        $data = $this->db->get('mt_' . $transactionType)->result_array();
        return empty($data) ? false : true;
    }

    public function deposit($data, $tokenData)
    {
        $this->db->trans_begin();
        $depositData = $this->assertDeposit($data);
        $trxData     = $this->assertTrx($data, $tokenData, 'deposit');

        if ($this->insertMtDeposit($depositData)) {
            $this->updateBalance($data, $tokenData);
            $this->insertTrx($trxData);
            $this->db->trans_commit();
            return $data['id_deposit'];
        }

        return false;
    }

    public function withdrawal($data, $tokenData)
    {
        $this->db->trans_begin();

        $withdrawalData = $this->assertWithdrawal($data);
        $trxData = $this->assertTrx($data, $tokenData, 'withdrawal');

        if ($this->insertMtWithdrawal($withdrawalData)) {
            $this->updateBalance($data, $tokenData);
            $this->insertTrx($trxData);
            $this->db->trans_commit();
            return $data['id_withdrawal'];
        }

        return false;
    }

    public function getDepositById($data, $depositId)
    {
        $select = [
            'deposit.id',
            'account.owned_by as deposited_by',
            'deposit.status',
            'deposited_at',
            'amount',
            'reference_id'
        ];

        $this->db->select($select)
                ->from('mt_deposit deposit')
                ->join('m_account account','account.id = deposit.deposited_by')
                ->where('deposit.id', $depositId)
                ->where('reference_id', $data['reference_id']);
        
        return $this->db->get()->result_array()[0] ?? [];
    }

    public function getWithdrawalById($data, $withdrawalId)
    {
        $select = [
            'withdrawal.id',
            'account.owned_by as deposited_by',
            'withdrawal.status',
            'withdrawn_at',
            'amount',
            'reference_id'
        ];

        $this->db->select($select)
                ->from('mt_withdraw withdrawal')
                ->join('m_account account','account.id = withdrawal.withdrawn_by')
                ->where('withdrawal.id', $withdrawalId)
                ->where('reference_id', $data['reference_id']);
        
        return $this->db->get()->result_array()[0] ?? [];
    }

    public function getHistory($data)
    {
        $select = [
            'transaction_id',
            'type transaction_type',
            'amount',
            'reference_id',
            'action_by',
            'transaction_date'
        ];
        $this->db->select($select)
            ->from('trx_wallet')
            ->where('action_by', $data['customer_xid'] ?? $data['owned_by'])
            ->order_by('transaction_date', 'DESC');

        return $this->db->get()->result_array();
    }

    private function updateBalance($data, $tokenData)
    {
        $this->db->where('id', $tokenData['id'])
            ->where('owned_by', $tokenData['customer_xid'])
            ->update('m_account', [ 'balance' => $data['balance']]);
    }

    private function insertMtDeposit($data)
    {
        try {
            $this->db->insert('mt_deposit', $data);
            if ($this->db->affected_rows() > 0) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            log_message('error', 'Error inserting data: ' . $e->getMessage());
            return false;
        }
    }

    private function insertMtWithdrawal($data)
    {
        try {
            $this->db->insert('mt_withdraw', $data);
            if ($this->db->affected_rows() > 0) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            log_message('error', 'Error inserting data: ' . $e->getMessage());
            return false;
        }
    }

    private function assertDeposit($data)
    {
        return [
            'id' => $data['id_deposit'],
            'amount' => $data['amount'],
            'reference_id' => $data['reference_id'],
            'status' => true,
            'deposited_by' => $data['id']
        ];
    }

    private function assertWithdrawal($data)
    {
        return [
            'id' => $data['id_withdrawal'],
            'amount' => $data['amount'],
            'reference_id' => $data['reference_id'],
            'status' => true,
            'withdrawn_by' => $data['id']
        ];
    }

    private function assertTrx($data, $tokenData, $type)
    {
        return [
            'transaction_id' => $data['id_trx'],
            'type' => $type,
            'amount' => $data['amount'],
            'action_by' => $tokenData['customer_xid'],
            'reference_id' => $data['reference_id']
        ];
    }

    public function insertTrx($data)
    {
        try {
            $this->db->insert('trx_wallet', $data);
            if ($this->db->affected_rows() > 0) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            log_message('error', 'Error inserting data: ' . $e->getMessage());
            return false;
        }
    }

    public function checkAccount($data)
    {
        $this->db->select('id, owned_by')
            ->where('id', $data['id'])
            ->where('owned_by', $data['customer_xid'] ?? $data['owned_by']);

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

    public function getAccountStatus($data)
    {
        $this->db->select('status')
            ->where('id', $data['id'])
            ->where('owned_by', $data['customer_xid'] ?? $data['owned_by']);

        $data = (array)$this->db->get('m_account')->row();
        return $data['status'] == 1 ? true : false;
    }

    public function assertCreateAccount($uuid, $cxid)
    {
        return [
            'id' => $uuid,
            'owned_by' => $cxid
        ];
    }
}
