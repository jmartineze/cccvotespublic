@php
    $lockedCriteria = $contest?->hasVotes() ?? false;
    $initialCriteria = ($contest?->criteria ?? collect())->map(fn ($c) => [
        'name' => $c->name, 'description' => $c->description, 'max_score' => $c->max_score, 'tiebreak_order' => $c->tiebreak_order,
    ])->values();
    $initialPrizes = ($contest?->specialPrizes ?? collect())->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->name, 'description' => $p->description,
    ])->values();
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5"
      x-data="contestForm({{ $initialCriteria->toJson() }}, {{ $initialPrizes->toJson() }})">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div>
        <label class="section-label block mb-1.5">Contest Name <span style="color: #ff2d78;">*</span></label>
        <input type="text" name="name" class="input {{ $errors->has('name') ? 'input-error' : '' }}"
            value="{{ old('name', $contest?->name) }}" placeholder="Culture Cuties Contest S2" required>
        @error('name') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="section-label block mb-1.5">Contest Type <span style="color: #ff2d78;">*</span></label>
        <select name="contest_type" class="input {{ $errors->has('contest_type') ? 'input-error' : '' }}" {{ $lockedCriteria ? 'disabled' : '' }}>
            <option value="image" {{ old('contest_type', $contest?->contest_type ?? 'image') === 'image' ? 'selected' : '' }}>Image — judged purely on visuals</option>
            <option value="character_scenario" {{ old('contest_type', $contest?->contest_type) === 'character_scenario' ? 'selected' : '' }}>Character/Scenario — judges add a required comment; submissions include a scenario field</option>
        </select>
        @if($lockedCriteria)
            <input type="hidden" name="contest_type" value="{{ $contest->contest_type }}">
            <p class="text-xs mt-1" style="color: var(--color-muted);">Locked — voting has already started.</p>
        @endif
        @error('contest_type') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="section-label block mb-1.5">Description</label>
        <textarea name="description" class="input {{ $errors->has('description') ? 'input-error' : '' }}"
            placeholder="A brief description of this contest..." rows="3">{{ old('description', $contest?->description) }}</textarea>
        @error('description') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="section-label block mb-1.5">Status <span style="color: #ff2d78;">*</span></label>
        <select name="status" class="input {{ $errors->has('status') ? 'input-error' : '' }}">
            <option value="draft"  {{ old('status', $contest?->status) === 'draft'  ? 'selected' : '' }}>Draft — not visible to judges</option>
            <option value="active" {{ old('status', $contest?->status) === 'active' ? 'selected' : '' }}>Active — open for voting</option>
            <option value="closed" {{ old('status', $contest?->status) === 'closed' ? 'selected' : '' }}>Closed — voting ended</option>
        </select>
        @error('status') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="section-label block mb-1.5">Cover Image</label>
        @if($contest?->cover_image_url)
            <img src="{{ $contest->cover_image_url }}" alt="Current cover" class="w-full rounded-xl mb-2 object-cover" style="max-height: 140px;">
            <p class="text-xs mb-2" style="color: var(--color-muted);">Upload a new image to replace the current one.</p>
        @endif
        <input type="file" name="cover_image" accept="image/*" class="input py-2 text-sm">
        @error('cover_image') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="section-label">Scoring Criteria <span style="color: #ff2d78;">*</span></label>
            @unless($lockedCriteria)
                <button type="button" @click="addCriterion()" class="btn btn-secondary btn-sm">+ Add criterion</button>
            @endunless
        </div>

        @if($lockedCriteria)
            <p class="text-xs mb-3" style="color: var(--color-muted);">Locked — voting has already started, criteria can no longer be edited.</p>
            <div class="space-y-2">
                @foreach($contest->criteria as $criterion)
                    <div class="card p-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="font-display font-700 text-sm" style="color: var(--color-text);">{{ $criterion->name }}</p>
                            @if($criterion->description)
                                <p class="text-xs" style="color: var(--color-muted);">{{ $criterion->description }}</p>
                            @endif
                        </div>
                        <div class="text-xs font-mono flex-shrink-0" style="color: var(--color-muted);">
                            max {{ $criterion->max_score }}
                            @if($criterion->tiebreak_order) · tiebreak #{{ $criterion->tiebreak_order }} @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs mb-3" style="color: var(--color-muted);">Define your own criteria and, optionally, a tiebreak order (1 = first tiebreak, 2 = second, etc).</p>
            <div class="space-y-3">
                <template x-for="(criterion, index) in criteria" :key="index">
                    <div class="card p-3 space-y-2">
                        <div class="flex gap-2">
                            <input type="text" :name="'criteria[' + index + '][name]'" x-model="criterion.name" class="input flex-1" placeholder="Criterion name" required>
                            <button type="button" @click="removeCriterion(index)" class="btn btn-danger btn-sm" x-show="criteria.length > 1">✕</button>
                        </div>
                        <input type="text" :name="'criteria[' + index + '][description]'" x-model="criterion.description" class="input" placeholder="Description (optional)">
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="text-xs" style="color: var(--color-muted);">Max score</label>
                                <input type="number" :name="'criteria[' + index + '][max_score]'" x-model.number="criterion.max_score" class="input" min="1" max="100" required>
                            </div>
                            <div class="flex-1">
                                <label class="text-xs" style="color: var(--color-muted);">Tiebreak order</label>
                                <input type="number" :name="'criteria[' + index + '][tiebreak_order]'" x-model.number="criterion.tiebreak_order" class="input" min="1" placeholder="None">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        @endif
        @error('criteria') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    {{-- Special prizes (non-scoring toggles) --}}
    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="section-label">Special Prizes <span style="color: var(--color-muted); text-transform: none; font-weight: 400;">(optional)</span></label>
            <button type="button" @click="addPrize()" class="btn btn-secondary btn-sm">+ Add prize</button>
        </div>
        <p class="text-xs mb-3" style="color: var(--color-muted);">
            Side awards judges toggle per submission (e.g. “Best image”, “Made me laugh”). They don't affect scores and stay editable after voting starts.
        </p>
        <div class="space-y-3">
            <template x-for="(prize, index) in prizes" :key="index">
                <div class="card p-3 space-y-2">
                    <input type="hidden" :name="'special_prizes[' + index + '][id]'" :value="prize.id || ''">
                    <div class="flex gap-2">
                        <input type="text" :name="'special_prizes[' + index + '][name]'" x-model="prize.name" class="input flex-1" placeholder="Prize name" required>
                        <button type="button" @click="removePrize(index)" class="btn btn-danger btn-sm">✕</button>
                    </div>
                    <input type="text" :name="'special_prizes[' + index + '][description]'" x-model="prize.description" class="input" placeholder="Description (optional)">
                </div>
            </template>
            <p x-show="prizes.length === 0" class="text-xs" style="color: var(--color-faint, var(--color-muted));">No special prizes.</p>
        </div>
        @error('special_prizes') <p class="error-msg">{{ $message }}</p> @enderror
        @error('special_prizes.*.name') <p class="error-msg">Every special prize needs a name.</p> @enderror
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="btn btn-primary flex-1">
            {{ $contest ? 'Save Changes' : 'Create Contest' }}
        </button>
        <a href="{{ route('admin.contests.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
function contestForm(initialCriteria, initialPrizes) {
    return {
        criteria: initialCriteria && initialCriteria.length ? initialCriteria : [
            { name: '', description: '', max_score: 10, tiebreak_order: null },
        ],
        prizes: initialPrizes || [],
        addCriterion() {
            this.criteria.push({ name: '', description: '', max_score: 10, tiebreak_order: null });
        },
        removeCriterion(index) {
            this.criteria.splice(index, 1);
        },
        addPrize() {
            this.prizes.push({ id: null, name: '', description: '' });
        },
        removePrize(index) {
            this.prizes.splice(index, 1);
        },
    };
}
</script>
