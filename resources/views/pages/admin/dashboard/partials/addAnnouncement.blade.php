@extends('layouts.dashboard')

@section('content')
@php
    $announcementQuery = $announcementNavigationQuery ?? request()->only(['school_year']);
@endphp
<div class="w-full p-6 md:p-10 bg-whiteColor dark:bg-whiteColor-dark shadow-accordion dark:shadow-accordion-dark rounded-lg">
    
    <div id="page-header" class="mb-4 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 id="page-title" class="text-3xl font-bold text-[#5E5F60] dark:text-white">
                Add Announcement
            </h1>
            <p class="text-sm text-[#808080] dark:text-gray-300 mt-1">
                Fill in the details below to post a new announcement.
            </p>
        </div>

        <div class="flex items-center gap-3 sm:ml-auto">
            <div class="rounded-full border border-[#D4D4D8] bg-[#F8FAFC] px-3 py-1 text-xs text-[#808080] dark:border-gray-700 dark:bg-[#161616] dark:text-gray-300">
                {{ $activeSchoolYear ? 'SY ' . $activeSchoolYear->label : 'No active school year' }}
            </div>

            <div id="spinner-template" class="hidden">
                <div class="flex items-center justify-center h-full w-full">
                    <x-spinner />
                </div>
            </div>
        </div>
    </div>

    <div id="main-panel">
        <div class="border-t border-[#D4D4D8] dark:border-white py-4">

            <form 
                action="{{ route('admin.storeAnnouncement', $announcementQuery) }}"
                method="POST" 
                enctype="multipart/form-data"
                class="space-y-6"
                x-data="{
                    title: @js(old('title', '')),
                    description: @js(trim(strip_tags(old('description', '')))),
                    imagePreview: null,
                    imageName: null,
                    confirmOpen: false,

                    touched: {
                        title: false,
                        description: false,
                    },

                    errors: {
                        title: '',
                        description: '',
                    },

                    validateTitle() {
                        if (!this.title.trim()) {
                            this.errors.title = 'Title is required.';
                        } else {
                            this.errors.title = '';
                        }
                    },

                    validateDescription() {
                        if (!this.description.trim()) {
                            this.errors.description = 'Description is required.';
                        } else {
                            this.errors.description = '';
                        }
                    },

                    get hasErrors() {
                        return Object.values(this.errors).some(err => err && err.length > 0);
                    },

                    removeImage() {
                        this.imagePreview = null;
                        this.imageName = null;
                        this.$refs.imageInput.value = null;
                    },

                    previewImage(event) {
                        const file = event.target.files[0];
                        if (!file) return;

                        this.imageName = file.name;
                        this.imagePreview = URL.createObjectURL(file);
                    },

                    attemptSubmit() {
                        this.touched.title = true;
                        this.touched.description = true;

                        this.validateTitle();
                        this.validateDescription();

                        if (!this.hasErrors) {
                            this.confirmOpen = true;
                        }
                    }
                }"
                x-init="
                    const initialDescription = @js(old('description', ''));
                    if (initialDescription) {
                        const editor = document.querySelector('trix-editor');
                        if (editor) {
                            editor.editor.loadHTML(initialDescription);
                        }
                    }

                    document.addEventListener('trix-change', (e) => {
                        description = e.target.editor.getDocument().toString();
                        touched.description = true;
                        validateDescription();
                    });
                "
            >
                @csrf

                @error('school_year')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror

                {{-- Title --}}
                <div class="w-full">
                    <label class="block text-lg font-semibold text-[#5E5F60] dark:text-white mb-2">
                        Title <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="title"
                        required
                        placeholder="Enter announcement title"
                        x-model="title"
                        @input="touched.title = true; validateTitle()"
                        class="w-full px-4 py-3 rounded-lg border 
                               bg-white dark:bg-[#1E1E1E] 
                               text-[#5E5F60] dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-primaryColor"
                        :class="touched.title && errors.title
                            ? 'border-red-500 dark:border-red-500'
                            : 'border-[#D4D4D8] dark:border-gray-700'"
                    />

                    <p 
                        x-show="touched.title && errors.title"
                        x-text="errors.title"
                        class="text-sm text-red-500 mt-2"
                    ></p>
                </div>

                {{-- Description --}}
                <div class="w-full">
                    <label class="block text-lg font-semibold text-[#5E5F60] dark:text-white mb-2">
                        Description <span class="text-red-500">*</span>
                    </label>

                    <input id="description" type="hidden" name="description" value="{{ old('description') }}" required>

                    <trix-editor
                        input="description"
                        class="w-full rounded-lg border 
                               bg-white dark:bg-[#1E1E1E]
                               text-[#5E5F60] dark:text-white
                               min-h-[180px] max-h-[250px]
                               overflow-y-auto p-3"
                        :class="touched.description && errors.description
                            ? 'border-red-500 dark:border-red-500'
                            : 'border-[#D4D4D8] dark:border-gray-700'"
                    ></trix-editor>

                    <p 
                        x-show="touched.description && errors.description"
                        x-text="errors.description"
                        class="text-sm text-red-500 mt-2"
                    ></p>

                    <p class="text-xs text-[#808080] dark:text-gray-400 mt-2">
                        Write the full announcement details here.
                    </p>
                </div>

                {{-- Image Upload --}}
                <div class="w-full">
                    <label class="block text-lg font-semibold text-[#5E5F60] dark:text-white mb-2">
                        Image <span class="text-xs text-gray-400">(optional)</span>
                    </label>

                    <input
                        type="file"
                        name="image"
                        accept="image/*"
                        x-ref="imageInput"
                        @change="previewImage($event)"
                        class="block w-full sm:w-auto text-sm text-[#5E5F60] dark:text-white
                               file:mr-4 file:py-2 file:px-4
                               file:rounded-lg file:border-0
                               file:bg-[#E1D6FF] dark:file:bg-[#5F2DED]
                               file:text-[#5F2DED] dark:file:text-white
                               hover:file:opacity-90 cursor-pointer"
                    />

                    <template x-if="imagePreview">
                        <div class="mt-4 relative w-full sm:w-[320px]">
                            <img 
                                :src="imagePreview"
                                alt="Preview"
                                class="w-full h-48 object-cover rounded-xl border border-[#D4D4D8] dark:border-gray-700"
                            >

                            <button
                                type="button"
                                @click="removeImage()"
                                class="absolute top-2 right-2 w-8 h-8 rounded-full
                                       bg-black/60 text-white flex items-center justify-center
                                       hover:bg-black/80 transition"
                            >
                                ✕
                            </button>

                            <p class="text-xs text-[#808080] dark:text-gray-400 mt-2 truncate">
                                <span class="font-semibold">Selected:</span>
                                <span x-text="imageName"></span>
                            </p>
                        </div>
                    </template>
                </div>

                {{-- Buttons --}}
                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                    <a
                        href="{{ route('admin.announcements', $announcementQuery) }}"
                        class="px-4 py-3 rounded-lg border border-[#D4D4D8]
                               dark:border-gray-700 text-[#5E5F60]
                               dark:text-white text-center hover:opacity-80"
                    >
                        Cancel
                    </a>

                    <button
                        type="button"
                        @click="attemptSubmit()"
                        @disabled(! $activeSchoolYear)
                        class="px-4 py-3 rounded-lg bg-[#5F2DED]
                               text-white transition-all disabled:cursor-not-allowed disabled:opacity-50 {{ $activeSchoolYear ? 'hover:scale-105' : '' }}"
                    >
                        <i class="icofont-check mr-2"></i> Post Announcement
                    </button>
                </div>

                {{-- Confirmation Modal --}}
                <div
                    x-show="confirmOpen"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center"
                >
                    <div class="absolute inset-0 bg-black/50" @click="confirmOpen = false"></div>

                    <div
                        class="relative w-[90%] max-w-md bg-white dark:bg-[#1E1E1E]
                               rounded-2xl shadow-lg p-6"
                        x-transition
                        @keydown.escape.window="confirmOpen = false"
                    >
                        <div class="flex justify-between mb-4">
                            <h2 class="text-xl font-bold text-[#5E5F60] dark:text-white">
                                Confirm Posting
                            </h2>
                            <button @click="confirmOpen = false">✕</button>
                        </div>

                        <p class="text-sm text-[#808080] dark:text-gray-300">
                            Are you sure you want to post this announcement?
                        </p>

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="confirmOpen = false"
                                class="px-4 py-2 border rounded-lg"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="bg-[#5F2DED] text-white px-4 py-2 rounded-lg"
                            >
                                Yes, Post
                            </button>
                        </div>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
