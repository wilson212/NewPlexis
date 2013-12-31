<?php
namespace Account;
use System\Core\Controller;

final class Account extends Controller
{
    public function actionIndex()
    {
        // Tell the parent controller we require a logged in user
        $this->requireAuth();
        
        // TODO
        $this->template->displayMessage('info', 'Under Construction');
		return $this->response;
    }
}