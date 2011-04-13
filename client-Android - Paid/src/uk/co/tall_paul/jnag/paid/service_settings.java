package uk.co.tall_paul.jnag.paid;

import java.util.Calendar;

import android.app.Activity;
import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.util.Log;
import android.os.Bundle;
import android.view.View;
import android.widget.CheckBox;

public class service_settings extends Activity{
	private settingsClass sc;
	
	@Override
    public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.service);
		sc = new settingsClass(this.getApplicationContext());
		
		String notifications_enabled = sc.getSetting("Notifications_enabled");
		final CheckBox checkBox = (CheckBox) findViewById(R.id.notification_checkbox);
		Log.d("jNag","notifications enabled = " + notifications_enabled);
		if (Integer.parseInt(notifications_enabled) == 1){
			Log.d("jNag","notifications are enabled");
			checkBox.setChecked(true);
		} else {
			Log.d("jNag","notifications are NOT enabled");
			checkBox.setChecked(false);
		}
		
		String service_enabled = sc.getSetting("Service_enabled").trim(); 
		final CheckBox service_checkbox = (CheckBox) findViewById(R.id.service_checkbox);
		Log.d("jNag","service_enabled = " + service_enabled);
		if (Integer.parseInt(service_enabled) == 1){
			Log.d("jNag","service is enabled");
			service_checkbox.setChecked(true);
		} else {
			Log.d("jNag","service is NOT enabled");
			service_checkbox.setChecked(false);
		}
		
	}
	
	public void togglenotifications(View v){
		sc = new settingsClass(this.getApplicationContext());
		String enabled = sc.getSetting("Notifications_enabled");
		CheckBox checkBox = (CheckBox) v;
		if (Integer.parseInt(enabled) == 1){
			sc.setSetting("Notifications_enabled", "0");
			checkBox.setChecked(false);
		} else {
			sc.setSetting("Notifications_enabled", "1");
			checkBox.setChecked(true);
		}		
	}
	
	public void toggleservice(View v){
		sc = new settingsClass(this.getApplicationContext());
		String enabled = sc.getSetting("Service_enabled");
		CheckBox checkBox = (CheckBox) v;
		if (Integer.parseInt(enabled) == 1){
			sc.setSetting("Service_enabled", "0");
			checkBox.setChecked(false);
			Intent myintent=new Intent("uk.co.tall_paul.jnag.paid.JNAG_REFRESH");
			PendingIntent pi=PendingIntent.getBroadcast(this, 0, myintent, 0);
			AlarmManager mgr=(AlarmManager)this.getSystemService(Context.ALARM_SERVICE);
			mgr.cancel(pi);
		} else {
			sc.setSetting("Service_enabled", "1");
			checkBox.setChecked(true);
			Log.d("jNag","Adding Alarm");        
      	  	AlarmManager mgr=(AlarmManager)this.getSystemService(Context.ALARM_SERVICE);
      	  	Intent myintent=new Intent("uk.co.tall_paul.jnag.paid.JNAG_REFRESH");
      	  	PendingIntent pi=PendingIntent.getBroadcast(this, 0, myintent, 0);
      	  	Calendar cal = Calendar.getInstance();
      	  	cal.setTimeInMillis(System.currentTimeMillis());
      	  	cal.add(Calendar.SECOND, 15);
      	  	mgr.setRepeating(AlarmManager.RTC_WAKEUP,cal.getTimeInMillis(),900000,pi);
		}		
	}
}
