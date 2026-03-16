<?php

use App\Models\{PasswordResetRequest, User};
use App\Notifications\PasswordResetApprovedNotification;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Str;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination;

    public function getRequests()
    {
        return PasswordResetRequest::with('user')
            ->latest()
            ->paginate(15);
    }

    public function approveRequest($id)
    {
        $request = PasswordResetRequest::findOrFail($id);

        if ($request->status === 'approved') {
            $this->toast()->warning('Already Approved', 'This request has already been approved')->send();
            return;
        }

        // Generate new token for the reset
        $token = Str::random(60);

        $request->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'token' => $token, // Refresh token
        ]);

        // Generate reset URL
        $resetUrl = route('password.reset', ['token' => $token]);

        // Send notification to user with reset link
        $request->user->notify(new PasswordResetApprovedNotification($request, $resetUrl));

        $this->toast()->success('✅ Approved', 'Password reset request approved. User has been notified.')->send();
    }

    public function rejectRequest($id)
    {
        $request = PasswordResetRequest::findOrFail($id);

        $request->update([
            'status' => 'rejected',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $this->toast()->success('Rejected', 'Password reset request rejected')->send();
    }

    public function deleteRequest($id)
    {
        PasswordResetRequest::findOrFail($id)->delete();
        $this->toast()->success('Deleted', 'Request deleted successfully')->send();
    }

    public function with()
    {
        return [
            'headers' => [
                ['index' => 'user.name', 'label' => 'User'],
                ['index' => 'email', 'label' => 'Email'],
                ['index' => 'status', 'label' => 'Status'],
                ['index' => 'created_at', 'label' => 'Requested'],
                ['index' => 'actions', 'label' => 'Actions']
            ],
            'rows' => $this->getRequests(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">Password Reset Requests</h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Review and approve password reset requests</p>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
        <x-table :$headers :$rows>
            @interact('column_status', $row)
                @php
                    $colors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        'used' => 'bg-gray-100 text-gray-800',
                    ];
                    $badgeClass = 'px-3 py-1 rounded-full text-xs font-semibold ' . ($colors[$row->status] ?? 'bg-gray-100 text-gray-800');
                @endphp
                <span class="{{ $badgeClass }}">{{ ucfirst($row->status) }}</span>
            @endinteract

            @interact('column_created_at', $row)
                <div>
                    <p class="text-sm font-medium">{{ $row->created_at->format('M d, Y') }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->created_at->format('h:i A') }}</p>
                </div>
            @endinteract

            @interact('column_actions', $row)
                <div class="flex gap-2">
                    @if($row->status === 'pending')
                        <x-button.circle color="green" icon="check" wire:click="approveRequest({{ $row->id }})"
                            title="Approve Request" />
                        <x-button.circle color="red" icon="x" wire:click="rejectRequest({{ $row->id }})"
                            title="Reject Request" />
                    @endif
                    <x-button.circle color="red" icon="trash" wire:click="deleteRequest({{ $row->id }})" />
                </div>
            @endinteract
        </x-table>
    </div>
</div>
