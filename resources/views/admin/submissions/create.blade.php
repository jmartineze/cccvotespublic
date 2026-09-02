@extends('layouts.app')

@section('title', 'Upload Submission')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('admin.submissions.index') }}" class="inline-flex items-center gap-1.5 text-sm mb-3" style="color: var(--color-muted);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Submissions
        </a>
        <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Upload Submission</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-error mb-4">
            <div>
                <p class="font-600">Please fix the following:</p>
                <ul class="mt-1 text-xs space-y-0.5 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.submissions.store') }}" enctype="multipart/form-data"
          class="space-y-5" x-data="submissionForm()">
        @csrf

        {{-- Contest --}}
        <div>
            <label class="section-label block mb-1.5">Contest <span style="color: #ff2d78;">*</span></label>
            <select name="contest_id" class="input {{ $errors->has('contest_id') ? 'input-error' : '' }}"
                    x-ref="contestSelect"
                    x-on:change="saveContest($event.target.value); updateScenario($event.target)"
                    required>
                <option value="">Select a contest…</option>
                @foreach($contests as $contest)
                    <option value="{{ $contest->id }}" data-type="{{ $contest->contest_type }}" {{ old('contest_id') == $contest->id ? 'selected' : '' }}>
                        {{ $contest->name }} ({{ ucfirst($contest->status) }})
                    </option>
                @endforeach
            </select>
            @error('contest_id') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        {{-- Discord user --}}
        <div>
            <label class="section-label block mb-1.5">Discord Username <span style="color: #ff2d78;">*</span></label>
            <input type="text" name="discord_user" class="input {{ $errors->has('discord_user') ? 'input-error' : '' }}"
                value="{{ old('discord_user') }}" placeholder="username#0000" required>
            @error('discord_user') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        {{-- Character name --}}
        <div>
            <label class="section-label block mb-1.5">Character Name <span style="color: #ff2d78;">*</span></label>
            <input type="text" name="character_name" class="input {{ $errors->has('character_name') ? 'input-error' : '' }}"
                value="{{ old('character_name') }}" placeholder="Sakura Hana" required>
            @error('character_name') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        {{-- Country --}}
        <div>
            <label class="section-label block mb-1.5">Country / Culture <span style="color: #ff2d78;">*</span></label>
            <input type="text" name="country" class="input {{ $errors->has('country') ? 'input-error' : '' }}"
                value="{{ old('country') }}" placeholder="Japan" required>
            @error('country') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        {{-- Gender + Style --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="section-label block mb-1.5">Gender <span style="color: #ff2d78;">*</span></label>
                <select name="gender" class="input {{ $errors->has('gender') ? 'input-error' : '' }}" required>
                    <option value="">Select…</option>
                    @foreach(['Male', 'Female', 'Trans'] as $g)
                        <option value="{{ $g }}" {{ old('gender') === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
                @error('gender') <p class="error-msg">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="section-label block mb-1.5">Style <span style="color: #ff2d78;">*</span></label>
                <select name="style" class="input {{ $errors->has('style') ? 'input-error' : '' }}" required>
                    <option value="">Select…</option>
                    @foreach(['Anime', 'Realistic'] as $s)
                        <option value="{{ $s }}" {{ old('style') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
                @error('style') <p class="error-msg">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Backstory --}}
        <div>
            <label class="section-label block mb-1.5">
                Backstory <span style="color: #ff2d78;">*</span>
                <span class="ml-2 font-mono text-xs" style="color: var(--color-muted);" x-text="`${backstoryLen}/1000`"></span>
            </label>
            <textarea name="backstory" class="input {{ $errors->has('backstory') ? 'input-error' : '' }}"
                rows="5" maxlength="1000" required
                x-on:input="backstoryLen = $event.target.value.length"
                placeholder="The story behind this character...">{{ old('backstory') }}</textarea>
            @error('backstory') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        {{-- Scenario description (character/scenario contests only) --}}
        <div x-show="showScenario" x-cloak>
            <label class="section-label block mb-1.5">Scenario Description <span style="color: #ff2d78;">*</span></label>
            <textarea name="scenario_description" class="input {{ $errors->has('scenario_description') ? 'input-error' : '' }}"
                rows="4" placeholder="Describe the scenario this character is placed in..."
                :required="showScenario">{{ old('scenario_description') }}</textarea>
            @error('scenario_description') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        {{-- Images / Videos --}}
        <div>
            <label class="section-label block mb-1.5">
                Images / Videos <span style="color: #ff2d78;">*</span>
                <span class="ml-2 font-mono text-xs" style="color: var(--color-muted);">1–12 files, max 10MB c/u</span>
            </label>

            {{-- Drop zone --}}
            <div class="rounded-xl border-2 border-dashed p-6 text-center cursor-pointer transition-colors"
                 style="border-color: var(--color-border);"
                 x-on:dragover.prevent="$el.style.borderColor='#ff2d78'"
                 x-on:dragleave="$el.style.borderColor='var(--color-border)'"
                 x-on:drop.prevent="handleDrop($event)"
                 x-on:click="$refs.fileInput.click()">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-sm" style="color: var(--color-muted);">Tap to select or drag & drop images/videos</p>
                <p class="text-xs mt-1" style="color: var(--color-faint);">First file will be the thumbnail</p>
                <input type="file" name="images[]" accept="image/*,video/mp4,video/webm,video/quicktime" multiple x-ref="fileInput"
                    class="hidden" x-on:change="handleFiles($event)" required>
            </div>
            @error('images') <p class="error-msg">{{ $message }}</p> @enderror
            @error('images.*') <p class="error-msg">{{ $message }}</p> @enderror

            {{-- Previews --}}
            <div class="grid grid-cols-3 gap-2 mt-3" x-show="previews.length > 0">
                <template x-for="(item, i) in previews" :key="i">
                    <div class="relative aspect-square rounded-lg overflow-hidden" style="background: var(--color-faint);">
                        <template x-if="item.isVideo">
                            <video :src="item.src" class="w-full h-full object-cover" muted playsinline preload="metadata"></video>
                        </template>
                        <template x-if="!item.isVideo">
                            <img :src="item.src" class="w-full h-full object-cover">
                        </template>
                        <div class="absolute top-1 left-1 w-5 h-5 rounded-full flex items-center justify-center text-xs font-mono font-700"
                             style="background: rgba(0,0,0,0.7); color: white;" x-text="i + 1"></div>
                        <template x-if="item.isVideo">
                            <div class="absolute bottom-1 left-1 px-1 rounded text-xs font-mono" style="background: rgba(0,0,0,0.7); color: #00d4ff;">▶</div>
                        </template>
                        <button type="button" x-on:click="removeImage(i)"
                            class="absolute top-1 right-1 w-5 h-5 rounded-full flex items-center justify-center"
                            style="background: rgba(239,68,68,0.8); color: white; font-size: 0.7rem;">✕</button>
                    </div>
                </template>
            </div>
            <p class="text-xs mt-2" style="color: var(--color-muted);" x-show="previews.length > 0">
                <span x-text="previews.length"></span> archivo(s) seleccionado(s)
                <template x-if="previews.length >= 12">
                    <span style="color: #ffd700;"> — máximo alcanzado</span>
                </template>
            </p>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Create Submission
        </button>
    </form>
</div>

<script>
function submissionForm() {
    return {
        previews: [],
        files: [],
        backstoryLen: 0,
        showScenario: false,

        init() {
            // Pre-select last used contest (only if no server-side old() value)
            const saved = localStorage.getItem('ccc_last_contest_id');
            if (saved && !this.$refs.contestSelect.value) {
                this.$refs.contestSelect.value = saved;
            }
            this.updateScenario(this.$refs.contestSelect);
        },

        saveContest(val) {
            if (val) localStorage.setItem('ccc_last_contest_id', val);
        },

        updateScenario(select) {
            const option = select.options[select.selectedIndex];
            this.showScenario = !!option && option.dataset.type === 'character_scenario';
        },

        handleFiles(e) {
            this.addFiles(Array.from(e.target.files));
        },

        handleDrop(e) {
            this.$el.style.borderColor = 'var(--color-border)';
            this.addFiles(Array.from(e.dataTransfer.files).filter(f =>
                f.type.startsWith('image/') || f.type.startsWith('video/')
            ));
        },

        addFiles(newFiles) {
            const remaining = 12 - this.files.length;
            const toAdd = newFiles.slice(0, remaining);
            toAdd.forEach(file => {
                this.files.push(file);
                const isVideo = file.type.startsWith('video/');
                const src = URL.createObjectURL(file);
                this.previews.push({ src, isVideo });
            });
            this.syncInput();
        },

        removeImage(index) {
            URL.revokeObjectURL(this.previews[index].src);
            this.files.splice(index, 1);
            this.previews.splice(index, 1);
            this.syncInput();
        },

        syncInput() {
            const dt = new DataTransfer();
            this.files.forEach(f => dt.items.add(f));
            this.$refs.fileInput.files = dt.files;
        },
    };
}
</script>
@endsection
