<?php
session_start();
/**
 * ScriptBox - Configuration File
 *
 * Below are the configuration options for the ScriptBox application.
 * Set them according to your application needs.
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

/**
 * Define Code Base Directory
 *
 * 'Categories' are made up by directory names in ScriptBox.
 * The application will scan the CATBASE directory and any directories it finds within it,
 * these will be the Categories.
 *
 * This allows you to categorize your scripts, such as 'css', 'ajax', 'php', etc. for
 * better organization.
 */
define('CATBASE', realpath('code/') . DIRECTORY_SEPARATOR);

// Base URL - The URL for ScriptBox to run at
define('BASEURL', 'http://whodidit.homeftp.net/collection/');

// Category URL - The base URL to where the categories are found.
//                This should point to the same place as CATBASE does.
define('CATURL', BASEURL .'code/');

/**
 * Show "View File" option
 *
 * This allows you to view(executing it) the script so if it is able to run as stored,
 * you can run the script while stored in ScriptBox.
 *
 * If set to TRUE, it will display a link 'VIEW FILE', which links directly to the actual file.
 *
 * This should be set to false on a public server since viewing the file executes the 
 * file and could potentially expose secure information and create a security risk.
 */
define('SHOW_VIEW_FILE', true);
