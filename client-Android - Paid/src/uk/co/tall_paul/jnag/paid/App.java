package uk.co.tall_paul.jnag.paid;

import java.util.Calendar;


import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.webkit.SslErrorHandler;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import com.phonegap.*;
import uk.co.tall_paul.jnag.paid.R;
import uk.co.tall_paul.jnag.paid.jnag_widgetinfo.UpdateService;

public class App extends DroidGap {
	
	private settingsClass sc;
	private webGetter wg;

	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
    	try{
        super.onCreate(savedInstanceState);
        super.init();        
        sc = new settingsClass(this.getApplicationContext());  
        wg = new webGetter(this.getApplicationContext());
        appView.addJavascriptInterface(sc,"phoneGapSettings");
        appView.addJavascriptInterface(wg, "webGetter");
        //set a ridiculously long timeout here, just in case
        super.loadUrlTimeoutValue = 120000;
        super.setIntegerProperty("splashscreen", R.drawable.splash);
        super.loadUrl("file:///android_asset/www/index.html",2000);
        String service_enabled = sc.getSetting("Service_enabled");
        if (service_enabled == "1"){
        	//attempt service start
        	Log.d("jNag","Attempting service start");
        	this.startService(new Intent(this, UpdateService.class));
        	//start alarm service
        	Log.d("jNag","Adding Alarm");        
      	  	AlarmManager mgr=(AlarmManager)this.getSystemService(Context.ALARM_SERVICE);
      	  	Intent myintent=new Intent("uk.co.tall_paul.jnag.paid.JNAG_REFRESH");
      	  	PendingIntent pi=PendingIntent.getBroadcast(this, 0, myintent, 0);
      	  	//get a Calendar object with current time
      	  	Calendar cal = Calendar.getInstance();
      	  	cal.setTimeInMillis(System.currentTimeMillis());
      	  	// add 5 minutes to the calendar object
      	  	cal.add(Calendar.MINUTE, 15);
      	  	mgr.setRepeating(AlarmManager.RTC_WAKEUP,cal.getTimeInMillis(),900000,pi);
        } else {
        	Log.d("jNag","service is not enabled");
        }
    	} catch(Exception e){
    		
    	}
    }
    
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.main_menu, menu);
        return true;
    }
    
    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        // Handle item selection
        switch (item.getItemId()) {       
        case R.id.quit:
            finish();
            return true;
        case R.id.home:
        	super.loadUrl("file:///android_asset/www/index.html");
            return true;
        case R.id.options:
        	super.loadUrl("javascript:open_config();");
        	return true;
        case R.id.refresh:
        	super.loadUrl("javascript:refresh_page();");
        	return true;
        case R.id.service:
        	Intent myintent=new Intent(App.this,service_settings.class);
        	startActivity(myintent);
        default:
            return super.onOptionsItemSelected(item);            
        }
    }
}