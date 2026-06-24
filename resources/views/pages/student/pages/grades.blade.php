@extends('pages.student.layout')

@section('content')
<div class="bg-white dark:bg-[#1F2937] shadow-lg border border-transparent dark:border-[#374151] p-6 rounded-2xl">
    {{-- HEADER --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#374151] mb-6 flex items-center justify-between">
        <h1 class="text-lg sm:text-2xl font-bold text-[#5E5F60] dark:text-white">
            Your Grades
        </h1>
    </div>

    {{-- RESPONSIVE WRAPPER --}}
    <div class="overflow-x-auto p-4">
        <table class="w-full border-separate border-spacing-0 rounded-xl overflow-hidden">
            {{-- TABLE HEADER --}}
            <thead>
                <tr class="bg-[#03A9F4] dark:bg-[#0284C7]">
                    <th class="px-4 py-3 text-left text-[#373737] dark:text-[#0F172A] font-bold first:rounded-tl-xl">
                        Subject
                    </th>
                    <th class="px-4 py-3 text-center text-[#373737] dark:text-[#0F172A] font-bold">
                        1st
                    </th>
                    <th class="px-4 py-3 text-center text-[#373737] dark:text-[#0F172A] font-bold">
                        2nd
                    </th>
                    <th class="px-4 py-3 text-center text-[#373737] dark:text-[#0F172A] font-bold">
                        3rd
                    </th>
                    <th class="px-4 py-3 text-center text-[#373737] dark:text-[#0F172A] font-bold last:rounded-tr-xl">
                        4th
                    </th>
                </tr>
            </thead>

            {{-- TABLE BODY --}}
            <tbody class="bg-white dark:bg-[#111827]">
                @forelse ($subjects as $subject)
                    <tr class="border-b border-[#E5E7EB] dark:border-[#374151] last:border-0">
                        {{-- SUBJECT COLUMN --}}
                        <td class="px-4 py-3 bg-[#F0F9FF] dark:bg-[#1E3A8A]/20 text-[#373737] dark:text-white font-medium">
                            {{ $subject['subject_name'] }}
                        </td>

                        {{-- GRADES --}}
                        <td class="px-4 py-3 text-center text-[#373737] dark:text-[#E5E7EB]">
                            {{ $subject['grades']['1st'] ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-[#373737] dark:text-[#E5E7EB]">
                            {{ $subject['grades']['2nd'] ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-[#373737] dark:text-[#E5E7EB]">
                            {{ $subject['grades']['3rd'] ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-[#373737] dark:text-[#E5E7EB]">
                            {{ $subject['grades']['4th'] ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-[#6B7280] dark:text-[#9CA3AF]">
                            No grades available.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
