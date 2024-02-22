<?php

namespace App\Http\Requests;

use App\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class AcceptGithubWebhook extends FormRequest
{
    public Project $project;

    public function authorize(): bool
    {
        $input = $this->all();
        $signature = $this->header('X-Hub-Signature');

        if (! is_string($signature)) {
            return false;
        }

        if (! Str::contains($signature, '=')) {
            return false;
        }

        if (! isset($input['repository']['full_name'])) {
            return false;
        }

        [$algorithm, $signature] = explode('=', $signature, 2);
        $project = Project::where('github_repo', $input['repository']['full_name'])->with(['branches', 'user'])->first();

        if ($project === null) {
            return false;
        }

        $this->project = $project;

        $content = $this->getContent();
        if (! is_string($content)) {
            return false;
        }

        // Signature Verification
        $hash = hash_hmac($algorithm, $content, $this->project->webhook_secret);

        return $hash === $signature;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'action' => 'nullable',
            'pull_request' => 'nullable',
            'repository.full_name' => 'required',
        ];
    }
}
