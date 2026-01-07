@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Activity Log #{{ $log->id }}</h1>
        <a href="{{ route('admin.activity-logs.index') }}" class="text-blue-600">Back to list</a>
    </div>

    <div class="bg-white rounded shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Action</p>
                <p class="font-medium">{{ $log->action }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Performed by</p>
                <p class="font-medium">{{ optional($log->user)->name ?? 'System / Unknown' }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Model</p>
                <p class="font-medium">{{ class_basename($log->model_type) }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Model ID</p>
                <p class="font-medium">{{ $log->model_id ?? '-' }}</p>
            </div>

            <div class="md:col-span-2">
                <p class="text-sm text-gray-500">Description</p>
                <p>{{ $log->description }}</p>
            </div>

            <div class="md:col-span-2">
                <p class="text-sm text-gray-500">When</p>
                <p>{{ $log->created_at->toDateTimeString() }}</p>
            </div>
        </div>

        <hr class="my-4">

        <h2 class="text-lg font-medium mb-2">Changes</h2>

        @if (is_array($log->changes) && count($log->changes))
            <div class="overflow-x-auto bg-gray-50 rounded p-3">
                <table class="min-w-full">
                    <thead class="text-xs text-gray-500">
                        <tr>
                            <th class="px-3 py-1 text-left">Field</th>
                            <th class="px-3 py-1 text-left">Old</th>
                            <th class="px-3 py-1 text-left">New</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($log->changes as $field => $change)
                            <tr>
                                <td class="px-3 py-2 font-medium">{{ $field }}</td>
                                <td class="px-3 py-2">{{ is_numeric($change['old']) ? number_format($change['old'], 2) : ($change['old'] ?? '') }}</td>
                                <td class="px-3 py-2">{{ is_numeric($change['new']) ? number_format($change['new'], 2) : ($change['new'] ?? '') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button id="toggleRaw" class="px-3 py-1 bg-gray-200 rounded">View raw JSON</button>
                <pre id="rawJson" class="hidden mt-2 p-2 bg-black text-white overflow-auto">{{ json_encode($log->changes, JSON_PRETTY_PRINT) }}</pre>
            </div>

            <script>
                document.getElementById('toggleRaw').addEventListener('click', function () {
                    var pre = document.getElementById('rawJson');
                    pre.classList.toggle('hidden');
                });
            </script>
        @else
            <p class="text-sm text-gray-500">No recorded field changes for this entry.</p>
        @endif
    </div>
</div>
@endsection
