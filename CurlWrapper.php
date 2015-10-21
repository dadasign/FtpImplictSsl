<?php
namespace dadasign\FtpImplictSsl;

/**
 * CurlWrapper prepares and maintains a curl resouce with some preset values.
 *
 * @author Jakub
 */
class CurlWrapper {
    /** @var bool */
    private $passive_mode;
    /** @var int */
    private $port;
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var resource cURL resource handle */
    private $curl_handle;
    
    public function __construct($username, $password, $port, $passive_mode) {
        $this->username = $username;
        $this->password = $password;
        $this->passive_mode = $passive_mode;
        $this->port = $port;
    }
    /**
     * Get a curl handle with the default configuration.
     * @return resource
     * @throws \Exception
     */
    public function getCurlHandle(){
        if(!is_resource($this->curl_handle)){
            $this->curl_handle = curl_init();
            if (!$this->curl_handle){
                throw new \Exception('Could not initialize cURL.');
            }
        }
        $this->assignCurlSettings();
        return $this->curl_handle;
    }
    /**
     * Assign a default configuration.
     * @throws \Exception
     */
    private function assignCurlSettings(){
        // connection options
        $options = array(
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_SSL_VERIFYPEER => false, // don't verify SSL
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FTP_SSL => CURLFTPSSL_ALL, // require SSL For both control and data connections
            CURLOPT_FTPSSLAUTH => CURLFTPAUTH_DEFAULT, // let cURL choose the FTP authentication method (either SSL or TLS)
            CURLOPT_UPLOAD => true,
            CURLOPT_PORT => $this->port,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true
        );
        // cURL FTP enables passive mode by default, so disable it by enabling the PORT command and allowing cURL to select the IP address for the data connection
        if (!$this->passive_mode){
            $options[CURLOPT_FTPPORT] = '-';
        }
        //Make sure previous state does not affect returned settings. Optional fallback for PHP < 5.5.
        if(function_exists('curl_reset')){
            curl_reset($this->curl_handle);
        }else{
            $options[CURLOPT_URL] = null;
            $options[CURLOPT_INFILESIZE] = 0;
            $options[CURLOPT_FTPLISTONLY] = false;
            $options[CURLOPT_UPLOAD] = false;
            $options[CURLOPT_HEADER] = false;
            $options[CURLOPT_NOBODY] = false;
        }
        // set connection options, use foreach so useful errors can be caught instead of a generic "cannot set options" error with curl_setopt_array()
        foreach ($options as $option_name => $option_value) {
            if (!curl_setopt($this->curl_handle, $option_name, $option_value)){
                throw new \Exception(sprintf('Could not set cURL option: %s', $option_name));
            }
        }
    }
    /**
     * Attempt to close cURL handle
     * Note - errors suppressed here as they are not useful
     *
     * @access public
     */
    public function __destruct() {
        if(is_resource($this->curl_handle)){
            @curl_close($this->curl_handle);
        }
    }
}
