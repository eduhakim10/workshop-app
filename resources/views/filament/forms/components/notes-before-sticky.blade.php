{{--
    Sidebar Notes Before (sempit) di kanan section Item Penawaran.
    Items tetap lebar; panel ini hanya ~2/12 kolom.
--}}
<aside
    wire:ignore
    x-data="{
        notes: '',
        collapsed: false,
        refresh() {
            let value = '';
            const input = document.getElementById('quotation-notes-before-source');
            if (input) {
                value = (input.value || '').trim();
            }
            if (! value) {
                try {
                    value = String(this.$wire.get('data.notes_before') || this.$wire.get('notes_before') || '').trim();
                } catch (e) {}
            }
            this.notes = value;
        },
        init() {
            this.refresh();
            this.$nextTick(() => this.refresh());
            setTimeout(() => this.refresh(), 300);

            if (window.Livewire) {
                Livewire.hook('morph.updated', () => setTimeout(() => this.refresh(), 50));
            }
        },
    }"
    x-init="init()"
    class="w-full min-w-0"
    aria-label="Notes Before"
>
    <div class="overflow-hidden rounded-xl border border-amber-300 bg-amber-50 shadow-sm ring-1 ring-amber-900/10 dark:border-amber-500/40 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-start gap-1.5 border-b border-amber-200 bg-amber-100 px-2.5 py-2 dark:border-white/10 dark:bg-amber-500/10">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-700 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
            </svg>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold leading-tight text-amber-950 dark:text-amber-100">Notes Before</p>
                <p class="text-[10px] leading-tight text-amber-800/80 dark:text-amber-200/70">Acuan item</p>
            </div>
            <button
                type="button"
                class="rounded p-0.5 text-amber-800 hover:bg-amber-200/70 dark:text-amber-200 dark:hover:bg-white/10"
                @click="collapsed = !collapsed"
                :title="collapsed ? 'Perbesar' : 'Ciutkan'"
            >
                <svg class="h-3.5 w-3.5 transition" :class="collapsed && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                </svg>
            </button>
        </div>

        <div
            x-show="!collapsed"
            class="max-h-[min(70vh,36rem)] overflow-y-auto px-2.5 py-2"
        >
            <p
                class="whitespace-pre-wrap break-words text-xs leading-relaxed text-gray-800 dark:text-gray-200"
                x-text="notes || 'Tidak ada notes before.'"
                :class="!notes && 'italic text-gray-500 dark:text-gray-400'"
            ></p>
        </div>
    </div>
</aside>
