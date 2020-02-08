<?php

namespace core\jpd\response;

use core\jpd\Response;

class Json extends Response
{
    public function response($data)
    {
        header('Content-Type: application/json;charset=utf-8');
        ob_clean();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
