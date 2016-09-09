<?php
namespace smn\pheeca\Session;

/**
 *
 * @author Simone Esposito
 */
interface SessionInterface {

    public function close();

    public function destroy($session_id);

    public function gc($maxlifetime);

    public function open($save_path, $name);

    public function read($session_id);

    public function write($session_id, $session_data);
    
    public function create_sid();
    
    public function setOptions($options = array());
}
