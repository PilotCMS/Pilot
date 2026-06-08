@extends('themes.default.layout')

@section('content')
    <div data-pilot-live-root>
        @include('themes.default.partials.blocks', ['blocks' => $blocks])
    </div>
@endsection
