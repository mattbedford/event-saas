<x-filament-panels::page>
    <div x-data="badgeBuilder()" x-init="init()" class="space-y-6">
        {{-- Form for uploads and basic settings --}}
        <form wire:submit="saveTemplate">
            {{ $this->form }}
        </form>

        {{-- Badge Canvas Builder --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Panel: Field List --}}
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-4">Available Fields</h3>

                    <div class="space-y-2">
                        <button type="button" @click="$wire.addField('full_name', 'Full Name')"
                                class="w-full px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 transition">
                            + Full Name
                        </button>
                        <button type="button" @click="$wire.addField('company', 'Company')"
                                class="w-full px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 transition">
                            + Company
                        </button>
                        <button type="button" @click="$wire.addField('email', 'Email')"
                                class="w-full px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 transition">
                            + Email
                        </button>
                    </div>
                </div>

                {{-- Active Fields Panel --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-4">Active Fields</h3>
                    <div class="space-y-3">
                        @foreach($data['fields'] ?? [] as $index => $field)
                            <div class="border border-gray-300 dark:border-gray-600 rounded p-3">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium">{{ $field['label'] }}</span>
                                    <button type="button" wire:click="removeField({{ $index }})"
                                            class="text-red-600 hover:text-red-800">×</button>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <input type="number" wire:model.blur="data.fields.{{ $index }}.font_size"
                                           class="w-full rounded border-gray-300 px-2 py-1" placeholder="Font Size">
                                    <input type="color" wire:model.blur="data.fields.{{ $index }}.color"
                                           class="w-full h-8 rounded">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Center: Canvas --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Badge Preview (Drag to position)</h3>
                    <div class="relative mx-auto border-2 border-gray-400 rounded overflow-hidden"
                         :style="`width: ${canvasWidth}px; height: ${canvasHeight}px; background: ${backgroundColor};`"
                         @drop="drop($event)" @dragover.prevent>

                        @foreach($data['fields'] ?? [] as $index => $field)
                            <div class="absolute cursor-move" draggable="true"
                                 @dragstart="startDrag($event, {{ $index }})"
                                 x-init="fields[{{ $index }}] = {{ json_encode($field['position']) }}"
                                 :style="`left: ${fields[{{ $index }}]?.x || 0}px; top: ${fields[{{ $index }}]?.y || 0}px;`">
                                <div :style="`font-size: {{ $field['font_size'] }}px; color: {{ $field['color'] }}; border: 2px dashed rgba(59, 130, 246, 0.5); padding: 4px;`">
                                    Sample {{ $field['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function badgeBuilder() {
            return {
                canvasWidth: @js($data['width'] ?? 400),
                canvasHeight: @js($data['height'] ?? 300),
                backgroundColor: @js($data['background_color'] ?? '#667eea'),
                fields: {},
                draggedIndex: null,
                dragOffset: { x: 0, y: 0 },

                init() {},

                startDrag(event, index) {
                    this.draggedIndex = index;
                    const rect = event.target.getBoundingClientRect();
                    this.dragOffset = {
                        x: event.clientX - rect.left,
                        y: event.clientY - rect.top
                    };
                },

                drop(event) {
                    event.preventDefault();
                    if (this.draggedIndex === null) return;

                    const rect = event.currentTarget.getBoundingClientRect();
                    let x = Math.max(0, Math.min(event.clientX - rect.left - this.dragOffset.x, this.canvasWidth - 100));
                    let y = Math.max(0, Math.min(event.clientY - rect.top - this.dragOffset.y, this.canvasHeight - 50));

                    this.fields[this.draggedIndex] = { x: Math.round(x), y: Math.round(y) };

                    const fieldName = @js(array_column($data['fields'] ?? [], 'name'))[this.draggedIndex];
                    this.$wire.updateFieldPosition(fieldName, this.fields[this.draggedIndex].x, this.fields[this.draggedIndex].y);

                    this.draggedIndex = null;
                }
            }
        }
    </script>
</x-filament-panels::page>
