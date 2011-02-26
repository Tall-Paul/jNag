<?PHP    
/*
################################################################################
###################    SETTINGS  ###############################################
################################################################################
*/
 
//URLS relative to your server webroot
$cgi_bin = "/nagios/cgi-bin";
$jNag_root = "/nagios/jNag";
$images = "/nagios/jNag/server/images";

//use pnp graphs in service view if available
$pnp_enable = true;
$pnp_url = "/nagios/pnp/index.php";

//path to your livestatus socket
$data_address = "unix:///usr/local/nagios/var/rw/live";

//Use AuthUsername in livestatus requests?
$authuser = true;

/*
################################################################################
END OF SETTINGS
DON'T CHANGE ANYTHING BELOW THIS LINE!!
################################################################################
*/


  $username = $_SERVER['PHP_AUTH_USER']; //change this if you want to get status info for a user other than the one you're logged into nagios with
  $password = $_SERVER['PHP_AUTH_PW'];
  if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off'   ){
    $server_root =  "http://$username:$password@".$_SERVER['HTTP_HOST'];
  } else {
     $server_root =  "https://$username:$password@".$_SERVER['HTTP_HOST'];
  }
  $cgi_bin = $server_root.$cgi_bin;
  $jNag_root = $server_root.$jNag_root;
  $pnp_url = $server_root.$pnp_url;
  $images_url = $server_root.$images;
  $cmd_url = "$cgi_bin/cmd.cgi";
  if ($authuser == true)
      $authuser = "\nAuthUser: $username";
  else
      $authuser = "";
    // PREVENT CACHING FIRST BEFORE ANYTHING ELSE!
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // alwaysmodified
    header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache"); // HTTP/1.0
    //CORS headers, taken from http://saltybeagle.com/2009/09/cross-origin-resource-sharing-demo/
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    header('Content-type: text/json');
    if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
        exit();
    }
    if (isset($HTTP_RAW_POST_DATA)) {
    $data = explode('&', $HTTP_RAW_POST_DATA);
    foreach ($data as $val) {
        if (!empty($val)) {
            list($key, $value) = explode('=', $val);   
            $_POST[$key] = urldecode($value);
        }
    }
    }        

    
    function format_time($timestamp){        
      if ($timestamp > 0){
          return date("d/m/Y-H:i",$timestamp);
      } else {
          return "Never";
      }
    }    

    function run_query($query){
      global $data_address;
      $fp = fsockopen($data_address,0);
      if (!$fp) {
        echo "$errstr ($errno)<br />\n";
      } else {
        $returnval = "";
        fwrite($fp,$query);        
        while (!feof($fp)) {
          $returnval = $returnval.fgets($fp, 128);
        }    
      fclose($fp);
      if ($returnval == "[] ")
          $returnval = "";
      return $returnval;
    }
    }
    
    $return_array = "";
    
    if (isset($_GET['settings'])){
        $settings = array("cmd_url"=>$cmd_url,"pnp_url"=>$pnp_url);      
        $return_array['settings'] = $settings;        
    }
    
    if (isset($_GET['status'])){
       $hosts = json_decode(run_query("GET columns\nOutputFormat: json\n\n"));       
       echo "<table>";
       $last_table = "table";            
       foreach($hosts as $host){
          if ($host[2] != $last_table)
              echo "<tr><td COLSPAN='4' ALIGN='middle' style='background-color:red;'>".$host[2]."</td></tr>";
          $last_table = $host[2];          
          echo "<tr><td>".$host[0]."</td><td>".$host[1]."</td><td>".$host[2]."</td><td>".$host[3]."</td></tr>";
       }              
       echo "</table>";
       
    }
    
     if (isset($_GET['get_pinned'])){
        $browse_items[] = array("type"=>"list","target"=>"pin_target","id"=>"pinned_list");        
        $pinned_items = explode(",",$_GET['get_pinned']);
            foreach($pinned_items as $pinned_item){
                $dat = explode("|",$pinned_item);
                $host = $dat[0];
                $service_name = $dat[1];
                if ($host != "" && $service_name != ""){
                $filter = $filter = "\nFilter: host_name = ".$host."\nFilter: display_name = ".$service_name;
                $data = json_decode(run_query("GET services\nColumns: display_name host_name plugin_output state host_address host_comments custom_variable_names custom_variable_values host_address host_last_check host_last_time_down host_last_time_unreachable host_last_time_up $filter $authuser\nOutputFormat: json\n\n"));
                if (is_array($data[0])){                
                $service = $data[0];    
                $image = "images/service.png";            
                if (is_array($service[6])){
                  foreach($service[6] as $key=>$value){                      
                      if ($value == "JNAG_IMAGE")
                        $image = str_replace("\$SERVER$",$images_url,$service[7][$key]);                                             
                  }                
                 }                                 
                 $variable = $service[1]."|".$service[0];
                 if ($service[3] != 0){
                    $colour = "warn";
                 } else {
                    $colour = "fine";
                 }              
                $browse_items[] = array("heading"=>$service[0],"text"=>$service[2],"type"=>"service","variable"=>$variable,"count"=>"1-","colour"=>$colour,"target"=>"pinned_list", "image"=>$image);
                }
            }
            }
            $return_array['browse_items'] = $browse_items;
        } 
     
    
    if (isset($_GET['browse'])){
        $type = $_GET['type'];                
        
        if ($type == "top"){
           $browse_items[] = array("type"=>"clear","target"=>"top_target");
           $browse_items[] = array("type"=>"list","target"=>"top_target","id"=>"top_list");        
           $count = count(json_decode(run_query("GET hostgroups \nColumns: name $authuser\nOutputFormat: json\n\n")));           
           if ($count > 0){           
              $browse_items[] = array("heading"=>"HostGroups","text"=>"","type"=>"hostgroups","variable"=>"","count"=>$count,"target"=>"top_list", "image"=>"images/hostgroup.png");
           }
           $hosts = json_decode(run_query("GET hosts \nColumns: name state current_attempt hard_state$authuser\nOutputFormat: json\n\n"));
           $count = 0;
           $problems = 0;
           foreach ($hosts as $host){
              $count++;
              if ($host[1] != 0 || $host[2] != 1 || $host[3] != 0)
                $problems++;
           }
           if ($count > 0){           
              $browse_items[] = array("heading"=>"Hosts","text"=>$problems." Host Problems","type"=>"hosts","variable"=>"","count"=>$count,"target"=>"top_list", "image"=>"images/host.png");
           }
           
           $count = count(json_decode(run_query("GET servicegroups \nColumns: name\nAuthUser: $username\nOutputFormat: json\n\n")));
           if ($count > 0){           
              $browse_items[] = array("heading"=>"ServiceGroups","text"=>"","type"=>"servicegroups","variable"=>"","count"=>$count,"target"=>"top_list", "image"=>"images/servicegroup.png");
           }           
           $services = json_decode(run_query("GET services \nColumns: display_name state\nFilter: display_name !~ Generic Event$authuser\nOutputFormat: json\n\n"));
           $count = 0;
           $problems = 0;
           foreach ($services as $service){
              $count++;
              if ($service[1] != 0)
                $problems++;
           }
           if ($count > 0){           
              $browse_items[] = array("heading"=>"Services","text"=>$problems." Service Problems","type"=>"services","variable"=>"","count"=>$count,"target"=>"top_list", "image"=>"images/service.png");
           }
                      
                      
        }        
        
        if ($type == "hostgroups"){
            $browse_items[] = array("type"=>"create_page","id"=>"hostgroups","title"=>"hostgroups","show_problems"=>true);            
            $browse_items[] = array("type"=>"list","target"=>"hostgroups_target","id"=>"hostgroups_list");
            $data = json_decode(run_query("GET hostgroups \nColumns: name num_hosts num_hosts_down num_hosts_unreach$authuser\nOutputFormat: json\n\n"));
            foreach($data as $hostgroup){
                $problems = $hostgroup[2] + $hostgroup[3];
                $browse_items[] = array("heading"=>$hostgroup[0],"text"=>$problems." Host Problems","type"=>"hosts","variable"=>$hostgroup[0],"count"=>$hostgroup[1],"target"=>"hostgroups_list","image"=>"images/hostgroup.png");
            }
        }
        if ($type == "hosts"){
            $browse_items[] = array("type"=>"create_page","id"=>"hosts","title"=>"Hosts","show_problems"=>true);            
            $browse_items[] = array("type"=>"list","target"=>"hosts_target","id"=>"hosts_list");
            if (isset($_GET['variable']) && $_GET['variable'] != ""){
                  $filter = "\nFilter: groups >= ".$_GET['variable'];  
            } else {
                  $filter = "";
            }            
            $data = json_decode(run_query("GET hosts \nColumns: name num_services current_attempt hard_state custom_variable_names custom_variable_values plugin_output $filter$authuser\nOutputFormat: json\n\n"));
            foreach ($data as $host){
                if ($host[2] != 1 || $host[3] != 0){
                    $colour = "warn";
                } else {
                    $colour = "fine";
                }
                $image = "images/host.png";
                if (is_array($host[4])){
                  foreach($host[4] as $key=>$value){                      
                      if ($value == "JNAG_IMAGE")
                        $image = str_replace("\$SERVER$",$images_url,$host[5][$key]);                        
                  }                
                }   
                $browse_items[] = array("heading"=>$host[0],"text"=>$host[6] ,"type"=>"services","variable"=>"host|".$host[0],"count"=>$host[1],"colour"=>$colour,"target"=>"hosts_list","image"=>$host[4], "image"=>$image);
            }
        }
        
        if ($type == "servicegroups"){
            $browse_items[] = array("type"=>"create_page","id"=>"servicegroups","title"=>"ServiceGroups","show_problems"=>true);            
            $browse_items[] = array("type"=>"list","target"=>"servicegroups_target","id"=>"servicegroups_list");
            $data = json_decode(run_query("GET servicegroups \nColumns: name num_services num_services_ok$authuser\nOutputFormat: json\n\n"));            
            foreach($data as $servicegroup){
                $problems = $servicegroup[2] - $servicegroup[1];
                $browse_items[] = array("heading"=>$servicegroup[0],"text"=>$problems." Service problems","type"=>"services","variable"=>"groups|".$servicegroup[0],"count"=>$servicegroup[1],"target"=>"servicegroups_list", "image"=>"images/servicegroup.png");
            }
        }
        
           if ($type == "services"){  
              $browse_items[] = array("type"=>"create_page","id"=>"services","title"=>"Services","show_problems"=>true);                                             
              if (isset($_GET['variable']) && $_GET['variable'] != ""){              
                  $var = explode("|",$_GET['variable']);
                  $filter_type = $var[0];                  
                  $filter_var = $var[1];                  
                  if ($filter_type == "host"){                     
                     $filter = "\nFilter: host_name = ".$filter_var;
                     
                  }
                  if ($filter_type == "groups"){                      
                     $filter = "\nFilter: groups >= ".$filter_var;
                  }
              } else {
                $filter = "";
              }                   
              $data = json_decode(run_query("GET services\nColumns: display_name host_name plugin_output state host_address host_comments custom_variable_names custom_variable_values host_address host_last_check host_last_time_down host_last_time_unreachable host_last_time_up $filter$authuser\nOutputFormat: json\n\n"));
              
              
              if ($filter_type == "host"){     
                  $address = $data[0][8];
                  $last_check = format_time($data[0][9]);
                  $last_down = format_time($data[0][10]);
                  $last_unreachable = format_time($data[0][11]);
                  $last_up  = format_time($data[0][12]);  
                  $browse_items[] = array("type"=>"text","heading"=>$data[0][1],"text"=>"<table><tr><td>Address: </td><td>".$data[0][8]."</td></tr><tr><td>Last Checked: </td><td>$last_check</td></tr><tr><td>Last Up: </td><td> $last_up</td></tr><tr><td>Last Down: </td><td>$last_down</td></tr><tr><td>Last Unreachable: </td><td>$last_unreachable</td></tr></table>","target"=>"services_target");          
                  if (is_array($data[0][5])){                                           
                     $comments = json_decode(run_query("GET comments\nColumns: author comment entry_time\nFilter: host_name = $filter_var\nOutputFormat: json\n\n"));
                     $browse_items[] = array("type"=>"list","target"=>"services_target","id"=>"comments_list");
                     $browse_items[] = array("text"=>"Comments","type"=>"header","target"=>"comments_list");
                     foreach($comments as $comment){
                         $browse_items[] = array("text"=>$comment[1],"type"=>"nolink","target"=>"comments_list");
                     }                     
                  }   
                  $browse_items[] = array("type"=>"browse_button","button_text"=>"Add Comment","button_type"=>"comment_host","button_variable"=>$filter_var,"target"=>"services_target");
                  $browse_items[] = array("type"=>"browse_button","button_text"=>"Re-check","button_type"=>"recheck_host","button_variable"=>$filter_var,"target"=>"services_target");               
                  $header_text = "Services on "; 
              } else {
                  $header_text = "";
              }             
              $browse_items[] = array("type"=>"list","target"=>"services_target","id"=>"services_list");
              $last_host = "";
              foreach($data as $service){ 
                 if ($service[0] != "Generic Event"){ 
                 $image = "images/service.png";              
                 if (is_array($service[6])){
                  foreach($service[6] as $key=>$value){                      
                      if ($value == "JNAG_IMAGE")
                        $image = str_replace("\$SERVER$",$images_url,$service[7][$key]);                                             
                  }                
                 }                
                 if ($service[1] != $last_host){
                      
                      $browse_items[] = array("text"=>$header_text.$service[1],"type"=>"header","variable"=>"","count"=>"-1","target"=>"services_list");
                 }
                 $variable = $service[1]."|".$service[0];
                 if ($service[3] != 0){
                    $colour = "warn";
                 } else {
                    $colour = "fine";
                 }
                 $browse_items[] = array("heading"=>$service[0],"text"=>$service[2],"type"=>"service","variable"=>$variable,"count"=>"1-","colour"=>$colour,"target"=>"services_list", "image"=>$image);
                 $last_host = $service[1]; 
              }
        }
        }
        
        if ($type == "service"){            
            $browse_items[] = array("type"=>"create_page","id"=>"service","title"=>"Service","show_problems"=>true);                        
            $arr = explode("|",$_GET['variable']);
            $host = $arr[0];
            $service_name = $arr[1];            
            $data = json_decode(run_query("GET services\nColumns: host_name display_name host_address acknowledged comments last_check last_time_ok last_time_unknown last_time_warning last_time_critical plugin_output\nFilter: host_name = $host\nFilter: display_name = $service_name $authuser\nOutputFormat: json\n\n"));
            
            $last_check = format_time($data[0][5]);
            $last_ok = format_time($data[0][6]);
            $last_warning = format_time($data[0][8]);
            $last_critical = format_time($data[0][9]);                        
            
            //this could do with chaging to 4 seperate lines, rather than one table
            $browse_items[] = array("type"=>"text","heading"=>$service_name." on ".$host,"text"=>"<table><tr><td>Output: </td><td>".$data[0][10]."</td></tr><tr><td>Last Checked: </td><td>$last_check</td></tr><tr><td>Last OK: </td><td> $last_ok</td></tr><tr><td>Last Warning: </td><td>$last_warning</td></tr><tr><td>Last Critical: </td><td>$last_critical</td></tr></table>","target"=>"service_target");
            if (is_array($data[0][4])){                                         
                     $comments = json_decode(run_query("GET comments\nColumns: author comment entry_time\nFilter: host_name = $host\nFilter: service_display_name = $service_name $authuser \nOutputFormat: json\n\n"));
                     $browse_items[] = array("type"=>"list","target"=>"service_target","id"=>"comments_list");
                     $browse_items[] = array("text"=>"Comments","type"=>"header","target"=>"comments_list");
                     foreach($comments as $comment){
                         $browse_items[] = array("text"=>$comment[1],"type"=>"nolink","target"=>"comments_list");
                     }
            }              
            $browse_items[] = array("type"=>"browse_button","button_text"=>"Add Comment","button_type"=>"comment_service","button_variable"=>$_GET['variable'],"target"=>"service_target");
            $browse_items[] = array("type"=>"browse_button","button_text"=>"Acknowledge","button_type"=>"acknowledge_service","button_variable"=>$_GET['variable'],"target"=>"service_target");
            $browse_items[] = array("type"=>"browse_button","button_text"=>"Re-check","button_type"=>"recheck_service","button_variable"=>$_GET['variable'],"target"=>"service_target");
            $browse_items[] = array("type"=>"browse_button","button_text"=>"Pin to Home","button_type"=>"pin_button","button_variable"=>$_GET['variable'],"target"=>"service_target");
            $browse_items[] = array("type"=>"browse_button","button_text"=>"UnPin","button_type"=>"unpin_button","button_variable"=>$_GET['variable'],"target"=>"service_target");                      
            if ($pnp_enable == true){                          
              $browse_items[] = array("type"=>"text","heading"=>"last 24 hours","text"=>"","target"=>"service_target");
              $browse_items[] = array("type"=>"pnp","host"=>$host,"service"=>$service_name,"target"=>"service_target","pnp_view"=>"1");
              
              $browse_items[] = array("type"=>"text","heading"=>"last week","text"=>"","target"=>"service_target");
              $browse_items[] = array("type"=>"pnp","host"=>$host,"service"=>$service_name,"target"=>"service_target","pnp_view"=>"2");
              
              $browse_items[] = array("type"=>"text","heading"=>"last month","text"=>"","target"=>"service_target");
              $browse_items[] = array("type"=>"pnp","host"=>$host,"service"=>$service_name,"target"=>"service_target","pnp_view"=>"3");
              
              $browse_items[] = array("type"=>"text","heading"=>"last year","text"=>"","target"=>"service_target");
              $browse_items[] = array("type"=>"pnp","host"=>$host,"service"=>$service_name,"target"=>"service_target","pnp_view"=>"4");
            }                                  
            //$browse_items[] = array("type"=>"text","heading"=>"Last Check","text"=>$service[3]);            
        }
        
        if ($type == "recheck_service"){
            $data = explode("|",$_GET['variable']);
            $host_name = $data[1];
            $service_name = $data[3];                        
            $browse_items[] = array("type"=>"create_dialog","id"=>"acknowledge_dialog","title"=>"Re-Check");
            $browse_items[] = array("type"=>"form","id"=>"acknowledge_form","target"=>"acknowledge_dialog_target");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_typ","val"=>"7","target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_mod","val"=>"2","target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"service","val"=>$service_name,"target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"host","val"=>$host_name,"target"=>"acknowledge_form");
            $start_time = date("m-d-Y H:i:s");
            $browse_items[] = array("type"=>"input_text","id"=>"start_time","text"=>"Time","target"=>"acknowledge_form","val"=>"$start_time");            
            $browse_items[] = array("type"=>"cmd_button","id"=>"acknowledge_form","target"=>"acknowledge_form");
        }
        
        if ($type == "recheck_host"){
            $data = explode("|",$_GET['variable']);
            $host_name = $data[1];                                    
            $browse_items[] = array("type"=>"create_dialog","id"=>"acknowledge_dialog","title"=>"Re-Check");
            $browse_items[] = array("type"=>"form","id"=>"acknowledge_form","target"=>"acknowledge_dialog_target");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_typ","val"=>"96","target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_mod","val"=>"2","target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"host","val"=>$host_name,"target"=>"acknowledge_form");
            $start_time = date("m-d-Y H:i:s");
            $browse_items[] = array("type"=>"input_text","id"=>"start_time","text"=>"Time","target"=>"acknowledge_form","val"=>"$start_time");            
            $browse_items[] = array("type"=>"cmd_button","id"=>"acknowledge_form","target"=>"acknowledge_form");
        }
        
        if ($type == "acknowledge_service"){
            $data = explode("|",$_GET['variable']);
            $host_name = $data[1];
            $service_name = $data[3];                        
            $browse_items[] = array("type"=>"create_dialog","id"=>"acknowledge_dialog","title"=>"Acknowledge");
            $browse_items[] = array("type"=>"form","id"=>"acknowledge_form","target"=>"acknowledge_dialog_target");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_typ","val"=>"34","target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_mod","val"=>"2","target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"service","val"=>$service_name,"target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"host","val"=>$host_name,"target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_text","id"=>"com_author","text"=>"Author","target"=>"acknowledge_form","val"=>"");
            $browse_items[] = array("type"=>"input_text","id"=>"com_data","text"=>"Comment","target"=>"acknowledge_form","val"=>"");
            $browse_items[] = array("type"=>"cmd_button","id"=>"acknowledge_form","target"=>"acknowledge_form"); 
        }
        
        if ($type == "acknowledge_host"){
            $data = explode("|",$_GET['variable']);
            $host_name = $data[1];                        
            $browse_items[] = array("type"=>"create_dialog","id"=>"acknowledge_dialog","title"=>"Acknowledge");
            $browse_items[] = array("type"=>"form","id"=>"acknowledge_form","target"=>"acknowledge_dialog_target");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_typ","val"=>"33","target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_mod","val"=>"2","target"=>"acknowledge_form");            
            $browse_items[] = array("type"=>"input_hidden","id"=>"host","val"=>$host_name,"target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_text","id"=>"com_author","text"=>"Author","target"=>"acknowledge_form","val"=>"");
            $browse_items[] = array("type"=>"input_text","id"=>"com_data","text"=>"Comment","target"=>"acknowledge_form","val"=>"");
            $browse_items[] = array("type"=>"cmd_button","id"=>"acknowledge_form","target"=>"acknowledge_form"); 
        }
        
        if ($type == "comment_host"){            
            $host_name = $_GET['variable'];                        
            $browse_items[] = array("type"=>"create_dialog","id"=>"acknowledge_dialog","title"=>"Comment");
            $browse_items[] = array("type"=>"form","id"=>"acknowledge_form","target"=>"acknowledge_dialog_target");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_typ","val"=>"1","target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_mod","val"=>"2","target"=>"acknowledge_form");            
            $browse_items[] = array("type"=>"input_hidden","id"=>"host","val"=>$host_name,"target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_text","id"=>"com_author","text"=>"Author","target"=>"acknowledge_form","val"=>"");
            $browse_items[] = array("type"=>"input_text","id"=>"com_data","text"=>"Comment","target"=>"acknowledge_form","val"=>"");
            $browse_items[] = array("type"=>"cmd_button","id"=>"acknowledge_form","target"=>"acknowledge_form"); 
        }
        
        if ($type == "comment_service"){            
            $data = explode("|",$_GET['variable']);
            $host = $data[0];
            $service = $data[1];                        
            $browse_items[] = array("type"=>"create_dialog","id"=>"acknowledge_dialog","title"=>"Comment");
            $browse_items[] = array("type"=>"form","id"=>"acknowledge_form","target"=>"acknowledge_dialog_target");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_typ","val"=>"3","target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"cmd_mod","val"=>"2","target"=>"acknowledge_form");            
            $browse_items[] = array("type"=>"input_hidden","id"=>"host","val"=>$host,"target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_hidden","id"=>"service","val"=>$service,"target"=>"acknowledge_form");
            $browse_items[] = array("type"=>"input_text","id"=>"com_author","text"=>"Author","target"=>"acknowledge_form","val"=>"");
            $browse_items[] = array("type"=>"input_text","id"=>"com_data","text"=>"Comment","target"=>"acknowledge_form","val"=>"");
            $browse_items[] = array("type"=>"cmd_button","id"=>"acknowledge_form","target"=>"acknowledge_form"); 
        }
                
        $return_array['browse_items'] = $browse_items;
    }
    
    if (isset($_GET['count_problems'])){
        $count = 0;
        $dat = run_query("GET hosts \nColumns: name \nFilter: acknowledged = 0 \nFilter: state != 0\nFilter: hard_state != 0 \nFilter: current_attempt > 1 \nOr: 3 $authuser\nOutputFormat: json\n\n");         
         $count += count(json_decode($dat));
         
         $dat = run_query("GET services \nColumns: display_name \nFilter: acknowledged = 0 \nFilter: state != 0$authuser\nOutputFormat: json\n\n");         
         
         $count += count(json_decode($dat));  
         
         $return_array['problem_count'] = "$count";  
    }
    
    
    if (isset($_GET['load_problems'])){
         $problems = "";
         $dat = run_query("GET hosts \nColumns: name plugin_output \nFilter: acknowledged = 0 \nFilter: state != 0\nFilter: hard_state != 0 \nFilter: current_attempt > 1 \nOr: 3 $authuser\nOutputFormat: json\n\n");
         $data = json_decode($dat);         
         foreach($data as $this_host){
            $name = $this_host[0];
            $output = $this_host[1];
            $problems[] = array("host"=>$name,"service"=>"_host","type"=>"host","plugin_output"=>$output);
         }
         
         $dat =  run_query("GET services \nColumns: host_name display_name plugin_output \nFilter: acknowledged = 0 \nFilter: state != 0$authuser\nOutputFormat: json\n\n");
          
          $data =  json_decode($dat);
          foreach($data as $this_host){
              $hostname = $this_host[0];
              $display_name = $this_host[1];
              $output = $this_host[2];
              $problems[] = array("host"=>$hostname,"service"=>$display_name,"type"=>"service","plugin_output"=>$output);
          }
         
         //check if any results
          if (count($problems) > 0){
         //sort results
          foreach ($problems as $key => $row) {
           $host[$key]  = $row['host'];   
           $service[$key] = $row['service'];       
          }
          array_multisort($host, SORT_DESC, $service, SORT_DESC, $problems);
         
          $return_array['problems'] = $problems;
         } else {
            $problems[] = array("host"=>"","service"=>"","type"=>"none","plugin_output"=>"");
            $return_array['problems'] = $problems;
            
         }
    
    }

    if (isset($_GET['callback'])){    
      echo $_GET['callback']."(".json_encode($return_array).")";
    } else {
      echo  json_encode($return_array);
    }