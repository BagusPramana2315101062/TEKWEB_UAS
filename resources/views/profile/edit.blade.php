<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-semibold">Recent Activity & Summary</h2>
                    <div class="mt-3">
                        <div>
                            <h3 class="font-semibold">Transactions ({{ $transactionsCount ?? 0 }})</h3>
                            <ul class="list-disc list-inside text-sm text-gray-700 mt-2">
                                @forelse($recentTransactions ?? [] as $t)
                                    <li>{{ $t->code }} &middot; {{ $t->transacted_at? $t->transacted_at->format('Y-m-d') : '-' }} &middot; {{ $t->status }}</li>
                                @empty
                                    <li class="text-gray-500">No transactions yet.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('transactions.index') }}" class="inline-block px-3 py-1 bg-blue-600 text-white rounded">View My Transactions</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
