<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TaskLog;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

class JobMonitor extends Component
{
    use WithPagination;


    #[Url(keep: true)]
    public $activeTab = 'all'; 

    // Filtry
    #[Url(keep: true)]
    public $filterDevice = '';
    #[Url(keep: true)]
    public $filterUser = '';
    #[Url(keep: true)]
    public $filterStatus = '';
    #[Url(keep: true)]
    public $filterBatch = ''; 

    public $selectedLog = null;
    public $formattedOutput = '';

    public function refreshLogs()
    {
    }

    public function resetFilters()
    {
        $this->reset(['filterDevice', 'filterUser', 'filterStatus', 'filterBatch']);
        $this->resetPage();
    }

    public function updating($key)
    {
        if (in_array($key, ['filterDevice', 'filterUser', 'filterStatus', 'filterBatch', 'activeTab'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $logsQuery = TaskLog::with(['user', 'device', 'action'])
            ->orderBy('created_at', 'desc');

        if ($this->filterDevice) {
            $logsQuery->whereHas('device', function ($q) {
                $q->where('name', 'like', '%' . $this->filterDevice . '%');
            });
        }
        if ($this->filterUser) {
            $logsQuery->whereHas('user', function ($q) {
                $q->where('full_name', 'like', '%' . $this->filterUser . '%');
            });
        }
        if ($this->filterStatus) {
            $logsQuery->where('status', $this->filterStatus);
        }
        if ($this->filterBatch) {
            $logsQuery->where('batch_id', 'like', '%' . $this->filterBatch . '%');
        }

        $batchesQuery = TaskLog::whereNotNull('batch_id')
            ->select(
                'batch_id',
                DB::raw('COUNT(*) as total_tasks'),
                DB::raw('MAX(created_at) as last_activity'),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_tasks"),
                DB::raw("SUM(CASE WHEN status = 'RUNNING' THEN 1 ELSE 0 END) as running_tasks"),
                DB::raw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_tasks")
            )
            ->groupBy('batch_id')
            ->orderBy('last_activity', 'desc');

        if ($this->filterBatch) {
            $batchesQuery->where('batch_id', 'like', '%' . $this->filterBatch . '%');
        }

        return view('livewire.job-monitor', [
            'logs' => $logsQuery->paginate(15, ['*'], 'logsPage'), 
            'batches' => $batchesQuery->paginate(10, ['*'], 'batchesPage'), 
        ])->layout('layouts.app', ['header' => 'Monitor Zadań']);
    }

    public function openModal($logId)
    {
        $this->selectedLog = TaskLog::find($logId);

        $this->formattedOutput = $this->selectedLog->raw_output;
        if ($this->selectedLog && $this->selectedLog->raw_output) {
            $json = json_decode($this->selectedLog->raw_output, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $this->formattedOutput = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function closeModal()
    {
        $this->selectedLog = null;
        $this->formattedOutput = '';
    }
    public function showBatchTasks($batchId)
{
    $this->filterBatch = $batchId;
    $this->activeTab = 'all';


}
}