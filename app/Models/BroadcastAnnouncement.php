<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastAnnouncement extends Model
{
    protected $fillable = [
        'title',
        'message',
        'status',
        'send_at',
        'channel',
        'chapter_id',
        'sent_at',
        'target_type',
        'target_audience',
        'creator_type',
        'created_by',
    ];

    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForAdminDashboard($query)
    {
        return $query->whereIn('target_type', ['admin_dashboard', 'both']);
    }

    public function scopeForUserToast($query)
    {
        return $query->whereIn('target_type', ['user_toast', 'both']);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeForAllUsers($query)
    {
        return $query->where('target_audience', 'all_users');
    }

    public function scopeForAdmins($query)
    {
        return $query->where('target_audience', 'admins');
    }

    public function scopeForTeamLeads($query)
    {
        return $query->where('target_audience', 'team_leads');
    }

    public function scopeForUser($query, $user)
    {
        return $query->sent()->forUserToast()
            ->where(function ($q) use ($user) {
                $q->where('creator_type', 'super_admin')
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('creator_type', 'admin')
                            ->where('chapter_id', $user->chapter_id);
                    });
            });
    }

    public function scopeForAdmin($query, $user)
    {
        return $query->forAdminDashboard()
            ->where(function ($q) use ($user) {
                // Super admin sees all
                if ($user->hasRole('super-admin')) {
                    return;
                }
                // Admin sees their own branch announcements + super admin announcements
                $q->where('creator_type', 'super_admin')
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('creator_type', 'admin')
                            ->where('chapter_id', $user->chapter_id);
                    });
            });
    }
}
