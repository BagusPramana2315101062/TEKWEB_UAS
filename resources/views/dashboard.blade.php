<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid gap-6 md:grid-cols-3 sm:grid-cols-1">
                        <!-- Products card -->
                        <div class="p-4 bg-white border rounded-lg shadow-sm">
                            <h3 class="text-sm font-medium text-gray-500">Products Available</h3>
                            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $productCount ?? 0 }}</div>
                            <p class="mt-2 text-sm text-gray-600">Number of products available in the catalog.</p>
                            <div class="mt-4">
                                <a href="{{ route('products.index') }}" class="inline-block px-3 py-2 bg-blue-600 text-white rounded">{{ __('View Products') }}</a>
                            </div>
                        </div>

                        <!-- Recent transactions card -->
                        <div class="p-4 bg-white border rounded-lg shadow-sm md:col-span-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Recent Transactions</h3>
                                    <p class="mt-2 text-sm text-gray-600">Your last transactions with status and total.</p>
                                </div>
                                <div class="mt-1">
                                    <a href="{{ route('transactions.index') }}" class="px-3 py-2 border rounded">{{ __('View My Transactions') }}</a>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                @if($recentTransactions && $recentTransactions->count())
                                    @foreach($recentTransactions as $tx)
                                        <div class="flex items-center justify-between p-3 border rounded">
                                            <div>
                                                <div class="text-sm font-medium text-gray-800">{{ $tx->code }}</div>
                                                <div class="text-xs text-gray-500">{{ $tx->created_at->format('Y-m-d') }} — {{ $tx->status }}</div>
                                            </div>
                                            <div class="text-sm font-semibold text-gray-900">{{ number_format($tx->grand_total, 0, ',', '.') }}</div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-sm text-gray-500">No recent transactions.</div>
                                @endif
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('products.index') }}" class="inline-block px-3 py-2 bg-green-600 text-white rounded">{{ __('Create Order') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
