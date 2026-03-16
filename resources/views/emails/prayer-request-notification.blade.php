<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Prayer Request</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #357be4; text-align: center;">New Prayer Request Submitted</h2>
        
        <p>A new prayer request has been submitted:</p>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Name:</strong> {{ $prayerRequest->name ?? 'Anonymous' }}</p>
            <p><strong>Email:</strong> {{ $prayerRequest->email ?? 'Not provided' }}</p>
            <p><strong>Chapter:</strong> {{ $prayerRequest->chapter->name ?? 'N/A' }}</p>
            <p><strong>Submitted:</strong> {{ $prayerRequest->created_at->format('M d, Y H:i A') }}</p>
        </div>
        
        <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 3px;">
            <h4>Prayer Request:</h4>
            <p style="white-space: pre-wrap;">{{ $prayerRequest->request }}</p>
        </div>
        
        <p style="color: #666; font-size: 12px; text-align: center; margin-top: 30px;">
            Please respond to this prayer request as soon as possible.
        </p>
    </div>
</body>
</html>
