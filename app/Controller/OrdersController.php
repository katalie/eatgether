<?php
// TODO: Reduce code duplication
App::import('Vendor', 'stripe-php/init');
class OrdersController extends AppController {
    public $components = array(
        'RequestHandler',
        'Session',
        'Auth' => array(
            'loginRedirect' => array(
                'controller' => 'orders',
                'action' => 'viewByRestaurant'
            ),
            'logoutRedirect' => array(
                'controller' => 'pages',
                'action' => 'display'
            ),
            'authenticate' => array(
                'Form' => array(
                    'userModel' => 'User',
                    'passwordHasher' => 'Simple'
                )
            )
        )
    );

    public $uses = array('Order', 'Customer', 'Token', 'TimeSlot', 'Restaurant', 'OrderDetail', 'Product', 'Location');
    // deprecated
    public function add() {
        $this->request->onlyAllow('post');

        $token = $this->request->header('token');

        $data = $this->request->input('json_decode', true);

        if($token && isset($data) && array_key_exists('customer_id', $data['Order'])) {
            $customer = $this->Customer->findById($data['Order']['customer_id']);

            // verify activation
            if(!array_key_exists('Customer', $customer) || !$customer['Customer']['enabled']) {
                throw new ForbiddenException(__('Account not activated')); 
            }

            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $data['Order']['customer_id'])
            ));

            // verify token
            if(!$lastToken || strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Token invalid',
                    '_serialize' => array('message')
                ));
            } else {
                $this->Order->create();

                if (!array_key_exists('due', $data)) {
                    $this->response->statusCode('400');
                    $this->set(array(
                        'message' => 'Please provide order due time',
                        '_serialize' => array('message')
                    )); 
                } else {
                    $data['Order']['due'] = $data['due'];
                    $data['Order']['order_serial'] = uniqid(rand(100, 999));

                    if ($data['due'] <= date("Y-m-d H:i:s")) {
                        $this->response->statusCode('400');
                        $this->set(array(
                            'message' => 'Too late',
                            '_serialize' => array('message')
                        )); 
                    } else if ($this->Order->saveAssociated($data)) {
                        // This may have concurrency problem
                        $id = $this->Order->getLastInsertId();

                        $this->set(array(
                            'id' => $id,
                            '_serialize' => array('id')
                        )); 
                    } else {
                        $this->response->statusCode('500');
                        $this->set(array(
                            'message' => 'Order did not placed.',
                            '_serialize' => array('message')
                        )); 
                    }
                }
            }
        } else {
            throw new ForbiddenException(__('JSON payload format error. Please provide both a token and a customer ID!'));
        }
    }

    public function payAndAdd() {
        $this->request->onlyAllow('post');

        $token = $this->request->header('token');

        $data = $this->request->input('json_decode', true);

        if($token && isset($data) && array_key_exists('customer_id', $data['Order'])) {
            $customer = $this->Customer->findById($data['Order']['customer_id']);

            // verify activation
            if(!array_key_exists('Customer', $customer) || !$customer['Customer']['enabled']) {
                throw new ForbiddenException(__('Account not activated')); 
            }

            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $data['Order']['customer_id'])
            ));

            // verify token
            if(!$lastToken || strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Token invalid',
                    '_serialize' => array('message')
                ));
            } else if(empty($data["payment"]["stripe_token"]) && empty($data["payment"]["customer_id"])) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provite either a stripe_token or customer_id.',
                    '_serialize' => array('message')
                ));
            } else if(empty($data['Order']['total']) || $data["Order"]["total"] <=0 ) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provite a valid total.',
                    '_serialize' => array('message')
                ));
            } else if($data['due'] <= date("Y-m-d H:i:s")) {
                $this->response->statusCode('400');
                $this->set(array(
                    'message' => 'Too late',
                    '_serialize' => array('message')
                )); 
            } else {
                // Set your secret key: remember to change this to your live secret key in production
                // See your keys here https://dashboard.stripe.com/account/apikeys
                \Stripe\Stripe::setApiKey("XXXXXXX");

                if(!empty($data["payment"]["customer_id"])) {
                    // Charge the Customer instead of the card
                    \Stripe\Charge::create(array(
                      "amount" => $data["Order"]["total"] * 100, # amount in cents, again
                      "currency" => "cad",
                      "customer" => $data['payment']['customer_id'])
                    );
                } else {
                    //Create a Customer
                    $stripeCustomer = \Stripe\Customer::create(array(
                      "source" => $data['payment']['stripe_token'],
                      "description" => $data["payment"]["description"])
                    );

                    \Stripe\Charge::create(array(
                      "amount" => $data["Order"]["total"] * 100, # amount in cents, again
                      "currency" => "cad",
                      "customer" => $stripeCustomer->id)
                    );
                }

                $this->Order->create();

                if (!array_key_exists('due', $data)) {
                    $this->response->statusCode('400');
                    $this->set(array(
                        'message' => 'Please provide order due time',
                        '_serialize' => array('message')
                    )); 
                } else {
                    $data['Order']['due'] = $data['due'];
                    $data['Order']['order_serial'] = uniqid(rand(100, 999));

                    if ($this->Order->saveAssociated($data)) {
                        // This may have concurrency problem
                        $id = $this->Order->getLastInsertId();

                        if(isset($stripeCustomer)) {
                            $this->set(array(
                                'id' => $id,
                                'customer_id' => $stripeCustomer->id,
                                '_serialize' => array('id', 'customer_id')
                            ));  
                        } else {
                            $this->set(array(
                                'id' => $id,
                                '_serialize' => array('id')
                            )); 
                        }
                    } else {
                        $this->response->statusCode('402');
                        $this->set(array(
                            'message' => 'Paid but order was not placed.',
                            '_serialize' => array('message')
                        )); 
                    }
                }
            }
        } else {
            throw new ForbiddenException(__('JSON payload format error. Please provide both a token and a customer ID!'));
        }
    }

    public function listByCustomer($customerId = null) {
        $this->request->onlyAllow('get');

        $token = $this->request->header('token');

        if($token && is_numeric($customerId)) {
            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $customerId)
            ));

            if(!$lastToken || strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid token and customer ID',
                    '_serialize' => array('message')
                ));
            } else {
                // Creating and destroying associations on the fly
                // $this->Order->unbindModel(
                //     array('hasMany' => array('OrderDetail'))
                // );
                $this->Order->bindModel(
                    array('belongsTo' => array(
                            'Restaurant' => array(
                                'className' => 'Restaurant',
                                'foreignKey' => 'restaurant_id',
                                'fields' => array('name')
                            )
                        )
                    )
                );

                $this->Order->bindModel(
                    array('belongsTo' => array(
                            'Location' => array(
                                'className' => 'Location',
                                'foreignKey' => 'location_id',
                                'fields' => array('name', 'description', 'latitude', 'longitude')
                            )
                        )
                    )
                );

                $orders = $this->Order->find('all', array(
                    'conditions' => array('Order.customer_id' => $customerId)
                ));

                if (!$orders) {
                    throw new NotFoundException(__('No orders of the customer with ID: '.$customerId. ' Found'));
                }

                if ($customerId == 89) {
                    foreach ($orders as $key => $value) {
                        $orders[$key]['Order']['order_serial'] = $value['Order']['order_serial'].' ❤ Kelly';
                    }
                }

                $this->set(array(
                    'orders' => $orders,
                    '_serialize' => array('orders')
                ));       
            }
        } else {
           throw new ForbiddenException(__('Please provide both a token and a customer ID!'));
        }
    }

    public function view($id = null, $customerId = null) {
        $this->request->onlyAllow('get');

        $token = $this->request->header('token');

        if($token && is_numeric($customerId)) {
            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $customerId)
            ));

            if(!$lastToken || strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid token and customer ID',
                    '_serialize' => array('message')
                ));
            } else {
                if (!is_numeric($id)) {
                    $this->response->statusCode('403');
                    $this->set(array(
                        'message' => 'Please provide a valid id.',
                        '_serialize' => array('message')
                    ));
                } else {
                    $order = $this->Order->find('first', array(
                        'conditions' => array('Order.customer_id' => $customerId, 'Order.id' => $id)
                    ));
                    if (!$order) {
                        throw new NotFoundException(__('Order with ID: '.$id. ' Not Found'));
                    }
                    $this->set(array(
                        'order' => $order,
                        '_serialize' => array('order')
                    ));
                }    
            }
        } else {
            throw new ForbiddenException(__('Please provide both a token and a customer ID!'));
        }
    }

    public function changeOrderState($id = null, $customerId = null) {
        $this->request->onlyAllow('put');

        $token = $this->request->header('token');

        if($token && is_numeric($customerId)) {
            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $customerId)
            ));

            $customer = $this->Customer->find('first', array(
                'conditions' => array('Customer.id' => $customerId)
            ));

            if(!$lastToken || strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid token and customer ID',
                    '_serialize' => array('message')
                ));
            } else if(!isset($customer['Customer']['restaurant_id'])) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'You don\'t have the permission to change order state',
                    '_serialize' => array('message')
                ));
            } else {
                $data = $this->request->input('json_decode', true);
                if(!isset($data['Order']['state']) || !is_numeric($id)) {
                    $this->response->statusCode('403');
                    $this->set(array(
                        'message' => 'Invalid payload',
                        '_serialize' => array('message')
                    ));
                } else {
                    // Read the instance into the model
                    $this->Order->read(null, $id);
                    if($this->Order->getNumRows() < 1) {
                        throw new NotFoundException(__('Order with ID: '.$id. ' Not Found'));
                    }

                    // Modified should not be kept, drop it
                    unset($data['Order']['modified']);
                    $data['Order']['modified'] = date("Y-m-d H:i:s");
                    $data['Order']['delivered_time'] = date("Y-m-d H:i:s");

                    $this->Order->set($data);

                    if ($this->Order->save()) {
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
                }    
            }
        } else {
            throw new ForbiddenException(__('Please provide both a token and a customer ID!'));
        }
    }

    // For food distributor
    public function getCurrentRoundOrders($customerId = null, $restaurantId = null) {
        $this->request->onlyAllow('get');

        $token = $this->request->header('token');

        if($token && is_numeric($customerId) || is_numeric($restaurantId)) {
            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $customerId)
            ));

            if(!$lastToken || strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid token and customer ID',
                    '_serialize' => array('message')
                ));
            } else {
                $this->Order->bindModel(
                    array('belongsTo' => array(
                            'Restaurant' => array(
                                'className' => 'Restaurant',
                                'foreignKey' => 'restaurant_id',
                                'fields' => array('name')
                            )
                        )
                    )
                );

                $this->Order->bindModel(
                    array('belongsTo' => array(
                            'Location' => array(
                                'className' => 'Location',
                                'foreignKey' => 'location_id',
                                'fields' => array('name', 'description')
                            )
                        )
                    )
                );

                $this->Order->bindModel(
                    array('belongsTo' => array(
                            'Customer' => array(
                                'className' => 'Customer',
                                'foreignKey' => 'customer_id',
                                'fields' => array('name', 'telephone')
                            )
                        )
                    )
                );

                $timeSlots = $this->TimeSlot->find('all', array(
                    'conditions' => array('TimeSlot.location_id' => $restaurantId)
                ));

                $orderTime = $this->Order->determineCurrentRoundOrderTime($timeSlots);

                $orders = $this->Order->find('all', array(
                    'conditions' => array('Order.location_id' => $restaurantId, "Order.created >" => $orderTime["startTime"], "Order.created <" => $orderTime["endTime"]),
                    'order' => array('Order.delivered_time' => 'asc')
                ));

                if (!$orders) {
                    throw new NotFoundException(__('No orders with the customer ID: '.$customerId. ' and location ID: '. $restaurantId . ' Found'));
                }

                $this->set(array(
                    'orders' => $orders,
                    '_serialize' => array('orders')
                ));       
            }
        } else {
           throw new ForbiddenException(__('Please provide a token, a customer ID and a location ID!'));
        }
    }

    public function deleteOrder($id = null, $customerId = null) {
        $this->request->onlyAllow('put');

        $token = $this->request->header('token');

        if($token && is_numeric($customerId)) {
            $lastToken = $this->Token->find('first', array(
                'order' => array('Token.created' => 'desc'),
                'conditions' => array('Token.customer_id' => $customerId)
            ));

            $customer = $this->Customer->find('first', array(
                'conditions' => array('Customer.id' => $customerId)
            ));

            if(!$lastToken || strcmp($lastToken['Token']['auth_token'], $token) != 0 || $lastToken['Token']['expiration'] < date("Y-m-d H:i:s")) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid token and customer ID',
                    '_serialize' => array('message')
                ));
            } else {
                $data = $this->request->input('json_decode', true);
                if(strcmp($data['Order']['state'], "2") !=0 || !is_numeric($id)) {
                    $this->response->statusCode('403');
                    $this->set(array(
                        'message' => 'Invalid payload',
                        '_serialize' => array('message')
                    ));
                } else {
                    // Read the instance into the model
                    $this->Order->read(null, $id);
                    if($this->Order->getNumRows() < 1) {
                        throw new NotFoundException(__('Order with ID: '.$id. ' Not Found'));
                    }
                    
                    if ($this->Order->data["Order"]["due"] <= date("Y-m-d H:i:s")) {
                        throw new BadRequestException(__('Too late.'));
                    }

                    // Modified should not be kept, drop it
                    unset($data['Order']['modified']);
                    $data['Order']['modified'] = date("Y-m-d H:i:s");

                    $this->Order->set($data);

                    if ($this->Order->save()) {
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
                }    
            }
        } else {
            throw new ForbiddenException(__('Please provide both a token and a customer ID!'));
        }
    }

    public function getVcode() {
        $num = 4;
        $w = 70;
        $h = 30;

        // 去掉了 0 1 O l 等
        $str = "23456789abcdefghijkmnpqrstuvwxyz";
        $code = '';
        for ($i = 0; $i < $num; $i++) {
            $code .= $str[mt_rand(0, strlen($str)-1)];
        }
        //将生成的验证码写入session，备验证页面使用
        $this->Session->write('vcode', $code);
        
        $this->response->type('image/PNG');

        //创建图片，定义颜色值
        $im = imagecreate($w, $h);
        $black = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
        $gray = imagecolorallocate($im, 118, 151, 199);
        $bgcolor = imagecolorallocate($im, 235, 236, 237);

        //画背景
        imagefilledrectangle($im, 0, 0, $w, $h, $bgcolor);
        //画边框
        imagerectangle($im, 0, 0, $w-1, $h-1, $gray);
        //imagefill($im, 0, 0, $bgcolor);

        //在画布上随机生成大量点，起干扰作用;
        for ($i = 0; $i < 80; $i++) {
            imagesetpixel($im, rand(0, $w), rand(0, $h), $black);
        }
        //将字符随机显示在画布上,字符的水平间距和位置都按一定波动范围随机生成
        $strx = rand(5, 14);
        for ($i = 0; $i < $num; $i++) {
            $strpos = rand(1, 6);
            imagestring($im, 5, $strx, $strpos, substr($code, $i, 1), $black);
            $strx += rand(8, 14);
        }
        imagepng($im);
        imagedestroy($im);
    }

    public function checkVcode($vCode = null) {
        $this->request->onlyAllow('post');

        $data = $this->request->input('json_decode', true);

        if (strcmp($data['vcode'], $this->Session->read('vcode')) == 0) {
            $this->set(array(
                'message' => 'vcode correct',
                '_serialize' => array('message')
            ));
        } else {
            throw new ForbiddenException(__('vcode incorrect'));
        }
    }

    public function viewByRestaurant() {
        $restaurantId = $this->Auth->user()['restaurant_id'];

        $this->Order->bindModel(
            array('belongsTo' => array(
                    'Customer' => array(
                        'className' => 'Customer',
                        'foreignKey' => 'customer_id',
                        'fields' => array('telephone')
                    )
                )
            )
        );

        $this->Order->bindModel(
            array('belongsTo' => array(
                    'Location' => array(
                        'className' => 'Location',
                        'foreignKey' => 'location_id',
                        'fields' => array('id', 'name')
                    )
                )
            )
        );

        $this->Order->bindModel(
            array('belongsTo' => array(
                    'Restaurant' => array(
                        'className' => 'Restaurant',
                        'foreignKey' => 'restaurant_id',
                        'fields' => array('name')
                    )
                )
            )
        );

        $timeSlots = $this->TimeSlot->find('all', array(
            'conditions' => array('TimeSlot.restaurant_id' => $restaurantId)
        ));

        $orderTime = $this->Order->determineCurrentRoundOrderTime($timeSlots);

        $orders = $this->Order->find('all', array(
            'conditions' => array('Order.restaurant_id' => $restaurantId, "Order.created >" => $orderTime["startTime"], "Order.created <" => $orderTime["endTime"])
        ));

        $restaurantUser = $this->Restaurant->find('first', array(
            'conditions' => array('Restaurant.id' => $restaurantId)
        ));
        
        $grandTotal = 0;
        foreach ($orders as $key => $value) {
            $grandTotal += $value['Order']['total'];
        }

        $this->set('orders', $orders);
        $this->set('user', $restaurantUser);
        $this->set('orderTime', $orderTime);
        $this->set('grandTotal', $grandTotal);
    }

    public function viewCurrentChefList() {
        $restaurantId = $this->Auth->user()['restaurant_id'];

        $this->Order->bindModel(
            array('belongsTo' => array(
                    'Restaurant' => array(
                        'className' => 'Restaurant',
                        'foreignKey' => 'restaurant_id',
                        'fields' => array('name')
                    )
                )
            )
        );

        $timeSlots = $this->TimeSlot->find('all', array(
            'conditions' => array('TimeSlot.restaurant_id' => $restaurantId)
        ));

        $orderTime = $this->Order->determineCurrentRoundOrderTime($timeSlots);

        $orders = $this->Order->find('all', array(
            'conditions' => array('Order.restaurant_id' => $restaurantId, "Order.created >" => $orderTime["startTime"], "Order.created <" => $orderTime["endTime"], "Order.state <" => 2)
        ));

        $products = $this->Product->find('all', array(
            'conditions' => array('Product.restaurant_id' => $restaurantId)
        ));

        foreach ($products as $key => $value) {
            $products[$key]['Product']['quantity'] = 0;
        }

        foreach ($orders as $key => $value) {
            foreach ($value['OrderDetail'] as $keyDetail => $valueDetail) {
                foreach ($products as $keyProduct => $valueProduct) {
                    if(strcmp($valueDetail['product_id'], $valueProduct['Product']['id']) == 0) {
                        $products[$keyProduct]['Product']['quantity'] += $valueDetail['quantity'];
                    }
                }
            }
        }

        $restaurantUser = $this->Restaurant->find('first', array(
            'conditions' => array('Restaurant.id' => $restaurantId)
        ));

        $this->set('products', $products);
        $this->set('user', $restaurantUser);
        $this->set('orderTime', $orderTime);
    }

    public function viewLocationChefList($locationId = null) {
        $restaurantId = $this->Auth->user()['restaurant_id'];

        $this->Order->bindModel(
            array('belongsTo' => array(
                    'Restaurant' => array(
                        'className' => 'Restaurant',
                        'foreignKey' => 'restaurant_id',
                        'fields' => array('name')
                    )
                )
            )
        );

        $location = $this->Location->getParentNode($locationId);

        $timeSlots = $this->TimeSlot->find('all', array(
            'conditions' => array('TimeSlot.restaurant_id' => $restaurantId)
        ));

        $orderTime = $this->Order->determineCurrentRoundOrderTime($timeSlots);

        $orders = $this->Order->find('all', array(
            'conditions' => array('Order.restaurant_id' => $restaurantId, "Order.created >" => $orderTime["startTime"], "Order.created <" => $orderTime["endTime"], 'Order.location_id' => $locationId, "Order.state <" => 2)
        ));

        $products = $this->Product->find('all', array(
            'conditions' => array('Product.restaurant_id' => $restaurantId)
        ));

        foreach ($products as $key => $value) {
            $products[$key]['Product']['quantity'] = 0;
        }

        foreach ($orders as $key => $value) {
            foreach ($value['OrderDetail'] as $keyDetail => $valueDetail) {
                foreach ($products as $keyProduct => $valueProduct) {
                    if(strcmp($valueDetail['product_id'], $valueProduct['Product']['id']) == 0) {
                        $products[$keyProduct]['Product']['quantity'] += $valueDetail['quantity'];
                    }
                }
            }
        }

        $restaurantUser = $this->Restaurant->find('first', array(
            'conditions' => array('Restaurant.id' => $restaurantId)
        ));

        $this->set('products', $products);
        $this->set('user', $restaurantUser);
        $this->set('orderTime', $orderTime);
        $this->set('location', $location);
    }

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('add', 'view', 'listByCustomer', 'changeOrderState', 'getCurrentRoundOrders', 'getVcode', 'checkVcode', 'deleteOrder', 'payAndAdd');
    }
}
