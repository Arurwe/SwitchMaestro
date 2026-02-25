<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Spatie\Activitylog\Models\Activity;

class AuditLogViewer extends Component
{
    use WithPagination;

    // --- Filtry ---
    #[Url(keep: true)]
    public $selectedUser = null;
    
    #[Url(keep: true)]
    public $dateFrom = null;
    
    #[Url(keep: true)]
    public $dateTo = null;

    #[Url(keep: true)]
    public $search = ''; 

    // Dane dla Tom Select
    public $allUsers = [];

    public function mount()
    {
        $this->allUsers = User::orderBy('full_name')->get(['id', 'full_name']);
    }
    
    public function resetFilters()
    {
        $this->reset(['selectedUser', 'dateFrom', 'dateTo', 'search']);
        $this->resetPage();
        $this->dispatch('resetTomSelect');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['selectedUser', 'dateFrom', 'dateTo', 'search'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Activity::with('causer', 'subject')
            
            ->when($this->search, function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%');
            })
            
            ->when($this->selectedUser, function ($q) {
                $q->where('causer_type', User::class)
                  ->where('causer_id', $this->selectedUser);
            })
            
            // Filtry daty
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            
            ->latest();

        $logs = $query->paginate(20);
            
        return view('livewire.audit-log-viewer', [
            'logs' => $logs,
        ])
        ->layout('layouts.app', ['header' => 'Dziennik Zdarzeń Systemu']);
    }
}