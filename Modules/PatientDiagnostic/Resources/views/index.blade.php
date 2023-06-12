@extends('patientdiagnostic::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>
        This view is loaded from module: {!! config('patientdiagnostic.name') !!}
    </p>
@endsection
