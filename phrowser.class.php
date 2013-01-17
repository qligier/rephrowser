<?php
/*	Rephrowser by Quentin Ligier 2013
	Based on Phrowser (http://code.google.com/p/phrowser/)
	Rephrowser - Utility for extracting web content with php
*/
class phrowser {
	
	/**
	 * @var	string	Version of Rephrowser
	 */
	const VERSION = 0.1;
	
	/**
	 * @var	string	Body of page received
	 */
	protected $body = '';
	
	/**
	 * @var	string	Headers received, raw
	 */
	protected $headers = '';
	
	/**
	 * @var	mixed[]	Cookies 
	 */
	protected $cookies = array();

	/**
	 * @var	bool	Preserve received cookie
	 */
	protected $preserve_cookies = true;
	
	/**
	 * @var	mixed[]	Default options
	 */
	protected $options = array(
		CURLOPT_FOLLOWLOCATION => true
	);
	
	
	/**
	 * Constructor of this class
	 * @return	void
	 */
	public function  __construct() {
		if (!function_exists('curl_init'))
			throw new Exception('cURL PHP library is required for this class, please install it');
	}

	/**
	 * Set cURL option
	 * @param	const	$curl_option	One of the cURL options
	 * @param	mixed	$value			The associated value
	 * @return	void
	 * @see		http://php.net/manual/fr/function.curl-setopt.php
	 */
	public function set_option($curl_option, $value) {
		$this->options[$curl_option] = $value;
	}
	
	
	/**
	 * Accept GZIP encoding reponse
	 * @param	bool	$value			Accept GZIP encoding
	 * @return	void
	 */
	public function accept_gzip($value = TRUE) {
		if ($value)
			$this->set_option(CURLOPT_ENCODING, 'gzip');
		else
			$this->set_option(CURLOPT_ENCODING, 'identity');
	}

	/**
	 * Send GET request to URL
	 * @param	string	$url			Get that page with a GET request
	 * @return	bool					Everything went well ?
	 */
	public function url_get( $url ) {
		$this->exec_url($url);
	}

