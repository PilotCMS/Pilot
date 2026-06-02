@extends('themes.default.layout')

@section('content')
    @if($blocks->isEmpty())
        <section class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-16 text-center">
            <h1 class="text-2xl font-bold text-slate-800">No published blocks</h1>
            <p class="mt-2 text-sm text-slate-500">Publish some content in the editor, then refresh this page.</p>
        </section>
    @else
        <div class="space-y-10">
            @foreach($blocks as $block)
                @include('themes.default.components._render-block', ['block' => $block])
            @endforeach
        </div>
    @endif
@endsection
