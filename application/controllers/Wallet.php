<?php
require(APPPATH . '/libraries/REST_Controller.php');

class Wallet extends REST_Controller
{
    private $statusCode = 400;
    private $payload = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['auth', 'uuidLib']);
        $this->load->model('WalletModel');
        $this->load->database();
    }

    public function index()
    {
        echo 'Welcome to Mini Wallet API';
        echo '<p>' . $this->uuidlib->generateUuid() . '</p>';
    }

    public function createAccount()
    {
        $result = $this->generateResponseBody(0, ['error' => 'insert customer xid!']);
        if ($this->input->post('customer_xid')) {
            $existingData = $this->WalletModel->isCxidExist($this->input->post('customer_xid'));
            
            if (!empty($existingData)) {
                $result = $this->getExistingToken($existingData);
            } else {
                $result = $this->processNewAccount($this->input->post('customer_xid'));
            }
        }

        return $this->response($result, $this->statusCode);
    }

    public function enableWallet()
    {
        $detailToken = $this->getTokenDetail();
        $result = $detailToken['result'];

        if ($detailToken['is_valid_token']) {
            $result = $this->updateStatusWallet($detailToken['data']);
        }

        return $this->response($result, $this->statusCode);
    }

    public function disableWallet()
    {
        $detailToken = $this->getTokenDetail();
        $result = $detailToken['result'];

        if ($detailToken['is_valid_token']) {
            $result = $this->updateStatusWallet($detailToken['data'], false);
        }

        return $this->response($result, $this->statusCode);
    }

    private function updateStatusWallet($data, $isEnabled = true)
    {
        $isFound = $this->WalletModel->checkAccount($data);

        if (!$isFound) {
            $result = $this->generateResponseBody(0, ['error' => 'invalid token!']);
        } else {
            $this->WalletModel->updateWalletStatus($data['id'], $data['customer_xid'], $isEnabled);
            $result = $this->responseStatusWallet($data, $isEnabled);
        }
        return $result;
    }

    public function viewWallet()
    {
        $detailToken = $this->getTokenDetail();
        $result = $detailToken['result'];

        if ($detailToken['is_valid_token']) {
            $result = $this->checkStatus($detailToken['data']);
            $data = $detailToken['data'];
            $isFound = $this->WalletModel->checkAccount($data);

            if ($isFound && $result === true) {
                $result = $this->responseStatusWallet($data);
            } else if ($result === true) {
                $result = $this->generateResponseBody(0, ['error' => 'invalid token!']);
            }
        }

        return $this->response($result, $this->statusCode);
    }

    public function deposit()
    {
        $detailToken = $this->getTokenDetail();
        $result = $detailToken['result'];

        if ($detailToken['is_valid_token']) {
            $result = $this->checkStatus($detailToken['data']);
            $data = $detailToken['data'];
            $isFound = $this->WalletModel->checkAccount($data);

            if ($isFound && $result === true) {
                if ($this->isValidAmount() == true) {
                    $result = $this->processDeposit($data);
                } else {
                    $result = $this->generateResponseBody(0, ['error' => 'invalid amount!']);
                }
            } else if ($result === true) {
                $result = $this->generateResponseBody(0, ['error' => 'invalid token!']);
            }
        }

        return $this->response($result, $this->statusCode);
    }

    public function withdrawal()
    {
        $detailToken = $this->getTokenDetail();
        $result = $detailToken['result'];

        if ($detailToken['is_valid_token']) {
            $result = $this->checkStatus($detailToken['data']);
            $data = $detailToken['data'];
            $isFound = $this->WalletModel->checkAccount($data);

            if ($isFound && $result === true) {
                if ($this->isValidAmount() == true) {
                    $result = $this->processWithdrawal($data);
                } else {
                    $result = $this->generateResponseBody(0, ['error' => 'invalid amount!']);
                }
            } else if ($result === true) {
                $result = $this->generateResponseBody(0, ['error' => 'invalid token!']);
            }
        }

        return $this->response($result, $this->statusCode);
    }

    private function isValidAmount()
    {
        return (int)$this->input->post('amount') < 0 ? false : true;
    }

    public function getHistory()
    {
        $detailToken = $this->getTokenDetail();
        $result = $detailToken['result'];

        if ($detailToken['is_valid_token']) {
            $result = $this->checkStatus($detailToken['data']);
            $data = $detailToken['data'];
            $isFound = $this->WalletModel->checkAccount($data);

            if ($isFound && $result === true) {
                $result = $this->getTransactionHistory($data);
            } else if ($result === true) {
                $result = $this->generateResponseBody(0, ['error' => 'invalid token!']);
            }
        }

        return $this->response($result, $this->statusCode);
    }

    private function getTransactionHistory($data)
    {
        $data = $this->WalletModel->getHistory($data);
        $this->statusCode = 200;
        return $this->generateResponseBody(1, ['transaction' => $data]);
    }

    private function processWithdrawal($data)
    {
        $result = $this->checkPayload();

        if ($result === true) {
            $accountDetail = $this->WalletModel->getAccount($data['id'], $data['customer_xid']);
            $accountDetail = $this->assertTransaction($accountDetail, 'withdrawal');

            if ($this->WalletModel->isReferenceIdExist($accountDetail['reference_id'], 'withdraw') == false) {
                if ($accountDetail['balance'] >= 0) {
                    $withdrawalId = $this->WalletModel->withdrawal($accountDetail, $data);
                    $this->statusCode = 200;
                    return $this->generateResponseBody(1, [
                        'withdrawal' => $this->responseWithdrawal($accountDetail, $withdrawalId)
                    ]);
                }
                return $this->generateResponseBody(0, ['error' => 'insufficient balance!']);
            }
            return $this->generateResponseBody(0, ['error' => 'reference id was ever used!']);
        }

        return $result;
    }

    private function processDeposit($data)
    {
        $result = $this->checkPayload();

        if ($result === true) {
            $accountDetail = $this->WalletModel->getAccount($data['id'], $data['customer_xid']);
            $accountDetail = $this->assertTransaction($accountDetail);

            if ($this->WalletModel->isReferenceIdExist($accountDetail['reference_id'], 'deposit') == false) {
                $depositId = $this->WalletModel->deposit($accountDetail, $data);
                $this->statusCode = 200;
                return $this->generateResponseBody(1, ['deposit' => $this->responseDeposit($accountDetail, $depositId)]);
            }
            return $this->generateResponseBody(0, ['error' => 'reference id was ever used!']);
        }
        return $result;
    }

    private function responseDeposit($data, $depositId)
    {
        $depositData = $this->WalletModel->getDepositById($data, $depositId);
        $depositData['status'] = $depositData['status'] == 1 ? 'success' : 'failed';
        $depositData['deposited_at'] = date(FORMAT_DATE, strtotime($depositData['deposited_at']));
        return $depositData;
    }

    private function responseWithdrawal($data, $withdrawalId)
    {
        $withdrawData = $this->WalletModel->getWithdrawalById($data, $withdrawalId);
        $withdrawData['status'] = $withdrawData['status'] == 1 ? 'success' : 'failed';
        $withdrawData['withdrawn_at'] = date(FORMAT_DATE, strtotime($withdrawData['withdrawn_at']));
        return $withdrawData;
    }

    private function assertTransaction($data, $type = 'deposit')
    {
        $totalBalance = $data['balance'];
        $amount = $this->input->post('amount');
        $id = $this->uuidlib->generateUuid();
        unset($data['balance']);

        $data = array_merge([
            'id_trx' => $this->uuidlib->generateUuid(),
            'amount' => $amount,
            'reference_id' => $this->input->post('reference_id')
        ], $data);

        switch ($type) {
            case 'withdrawal':
                $totalBalance = $totalBalance - $amount;
                $data['id_withdrawal'] = $id;
                break;
            default:
                $totalBalance = $totalBalance + $amount;
                $data['id_deposit'] = $id;
        }
        $data['balance'] = $totalBalance;

        return $data;
    }

    private function checkPayload()
    {
        $logError = [];
        $this->input->post('amount') ?? array_push($logError, 'Insert amount!');
        $this->input->post('reference_id') ?? array_push($logError, 'Insert reference id!');

        if (!empty($logError)) {
            return $this->generateResponseBody(0, ['error' => $logError]);
        }

        return true;
    }

    private function checkStatus($data)
    {
        $isEnabled = $this->WalletModel->getAccountStatus($data);
        if (!$isEnabled) {
            $this->statusCode = 404;
            return $this->generateResponseBody(0, ['error' => 'wallet disabled!']);
        }
        return true;
    }

    private function isValidToken($data)
    {
        if (!empty($data)) {
            if (isset($data['error'])) {
                return false;
            }
            if (isset($data['id']) && isset($data['customer_xid'])) {
                return true;
            }
        }
        return false;
    }

    private function getTokenDetail()
    {
        $data['header_token'] = $this->input->get_request_header('Authorization');
        $data['data'] = $this->auth->getTokenContent($data['header_token']);
        $data['is_valid_token'] = $this->isValidToken($data['data']);
        $data['result'] = $this->generateResponseBody(0, $data['data']);
        return $data;
    }

    private function responseStatusWallet($data, $isEnabled = true)
    {
        $accountDetail = $this->WalletModel->getAccount($data['id'], $data['customer_xid']);

        if (!empty($accountDetail)) {
            $this->statusCode = 200;
            $data = [
                'id' => $accountDetail['id'],
                'owned_by' => $accountDetail['owned_by'],
                'status' => $accountDetail['status'] == 1 ? 'enabled' : 'disabled',
                'enabled_at' => date(FORMAT_DATE, strtotime($accountDetail['enabled_at'])),
                'disabled_at' => date(FORMAT_DATE, strtotime($accountDetail['disabled_at'])),
                'balance' => $accountDetail['balance']
            ];

            switch ($isEnabled) {
                case false:
                    unset($data['enabled_at']);
                    break;
                default:
                    unset($data['disabled_at']);
            }

            $result = $this->generateResponseBody(1, ['wallet' => $data]);
        } else {
            $result = $this->resultAccountNotFound();
        }

        return $result;
    }

    private function resultAccountNotFound()
    {
        return $this->generateResponseBody(0, [
            'error' => 'account not found!'
        ]);
    }

    private function getExistingToken($data)
    {
        $this->statusCode = 200;
        return $this->generateResponseBody(1, ['token' => $this->auth->getToken($data)]);
    }

    private function processNewAccount($cxid)
    {
        $data = $this->WalletModel->assertCreateAccount($this->uuidlib->generateUuid(), $cxid);
        $isInserted = $this->WalletModel->saveAccount($data);

        $token = $this->auth->getToken($data);

        if (!$isInserted) {
            return $this->generateResponseBody(0, ['error' => 'failed to create account!']);
        } else {
            $this->statusCode = 201;
            return $this->generateResponseBody(1, ['token' => $token]);
        }
    }

    private function generateResponseBody($status = 0, $data = [])
    {
        return [
            'data' => $data,
            'status' => $status === 0 ? 'fail' : 'success'
        ];
    }
}
