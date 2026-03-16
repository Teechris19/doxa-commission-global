<?php

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads, Interactions;

    #[Url] 
    public $chapter; // query string: chapter name

    #[Url] 
    public $member; // route parameter: user ID

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
        'marital_status' => '',
        'wedding_anniversary' => '',
        'occupation' => '',
        'employer' => '',
        'education_level' => '',
        'baptism_status' => '',
        'membership_date' => '',
        'avatar' => null,
    ];

    public function mount()
    {
        $user = User::with('profile')->findOrFail($this->member);

        $this->user['name']  = $user->name;
        $this->user['email'] = $user->email;

        if ($user->profile) {
            $this->profile = array_merge($this->profile, $user->profile->toArray());
        }
    }

    public function save()
    {
        $user = User::findOrFail($this->member);

        $this->validate([
            'user.name' => 'required|string|max:255',
            'user.email' => 'required|email|unique:users,email,' . $user->id,
            'user.password' => 'nullable|min:6|confirmed',

            'profile.first_name' => 'nullable|string|max:255',
            'profile.last_name' => 'nullable|string|max:255',
            'profile.email' => 'nullable|email',
            'profile.phone' => 'nullable|string|max:20',
            'profile.avatar' => 'nullable|image|max:2048',
        ]);

        // Update User
        $user->update([
            'name' => $this->user['name'],
            'email' => $this->user['email'],
            'password' => $this->user['password'] ? Hash::make($this->user['password']) : $user->password,
        ]);

        // Handle avatar upload
        if ($this->profile['avatar'] && is_object($this->profile['avatar'])) {
            $avatarPath = $this->profile['avatar']->store('avatars', 'public');
            $this->profile['avatar'] = $avatarPath;
        } else {
            $this->profile['avatar'] = $user->profile?->avatar;
        }

        // Update or create profile
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            array_merge($this->profile, [
                'chapter_id' => $user->chapter_id,
            ])
        );

        // Success Toast + Redirect
        $this->toast()
            ->success('Done!', 'Member updated successfully!')
            ->flash()
            ->send();

        $this->redirectRoute(
            'admin.dashboard.members',
            ['chapter' => $this->chapter],
            navigate: true
        );
    }
};
?>

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
                    <x-select label="Marital Status" wire:model.defer="profile.marital_status">
                        <option value="">Select</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="widowed">Widowed</option>
                    </x-select>
                    <x-input type="date" label="Wedding Anniversary" wire:model.defer="profile.wedding_anniversary" />
                    <x-input label="Occupation" wire:model.defer="profile.occupation" />
                    <x-input label="Employer" wire:model.defer="profile.employer" />
                    <x-input label="Education Level" wire:model.defer="profile.education_level" />
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