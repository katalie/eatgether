<?php
class TimeSlot extends AppModel {
	public $name = 'TimeSlot';

	public $belongsTo = array(
        'Restaurant' => array(
            'className' => 'Restaurant',
            'foreignKey' => 'restaurant_id',
            'dependent' => true
        ),
        'Location' => array(
            'className' => 'Location',
            'foreignKey' => 'location_id',
            'dependent' => true
        )
    );

    // 'next_round' is an integral indicator regarding the next meal available time slot:
    // 0 - today noon; 1 - today evening; 2 - tomorrow noon; 3 - tomorrow evening
    public function determineNextRound($timeSlots = null) {
        foreach ($timeSlots as $key => $value) {
            $now = date('H:i:s');
            // this restaurant has both noon and evening delivering service
            if(isset($value['TimeSlot']['lunch_due']) && isset($value['TimeSlot']['dinner_due'])) {
                $lunchDue = date('H:i:s', strtotime($value['TimeSlot']['lunch_due']));
                $dinnerDue = date('H:i:s', strtotime($value['TimeSlot']['dinner_due']));
                $lunchDelivery = date('H:i:s', strtotime($value['TimeSlot']['lunch_delivery']));
                $dinnerDelivery = date('H:i:s', strtotime($value['TimeSlot']['dinner_delivery']));

                if($now < $lunchDue) {
                    $timeSlots[$key]['next_round'] = 0;
                    $timeSlots[$key]['next_round_time'] = date('Y-m-d').' '.$lunchDue;
                    $timeSlots[$key]['next_round_delivery_time'] = date('Y-m-d').' '.$lunchDelivery;
                } else if ($now > $lunchDue && $now < $dinnerDue) {
                    $timeSlots[$key]['next_round'] = 1;
                    $timeSlots[$key]['next_round_time'] = date('Y-m-d').' '.$dinnerDue;
                    $timeSlots[$key]['next_round_delivery_time'] = date('Y-m-d').' '.$dinnerDelivery;
                } else {
                    $timeSlots[$key]['next_round'] = 2;
                    $timeSlots[$key]['next_round_time'] = date('Y-m-d', strtotime('+1 day')).' '.$lunchDue;
                    $timeSlots[$key]['next_round_delivery_time'] = date('Y-m-d', strtotime('+1 day')).' '.$lunchDelivery;
                }
            } else if(isset($value['TimeSlot']['lunch_due']) && !isset($value['TimeSlot']['dinner_due'])) {
                $lunchDue = date('H:i:s', strtotime($value['TimeSlot']['lunch_due']));
                $lunchDelivery = date('H:i:s', strtotime($value['TimeSlot']['lunch_delivery']));

                if($now < $lunchDue) {
                    $timeSlots[$key]['next_round'] = 0;
                    $timeSlots[$key]['next_round_time'] = date('Y-m-d').' '.$lunchDue;
                    $timeSlots[$key]['next_round_delivery_time'] = date('Y-m-d').' '.$lunchDelivery;
                } else {
                    $timeSlots[$key]['next_round'] = 2;
                    $timeSlots[$key]['next_round_time'] = date('Y-m-d', strtotime('+1 day')).' '.$lunchDue;
                    $timeSlots[$key]['next_round_delivery_time'] = date('Y-m-d', strtotime('+1 day')).' '.$lunchDelivery;
                }
            } else if(!isset($value['TimeSlot']['lunch_due']) && isset($value['TimeSlot']['dinner_due'])) {
                $dinnerDue = date('H:i:s', strtotime($value['TimeSlot']['dinner_due']));
                $dinnerDelivery = date('H:i:s', strtotime($value['TimeSlot']['dinner_delivery']));

                if($now < $dinnerDue) {
                    $timeSlots[$key]['next_round'] = 1;
                    $timeSlots[$key]['next_round_time'] = date('Y-m-d').' '.$dinnerDue;
                    $timeSlots[$key]['next_round_delivery_time'] = date('Y-m-d').' '.$dinnerDelivery;
                } else {
                    $timeSlots[$key]['next_round'] = 3;
                    $timeSlots[$key]['next_round_time'] = date('Y-m-d', strtotime('+1 day')).' '.$dinnerDue;
                    $timeSlots[$key]['next_round_delivery_time'] = date('Y-m-d', strtotime('+1 day')).' '.$dinnerDelivery;
                }
            } else {
                // if database integration is violated or there is no restaurant open
                unset($timeSlots[$key]);
            }
        }

        return $timeSlots;
    }
}
