<?php
namespace smn\pheeca\Session\DataManipulation;

use \smn\pheeca\Session\DataManipulationInterface;
/**
 * Description of DefaultStorageManager
 *
 * @author Simone Esposito
 */
class DefaultManipulation implements DataManipulationInterface {

    public function retrieve($data) {
        return $data;
    }

    public function storage($session_id, $data) {
        return $data;
    }

    public function setOptions($options = array()) {
        return true;
    }
    
}
