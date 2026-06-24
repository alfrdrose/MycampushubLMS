@props(['description', 'attachments'])

{{-- Description --}}
@if($description)
<div class="my-4">
    <div class="prose dark:prose-invert">
        {!! $description !!}
    </div>
</div>
@endif

{{-- Attached Files --}}
@if($attachments && $attachments->isNotEmpty())
<div class="mt-4 pt-4">
    <h2 class="text-xl font-semibold text-[#5E5F60] dark:text-white mb-2">Attached Files</h2>
    <ul class="space-y-2">
        @foreach($attachments as $media)
            <li class="flex justify-between items-center bg-gray-100 dark:bg-[#2A2A3C] p-2 rounded-lg">
                <div class="flex items-center gap-2"> 
                    <i class="icofont-file-alt text-primaryColor"></i>
                    <a href="{{ $media->getUrl() }}" target="_blank"
   class="text-[#373737] dark:text-white truncate max-w-[70vw] sm:max-w-xs">
    {{ $media->file_name }}
</a>

                </div>
            </li>
        @endforeach
    </ul>
</div>
@endif
