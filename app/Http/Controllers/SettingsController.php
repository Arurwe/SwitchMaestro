<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting; // Importuj model Setting
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log; // Dodaj Log

class SettingsController extends Controller
{


    public function index(): View
    {
        $settings = Setting::orderBy('key')->get();

        return view('settings.index', [
            'settings' => $settings
        ]);
    }


    public function update(Request $request): RedirectResponse
    {

        $validated = $request->validate([
            'settings.backup_schedule_time' => 'required|date_format:H:i',
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ]);

        try {
            foreach ($validated['settings'] as $key => $value) {
                Setting::where('key', $key)->update(['value' => $value]);
            }

            return redirect()->route('settings.index')
                ->with('success', 'Ustawienia zostały pomyślnie zaktualizowane.');

        } catch (\Exception $e) {
            Log::error("Błąd podczas aktualizacji ustawień: " . $e->getMessage());
            return redirect()->route('settings.index')
                ->with('error', 'Wystąpił błąd podczas zapisywania ustawień.');
        }
    }
}