@extends('pages.student.layout')

@section('content')

<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-4 sm:p-6 lg:p-8 rounded-lg"> 
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
        <x-student-nav :active="'attendance'" :sectionId="$section->id" :subjectId="$subject->id" />
    </div>

    <div class="mt-6">
        <h2 class="text-xl sm:text-2xl font-semibold mb-4 text-[#5E5F60] dark:text-white">
            Attendance Records
        </h2>

        @php
            $quarters = [
                '1st' => '1st Quarter',
                '2nd' => '2nd Quarter',
                '3rd' => '3rd Quarter',
                '4th' => '4th Quarter',
            ];
        @endphp

        <div x-data="{ selectedQuarter: '1st' }" class="w-full">

            {{-- DROPDOWN --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
                <label class="text-[#373737] dark:text-white font-semibold">
                    Grading Period:
                </label>

                <select x-model="selectedQuarter"
                        class="w-full sm:w-auto px-8 py-2 rounded-lg border border-gray-300 dark:border-[#5F2DED]
                               bg-white dark:bg-[#2A2A3C] text-[#373737] dark:text-white">
                    @foreach($quarters as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- PERIOD CONTENT --}}
            @foreach($quarters as $quarterKey => $quarterLabel)

                <div x-show="selectedQuarter === '{{ $quarterKey }}'" x-cloak>

                    <h3 class="text-lg sm:text-xl font-semibold mt-6 mb-2 text-[#5E5F60] dark:text-white">
                        {{ $quarterLabel }}
                    </h3>

                    @php
                        $records = $attendanceRecords->where('grading_period', $quarterKey);
                        $totalDays = $records->count();

                        $earnedScore = $records->sum(function ($record) {
                            return match ($record->status) {
                                'present' => 1,
                                'late' => 0.75,
                                'excused' => 0.5,
                                'absent' => 0,
                                default => 0,
                            };
                        });

                        $earnedScoreFormatted = rtrim(rtrim(number_format($earnedScore, 2), '0'), '.');
                    @endphp

                    @if($records->isEmpty())
                        <p class="text-gray-500 dark:text-gray-300">No records yet.</p>
                    @else

                        {{-- DESKTOP TABLE --}}
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full border border-[#E1E1E1] dark:border-gray-600 rounded-xl">
                                <thead class="bg-[#47D47B]">
                                    <tr>
                                        <th class="px-4 py-4 text-center">Day</th>
                                        <th class="px-4 py-4 text-center">Date</th>
                                        <th class="px-4 py-4 text-center">Grading Period</th>
                                        <th class="px-4 py-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $record)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 text-[#373737] dark:text-white">
                                            <td class="px-4 py-4 text-center bg-[#EDFDF3] font-semibold dark:text-[#373737]">
                                                {{ $record->day }}
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                {{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                {{ $record->grading_period }}
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    @if($record->status === 'present')
                                                        <div class="w-2 h-2 rounded-full bg-[#4ed43c]"></div>
                                                        <span class="text-[#4ed43c] font-semibold">Present</span>
                                                    @elseif($record->status === 'absent')
                                                        <div class="w-2 h-2 rounded-full bg-[#b83330]"></div>
                                                        <span class="text-[#b83330] font-semibold">Absent</span>
                                                    @elseif($record->status === 'excused')
                                                        <div class="w-2 h-2 rounded-full bg-[#b298f6]"></div>
                                                        <span class="text-[#b298f6] font-semibold">Excused</span>
                                                    @elseif($record->status === 'late')
                                                        <div class="w-2 h-2 rounded-full bg-[#fca127]"></div>
                                                        <span class="text-[#fca127] font-semibold">Late</span>
                                                    @else
                                                        <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                                                        <span class="text-gray-500 font-semibold">
                                                            {{ ucfirst($record->status) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- MOBILE CARDS --}}
                        <div class="md:hidden space-y-4">
                            @foreach($records as $record)
                                <div class="border rounded-lg p-4 bg-white dark:bg-[#2A2A3C] shadow text-[#373737] dark:text-white">
                                    <div class="flex justify-between mb-1">
                                        <span class="font-semibold">Day</span>
                                        <span>{{ $record->day }}</span>
                                    </div>

                                    <div class="flex justify-between mb-1">
                                        <span class="font-semibold">Date</span>
                                        <span>{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</span>
                                    </div>

                                    <div class="flex justify-between mb-1">
                                        <span class="font-semibold">Quarter</span>
                                        <span>{{ $record->grading_period }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold">Status</span>
                                        <div class="flex items-center gap-2">
                                            @if($record->status === 'present')
                                                <div class="w-2 h-2 rounded-full bg-[#4ed43c]"></div>
                                                <span class="text-[#4ed43c] font-semibold">Present</span>
                                            @elseif($record->status === 'absent')
                                                <div class="w-2 h-2 rounded-full bg-[#b83330]"></div>
                                                <span class="text-[#b83330] font-semibold">Absent</span>
                                            @elseif($record->status === 'excused')
                                                <div class="w-2 h-2 rounded-full bg-[#b298f6]"></div>
                                                <span class="text-[#b298f6] font-semibold">Excused</span>
                                            @elseif($record->status === 'late')
                                                <div class="w-2 h-2 rounded-full bg-[#fca127]"></div>
                                                <span class="text-[#fca127] font-semibold">Late</span>
                                            @else
                                                <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                                                <span class="text-gray-500 font-semibold">
                                                    {{ ucfirst($record->status) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- TOTAL SCORE --}}
                        <div class="flex justify-end mt-4">
                            <p class="text-sm font-semibold text-[#5E5F60] dark:text-white">
                                Total Score:
                                <span class="text-[#5F2DED] dark:text-[#A88BFF] font-bold">
                                    {{ $earnedScoreFormatted }}
                                </span>
                                /
                                <span class="font-bold">{{ $totalDays }}</span>
                            </p>
                        </div>

                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
