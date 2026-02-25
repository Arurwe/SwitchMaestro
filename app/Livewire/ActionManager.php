<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Action;
use App\Models\Command;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Services\AiCommandTranslator;

class ActionManager extends Component
{
    // --- Stan listy ---
    public ?int $expandedActionId = null;
    public Collection $allVendors;
    public array $newVendorId = [];

    // --- Stan formularza tworzenia nowej akcji
    public bool $showCreateForm = false;
    public string $newActionName = '';
    public string $newActionSlug = '';
    public string $newActionDescription = '';

    public ?int $editActionId = null;
    public string $editActionName = '';
    public string $editActionSlug = '';
    public string $editActionDescription = '';

    public bool $showAiModal = false;
    public string $aiSourceCommands = '';
    public ?int $aiSourceVendorId = null;
    public ?int $aiTargetActionId = null;
    
    public bool $isTranslating = false;



    protected $rules = [
        'newActionName' => 'required|string|max:255',
        'newActionSlug' => 'required|string|max:255|alpha_dash|unique:actions,action_slug',
        'newActionDescription' => 'nullable|string',
    ];


    protected $messages = [
        'newActionName.required' => 'Nazwa akcji jest wymagana.',
        'newActionSlug.required' => 'Slug akcji jest wymagany.',
        'newActionSlug.alpha_dash' => 'Slug może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
        'newActionSlug.unique' => 'Taki slug akcji już istnieje w bazie danych.',
    ];


    public function mount()
    {
        $this->allVendors = Vendor::all();
    }

 
    public function toggleExpand($actionId)
    {
        $this->expandedActionId = ($this->expandedActionId === $actionId) ? null : $actionId;
        $this->resetErrorBag();
    }
    
    

    public function toggleCreateForm()
    {
        $this->showCreateForm = !$this->showCreateForm;
        $this->resetNewActionForm();
        $this->resetErrorBag();
    }

    
     
    public function resetNewActionForm()
    {
        $this->newActionName = '';
        $this->newActionSlug = '';
        $this->newActionDescription = '';
    }

    
    //  Zapisuje nową, abstrakcyjną akcję w bazie.
     
    public function storeNewAction()
    {
        $validated = $this->validate();

        $action = Action::create([
            'name' => $validated['newActionName'],
            'action_slug' => $validated['newActionSlug'],
            'description' => $validated['newActionDescription'] ?? null,
        ]);

        session()->flash('success', 'Nowa akcja została utworzona!');
        $this->toggleCreateForm();
    }

   
    public function addImplementation($actionId)
    {
        $vendorId = $this->newVendorId[$actionId] ?? null;

        $this->validate(
            ['newVendorId.'.$actionId => 'required|exists:vendors,id'], 
            ['newVendorId.'.$actionId.'.required' => 'Musisz wybrać vendora.']
        );

        $exists = Command::where('action_id', $actionId)
                         ->where('vendor_id', $vendorId)
                         ->exists();

        if ($exists) {
            $this->addError('newVendorId.'.$actionId, 'Ten vendor ma już implementację dla tej akcji.');
            return;
        }

        $newCommand = Command::create([
            'action_id' => $actionId,
            'vendor_id' => $vendorId,
            'commands' => ['# TODO: Dodaj tutaj swoje komendy'],
            'description' => 'Implementacja w trakcie tworzenia.',
            'user_id' => Auth::user()->id ?? 1,
        ]);

        $this->newVendorId[$actionId] = null;

        session()->flash('success', 'Vendor dodany. Uzupełnij teraz komendy.');
        return redirect()->route('commands.edit', $newCommand->id);
    }

    
//   Edycja akcjii

public function startEditing($actionId)
{
    $action = Action::findOrFail($actionId);

    $this->editActionId = $action->id;
    $this->editActionName = $action->name;
    $this->editActionSlug = $action->action_slug;
    $this->editActionDescription = $action->description ?? '';

    $this->resetErrorBag();
}


public function cancelEditing()
{
    $this->editActionId = null;
    $this->resetErrorBag();
}


public function updateAction()
{
    $this->validate([
        'editActionName' => 'required|string|max:255',
        'editActionSlug' => 'required|string|max:255|alpha_dash|unique:actions,action_slug,' . $this->editActionId,
        'editActionDescription' => 'nullable|string',
    ], [
        'editActionName.required' => 'Nazwa akcji jest wymagana.',
        'editActionSlug.required' => 'Slug akcji jest wymagany.',
        'editActionSlug.alpha_dash' => 'Slug może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
        'editActionSlug.unique' => 'Taki slug akcji już istnieje w bazie danych.',
    ]);

    $action = Action::findOrFail($this->editActionId);
    $action->update([
        'name' => $this->editActionName,
        'action_slug' => $this->editActionSlug,
        'description' => $this->editActionDescription,
    ]);

    session()->flash('success', 'Akcja została zaktualizowana.');
    $this->editActionId = null;
}


    public function deleteAction($actionId)
    {
        $action = Action::findOrFail($actionId);

        $action->commands()->delete();

        $action->delete();

        session()->flash('success', 'Akcja została usunięta.');
    }



    public function render()
    {
        $actions = Action::with(['commands.vendor'])
                         ->orderBy('name')
                         ->get();
                         
        return view('livewire.action-manager', [
            'actions' => $actions
        ])->layout('layouts.app', ['header' => 'Zarządzanie Akcjami']);
    }




    public function openAiAssistant($actionId)
    {
        $this->aiTargetActionId = $actionId;
        $this->aiSourceCommands = '';
        $this->aiSourceVendorId = null;
        $this->showAiModal = true;
    }

    public function closeAiModal()
    {
        $this->showAiModal = false;
    }


    public function generateImplementationWithAi(AiCommandTranslator $translator)
    {
        $this->validate([
            'aiSourceVendorId' => 'required|exists:vendors,id',
            'newVendorId.'.$this->aiTargetActionId => 'required|exists:vendors,id',
            'aiSourceCommands' => 'required|string|min:3',
        ]);

        $this->isTranslating = true;

        $sourceVendorId = (int) $this->aiSourceVendorId;
        $targetVendorId = (int) $this->newVendorId[$this->aiTargetActionId];
        $userId = Auth::id() ?? 1;

        $translatedCommands = $translator->translate(
            $userId,
            $sourceVendorId, 
            $targetVendorId, 
            $this->aiSourceCommands
        );

        $this->isTranslating = false;

        if (!$translatedCommands) {
            $this->addError('aiSourceCommands', 'Nie udało się przetłumaczyć komend. Spróbuj ponownie lub sprawdź logi.');
            return;
        }

        $sourceVendorObj = $this->allVendors->find($sourceVendorId);

        $commandsArray = array_filter(explode("\n", str_replace("\r", "", $translatedCommands)));

        $newCommand = Command::create([
            'action_id' => $this->aiTargetActionId,
            'vendor_id' => $targetVendorId,
            'commands' => array_values($commandsArray), 
            'description' => "Wygenerowano automatycznie przez AI z formatu: {$sourceVendorObj->name}. Zweryfikuj przed użyciem!",
            'user_id' => $userId,
        ]);


        $this->newVendorId[$this->aiTargetActionId] = null;
        $this->closeAiModal();
        session()->flash('success', 'AI wygenerowało implementację! Sprawdź poprawność w edytorze.');
        
        return redirect()->route('commands.edit', $newCommand->id);
    }
}