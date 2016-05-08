<?php
// TODO: Reduce code duplication
class RestaurantsController extends AppController {
    public $components = array('RequestHandler');

    public function index() {
        if($this->request->is('get')) {
            $restaurants = $this->Restaurant->find('all');

            if (!$restaurants) {
            	throw new NotFoundException(__('No Restaurants Found'));
            }

            $this->set(array(
                'restaurants' => $restaurants,
                '_serialize' => array('restaurants')
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
                $restaurant = $this->Restaurant->findById($id);
                if (!$restaurant) {
                    throw new NotFoundException(__('Restaurant with ID: '.$id. ' Not Found'));
                }
                $this->set(array(
                    'restaurant' => $restaurant,
                    '_serialize' => array('restaurant')
                ));
            }
        } else {
            throw new MethodNotAllowedException();
        }
    }
}
