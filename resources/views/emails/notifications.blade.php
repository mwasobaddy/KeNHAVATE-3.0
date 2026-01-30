<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KeNHAVATE Notifications</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
        .notification-group { margin-bottom: 30px; }
        .notification-group h3 { color: #4F46E5; margin-bottom: 15px; border-bottom: 2px solid #4F46E5; padding-bottom: 5px; }
        .notification { background: white; padding: 15px; margin-bottom: 10px; border-radius: 6px; border-left: 4px solid #4F46E5; }
        .notification-title { font-weight: bold; margin-bottom: 5px; }
        .notification-time { color: #666; font-size: 0.9em; }
        .footer { text-align: center; margin-top: 20px; padding: 20px; background: #f1f1f1; border-radius: 8px; }
        .button { display: inline-block; background: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>KeNHAVATE</h1>
            <p>You have new notifications</p>
        </div>

        <div class="content">
            @foreach($groupedNotifications as $type => $notifications)
                <div class="notification-group">
                    <h3>{{ ucwords(str_replace('_', ' ', $type)) }}</h3>

                    @foreach($notifications as $notification)
                        <div class="notification">
                            <div class="notification-title">{{ $notification->title }}</div>
                            <p>{{ $notification->message }}</p>
                            <div class="notification-time">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="footer">
                <p>Stay engaged with your ideas and collaborations!</p>
                <a href="{{ url('/dashboard') }}" class="button">View Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>