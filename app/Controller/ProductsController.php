<?php
// TODO: Reduce code duplication
class ProductsController extends AppController {
    public $components = array('RequestHandler');

    public function index() {
        if($this->request->is('get')) {
            $products = $this->Product->find('all');

            if (!$products) {
            	throw new NotFoundException(__('No Recipes Found'));
            }

            $this->set(array(
                'products' => $products,
                '_serialize' => array('products')
            )); 
        } else {
           throw new MethodNotAllowedException();
        }

    }
    
    public function view($id = null) {
        if($this->request->is('get')) {
            if (!is_numeric($id)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid id.',
                    '_serialize' => array('message')
                ));
            } else {
                $product = $this->Product->findById($id);
                if (!$product) {
                    throw new NotFoundException(__('Recipe with ID: '.$id. ' Not Found'));
                }
                $this->set(array(
                    'product' => $product,
                    '_serialize' => array('product')
                ));
            }
        } else {
            throw new MethodNotAllowedException();
        }
    }

    public function listByRestuarant($restaurantId = null) {
		if($this->request->is('get')) {
            if (!is_numeric($restaurantId)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid id.',
                    '_serialize' => array('message')
                ));
            } else {
                $products = $this->Product->find('all', array(
        			'conditions' => array('Product.restaurant_id' => $restaurantId),
                    'order' => array('Product.id' => 'desc')
    			));

                if (!$products) {
                    throw new NotFoundException(__('No recipe of the restaurant with ID: '.$restaurantId. ' Found'));
                }

                $this->set(array(
                    'products' => $products,
                    '_serialize' => array('products')
                ));
            }
        } else {
           throw new MethodNotAllowedException();
        }
    }
}
