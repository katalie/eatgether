<?php
class OrderDetail extends AppModel {
	public $name = 'OrderDetail';

	public $belongsTo = array(
        'Product' => array(
            'className' => 'Product',
            'foreignKey' => 'product_id'
        )
    );
}
