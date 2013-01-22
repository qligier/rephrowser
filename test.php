<?php
error_reporting(E_ALL);
// require 'php_error.php';
// \php_error\reportErrors();

define('URL', 'http://www.qligier.ch/');
define('COOKIE', 'cookie1=valeur1;cookie2=valeur2;');
define('POST', 'post1=value1&post2=value2');

require_once 'rephrowser.class.php';


$session = new rephrowser_session();
$session->set_keep_cookies(true);

$page = $session->new_page(URL);
$page->set_follow_location(true);
$page->accept_gzip(true);
$page->exec();

$page->infos();