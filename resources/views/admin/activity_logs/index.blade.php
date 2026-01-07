@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Activity Logs</h1>
    </div>

    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label class="block text-sm font-medium">Action</label>
            <select name="action" class="mt-1 block w-full rounded border-gray-300">
                <option value="">-- Any --</option>
                <option value="CREATE" {{ (isset($filters['action']) && $filters['action']=='CREATE') ? 'selected' : '' }}>CREATE</option>
                <option value="UPDATE" {{ (isset($filters['action']) && $filters['action']=='UPDATE') ? 'selected' : '' }}>UPDATE</option>
                <option value="DELETE" {{ (isset($filters['action']) && $filters['action']=='DELETE') ? 'selected' : '' }}>DELETE</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium">Model Type</label>
            <input type="text" name="model_type" value="{{ $filters['model_type'] ?? '' }}" class="mt-1 block w-full rounded border-gray-300" placeholder="e.g., Product">
        </div>

        <div>
            <label class="block text-sm font-medium">Model ID</label>
            <input type="text" name="model_id" value="{{ $filters['model_id'] ?? '' }}" class="mt-1 block w-full rounded border-gray-300" placeholder="Optional">
        </div>

        <div>
            <label class="block text-sm font-medium">User ID</label>
            <input type="text" name="user_id" value="{{ $filters['user_id'] ?? '' }}" class="mt-1 block w-full rounded border-gray-300" placeholder="Optional">
        </div>

        <div>
            <label class="block text-sm font-medium">Date From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 block w-full rounded border-gray-300">
        </div>

        <div class="flex items-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Filter</button>
        </div>
    </form>

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">User</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Action</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Model</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Model ID</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Description</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">When</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $log->id }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ optional($log->user)->name ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $log->action }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ class_basename($log->model_type) }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $log->model_id ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $log->description }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.activity-logs.show', $log->id) }}" class="text-blue-600">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">No activity logged.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->withQueryString()->links() }}
    </div>
</div>
@endsection
