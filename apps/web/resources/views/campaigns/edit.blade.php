@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-display font-semibold mb-6">Edit Campaign</h1>

    <form method="POST" action="{{ route('campaigns.update', $campaign) }}" class="card p-6 space-y-4 max-w-xl">
        @csrf
        @method('PUT')
        @include('campaigns._form', ['campaign' => $campaign])

        <button type="submit" class="btn-primary">บันทึกการแก้ไข</button>
    </form>
@endsection
