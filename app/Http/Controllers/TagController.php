<?php

namespace App\Http\Controllers;

use App\Models\SubjectModel;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class TagController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $tags = Tag::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $columns = ['id', 'name', 'category', 'actions'];

        return view('pages.admin.dashboard.tags', [
            'title' => 'My Campus LMS | Tags',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'sidebar' => true,
            'banner' => false,
            'tags' => $tags,
            'columns' => $columns,
        ]);
    }

    public function create()
    {
        $subjects = SubjectModel::query()
            ->orderBy('grade_level')
            ->orderBy('subject_name')
            ->get();

        return view('components.tags.addtagContent', [
            'title' => 'My Campus LMS | Add Tag',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'sidebar' => true,
            'banner' => false,
            'subjects' => $subjects, // pass subjects to view
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:tags,name'
            ],
            'category' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Please enter the tag name.',
            'name.unique' => 'This tag already exists.',
            'category.required' => 'Please enter the tag category.',
        ]);


        $slug = $this->generateUniqueSlug($validated['name']);

        Tag::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'category' => $validated['category'],
        ]);

        return redirect()
            ->route('admin.tags')
            ->with('success', 'Tag added successfully!');
    }

    public function edit(string $id)
{
    $tag = Tag::findOrFail($id);

    $subjects = SubjectModel::query()
        ->orderBy('grade_level')
        ->orderBy('subject_name')
        ->get();

    return view('components.tags.updatetagContent', [
        'title' => 'My Campus LMS | Update Tag',
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'sidebar' => true,
        'banner' => false,
        'tag' => $tag,
        'subjects' => $subjects,
    ]);
}


    public function update(Request $request, string $id)
    {
        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name')->ignore($tag->id),
            ],
            'category' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Please enter the tag name.',
            'name.unique' => 'This tag already exists.',
            'category.required' => 'Please enter the tag category.',
        ]);


        $slug = $this->generateUniqueSlug($validated['name'], $tag->id);

        $tag->name = $validated['name'];
        $tag->slug = $slug;
        $tag->category = $validated['category'];
        $tag->save();

        return redirect()
            ->route('admin.tags')
            ->with('success', 'Tag updated successfully!');
    }

    public function destroy(string $id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully!',
        ]);
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'tag';
        }

        $slug = $base;
        $counter = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $counter++;
            $slug = $base . '-' . $counter;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Tag::query()
            ->where('slug', $slug)
            ->when($ignoreId, function ($query, $ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->exists();
    }
}
