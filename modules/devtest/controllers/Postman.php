<?php
/**
 * Plexis Content Management System
 *
 * @module      Devtest
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace Devtest;
use System\Core\Controller;

/**
 * Postman Controller
 *
 * @author      Steven Wilson 
 */
class Postman extends Controller
{
    public function __construct($Module, $Request)
    {
        parent::__construct($Module, $Request);
    }

    public function getIndex()
    {
        $this->response->body("<h1>Hello</h1>");
        return $this->response;
    }

    public function postIndex()
    {
        $this->response->body(
            json_encode(
                array(
                    'Post Data Recieved' => $_POST,
                )
            )
        );
        $this->response->send();
        die;
    }
}