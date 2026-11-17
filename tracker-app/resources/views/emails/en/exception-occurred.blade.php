@extends('layouts.email')

@section('message')

    <h1 style="margin-top: 0;">🚨 Exception Occurred</h1>

    <p><strong>Message:</strong><br>
        {{ $exception->getMessage() }}
    </p>

    <p><strong>Location:</strong><br>
        {{ $exception->getFile() }}:{{ $exception->getLine() }}
    </p>

    @if(!empty($context))
        <p><strong>Request Context:</strong></p>
        <pre style="
                            background:#f5f5f5;
                            padding:12px;
                            border-radius:6px;
                            white-space:pre-wrap;
                            font-size:12px;
                        ">{{ json_encode($context, JSON_PRETTY_PRINT) }}</pre>
    @endif

    <p><strong>Stack Trace:</strong></p>
    <pre style="
                background:#f5f5f5;
                padding:12px;
                border-radius:6px;
                white-space:pre-wrap;
                font-size:12px;
            ">{{ $exception->getTraceAsString() }}</pre>
@endsection