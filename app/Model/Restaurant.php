<?php
class Restaurant extends AppModel {
	public $name = 'Restaurant';

	public $hasMany = array(
        'TimeSlot' => array(
            'className' => 'TimeSlot',
            'foreignKey' => 'restaurant_id',
            'dependent' => true
        ),
        'Product' => array(
            'className' => 'Product',
            'foreignKey' => 'restaurant_id',
            'order' => 'Product.created DESC',
            'dependent' => true
        )
    );
}
