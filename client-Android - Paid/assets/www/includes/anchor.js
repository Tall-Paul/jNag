
    //Grab our current Url
	   var url = window.location.toString();
	 //Remove anchor from url
	 var anchor_index = url.indexOf('#');
    if (anchor_index != -1) {
    	 window.location = url.substring(0, anchor_index);
    }
    