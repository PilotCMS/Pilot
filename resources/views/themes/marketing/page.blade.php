@extends('themes.marketing.layout')

@section('content')
    <div data-pilot-live-root>
        @include('themes.marketing.partials.blocks', ['blocks' => $blocks])
    </div>
@endsection
