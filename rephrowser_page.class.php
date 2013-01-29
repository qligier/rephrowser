<?php

class rephrowser_page {
    const VERSION                   = 0.1;

    protected $cookies              = array();
    protected $preserve_cookies     = true;
    protected $basedir              = false;

    protected $request_time         = 0;
    protected $request_url          = '';
    protected $request_follow_location = true;
    protected $request_options      = array();

    protected $response_plain       = '';
    protected $response_url         = '';
    protected $response_body        = '';
    protected $response_typemime    = '';
    protected $response_http_code   = 0;
    protected $response_http_msg    = 0;
    protected $response_http_version= 0;
    protected $response_base_url    = '';
    protected $response_redirected  = false;
    protected $response_headers     = array();
    protected $response_charset     = '';

    protected $associated_session   = false;
    protected $curl_infos           = array();


    /**
    * Constructor of this class
    * @param    string  $url              The URL to get
    * @return   void
    */
    public function  __construct($url) {
        if (!function_exists('curl_init'))
           throw new Exception('cURL PHP library is required for this class, please install it');
       if (!isset($url) || empty($url))
           throw new Exception('You are trying to initialize phrowser with an invalid URL');
       $this->request_url = $url;
       $this->response_url = $url;
       $this->response_base_url = $this->webdirname($this->request_url);
       $this->basedir = (bool)(mb_strlen(ini_get('open_basedir')) > 0);
       $this->basedir = true;
    }

    function __get($variable) {
        if (!isset($this->{$variable}))
            throw new Exception('This variable doesn\'t exist');
        return $this->{$variable};
    }


    public function set_preserve_cookies($bool = true) {
        $this->preserve_cookies = (bool)$bool;
    }

    public function set_follow_location($bool = true) {
        if ($bool && !$this->basedir)
            $this->set_option(CURLOPT_FOLLOWLOCATION, true);
        $this->request_follow_location = $bool;
    }

    /**
    * Set cURL option
    * @param    const   $curl_option     One of the cURL options
    * @param    mixed   $value           The associated value
    * @return   void
    * @see      http://php.net/manual/fr/function.curl-setopt.php
    */
    public function set_option($curl_option, $value) {
        $this->request_options[$curl_option] = $value;
    }


    /**
    * Accept GZIP encoding reponse
    * @param    bool    $value          Accept GZIP encoding
    * @return   void
    */
    public function accept_gzip($value = TRUE) {
        if ($value)
           $this->set_option(CURLOPT_ENCODING, 'gzip');
       else
           $this->set_option(CURLOPT_ENCODING, 'identity');
    }


    /*
    * Set POST option
    * @param    mixed[] $post           ?
    */
    public function set_post($post) {
        if (!is_string($post) && !is_array($post))
           throw new Exception('This is not a valid POST value');
       $this->set_option(CURLOPT_POST, true);
       if (is_string($post))
           $this->set_option(CURLOPT_POSTFIELDS, $post);
       else {
           $post_vars = array();
           foreach ($post AS $k => $v)
              $post_vars[] = $k.'='.urlencode($v);
          $this->set_option(CURLOPT_POSTFIELDS, implode('&', $post_vars));
      }
    }

    /**
    * Returns the value of a cookie by name
    * @param    string  $name           The name of the cookie
    * @return   mixed                   The value of the cookie
    */
    public function get_cookie($name) {
        return $this->cookies[$name]['value'];
    }

    /**
    * Sets cookie
    * @param    string  $name           The name of the cookie
    * @param    mixed   $value          The value of the cookie
    * @return   void
    */
    public function set_cookie($name, $value) {
        $this->cookies[$name] = array('value' => $value);
    }

    public function set_cookies($cookies) {
        if (is_string($cookies)) {
            $parts = explode('; ', $cookies);
            foreach ((array)$parts AS $cookie) {
                list($name, $value) = explode($cookie, '=', 2);
                $this->set_cookie($name, $value);
            }
        }
        elseif (is_array($cookies)) {
            $this->cookies = array_merge($this->cookies, $cookies);
        }
    }

    public function set_user_agent($ua) {
        $this->set_option(CURLOPT_USERAGENT, $ua);
    }

