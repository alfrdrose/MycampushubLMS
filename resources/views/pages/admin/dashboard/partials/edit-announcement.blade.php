@extends('layouts.dashboard')

@section('content')
@php
    $announcementQuery = $announcementNavigationQuery ?? request()->only(['school_year']);
@endphp
<div class="w-full p-6 md:p-10 bg-whiteColor dark:bg-whiteColor-dark shadow-accordion dark:shadow-accordion-dark rounded-lg">

    <div id="page-header" class="mb-4 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-bold text-[#5E5F60] dark:text-white">
                Edit Announcement
            </h1>
            <p class="text-sm text-[#808080] dark:text-gray-300 mt-1">
                Update the details below.
            </p>
        </div>

        <div class="rounded-full border border-[#D4D4D8] bg-[#F8FAFC] px-3 py-1 text-xs text-[#808080] dark:border-gray-700 dark:bg-[#161616] dark:text-gray-300 sm:ml-auto">
            {{ $announcement->schoolYear ? 'SY ' . $announcement->schoolYear->label : 'Unassigned school year' }}
        </div>
    </div>

    @if ($announcementReadOnly)
        <div class="mb-4 rounded-xl border border-[#F3D7A3] bg-[#FFF7E8] px-4 py-3 text-sm text-[#8A5A00] dark:border-[#5B4721] dark:bg-[#2B2112] dark:text-[#F2C889]">
            Viewing records for SY {{ $announcement->schoolYear?->label ?? 'Unassigned' }}. All announcement actions are disabled while this school year is inactive.
        </div>
    @endif

    <div class="border-t border-[#D4D4D8] dark:border-white py-4">

        <form
            action="{{ route('admin.updateAnnouncement', array_merge(['announcement' => $announcement->id], $announcementQuery)) }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6"
            x-data="{
                title: @js($announcement->title),
                description: @js(strip_tags($announcement->description)),
                currentImage: {{ $announcement->image ? "'" . asset('storage/' . $announcement->image) . "'" : 'null' }},
                currentImageRemoved: false,
                newImagePreview: null,
                newImageName: null,
                confirmOpen: false,
                errors: {
                    title: '',
                    description: '',
                },

                validateTitle() {
                    if (!this.title.trim()) this.errors.title = 'Title is required.';
                    else this.errors.title = '';
                },

                validateDescription() {
                    if (!this.description.trim()) this.errors.description = 'Description is required.';
                    else this.errors.description = '';
                },

                get hasErrors() {
                    return Object.values(this.errors).some(err => err && err.length > 0);
                },

                removeCurrentImage() {
                    this.currentImageRemoved = true;
                    this.currentImage = null;
                },

                previewNewImage(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.currentImageRemoved = false;
                    this.newImageName = file.name;
                    this.newImagePreview = URL.createObjectURL(file);
                },

                removeNewImage() {
                    this.newImagePreview = null;
                    this.newImageName = null;
                    this.$refs.newImageInput.value = null;
                }
            }"
            x-init="
                validateTitle();
                validateDescription();

                setTimeout(() => {
                    const editor = document.querySelector('trix-editor');
                    if (editor) editor.editor.loadHTML(@js($announcement->description));
                }, 50);

                document.addEventListener('trix-change', (e) => {
                    description = e.target.editor.getDocument().toString();
                    validateDescription();
                });
            "
        >
            @csrf
            @method('PUT')

            <div @if ($announcementReadOnly) inert aria-disabled="true" @endif class="{{ $announcementReadOnly ? 'pointer-events-none select-none opacity-70' : '' }}">
                <div>
                    <label class="block text-lg font-semibold text-[#5E5F60] dark:text-white mb-2">
                        Title <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="title"
                        required
                        x-model="title"
                        @input="validateTitle()"
                        class="w-full px-4 py-3 rounded-lg border bg-white dark:bg-[#1E1E1E]
                               text-[#5E5F60] dark:text-white focus:outline-none focus:ring-2 focus:ring-primaryColor"
                        :class="errors.title ? 'border-red-500' : 'border-[#D4D4D8] dark:border-gray-700'"
                    />

                    <p x-show="errors.title" x-text="errors.title" class="text-sm text-red-500 mt-2"></p>
                </div>

                <div>
                    <label class="block text-lg font-semibold text-[#5E5F60] dark:text-white mb-2">
                        Description <span class="text-red-500">*</span>
                    </label>

                    <input id="description" type="hidden" name="description" required>

                    <trix-editor
                        input="description"
                        class="w-full rounded-lg border bg-white dark:bg-[#1E1E1E]
                               text-[#5E5F60] dark:text-white min-h-[180px] p-3"
                        :class="errors.description ? 'border-red-500' : 'border-[#D4D4D8] dark:border-gray-700'"
                    ></trix-editor>

                    <p x-show="errors.description" x-text="errors.description" class="text-sm text-red-500 mt-2"></p>
                </div>

                <div class="space-y-4">
                    <label class="block text-lg font-semibold text-[#5E5F60] dark:text-white mb-2">
                        Image <span class="text-xs text-gray-400">(optional)</span>
                    </label>

                    <template x-if="currentImage">
                        <div class="w-full">
                            <p class="text-sm text-[#808080] dark:text-gray-300 mb-2">
                                Current Image
                            </p>

                            <div class="relative w-full sm:w-[320px]">
                                <img
                                    :src="currentImage"
                                    class="w-full h-48 object-cover rounded-xl border border-[#D4D4D8] dark:border-gray-700"
                                    alt="Current Image"
                                >

                                <button
                                    type="button"
                                    @click="removeCurrentImage()"
                                    class="absolute top-2 right-2 w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black/80 transition"
                                    title="Remove current image"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                    </template>

                    <input type="hidden" name="remove_image" :value="currentImageRemoved ? 1 : 0">

                    <div class="w-full">
                        <p class="text-sm text-[#808080] dark:text-gray-300 mb-2">
                            Upload New Image
                        </p>

                        <input
                            type="file"
                            name="image"
                            accept="image/*"
                            x-ref="newImageInput"
                            @change="previewNewImage($event)"
                            class="block w-full sm:w-auto text-sm text-[#5E5F60] dark:text-white
                                   file:mr-4 file:py-2 file:px-4
                                   file:rounded-lg file:border-0
                                   file:bg-[#E1D6FF] dark:file:bg-[#5F2DED]
                                   file:text-[#5F2DED] dark:file:text-white
                                   hover:file:opacity-90 cursor-pointer"
                        />

                        <template x-if="newImagePreview">
                            <div class="mt-4 relative w-full sm:w-[320px]">
                                <img
                                    :src="newImagePreview"
                                    class="w-full h-48 object-cover rounded-xl border border-[#D4D4D8] dark:border-gray-700"
                                    alt="New Preview"
                                >

                                <button
                                    type="button"
                                    @click="removeNewImage()"
                                    class="absolute top-2 right-2 w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black/80 transition"
                                    title="Remove new image"
                                >
                                    &times;
                                </button>

                                <p class="text-xs text-[#808080] dark:text-gray-400 mt-2 truncate">
                                    <span class="font-semibold">Selected:</span> <span x-text="newImageName"></span>
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-2">
                <a
                    href="{{ route('admin.announcements', $announcementQuery) }}"
                    class="w-full sm:w-auto px-4 py-3 rounded-lg border border-[#D4D4D8] dark:border-gray-700
                        text-center text-[#5E5F60] dark:text-white
                        hover:opacity-80 transition"
                >
                    Cancel
                </a>

                @if ($announcementReadOnly)
                    <button
                        type="button"
                        disabled
                        aria-disabled="true"
                        class="w-full sm:w-auto cursor-not-allowed rounded-lg bg-gray-300 px-4 py-3 text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                    >
                        <i class="icofont-save mr-2"></i> Update Announcement
                    </button>
                @else
                    <button
                        type="button"
                        :disabled="hasErrors"
                        @click="confirmOpen = true"
                        class="w-full sm:w-auto px-4 py-3 rounded-lg flex items-center justify-center transition-all"
                        :class="hasErrors
                            ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-300 cursor-not-allowed'
                            : 'bg-[#5F2DED] text-white md:hover:scale-105'"
                    >
                        <i class="icofont-save mr-2"></i> Update Announcement
                    </button>
                @endif
            </div>

            @unless ($announcementReadOnly)
                <div
                    x-show="confirmOpen"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center"
                >
                    <div class="absolute inset-0 bg-black/50" @click="confirmOpen = false"></div>

                    <div
                        class="relative w-[90%] max-w-md bg-white dark:bg-[#1E1E1E] rounded-2xl shadow-lg p-6"
                        x-transition
                        @keydown.escape.window="confirmOpen = false"
                    >
                        <div class="flex items-start justify-between mb-4">
                            <h2 class="text-xl font-bold text-[#5E5F60] dark:text-white">
                                Confirm Update
                            </h2>

                            <button type="button" @click="confirmOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                &times;
                            </button>
                        </div>

                        <p class="text-sm text-[#808080] dark:text-gray-300">
                            Are you sure you want to update this announcement?
                        </p>

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="confirmOpen = false"
                                class="px-4 py-2 rounded-lg border border-[#D4D4D8] dark:border-gray-700
                                       text-[#5E5F60] dark:text-white hover:opacity-80 transition"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="bg-[#5F2DED] text-white px-4 py-2 rounded-lg hover:scale-105 transition-all"
                            >
                                Yes, Update
                            </button>
                        </div>
                    </div>
                </div>
            @endunless

        </form>

    </div>
</div>
@endsection
