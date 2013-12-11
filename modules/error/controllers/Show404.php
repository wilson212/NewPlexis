<?php  
/**
 * Core 404 handling Class
 */
namespace Error;
use System\Core\Controller;
use System\Http\WebResponse;
use System\Http\WebRequest;
use System\Web\Template;
 
final class Show404 extends Controller
{
    /**
     * For 404's and 403's, plexis will always call upon the
     * "index" method to handle the request.
     */
    public function actionIndex()
    {
        // Clean all current output
        ob_clean();

        // Get Config
        $Config = \Plexis::Config();
        
        // Get our 404 template contents
        $View = $this->loadView('404');
        $View->set('site_url', WebRequest::BaseUrl());
        $View->set('root_dir', $this->moduleUri);
        $View->set('title', $Config["site_title"]);
        
        // Return response
        $this->response->statusCode(404);
        $this->response->body($View);
        return $this->response;
    }
    
    public function actionAjax()
    {
        // Clean all current output
        ob_clean();
        
        // Reset all headers, and set our status code to 404
        $this->response->statusCode(404);
        $this->response->body( json_encode(array('message' => 'Page Not Found')) );
        return $this->response;
    }
}