    /**
    * Find one match by pcre pattern in last response
    * Only one capturing group must be defined!
    *
    * Example (Finds some image source on page):
    * $b->find_scalar_match('/<img src="([^"]+)"\/>/');
    * @param    string  $pattern        The PCRE pattern to search for
    * @return   mixed                   Returns the first capturing group or false
    */
    public function find_scalar_match($pattern) {
        if (preg_match($pattern, $this->response_body, $matches) === 1)
           return $matches[1];
       return false;
    }

    /**
    * Find all matches by pcre pattern in last response
    * At least one capturing group must be defined
    * Returns an array of resulted matches
    * @param    string  $pattern        The PCRE pattern to search for
    * @return   mixed[]                 Returns the matches or false
    */
    public function find_all_matches($pattern) {
        preg_match_all($pattern, $this->response_body, $matches);
        array_shift($matches);

        $result = array();
        if ( $matches[0] ) {
           foreach ( $matches[0] as $i => $match ) foreach ( $matches as $j => $match )
              $result[$i][$j] = $match[$i];
      }

      return $result;
    }

    public function get_all_links($absolute_urls = true) {
        $matches = $this->find_all_matches('#(src|href)=("|\')([^"\']+)\2#i');
        foreach ($matches AS $match) {
            if ($absolute_urls)
                $urls[] = $this->absolute_url($match[2]);
            else
                $urls[] = $match[2];
        }
        foreach ($urls AS $url)
            $unique_urls[$url] = 1;
        return array_keys((array)$unique_urls);
    }

    public function associate_session(rephrowser_session &$session) {
        $this->associated_session = $session;
    }

    public function webdirname($url) {
        $parsed = parse_url($url);
        if (!$parsed)
            return false;
        $return = $parsed['scheme'].'://';
        if (isset($parsed['user'])) {
            $return .= $parsed['user'];
            if (isset($parsed['pass']))
                $return .= $parsed['pass'];
            $return .= '@';
        }
        $return .= $parsed['host'];
        if (isset($parsed['port']))
            $return .= ':'.$parsed['port'];
        if (isset($parsed['path']))
            $return .= dirname($parsed['path']);
        return rtrim($return, '\/').'/';
    }

    public function exec() {
        $this->request_time = -microtime(true);

        $this->request_options[CURLOPT_RETURNTRANSFER] = true;
        $this->request_options[CURLOPT_HEADER] = true;

        if ($this->preserve_cookies && $this->cookies) {
            $cookies = array();
            foreach ( $this->cookies as $cookie_name => $cookie_data )
                $cookies[] = "{$cookie_name}={$cookie_data[value]}";
            $this->request_options[CURLOPT_COOKIE] = implode(';', $cookies);
        }

        $c = curl_init($this->response_url);
        foreach ($this->request_options as $opt => $val)
            @curl_setopt($c, $opt, $val);

        $data = curl_exec($c);
        $this->curl_infos = curl_getinfo($c);
        curl_close($c);
        $this->response_plain = $data;
        $this->parse_response($data);

        if ($this->redirect())
            return false;

        $this->associated_session->add_to_history($this);
        $this->request_time += microtime(true);
    }

    public function infos() {
        if ($this->request_time === 0) {
            echo 'La requête n\'a pas encore été exécutée.';
        }

        echo 'La page ',$this->response_url,' a été récupérée en ',round($this->request_time, 3),'s avec le code HTTP ',$this->response_http_code;
        if ($this->response_redirected)
            echo ' (la page a été redirigée)';
        echo '.<br />Son type MIME est ',$this->response_typemime,' et le corps fait ',number_format(mb_strlen($this->response_body), 0, '.', "'"),' caractères.';
    }

    public function save_in_file($filepath, $overwrite = false) {
        if ((is_file($filepath) && !$overwrite) || $this->request_time === 0)
            return false;
        return file_put_contents($filepath, serialize($this));
    }




    protected function absolute_url($url) {
        if (strpos($url, '://') !== false) {}
        elseif (substr($url, 0, 2) == '//')
            $url = parse_url($this->response_url, PHP_URL_SCHEME).':'.$url;
        elseif (substr($url, 0, 1) == '/') {
            $parts = parse_url($this->response_url);
            $end_limit = 0;
            $end_limit -= (isset($parts['path'])) ? mb_strlen($parts['path']) : 0;
            $end_limit -= (isset($parts['query'])) ? mb_strlen($parts['query'])+1 : 0;
            $end_limit -= (isset($parts['fragment'])) ? mb_strlen($parts['fragment'])+1 : 0;
            $url = substr($this->response_url, 0, $end_limit).$url;
        }
        else
            $url = rtrim($this->response_base_url, '/').'/'.$url;
        if (strpos($url, '#') !== false)
            $url = substr($url, 0, strpos($url, '#'));
        return $url;
    }

