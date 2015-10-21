<?php
namespace dadasign\FtpImplictSsl;

/**
 * FTP with Implicit SSL/TLS Class
 *
 * Simple wrapper for cURL functions to use FTP with implicit SSL/TLS
 *
 * @category    Class
 * @author      Max Rice / Damith Jayasinghe
 * @since       1.0
 */
class FtpImplictSsl {
    /** @var string cURL URL for upload */
    private $url;
    /** @var CurlWrapper */
    private $curlWrapper;

    /**
     * Connect to FTP server over Implicit SSL/TLS
     *
     *
     * @access public
     * @param string $username
     * @param string $password
     * @param string $server
     * @param int $port
     * @param string $initial_path
     * @param bool $passive_mode
     * @throws Exception - blank username / server / port
     * @return \FTP_Implicit_SSL
     */
    public function __construct($username, $password, $server, $port = 990, $initial_path = '', $passive_mode = false) {
        
        if (!$username){
            throw new \Exception('FTP Username is blank.');
        }
        if (!$server){
            throw new \Exception('FTP Server is blank.');
        }
        if (!$port){
            throw new \Exception('FTP Port is blank.', WC_XML_Suite::$text_domain);
        }
        $this->curlWrapper = new CurlWrapper($username, $password, $port, $passive_mode);
        // set host/initial path
        $this->url = "ftps://{$server}/{$initial_path}";
    }
    

    /**
     * Write file into temporary memory and upload stream to remote file
     *
     * @access public
     * @param string $file_name - remote file name to create
     * @param string $file - path to local file to upload
     * @throws Exception - Open remote file failure or write data failure
     */
    public function upload($file_name, $file) {
        $ch = $this->curlWrapper->getCurlHandle();
        // set file name
        curl_setopt($ch, CURLOPT_URL, $this->url . $file_name);
        // set the file to be uploaded
        $fp = fopen($file, "r");
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
        // upload file
        if (!curl_exec($ch)){
            throw new \Exception(sprintf('Could not upload file. cURL Error: [%s] - %s', curl_errno($ch), curl_error($ch)));
        }
    }
    /**
     * List file details.
     * @return array
     */
    public function rawList(){
        return $this->getList(false);
    }
    /**
     * List file names.
     * @return array
     */
    public function nList() {
        return $this->getList(true);
    }
    /**
     * @return array List of file names
     * @throws Exception
     */
    private function getList($namesOnly){
        $ch = $this->curlWrapper->getCurlHandle();
        if (!curl_setopt($ch, CURLOPT_URL, $this->url)){
            throw new \Exception("Could not set cURL directory: $this->url");
        }

        curl_setopt($ch, CURLOPT_UPLOAD, false);
        if($namesOnly){
            curl_setopt($ch, CURLOPT_FTPLISTONLY, 1);
        }
        
        $result = curl_exec($ch);

        if($result===false){
            throw new \Exception("Listing files failed: ".curl_error($ch));
        }
        $files = explode("\n", trim($result));
        if (count($files)) {
            return $files;
        } else {
            return array();
        }
    }

    /**
     * Download file from FTPS default directory
     *
     * @param string $localFileName File path to local file name.
     * @param string $remoteFileName Path to remote file.
     * @return string
     */
    public function get($localFileName, $remoteFileName) {
        $file = fopen($localFileName, "w");
        $ch = $this->curlWrapper->getCurlHandle();
        curl_setopt($ch, CURLOPT_URL, $this->url . $remoteFileName);
        curl_setopt($ch, CURLOPT_UPLOAD, false);
        curl_setopt($ch, CURLOPT_FILE, $file);

        $result = curl_exec($ch);
        fclose($file);

        if (strlen($result)) {
            return $result;
        } else {
            return "";
        }
    }

    public function remoteFileSize($file_name) {
        $ch = $this->curlWrapper->getCurlHandle();
        curl_setopt($ch, CURLOPT_URL, $this->url . $file_name);
        curl_setopt($ch, CURLOPT_UPLOAD, false);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);

        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        return $size;
    }

    /**
     * Delete remote file.
     * @param string $file_name
     * @return boolean|string Returns false on failure or full file path on success.
     */
    public function delete($file_name) {
        $ch = $this->curlWrapper->getCurlHandle();
        curl_setopt($ch, CURLOPT_URL, $this->url . $file_name);
        curl_setopt($ch, CURLOPT_UPLOAD, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_QUOTE, array('DELE ' . $file_name));
        $result = curl_exec($ch);
        if($result===false){
            throw new \Exception("Error deleting: ".curl_error($ch));
        }
        $files = explode("\n", trim($result));

        if (!in_array($file_name, $files)) {
            return $this->url . $file_name;
        } else {
            return false;
        }
    }
}
