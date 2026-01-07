@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <div class="bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-semibold mb-4">Tax Settings</h1>

        @if(session('success'))
            <div class="mb-4 text-green-700">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 text-red-700">
                <ul>
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.tax.update') }}">
            @csrf
            <div class="mb-4">
                <label class="block mb-1">Tax Rate (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $rate) }}" class="w-48 border px-2 py-1" aria-label="Tax rate">
                <p class="text-sm text-gray-600 mt-1">Enter tax rate as a percentage (e.g., 11 for 11%).</p>
            </div>

            <div class="flex gap-2">
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 border rounded">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
