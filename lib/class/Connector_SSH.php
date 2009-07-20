<?php

/**
 * SSH connection class
 */
include_once(path('/lib/class/Connector.php'));

class Connector_SSH extends Connector
{
    var $_config_sectionname = 'ssh';
    var $_working_dir = '';
    var $conn; // connection resource
    var $sftp_resource = null;  //SFTP resource
    
    function Connector_SSH()
    {
        if (!extension_loaded('ssh2')) {
            $prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
            $r = dl($prefix . 'ssh2.' . PHP_SHLIB_SUFFIX);
            if (!$r) {
                $this->trigger_error("Connector_SSH() Couldn't load ssh2 module\n check \"php.ini\"");
            }
        }
        parent::Connector();
    }
    
    function connect()
    {
        $port = $this->config['ssh_port'] ? $this->config['ssh_port'] : '22';
        $this->conn = ssh2_connect(srcds_server(false), $port);
        if (!$this->conn) {
            $this->trigger_error('SSH connection has failed');
            return;
        }
        switch (strtolower($this->config['ssh_auth_type'])) {
        case 'pubkey':
        case 'pubkey_file':
            if ($this->config['passphrase']) {
                $r = ssh2_auth_pubkey_file($this->conn, $this->config['ssh_username'],
                                             $this->config['ssh_pubkeyfile'],
                                               $this->config['ssh_privkeyfile'],
                                                 $this->config['passphrase']
                                          );
            } else {
                $r = ssh2_auth_pubkey_file($this->conn, $this->config['ssh_username'],
                                             $this->config['ssh_pubkeyfile'],
                                               $this->config['ssh_privkeyfile']
                                          );
            }
            break;
        case 'hostbase':
        case 'hostbased':
        case 'hostbased_file':
            $passphrase = $this->config['passphrase'];
            if ($this->config['local_username']) {
                $r = ssh2_auth_hostbased_file($this->conn, $this->config['ssh_username'],
                                                $this->config['hostname'],
                                                  $this->config['ssh_pubkeyfile'],
                                                    $this->config['ssh_privkeyfile'],
                                                      $passphrase,
                                                        $this->config['local_username']
                                             );
            } else {
                $r = ssh2_auth_hostbased_file($this->conn, $this->config['ssh_username'],
                                                $this->config['hostname'],
                                                  $this->config['ssh_pubkeyfile'],
                                                    $this->config['ssh_privkeyfile'],
                                                      $passphrase
                                             );
            }
            break;
        case 'none':
            $r = ssh2_auth_none($this->conn, $this->config['ssh_username']);
            break;
        case 'password':
        default:
            $r = ssh2_auth_password($this->conn, $this->config['ssh_username'], $this->config['ssh_password']);
            break;
        }
        if (!$r) {
            $this->trigger_error('SSH authentication has failed. method: ' . $this->config['ssh_auth_type']);
        }
    }
    
    function get($filename, $local_dir = '')
    {
        $remote_filename = $this->_build_path($filename);
        if (!$local_dir) {
            $local_dir = tmp_dir();
        }
        $local_filename = $local_dir . '/' . $filename;
        $r = ssh2_scp_recv($this->conn, $remote_filename, $local_filename);
        if (!$r) {
            $emsg = sprintf('get() failed. remotefile:%s, localfile:%s', $remote_filename, $local_filename);
            $this->trigger_error($emsg);
        }
        return $local_filename;
    }
    
    function move($remote_filename, $local_dir = '')
    {
        $local_filename = $this->get($remote_filename, $local_dir);
        $this->delete($remote_filename);
        return $local_filename;
    }
    
    function put()
    {
    }
    
    function read($filename)
    {
        $local_file = $this->get($filename);
        return file_exists($local_file) ? file_get_contents($local_file) : '';
    }
    
    function delete($filename)
    {
        $filename = $this->_build_path($filename);
        $r = ssh2_sftp_unlink($this->_sftp(), $filename);
        if (!$r) {
            $this->trigger_error("ssh2_sftp_unlink() Couldn't delete file [ $filename ] ");
        }
        return $r;
    }
    
    function chdir($dir)
    {
        $this->_working_dir = $dir;
    }
    
    function _build_path($filename)
    {
        return $this->_working_dir . '/' . $filename;
    }
    
    function _sftp()
    {
        if (is_null($this->sftp_resource)) {
            $this->sftp_resource = ssh2_sftp($this->conn);
        }
        return $this->sftp_resource;
    }
}

