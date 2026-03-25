@extends('voyager::master')

@section('page_title', __('Import Books'))

@section('content')
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-10">
                <h1 class="page-title">
                    <i class="voyager-upload"></i> {{ __('Import Books') }}
                </h1>
                <p class="text-muted">
                    {{ __('Upload an Excel (.xlsx) or CSV file and create multiple books at once.') }}
                </p>

                @if(session('book_import_warnings'))
                    <div class="alert alert-warning">
                        <strong>{{ __('Import warnings') }}:</strong>
                        <ul class="mb-0">
                            @foreach((array) session('book_import_warnings') as $w)
                                <li>{{ $w }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <strong>{{ __('Validation error') }}:</strong>
                        <ul class="mb-0">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php($result = session('book_import_result'))
                @if(is_array($result))
                    <div class="panel panel-bordered">
                        <div class="panel-heading">
                            <h3 class="panel-title">{{ __('Last import result') }}</h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="well">
                                        <div class="h4 mb-0">{{ (int) ($result['inserted'] ?? 0) }}</div>
                                        <div class="text-muted">{{ __('Inserted') }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="well">
                                        <div class="h4 mb-0">{{ (int) ($result['skipped'] ?? 0) }}</div>
                                        <div class="text-muted">{{ __('Skipped (duplicates)') }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="well">
                                        <div class="h4 mb-0">
                                            {{ __('Created') }}:
                                            {{ __('Authors') }} {{ (int) data_get($result, 'created.authors', 0) }},
                                            {{ __('Publishers') }} {{ (int) data_get($result, 'created.publishers', 0) }},
                                            {{ __('Categories') }} {{ (int) data_get($result, 'created.categories', 0) }}
                                        </div>
                                        <div class="text-muted">{{ __('New related records added during import') }}</div>
                                    </div>
                                </div>
                            </div>

                            @php($skippedDownloads = session('book_import_skipped_downloads'))
                            @if(is_array($skippedDownloads) && !empty($result['skipped']))
                                <div class="alert alert-info">
                                    <strong>{{ __('Skipped rows export') }}:</strong>
                                    {{ __('Download a file containing the skipped (duplicate) rows.') }}
                                    <div class="mt-2">
                                        @if(!empty($skippedDownloads['csv']))
                                            <a class="btn btn-sm btn-success" href="{{ $skippedDownloads['csv'] }}" target="_blank" rel="noopener">
                                                <i class="voyager-download"></i> {{ __('Download CSV') }}
                                            </a>
                                        @endif
                                        @if(!empty($skippedDownloads['xlsx']))
                                            <a class="btn btn-sm btn-primary" href="{{ $skippedDownloads['xlsx'] }}" target="_blank" rel="noopener">
                                                <i class="voyager-download"></i> {{ __('Download XLSX') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if(!empty($result['errors']))
                                <div class="alert alert-danger">
                                    <strong>{{ __('Some rows were not imported.') }}</strong>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th style="width: 120px;">{{ __('Row') }}</th>
                                            <th>{{ __('Error') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach((array) $result['errors'] as $err)
                                            <tr>
                                                <td>#{{ (int) data_get($err, 'row', 0) }}</td>
                                                <td>{{ (string) data_get($err, 'message', '') }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ __('Upload file') }}</h3>
                    </div>
                    <div class="panel-body">
                        <form action="{{ route('voyager.book-import.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group">
                                <label for="file">{{ __('Excel/CSV file') }}</label>
                                <input id="file" name="file" type="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                                <p class="help-block mb-0">
                                    {{ __('Max size: 5MB.') }}
                                    <a href="{{ asset('book-import-template.csv') }}" target="_blank" rel="noopener">
                                        {{ __('Download CSV template') }}
                                    </a>
                                </p>
                            </div>

                            <button class="btn btn-primary" type="submit">
                                <i class="voyager-upload"></i> {{ __('Import') }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ __('Excel columns') }}</h3>
                    </div>
                    <div class="panel-body">
                        <p class="mb-2">
                            <strong>{{ __('Required columns') }}:</strong>
                            <code>Title</code> (or <code>Name</code>), <code>Author</code>, <code>Publisher</code>, <code>Category</code>
                        </p>
                        <p class="mb-2">
                            <strong>{{ __('Optional columns') }}:</strong>
                            <code>Price</code>, <code>ISBN</code>, <code>Year</code>, <code>Description</code>, <code>Image</code>, <code>Available</code>, <code>Internal Code</code>
                        </p>
                        <p class="mb-2">
                            <strong>{{ __('Multiple categories') }}:</strong>
                            {{ __('You can separate multiple categories using') }} <code>|</code>, <code>,</code> {{ __('or') }} <code>;</code>
                        </p>
                        <p class="mb-0 text-muted">
                            {{ __('Notes: Existing books with the same Title are skipped. Missing authors/publishers are created automatically. Categories are validated against the allowed list.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

