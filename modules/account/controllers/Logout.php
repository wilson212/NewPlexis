<?php
namespace Account;
use System\Core\Controller;
use System\Security\Session;

final class Logout extends Controller
{  
    /**
     * Page used to logout a user
     */
    public function actionIndex()
    {
        // If the user is a guest already, just redirect to index
		if(Session::GetUser()->isGuest())
		{
			$this->response->redirect( SITE_URL );
			return $this->response;
		}
        
        // Tell the auth class to logout
        Session::Logout();
        
        // Show a goodbye screen
        $View = $this->template->loadPartial('contentbox');
        $View->set('title', 'Logout');
        $View->set('contents', $this->loadView('logout'));
        $this->template->addView($View);
		
		return $this->response;
    }
}