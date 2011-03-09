package uk.co.tall_paul.jnag.paid;

import android.app.Activity;
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
		if (notifications_enabled == "1"){
			checkBox.setChecked(true);
		} else {
			checkBox.setChecked(false);
		}
		
		String service_enabled = sc.getSetting("Service_enabled"); 
		final CheckBox service_checkbox = (CheckBox) findViewById(R.id.service_checkbox);
		if (service_enabled == "1"){
			service_checkbox.setChecked(true);
		} else {
			service_checkbox.setChecked(false);
		}
		
	}
	
	public void togglenotifications(View v){
		sc = new settingsClass(this.getApplicationContext());
		String enabled = sc.getSetting("Notifications_enabled");
		CheckBox checkBox = (CheckBox) v;
		if (enabled == "1"){
			sc.setSetting("Notifications_enabled", "0");
			checkBox.setChecked(false);
		} else {
			sc.setSetting("Notifications_enabled", "1");
			checkBox.setChecked(true);
		}		
	}
	
	public void toggleservice(View v){
		sc = new settingsClass(this.getApplicationContext());
		String enabled = sc.getSetting("Settings_enabled");
		CheckBox checkBox = (CheckBox) v;
		if (enabled == "1"){
			sc.setSetting("Service_enabled", "0");
			checkBox.setChecked(false);
		} else {
			sc.setSetting("Service_enabled", "1");
			checkBox.setChecked(true);
		}		
	}
}
