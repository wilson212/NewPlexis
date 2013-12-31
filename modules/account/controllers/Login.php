<?php
namespace Account;
use System\Core\Controller;
use System\Security\Session;

final class Login extends Controller
{
    public function postIndex()
    {
        // Get post data
        $username = $this->request->post('username');
		$password = $this->request->post('password');
		
		// Show login screen if we are missing data
        if(empty($username) || empty($password))
        {
            // Open the login screen
            return $this->getIndex();
        }
        
        // Check result
        if(!Session::Login($username, $password))
        {
            $this->template->displayMessage('error', 'failed');
            return $this->getIndex(); // Show login screen yet again! Yay!
        }
        else
        {
            // So the login was successful, now we must figure our redirection url
            $referer = $this->request->referer();
            if(strpos("/account/login", $referer) !== false)
                $this->response->redirect('account');
            else
                $this->response->redirect($referer);
				
			$this->response->send();
			\Plexis::Stop();
        }
    }
	
	public function getIndex()
	{
		$View = $this->template->loadPartial('login');
        $View->set('title', 'Login');
        $this->template->addView($View);
		return $this->response;
	}
}