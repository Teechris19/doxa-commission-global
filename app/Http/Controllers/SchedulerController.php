<?php

namespace App\Http\Controllers;

use App\Services\SchedulerTasks;
use Illuminate\Http\Request;

class SchedulerController extends Controller
{
    public function tick(Request $request, SchedulerTasks $tasks)
    {
        $token = config('app.scheduler_token');
        if (!$token || $request->query('token') !== $token) {
            abort(403, 'Unauthorized');
        }

        $eventReminders = $tasks->runEventReminders();
        $broadcasts = $tasks->runBroadcastAnnouncements();

        return response()->json([
            'ok' => true,
            'event_reminders' => $eventReminders,
            'broadcasts' => $broadcasts,
        ]);
    }
}
