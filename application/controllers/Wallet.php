<?php
require(APPPATH . '/libraries/REST_Controller.php');

use LDAP\Result;
use Ramsey\Uuid\Uuid;

class Wallet extends REST_Controller
{
    private $statusCode = 400;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('auth');
        $this->load->model('WalletModel');
        $this->load->database();
    }

    public function index()
    {
        echo 'Welcome to Mini Wallet API';
        echo '<p>' . $this->generateUuid() . '</p>';
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

    private function checkStatus($data)
    {
        $isEnabled = $this->WalletModel->getAccountStatus($data);
        if (!$isEnabled) {
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
                'enabled_at' => date('Y-m-d\TH:i:sO', strtotime($accountDetail['enabled_at'])),
                'disabled_at' => date('Y-m-d\TH:i:sO', strtotime($accountDetail['disabled_at'])),
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

    private function responseDisabled($data)
    {
        $accountDetail = $this->WalletModel->getAccount($data['id'], $data['customer_xid']);

        if (!empty($accountDetail)) {
            $this->statusCode = 200;
            $result = $this->generateResponseBody(1, [
                'wallet' => [
                    'id' => $accountDetail['id'],
                    'owned_by' => $accountDetail['owned_by'],
                    'status' => $accountDetail['status'] == 1 ? 'enabled' : 'disabled',
                    'disabled_at' => date('Y-m-d\TH:i:sO', strtotime($accountDetail['disabled_at'])),
                    'balance' => $accountDetail['balance']
                ]
            ]);
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
        $data = $this->WalletModel->assertCreateAccount($this->generateUuid(), $cxid);
        $isInserted = $this->WalletModel->saveAccount($data);

        $token = $this->auth->getToken($data);

        if (!$isInserted) {
            return $this->generateResponseBody(0, ['error' => 'failed to create account!']);
        } else {
            $this->statusCode = 201;
            return $this->generateResponseBody(1, ['token' => $token]);
        }
    }

    private function generateUuid()
    {
        $uuid = Uuid::uuid4();
        return $uuid->toString();
    }

    private function generateResponseBody($status = 0, $data = [])
    {
        return [
            'data' => $data,
            'status' => $status === 0 ? 'fail' : 'success'
        ];
    }
}
