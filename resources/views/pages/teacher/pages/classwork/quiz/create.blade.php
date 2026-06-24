@extends('pages.teacher.layout')
@section('content')
    <form action="{{ route('teacher.quiz.store', [$section->id, $subject->id]) }}" method="POST">
        @csrf
        <div class="bg-white dark:bg-[#1E1E2D] shadow-lg border p-8 rounded-lg"> 
            <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
                <h1 class="text-3xl font-bold mb-4 text-[#5E5F60] dark:text-white">Create Quiz</h1>
            </div>

            <label class="text-[#373737] dark:text-white font-medium mt-4">Quiz Title</label>
            <input type="text" name="title" class="w-full mt-2 p-2 border rounded" required>

            <label class="text-[#373737] dark:text-white font-medium mt-4">Description</label>
            <textarea name="description" class="w-full mt-2 p-2 border rounded"></textarea>

            <label class="text-[#373737] dark:text-white font-medium mt-4">Classwork Type</label>
            <select name="classwork_type" class="w-full mt-2 p-2 border rounded" required>
                @foreach($classworkTypes as $type)
                    <option value="{{ $type->id }}">
                        {{ $type->classwork_type }} {{ $type->percentage ? '(' . ($type->percentage + 0) . '%)' : '' }}
                    </option>
                @endforeach
            </select>

            <label class="text-[#373737] dark:text-white font-medium mt-4">Grading Period</label>
            <select name="grading_period" class="w-full mt-2 p-2 border rounded" required>
                <option value="">Select</option>
                <option value="1st">1st Quarter</option>
                <option value="2nd">2nd Quarter</option>
                <option value="3rd">3rd Quarter</option>
                <option value="4th">4th Quarter</option>
            </select>

            <div class="w-full justify-end flex mt-6"> 
                <button class="bg-[#5F2DED] text-white px-4 py-2 rounded-lg">
                    Create Quiz
                </button>
            </div>
        </div>
    </form>
@endsection
