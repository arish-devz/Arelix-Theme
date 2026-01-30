@extends('layouts.admin')

@section('title')
    Billing
@endsection

@section('content-header')
    <h1>Billing Management<small>Configure billing settings, gateways, and plans.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Billing</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Billing Settings</h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Billing configuration is currently handled via configuration files
                        and API.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection