<?php
/**
 * Simple class to wrap the YQL API and cache the results
 * 
 * @package yqlstitcher
 */
class YQLStitcher {
  /**
   * @access static
   * @var string
   */
  public static $BASE_URL  = "http://query.yahooapis.com/v1/public/yql?q=";
  
  /**
   * @access public
   * @var string
   */
  public $CACHE_DIR;
  public $TIMEOUT;
  public $FEEDS;
  public $FORMAT;
  public $YQL_QUERY;
  public $LIMIT;
  public $UNIQUE_FIELD;
  public $RANDOMIZED;
  public $YQL_FEED;
  public $ERRORS;
  
  /**
   * Constructor
   * 
   * @param $cache string
   * @param $timeout integer
   * @param $feeds array
   * @return object
   */
  public function __construct($cache, $timeout=3600, $feeds, $format="xml", $query, $limit=10, $unique="channel.item.title", $random=false) {
    $this->CACHE_DIR  = $cache;
    $this->TIMEOUT    = $timeout;
    $this->FEEDS      = $feeds;
    $this->FORMAT     = "&format=$format";
    $this->YQL_QUERY  = $query;
    $this->LIMIT      = $limit;
    $this->YQL_FEED   = self::$BASE_URL . urlencode($this->YQL_QUERY . " where url in('") . implode("','", $this->FEEDS) . "')". urlencode(" limit " . $this->LIMIT . " | unique(field=\"channel.item.title\")") . $this->FORMAT;
    $this->RANDOMIZED = $random;
    $this->ERRORS     = array();
    
    if (!file_exists($this->CACHE_DIR)) {
      $new_cache = @mkdir($this->CACHE_DIR, 0755);
      if (!$new_cache) {
        $this->errors(
          "Unable to create cache folder at '" . $this->CACHE_DIR . "'. Please verify that you have the proper permissions and that the path exists."
        );
        return false;
      }
    }
  }
  
  /**
   * cache_file_name
   * 
   * @param $url string
   * @return string
   */
  protected function cache_file_name($url) {
    $hashed = md5($url);
    return join(DIRECTORY_SEPARATOR, array($this->CACHE_DIR, $hashed));
  }
  
  /**
   * serialize_items
   * 
   * @param $items array
   * @return string
   * TODO
   */
  protected function serialize_items($items) {
    return serialize($items);
  }
  
  /**
   * deserialize_items
   * 
   * @param $items string
   * @return array
   * TODO
   */
  protected function deserialize_items($items) {
    return unserialize($items);
  }
  
  /**
   * request_cache
   * 
   * @return array|boolean
   */
  protected function request_cache($url) {
    $destination = $this->cache_file_name($url);
    
    if((!file_exists($destination)) || (filemtime($destination) < ($_SERVER['REQUEST_TIME']-$this->TIMEOUT))) {
    	$data = file_get_contents($url);
    	
      if ($data === false) {
        $this->errors(
          "Data was empty."
        );
        return false;
      }
      
    	$tmpf = tempnam('/tmp', 'YQL');
    	$fp = fopen($tmpf, "w");
    	
    	fwrite($fp, $data);
    	fclose($fp);
    	
    	if (!copy($tmpf, $destination)) {
        $this->errors(
          "failed to copy source file to destination."
        );
        return false;
      }
    } else if(file_exists($destination)) {
      $data = file_get_contents($destination);
    } else {
      $this->errors(
        "Requesting the cache failed."
      );
      
      return false;
    }
    
    return $data;
  }
  
  /**
   * submit_query
   * 
   * @param $query string
   * @return array
   */
  protected function submit_query() {
    $items = $this->request_cache($this->YQL_FEED);
    
    if ($items) {
      return $items;
    } else {
      $this->errors(
        "Unable to submit query."
      );
      return false;
    }
  }
  
  /**
   * @function to_array
   *
   * @param $data object
   */
  protected function to_array($data) {
    if (is_object($data)) {
      $data = get_object_vars($data);
    }
    return (is_array($data)) ? array_map(__FUNCTION__, $data) : $data;
  }
  
  /**
   * get_items
   * 
   * @param $items array
   * @return array
   */
  public function get_items() {
    $data = $this->submit_query();
    
    if($data) {
      $xml   = simplexml_load_string($data);
      $data  = get_object_vars($xml->results);
      $items = $data['rss'];
      
      if($this->RANDOMIZED) {
        shuffle($items);
      }
      
      return $items;
    } else {
      $this->errors(
        "Unable to initialize the stitched feed."
      );
      return $items = array();
    }
  }
  
  /**
   * errors
   * 
   * @param $error string
   * @return array
   */
  public function errors($error, $return=false) {
    $this->ERRORS[] .= "$error";
    error_log($error, 0);
    
    if($return) {
      return $this->ERRORS;
    }
  }
  
}
?>