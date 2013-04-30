<?php  
/**
 * Core 404 handling Class
 */
namespace Error;
use System\Core\Controller;
use System\Http\Response;
use System\Http\Request;
use System\Web\Template;
 
final class ShowOffline extends Controller
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
        $Config = \Plexis::GetConfig();

        // Get our 404 template contents
        $View = $this->loadView('site_offline');
        $View->set('site_url', $this->request->getBaseUrl());
        $View->set('root_dir', $this->moduleUri);
        $View->set('title', $Config["site_title"]);
        $View->set('template_url', Template::GetThemeUrl());

        // Return response
        $this->response->statusCode(503);
        $this->response->body($View);
        return $this->response;
    }
    
    public function actionAjax()
    {
        // Clean all current output
        ob_clean();

        // Reset all headers, and set our status code to 404
        $this->response->statusCode(503);
        $this->response->body( json_encode(array('message' => 'Site Currently Offline')) );
        return $this->response;
    }
}