<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="font-semibold text-sm mb-2">Subject:</h3>
        <p class="text-sm">{{ $template->subject }}</p>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="font-semibold text-sm mb-4">HTML Content:</h3>
        <div class="prose dark:prose-invert max-w-none">
            {!! $template->html_content !!}
        </div>
    </div>

    @if($template->text_content)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="font-semibold text-sm mb-2">Plain Text Content:</h3>
            <pre class="text-sm whitespace-pre-wrap">{{ $template->text_content }}</pre>
        </div>
    @endif

    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <p class="text-xs text-yellow-800 dark:text-yellow-300">
            <strong>Note:</strong> Variables like {{full_name}}, {{event_name}}, etc. will be replaced with actual values when the email is sent.
        </p>
    </div>
</div>
