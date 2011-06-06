<?php
/**
 * Scriptbox - Ajax Handling
 *
 * This file is responsible for handling all AJAX requests and processing the return data.
 * Any requests other than AJAX requests will result in the script stopping execution and dying.
 *
 * You are free to use this script as long as you abide by the terms of the
 * <b>Creative Commons Attribution-ShareAlike 3.0 License</b>, which in summary means
 * you must give credit to the author of Scriptbox in any derivative works and
 * any derivative works must have a like license.
 *
 * Visit the license URL if you are not clear what the license means and need clearer details.
 * Please enjoy and share!
 *
 * @category    Organization
 * @package     Scripbox
 * @version     1.0
 * @author      David Miles <david@amereservant.com>
 * @link        http://github.com/amereservant
 * @license     http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-ShareAlike 3.0 Unported
 */
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') die('Invalid request!');

if(!@include 'system/functions.php') { 
    header('HTTP/1.1 500 Internal Server Error');
    exit();
}

if(isset($_POST['searchphrase'])) {
    echo strlen($_POST['searchphrase']) > 0 ? get_formatted_search($_POST['searchphrase']) : '<h2 class="noresults">No Results</h2>';
    exit();
}

if(isset($_POST['file']) && isset($_POST['appname']) && isset($_POST['category'])) {
    $req_file = CATBASE . sanitize_path($_POST['category']) . DIRECTORY_SEPARATOR . 
        sanitize_path($_POST['appname']) . DIRECTORY_SEPARATOR . sanitize_path($_POST['file']);
    
    if(!file_exists($req_file))
    {
        header('HTTP/1.1 404 Not Found');
        exit();
    }
    echo get_file_syntax($req_file);
    exit();
}
