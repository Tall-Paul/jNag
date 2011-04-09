package uk.co.tall_paul.jnag.paid;

import java.io.BufferedInputStream;


import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.Authenticator;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.PasswordAuthentication;
import java.net.URL;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import java.util.Calendar;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSession;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;

import org.json.JSONException;
import org.json.JSONObject;



import android.app.AlarmManager;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.appwidget.AppWidgetManager;
import android.appwidget.AppWidgetProvider;
import android.content.BroadcastReceiver;
import android.content.ComponentName;
import android.content.Context;
import android.content.ContextWrapper;
import android.content.Intent;
import android.content.res.Resources;
import android.os.AsyncTask;
import android.os.IBinder;
import android.os.SystemClock;
import android.util.Log;
import android.widget.RemoteViews;
import android.widget.TextView;


public class jnag_widgetinfo extends AppWidgetProvider {
	
	// always verify the host - dont check for certificate
	 final static HostnameVerifier DO_NOT_VERIFY = new HostnameVerifier() {
	         public boolean verify(String hostname, SSLSession session) {
	                 return true;
	         }
	 };

	 /**
	  * Trust every server - dont check for any certificate
	  */
	 private static void trustAllHosts() {
	         // Create a trust manager that does not validate certificate chains
	         TrustManager[] trustAllCerts = new TrustManager[] { 
	        		 new X509TrustManager() {
	        			 public java.security.cert.X509Certificate[] getAcceptedIssuers() {
	                        return new java.security.cert.X509Certificate[] {};
	        			 }
	        			 public void checkClientTrusted(X509Certificate[] chain, String authType) throws CertificateException {
	        			 }

	                 public void checkServerTrusted(X509Certificate[] chain,
	                                 String authType) throws CertificateException {
	                 }
	         } };

	         // Install the all-trusting trust manager
	         try {
	                 SSLContext sc = SSLContext.getInstance("TLS");
	                 sc.init(null, trustAllCerts, new java.security.SecureRandom());
	                 HttpsURLConnection
	                                 .setDefaultSSLSocketFactory(sc.getSocketFactory());
	         } catch (Exception e) {
	                 e.printStackTrace();
	         }
	 }
	
	
	@Override
	public void onUpdate(Context context, AppWidgetManager appWidgetManager, int[] appWidgetIds) {        	
	// To prevent any ANR timeouts, we perform the update in a service
    
    settingsClass sc = new settingsClass(context);
    String service_enabled = sc.getSetting("Service_enabled");
    if (service_enabled == "1"){
    	Log.w("jNag","Attempting service start");
    	context.startService(new Intent(context, UpdateService.class));
      }
    }
	
	private ContextWrapper getApplicationContext() {
		// TODO Auto-generated method stub
		return null;
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
            //stopSelf();
        }

        @Override
        public IBinder onBind(Intent intent) {
            return null;
        }
        
        public RemoteViews buildUpdate(Context context) {
            //get url, username, password
            sc = new settingsClass(context);
            String data_url = sc.getSetting("data_url");
            final String password = sc.getSetting("password");
            final String username = sc.getSetting("username");
            //Read problem count
            Authenticator.setDefault(new Authenticator(){
                protected PasswordAuthentication getPasswordAuthentication() {
                    return new PasswordAuthentication(username,password.toCharArray());
                }});
            HttpURLConnection c;
            //we use -1 as an error condition here
            String returnedVal = "{\"problem_count\":\"-1\"}";
            int problem_count = -1;
            int use_drawable = R.drawable.unknown;
    		try {
    			//try to connect using saved credentials and URL
    			if (data_url.toLowerCase().contains("https")){
            		trustAllHosts();
            		c = (HttpsURLConnection) new URL(data_url + "?count_problems=true").openConnection();
            		((HttpsURLConnection) c).setHostnameVerifier(DO_NOT_VERIFY);
            	} else {
            		c = (HttpURLConnection) new URL(data_url + "?count_problems=true").openConnection();
            	}
    			c.setConnectTimeout(1500);
    			InputStream in = new BufferedInputStream(c.getInputStream());		    
    		    BufferedReader r = new BufferedReader(new InputStreamReader(in));
    			StringBuilder total = new StringBuilder();
    			String line;
    			while ((line = r.readLine()) != null) {
    			    total.append(line);
    			}
    			returnedVal = total.toString();
    			c.disconnect();
    		} catch (Exception e) {
    			Log.d("jNag","connection error " + e.getLocalizedMessage() );
    		}		
    		try {
    			//try to parse returned json string
    			JSONObject json;
    			json = new JSONObject(returnedVal);
    			String problem_count_string = json.getString("problem_count");
    			problem_count = Integer.parseInt(problem_count_string);
    		} catch (JSONException e) {
    			Log.d("jNag","JSON ERROR");
    		}
    		//if problem_count is still -1 here, we've hit an error somewhere
    		if (problem_count == 0){
    			use_drawable = R.drawable.icon;
    		} else {
    			if (problem_count > 0){
    				//fire notification
    				String enabled = sc.getSetting("Notifications_enabled");
    				if (enabled == "1"){
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
    				}
    				use_drawable = R.drawable.problem;
    			} else {
    				use_drawable = R.drawable.unknown;
    			}
    		}
    		RemoteViews views = new RemoteViews(context.getPackageName(), R.layout.jnag_widgetlayout);
    		Intent intent = new Intent(context, App.class);
            PendingIntent pendingIntent = PendingIntent.getActivity(context, 0, intent, 0);            
    		views.setOnClickPendingIntent(R.id.imageView1, pendingIntent);    	
            views.setImageViewResource(R.id.imageView1, use_drawable);            
            return views;
        }

	


}
}
