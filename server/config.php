<?PHP
/*
    jNag, mobile interface for the Nagios network monitoring server
    Copyright (C) 2011  Paul Berry

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>
    
    Contact the author: tall_paulb@hotmail.com
                        tall-paul.co.uk
 */


  /* #############CHANGE THESE SETTINGS FOR YOUR NAGIOS INSTALL############ */

$username = $_SERVER['PHP_AUTH_USER']; //change this if you want to get status info for a user other than the one you're logged into nagios with
$password = $_SERVER['PHP_AUTH_PW'];

//URLS relative to your server webroot
$cgi_bin = "/nagios/cgi-bin";
$jNag_root = "/nagios/mobilev2";
$images = "/nagios/mobilev2/server/images";

//use pnp graphs in service view if available
$pnp_enable = true;
$pnp_url = "/nagios/pnp/index.php";

//path to your livestatus socket
$data_address = "unix:///usr/local/nagios/var/rw/live";

/* #########DON'T CHANGE ANYTHING BELOW THIS LINE############# */
global $json,$cmd_url,$version,$poll_time,$page,$version_info, $pnp_url,$pnp_enable,$images_url;
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off'   ){
  $server_root =  "http://$username:$password@".$_SERVER['HTTP_HOST'];
} else {
   $server_root =  "https://$username:$password@".$_SERVER['HTTP_HOST'];
}
$data_source = "livestatus";
$cgi_bin = $server_root.$cgi_bin;
$jNag_root = $server_root.$jNag_root;
$pnp_url = $server_root.$pnp_url;
$images_url = $server_root.$images;
$page = str_replace($server_root.$jNag_root."/pages/","","http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
if ($page == "")
  $page = "index.php";
$cmd_url = "$cgi_bin/cmd.cgi";





