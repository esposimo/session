<?php

namespace smn\pheeca\Session\Handler;

use \smn\pheeca\Session\SessionInterface;
use \smn\pheeca\Cache\MemCacheServer;
use \smn\pheeca\Logger;

/**
 * Description of MemcacheHandler
 *
 * @author Simone Esposito
 */
class MemcacheHandler implements SessionInterface {

    /**
     *
     * @var type \smn\pheeca\Cache\MemCacheServer
     */
    public $_memcache_server;
    protected $_session_expire = 0;
    protected $_cacheTypeSession = 'session';
    protected $_cacheTypeSessionIndex = 'global_session';
    protected $_cacheTypeSessionExpire = 'session_expire';

    public function close() {
        
    }

    public function destroy($session_id) {
        //Logger::debug('Cancello la sessione ' . $session_id . ' dal memcache server', RENDER_CORE_LOGNAME);
        $this->_memcache_server->delete($session_id, $this->_cacheTypeSession);
    }

    public function gc($maxlifetime) {

        // mi prendo la lista di sessioni
        if (!$this->_memcache_server->isCached('global_session', $this->_cacheTypeSessionIndex)) {
            //Logger::debug('Non erano presenti global session', RENDER_CORE_LOGNAME);
            $sessions = array();
        } else {
            //Logger::debug('Ho prelevato le global session', RENDER_CORE_LOGNAME);
            $data = $this->_memcache_server->get('global_session', $this->_cacheTypeSessionIndex);
            $sessions = json_decode($data);
        }

        //Logger::debug('Ci sono ' . count($sessions) . ' sessioni nell\'indice', RENDER_CORE_LOGNAME);

        $restoreSession = array();
        foreach ($sessions as $session) {
            // per ogni sessione mi vedo il timestamp e lo confronto con $maxlifetime
            // $session è il $session_id
            $stored_data = $this->_memcache_server->get($session, $this->_cacheTypeSessionExpire);
            if (($stored_data + $maxlifetime) < time()) {
                // cancello la sessione dal server
                //Logger::debug('Cancello la sessione dal server perchè ' . date('d-m-Y H:i:s', ($stored_data + $maxlifetime)) . ' < ' . date('d-m-Y H:i:s', time()), RENDER_CORE_LOGNAME);
                if (!$this->_memcache_server->delete($session, $this->_cacheTypeSessionExpire)) {
                    //Logger::debug('Non sono riuscito a cancellare la sessione da session_expire', RENDER_CORE_LOGNAME);
                }
                if (!$this->_memcache_server->delete($session, $this->_cacheTypeSession)) {
                    //Logger::debug('Non sono riuscito a cancellare la sessione', RENDER_CORE_LOGNAME);
                }
            } else {
                //Logger::debug('Ho aggiunto una sessione perchè non scaduta', RENDER_CORE_LOGNAME);
                $restoreSession[] = $session;
            }
        }
        if ($this->_memcache_server->update('global_session', json_encode($restoreSession), $this->_cacheTypeSessionIndex)) {
            //Logger::debug('Indice sessioni aggiornato con successo', RENDER_CORE_LOGNAME);
        }
    }

    public function open($save_path, $name) {
        
    }

    public function read($session_id) {
        //Logger::debug('Leggo la sessione dal server memcache', RENDER_CORE_LOGNAME);
        return $this->_memcache_server->get($session_id, $this->_cacheTypeSession);
    }

    public function write($session_id, $session_data) {
        // mi setto l'expire della sessione in cache
//        Logger::debug('Scrivo la data di scadenza sul server memcache pari a ' . date('d-m-Y H:i:s', time()) . ' per la sessione ' . $session_id, RENDER_CORE_LOGNAME);
        $this->_memcache_server->set($session_id, time(), $this->_cacheTypeSessionExpire, 0);

        // aggiungo la sessione nella lista di sessioni attive
//        Logger::debug('Prendo l\'indice delle sessioni', RENDER_CORE_LOGNAME);
        $data = $this->_memcache_server->get('global_session', $this->_cacheTypeSessionIndex);
        if (count(json_decode($data)) == 0) {
//            Logger::debug('Indice sessioni trovato vuoto', RENDER_CORE_LOGNAME);
            $sessions = array();
        } else {
            $sessions = json_decode($data);
        }
        // add
//        Logger::debug('Aggiungo sessione all\'indice', RENDER_CORE_LOGNAME);
        if (array_search($session_id, $sessions) === false) {
            $sessions[] = $session_id;
//            Logger::debug('La sessione era nuova e l\'ho aggiunta', RENDER_CORE_LOGNAME);
        }

        // json encode
//        Logger::debug('Codifico in json l\'indice', RENDER_CORE_LOGNAME);
        $json = json_encode($sessions);
        // storicizzo

//        Logger::debug('Storicizzo i dati e l\'indice sul server memcache ' . print_r($json, true), RENDER_CORE_LOGNAME);
        $this->_memcache_server->set('global_session', $json, $this->_cacheTypeSessionIndex, 0);

        // salvo i dati di sessione
//        Logger::debug('Storicizzo la sessione ' . $session_data, RENDER_CORE_LOGNAME);
        return $this->_memcache_server->set($session_id, $session_data, $this->_cacheTypeSession, 0);
    }

    public function create_sid() {
//        echo 'create_sid()<br>';
    }

    public function setOptions($options = array()) {
        if (array_key_exists('server', $options)) {
            $this->_memcache_server = new MemCacheServer(array($options['server']));
        } else {
            $this->_memcache_server = new MemCacheServer(array());
        }
        if (array_key_exists('session_expire', $options)) {
            $this->_session_expire = $options['session_expire'];
        }
    }

}
