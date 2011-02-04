jNag is a mobile, web based interface for the Nagios network monitoring system

It uses the livestatus nagios plugin for its backend, and jQuery mobile for the front end.

A native android app is available from the android market, which is packaged using phonegap.

If anyone has access to an iPhone development environment and would be willing to package jNag as 
an iPhone app for me, hit me up via email.

Me: http://tall-paul.co.uk
Nagios: http://www.nagios.org/
Livestatus: http://mathias-kettner.de/checkmk_livestatus.html
jQuery mobile: http://jquerymobile.com/
phonegap: http://phonegap.com/  


jNag is released under the GPLv3 license
You should have received a copy of the licence with this distribution.

installation /config.

requirements:  Nagios install (tested on 3.2, might work on others?)
               Livestatus installed and working

jNag consists of two seperate components: client & server.

jNag Server is a small plugin that sits in your nagios web folder and queries 
livestatus for information, which it then provides to clients

jNag client connects to the server component and displays its information

the client and server don't have to be located on the same physical server, but
for most installations they will be.

to setup jNag:

1: copy the client and server folders somewhere under your nagios web directory
    for this example we'll use 'jNag', which is located at http://example.com/nagios/jNag
    
2: open "jNag/server/config.php" in a text editor and edit to match your installation

3: connect to http://example.com/nagios/jNag/client with your browser

4: if this is your first time connecting you'll be prompted for a data address,
    and a username / password.  Data address in our example is 'example.com/nagios/jNag/server/returndata.php'
    username / password is the user you connect to nagios with (case sensitive!!)

5: that should be it!

To get host / service specific images you need to add a custom variable to your nagios
host / service definition. Add a line to each host / service (or template) as follows:

_jnag_image $SERVER$/windows-host.png

$SERVER$ is replaced with the url to your images directory (in our example this is
example.com/nagios/jNag/server/images but can be changed in config)

windows-host.png is a valid image in this folder.

if an image is not specified then the default image on the client is used.  

 
    
