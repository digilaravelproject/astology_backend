<?php

namespace App\Http\Controllers\Api;

use App\Models\AstrologerGallery;
use App\Models\Astrologer;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class AstrologerGalleryController extends Controller
{
    /**
     * Insert multiple images
     */
    public function storeMultiple(Request $request)
    {
        try {
            $user = $request->user();
            $astrologer = Astrologer::where('user_id', $user->id)->firstOrFail();

            $validated = $request->validate([
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
                'status' => 'sometimes|in:active,pending',
            ]);

            $images = [];
            $status = $validated['status'] ?? 'pending';

            foreach ($request->file('images') as $image) {
                $path = $image->store("astrologer-gallery/{$astrologer->id}", 'public');
                
                $gallery = AstrologerGallery::create([
                    'astrologer_id' => $astrologer->id,
                    'image_path' => $path,
                    'status' => $status,
                    'is_visible' => true,
                ]);

                $images[] = $gallery;
            }

            return ApiResponse::success($images, 'Images uploaded successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get total gallery images
     */
    public function getTotalImages(Request $request)
    {
        try {
            $user = $request->user();
            $astrologer = Astrologer::where('user_id', $user->id)->firstOrFail();

            $totalImages = AstrologerGallery::where('astrologer_id', $astrologer->id)->count();
            $activeImages = AstrologerGallery::where('astrologer_id', $astrologer->id)
                ->where('status', 'active')
                ->count();
            $pendingImages = AstrologerGallery::where('astrologer_id', $astrologer->id)
                ->where('status', 'pending')
                ->count();

            $data = [
                'total_images' => $totalImages,
                'active_images' => $activeImages,
                'pending_images' => $pendingImages,
            ];

            return ApiResponse::success($data, 'Gallery count retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all gallery images with pagination
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $astrologer = Astrologer::where('user_id', $user->id)->firstOrFail();

            $per_page = $request->get('per_page', 15);
            $status = $request->get('status'); // Filter by status

            $query = AstrologerGallery::where('astrologer_id', $astrologer->id);

            if ($status) {
                $query->where('status', $status);
            }

            $galleries = $query->orderByDesc('created_at')->paginate($per_page);

            return ApiResponse::success($galleries, 'Gallery images retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Toggle visibility of an image
     */
    public function toggleVisibility($id, Request $request)
    {
        try {
            $user = $request->user();
            $astrologer = Astrologer::where('user_id', $user->id)->firstOrFail();

            $gallery = AstrologerGallery::where('astrologer_id', $astrologer->id)
                ->findOrFail($id);

            $gallery->is_visible = !$gallery->is_visible;
            $gallery->save();

            return ApiResponse::success($gallery, 'Visibility toggled successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error('Gallery image not found', 404);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a gallery image
     */
    public function destroy($id, Request $request)
    {
        try {
            $user = $request->user();
            $astrologer = Astrologer::where('user_id', $user->id)->firstOrFail();

            $gallery = AstrologerGallery::where('astrologer_id', $astrologer->id)
                ->findOrFail($id);

            // Delete from storage
            if (Storage::disk('public')->exists($gallery->image_path)) {
                Storage::disk('public')->delete($gallery->image_path);
            }

            $gallery->delete();

            return ApiResponse::success(null, 'Gallery image deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error('Gallery image not found', 404);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
