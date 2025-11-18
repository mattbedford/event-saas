<x-filament-panels::page>
    <div x-data="emailBuilder()" x-init="init()" class="space-y-6">
        {{-- Quick Actions Bar --}}
        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Email Builder</h3>
                <span class="text-xs text-gray-500" x-text="`${blocks.length} blocks`"></span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="showMobilePreview = !showMobilePreview"
                        :class="showMobilePreview ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-gray-100 text-gray-600 border-gray-300'"
                        class="px-3 py-1.5 text-xs font-medium rounded-md border transition-all">
                    <span x-show="showMobilePreview">📱 Mobile</span>
                    <span x-show="!showMobilePreview">🖥️ Desktop</span>
                </button>
            </div>
        </div>

        {{-- Main Layout: 3 Columns --}}
        <div class="grid grid-cols-12 gap-6">

            {{-- Left Panel: Available Blocks --}}
            <div class="col-span-12 lg:col-span-3 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Content Blocks</h3>
                    <p class="text-xs text-gray-500 mb-4">Click to add blocks to your email</p>

                    <div class="space-y-2">
                        @foreach($availableBlocks as $block)
                            <button
                                @click="addBlockFromTemplate({{ json_encode($block) }})"
                                class="w-full flex items-center gap-3 p-3 rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-400 hover:bg-blue-50 transition-all group">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                                    @if($block['type'] === 'text')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                                        </svg>
                                    @elseif($block['type'] === 'heading')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                        </svg>
                                    @elseif($block['type'] === 'button')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                                        </svg>
                                    @elseif($block['type'] === 'image')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    @elseif($block['type'] === 'spacer')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                        </svg>
                                    @elseif($block['type'] === 'divider')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 text-left">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-600">
                                        {{ $block['label'] }}
                                    </div>
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

                {{-- Available Variables --}}
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 p-4">
                    <h4 class="text-xs font-semibold text-amber-700 dark:text-amber-300 mb-2">📋 Variables</h4>
                    <div class="text-xs text-amber-600 dark:text-amber-400 space-y-1">
                        <code class="block">@{{ '{{full_name}}' }}</code>
                        <code class="block">@{{ '{{email}}' }}</code>
                        <code class="block">@{{ '{{event_name}}' }}</code>
                        <code class="block">@{{ '{{company}}' }}</code>
                    </div>
                </div>
            </div>

            {{-- Center: Email Preview --}}
            <div class="col-span-12 lg:col-span-6">
                <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-6 min-h-[600px]">
                    {{-- Email Container with dynamic width --}}
                    <div :style="`max-width: ${showMobilePreview ? '375px' : settings.content_width + 'px'}; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);`">

                        {{-- Sortable Block List --}}
                        <div x-ref="blockList"
                             :style="`background-color: ${settings.background_color}; font-family: ${settings.font_family}; padding: 40px 30px;`"
                             @drop="drop($event)"
                             @dragover.prevent>

                            <template x-for="(block, index) in blocks" :key="'block-' + index">
                                <div :draggable="true"
                                     @dragstart="startDrag($event, index)"
                                     @click="selectBlock(index)"
                                     :class="selectedBlock === index ? 'ring-2 ring-blue-500' : ''"
                                     class="relative group mb-4 cursor-move transition-all hover:ring-2 hover:ring-blue-300">

                                    {{-- Text Block --}}
                                    <template x-if="block.type === 'text'">
                                        <div :style="`text-align: ${block.alignment}; font-size: ${block.font_size}px; color: ${block.color}; line-height: 1.6;`"
                                             x-html="block.content"></div>
                                    </template>

                                    {{-- Heading Block --}}
                                    <template x-if="block.type === 'heading'">
                                        <h2 :style="`text-align: ${block.alignment}; font-size: ${block.font_size}px; color: ${block.color}; font-weight: bold; margin: 0;`"
                                            x-html="block.content"></h2>
                                    </template>

                                    {{-- Button Block --}}
                                    <template x-if="block.type === 'button'">
                                        <div :style="`text-align: ${block.alignment};`">
                                            <a :href="block.url"
                                               :style="`display: inline-block; padding: 12px 24px; background-color: ${block.bg_color}; color: ${block.text_color}; text-decoration: none; border-radius: ${block.border_radius}px; font-weight: 600;`"
                                               x-text="block.text"></a>
                                        </div>
                                    </template>

                                    {{-- Image Block --}}
                                    <template x-if="block.type === 'image'">
                                        <div :style="`text-align: ${block.alignment};`">
                                            <img :src="block.url || 'https://via.placeholder.com/600x300?text=Add+Image+URL'"
                                                 :alt="block.alt"
                                                 :style="`width: ${block.width}%; max-width: 100%; height: auto;`" />
                                        </div>
                                    </template>

                                    {{-- Spacer Block --}}
                                    <template x-if="block.type === 'spacer'">
                                        <div :style="`height: ${block.height}px;`"></div>
                                    </template>

                                    {{-- Divider Block --}}
                                    <template x-if="block.type === 'divider'">
                                        <hr :style="`border: none; border-top: ${block.thickness}px solid ${block.color}; margin: 20px 0;`" />
                                    </template>

                                    {{-- Delete Button (on hover) --}}
                                    <button @click.stop="removeBlockAtIndex(index)"
                                            class="absolute top-0 right-0 bg-red-500 text-white p-1.5 rounded-bl-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            <div x-show="blocks.length === 0" class="text-center py-12 text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-sm">No content blocks yet</p>
                                <p class="text-xs mt-1">Add blocks from the left panel</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Panel: Block Properties --}}
            <div class="col-span-12 lg:col-span-3 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Block Properties</h3>

                    <div x-show="selectedBlock !== null" class="space-y-4">
                        {{-- Text Block Properties --}}
                        <template x-if="blocks[selectedBlock]?.type === 'text' || blocks[selectedBlock]?.type === 'heading'">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label>
                                    <textarea x-model="blocks[selectedBlock].content"
                                              @input="updateBlock(selectedBlock, {content: $event.target.value})"
                                              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md"
                                              rows="3"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Font Size (px)</label>
                                    <input type="number" x-model="blocks[selectedBlock].font_size"
                                           @input="updateBlock(selectedBlock, {font_size: $event.target.value})"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                                    <input type="color" x-model="blocks[selectedBlock].color"
                                           @input="updateBlock(selectedBlock, {color: $event.target.value})"
                                           class="w-full h-10 border border-gray-300 rounded-md" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Alignment</label>
                                    <select x-model="blocks[selectedBlock].alignment"
                                            @change="updateBlock(selectedBlock, {alignment: $event.target.value})"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                                        <option value="left">Left</option>
                                        <option value="center">Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                </div>
                            </div>
                        </template>

                        {{-- Button Block Properties --}}
                        <template x-if="blocks[selectedBlock]?.type === 'button'">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Button Text</label>
                                    <input type="text" x-model="blocks[selectedBlock].text"
                                           @input="updateBlock(selectedBlock, {text: $event.target.value})"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">URL</label>
                                    <input type="url" x-model="blocks[selectedBlock].url"
                                           @input="updateBlock(selectedBlock, {url: $event.target.value})"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background Color</label>
                                    <input type="color" x-model="blocks[selectedBlock].bg_color"
                                           @input="updateBlock(selectedBlock, {bg_color: $event.target.value})"
                                           class="w-full h-10 border border-gray-300 rounded-md" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                                    <input type="color" x-model="blocks[selectedBlock].text_color"
                                           @input="updateBlock(selectedBlock, {text_color: $event.target.value})"
                                           class="w-full h-10 border border-gray-300 rounded-md" />
                                </div>
                            </div>
                        </template>

                        {{-- Image Block Properties --}}
                        <template x-if="blocks[selectedBlock]?.type === 'image'">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Image URL</label>
                                    <input type="url" x-model="blocks[selectedBlock].url"
                                           @input="updateBlock(selectedBlock, {url: $event.target.value})"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Width (%)</label>
                                    <input type="number" min="10" max="100" x-model="blocks[selectedBlock].width"
                                           @input="updateBlock(selectedBlock, {width: $event.target.value})"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" />
                                </div>
                            </div>
                        </template>

                        {{-- Spacer Block Properties --}}
                        <template x-if="blocks[selectedBlock]?.type === 'spacer'">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Height (px)</label>
                                <input type="number" x-model="blocks[selectedBlock].height"
                                       @input="updateBlock(selectedBlock, {height: $event.target.value})"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" />
                            </div>
                        </template>

                        {{-- Divider Block Properties --}}
                        <template x-if="blocks[selectedBlock]?.type === 'divider'">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                                    <input type="color" x-model="blocks[selectedBlock].color"
                                           @input="updateBlock(selectedBlock, {color: $event.target.value})"
                                           class="w-full h-10 border border-gray-300 rounded-md" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Thickness (px)</label>
                                    <input type="number" x-model="blocks[selectedBlock].thickness"
                                           @input="updateBlock(selectedBlock, {thickness: $event.target.value})"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" />
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="selectedBlock === null" class="text-center py-8 text-gray-400">
                        <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                        <p class="text-xs">Select a block to edit</p>
                    </div>
                </div>

                {{-- Email Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3">Email Settings</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Content Width (px)</label>
                            <input type="number" x-model="settings.content_width"
                                   @input="updateSettings({content_width: $event.target.value})"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background Color</label>
                            <input type="color" x-model="settings.background_color"
                                   @input="updateSettings({background_color: $event.target.value})"
                                   class="w-full h-10 border border-gray-300 rounded-md" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function emailBuilder() {
            return {
                blocks: @js($data['blocks'] ?? []),
                settings: @js($data['settings'] ?? []),
                selectedBlock: null,
                draggedIndex: null,
                showMobilePreview: false,

                init() {
                    console.log('Email builder initialized with', this.blocks.length, 'blocks');
                },

                addBlockFromTemplate(template) {
                    const newBlock = { ...template.default, type: template.type };
                    this.$wire.addBlock(newBlock).then(() => {
                        this.blocks = this.$wire.data.blocks;
                    });
                },

                startDrag(event, index) {
                    this.draggedIndex = index;
                    event.dataTransfer.effectAllowed = 'move';
                    event.target.style.opacity = '0.5';
                },

                drop(event) {
                    event.preventDefault();
                    event.target.style.opacity = '1';

                    if (this.draggedIndex === null) return;

                    const dropTarget = event.target.closest('[draggable="true"]');
                    if (!dropTarget) return;

                    const dropIndex = Array.from(this.$refs.blockList.children)
                        .indexOf(dropTarget);

                    if (dropIndex === -1 || dropIndex === this.draggedIndex) return;

                    // Reorder blocks
                    const item = this.blocks.splice(this.draggedIndex, 1)[0];
                    this.blocks.splice(dropIndex, 0, item);

                    const order = this.blocks.map((_, i) => i);
                    this.$wire.reorderBlocks(order);

                    this.draggedIndex = null;
                },

                selectBlock(index) {
                    this.selectedBlock = index;
                },

                updateBlock(index, updates) {
                    this.$wire.updateBlock(index, updates);
                },

                removeBlockAtIndex(index) {
                    if (confirm('Remove this block?')) {
                        this.$wire.removeBlock(index).then(() => {
                            this.blocks = this.$wire.data.blocks;
                            this.selectedBlock = null;
                        });
                    }
                },

                updateSettings(settings) {
                    this.$wire.updateSettings(settings);
                }
            }
        }
    </script>
</x-filament-panels::page>
