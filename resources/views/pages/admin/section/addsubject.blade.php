@extends('layouts.updatesection-layout')

@section('content')
<script src="//unpkg.com/alpinejs" defer></script>

<div
    x-data="{ selected: [], search: '' }"
    class="flex flex-col w-full"
>
    <!-- Header + Search -->
    <div class="flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between mb-6">
        <div class="flex-1">
            <p class="font-medium text-xl sm:text-2xl dark:text-white">
                Add Subjects
            </p>
            <p class="text-[#ABB7C2] dark:text-white text-sm sm:text-base">
                Choose subjects to add for section:
                <span class="font-semibold">{{ $section->section_name }}</span>
            </p>
        </div>

        <!-- Search -->
        <div class="w-full sm:w-auto">
            <input
                type="text"
                placeholder="Search subject..."
                x-model="search"
                class="w-full sm:w-72 border border-gray-300 dark:border-gray-600
                       rounded-lg px-4 py-2
                       focus:ring-2 focus:ring-[#5F2DED]
                       dark:bg-darkblack dark:text-white"
            >
        </div>
    </div>

    <form
        id="addSubjectsForm"
        method="POST"
        action="{{ route('admin.section.addsubjects', $section->id) }}"
        class="flex flex-col w-full"
    >
        @csrf

        <input type="hidden" name="subject_ids" :value="JSON.stringify(selected)">

        <!-- Desktop Table -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-300 dark:border-gray-700">
                        <th class="py-3 px-3 w-12 text-center">
                            <input
                                type="checkbox"
                                @click="
                                    selected.length === {{ count($subjects) }}
                                        ? selected = []
                                        : selected = {{ json_encode($subjects->pluck('id')) }}
                                "
                                class="w-4 h-4 text-[#5F2DED] rounded focus:ring-[#5F2DED]"
                            >
                        </th>
                        <th class="py-3 px-3 dark:text-white">Subject Name</th>
                        <th class="py-3 px-3 dark:text-white">Grade Level</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subjects as $subject)
                        <tr
                            x-show="
                                ('{{ strtolower($subject->subject_code . ' ' . $subject->subject_name) }}'.includes(search.toLowerCase())) ||
                                ('{{ strtolower($subject->grade_level) }}'.includes(search.toLowerCase()))
                            "
                            @click="
                                selected.includes({{ $subject->id }})
                                    ? selected = selected.filter(id => id !== {{ $subject->id }})
                                    : selected.push({{ $subject->id }})
                            "
                            :class="
                                selected.includes({{ $subject->id }})
                                    ? 'bg-[#5F2DED]/20 border-l-4 border-[#5F2DED]'
                                    : 'hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer'
                            "
                            class="transition"
                        >
                            <td class="py-3 px-3 text-center">
                                <input
                                    type="checkbox"
                                    :checked="selected.includes({{ $subject->id }})"
                                    class="w-4 h-4 text-[#5F2DED] rounded focus:ring-[#5F2DED]"
                                >
                            </td>
                            <td class="py-3 px-3 dark:text-white">
                                {{ $subject->subject_name }}
                            </td>
                            <td class="py-3 px-3 dark:text-white">
                                {{ $subject->grade_level }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="sm:hidden flex flex-col gap-3">
            @foreach ($subjects as $subject)
                <div
                    x-show="
                        ('{{ strtolower($subject->subject_code . ' ' . $subject->subject_name) }}'.includes(search.toLowerCase())) ||
                        ('{{ strtolower($subject->grade_level) }}'.includes(search.toLowerCase()))
                    "
                    @click="
                        selected.includes({{ $subject->id }})
                            ? selected = selected.filter(id => id !== {{ $subject->id }})
                            : selected.push({{ $subject->id }})
                    "
                    :class="
                        selected.includes({{ $subject->id }})
                            ? 'bg-[#5F2DED]/20 border-l-4 border-[#5F2DED]'
                            : 'hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer'
                    "
                    class="p-4 border border-gray-300 dark:border-gray-600
                           rounded-lg transition flex flex-col gap-2"
                >
                    <div class="flex items-center justify-between">
                        <p class="font-semibold dark:text-white">
                            {{ $subject->subject_name }}
                        </p>
                        <input
                            type="checkbox"
                            :checked="selected.includes({{ $subject->id }})"
                            class="w-4 h-4 text-[#5F2DED] rounded"
                            readonly
                        >
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Grade: {{ $subject->grade_level }}
                    </p>
                </div>
            @endforeach
        </div>

        <!-- Empty State -->
        @if ($subjects->isEmpty())
            <p class="text-center py-6 text-gray-500 dark:text-gray-400">
                No subjects found for this grade level.
            </p>
        @endif

        <!-- Action Button -->
        <div class="flex flex-col sm:flex-row justify-end mt-6 gap-3">
            <button
                type="button"
                onclick="openConfirmAddSubjectsModal()"
                :disabled="selected.length === 0"
                class="w-full sm:w-auto bg-[#5F2DED] text-white
                       px-6 py-3 rounded
                       disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Add Selected Subjects
            </button>
        </div>
    </form>
</div>

<!-- Global modal -->
<x-lmsmodal />
@endsection

@section('scripts')
<script>
function openConfirmAddSubjectsModal() {
    const selectedSubjects = document.querySelector('input[name="subject_ids"]').value;
    const parsed = JSON.parse(selectedSubjects);

    if (parsed.length === 0) return;

    const modal = document.getElementById("lmsmodal");
    const title = document.getElementById("lmsmodaltitle");
    const content = document.getElementById("lmsmodalcontent");

    title.textContent = "Confirm Subject Addition";

    content.innerHTML = `
        <p class="text-center dark:text-white mb-4">
            Are you sure you want to add <b>${parsed.length}</b> subject(s) to this section?
        </p>
        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <button
                data-close="lmsmodal"
                class="px-4 py-2 flex-1 border
                       dark:text-white dark:hover:text-black
                       border-[#ABB7C2] hover:bg-gray-100 rounded">
                Cancel
            </button>
            <button
                id="confirmAddSubjectsBtn"
                class="px-4 py-2 flex-1 bg-[#5F2DED]
                       hover:bg-[#4C23C7] text-white rounded">
                Confirm
            </button>
        </div>
    `;

    modal.classList.remove("hidden");

    setTimeout(() => {
        const confirmBtn = document.getElementById("confirmAddSubjectsBtn");
        if (confirmBtn) {
            confirmBtn.addEventListener("click", () => {
                window.AdminModalLoading?.setLoading(confirmBtn, { text: "Adding..." });
                document.getElementById("addSubjectsForm").submit();
            });
        }
    }, 50);
}
</script>
@endsection
