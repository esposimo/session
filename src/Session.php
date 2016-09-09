<?php

namespace smn\pheeca;

use \smn\pheeca\Session\FilesHandler;
use \smn\pheeca\Session\Exception;
use \ReflectionClass;
use \smn\pheeca\override\kernel\Events;

class Session {
    
    
    protected $_sessionHandler;
    protected $_dataManipulation;
    protected $_options = array();
    
    
    protected static $_instance;

    /**
     * Inizializza la gestione delle sessioni
     * 
     * @param Object $handler è la classe che si occupa di gestire la sessione e deve implementare l'interfaccia SessionInterface
     * @param Object $storageManager è la classe che si occupa di gestire i dati (ad es. crittografia)
     * @param Array $options
     */
    public static function start($handler = null, $dataManipulation = null, $options = array()) {
        self::$_instance = new self($handler, $dataManipulation, $options);
        return self::$_instance;
    }
    
    public static function getSessionInstance() {
        return self::$_instance;
    }

    public function __construct($handler = null, $dataManipulation = null, $options = array()) {
        $this->_options = $options;
        $this->setHandlerClass($handler);
        $this->setDataManipulationClass($dataManipulation);
        session_set_save_handler(
                array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc')
        );
        
        if (array_key_exists('handler', $options)) {
            $this->getHandlerClass()->setOptions($options['handler']);
        }
        
        if (array_key_exists('session_expire', $options)) {
            session_set_cookie_params($options['session_expire']);
        }
        
        if (array_key_exists('session_gc_lifetime', $options)) {
            ini_set('session.gc_maxlifetime', $options['session_gc_lifetime']);
        }
        
        if (array_key_exists('session_gc_probability', $options)) {
            ini_set('session.gc_probability', $options['session_gc_probability']);
        }
        if (array_key_exists('session_gc_divisor', $options)) {
            ini_set('session.gc_divisor', $options['session_gc_divisor']);
        }
        
       
        register_shutdown_function('session_write_close');
        session_start();
    }

    // il SessionHandler da solo è capache di storicizzare i dati a seocnda dell'handler
    // è fondamentale però passargli i parametri di connessione come opzione di session.save_path
    // 
    // per le verioni di PHP < 5.4 , gli handler non vanno creati a mano
    // 
    // in definitiva , per le versioni di PHP < 5.4 serve soltanto creare 
    // 

    /**
     * Configura la classe che gestisce dove salvaere e dove reperire i dati di sessione
     * @param type $handler
     * @return type
     * @throws Exception
     */
    public function setHandlerClass($handler) {
        if (is_null($handler)) {
            $handler = new FilesHandler();
        }
        $reflection = new ReflectionClass($handler);
        if (!preg_grep('/(.*)\x5c?SessionInterface/', $reflection->getInterfaceNames())) {
            throw new Exception('L\'handler deve implementare l\'interfaccia SessionInterface');
        }
        if (array_key_exists('handler', $this->_options)) {
            $handler->setOptions($this->_options['handler']);
        }

        $this->_sessionHandler = $handler;
    }

    /**
     * 
     * @return \smn\pheeca\Session\SessionInterface
     */
    public function getHandlerClass() {
        return $this->_sessionHandler;
    }

    /**
     * Configura la classe che gestisce la manipolazione dei dati
     * @param type $dataManipulation
     * @return type
     * @throws Exception
     */
    public function setDataManipulationClass($dataManipulation) {
        if (is_null($dataManipulation)) {
            $dataManipulation = new DefaultStorageManager();
        }
        $reflection = new ReflectionClass($dataManipulation);
        if (!preg_grep('/(.*)\x5c?DataManipulationInterface/', $reflection->getInterfaceNames())) {
            throw new Exception('Lo storage deve implementare l\'interfaccia StorageManager');
        }
        if (array_key_exists('manipulation', $this->_options)) {
            $dataManipulation->setOptions($this->_options['manipulation']);
        }
        $this->_dataManipulation = $dataManipulation;
    }

    /**
     * 
     * @return \smn\pheeca\Session\StorageInterface
     */
    public function getDataManipulationClass() {
        return $this->_dataManipulation;
    }

    public function close() {
        return $this->getHandlerClass()->close();
    }

    public function create_sid() {
        return $this->getHandlerClass()->create_sid();
    }

    public function destroy($session_id) {
        return $this->getHandlerClass()->destroy($session_id);
    }

    public function gc($maxlifetime) {
        return $this->getHandlerClass()->gc($maxlifetime);
    }

    /**
     * Inizia la sessione
     * @param type $save_path Contiene il dato relativo a session.save_path. Se questo non è configurato, viene passata una stringa vuota
     * @param type $session_name Il nome del cookie di sessione dove è memorizzato l'id di sessione lato server
     * @return type
     */
    public function open($save_path, $session_name) {
        return $this->getHandlerClass()->open($save_path, $session_name);
    }

    /**
     * Con la read prima prelevo i dati con l'handler, poi li passo al DataManipulation che restituisce il dato corretto
     * @param type $session_id String
     */
    public function read($session_id) {
        $data = $this->getHandlerClass()->read($session_id);
        $return = $this->getDataManipulationClass()->retrieve($data);
        return $return;
    }

    /**
     * La write prima passa il dato al DataManipulation (che può trasformarlo) e poi passa il dato trasformato all'handler che lo salva
     * @param type $session_id
     * @param type $session_data
     * @return type
     */
    public function write($session_id, $session_data) {
        $data = $this->getDataManipulationClass()->storage($session_id, $session_data);
//        $this->getHandlerClass()->write($session_id, $data);
        return $this->getHandlerClass()->write($session_id, $data);
        //return ($data) ? true : false;
//        parent::write($session_id, $session_data);
    }

//    private function unserialize_php($session_data) {
//        $return_data = array();
//        $offset = 0;
//        while ($offset < strlen($session_data)) {
//            if (!strstr(substr($session_data, $offset), "|")) {
//                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
//            }
//            $pos = strpos($session_data, "|", $offset);
//            $num = $pos - $offset;
//            $varname = substr($session_data, $offset, $num);
//            $offset += $num + 1;
//            $data = unserialize(substr($session_data, $offset));
//            $return_data[$varname] = $data;
//            $offset += strlen(serialize($data));
//        }
//        return $return_data;
//    }
//
//    private function unserialize_phpbinary($session_data) {
//        $return_data = array();
//        $offset = 0;
//        while ($offset < strlen($session_data)) {
//            $num = ord($session_data[$offset]);
//            $offset += 1;
//            $varname = substr($session_data, $offset, $num);
//            $offset += $num;
//            $data = unserialize(substr($session_data, $offset));
//            $return_data[$varname] = $data;
//            $offset += strlen(serialize($data));
//        }
//        return $return_data;
//    }

}
