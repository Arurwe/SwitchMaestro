<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManager extends Component
{
    public $users;
    public $roles;
    
    public $full_name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $selectedRole = '';
    
    public $userIdToUpdate = null;
    public $isFormVisible = false;

    public function mount()
    {
        $this->roles = Role::all();
    }

    public function render()
    {
        $this->users = User::with('roles')->get();
        
        return view('livewire.user-manager')
            ->layout('layouts.app', ['header' => 'Zarządzanie Użytkownikami']);
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->isFormVisible = true;
    }
    
    public function showEditForm($id)
    {
        $user = User::findOrFail($id);
        $this->userIdToUpdate = $user->id;
        $this->full_name = $user->full_name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->selectedRole = $user->roles->first()->name ?? '';
        
        $this->password = '';
        $this->password_confirmation = '';
        
        $this->isFormVisible = true;
    }

    public function hideForm()
    {
        $this->isFormVisible = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->full_name = '';
        $this->username = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRole = '';
        $this->userIdToUpdate = null;
    }



    public function saveUser()
    {
        $isCreating = is_null($this->userIdToUpdate);

        $rules = [
            'full_name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', $isCreating ? 'unique:users' : Rule::unique('users')->ignore($this->userIdToUpdate)],
            'email' => ['required', 'email', 'max:255', $isCreating ? 'unique:users' : Rule::unique('users')->ignore($this->userIdToUpdate)],
            'selectedRole' => ['required', Rule::exists('roles', 'name')],
        ];
        if ($isCreating || !empty($this->password)) {
            $rules['password'] = ['required', 'confirmed', Password::min(8)];
        }
        $this->validate($rules);

        $data = [
            'full_name' => $this->full_name,
            'username' => $this->username,
            'email' => $this->email,
        ];
        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $user = User::updateOrCreate(['id' => $this->userIdToUpdate], $data);
        $user->syncRoles($this->selectedRole);

        session()->flash('message', $isCreating ? 'Użytkownik pomyślnie utworzony.' : 'Użytkownik pomyślnie zaktualizowany.');
        $this->hideForm(); 
    }

    public function deleteUser($id)
    {
        if ($id == auth()->id()) {
            session()->flash('error', 'Nie możesz usunąć własnego konta.');
            return;
        }

        User::findOrFail($id)->delete();
        session()->flash('message', 'Użytkownik usunięty.');
    }
}