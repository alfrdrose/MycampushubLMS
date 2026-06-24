@extends('pages.student.layout')
@section('content')

<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">

    <!-- Header -->
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <div class="flex items-center gap-3">

            <a href="{{ route('student.reviewer.recommendation') }}"
               class="flex items-center justify-center w-10 h-10 rounded-lg
                      bg-[#F1F5F9] dark:bg-[#2A2A3B]
                      hover:bg-[#E2E8F0] dark:hover:bg-[#3A3A4D] transition">
                <i class="icofont-arrow-left text-lg text-[#5F2DED]"></i>
            </a>

            <div>
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    Generated Reviewers
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    List of your generated reviewers.
                </p>
            </div>

        </div>
    </div>

    <!-- Card Container -->
    <div class="mt-6 border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-6 bg-[#F8FAFC] dark:bg-[#0B1220]">

        @if($reviewers->isEmpty())
            <p class="text-[#5E5F60] dark:text-gray-300">
                No reviewers available yet.
            </p>
        @else

            <div class="grid grid-cols-1 gap-4">

                @foreach($reviewers as $reviewer)

                    @php
                        $tags = [];
                        if(is_array($reviewer->data)){
                            foreach($reviewer->data as $subject => $subjectTags){
                                foreach($subjectTags as $tagName => $questions){
                                    $tags[] = $tagName;
                                }
                            }
                        }
                        $tags = array_unique($tags);
                    @endphp

                    <div class="border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-4 hover:scale-105
                                cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2A2A3C] transition relative"
                         onclick="window.location='{{ route('student.reviewer.generated.view', $reviewer->id) }}'">

                        <div class="border-l-4 border-[#5F2DED] dark:border-[#A88BFF] p-4">

                            <div class="flex justify-between items-start">

                                <!-- Left -->
                                <div>
                                    <p class="text-lg font-semibold text-[#373737] dark:text-white">
                                        Reviewer #{{ $reviewer->reviewer_number }}
                                    </p>

                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $reviewer->created_at->format('M d, Y h:i A') }}
                                    </p>

                                    <!-- Tags -->
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @forelse($tags as $tag)
                                            <span class="text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                {{ $tag }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-gray-400">
                                                No tags
                                            </span>
                                        @endforelse
                                    </div>

                                    <!-- Completed Badge -->
                                    @if($reviewer->completed_at)
                                        <div class="mt-2">
                                            <span class="text-xs font-semibold text-green-600">
                                                ✔ Completed
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Delete Button -->
                                <button type="button"
                                        onclick="event.stopPropagation(); openDeleteModal({{ $reviewer->id }})"
                                        class="text-red-500 hover:text-red-700 text-lg">
                                    <i class="icofont-trash"></i>
                                </button>

                            </div>
                        </div>
                    </div>

                @endforeach

            </div>
            <div class="mt-6">
                {{ $reviewers->links() }}
            </div>
        @endif

    </div>

</div>

@endsection
<!-- Delete Modal -->
<div id="deleteModal"
     class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">

    <div class="bg-white dark:bg-[#1E1E2D] rounded-lg shadow-lg w-full max-w-md p-6">

        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
            Delete Reviewer
        </h3>

        <p class="text-sm text-gray-500 dark:text-gray-300 mt-2">
            Are you sure you want to delete this reviewer? This action cannot be undone.
        </p>

        <form id="deleteForm" method="POST" class="mt-6 flex justify-end gap-3">
            @csrf
            @method('DELETE')

            <button type="button"
                    onclick="closeDeleteModal()"
                    class="px-4 py-2 rounded-lg border text-gray-600 dark:text-gray-300">
                Cancel
            </button>

            <button type="submit"
                    id="deleteConfirmBtn"
                    class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">

                <svg id="deleteSpinner"
                     class="hidden w-4 h-4 animate-spin"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>

                <span id="deleteBtnText">Delete</span>
            </button>
        </form>

    </div>
</div>

<script>
    function openDeleteModal(id) {
        const modal = document.getElementById('deleteModal');
        const form = document.getElementById('deleteForm');
        form.action = `/student/reviewer/generated/${id}`;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('deleteForm').addEventListener('submit', function () {
        const btn = document.getElementById('deleteConfirmBtn');
        const spinner = document.getElementById('deleteSpinner');
        const text = document.getElementById('deleteBtnText');

        btn.disabled = true;
        spinner.classList.remove('hidden');
        text.textContent = 'Deleting...';
    });
</script>