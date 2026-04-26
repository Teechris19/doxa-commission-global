<?php

use App\Models\Chapter;
use App\Models\User;
use App\Notifications\TeamMemberAdded;
use App\Services\NotificationRecipients;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads, Interactions;

    #[Url]
    public $chapter; // query string (chapter name)

    public $user = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public $profile = [
        'first_name' => '',
        'last_name' => '',
        'gender' => '',
        'dob' => '',
        'phone' => '',
        'secondary_phone' => '',
        'email' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'baptism_status' => '',
        'membership_date' => '',
        'avatar' => null,
    ];

    public function save()
    {
        $this->validate([
            'user.name' => 'required|string|max:255',
            'user.email' => 'required|email|unique:users,email',
            'user.password' => 'required|min:6|confirmed',

            'profile.first_name' => 'nullable|string|max:255',
            'profile.last_name' => 'nullable|string|max:255',
            'profile.email' => 'nullable|email',
            'profile.phone' => 'nullable|string|max:20',
            'profile.avatar' => 'nullable|image|max:2048',
        ]);

        // Get chapter from query string by name
        $chapter = Chapter::where('name', $this->chapter)->firstOrFail();

        // Create User linked to chapter
        $user = User::create([
            'name' => $this->user['name'],
            'email' => $this->user['email'],
            'password' => Hash::make($this->user['password']),
            'chapter_id' => $chapter->id,
        ]);

        if (Auth::user()->hasRole(['team-lead', 'lead-assist', 'lead_assist'])) {
            $authUser = Auth::user();
            $leaderTeam = $authUser->teams()
                ->wherePivotIn('role_in_team', ['team-lead', 'lead-assist', 'lead_assist'])
                ->first();

            $leaderTeam->users()->attach($user->id);

            $recipients = (new NotificationRecipients())
                ->forTeamAndChapter($leaderTeam->id, $leaderTeam->chapter_id)
                ->merge([$user])
                ->unique('id');

            foreach ($recipients as $recipient) {
                $recipient->notify(new TeamMemberAdded($leaderTeam, $user));
            }
        }

        // Handle avatar upload
        if ($this->profile['avatar']) {
            $avatarPath              = $this->profile['avatar']->store('avatars', 'public');
            $this->profile['avatar'] = $avatarPath;
        }

        // Create Profile linked to User
        $user->profile()->create(array_merge($this->profile, [
            'chapter_id' => $chapter->id,
        ]));

        // Success Toast + Redirect
        $this->toast()
            ->success('Done!', 'Member created successfully!')
            ->flash()
            ->send();

        $this->redirectRoute(
            'admin.dashboard.members',
            ['chapter' => $chapter->name],
            navigate: true
        );

        $this->reset();
    }
};?>

<div>
    <x-card>
        <x-tab selected="Personal Info" scroll-on-mobile>
            {{-- ================== PERSONAL INFO ================== --}}
            <x-tab.items tab="Personal Info">
                <div class="grid md:grid-cols-2 gap-4 p-4">
                    <x-input label="First Name" wire:model.defer="profile.first_name" />
                    <x-input label="Last Name" wire:model.defer="profile.last_name" />
                    <x-select label="Gender" wire:model.defer="profile.gender">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </x-select>
                    <x-input type="date" label="Date of Birth" wire:model.defer="profile.dob" />
                </div>
            </x-tab.items>

            {{-- ================== CONTACT ================== --}}
            <x-tab.items tab="Contact">
                <div class="grid md:grid-cols-2 gap-4 p-4">
                    <x-input label="Phone" wire:model.defer="profile.phone" />
                    <x-input label="Secondary Phone" wire:model.defer="profile.secondary_phone" />
                    <x-input type="email" label="Email" wire:model.defer="profile.email" />
                    <x-input label="Address" wire:model.defer="profile.address" />
                    <x-input label="City" wire:model.defer="profile.city" />
                    <x-input label="State" wire:model.defer="profile.state" />
                    <x-input label="Country" wire:model.defer="profile.country" />
                </div>
            </x-tab.items>

            {{-- ================== CHURCH INFO ================== --}}
            <x-tab.items tab="Church Info">
                <div class="grid md:grid-cols-2 gap-4 p-4">
                    <x-select label="Baptism Status" wire:model.defer="profile.baptism_status">
                        <option value="">Select</option>
                        <option value="baptized">Baptized</option>
                        <option value="not baptized">Not Baptized</option>
                    </x-select>
                    <x-input type="date" label="Membership Date" wire:model.defer="profile.membership_date" />
                </div>
            </x-tab.items>

            {{-- ================== MEDIA ================== --}}
            <x-tab.items tab="Media">
                <div class="p-4">
                    <x-input type="file" label="Avatar" wire:model="profile.avatar" />
                </div>
            </x-tab.items>

            {{-- ================== USER ACCOUNT ================== --}}
            <x-tab.items tab="Account">
                <div class="grid md:grid-cols-2 gap-4 p-4">
                    <x-input label="Name" wire:model.defer="user.name" />
                    <x-input type="email" label="Email" wire:model.defer="user.email" />
                    <x-input type="password" label="Password" wire:model.defer="user.password" />
                    <x-input type="password" label="Confirm Password" wire:model.defer="user.password_confirmation" />
                </div>
            </x-tab.items>
        </x-tab>

        <div class="p-4">
            <x-button primary wire:click="save">Save</x-button>
        </div>

    </x-card>
</div>
