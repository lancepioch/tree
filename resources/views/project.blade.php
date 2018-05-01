@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 mt-3">
                <div class="card">
                    <div class="card-header">{{ $project->github_repo }}</div>

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
                                    <textarea name="forge_deployment" class="form-control" rows="5" placeholder="php artisan db:seed">{{ $project->forge_deployment }}</textarea>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary">Save Project</button>
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
