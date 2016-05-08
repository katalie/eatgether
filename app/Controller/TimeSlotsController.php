<?php
// TODO: Reduce code duplication
class TimeSlotsController extends AppController {
    public $components = array('RequestHandler');

    //public $uses = array('Order', 'Customer', 'Token', 'TimeSlot');

    public function index() {
        if($this->request->is('get')) {
            $timeSlots = $this->TimeSlot->find('all');

            if (!$timeSlots) {
            	throw new NotFoundException(__('No TimeSlots Found'));
            }

            $this->set(array(
                'timeSlots' => $timeSlots,
                '_serialize' => array('timeSlots')
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
                $timeSlot = $this->TimeSlot->findById($id);
                if (!$timeSlot) {
                    throw new NotFoundException(__('Time slot with ID: '.$id. ' Not Found'));
                }
                $this->set(array(
                    'timeSlot' => $timeSlot,
                    '_serialize' => array('timeSlot')
                ));
            }
        } else {
            throw new MethodNotAllowedException();
        }
    }

    public function listByRestarant($restaurantId = null) {
        if($this->request->is('get')) {
            if (!is_numeric($restaurantId)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid id.',
                    '_serialize' => array('message')
                ));
            } else {
                // destroying associations on the fly
                $this->TimeSlot->unbindModel(
                    array('belongsTo' => array('Restaurant'))
                );

                $timeSlots = $this->TimeSlot->find('all', array(
        			'conditions' => array('TimeSlot.restaurant_id' => $restaurantId)
    			));

                if (!$timeSlots) {
                    throw new NotFoundException(__('No time slots of the restarant with ID: '.$restaurantId. ' Found'));
                }

                $this->set(array(
                    'timeSlots' => $timeSlots,
                    '_serialize' => array('timeSlots')
                ));
            }
        } else {
           throw new MethodNotAllowedException();
        }
    }

    public function listByLocation($locationId = null) {
        if($this->request->is('get')) {
            if (!is_numeric($locationId)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid id.',
                    '_serialize' => array('message')
                ));
            } else {
                // destroying associations on the fly
                $this->TimeSlot->unbindModel(
                    array('belongsTo' => array('Location'))
                );

                $timeSlots = $this->TimeSlot->find('all', array(
        			'conditions' => array('TimeSlot.location_id' => $locationId)
    			));

                if (!$timeSlots) {
                    throw new NotFoundException(__('No time slots of the location with ID: '.$locationId. ' Found'));
                }

                $this->set(array(
                    'timeSlots' => $timeSlots,
                    '_serialize' => array('timeSlots')
                ));
            }
        } else {
           throw new MethodNotAllowedException();
        }
    }

    public function listNextRound($locationId = null) {
        if($this->request->is('get')) {
            if (!is_numeric($locationId)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid id.',
                    '_serialize' => array('message')
                ));
            } else {
            	// destroying associations on the fly
                $this->TimeSlot->unbindModel(
                    array('belongsTo' => array('Location'))
                );

                $timeSlots = $this->TimeSlot->find('all', array(
        			'conditions' => array('TimeSlot.location_id' => $locationId)
    			));

                if (!$timeSlots) {
                    throw new NotFoundException(__('No time slots of the location with ID: '.$locationId. ' Found'));
                }

                $timeSlots = $this->TimeSlot->determineNextRound($timeSlots);

                $this->set(array(
                    'timeSlots' => $timeSlots,
                    '_serialize' => array('timeSlots')
                ));
            }
        } else {
           throw new MethodNotAllowedException();
        }
    }

    public function updateTimeSlot($id = null) {
        // waiting to be finished
        $this->request->onlyAllow('put');

        $data = $this->request->input('json_decode', true);

        // Read the instance into the model
        $this->TimeSlot->read(null, $id);
        if($this->TimeSlot->getNumRows() < 1) {
            throw new NotFoundException(__('TimeSlot with ID: '.$id. ' Not Found'));
        }
        
        // Modified should not be kept, drop it
        unset($data['TimeSlot']['modified']);
        $data['TimeSlot']['modified'] = date("Y-m-d H:i:s");

        $this->TimeSlot->set($data);

        if ($this->TimeSlot->save()) {
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
