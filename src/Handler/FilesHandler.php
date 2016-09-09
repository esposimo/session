<?php

namespace smn\pheeca\Session\Handler;

use \smn\pheeca\Session\SessionInterface;

class FilesHandler implements SessionInterface {

    protected $_save_path = null;

    public function close() {
        return true;
    }

    public function destroy($session_id) {
        $file = $this->_save_path .'/session_' .$session_id;
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }

    public function gc($maxlifetime) {
        foreach(glob($this->_save_path .'/session_*') as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }
        return true;
    }

    public function open($save_path, $name) {
        if (($this->_save_path == null) || ($save_path == '')) {
            $save_path = sys_get_temp_dir();
        }
        $this->_save_path = $save_path;
        if (!is_dir($this->_save_path)) {
            mkdir($this->_save_path, 0777);
        }
        return true;
    }

    public function read($session_id) {
        if (file_exists($this->_save_path . '/session_' . $session_id)) {
            return (string)file_get_contents($this->_save_path . '/session_' . $session_id);
        }
        return false;
    }

    public function write($session_id, $session_data) {
        return (file_put_contents($this->_save_path . '/session_' . $session_id, $session_data) === false) ? false : true;
//        file_put_contents($this->_save_path . '/session_' . $session_id, $session_data);
//        return true;
    }

    public function create_sid() {
        return \session_id();
    }

    public function setOptions($options = array()) {
        if (array_key_exists('save_path', $options)) {
            $this->_save_path = $options['save_path'];
            ini_set('session.save_path', $this->_save_path);
        }
    }

}
