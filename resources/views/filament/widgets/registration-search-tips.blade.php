<x-filament-widgets::widget>
    <x-filament::section
        collapsible
        collapsed
        icon="heroicon-o-magnifying-glass"
        icon-color="info"
    >
        <x-slot name="heading">
            💡 Quick Search Tips
        </x-slot>

        <div class="text-sm space-y-2">
            <p class="font-medium text-gray-700 dark:text-gray-300">
                Use the search box above to find registrations quickly:
            </p>

            <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
                <li><strong>Name:</strong> Search by first or last name (e.g., "Winkleman")</li>
                <li><strong>Email:</strong> Search by email address</li>
                <li><strong>Company:</strong> Find all registrations from a company</li>
                <li><strong>Event:</strong> Search by event name</li>
                <li><strong>Coupon:</strong> Find who used a specific coupon code</li>
                <li><strong>Stripe ID:</strong> Paste a Stripe Session or Payment Intent ID to find the registration</li>
                <li><strong>Phone:</strong> Search by phone number</li>
            </ul>

            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <p class="text-xs font-medium text-blue-800 dark:text-blue-200">
                    <strong>Pro Tip:</strong> You can also use the global search (Cmd+K or Ctrl+K) from anywhere in the admin panel to quickly jump to a registration!
                </p>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <strong>Common scenarios:</strong>
                </p>
                <ul class="text-xs text-gray-500 dark:text-gray-400 mt-1 space-y-0.5">
                    <li>• "Did Winkleman sign up?" → Search "winkleman"</li>
                    <li>• "Which event for this Stripe payment?" → Paste the Stripe ID</li>
                    <li>• "Show all Tech Corp registrations" → Search "Tech Corp"</li>
                    <li>• "Who used coupon VIP50?" → Search "VIP50"</li>
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
