@extends('dashboard.index')

@section('dashboardContent')
<div class="container mt-2">

    {{-- IT --}}
    @if(isset($sections['it']))
        {!! $sections['it'] !!}
    @endif
    {{-- Learner --}}
    @if(isset($sections['learner']))
        {!! $sections['learner'] !!}
    @endif

    {{-- Administrator --}}
    @if(isset($sections['hr']))
        {!! $sections['hr'] !!}
    @endif

    {{-- Instructor --}}
    @if(isset($sections['instructor']))
        {!! $sections['instructor'] !!}
    @endif

</div>
@endsection
