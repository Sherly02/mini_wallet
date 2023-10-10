<?php
require(APPPATH.'/libraries/REST_Controller.php');

use LDAP\Result;
use Ramsey\Uuid\Uuid;

class Wallet extends REST_Controller {
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
        $headerToken = $this->input->get_request_header('Authorization');
        $data = $this->auth->getTokenContent($headerToken);
        $isValidToken = $this->isValidToken($data);
        $result = $this->generateResponseBody(0, $data);

        if ($isValidToken) {
            $isFound = $this->WalletModel->checkAccount($data);

            if (!$isFound) {
                $result = $this->generateResponseBody(0, ['error' => 'invalid token!']);
            } else {
                $this->WalletModel->updateWalletStatus($data['id'], $data['customer_xid'], 1);
                $result = $this->responseEnabled($data);
            }
        }

        return $this->response($result, $this->statusCode);
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

    private function responseEnabled($data)
    {
        $accountDetail = $this->WalletModel->getAccount($data['id'], $data['customer_xid']);
        
        if (!empty($accountDetail)) {
            $this->statusCode = 200;
            $result = $this->generateResponseBody(1, [
                    'wallet' => [
                    'id' => $accountDetail['id'],
                    'owned_by' => $accountDetail['owned_by'],
                    'status' => $accountDetail['status'] == 1 ? 'enabled' : 'disabled',
                    'enabled_at' => date('Y-m-d\TH:i:sO', strtotime($accountDetail['enabled_at'])),
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