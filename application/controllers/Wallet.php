<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wallet extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->library('guzzle');
    }

	public function index()
	{
        echo 'Welcome to Mini Wallet API';
        die();
	}
}