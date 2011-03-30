package uk.co.tall_paul.jnag.paid;

import java.io.BufferedInputStream;
import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.Authenticator;
import java.net.HttpURLConnection;
import java.net.PasswordAuthentication;
import java.net.URL;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSession;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;

import android.content.Context;
import android.content.ContextWrapper;
import android.util.Log;

public class webGetter {
	
	private Context context;
	private settingsClass sc;
	
	 public webGetter(Context myContext){
	        context = myContext;
	    } 
	 
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

	
	
	public String get(String parameters){
		String returnedVal;
		
		//get url, username, password
        sc = new settingsClass(context);
        String data_url = sc.getSetting("data_url");
        final String password = sc.getSetting("password");
        final String username = sc.getSetting("username");
        Log.d("jNag","Get: " + data_url + parameters);
        //Read problem count
        Authenticator.setDefault(new Authenticator(){
            protected PasswordAuthentication getPasswordAuthentication() {
                return new PasswordAuthentication(username,password.toCharArray());
            }});
        HttpURLConnection c;       
        try {
			//try to connect using saved credentials and URL
        	if (data_url.toLowerCase().contains("https")){
        		trustAllHosts();
        		c = (HttpsURLConnection) new URL(data_url + parameters).openConnection();
        		((HttpsURLConnection) c).setHostnameVerifier(DO_NOT_VERIFY);
        	} else {
        		c = (HttpURLConnection) new URL(data_url + parameters).openConnection();
        	}
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
			Log.d("jNag","connection error " + e.getLocalizedMessage() + "in webgetter");
			return "";
		}
		Log.d("jNag","webGetter returning: " + returnedVal);
		return returnedVal.replace("\\", "").trim();
	}
	
}
