<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin-side checklist: which catalog skills are ticked by default for a position. */
class PositionDefaultSkillController extends Controller
{
    public function index(): View
    {
        $positions = Position::where('is_active', true)
            ->with('department')
            ->withCount('skills')
            ->orderBy('title')
            ->paginate(25);

        return view('skills.position-defaults-index', ['positions' => $positions, 'totalSkills' => Skill::where('is_active', true)->count()]);
    }

    public function edit(Position $position): View
    {
        return view('skills.checklist', [
            'title' => 'Default Skills — '.$position->title,
            'intro' => 'Ticked skills are on the checklist of every employee holding this position. Supervisors can still add or remove skills per employee.',
            'back' => ['url' => route('skills.position-defaults.index'), 'label' => 'Back to Positions'],
            'action' => route('skills.position-defaults.update', $position),
            'skills' => Skill::with('skillCategory')->where('is_active', true)->orderBy('code')->get(),
            'checked' => $position->skills()->pluck('skills.id')->all(),
            'defaults' => null,
        ]);
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $data = $request->validate([
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:skills,id'],
        ]);

        $position->skills()->sync($data['skills'] ?? []);

        return redirect()->route('skills.position-defaults.index')->with('status', "Default skills for {$position->title} saved.");
    }
}