    protected function detect_encoding() {
        if (isset($this->response_headers['Content-Type']) && strpos($this->response_headers['Content-Type'], 'charset=') !== false)
            $this->response_charset = substr($this->response_headers['Content-Type'], strpos($this->response_headers['Content-Type'], 'charset=') + 8);
        else
            $this->response_charset = $this->find_scalar_match('/text\/html; charset=([^"]+)"/');
    }

    protected function parse_response( $http ) {
        while (strpos($http, 'HTTP') === 0) {
            $header_divider = strpos($http, "\r\n\r\n");
            $this->response_body = substr($http, $header_divider + 4);
            $this->response_headers = $this->parse_headers(substr($http, 0, $header_divider));

            $http = $this->response_body;
        }

        $this->detect_encoding();
        if (!empty($this->response_charset) && $this->response_body != 'UTF-8')
            $this->response_body = iconv($this->response_charset, 'UTF-8', $this->response_body);

        if ($this->response_typemime = 'text/html')
            $this->get_base_url();
    }

    /*
     * @todo    Code HTTP ?
     */
    protected function parse_headers($http) {
        $headers = array();

        if ($http_lines = explode("\r\n", $http)) {
            foreach ($http_lines as $line) {
                $header = explode(': ', $line, 2);
                if (isset($headers[$header[0]])) {
                    $headers[$header[0]] = (array)$headers[$header[0]];
                    $headers[$header[0]][] = $header[1];
                } else {
                    if (isset($header[1]))
                        $headers[$header[0]] = $header[1];
                    else {
                        $headers[$header[0]] = '';
                        if (preg_match('$HTTP/(\d{1}\.\d{1}) (\d{3}) (.+)$i', $header[0], $matches))
                            list($null, $this->response_http_version, $this->response_http_code, $this->response_http_msg) = $matches;
                    }
                }
            }
        }

        if (isset($headers['Set-Cookie']))
            $this->parse_cookies($headers['Set-Cookie']);

        if (isset($headers['Content-Type'])) {
            $parts = explode(';', $headers['Content-Type']);
            $this->response_typemime = $parts[0];
        }

        return $headers;
    }

    /*
     * 
     */
    protected function parse_cookies($set_cookie) {
        $set_cookie = (array)$set_cookie;
        foreach ($set_cookie AS $cookie) {
            $parts = explode('; ', $cookie);
            list($name, $value) = explode('=', $parts[0], 2);
            $new_cookie = array('value' => $value);
            for ($i=1, $end=count($parts); $i<$end; ++$i) {
                if (strpos($parts[$i], '=') !== false) {
                    list($prop, $value) = explode('=', $parts[$i], 2);
                    $new_cookie[$prop] = $value;
                }
                else
                    $new_cookie[$parts[$i]] = '';
            }
            //echo 'Expires : '.strtotime(@$new_cookie['expires']).' : '.time().'<br />';
            if (isset($new_cookie['expires']) && strtotime($new_cookie['expires']) > time())
                $this->cookies[$name] = $new_cookie;
        }
    }

    protected function redirect() {
        if (!$this->request_follow_location)
            return false;
        if (!$this->basedir && $this->curl_infos['redirect_count'] > 0) {
            $this->response_redirected = true;
            $this->response_url = $this->curl_infos['url'];
            $this->response_http_code = $this->curl_infos['http_code'];
            $this->response_typemime = $this->curl_infos['content_type'];
            return false;
        }
        elseif (isset($this->response_headers['Location'])) {
            $this->response_redirected = true;
            $this->response_url = $this->response_headers['Location'];
            $this->response_base_url = $this->webdirname($this->response_url);
            $this->exec();
            return true;
        }
        return false;
    }

    protected function get_base_url() {
        if (preg_match('$<base href="([^"]+)"$is', $this->response_body, $matches))
            $this->response_base_url = $matches[1];
    }
}