<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'image' => $this->image ? asset('storage/' . $this->image) : asset('storage/dummy.jpg'),
            'category' => $this->category ? $this->category->name : null,
            'cats' => $this->cats->take(3)->map(function ($cat) {
                return $cat->name;
            })->toArray(),
            'user_name' => $this->user ? $this->user->name : '店長',
            'url' => route('blogs.show', $this),
        ];
    }
}
