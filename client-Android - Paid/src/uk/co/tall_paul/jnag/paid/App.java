package uk.co.tall_paul.jnag.paid;

import android.os.Bundle;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;

import com.phonegap.*;
import uk.co.tall_paul.jnag.paid.R;

public class App extends DroidGap {
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        super.loadUrl("file:///android_asset/www/index.html");        
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
        default:
            return super.onOptionsItemSelected(item);            
        }
    }
}