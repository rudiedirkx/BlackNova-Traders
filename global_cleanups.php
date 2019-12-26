<?php
// Blacknova Traders - A web-based massively multiplayer space combat and trading game
// Copyright (C) 2001-2012 Ron Harwood and the BNT development team
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU Affero General Public License as
//  published by the Free Software Foundation, either version 3 of the
//  License, or (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU Affero General Public License for more details.
//
//  You should have received a copy of the GNU Affero General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// File: global_cleanups.php

if (preg_match("/global_cleanups.php/i", $_SERVER['PHP_SELF'])) {
      echo "You can not access this file directly!";
      die();
}

if (!ob_start("ob_gzhandler")) ob_start(); // If the server will support gzip compression, use it. Otherwise, start buffering.
//ob_start();

// Benchmarking - start before anything else.
$BenchmarkTimer = new c_Timer;
$BenchmarkTimer->start(); // Start benchmarking immediately

// Create/touch a file named dev in the main game directory to activate development mode
if (file_exists("dev"))
{
    ini_set('error_reporting', E_ALL); // During development, output all errors, even notices
    ini_set('display_errors', '1'); // During development, *display* all errors
    $db_logging = true; // True gives an admin log entry for any SQL calls that update/insert/delete, and turns on adodb's sql logging. Only for use during development!This makes a huge amount of logs! You have been warned!!
}
else
{
    ini_set('error_reporting', 0); // No errors
    ini_set('display_errors', '0'); // Don't show them
    $db_logging = false; // True gives an admin log entry for any SQL calls that update/insert/delete, and turns on adodb's sql logging. Only for use during development!This makes a huge amount of logs! You have been warned!!
}

ini_set('url_rewriter.tags', ''); // Ensure that the session id is *not* passed on the url - this is a possible security hole for logins - including admin.

global $db;
connectdb();
$db->prefix = $db_prefix;
$db->logging = $db_logging;

if ($db_logging)
{
    adodb_perf::table("{$db->prefix}adodb_logsql");
    $db->LogSQL(); // Turn on adodb performance logging
}

if (!isset($index_page))
{
    $index_page = false;
}

session_start();

// reg_global_fix,0.1.1,22-09-2004,BNT DevTeam
if (!defined('reg_global_fix'))define('reg_global_fix', TRUE);

foreach ($_POST as $k=>$v)
{
    if (!isset($GLOBALS[$k]))
    {
        ${$k}=$v;
    }
}
foreach ($_GET as $k=>$v)
{
    if (!isset($GLOBALS[$k]))
    {
        ${$k}=$v;
    }
}
foreach ($_COOKIE as $k=>$v)
{
    if (!isset($GLOBALS[$k]))
    {
        ${$k}=$v;
    }
}

$lang = $default_lang;
$playerinfo = $playerfound = $username = null;

if (!empty($_SESSION['logged_in'])) {
    $id = $_SESSION['logged_in'];
    $res = $db->Execute("SELECT * FROM {$db->prefix}ships WHERE ship_id='$id'");
    db_op_result ($db, $res, __LINE__, __FILE__, $db_logging);
    if ($res && ($playerfound = $res->RecordCount()))
    {
        $playerinfo = $res->fields;
        $username = $playerinfo['email'];
        $lang = $playerinfo['lang'];
    }
}

if (!$playerinfo && isset($_GET['lang']))
{
    $lang = $_GET['lang'];
}

$avail_lang[0]['file'] = 'english';
$avail_lang[0]['name'] = 'English';
$avail_lang[1]['file'] = 'french';
$avail_lang[1]['name'] = 'Francais';
$avail_lang[2]['file'] = 'german';
$avail_lang[2]['name'] = 'German';
$avail_lang[3]['file'] = 'spanish';
$avail_lang[3]['name'] = 'Spanish';

if (empty($link_forums))
{
    $link_forums = "http://forums.blacknova.net";
}

$ip = $_SERVER['REMOTE_ADDR'];
$plugin_config = array();
$admin_list = array();
date_default_timezone_set('America/New_York'); // Set to your server's local time zone - PHP throws a notice if this is not set.

// Used to define what devices are used to calculate the average tech level.
$calc_tech         = array("hull", "engines", "computer", "armor", "shields", "beams", "torp_launchers");
$calc_ship_tech    = array("hull", "engines", "computer", "armor", "shields", "beams", "torp_launchers");
$calc_planet_tech  = array("hull", "engines", "computer", "armor", "shields", "beams", "torp_launchers");

$l = new bnt_translation();

// Auto detect and set the game path (uses the logic from setup_info)
// If it does not work, please comment this out and set it in db_config.php instead.
// But PLEASE also report that it did not work for you at the main BNT forums (forums.blacknova.net)
$gamepath = dirname($_SERVER['PHP_SELF']);
if (isset($gamepath) && strlen($gamepath) > 0)
{
    if ($gamepath === "\\")
    {
        $gamepath = "/";
    }

    if ($gamepath[0] != ".")
    {
        if ($gamepath[0] != "/")
        {
            $gamepath = "/$gamepath";
        }

        if ($gamepath[strlen($gamepath)-1] != "/")
        {
            $gamepath = "$gamepath/";
        }
    }
    else
    {
        $gamepath ="/";
    }
    $gamepath = str_replace("\\", "/", stripcslashes($gamepath));
}
// Game path setting ends

// Auto detect and set the Game domain setting (uses the logic from setup_info)
// If it does not work, please comment this out and set it in db_config.php instead.
// But PLEASE also report that it did not work for you at the main BNT forums (forums.blacknova.net)

$remove_port = true;
$gamedomain = $_SERVER['HTTP_HOST'];

if (isset($gamedomain) && strlen($gamedomain) >0)
{
    $pos = strpos($gamedomain,"http://");
    if (is_integer($pos))
    {
        $gamedomain = substr($gamedomain,$pos+7);
    }

    $pos = strpos($gamedomain,"www.");
    if (is_integer($pos))
    {
        $gamedomain = substr($gamedomain,$pos+4);
    }

    if ($remove_port)
    {
        $pos = strpos($gamedomain,":");
    }

    if (is_integer($pos))
    {
        $gamedomain = substr($gamedomain,0,$pos);
    }

    if ($gamedomain[0]!=".")
    {
        $gamedomain=".$gamedomain";
    }
}
// Game domain setting ends
