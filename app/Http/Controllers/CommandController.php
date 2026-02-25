<?php

namespace App\Http\Controllers;

use App\Models\Command;
use Illuminate\Http\Request;

class CommandController extends Controller
{
 public function edit(Command $command)
    {
        $command->load(['action', 'vendor']);
        
        return view('commands.edit', compact('command'));
    }


    public function update(Request $request, Command $command)
    {
        $validated = $request->validate([
            'commands' => 'required|string',
            'description' => 'nullable|string',
        ]);

        // Przekształcenie linii na array
        $commandsArray = array_filter(
            explode("\n", str_replace("\r", "", $validated['commands']))
        );

        $command->update([
            'commands' => $commandsArray,
            'description' => $validated['description'],
            'user_id' => auth()->id() ?? 1,
        ]);

        return redirect()->route('actions.index')->with('success', 'Komendy zostały zaktualizowane!');
    }
}
