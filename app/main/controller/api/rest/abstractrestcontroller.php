<?php
/**
 * Created by PhpStorm.
 * User: exodu
 * Date: 13.10.2018
 * Time: 16:14
 */

namespace Controller\Api\Rest;

use Controller;

abstract class AbstractRestController extends Controller\AccessController {

    /**
     * get send data from request
     * API requests require "Content-Type: application/json"
     * -> $_POST does not include request data -> request BODY might contain JSON
     * @param \Base $f3
     * @return array
     */
    protected function getRequestData(\Base $f3) : array {
        $data = [];
        if( !empty($body = $f3->get('BODY')) ){
            $bodyDecode = json_decode($body, true);
            if(($jsonError = json_last_error()) === JSON_ERROR_NONE){
                $data = $bodyDecode;
            }else{
                $f3->set('HALT', true);
                $f3->error(400, 'Request data: ' . json_last_error_msg());
            }
        }

        return $data;
    }

    /**
     * render API response to client
     * @param $output
     */
    protected function out($output){
        echo json_encode($output);
    }

}