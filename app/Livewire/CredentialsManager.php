<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Credential;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class CredentialsManager extends Component
{
    public $credentials;


    public $name = '';
    public $username = '';
    public $password = '';
    public $secret = '';
    
    public $credentialIdToUpdate = null;
    public $isFormVisible = false;
    protected function rules()
    {
        $isCreating = is_null($this->credentialIdToUpdate);

        return [
            'name' => ['required', 'string', 'max:255', $isCreating ? 'unique:credentials' : Rule::unique('credentials')->ignore($this->credentialIdToUpdate)],
            'username' => 'required|string|max:255',
            
            'password' => [$isCreating ? 'required' : 'nullable', 'string', 'min:8'],
            'secret' => 'nullable|string',
        ];
    }
    
    public function render()
    {
        $this->credentials = Credential::all();
        
        return view('livewire.credentials-manager')
            ->layout('layouts.app', ['header' => 'Zarządzanie Poświadczeniami']);
    }

// Formularz:

    public function showCreateForm()
    {
        $this->resetForm();
        $this->isFormVisible = true;
    }
    
    public function showEditForm($id)
    {
        $credential = Credential::findOrFail($id);
        $this->credentialIdToUpdate = $credential->id;
        $this->name = $credential->name;
        $this->username = $credential->username;
    
        $this->password = '';
        $this->secret = '';
        
        $this->isFormVisible = true;
    }

    public function hideForm()
    {
        $this->isFormVisible = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->username = '';
        $this->password = '';
        $this->secret = '';
        $this->credentialIdToUpdate = null;
    }


    public function saveCredential()
    {
        $isCreating = is_null($this->credentialIdToUpdate);
        $this->validate();

        $data = [
            'name' => $this->name,
            'username' => $this->username,
        ];

        if (!empty($this->password)) {
            $data['password'] = $this->password;
        }
        if (!empty($this->secret)) {
            $data['secret'] = $this->secret;
        }

        $credential = Credential::updateOrCreate(['id' => $this->credentialIdToUpdate], $data);
              
        session()->flash('message', $isCreating ? 'Poświadczenia pomyślnie utworzone.' : 'Poświadczenia pomyślnie zaktualizowane.');
        $this->hideForm();
    }

    public function deleteCredential($id)
    {
        $credential = Credential::findOrFail($id);
        $credentialName = $credential->name;
        $credentialUser = $credential->username;
        
        $credential->delete();

        session()->flash('message', 'Poświadczenia usunięte.');
    }
}