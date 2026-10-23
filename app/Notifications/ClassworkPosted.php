<?php

namespace App\Notifications;

use App\Enums\ItemType;
use App\Models\Item;

class ClassworkPosted extends InAppNotification
{
    public function __construct(public Item $item) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->item->space->name,
            'body' => $this->item->type === ItemType::Assignment
                ? __('New assignment: :title', ['title' => $this->item->title])
                : __('New material: :title', ['title' => $this->item->title]),
            'url' => route('items.show', $this->item),
        ];
    }
}
