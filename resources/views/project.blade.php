@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 mt-3">
                <div class="card">
                    <div class="card-header">Project Information: {{ $project->github_repo }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ action('ProjectController@update', [$project]) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <label for="forge_site_url" class="col-sm-4 col-form-label text-md-right">URL:</label>

                                <div class="col-md-6">
                                    <input id="forge_site_url" type="text" class="form-control" name="forge_site_url" value="{{ $project->forge_site_url }}" required placeholder="*.example.com">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="forge_deployment" class="col-md-4 col-form-label text-md-right">Deployment:</label>

                                <div class="col-md-6">
                                    <textarea style="white-space: nowrap;" name="forge_deployment" class="form-control" rows="5" placeholder="php artisan migrate --force">{{ $project->forge_deployment }}</textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="forge_deployment" class="col-md-4 col-form-label text-md-right">Initial Deployment:</label>

                                <div class="col-md-6">
                                    <textarea style="white-space: nowrap;" name="forge_deployment_initial" class="form-control" rows="5" placeholder="php artisan db:seed">{{ $project->forge_deployment_initial }}</textarea>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-10 offset-md-4">
                                    <button type="submit" class="btn btn-primary">Save Project</button>
                                    <a href="{{ action('ProjectController@index') }}"><span class="btn btn-default">Cancel</span></a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-10 mt-3">
                <div class="card">
                    <div class="card-header">Project Deployments: {{ $project->github_repo }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ action('PauseProjectController', [$project]) }}">
                            @csrf
                            <div class="form-group row">
                                <label for="forge_site_url" class="col-sm-4 col-form-label text-md-right">Deployments:</label>

                                <div class="col-md-6">
                                    <div class="progress" style="height: 100%;">
                                        @if (is_null($project->paused_at))
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%">Currently Active</div>
                                        @else
                                        <div class="progress-bar progress-bar-striped bg-warning" role="progressbar" style="width: 100%">Currently Paused</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-10 offset-md-4">
                                    <button type="submit" class="btn btn-primary">@if (is_null($project->paused_at)) Pause @else Unpause @endif Project</button>
                                    <a href="{{ action('ProjectController@index') }}"><span class="btn btn-default">Cancel</span></a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
