<?php
App::uses('AppController', 'Controller');

class UsersController extends AppController {

	public $components = array(
		'RequestHandler',
        'Session',
        'Auth' => array(
            'loginRedirect' => array(
                'controller' => 'orders',
                'action' => 'viewByRestaurant'
            ),
            'logoutRedirect' => array(
                'controller' => 'Users',
                'action' => 'login'
            ),
            'authenticate' => array(
                'Form' => array(
                	'userModel' => 'User',
                    'passwordHasher' => 'Simple'
                )
            )
        )
    );

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('addARestaurantChef');
	}
	
	public function login() {
	    if ($this->request->is('post')) {
	    	//print_r($this->request->input());
	        if ($this->Auth->login()) {
	            return $this->redirect($this->Auth->redirectUrl());
	        }
	        $this->Session->setFlash(__('Invalid login information, please try again.'));
	    }
	}
	
	public function logout() {
		return $this->redirect($this->Auth->logout());
	}

    public function index() {
        $this->User->recursive = 0;
        $this->set('users', $this->paginate());
    }

    public function view($id = null) {
        $this->User->id = $id;
        if (!$this->User->exists()) {
            throw new NotFoundException(__('Invalid user'));
        }
        $this->set('user', $this->User->read(null, $id));
    }

    public function addARestaurantChef() {
    	$this->request->allowMethod('post');

        $this->User->create();
        $data = $this->request->input('json_decode', true);

        if (!isset($data)) {
            $this->response->statusCode('403');
            $this->set(array(
                'message' => 'JSON payload format error',
                '_serialize' => array('message')
            )); 
        } else {
        	$this->User->set($data);
			if ($this->User->save()) {
                // This may have concurrency problem
                $id = $this->User->getLastInsertId();
                $this->set(array(
                    'id' => $id,
                    '_serialize' => array('id')
                ));             
            } else {
                $this->response->statusCode('500');
                $this->set(array(
                    'message' => 'Failed',
                    '_serialize' => array('message')
                )); 
            }
        }
    }
}