	/**
	 * Send POST request to URL
	 * @param	string	$url			Get that page with a POST request
	 * @param	mixed[]	$variables		An array of the POST values
	 * @return	void
	 */
	public function url_post( $url, $variables = array() ) {
		foreach ( $variables as $var => $value )
			$post_vars[] = "{$var}=" . urlencode($value);

		$this->exec_url($url, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => implode('&', $post_vars)
		));
	}

	/**
	 * Returns last response body
	 * @return	string					The last body received
	 */
	public function get_body() {
		return $this->body;
	}

	/**
	 * Returns last response headers array
	 * @return	string[]				The array of the last headers received
	 */
	public function get_headers() {
		return $this->parse_headers($this->headers);
	}
	
	/**
	 * Returns last response headers raw
	 * @return	string					The last headers received, raw
	 */
	public function get_headers_raw() {
		return $this->headers;
	}

	/**
	 * Returns last response cookies array
	 */
	public function get_cookies() {
		return $this->cookies;
	}

	/**
	 * Returns the value of a cookie by name
	 * @param	string	$name			The name of the cookie
	 * @return	mixed					The value of the cookie
	 */
	public function get_cookie($name) {
		return $this->cookies[$name]['value'];
	}

	/**
	 * Sets cookie
	 * @param	string	$name			The name of the cookie
	 * @param	mixed	$value			The value of the cookie
	 * @return	void
	 */
	public function set_cookie($name, $value) {
		$this->cookies[$name] = array('value' => $value);
	}

	/**
	 * Find one match by pcre pattern in last response
	 * Only one capturing group must be defined!
	 *
	 * Example (Finds some image source on page):
	 * $b->find_scalar_match('/<img src="([^"]+)"\/>/');
	 * @param	string	$pattern		The PCRE pattern to search for
	 * @return	mixed					Returns the first capturing group or false
	 */
	public function find_scalar_match($pattern) {
		if (preg_match($pattern, $this->get_body(), $matches) === 1)
			return $matches[1];
		return false;
	}

	/**
	 * Find all matches by pcre pattern in last response
	 * At least one capturing group must be defined
	 * Returns an array of resulted matches
	 * @param	string	$pattern		The PCRE pattern to search for
	 * @return	mixed					Returns the matches or false
	 */
	public function find_all_matches($pattern) {
		preg_match_all($pattern, $this->get_body(), $matches);
		array_shift($matches);

		$result = array();
		if ( $matches[0] ) {
			foreach ( $matches[0] as $i => $match ) foreach ( $matches as $j => $match )
				$result[$i][$j] = $match[$i];
		}

		return $result;
	}

	protected function exec_url( $url, $options = array() ) {
		foreach ( $this->options as $k => $v )
			$options[$k] = $v;
		
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_HEADER] = true;
		
		if (!isset($options[CURLOPT_USERAGENT]))
			$options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.10) Gecko/2009042523 Ubuntu/8.10 (intrepid) Firefox/3.0.10 GTB5';
		
		if (!isset($options[CURLOPT_REFERER]))
			$options[CURLOPT_REFERER] = $url;
		
		if ( $this->preserve_cookies && $this->cookies ) {
			$cookies = array();
			
			foreach ( $this->cookies as $cookie_name => $cookie_data )
				$cookies[] = "{$cookie_name}={$cookie_data[value]}";
				
			$options[CURLOPT_COOKIE] = implode(';', $cookies);
		}

		$c = curl_init( $url );

		foreach ( $options as $opt => $val )
				@curl_setopt($c, $opt, $val);

		$data = curl_exec($c);
		curl_close($c);

		$this->parse_response($data);
	}

	protected function parse_response( $http ) {
		while ( strpos($http, 'HTTP') === 0 ) {
			$header_divider = strpos($http, "\r\n\r\n");
			$this->body = substr($http, $header_divider + 4);
			$this->headers = substr($http, 0, $header_divider);
			//$this->headers = $this->parse_headers($this->headers_raw);

			$http = $this->body;
		}

		if ( array_key_exists('Content-Type', $this->headers) && strpos($this->headers['Content-Type'], 'charset=') ) {
			if ( ( $charset = substr($this->headers['Content-Type'], strpos($this->headers['Content-Type'], 'charset=') + 8) ) != 'UTF-8' )
				$this->body = iconv($charset, 'UTF-8', $this->body);
		}
		else if ( $charset = $this->find_scalar_match('/text\/html; charset=([^"]+)"/') )
			$this->body = iconv($charset, 'UTF-8', $this->body);
	}

	protected function parse_headers( $http ) {
		$headers = array();

		if ( $http_lines = explode("\r\n", $http) ) {
			foreach ( $http_lines as $line ) {
				$header = explode(':', $line);
				if ( $headers[$header[0]] ) {
					$headers[$header[0]] = (array)$headers[$header[0]];
					$headers[$header[0]][] = $header[1];
				} else {
					if ( $header[0] == 'Location' )
						$headers[$header[0]] = implode(':', array_slice($header, 1));
					else
						$headers[$header[0]] = $header[1];
				}
			}
		}

		if ( $headers['Set-Cookie'] )
			$this->parse_cookies($headers);
		
		return $headers;
	}

	protected function parse_cookies( $headers ) {
		if ( $cookie_headers = (array)$headers['Set-Cookie'] ) {
			foreach ( $cookie_headers as $cookie_header ) {
				$cookie_data = explode(';', $cookie_header);
				$cookie_val = array_shift($cookie_data);
				$cookie_val = explode('=', $cookie_val);
				
				$cookie_params = array();
				foreach ( $cookie_data as $cookie_param ) {
					$cookie_param = explode('=', $cookie_param);
					$cookie_params[trim($cookie_param[0])] = $cookie_param[1];
				}

				$cookie_params['value'] = $cookie_val[1];
				
				$this->cookies[trim($cookie_val[0])] = $cookie_params;
			}
		}
	}
}