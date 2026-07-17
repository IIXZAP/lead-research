@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-display font-semibold mb-6">New Campaign</h1>

    <form method="POST" action="{{ route('campaigns.store') }}" class="card p-6 space-y-4 max-w-xl">
        @csrf
        @include('campaigns._form', ['campaign' => null])

        <button type="submit" class="btn-primary">สร้างแคมเปญ</button>
    </form>
@endsection
