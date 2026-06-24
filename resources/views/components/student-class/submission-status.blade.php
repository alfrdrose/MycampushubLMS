@props(['submission', 'activity'])

<div class="mt-4">
    <div class="border items-center border-l-8 border-l-[#531B8A] dark:border-l-[#A88BFF] flex flex-col sm:flex-row">

        {{-- Score --}}
        <div class="w-full sm:w-1/2 border-b sm:border-b-0 sm:border-r p-6">
            @php
                $score = $submission?->score ?? 0;
                $total = $activity->total_score ?? 0;
            @endphp

            <div class="flex flex-col sm:flex-row gap-2 items-center sm:items-start">
                <div class="flex-col p-4 w-full sm:w-[90%]">
                    <p class="font-bold text-4xl text-[#373737] dark:text-white">
                        {{ $score }} <span class="{{ $submission?->score ? 'text-[#F2277E]' : 'text-gray-400' }}">/ {{ $total }}</span>
                    </p>
                    <p class="text-xs text-[#757575] dark:text-gray-300">
                        {{ $submission?->score ? 'Your Total Score' : 'Submitted for grading' }}
                    </p>
                </div>

                <div class="hidden sm:flex w-full sm:w-10% justify-center sm:justify-start mt-2">
    <div class="rounded-full w-12 h-12 bg-[#E1D6FF] flex justify-center items-center">
        <i class="icofont-ui-pointer text-[#5F2DED] text-2xl"></i>
    </div>
</div>

            </div>
        </div>

        {{-- Submission timing --}}
        <div class="w-full sm:w-1/2 p-6">
            @if($submission?->timing_status === 'on_time')
                <div class="flex justify-center sm:justify-start items-center gap-2 p-2 rounded-xl bg-green-300 text-green-800 dark:bg-green-900 dark:text-white text-sm">
                    <i class="icofont-warning-alt"></i>
                    <p>You submitted on time!</p>
                </div>
            @elseif($submission?->timing_status)
                <div class="flex justify-center sm:justify-start items-center gap-2 p-2 rounded-xl bg-red-300 text-red-800 dark:bg-red-900 dark:text-white text-sm">
                    <i class="icofont-warning-alt"></i>
                    <p>You missed the deadline.</p>
                </div>
            @else
                <div class="flex justify-center sm:justify-start items-center gap-2 p-2 rounded-xl bg-gray-200 dark:bg-gray-700 dark:text-white text-sm">
                    <i class="icofont-warning-alt"></i>
                    <p>No submission yet</p>
                </div>
            @endif

            <div class="flex flex-col mt-2 text-sm font-semibold dark:text-white text-[#373737] gap-2">
                <p>Date submitted: 
                    <span class="text-[#373737] dark:text-white font-normal">
                        {{ $submission?->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('F d, Y') : 'N/A' }}
                    </span>
                </p>
                <p>Time submitted: 
                    <span class="text-[#373737] dark:text-white font-normal">
                        {{ $submission?->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('h:i A') : 'N/A' }}
                    </span>
                </p>
                <p>Updated at: 
                    <span class="text-[#373737] dark:text-white font-normal">
                        {{ $submission?->updated_at ? \Carbon\Carbon::parse($submission->updated_at)->format('F d, Y h:i A') : 'N/A' }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>
