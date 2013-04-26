<?php
/**
 * Plexis Content Management System
 *
 * @module      Welcome
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace Welcome;
use System\Core\Controller;
use System\Http\WebRequest;

/**
 * Welcome Controller
 *
 * @author      Steven Wilson 
 */
class Welcome extends Controller
{
    public function __construct($Module, $Request)
    {
        parent::__construct($Module, $Request);
    }

    public function actionIndex()
    {
        $Request = new WebRequest('error/404');
        return $Request->execute();
    }
}