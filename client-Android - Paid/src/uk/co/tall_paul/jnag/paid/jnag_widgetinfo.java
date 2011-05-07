package uk.co.tall_paul.jnag.paid;



import org.json.JSONException;
import org.json.JSONObject;



import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.appwidget.AppWidgetManager;
import android.appwidget.AppWidgetProvider;
import android.content.ComponentName;
import android.content.Context;
import android.content.Intent;
import android.os.IBinder;
import android.util.Log;
import android.widget.RemoteViews;


public class jnag_widgetinfo extends AppWidgetProvider {
	
		
	
	
	@Override
	public void onUpdate(Context context, AppWidgetManager appWidgetManager, int[] appWidgetIds) {        	
	// To prevent any ANR timeouts, we perform the update in a service
    
    settingsClass sc = new settingsClass(context);
    String service_enabled = sc.getSetting("Service_enabled");
    	context.startService(new Intent(context, UpdateService.class));
    }
	

	
	

	public static class UpdateService extends Service {
		private settingsClass sc;
		
		@Override
        public void onStart(Intent intent, int startId) {
            Log.d("jNag", "onStart()");

            // Build the widget update for today
            RemoteViews updateViews = buildUpdate(this);
            Log.d("jNag", "update built");
            
            // Push update for this widget to the home screen
            ComponentName thisWidget = new ComponentName(this, jnag_widgetinfo.class);
            AppWidgetManager manager = AppWidgetManager.getInstance(this);
            manager.updateAppWidget(thisWidget, updateViews);
            Log.d("jNag", "widget updated");
            Log.d("jNag", "Stopping service");
            stopSelf();
        }

        @Override
        public IBinder onBind(Intent intent) {
            return null;
        }
        
        public RemoteViews buildUpdate(Context context) {
            //get url, username, password
            String returnedVal = "{\"problem_count\":\"-1\"}";
            int problem_count = -1;
            int use_drawable = R.drawable.unknown;
            sc = new settingsClass(context);
    		try {
    			webGetter wg = new webGetter(context);
    			returnedVal = wg.get("?count_problems=true");
    			Log.d("jNag","returnedVal is" + returnedVal);
    		} catch (Exception e) {
    			Log.d("jNag","connection error " + e.getLocalizedMessage() );
    		}		
    		try {
    			//try to parse returned json string
    			JSONObject json;
    			json = new JSONObject(returnedVal);
    			String problem_count_string = json.getString("problem_count");
    			problem_count = Integer.parseInt(problem_count_string);
    			Log.d("jNag","Problem count is " + problem_count);
    		} catch (JSONException e) {
    			Log.d("jNag","JSON ERROR");
    		}
    		//if problem_count is still -1 here, we've hit an error somewhere
    		if (problem_count == 0){
    			use_drawable = R.drawable.icon;
    		} else {
    			if (problem_count > 0){
    				//fire notification
    				Log.d("jNag","problem count > 0");
    				String enabled = sc.getSetting("Notifications_enabled");
    				Log.d("jNag","Notifications enabled returned: " + enabled);
    				if (Integer.parseInt(enabled) == 1){
    					Log.d("jNag","Notifying");
    					String ns = Context.NOTIFICATION_SERVICE;
    					NotificationManager mNotificationManager = (NotificationManager) getSystemService(ns);
    					int icon = R.drawable.problem;        // icon from resources
    					CharSequence tickerText = "jNag Problems";              // ticker-text
    					long when = System.currentTimeMillis();         // notification time
    					//Context context = getApplicationContext();      // application Context
    					CharSequence contentTitle = "jNag";  // expanded message title
    					CharSequence contentText = "Problems detected";      // expanded message text
    					Intent notificationIntent = new Intent(this.getApplicationContext(), App.class);
    					PendingIntent contentIntent = PendingIntent.getActivity(this.getApplicationContext(), 0, notificationIntent, 0);
    					//the next two lines initialize the Notification, using the configurations above
    					Notification notification = new Notification(icon, tickerText, when);
    					notification.defaults |= Notification.DEFAULT_SOUND;
    					notification.defaults |= Notification.DEFAULT_VIBRATE;
    					notification.setLatestEventInfo(this.getApplicationContext(), contentTitle, contentText, contentIntent);
    					mNotificationManager.notify(1, notification);    					
    				} else {
    					Log.d("jNag","notifications are disabled");
    				}
    				Log.d("jNag","using problem icon");
    				use_drawable = R.drawable.problem;
    			} else {
    				Log.d("jNag","using unknown icon");
    				use_drawable = R.drawable.unknown;
    			}
    		}
    		Log.d("jNag","doing views stuff");
    		RemoteViews views = new RemoteViews(context.getPackageName(), R.layout.jnag_widgetlayout);
    		Intent intent = new Intent(context, App.class);
            PendingIntent pendingIntent = PendingIntent.getActivity(context, 0, intent, 0);            
    		views.setOnClickPendingIntent(R.id.imageView1, pendingIntent);    	
            views.setImageViewResource(R.id.imageView1, use_drawable);            
            return views;
        }

	


}
}
