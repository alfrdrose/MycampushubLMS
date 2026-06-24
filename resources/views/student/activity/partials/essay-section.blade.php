{{-- ================= ESSAY SECTION ================= --}}
<div class="mt-6 border-t border-gray-300 dark:border-[#5F2DED] pt-4">

    <h2 class="text-xl font-semibold text-[#5E5F60] dark:text-white mb-2">
        Essay Submission
    </h2>

    @php
        $min = $activity->min_words;
        $max = $activity->max_words;
        $hasLimit = $min || $max;
    @endphp


@if($submission)

<div >

    {{-- VIEW --}}
    <div x-show="!editing"
         class="bg-gray-100 dark:text-white dark:bg-[#2A2A3C] p-4 rounded">
        {!! $submission->essay->essay_text !!}

    </div>

    {{-- EDIT --}}
    <div x-show="editing" class="mt-2">

        <input type="hidden"
               id="essay_text_input"
               name="essay_text"
               value="{{ $submission->essay->essay_text }}">

        <trix-editor
            input="essay_text_input"
            class="w-full bg-white dark:bg-[#1E1E2D] border rounded text-[#373737] dark:text-white">
        </trix-editor>

        {{-- WORD COUNTER --}}
        @if($hasLimit)
        <div class="mt-2 text-sm font-semibold">

            {{-- HEADER --}}
            <p class="text-[#373737] dark:text-white">
                Word Counter:
                <span id="essayWordCurrent" class="font-bold text-primaryColor">0</span>
                /
                <span id="essayWordLimit" class="font-bold text-[#373737] dark:text-white">
                    {{ $max ?? $min }}
                </span>
            </p>

            {{-- MIN --}}
            @if($min)
            <p class="text-[#373737] dark:text-white">
                Minimum Words:
                <span id="essayMinDisplay" class="font-bold">{{ $min }}</span> words
            </p>
            @endif

            {{-- MAX --}}
            @if($max)
            <p class="text-[#373737] dark:text-white">
                Maximum Words:
                <span id="essayMaxDisplay" class="font-bold">{{ $max }}</span> words
            </p>
            @endif

        </div>
        @endif

    </div>

</div>

@else

{{-- ================= FIRST SUBMISSION ================= --}}
<input type="hidden" id="essay_text_input" name="essay_text">

<trix-editor
    input="essay_text_input"
    class="w-full bg-white dark:bg-[#1E1E2D] border rounded text-[#373737] dark:text-white">
</trix-editor>

@if($hasLimit)
<div class="mt-2 text-sm font-semibold">

    {{-- HEADER --}}
    <p class="text-[#373737] dark:text-white font-normal">
        Word Counter:
        <span id="essayWordCurrent" class="font-bold text-primaryColor">0</span>
        /
        <span id="essayWordLimit" class="font-bold text-[#373737] dark:text-white">
            {{ $max ?? $min }}
        </span>
    </p>

    {{-- MIN --}}
    @if($min)
    <p class="text-[#373737] dark:text-white font-normal">
        Minimum Words:
        <span id="essayMinDisplay" class="font-bold">{{ $min }}</span> words
    </p>
    @endif

    {{-- MAX --}}
    @if($max)
    <p class="text-[#373737] dark:text-white font-normal">
        Maximum Words:
        <span id="essayMaxDisplay" class="font-bold">{{ $max }}</span> words
    </p>
    @endif

</div>
@endif

@endif
</div>


<script>
document.addEventListener("DOMContentLoaded", () => {

    const input = document.getElementById("essay_text_input");
    const current = document.getElementById("essayWordCurrent");
    const limitDisplay = document.getElementById("essayWordLimit");

    const minDisplay = document.getElementById("essayMinDisplay");
    const maxDisplay = document.getElementById("essayMaxDisplay");

    if (!input || !current) return;

    const min = parseInt(minDisplay?.textContent || 0);
    const max = parseInt(maxDisplay?.textContent || 0);
    const limit = max || min || 0;

    function updateCount() {

        const text = input.value.replace(/<[^>]*>?/gm, '').trim();
        const words = text.length ? text.split(/\s+/).length : 0;

        current.textContent = words;
        if (limitDisplay) limitDisplay.textContent = limit;

        let ok = true;

        // MIN
        if (min) {
            if (words < min) {
                ok = false;
                minDisplay?.classList.add("text-red-500");
                minDisplay?.classList.remove("text-green-500");
            } else {
                minDisplay?.classList.add("text-green-500");
                minDisplay?.classList.remove("text-red-500");
            }
        }

        // MAX
        if (max) {
            if (words > max) {
                ok = false;
                maxDisplay?.classList.add("text-red-500");
                maxDisplay?.classList.remove("text-green-500");
            } else {
                maxDisplay?.classList.add("text-green-500");
                maxDisplay?.classList.remove("text-red-500");
            }
        }

        // NO LIMITS -> cannot be empty
        if (!min && !max) {
            ok = words > 0;
        }

        // 🔥 VERY IMPORTANT
        window.dispatchEvent(new CustomEvent("essay-valid", {
            detail: ok
        }));
    }

    // Run when loaded
    updateCount();

    // Listen to EVERY trix editor (fixes your issue)
    document.querySelectorAll("trix-editor").forEach(editor => {
        editor.addEventListener("trix-change", updateCount);
    });
});
</script>

