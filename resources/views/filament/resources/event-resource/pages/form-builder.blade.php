<x-filament-panels::page>
    <div x-data="formBuilder()" x-init="init()" class="space-y-6">
        {{-- Quick Actions Bar --}}
        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Form Builder</h3>
                <span class="text-xs text-gray-500" x-text="`${fields.length} fields`"></span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="autoSave = !autoSave"
                        :class="autoSave ? 'bg-green-100 text-green-700 border-green-300' : 'bg-gray-100 text-gray-600 border-gray-300'"
                        class="px-3 py-1.5 text-xs font-medium rounded-md border transition-all">
                    <span x-show="autoSave">✓ Auto-save: ON</span>
                    <span x-show="!autoSave">Auto-save: OFF</span>
                </button>
            </div>
        </div>

        {{-- Main Layout: 3 Columns --}}
        <div class="grid grid-cols-12 gap-6">

            {{-- Left Panel: Available Fields --}}
            <div class="col-span-12 lg:col-span-3 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Available Fields</h3>
                    <p class="text-xs text-gray-500 mb-4">Click to add fields to your form</p>

                    <div class="space-y-2">
                        @foreach($availableFields as $field)
                            <button
                                @click="addFieldFromTemplate({{ json_encode($field) }})"
                                class="w-full flex items-center gap-3 p-3 rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-400 hover:bg-blue-50 transition-all group">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                    @if($field['type'] === 'text')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                        </svg>
                                    @elseif($field['type'] === 'email')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    @elseif($field['type'] === 'tel')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                    @elseif($field['type'] === 'textarea')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                                        </svg>
                                    @elseif($field['type'] === 'select')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 text-left">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-600">
                                        {{ $field['label'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ ucfirst($field['type']) }}</div>
                                </div>
                                <div class="text-gray-400 group-hover:text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Quick Tips --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
                    <h4 class="text-xs font-semibold text-blue-700 dark:text-blue-300 mb-2">💡 Quick Tips</h4>
                    <ul class="text-xs text-blue-600 dark:text-blue-400 space-y-1.5">
                        <li>• Drag fields to reorder them</li>
                        <li>• Toggle fields on/off</li>
                        <li>• Click to edit properties</li>
                        <li>• Required fields have *</li>
                    </ul>
                </div>
            </div>

            {{-- Center: Form Preview --}}
            <div class="col-span-12 lg:col-span-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Form Preview</h3>
                        <span class="text-xs text-gray-500">Drag to reorder</span>
                    </div>

                    {{-- Sortable Field List --}}
                    <div class="space-y-3"
                         x-ref="fieldList"
                         @drop="drop($event)"
                         @dragover.prevent>

                        <template x-for="(field, index) in fields" :key="field.name + index">
                            <div :draggable="true"
                                 @dragstart="startDrag($event, index)"
                                 @click="selectField(index)"
                                 :class="selectedField === index ? 'ring-2 ring-blue-500' : ''"
                                 class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 cursor-move hover:shadow-md transition-all">

                                <div class="flex items-start gap-3">
                                    {{-- Drag Handle --}}
                                    <div class="flex-shrink-0 text-gray-400 mt-1">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z"></path>
                                        </svg>
                                    </div>

                                    {{-- Field Info --}}
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="field.label"></span>
                                            <span x-show="field.required" class="text-red-500 text-sm">*</span>
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400" x-text="field.type"></span>
                                        </div>

                                        {{-- Field Preview --}}
                                        <div class="mt-2">
                                            <template x-if="field.type === 'textarea'">
                                                <textarea
                                                    :placeholder="field.placeholder || 'Enter ' + field.label.toLowerCase() + '...'"
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                                                    rows="3" disabled></textarea>
                                            </template>
                                            <template x-if="field.type === 'select'">
                                                <select class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300" disabled>
                                                    <option>Select an option...</option>
                                                </select>
                                            </template>
                                            <template x-if="!['textarea', 'select'].includes(field.type)">
                                                <input
                                                    :type="field.type"
                                                    :placeholder="field.placeholder || 'Enter ' + field.label.toLowerCase() + '...'"
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                                                    disabled />
                                            </template>
                                        </div>

                                        <div x-show="field.help_text" class="mt-1 text-xs text-gray-500" x-text="field.help_text"></div>
                                    </div>

                                    {{-- Field Actions --}}
                                    <div class="flex-shrink-0 flex flex-col gap-2">
                                        <button @click.stop="toggleFieldEnabled(index)"
                                                :class="field.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                                class="px-2 py-1 text-xs rounded transition-all">
                                            <span x-show="field.enabled">ON</span>
                                            <span x-show="!field.enabled">OFF</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="fields.length === 0" class="text-center py-12 text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-sm">No fields added yet</p>
                            <p class="text-xs mt-1">Click on fields from the left panel to add them</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Panel: Field Properties --}}
            <div class="col-span-12 lg:col-span-3 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Field Properties</h3>

                    <div x-show="selectedField !== null" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                            <input type="text"
                                   x-model="fields[selectedField]?.label"
                                   @input="updateField(selectedField, {label: $event.target.value})"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Placeholder</label>
                            <input type="text"
                                   x-model="fields[selectedField]?.placeholder"
                                   @input="updateField(selectedField, {placeholder: $event.target.value})"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Help Text</label>
                            <textarea
                                x-model="fields[selectedField]?.help_text"
                                @input="updateField(selectedField, {help_text: $event.target.value})"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                rows="2"></textarea>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox"
                                   x-model="fields[selectedField]?.required"
                                   @change="updateField(selectedField, {required: $event.target.checked})"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Required field</label>
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button @click="removeFieldAtIndex(selectedField)"
                                    class="w-full px-3 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors">
                                Remove Field
                            </button>
                        </div>
                    </div>

                    <div x-show="selectedField === null" class="text-center py-8 text-gray-400">
                        <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                        <p class="text-xs">Select a field to edit</p>
                    </div>
                </div>

                {{-- Active Fields List --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Active Fields</h4>
                    <div class="space-y-1">
                        <template x-for="(field, index) in fields.filter(f => f.enabled)" :key="index">
                            <div class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                <span x-text="(index + 1) + '.'"></span>
                                <span x-text="field.label"></span>
                                <span x-show="field.required" class="text-red-500">*</span>
                            </div>
                        </template>
                        <div x-show="fields.filter(f => f.enabled).length === 0" class="text-xs text-gray-400 italic">
                            No active fields
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function formBuilder() {
            return {
                fields: @js($data['fields'] ?? []),
                selectedField: null,
                draggedIndex: null,
                autoSave: false,

                init() {
                    console.log('Form builder initialized with', this.fields.length, 'fields');
                },

                addFieldFromTemplate(template) {
                    // Check if field already exists
                    const exists = this.fields.some(f => f.name === template.name);
                    if (exists) {
                        window.dispatchEvent(new CustomEvent('show-notification', {
                            detail: {
                                type: 'warning',
                                message: 'This field is already in the form'
                            }
                        }));
                        return;
                    }

                    this.$wire.addField(template).then(() => {
                        this.fields = this.$wire.data.fields;
                        if (this.autoSave) {
                            this.save();
                        }
                    });
                },

                startDrag(event, index) {
                    this.draggedIndex = index;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', index);
                    event.target.style.opacity = '0.5';
                },

                drop(event) {
                    event.preventDefault();
                    event.target.style.opacity = '1';

                    if (this.draggedIndex === null) return;

                    const dropTarget = event.target.closest('[draggable="true"]');
                    if (!dropTarget) return;

                    const dropIndex = Array.from(this.$refs.fieldList.children)
                        .indexOf(dropTarget);

                    if (dropIndex === -1 || dropIndex === this.draggedIndex) return;

                    // Reorder fields
                    const item = this.fields.splice(this.draggedIndex, 1)[0];
                    this.fields.splice(dropIndex, 0, item);

                    const order = this.fields.map((_, i) => i);
                    this.$wire.reorderFields(order).then(() => {
                        if (this.autoSave) {
                            this.save();
                        }
                    });

                    this.draggedIndex = null;
                },

                selectField(index) {
                    this.selectedField = index;
                },

                toggleFieldEnabled(index) {
                    this.fields[index].enabled = !this.fields[index].enabled;
                    this.updateField(index, { enabled: this.fields[index].enabled });
                },

                updateField(index, updates) {
                    this.$wire.updateField(index, updates).then(() => {
                        if (this.autoSave) {
                            this.save();
                        }
                    });
                },

                removeFieldAtIndex(index) {
                    if (confirm('Are you sure you want to remove this field?')) {
                        this.$wire.removeField(index).then(() => {
                            this.fields = this.$wire.data.fields;
                            this.selectedField = null;
                            if (this.autoSave) {
                                this.save();
                            }
                        });
                    }
                },

                save() {
                    this.$wire.saveForm();
                }
            }
        }
    </script>
</x-filament-panels::page>
