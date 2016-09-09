<?php

namespace smn\pheeca\Session\DataManipulation;

use \smn\pheeca\Crypto;
use \smn\pheeca\Session\DataManipulationInterface;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CryptoStorageManager
 *
 * @author Simone Esposito
 */
class CryptoStorageManager implements DataManipulationInterface {

    
    /**
     *
     * @var type \smn\pheeca\Crypto
     */
    protected $_cryptoClass;
    
    public function retrieve($data) {
        return $this->_cryptoClass->decrypt(base64_decode($data));
    }

    public function storage($session_id, $data) {
        return base64_encode($this->_cryptoClass->crypt($data));
    }

    public function setOptions($options = array()) {
        $this->_cryptoClass = new Crypto($options['secure_key'], $options['iv']);
    }

}
