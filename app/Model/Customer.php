<?php
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
App::uses('CakeEmail', 'Network/Email');

class Customer extends AppModel {
	public $name = 'Customer';

	public $validate = array(
        // 'username' => array(
        //     'alphaNumeric' => array(
        //         'rule' => 'alphaNumeric',
        //         'message' => 'Letters and numbers only'       
        //     ),
        //     'between' => array(
        //         'rule' => array('between', 5, 15),
        //         'message' => 'Between 5 to 15 characters'
        //     )
        // ),
        'password' => array(
            'rule' => array('minLength', '8'),
            'required' => true,
            'allowEmpty' => false,
            'message' => 'Minimum 8 characters long'
        ),
        'name' => array(
            'rule' => 'alphaNumeric',
            'allowEmpty' => true,
            'message' => 'Should be a valid name'
        ),
        'email' => array(
            'rule' => 'email',
            'required' => true,
            'allowEmpty' => false,
            'message' => 'Should be a valid email'
        ),
        'postal_code' => array(
            // This rule allows lower case letters
            'rule' => array('postal', null, 'ca'),
            'allowEmpty' => true,
            'message' => 'Should be a valid Canadian postal code (e.g. K1V 8X4)'
        ),
        'telephone' => array(
            'telephone' => array(
                'rule' => array('minLength', '12'),
                'required' => true,
                'allowEmpty' => false,
                'message' => 'Telephone format error'
            ),
            'isUnique' => array(
                'rule' => 'isUnique',
                'message' => 'This telephone has already been taken.'
            )
        )
    );

    public function beforeSave($options = array()) {
        if(!empty($this->data[$this->alias]['password']) && strlen($this->data[$this->alias]['password']) < 64) {
            $this->data[$this->alias]['password'] = $this->hashPassword($this->data[$this->alias]['password']);
        }

        if(!empty($this->data['Customer']['postal_code'])) {
            $this->data['Customer']['postal_code'] = strtoupper($this->data['Customer']['postal_code']);
        }
        return true;
    }

    public function hashPassword($password) {
        $passwordHasher = new SimplePasswordHasher(array('hashType' => 'sha256'));
        $password = $passwordHasher->hash($password);

        return $password;
    }

    public function generateEnableCode() {
        return str_replace('-', '', String::uuid());
    }

    public function generateCellEnableCode() {
        return rand(100000, 999999);
    }

    //http://45.55.164.17/eat/customers/enable/15/54b891fdc1004642a696031dd8b1ce9d
    public function sendEmail($id = null, $name = null, $enableCode = null, $email = null) {
        $url = 'http://45.55.164.174/eat/customers/enable/'.$id.'/'.$enableCode;

        $Email = new CakeEmail('gmail');
        $Email->template('activate');
        $Email->emailFormat('html');
        $Email->viewVars(array('name' => $name, 'url' => $url));

        $Email->from(array('info@eatogether.ca' => 'Ottawazine - No Reply'));
        $Email->to($email);
        $Email->subject('Eat Together - Account Activation');
        $Email->send();
    }

    // This customized validation function is used to validate US or Canadian postal code
    // public function postalCode($check) {

 //        $value = array_values($check);
 //        $value = $value[0];

 //        return preg_match('/(^\d{5}(-\d{4})?$)|(^[ABCEGHJKLMNPRSTVXY]{1}\d{1}[A-Z]{1} *\d{1}[A-Z]{1}\d{1}$)/', $value);
 //    }
}
