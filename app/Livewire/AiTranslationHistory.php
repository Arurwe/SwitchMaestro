<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AiTranslation;

class AiTranslationHistory extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public bool $showModal = false;
    public ?AiTranslation $selectedTranslation = null;


    public function viewDetails($id)
    {
        $this->selectedTranslation = AiTranslation::with(['user', 'sourceVendor', 'targetVendor'])->find($id);
        $this->showModal = true;
    }


    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedTranslation = null;
    }

    public function render()
    {
        $translations = AiTranslation::with(['user', 'sourceVendor', 'targetVendor'])
            ->latest()
            ->paginate(15);

        return view('livewire.ai-translation-history', [
            'translations' => $translations
        ])->layout('layouts.app', ['header' => 'Historia Translacji AI']);
    }
}