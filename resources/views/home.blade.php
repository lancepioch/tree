@extends('layouts.app')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v1.9.3/dist/alpine.js" defer></script>
@endpush

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 mt-3">
                <div class="card">
                    <div class="card-header">Tokens</div>

                    <div class="card-body">
                        @isset($forgeException) <div class="alert alert-danger">{!! $forgeException !!}</div> @endisset
                        @isset($githubException) <div class="alert alert-danger">{{ $githubException }}</div> @endisset

                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf

                            @isset (auth()->user()->forge_token)
                            <div class="form-group row">
                                <label for="forge_token" class="col-sm-4 col-form-label text-md-right">Current Forge Token:</label>

                                <div class="col-md-6">
                                    <input type="text" class="form-control" readonly value="{{ substr(auth()->user()->forge_token, 0, 15) }}...{{ substr(auth()->user()->forge_token, -15, 15) }}">
                                </div>
                            </div>
                            @endisset

                            <div class="form-group row">
                                <label for="forge_token" class="col-sm-4 col-form-label text-md-right">New Forge Token:</label>

                                <div class="col-md-6">
                                    <input id="forge_token" type="text" class="form-control{{ $errors->has('forge_token') ? ' is-invalid' : '' }}" name="forge_token" required>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary">Save Token</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if (auth()->user()->projects->count() > 0)
            <div class="col-md-10 mt-3">
                <div class="card">
                    <div class="card-header">Active Projects</div>
                        <div class="card-body">
                            <div class="form-group row">
                                <label for="project_id" class="col-sm-4 col-form-label text-md-right">Project:</label>

                                <div class="col-md-6">
                                    <select id="project_id" name="forge_server_id" class="form-control" onchange="projectSelector()">
                                        @foreach (auth()->user()->projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->forge_site_url }} ({{ $project->github_repo }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <a id="EditProject">
                                        <button class="btn btn-primary">Edit Project</button>
                                    </a>
                                    <form id="DeleteProject" style="display: inline-block;" method="post" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Delete Project</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <script>
                function projectSelector() {
                    var select = document.querySelector('#project_id');
                    var projectId = select.options[select.selectedIndex].value;

                    document.querySelector('#EditProject').href = '{{ route("projects.show", [""]) }}' + '/' + projectId;
                    document.querySelector('#DeleteProject').action = '{{ route("projects.destroy", [""]) }}' + '/' + projectId;
                }

                projectSelector();
            </script>
            @endif

            @if (isset(auth()->user()->forge_token) && count($servers) > 0)
            <div class="col-md-10 mt-3" x-data="{ url: '', newEnvKey: '', newEnvVal: '', vars: {} }">
                <div class="card">
                    <div class="card-header">Create New Project</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('projects.store') }}">
                            @csrf

                            <div class="form-group row">
                                <label for="forge_site_url" class="col-sm-4 col-form-label text-md-right">URL:</label>

                                <div class="col-md-6">
                                    <input id="forge_site_url" type="text" class="form-control{{ $errors->has('forge_site_url') ? ' is-invalid' : '' }}" name="forge_site_url" value="{{ old('forge_site_url') }}" required placeholder="*.example.com" x-model="url" @change="vars['APP_URL'] = 'http://' + url">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="forge_server_id" class="col-sm-4 col-form-label text-md-right">Server:</label>

                                <div class="col-md-6">
                                    <select id="forge_server_id" name="forge_server_id" class="form-control">
                                        @foreach ($servers as $server)
                                            <option value="{{ $server->id }}">{{ $server->name }} ({{ $server->ipAddress}})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="github_repo" class="col-md-4 col-form-label text-md-right">Github Repository:</label>

                                <div class="col-md-6">
                                    <select id="github_repo" name="github_repo" class="form-control">
                                        @foreach ($repositories as $repository)
                                            <option value="{{ $repository['full_name'] }}">{{ $repository['full_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="forge_user" class="col-md-4 col-form-label text-md-right">Isolated User:</label>

                                <div class="col-md-6">
                                    <input id="forge_user" type="text" class="form-control{{ $errors->has('forge_user') ? ' is-invalid' : '' }}" name="forge_user" value="{{ old('forge_user') }}" placeholder="forge">
                                </div>
                            </div>

                            <template x-for="key in Object.keys(vars)">
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Env Var:</label>

                                <input type="hidden" name="forge_env_vars" :value="JSON.stringify(vars)">

                                <div class="col-md-3">
                                    <input readonly="readonly" type="text" class="form-control" :value="key">
                                </div>

                                <div class="col-md-3">
                                    <input readonly="readonly" type="text" class="form-control" :value="vars[key]">
                                </div>

                                <div>
                                    <span class="btn btn-danger" @click="var v = vars; delete v[key]; vars = v;">&times;</span>
                                </div>
                            </div>
                            </template>

                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">New Env Variable:</label>

                                <div class="col-md-3">
                                    <input type="text" class="form-control" placeholder="ENV_VAR_KEY" x-model="newEnvKey">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" placeholder="valueofenv" x-model="newEnvVal">
                                </div>
                                <div>
                                    <span class="btn btn-primary" @click="vars[newEnvKey] = newEnvVal; newEnvKey = ''; newEnvVal = '';">&plus;</span>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="forge_deployment" class="col-md-4 col-form-label text-md-right">All Deployments:</label>

                                <div class="col-md-6">
                                    <textarea style="white-space: nowrap;" name="forge_deployment" class="form-control" rows="5" placeholder="php artisan migrate --force">{{ "composer install --no-interaction --prefer-dist\nphp artisan migrate --force\n" }}</textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="forge_deployment_initial" class="col-md-4 col-form-label text-md-right">Initial Deployment:</label>

                                <div class="col-md-6">
                                    <textarea style="white-space: nowrap;" name="forge_deployment_initial" class="form-control" rows="3" placeholder="php artisan db:seed">{{ "php artisan key:generate\n" }}</textarea>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary" @if (isset($forgeException)) disabled @endif>Create Project</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endisset

            @if (isset(auth()->user()->forge_token) && count($servers) <= 0)
                <div class="col-md-10 mt-3">
                    <div class="card">
                        <div class="card-header">Create New Project</div>

                        <div class="card-body">
                            You must have at least one server set up on Laravel Forge in order to use this!
                        </div>
                    </div>
                </div>
            @endif

            @if (config('forest.survey'))
            <div class="col-md-10 mt-3">
                <div class="card">
                    <div class="card-header">Optional Survey</div>

                    <div class="card-body">
                        <div id="surveyhero-embed-1c71d8e3"></div>
                        <script src="https://embed-cdn.surveyhero.com/js/user/embed.1c71d8e3.js" async></script>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
