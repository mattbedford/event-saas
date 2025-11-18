<x-filament-panels::page>
    <div x-data="badgeBuilder()" x-init="init()" class="space-y-6">
        {{-- Quick Actions Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex flex-wrap gap-3 items-center justify-between">
                <div class="flex gap-2">
                    <button type="button" @click="gridSnap = !gridSnap" class="px-3 py-2 rounded border transition"
                            :class="gridSnap ? 'bg-blue-100 border-blue-500 text-blue-700' : 'border-gray-300'">
                        <span x-show="gridSnap">✓</span> Snap to Grid
                    </button>
                    <button type="button" @click="showGrid = !showGrid" class="px-3 py-2 rounded border transition"
                            :class="showGrid ? 'bg-blue-100 border-blue-500 text-blue-700' : 'border-gray-300'">
                        <span x-show="showGrid">✓</span> Show Grid
                    </button>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Canvas: <span x-text="canvasWidth"></span> × <span x-text="canvasHeight"></span> px
                </div>
            </div>
        </div>

        {{-- Form for uploads and basic settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form wire:submit="saveTemplate">
                {{ $this->form }}
            </form>
        </div>

        {{-- Badge Canvas Builder --}}
        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
            {{-- Left Panel: Available Fields to Drag --}}
            <div class="xl:col-span-1 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">📋 Add Fields</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Drag onto canvas or click to add</p>

                    <div class="space-y-2">
                        <button type="button" @click="addFieldAtCenter('full_name', 'Full Name')"
                                class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-sm font-medium text-left flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Full Name
                        </button>
                        <button type="button" @click="addFieldAtCenter('company', 'Company')"
                                class="w-full px-4 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition shadow-sm font-medium text-left flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            Company
                        </button>
                        <button type="button" @click="addFieldAtCenter('email', 'Email')"
                                class="w-full px-4 py-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:from-purple-600 hover:to-purple-700 transition shadow-sm font-medium text-left flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            Email
                        </button>
                        <button type="button" @click="addFieldAtCenter('phone', 'Phone')"
                                class="w-full px-4 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-orange-700 transition shadow-sm font-medium text-left flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            Phone
                        </button>
                        <button type="button" @click="addFieldAtCenter('event_name', 'Event Name')"
                                class="w-full px-4 py-3 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 transition shadow-sm font-medium text-left flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Event Name
                        </button>
                    </div>
                </div>

                {{-- Quick Tips --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Quick Tips
                    </h4>
                    <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                        <li>• Drag fields to position</li>
                        <li>• Click field to edit styling</li>
                        <li>• Enable grid for alignment</li>
                        <li>• Upload PDF for background</li>
                    </ul>
                </div>
            </div>

            {{-- Center: Canvas --}}
            <div class="xl:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">🎨 Badge Canvas</h3>
                        <div class="flex gap-2">
                            <button type="button" @click="zoomIn" class="px-3 py-1 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">+</button>
                            <span class="px-3 py-1 text-sm text-gray-600 dark:text-gray-400" x-text="Math.round(zoom * 100) + '%'"></span>
                            <button type="button" @click="zoomOut" class="px-3 py-1 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">−</button>
                        </div>
                    </div>

                    <div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-100 dark:bg-gray-900 overflow-auto">
                        <div class="relative mx-auto transition-transform"
                             :style="`width: ${canvasWidth}px; height: ${canvasHeight}px; transform: scale(${zoom}); transform-origin: top left;`">
                            <div class="w-full h-full relative shadow-2xl rounded-lg overflow-hidden"
                                 :style="`background: ${backgroundColor};`"
                                 @drop="drop($event)" @dragover.prevent>

                                {{-- PDF Background Indicator --}}
                                @if(isset($data['background_pdf']) && $data['background_pdf'])
                                    <div class="absolute inset-0 bg-white opacity-95 flex items-center justify-center">
                                        <div class="text-center p-6">
                                            <svg class="w-20 h-20 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="text-lg font-semibold text-gray-700">PDF Background</p>
                                            <p class="text-sm text-gray-500 mt-1">{{ basename($data['background_pdf'] ?? '') }}</p>
                                            <p class="text-xs text-gray-400 mt-2">Your text fields will be layered on top</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Grid Overlay --}}
                                <div x-show="showGrid" class="absolute inset-0 pointer-events-none opacity-20"
                                     style="background-image: linear-gradient(#999 1px, transparent 1px), linear-gradient(90deg, #999 1px, transparent 1px); background-size: 20px 20px;"></div>

                                {{-- Draggable Fields --}}
                                @foreach($data['fields'] ?? [] as $index => $field)
                                    <div class="absolute cursor-move group"
                                         draggable="true"
                                         @dragstart="startDrag($event, {{ $index }})"
                                         @click="selectField({{ $index }})"
                                         x-init="fields[{{ $index }}] = {{ json_encode($field['position'] ?? ['x' => 100, 'y' => 100]) }}"
                                         :style="`left: ${fields[{{ $index }}]?.x || 0}px; top: ${fields[{{ $index }}]?.y || 0}px;`">
                                        <div class="relative"
                                             :style="`font-size: {{ $field['font_size'] ?? 14 }}px;
                                                      color: {{ $field['color'] ?? '#000000' }};
                                                      font-weight: {{ $field['font_weight'] ?? 'normal' }};
                                                      text-align: {{ $field['align'] ?? 'center' }};
                                                      min-width: 100px;
                                                      padding: 8px;
                                                      border: 2px dashed transparent;
                                                      background: rgba(59, 130, 246, 0.05);`"
                                             :class="{'!border-blue-500 !bg-blue-100 !bg-opacity-30': selectedField === {{ $index }}}">
                                            <div class="pointer-events-none">
                                                {{ $field['label'] ?? 'Sample Text' }}
                                            </div>
                                            <div class="absolute -top-6 left-0 opacity-0 group-hover:opacity-100 transition bg-gray-900 text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                                                {{ $field['label'] }} (drag to move)
                                            </div>
                                        </div>
                                    </div>
                                @endforeach>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Panel: Properties --}}
            <div class="xl:col-span-1 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4" x-show="selectedField !== null">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">✏️ Field Properties</h3>

                    <div class="space-y-4">
                        <template x-if="selectedField !== null">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Font Size</label>
                                    <input type="range" min="8" max="72" step="1"
                                           :value="selectedFieldData?.font_size || 14"
                                           @input="updateFieldStyle('font_size', $event.target.value)"
                                           class="w-full">
                                    <div class="text-right text-xs text-gray-500" x-text="(selectedFieldData?.font_size || 14) + 'px'"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Font Weight</label>
                                    <select @change="updateFieldStyle('font_weight', $event.target.value)"
                                            :value="selectedFieldData?.font_weight || 'normal'"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                        <option value="300">Light</option>
                                        <option value="normal">Normal</option>
                                        <option value="600">Semi-Bold</option>
                                        <option value="bold">Bold</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Text Color</label>
                                    <input type="color"
                                           :value="selectedFieldData?.color || '#000000'"
                                           @input="updateFieldStyle('color', $event.target.value)"
                                           class="w-full h-10 rounded-md border-gray-300 dark:border-gray-700">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alignment</label>
                                    <select @change="updateFieldStyle('align', $event.target.value)"
                                            :value="selectedFieldData?.align || 'center'"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                        <option value="left">Left</option>
                                        <option value="center">Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                </div>

                                <button type="button" @click="deleteField"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition font-medium">
                                    🗑️ Delete Field
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Active Fields List --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">📑 Active Fields</h3>
                    <div class="space-y-2">
                        @forelse($data['fields'] ?? [] as $index => $field)
                            <div class="border border-gray-200 dark:border-gray-700 rounded p-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer"
                                 @click="selectField({{ $index }})">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $field['label'] }}</span>
                                    <span class="text-xs text-gray-500">{{ $field['font_size'] ?? 14 }}px</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 text-center py-4">No fields added yet</p>
                        @endforelse
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
                backgroundColor: @js($data['background_color'] ?? '#ffffff'),
                fields: {},
                draggedIndex: null,
                dragOffset: { x: 0, y: 0 },
                selectedField: null,
                selectedFieldData: null,
                gridSnap: false,
                showGrid: false,
                zoom: 1,

                init() {
                    // Initialize field positions
                    @foreach($data['fields'] ?? [] as $index => $field)
                        this.fields[{{ $index }}] = @json($field['position'] ?? ['x' => 100, 'y' => 100]);
                    @endforeach
                },

                addFieldAtCenter(name, label) {
                    const centerX = Math.round(this.canvasWidth / 2 - 50);
                    const centerY = Math.round(this.canvasHeight / 2);
                    this.$wire.addField(name, label).then(() => {
                        setTimeout(() => {
                            const newIndex = Object.keys(this.fields).length;
                            this.fields[newIndex] = { x: centerX, y: centerY };
                            this.$wire.updateFieldPosition(name, centerX, centerY);
                        }, 100);
                    });
                },

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
                    let x = event.clientX - rect.left - this.dragOffset.x;
                    let y = event.clientY - rect.top - this.dragOffset.y;

                    // Apply zoom
                    x = x / this.zoom;
                    y = y / this.zoom;

                    // Snap to grid if enabled
                    if (this.gridSnap) {
                        x = Math.round(x / 20) * 20;
                        y = Math.round(y / 20) * 20;
                    }

                    // Constrain to canvas
                    x = Math.max(0, Math.min(x, this.canvasWidth - 100));
                    y = Math.max(0, Math.min(y, this.canvasHeight - 30));

                    this.fields[this.draggedIndex] = { x: Math.round(x), y: Math.round(y) };

                    const fieldName = @js(array_column($data['fields'] ?? [], 'name'))[this.draggedIndex];
                    this.$wire.updateFieldPosition(fieldName, Math.round(x), Math.round(y));

                    this.draggedIndex = null;
                },

                selectField(index) {
                    this.selectedField = index;
                    this.selectedFieldData = @js($data['fields'])[index];
                },

                updateFieldStyle(property, value) {
                    if (this.selectedField !== null) {
                        const style = {};
                        style[property] = property === 'font_size' ? parseInt(value) : value;
                        this.$wire.updateFieldStyle(this.selectedField, style);

                        // Update local data
                        if (!this.selectedFieldData) this.selectedFieldData = {};
                        this.selectedFieldData[property] = property === 'font_size' ? parseInt(value) : value;
                    }
                },

                deleteField() {
                    if (this.selectedField !== null && confirm('Delete this field?')) {
                        this.$wire.removeField(this.selectedField).then(() => {
                            this.selectedField = null;
                            this.selectedFieldData = null;
                        });
                    }
                },

                zoomIn() {
                    this.zoom = Math.min(2, this.zoom + 0.1);
                },

                zoomOut() {
                    this.zoom = Math.max(0.5, this.zoom - 0.1);
                }
            }
        }
    </script>
</x-filament-panels::page>
