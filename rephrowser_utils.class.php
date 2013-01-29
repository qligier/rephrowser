<?php


class rephrowser_utils {
    const VERSION                   = 0.1;

    /* Cookies */
    protected $cookies              = array();
    protected $preserve_cookies     = true;

    /* Options */
    protected $options              = array();
    protected $follow_location      = true;





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



    public function abc() {
        echo 'ABC_REPHROWSER_UTILS_';
    }
}