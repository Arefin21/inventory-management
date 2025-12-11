<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductService
{

    public function handleImageUpload(?UploadedFile $image, ?string $oldImagePath = null): ?string
    {
        // Delete old image if exists and new image is being uploaded
        if ($oldImagePath && $image) {
            $this->deleteImage($oldImagePath);
        }

        // Upload new image
        if ($image) {
            return $image->store('products', 'public');
        }

        return $oldImagePath;
    }



    public function deleteImage(string $imagePath): bool
    {
        if (Storage::disk('public')->exists($imagePath)) {
            return Storage::disk('public')->delete($imagePath);
        }

        return false;
    }

   

    public function getImageUrl(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        return Storage::disk('public')->url($imagePath);
    }
}

