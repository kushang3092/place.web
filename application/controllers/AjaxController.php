<?php
require(APPLICATION_PATH.'/models/ajaxfileuploader/upload.php');

class AjaxController extends Zend_Controller_Action
{
    //$_SESSION is not available in classactivityAction() ??
    private $session;

    public function init()
    {
        $this->_helper->layout()->disableLayout();
    }
    
    public function resolveAlertAction()
    {
        $params = $this->getRequest()->getParams();
        $activityId = $params['activityId'];
        
        $resolver = new ResolvedUserAlert();
        $resolver->run_id = $_SESSION['run_id'];
        $resolver->author_id = $_SESSION['author_id'];
        $resolver->date_created = date( 'Y-m-d H:i:s');
        $resolver->activity_id = $activityId;
        $resolver->response = 'ok';        
        $resolver->save();
        
        $this->_helper->viewRenderer->setNoRender();
        echo "ok";
    }
    
    public function myupdatesAction()
    {
        $q = new Doctrine_RawSql();
        $q
        	->select('{a.*}')
        	->from('Activity a')
        	->addComponent('a', 'Activity a')
        	->where('a.run_id = ?' , $_SESSION['run_id'])
        	->andWhere('a.activity_on_user = ?', $_SESSION['author_id'])
            ->andWhere('a.author_id != ?', $_SESSION['author_id'])
            ->andWhere('a.id NOT IN (select r.activity_id from resolved_user_alert r where r.author_id = '.$_SESSION['author_id'].')')
        	->orderBy('a.id DESC');

    	$activities = $q->execute();		
        
        $this->view->activities = $activities;
    }

    public function myactivityAction()
    {
        $q = new Doctrine_RawSql();
    	$q	->select('{a.*}')
        	->from('Activity a')
        	->addComponent('a', 'Activity a')
        	->where('a.run_id = ?' , $_SESSION['run_id'])
        	->andWhere('a.author_id = ?', $_SESSION['author_id'])
        	->andWhere('a.id NOT IN (select r.activity_id from resolved_user_alert r where r.author_id = '.$_SESSION['author_id'].')')
        	->orderBy('a.id DESC');
    	
    	$activities = $q->execute();
    	
        $this->view->activities = $activities;
    }

    public function classactivityAction()
    {
        $q = new Doctrine_RawSql();
        $q	->select('{a.*}')
        	->from('Activity a')
        	->addComponent('a', 'Activity a')
        	->where('a.run_id = ?' , $_SESSION['run_id'])
        	->andWhere('a.activity_on_user != ?', $_SESSION['author_id'])
            ->andWhere('a.author_id != ?', $_SESSION['author_id'])
            ->andWhere('a.id NOT IN (select r.activity_id from resolved_user_alert r where r.author_id = '.$_SESSION['author_id'].')')
        	->orderBy('a.id DESC');
    	
        // echo $q->getSqlQuery();die();
    	
    	$activities = $q->execute();
    	
        $this->view->activities = $activities;
    }
    
    public function myhomeworkAction(){
        $answeredQuestions = Doctrine_Query::create()
                    ->select("q.*, a.*")
                    ->from("Question q")
                    ->innerJoin("q.Answer a")
                    ->where("q.run_id = ?", $_SESSION['run_id'])
                    ->andWhere("a.author_id = ?", $_SESSION['author_id'])
                    // ->andWhere("q.is_published = 1")
                    // ->andWhere("q.is_public = 1")
                    ->orderBy("q.date_created desc")
                    ->execute();
    
        $q = new Doctrine_RawSql();
        $q  ->select('{q.*}')
            ->from("question q")
            ->addComponent('q', 'Question q')
            ->where("q.run_id = ?", $_SESSION['run_id'])
            ->andWhere("q.id NOT IN (select a.question_id from answer a where a.author_id = ".$_SESSION['author_id'].")")
            // ->andWhere("q.is_published = 1")
            // ->andWhere("q.is_public = 1")            
            ->orderBy("q.date_created desc");
            
        // echo $q->getSqlQuery();die();
        $unansweredQuestions = $q->execute();
        
        $this->view->answeredQuestions = $answeredQuestions;
        $this->view->unansweredQuestions = $unansweredQuestions;
    }

    public function uploadfileAction()
    {
	global $PLACEWEB_CONFIG;
	$this->_helper->layout->disableLayout();
	$this->_helper->viewRenderer->setNoRender();	

	$upload_handler = new UploadHandler();
	ob_end_clean();


	    ob_start();
 
    	switch ($_SERVER['REQUEST_METHOD']) {
    	    case 'HEAD':
    	    case 'GET':
    		    $upload_handler->get();
    		    break;
    	    case 'POST':
    		    $upload_handler->post();
    		    break;
    	    case 'DELETE':
    		    $upload_handler->delete();
    		    break;
    	    default:
		$this->getResponse()    	
		->setHeader('HTTP/1.0 405 Method Not Allowed')
		->sendResponse();
    		return;	  
    	}
    	

    	$content = ob_get_contents();
    	ob_end_clean();

///*
    	$this->getResponse()    	
	->setHeader('Pragma: no-cache')
    	->setHeader('Cache-Control: private, no-cache')
    	->setHeader('Content-Disposition: inline; filename="files.json"')
    	->setHeader('X-Content-Type-Options: nosniff')
	->setHeader('Content-Type', 'text/plain')
    	    ->appendBody($content)
    	->sendResponse(); 
	
	ob_end_clean();
    		
    	
//*/
    }
}

