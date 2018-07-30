@extends('layouts.app')

@section('header')
    <style>
        ul.pagination {
            justify-content: center;
        }

        a.nav-link {
            padding: 0 1rem;
        }
    </style>
@endsection

@section('content')

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 mt-3">
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
                    <div class="card-header">
                        <span style="float: left;">Pull Requests</span>
                        <nav class="nav nav-pills nav-justified" style="float: right;">
                            <a class="nav-link @if ($state === 'all') active @endif" href="?state=all">All</a>
                            <a class="nav-link @if ($state === 'open') active @endif" href="?state=open">Open</a>
                            <a class="nav-link @if ($state === 'closed') active @endif" href="?state=closed">Closed</a>
                        </nav>
                    </div>

                    <div class="card-body">

                        <table class="table">
                            <thead>
                            <tr>
                                <th scope="col">Title</th>
                                <th scope="col">State</th>
                                <th scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($pullRequests as $pr)
                                @php $branch = $project->branches->firstWhere('issue_number', $pr['number']); @endphp
                            <tr>
                                <td><a href="{{ $pr['html_url'] }}">#{{ $pr['number'] }} - {{ $pr['title'] }}</a></td>
                                <td>@if ($pr['state'] === 'open') <span class="badge badge-success">Open</span>
                                    @elseif ($pr['merged_at'] === null) <span class="badge badge-danger">Closed</span>
                                    @else <span class="badge badge-primary">Merged</span>
                                    @endif</td>
                                <td>
                                    <div class="dropdown">
                                        @if ($branch === null)
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                                Undeployed
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="#">Deploy</a>
                                            </div>
                                        @else
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                                Deployed
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="#">Deploy Latest</a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="#">Remove</a>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <div class="text-center">{{ $pullRequests->links() }}</div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
