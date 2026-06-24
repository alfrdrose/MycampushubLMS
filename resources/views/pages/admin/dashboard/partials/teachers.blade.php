
{{-- Recent activities log --}}
<div class=""> 
  {{-- filters & Search --}}
  <div class="flex w-full flex-row h-auto justify-center items-center pb-4">
    {{-- left side --}}
    <div class="flex-1 flex flex-row items-center gap-12 py-[0.5%]">
      <p class="text-[#5E5F60] dark:text-white">Filter by:</p>
      {{-- type filter --}}
      <div class="flex items-center gap-1 cursor-pointer ">
        <p class="font-medium text-[#5E5F60] dark:text-white text-sm">Type</p>
        <i class="icofont-rounded-down dark:text-white"></i>
      </div>
      {{-- status filter --}}
      <div class="flex items-center gap-1 cursor-pointer">
        <p class="font-medium text-[#5E5F60] dark:text-white text-sm">Status</p>
        <i class="icofont-rounded-down dark:text-white"></i>
      </div>
    </div>
    {{-- right side --}}
    <div class="flex-1 flex justify-end ">
      {{-- Search Bar --}}
      <div class="relative w-[60%]">
        {{-- Search icon --}}
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
          <i class="icofont-search-1"></i>
        </span>
        <input 
          type="text" 
          placeholder="Search..." 
          class="w-full pl-10 pr-3 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7F6994] text-sm
          dark:bg-bodyBg-dark dark:text-white dark:focus:ring-blue
          "
        />
      </div>
    </div>
  </div>
  <div class="pb-6">
  {{-- comment --}}
    <div class="overflow-y-auto max-h-[410px] dark:bg-transparent scrollbar-thin border-b-[1px]">
      <table class="min-w-full max-w-full text-sm ">
        <thead class="border-b-[1px] sticky top-0 ">
          <tr class="bg-whiteColor dark:bg-whiteColor-dark">
            @foreach ($columns as $column)
                <th class="py-4 px-4 text-center font-semibold text-[#5E5F60] dark:text-gray-300">
                    <div class="flex items-center gap-1 cursor-pointer">
                        {{ ucfirst(str_replace('_', ' ', $column)) }}
                        <div class="flex flex-col leading-none text-xs">
                            <i class="icofont-caret-up -mb-1 hover:text-[#5F2DED]"></i>
                            <i class="icofont-caret-down hover:text-[#5F2DED]"></i>
                        </div>
                    </div>
                </th>
            @endforeach
        </thead>
        <tbody>
          {{-- testing --}}
            @forelse ($teachers as $teacher)
            <tr class="border-b text-[#373737] dark:text-white">
                <td class="py-4 px-4">{{ $teacher['teacherID'] }}</td>
                <td class="py-4 px-4">{{ $teacher['name'] }}</td>
                <td class="py-4 px-4">{{ $teacher['faculty'] }}</td>
                @php
                    $statusClass = $teacher['status'] === 'Active'
                        ? 'text-[#008236] bg-[#DCFCE7] text-center p-1 rounded-lg w-[80%] font-medium'
                        : 'text-[#E7000B] bg-[#FFE2E2] text-center p-1 rounded-lg w-[80%] font-medium';
                @endphp
                <td class="py-4 px-4 "><p class="{{ $statusClass }}">{{ $teacher['status'] }}</p></td>
                <td class="py-4 px-4">{{ $teacher['actions'] }}</td>
            </tr>
              @empty
                  <tr>
                      <td colspan="4" class="text-center py-4 text-gray-500">
                          No teachers found.
                      </td>
                  </tr>
              @endforelse


        </tbody>
      </table>
    </div>
  </div>


<div class="flex ">
  <div class="flex-1 flex flex-row items-center gap-4">

    <button 
      data-page="addaccount"
      class="p-2 text-sm gap-2 flex flex-row justify-center items-center text-[#5F2DED] font-[450] border border-[#5F2DED] rounded-xl
      dark:text-[#244890] dark:border-[#244890] hover:bg-[#5F2DED] hover:text-white hover:dark:bg-[#244890] hover:dark:text-white">
      <i class="icofont-ui-add text-sm"></i>
        <p>Add</p>
  </button>

    <button class="p-2 text-sm gap-2 flex flex-row justify-center items-center text-[#C10007] font-[450] border border-[#C10007] rounded-xl
    dark:text-[#C10007] dark:border-[#C10007] hover:bg-[#C10007] hover:text-white hover:dark:bg-[#C10007] hover:dark:text-white">
      <i class="icofont-ui-delete text-sm"></i>
      <p>Delete All</p>
    </button>

  </div>
  <div class="flex-1 flex justify-end">
  {{-- Pagination --}}
    {{-- {{ $teachers->links() }} --}}
  </div>
</div>


</div>




