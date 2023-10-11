<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Ramsey\Uuid\Uuid;

class UuidLib {
    public function generateUuid()
    {
        $uuid = Uuid::uuid4();
        return $uuid->toString();
    }
}