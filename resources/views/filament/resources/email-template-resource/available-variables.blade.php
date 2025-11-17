<div class="prose dark:prose-invert max-w-none">
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Use these variables in your email subject and content. They will be automatically replaced with actual values when the email is sent.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="font-semibold text-sm mb-2">Registration Variables</h4>
            <ul class="space-y-1 text-sm">
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{full_name}}</code> - Attendee's full name</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{email}}</code> - Attendee's email</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{phone}}</code> - Attendee's phone</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{company}}</code> - Company name</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{registration_id}}</code> - Registration ID</li>
            </ul>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="font-semibold text-sm mb-2">Event Variables</h4>
            <ul class="space-y-1 text-sm">
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{event_name}}</code> - Event name</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{event_date}}</code> - Event date</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{event_slug}}</code> - Event URL slug</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{ticket_price}}</code> - Original ticket price</li>
            </ul>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="font-semibold text-sm mb-2">Payment Variables</h4>
            <ul class="space-y-1 text-sm">
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{paid_amount}}</code> - Amount paid</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{discount_amount}}</code> - Discount applied</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{coupon_code}}</code> - Coupon used</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{payment_status}}</code> - Payment status</li>
            </ul>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="font-semibold text-sm mb-2">Badge Variables</h4>
            <ul class="space-y-1 text-sm">
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{badge_url}}</code> - Badge download URL</li>
                <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{badge_link}}</code> - Full badge download link</li>
            </ul>
        </div>
    </div>

    <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <p class="text-sm text-blue-800 dark:text-blue-300">
            <strong>Example:</strong>
            <span class="block mt-2 font-mono text-xs">
                Hi {{full_name}}, thank you for registering for {{event_name}}!<br>
                Your event is on {{event_date}}. Download your badge here: {{badge_link}}
            </span>
        </p>
    </div>
</div>
