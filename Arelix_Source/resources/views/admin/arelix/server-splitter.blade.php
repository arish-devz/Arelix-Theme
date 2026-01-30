@extends('layouts.admin')

@section('title')
    Arelix Extensions
@endsection

@section('content-header')
    <h1>Server Splitter<small>Manage server splitting configurations and whitelists.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Server Splitter</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Server Splitter Configuration</h3>
                </div>
                <div class="box-body">
                    <p>Use the API to manage whitelists.</p>
                    <!-- Placeholder for future React or interactive components -->
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> The Server Splitter management interface is currently being
                        integrated. Please use the API endpoints or the Client Area for management.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection