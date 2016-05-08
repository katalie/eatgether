<?php

class LocationsController extends AppController {
    public $components = array('RequestHandler');
    public function index() {
        if($this->request->is('get')) {
            $locations = $this->Location->find('threaded');
            if (!$locations) {
                throw new NotFoundException(__('No locations found'));
            }

            $this->set(array(
                'locations' => $locations,
                '_serialize' => array('locations')
            ));
        } else {
            throw new MethodNotAllowedException();
        }
    }

    // public function allLocationNames() {
    //     if($this->request->is('get')) {
    //         $allLocationNames = $this->Location->find('threaded', array(
    //             'fields' => array('id', 'parent_id', 'name')
    //         ));
    //         if (!$allLocationNames) {
    //             throw new NotFoundException(__('No locations found'));
    //         }

    //         $this->set(array(
    //             'allLocationNames' => $allLocationNames,
    //             '_serialize' => array('allLocationNames')
    //         ));
    //     } else {
    //         throw new MethodNotAllowedException();
    //     }
    // }

    public function view($id = null) {
        if($this->request->is('get')) {
            if (!is_numeric($id)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid location id.',
                    '_serialize' => array('message')
                ));
            } else {
                $location = $this->Location->findById($id);
                if (!$location) {
                    throw new NotFoundException(__('Location with ID: '.$id. ' Not Found'));
                }

                $this->set(array(
                    'location' => $location,
                    '_serialize' => array('location')
                ));
            }
        } else {
            throw new MethodNotAllowedException();
        }  
    }

    public function directChildren($id = null) {
        if($this->request->is('get')) {
            if (!is_numeric($id)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid location id.',
                    '_serialize' => array('message')
                ));
            } else {
                $directChildren = $this->Location->children($id, true);
                if (!$directChildren) {
                    throw new NotFoundException(__('Location with ID: '.$id. ' has no direct children'));
                }

                $this->set(array(
                    'directChildren' => $directChildren,
                    '_serialize' => array('directChildren')
                ));
            }
        } else {
            throw new MethodNotAllowedException();
        }
    }

    public function parent($id = null) {
        if($this->request->is('get')) {
            if (!is_numeric($id)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid location id.',
                    '_serialize' => array('message')
                ));
            } else {
                $parent = $this->Location->getParentNode($id);
                if (!$parent) {
                    throw new NotFoundException(__('Location with ID: '.$id. ' has no parent'));
                }

                $this->set(array(
                    'parent' => $parent,
                    '_serialize' => array('parent')
                ));
            }
        } else {
            throw new MethodNotAllowedException();
        }
    }

    public function path($id = null) {
        if($this->request->is('get')) {
            if (!is_numeric($id)) {
                $this->response->statusCode('403');
                $this->set(array(
                    'message' => 'Please provide a valid location id.',
                    '_serialize' => array('message')
                ));
            } else {
                $path = $this->Location->getPath($id);
                if (!$path) {
                    throw new NotFoundException(__('Location path with ID: '.$id. ' Not Found'));
                }

                $this->set(array(
                    'path' => $path,
                    '_serialize' => array('path')
                ));
            }
        } else {
            throw new MethodNotAllowedException();
        }
    }

    public function reconstruct() {
        $this->Location->recover();
        return $this->response;
    }
}