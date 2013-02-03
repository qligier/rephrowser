<?php


class rephrowser_utils {
    const VERSION                   = 0.1;

    /* Cookies */
    protected $cookies              = array();
    protected $preserve_cookies     = true;

    /* Options */
    protected $options              = array();
    protected $follow_location      = true;
    protected $post_values          = array();





    public function __get($variable) {
        if (!isset($this->{$variable}))
            throw new Exception('This variable doesn\'t exist');
        return $this->{$variable};
    }


    public function set_preserve_cookies($bool = true) {
        $this->preserve_cookies = (bool)$bool;
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







    /**
    * Set cURL option
    * @param    const   $curl_option     One of the cURL options
    * @param    mixed   $value           The associated value
    * @return   void
    * @see      http://php.net/manual/fr/function.curl-setopt.php
    */
    public function set_option($curl_option, $value) {
        $this->options[$curl_option] = $value;
    }


    public function set_options($options) {
        if (!is_array($options))
            return false;
        foreach ($options AS $option => $value)
            $this->set_option($option, $value);
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
    public function add_post($post, $value = '') {
        if (!is_string($post) && !is_array($post))
            throw new Exception('This is not a valid POST value');
        
        if (is_string($post) && empty($value)) {
            $post_vars = explode('&', $post);
            foreach ($post_vars AS $post_var) {
                list($name, $value) = explode('=', $post_var);
                $this->post_values[$name] = urlencode($value);
            }
        }
        elseif (is_array($post))
            $this->post_values = array_merge($this->post_values, $post);
        else
            $this->post_values[$post] = urlencode($value);
    }


    public function add_post_file($name, $path) {
        $this->add_post($name, '@'.$path);
    }


    public function set_follow_location($bool = true) {
        $this->follow_location = $bool;
    }


    public function set_user_agent($ua) {
        $this->set_option(CURLOPT_USERAGENT, $ua);
    }


    public function set_ssl_accept_all_certificats($bool = false) {
        $this->set_option(CURLOPT_SSL_VERIFYPEER, (bool)$bool);
    }

    // http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
    public function set_ssl_certificat($path) {
        $this->set_ssl_accept_all_certificats(false);
        $this->set_option(CURLOPT_SSL_VERIFYHOST, 2);
        $this->set_option(CURLOPT_CAINFO, $path);
    }


    public function set_proxy($proxy, $isSocks5 = false, $username = '', $password = '') {
        $this->set_option(CURLOPT_PROXY, $proxy);
        if ($isSocks5)
            $this->set_option(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        else
            $this->set_option(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        if (!empty($username) && !empty($password))
            $this->set_option(CURLOPT_PROXYUSERPWD, $username.':'.$password);
    }


    public function set_referer($referer) {
        $this->set_option(CURLOPT_REFERER, $referer);
    }


    public function set_auth($username, $password) {
        $this->set_option(CURLOPT_USERPWD, $username.':'.$password);
    }



    public function abc() {
        echo 'ABC_REPHROWSER_UTILS_';
    }
}