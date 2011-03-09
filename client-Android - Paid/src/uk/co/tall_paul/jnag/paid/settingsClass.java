package uk.co.tall_paul.jnag.paid;

import android.app.Activity;
import android.content.Context;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.webkit.WebView;

import com.phonegap.*;
import uk.co.tall_paul.jnag.paid.R;

public class settingsClass {
	private Context settingsContext;
    
    public settingsClass(Context myContext){
        settingsContext = myContext;
    }    
        
    public void setSetting(String key, String val){    	  
    	Log.w("jNag","writing " + val + " to " + key);
    	SharedPreferences settings = settingsContext.getSharedPreferences("phoneGap", 0);    	
    	SharedPreferences.Editor editor = settings.edit();    	
    	editor.putString(key, val);
    	editor.commit();
    }
    
    public String getSetting(String key){
    	Log.w("jNag", "Setting " + key + " requested");
    	SharedPreferences settings = settingsContext.getSharedPreferences("phoneGap", 0);
    	return settings.getString(key,"");
    }
}
