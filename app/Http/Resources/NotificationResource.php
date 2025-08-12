<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type ?? null,
            'title'      => $this->data['title'] ?? null,
            'excerpt'    => Str::limit($this->data['body'] ?? '', 30, '...'),
            'body'       => $this->data['body'] ?? null,
            'read'       => !is_null($this->read_at),
            'read_at'    => optional($this->read_at)->format('Y-m-d H:i:s'),
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
