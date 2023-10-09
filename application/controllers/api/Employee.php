<?php

require APPPATH . 'libraries/REST_Controller.php';

defined('BASEPATH') OR exit('No direct script access allowed');

class Employee extends REST_Controller {
    public function __construct()
    {
        parent::__construct();
    }
    
	public function index_get()
	{
        $response = $this->responseGet($this->generateEmployees());
        $this->response($response, $response['status']);
	}

    private function generateEmployees()
    {
        return [
            0 => [
                'Name' => 'Jane',
                'Role' => 'Project Manager',
                'Status' => 'O'
            ],
            1 => [
                'Name' => 'Tess',
                'Role' => 'IT Manager',
                'Status' => 'A'
            ]
        ];
    }

    private function responseGet($data = [])
    {
        return [
            'status' => REST_Controller::HTTP_OK,
            'data' => $data,
            'message' => 'Success get data!'
        ];
    }
}