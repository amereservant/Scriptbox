<?php
/**
 * Scriptbox - Download File
 *
 * Here all download requests are tested for security reasons and if the proper information
 * is given, it will prompt the user to download the file.
 *
 * This allows for you to download your script files directly instead of having to copy/paste
 * the code into a blank file.
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
$required_keys = array('category', 'appname', 'file', 'action');
foreach($required_keys as $k => $key) {
    if(!isset($_GET[$key]) || strlen(trim($_GET[$key])) < 1 || $_GET['action'] != 'filedownload') {
        header('HTTP/1.1 500 Internal Error');
        exit();
    }
    if($key == 'action') unset($required_keys[$k]);
}
require_once 'system/functions.php';

$file = CATBASE;
foreach($required_keys as $key)
{
    if($key == 'file') 
        $file .= sanitize_path($_GET[$key]);
    else 
        $file .= sanitize_path($_GET[$key]) . DIRECTORY_SEPARATOR;
}

if(!file_exists($file)) {
    header('HTTP/1.1 400 Not Found');
    echo '<h1>File Not Found</h1>';
    exit();
}

$mime = get_mime_type($file, true);
$name = substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1);
$length = filesize($file);

// More extensive file download script at http://w-shadow.com/blog/2007/08/12/how-to-force-file-download-with-php/
// required for IE, otherwise Content-Disposition may be ignored
if(ini_get('zlib.output_compression'))
    ini_set('zlib.output_compression', 'Off');

if(headers_sent()) die('Invalid output before content!  Cannot proceed with download.');
header('Content-Type: '. $mime);
header('Content-Disposition: attachment; filename="'. $name .'"');
header('Content-Transfer-Encoding: binary');

// These three lines basically make the download non-cacheable
header("Cache-control: private");
header('Pragma: private');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Content-Length: $length");
readfile($file);
exit();
