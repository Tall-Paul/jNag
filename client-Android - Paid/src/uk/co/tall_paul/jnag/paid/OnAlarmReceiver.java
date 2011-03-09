package uk.co.tall_paul.jnag.paid;

import uk.co.tall_paul.jnag.paid.jnag_widgetinfo.UpdateService;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.util.Log;

public final class OnAlarmReceiver extends BroadcastReceiver {
	@Override
	public void onReceive(Context context, Intent intent) {		
		Log.d("jNag","Alarm received, starting updateservice");
		context.startService(new Intent(context, UpdateService.class));
	}
}
