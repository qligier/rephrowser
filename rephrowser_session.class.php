<?php

class rephrowser_session extends rephrowser_utils {

    protected $session_history          = array();
    protected $session_auto_referer     = true;



    public function destroy_historical() {
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

    public function set_auto_referer($bool = true) {
        $this->session_auto_referer = (bool)$bool;
    }

    public function new_page($url) {
        $page = new rephrowser_page($url);

        $page->associate_session($this);
        $page->set_preserve_cookies($this->preserve_cookies);
        if ($this->preserve_cookies)
            $page->set_cookies($this->cookies);

        if ($this->session_auto_referer && $last_page = $this->get_history(1)) {
            $page->set_option(CURLOPT_REFERER, $last_page->response_url);
            unset($last_page);
        }

        if ($this->preserve_cookies)
            $page->set_cookies($this->cookies);

        $page->set_options($this->options);
        $page->set_follow_location($this->follow_location);

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

    public function save_in_file($filepath, $overwrite = false) {
        if (is_file($filepath) && !$overwrite)
            return false;
        return file_put_contents($filepath, serialize($this));
    }

    public function load_from_file($filename) {
        unset($this);
        return unserialize(file_get_contents($filename));
    }

}