@extends('layouts.admin')

@section('title')
    Staff Requests
@endsection

@section('content-header')
    <h1>Staff Requests<small>Manage and review staff access requests.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Staff Requests</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Management</h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Staff requests are managed via the Client Area or API.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection