<?php

namespace App\Http\Controllers;

use App\Project;
use Illuminate\Http\RedirectResponse;

class PauseProjectController extends Controller
{
    public function __invoke(Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->paused_at = is_null($project->paused_at) ? now() : null;
        $project->save();

        return redirect()->route('projects.show', [$project]);
    }
}
