<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = Blog::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            $query->where('type', $type);
        }

        $blogs = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $total = Blog::count();
        $active = Blog::where('is_active', true)->count();
        $drafts = Blog::where('is_active', false)->count();

        return view('admin.blogs.index', compact('blogs', 'total', 'active', 'drafts'));
    }

    public function create()
    {
        return view('admin.blogs.create', ['blog' => new Blog()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'type' => 'required|in:article,news,update,education,announcement',
            'blog_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'blog_tags' => 'nullable|array',
            'blog_tags.*' => 'string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('blog_image')) {
            $file = $request->file('blog_image');
            $filename = 'blog_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $data['blog_image'] = Storage::disk('public')->putFileAs('blogs/images', $file, $filename);
        }

        $data['blog_tags'] = $request->input('blog_tags', []);
        $data['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        Blog::create($data);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post created successfully.');
    }

    public function edit($id)
    {
        $blog = Blog::findOrFail($id);
        return view('admin.blogs.create', compact('blog'));
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'type' => 'required|in:article,news,update,education,announcement',
            'blog_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'blog_tags' => 'nullable|array',
            'blog_tags.*' => 'string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('blog_image')) {
            // delete old image if exists
            if ($blog->blog_image && Storage::disk('public')->exists($blog->blog_image)) {
                Storage::disk('public')->delete($blog->blog_image);
            }
            $file = $request->file('blog_image');
            $filename = 'blog_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $data['blog_image'] = Storage::disk('public')->putFileAs('blogs/images', $file, $filename);
        }

        $data['blog_tags'] = $request->input('blog_tags', []);
        $data['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        $blog->update($data);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post updated successfully.');
    }

    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post deleted successfully.');
    }
}
