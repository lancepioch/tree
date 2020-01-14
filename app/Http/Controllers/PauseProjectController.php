<?php

namespace App\Http\Controllers;

use App\Project;

class PauseProjectController extends Controller
{
    public function __invoke(Project $project)
    {
        $this->authorize('update', $project);

        $project->paused_at = is_null($project->paused_at) ? now() : null;
        $project->save();

        return redirect()->action([ProjectController::class, 'show'], [$project]);
    }
}
