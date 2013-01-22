<?php

class rephrowser_session {
    const VERSION                       = 0.1;

    protected $session_history          = array();
    protected $session_keep_cookies     = false;
    protected $session_cookies          = array();
    protected $session_follow_location  = true;
    protected $session_auto_referer     = true;

    public function destry_historical() {
        $this->session_history = array();
    }

    public function add_to_history(rephrowser_page $page) {
        $this->session_history[] = $page;
    }

    public function get_history($nth = 1) {
        if (count($this->session_history) < $nth || $nth < 1)
            return false;
        return $this->session_history[count($this->session_history) -$nth];
    }

    public function set_keep_cookies($bool = true) {
        $this->session_keep_cookies = (bool)$bool;
    }

    public function set_auto_referer($bool = true) {
        $this->session_auto_referer = (bool)$bool;
    }

    public function new_page($url) {
        $page = new rephrowser_page($url);
        $page->set_preserve_cookies($this->session_keep_cookies);
        if ($this->session_keep_cookies)
            $page->set_cookies($this->session_cookies);
        $page->associate_session($this);
        if ($this->session_auto_referer && $last_page = $this->get_history(1)) {
            $page->set_option(CURLOPT_REFERER, $last_page->response_url);
            unset($last_page);
        }
        return $page;
    }

    public function new_page_from_history($nth = 1) {
        $old_page = $this->get_history($nth);
        if (!$old_page)
            return false;
        $new_page = $this->new_page();
        foreach ($old_page->request_options AS $option => $value)
            $new_page->set_option($option, $value);
        $new_page->set_cookies($old_page->cookies);
        return $new_page;
    }

    public function abc() {
        echo 'ABC as easy as 123';
    }
}