<?php
/**
 * Plexis Content Management System
 *
 * @module      Admin
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace Admin;
use System\Core\Controller;
use System\IO\Path;
use System\Web\Gravatar;

/**
 * Plexis Administration Panel
 *
 * @author      Steven Wilson 
 */
final class Admin extends Controller
{
    public function __construct($Module, $Request)
    {
        // Be nice, construct the parent
        parent::__construct($Module, $Request);
		
		// Require user be logged in, and have admin access
        $this->requireAuth(true);
        //$this->requirePermission('adminAccess', SITE_URL);

        // Set admin template path
        $this->loadTheme( Path::Combine(ROOT, "modules", "admin", "theme") );

        // Load gravatar... later we will load the correct avatar
        $Gravatar = new Gravatar();
        $Gravatar->setAvatarSize(60);
        $this->template->set('gravatar_url', $Gravatar->get('wilson.steven10@yahoo.com'));
    }

    public function actionIndex()
    {
        // Set admin panel header and page description
        $this->template->set('page_title', 'Dashboard');
        $this->template->set('page_desc', 'Here you have a quick overview of some features');
        $this->template->breadcrumb->append('Dashboard', '#');

        // Set our page data
        $data = array(
            //'driver' => ucfirst( $info['driver'] ),
            'php_version' => phpversion(),
            'mod_rewrite' => ( MOD_REWRITE ) ? "Enabled" : "Disabled",
            //'database_version' => $info['version'],
            'CMS_VERSION' => CMS_MAJOR_VER .'.'. CMS_MINOR_VER .'.'. CMS_MINOR_REV,
            'CMS_BUILD' => CMS_REVISION,
            //'CMS_DB_VERSION' => REQ_DB_VER
        );

        // Load our dashboard view
        $View = $this->loadView('dashboard');
        $View->set($data);
        $this->template->addView($View);

        // An action must return a WebResponse!
        return $this->response;
    }
}