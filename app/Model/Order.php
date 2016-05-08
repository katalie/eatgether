<?php
class Order extends AppModel {
	public $name = 'Order';

	public $hasMany = array(
        'OrderDetail' => array(
            'className' => 'OrderDetail',
            'foreignKey' => 'order_id',
            'order' => 'OrderDetail.created DESC',
            'dependent' => true
        )
    );

    public function determineCurrentRoundOrderTime($timeSlots = null, $startTime = null, $endTime = null) {
        // foreach ($timeSlots as $key => $value) {
        //     $now = date('H:i:s', strtotime('+1 hour'));
        //     // this restaurant has both noon and evening delivering service
        //     if(isset($value['TimeSlot']['lunch_due']) && isset($value['TimeSlot']['dinner_due'])) {
        //         $lunchDue = date('H:i:s', strtotime($value['TimeSlot']['lunch_due']));
        //         $dinnerDue = date('H:i:s', strtotime($value['TimeSlot']['dinner_due']));

        //         if($now < $lunchDue) {
        //             $startTime = date('Y-m-d', strtotime('-1 day')).' '.$lunchDue;
        //             $endTime = date('Y-m-d', strtotime('-1 day')).' '.$dinnerDue;
        //         } else if ($now > $lunchDue && $now < $dinnerDue) {
        //             $startTime = date('Y-m-d', strtotime('-1 day')).' '.$dinnerDue;
        //             $endTime = date('Y-m-d').' '.$lunchDue;
        //         } else {
        //             $startTime = date('Y-m-d').' '.$lunchDue;
        //             $endTime = date('Y-m-d').' '.$dinnerDue;
        //         }
        //     } else if(isset($value['TimeSlot']['lunch_due']) && !isset($value['TimeSlot']['dinner_due'])) {
        //         $lunchDue = date('H:i:s', strtotime($value['TimeSlot']['lunch_due']));

        //         if($now < $lunchDue) {
        //             $startTime = date('Y-m-d', strtotime('-2 day')).' '.$lunchDue;
        //             $endTime = date('Y-m-d', strtotime('-1 day')).' '.$lunchDue;
        //         } else {
        //             $startTime = date('Y-m-d', strtotime('-1 day')).' '.$lunchDue;
        //             $endTime = date('Y-m-d').' '.$lunchDue;
        //         }
        //     } else if(!isset($value['TimeSlot']['lunch_due']) && isset($value['TimeSlot']['dinner_due'])) {
        //         $dinnerDue = date('H:i:s', strtotime($value['TimeSlot']['dinner_due']));

        //         if($now < $dinnerDue) {
        //             $startTime = date('Y-m-d', strtotime('-2 day')).' '.$dinnerDue;
        //             $endTime = date('Y-m-d', strtotime('-1 day')).' '.$dinnerDue;
        //         } else {
        //             $startTime = date('Y-m-d', strtotime('-1 day')).' '.$dinnerDue;
        //             $endTime = date('Y-m-d').' '.$dinnerDue;
        //         }
        //     } else {
        //         // not likely to happen unless database integration is violated
        //         unset($timeSlots[$key]);
        //     }
        // }

        $now = date('H:i:s', strtotime('+1 hour'));
        $lunchDue = date('H:i:s', strtotime('2015-10-08 11:30:00'));

        if($now < $lunchDue) {
            $startTime = date('Y-m-d', strtotime('-2 day')).' '.$lunchDue;
            $endTime = date('Y-m-d', strtotime('-1 day')).' '.$lunchDue;
        } else {
            $startTime = date('Y-m-d', strtotime('-1 day')).' '.$lunchDue;
            $endTime = date('Y-m-d').' '.$lunchDue;
        }

        $orderTime = array(
	    	"startTime"    => $startTime,
	    	"endTime"    => $endTime
		);

        return $orderTime;
    }
}
