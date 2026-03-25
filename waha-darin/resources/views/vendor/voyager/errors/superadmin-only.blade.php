@extends('voyager::master')

@section('page_title', __('voyager::generic.error'))

@section('content')
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-bordered">
                    <div class="panel-body" style="padding: 30px;">
                        <h3 style="margin-top: 0;">
                            <i class="voyager-lock"></i>
                            Super Admin only
                        </h3>
                        <p>
                            Only the <strong>Super Admin</strong> is allowed to access this page or perform this action.
                        </p>
                        <a class="btn btn-primary" href="{{ route('voyager.dashboard') }}">
                            Back to Dashboard
                        </a>
                        <a class="btn btn-default" href="{{ url()->previous() }}">
                            Go Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

