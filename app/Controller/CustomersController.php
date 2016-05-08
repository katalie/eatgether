<?php
// TODO: Reduce code duplication
App::import('Vendor', 'twilio-php-master/Services/Twilio');
class CustomersController extends AppController {
    public $components = array('RequestHandler');
    public $uses = array('Customer', 'Token');

    private $sid = "XXXXXXX"; //* Twilio id get it from Twilio account */
    private $token = "XXXXX";
    private $fromPhone = "000-000-0000";

    // Deprecated
    public function add() {
        if($this->request->is('post')) {
            $this->Customer->create();
            $data = $this->request->input('json_decode', true);

            if (!isset($data)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'JSON payload format error',
                    '_serialize' => array('message')
                )); 
            } else {
            	$this->Customer->set($data);

                // Set the enable code to the model
                $enableCode = $this->Customer->generateCellEnableCode();
                $this->Customer->set(array(
                    'enable_code' => $enableCode
                ));

            	if ($this->Customer->validates(array('fieldList' => array('name', 'postal_code', 'email', 'password', 'telephone')))) { 
                    $client = new Services_Twilio($this->sid, $this->token);

                    try {    
                        $message = $client->account->messages->create(array(
                            "From" => $this->fromPhone,
                            "To" => $data['Customer']['telephone'],
                            "Body" => "感谢您注册Eatogether! 您的六位数字激活码是: $enableCode."
                        ));

                        if ($this->Customer->save()) {
                            // This may have concurrency problem
                            $id = $this->Customer->getLastInsertId();
                            $this->set(array(
                                'id' => $id,
                                '_serialize' => array('id')
                            ));

                            // if (!isset($data['Customer']['name'])) {
                            //     $this->Customer->sendEmail($id, $data['Customer']['telephone'], $enableCode, $data['Customer']['email']);
                            // } else {
                            //     $this->Customer->sendEmail($id, $data['Customer']['name'], $enableCode, $data['Customer']['email']);
                            // }
                        } else {
                            $this->response->statusCode('400');
                            $this->set(array(
                                'message' => 'Failed',
                                '_serialize' => array('message')
                            )); 
                        }
                    } catch (Services_Twilio_RestException $e) {
                        //echo $e->getCode();
                        $this->response->statusCode('403');
                        $this->set(array(
                            'message' => 'Check your telephone.',
                            '_serialize' => array('message')
                        )); 
                    }
				} else {
					$message = $this->Customer->validationErrors;
					$this->response->statusCode('403');
                    $this->set(array(
                        'message' => $message,
                        '_serialize' => array('message')
                    )); 
				}
            }
        } else {
            throw new MethodNotAllowedException();
        }
    }

    public function verifyTelephone() {
        if($this->request->is('post')) {
            $this->Customer->create();
            $data = $this->request->input('json_decode', true);

            if (!isset($data)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'JSON payload format error',
                    '_serialize' => array('message')
                )); 
            } else {
                $customerTest = $this->Customer->find('first', array(
                    'conditions' => array('Customer.telephone' => $data['Customer']['telephone'])
                ));

                if ($customerTest != null && $customerTest['Customer']['enabled'] == 0) {
                    $this->response->statusCode('201');
                    $this->set(array(
                        'message' => 'Telephone number exists. Enabled false.',
                        '_serialize' => array('message')
                    )); 
                } else if ($customerTest != null && $customerTest['Customer']['enabled'] == 1) {
                    $this->response->statusCode('202');
                    $this->set(array(
                        'message' => 'Telephone number exists. Enabled true.',
                        '_serialize' => array('message')
                    )); 
                } else {
                    $this->Customer->set($data);

                    // Set the enable code to the model
                    $enableCode = $this->Customer->generateCellEnableCode();
                    $this->Customer->set(array(
                        'enable_code' => $enableCode,
                        'email' => 'example@example.com',
                        'password' => '12345678',
                        'last_time_message' => date("Y-m-d H:i:s")
                    ));

                    if ($this->Customer->validates(array('fieldList' => array('telephone')))) { 
                        $client = new Services_Twilio($this->sid, $this->token);

                        try {    
                            $message = $client->account->messages->create(array(
                                "From" => $this->fromPhone,
                                "To" => $data['Customer']['telephone'],
                                "Body" => "感谢您注册Eatogether! 您的六位数字激活码是: $enableCode."
                            ));

                            if ($this->Customer->save()) {
                                // This may have concurrency problem
                                $id = $this->Customer->getLastInsertId();
                                $this->set(array(
                                    'id' => $id,
                                    '_serialize' => array('id')
                                ));
                            } else {
                                $this->response->statusCode('400');
                                $this->set(array(
                                    'message' => 'Failed',
                                    '_serialize' => array('message')
                                )); 
                            }
                        } catch (Services_Twilio_RestException $e) {
                            //echo $e->getCode();
                            $this->response->statusCode('403');
                            $this->set(array(
                                'message' => 'Check your telephone.',
                                '_serialize' => array('message')
                            )); 
                        }
                    } else {
                        $message = $this->Customer->validationErrors;
                        $this->response->statusCode('403');
                        $this->set(array(
                            'message' => $message,
                            '_serialize' => array('message')
                        )); 
                    }
                }
            }
        } else {
            throw new MethodNotAllowedException();
        }
    }

    public function addInformation() {
        $this->request->onlyAllow('put');

        $data = $this->request->input('json_decode', true);

        if (!array_key_exists('telephone', $data['Customer']) || !array_key_exists('email', $data['Customer']) || !array_key_exists('password', $data['Customer'])) {
            throw new ForbiddenException(__('JSON payload error.'));
        }

        $customer = $this->Customer->find('first', array(
            'conditions' => array('Customer.telephone' => $data['Customer']['telephone'], 'Customer.enabled' => 1)
        ));

        if (!$customer) {
            throw new NotFoundException(__('Customer telephone ID: '.$data['Customer']['telephone']. ' not found or this customer has not been enabled.'));
        }

        $this->Customer->read(null, $customer['Customer']['id']);

        $data['Customer']['modified'] = date("Y-m-d H:i:s");
        //$data['Customer']['enabled'] = 1;

        // Telephone should not be updated anyway
        unset($data['Customer']['telephone']);

        $this->Customer->set($data);

        if ($this->Customer->validates()) {
            if ($this->Customer->save()) {
                $this->set(array(
                    'message' => 'Information created',
                    '_serialize' => array('message')
                )); 
            } else {
                $this->response->statusCode('500');
                $this->set(array(
                    'message' => 'Failed',
                    '_serialize' => array('message')
                )); 
            }
        } else {
            $message = $this->Customer->validationErrors;
            $this->response->statusCode('403');
            $this->set(array(
                'message' => $message,
                '_serialize' => array('message')
            )); 
        }
    }

    public function view($id = null) {
        $this->request->onlyAllow('get');

        $token = $this->request->header('token');

        if($token) {
            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $id)
            ));

            if(strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid token.',
                    '_serialize' => array('message')
                ));
            } else {
                $customer = $this->Customer->findById($id);
                if (!$customer) {
                    throw new NotFoundException(__('Customer with ID: '.$id. ' Not Found'));
                }
                // App should not store password
                unset($customer['Customer']['password']);
                $this->set(array(
                    'customer' => $customer,
                    '_serialize' => array('customer')
                ));
            }
        } else {
            throw new ForbiddenException('Please provide a token!');
        }
    }
    
    public function edit($id = null) {
        $this->request->onlyAllow('put');

        $token = $this->request->header('token');

        if($token) {
            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $id)
            ));

            // token should not expire
            if(strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid token.',
                    '_serialize' => array('message')
                ));
            } else {
                $data = $this->request->input('json_decode', true);
                if(!isset($data) || !is_numeric($id)) {
                    $this->response->statusCode('403');
                    $this->set(array(
                        'message' => 'Invalid payload',
                        '_serialize' => array('message')
                    )); 
                } else {
                    // Read the instance into the model
                    $this->Customer->read(null, $id);
                    if($this->Customer->getNumRows() < 1) {
                        throw new NotFoundException(__('Customer with ID: '.$id. ' Not Found'));
                    }

                    // Telephone should not be updated anyway
                    unset($data['Customer']['telephone']);
                    // Password is not supposed to appear here; but if it does, drop it
                    unset($data['Customer']['password']);
                    // Modified should not be kept, drop it
                    unset($data['Customer']['modified']);


                    $data['Customer']['modified'] = date("Y-m-d H:i:s");

                    $this->Customer->set($data);

                    if ($this->Customer->validates()) {
                        if ($this->Customer->save()) {
                            $this->set(array(
                                'message' => 'Updated',
                                '_serialize' => array('message')
                            )); 
                        } else {
                            $this->response->statusCode('500');
                            $this->set(array(
                                'message' => 'Failed',
                                '_serialize' => array('message')
                            )); 
                        }
                    } else {
                        $message = $this->Customer->validationErrors;
                        $this->response->statusCode('403');
                        $this->set(array(
                            'message' => $message,
                            '_serialize' => array('message')
                        )); 
                    }
                }
            }
        } else {
            throw new ForbiddenException(__('Please provide a token!'));
        }
    }

    public function changePassword($id = null) {
        $this->request->onlyAllow('put');

        $token = $this->request->header('token');

        if($token) {
            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $id)
            ));

            if(!$lastToken || strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid token.',
                    '_serialize' => array('message')
                ));
            } else {
                $data = $this->request->input('json_decode', true);
                if(!isset($data['Customer']['password']) || !isset($data['Customer']['password_old']) || !is_numeric($id)) {
                    $this->response->statusCode('403');
                    $this->set(array(
                        'message' => 'Invalid payload',
                        '_serialize' => array('message')
                    )); 
                } else {
                    $data['Customer']['password_old'] = $this->Customer->hashPassword($data['Customer']['password_old']);
                    $customer = $this->Customer->find('first', array(
                        'conditions' => array('Customer.id' => $id, 'Customer.password' => $data['Customer']['password_old'])
                    ));

                    if(!$customer) {
                        throw new NotFoundException(__('No customer with the id and password found.'));
                    }

                    // Read the instance into the model
                    $this->Customer->read(null, $id);

                    // Set the new password to the model
                    $this->Customer->set(array(
                        'password' => $data['Customer']['password']
                    ));

                    if ($this->Customer->validates(array('fieldList' => array('password')))) {
                        if ($this->Customer->saveField('password', $data['Customer']['password'])) {
                            $this->set(array(
                                'message' => 'Changed',
                                '_serialize' => array('message')
                            )); 
                        } else {
                            $this->response->statusCode('500');
                            $this->set(array(
                                'message' => 'Failed',
                                '_serialize' => array('message')
                            )); 
                        }
                    } else {
                        $message = $this->Customer->validationErrors;
                        $this->response->statusCode('403');
                        $this->set(array(
                            'message' => $message,
                            '_serialize' => array('message')
                        ));
                    }
                }
            }
        } else {
            throw new ForbiddenException(__('Please provide a token!'));
        }
    }

    // create token
    public function login() {
        $this->request->onlyAllow('post');

        $data = $this->request->input('json_decode', true);
        if (!isset($data) || !array_key_exists('telephone', $data['Customer']) || !array_key_exists('password', $data['Customer'])) {
            $this->response->statusCode('403');
            $this->set(array(
                'message' => 'JSON payload format error',
                '_serialize' => array('message')
            )); 
        } else {
            $data['Customer']['password'] = $this->Customer->hashPassword($data['Customer']['password']);
            $customer = $this->Customer->find('first', array(
                'conditions' => array('Customer.telephone' => $data['Customer']['telephone'], 'Customer.password' => $data['Customer']['password'])
            ));

            if(!$customer) {
                throw new NotFoundException(__('No customer with the telephone and password found.'));
            }

            $this->Token->create();

            $this->Token->set(array(
                'customer_id' => $customer['Customer']['id'],
                'auth_token' => base64_encode(uniqid() . substr(uniqid(), 11, 2)),
                'expiration' => date("Y-m-d H:i:s", strtotime('+2 week'))
            ));

            if ($this->Token->save()) {
                $lastToken = $this->Token->find('first', array(
                    'order' => array('Token.created' => 'desc'),
                    'conditions' => array('Token.customer_id' => $customer['Customer']['id'])
                ));

                if($lastToken) {
                    $lastToken["Token"]["enabled"] = $customer['Customer']['enabled'];
                    $lastToken['Token']["restaurant_id"] = $customer['Customer']['restaurant_id'];
                    $lastToken['Token']["name"] = $customer['Customer']['name'];
                    $lastToken['Token']["telephone"] = $customer['Customer']['telephone'];
                    $lastToken['Token']["email"] = $customer['Customer']['email'];
                    $this->set(array(
                        'token' => $lastToken,
                        '_serialize' => array('token')
                    )); 
                } else {
                    throw new NotFoundException(__('Login failed!'));
                }
            } else {
                $this->response->statusCode('500');
                $this->set(array(
                    'message' => 'Login failed!',
                    '_serialize' => array('message')
                )); 
            }
        }
    }

    // should get called when users open the app
    public function refreshToken() {
        $this->request->onlyAllow('post');

        $data = $this->request->input('json_decode', true);

        if (!isset($data) || !array_key_exists('telephone', $data['Customer']) || !array_key_exists('password', $data['Customer'])) {
            $this->response->statusCode('403');
            $this->set(array(
                'message' => 'JSON payload format error',
                '_serialize' => array('message')
            )); 
        } else {
            $data['Customer']['password'] = $this->Customer->hashPassword($data['Customer']['password']);
            $customer = $this->Customer->find('first', array(
                'conditions' => array('Customer.telephone' => $data['Customer']['telephone'], 'Customer.password' => $data['Customer']['password'])
            ));

            if(!$customer) {
                throw new NotFoundException(__('No customer with the telephone and password found.'));
            }

            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $customer['Customer']['id'])
            ));

            if(!$lastToken) {
                throw new NotFoundException(__('No token associate with the customer. Please login first.'));
            }

            $this->Token->read(null, $lastToken['Token']['id']);

            $this->Token->set(array(
                'expiration' => date("Y-m-d H:i:s", strtotime('+2 week')),
                'modified' => date("Y-m-d H:i:s")
            ));

            if ($this->Token->save()) {
                $lastToken = $this->Token->find('first', array(
                    'order' => array('Token.created' => 'desc'),
                    'conditions' => array('Token.customer_id' => $customer['Customer']['id'])
                ));

                if($lastToken) {
                    $this->set(array(
                        'token' => $lastToken,
                        '_serialize' => array('token')
                    )); 
                } else {
                    throw new NotFoundException(__('Login failed!'));
                }
            } else {
                $this->response->statusCode('500');
                $this->set(array(
                    'message' => 'Login failed!',
                    '_serialize' => array('message')
                )); 
            }
        }
    }

    //http://localhost/eat/customers/enable/15/54b891fdc1004642a696031dd8b1ce9d.json
    public function enable($id = null, $enable_code = null) {
        $this->request->onlyAllow('get');

        if(!isset($id) || !isset($enable_code)) {
            $this->response->statusCode('403');
            $this->set(array(
                'message' => 'Invalid enable information.',
                '_serialize' => array('message')
            )); 
        } else {
            $customer = $this->Customer->find('first', array(
                'conditions' => array('Customer.id' => $id, 'Customer.enable_code' => $enable_code, 'Customer.enabled' => 0)
            ));

            if(!$customer) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Sorry. You provided invalid activation information or your account has been activated.',
                    '_serialize' => array('message')
                )); 
                //$this->set(array('message' => 'Sorry. You provided invalid activation information or your account has been activated.'));
            } else {
                $this->Customer->id = $customer['Customer']['id'];

                if ($this->Customer->saveField('enabled', 1)) {
                    $this->response->statusCode('200');
                    $this->set(array(
                        'message' => 'Congratulations!',
                        '_serialize' => array('message')
                    ));
                    //$this->set(array('message' => 'Congratulations! You have activated your account. Open our APP and close your hunger.'));
                } else {
                    $this->response->statusCode('500');
                    $this->set(array(
                        'message' => 'Unable to activate your account.',
                        '_serialize' => array('message')
                    )); 
                    //$this->set(array('message' => 'Unable to activate your account.'));
                }
            }
        }
    }

    // must be called with a valid token
    public function resendEnableCode($customerId = null) {
        $this->request->onlyAllow('get');

        if(is_numeric($customerId)) {
            $customer = $this->Customer->find('first', array(
                'conditions' => array('Customer.id' => $customerId, 'Customer.enabled' => 0)
            ));

            if (!$customer) {
                throw new NotFoundException(__('Customer with ID: '.$customerId. ' Not Found or this customer has been enabled.'));
            }

            if ($customer['Customer']['last_time_message'] > date('Y-m-d H:i:s', strtotime('-1 minute'))) {
                $this->Customer->id = $customer['Customer']['id'];
                $this->Customer->saveField('last_time_message', date("Y-m-d H:i:s"));
                throw new BadRequestException(__('Too frequent.')); 
            }

            $enableCode = $customer['Customer']['enable_code'];
            if (!$enableCode) {
                throw new InternalErrorException(__('Database error!'));
            }

            $client = new Services_Twilio($this->sid, $this->token);
            try {    
                $message = $client->account->messages->create(array(
                    "From" => $this->fromPhone,
                    "To" => $customer['Customer']['telephone'],
                    "Body" => "感谢您注册Eatogether! 您的六位数字验证码是: $enableCode."
                ));

                $this->Customer->id = $customer['Customer']['id'];
                $this->Customer->saveField('last_time_message', date("Y-m-d H:i:s"));

                $this->set(array(
                    'message' => "Enable code resent.",
                    '_serialize' => array('message')
                ));
            } catch (Services_Twilio_RestException $e) {
                //echo $e->getCode();
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Check your telephone.',
                    '_serialize' => array('message')
                )); 
            } 
        } else {
            throw new ForbiddenException(__('Please provide both a token and a customer ID!'));
        }
    }

    public function sendVerificationCode() {
        $this->request->onlyAllow('put');

        $data = $this->request->input('json_decode', true);
        
        if (!array_key_exists('telephone', $data['Customer'])) {
            throw new ForbiddenException(__('JSON payload error.'));
        }

        $customer = $this->Customer->find('first', array(
            'conditions' => array('Customer.telephone' => $data['Customer']['telephone'])
        ));

        if (!$customer) {
            throw new NotFoundException(__('Customer telephone ID: '.$data['Customer']['telephone']. ' Not Found'));
        }

        if ($customer['Customer']['last_time_message'] > date('Y-m-d H:i:s', strtotime('-1 minute'))) {
            $this->Customer->id = $customer['Customer']['id'];
            $this->Customer->saveField('last_time_message', date("Y-m-d H:i:s"));
            throw new BadRequestException(__('Too frequent.')); 
        }

        $verificationCode = $this->Customer->generateCellEnableCode();
        $this->Customer->set(array(
            'verification_code' => $verificationCode
        ));
        
        $client = new Services_Twilio($this->sid, $this->token);
        try {    
            $message = $client->account->messages->create(array(
                "From" => $this->fromPhone,
                "To" => $customer['Customer']['telephone'],
                "Body" => "您的六位数字验证码是: $verificationCode. 感谢您对Eatogether的支持!"
            ));

            $this->Customer->id = $customer['Customer']['id'];
            if ($this->Customer->saveField('verification_code', $verificationCode) && $this->Customer->saveField('last_time_message', date("Y-m-d H:i:s"))) {
                $this->set(array(
                    'message' => "verification code sent.",
                    '_serialize' => array('message')
                ));
            } else {
                $this->response->statusCode('400');
                $this->set(array(
                    'message' => 'Failed',
                    '_serialize' => array('message')
                )); 
            }
        } catch (Services_Twilio_RestException $e) {
            //echo $e->getCode();
            $this->response->statusCode('403');
            $this->set(array(
                'message' => 'Check your telephone.',
                '_serialize' => array('message')
            )); 
        } 
    }

    public function createNewPassword(){
        $this->request->onlyAllow('put');

        $data = $this->request->input('json_decode', true);
        
        if (!array_key_exists('telephone', $data['Customer']) || !array_key_exists('verification_code', $data['Customer']) || !array_key_exists('password', $data['Customer'])) {
            throw new ForbiddenException(__('JSON payload error.'));
        }

        $customer = $this->Customer->find('first', array(
            'conditions' => array('Customer.telephone' => $data['Customer']['telephone'], 'Customer.verification_code' => $data['Customer']['verification_code'])
        ));

        if (!$customer) {
            throw new NotFoundException(__('Customer telephone ID: '.$data['Customer']['telephone']. ' Not Found'));
        }

        $this->Customer->set(array(
            'password' => $data['Customer']['password']
        ));

        $this->Customer->id = $customer['Customer']['id'];

        if ($this->Customer->validates(array('fieldList' => array('password')))) {
            if ($this->Customer->saveField('password', $data['Customer']['password'])) {
                $this->set(array(
                    'message' => 'Changed',
                    '_serialize' => array('message')
                )); 
            } else {
                $this->response->statusCode('500');
                $this->set(array(
                    'message' => 'Failed',
                    '_serialize' => array('message')
                )); 
            }
        } else {
            $message = $this->Customer->validationErrors;
            $this->response->statusCode('403');
            $this->set(array(
                'message' => $message,
                '_serialize' => array('message')
            ));
        }
    }

    public function purgeOldTokens() {
        $this->request->onlyAllow('delete');
        $this->Token->deleteAll(array('Token.expiration <' => date("Y-m-d H:i:s")), false);

        $this->set(array(
            'message' => 'Purged.',
            '_serialize' => array('message')
        )); 
    }

    public function beforeFilter() {
        parent::beforeFilter();
    }
}
