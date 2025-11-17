<div class="space-y-6">
    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Uses</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                {{ $stats['total_uses'] }}
                @if($coupon->max_uses)
                    <span class="text-sm text-gray-500">/ {{ $coupon->max_uses }}</span>
                @endif
            </dd>
        </div>

        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Remaining Uses</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                {{ $stats['remaining_uses'] ?? '∞' }}
            </dd>
        </div>

        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</dt>
            <dd class="mt-1 text-3xl font-semibold text-green-600 dark:text-green-400">
                CHF {{ number_format($stats['total_revenue'], 2) }}
            </dd>
        </div>

        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Discount Given</dt>
            <dd class="mt-1 text-3xl font-semibold text-red-600 dark:text-red-400">
                CHF {{ number_format($stats['total_discount'], 2) }}
            </dd>
        </div>
    </div>

    {{-- Registrations Table --}}
    @if(count($stats['registrations']) > 0)
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Registrations Using This Coupon</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Name
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Email
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Event
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Status
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Paid
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Discount
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @foreach($stats['registrations'] as $registration)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $registration['name'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $registration['email'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $registration['event'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                        @if($registration['payment_status'] === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($registration['payment_status'] === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endif">
                                        {{ ucfirst($registration['payment_status']) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                                    CHF {{ number_format($registration['paid_amount'], 2) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-red-600 dark:text-red-400">
                                    CHF {{ number_format($registration['discount_amount'], 2) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($registration['created_at'])->format('M d, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="rounded-lg bg-white p-8 text-center shadow dark:bg-gray-800">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No registrations yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                This coupon hasn't been used for any registrations yet.
            </p>
        </div>
    @endif
</div>
