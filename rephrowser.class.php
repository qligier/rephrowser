<?php
/*
 * Rephrowser
 * Quentin Ligier <quentin.ligier@bluewin.ch>, 2013
 * https://github.com/qligier/rephrowser
 *
 * Based on Phrowser (http://code.google.com/p/phrowser/)
 * Rephrowser - Utility for browsing web content with php
*/


date_default_timezone_set(@date_default_timezone_get());
define('FIREFOX_WINDOWS',   'Mozilla/5.0 (Windows NT 5.1; rv:15.0) Gecko/20100101 Firefox/15.0.1');
define('FIREFOX_UNIX',      'Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');
define('FIREFOX_MAC',       'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; fr; rv:1.9.0.3) Gecko/2008092414 Firefox/3.0.3');
define('IPHONE',            'Mozilla/5.0 (iPod; U; CPU iPhone OS 2_1 like Mac OS X; fr-fr) AppleWebKit/525.18.1 (KHTML, like Gecko) Version/3.1.1 Mobile/5F137 Safari/525.20');
define('GOOGLEBOT',         'Googlebot/2.1 (+http://www.google.com/bot.html)');


require_once 'rephrowser_utils.class.php';
require_once 'rephrowser_page.class.php';
require_once 'rephrowser_session.class.php';
require_once 'rephrowser_html.class.php';