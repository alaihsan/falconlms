<?php

namespace App\Http\Requests\Content;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Space;
use App\Support\Uploads;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveItemRequest extends FormRequest
{
    /**
     * The space the item is created in or already belongs to.
     */
    public function space(): Space
    {
        $space = $this->route('space');

        if ($space instanceof Space) {
            return $space;
        }

        /** @var Item $item */
        $item = $this->route('item');

        return $item->space;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $space = $this->space();
        $types = $space->isCourse()
            ? [ItemType::Material, ItemType::Assignment]
            : [ItemType::Material, ItemType::Assignment, ItemType::Link];

        return [
            'type' => [$this->route('item') ? 'prohibited' : 'required', Rule::enum(ItemType::class)->only($types)],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:200000'],
            'url' => ['nullable', 'required_if:type,link', 'url:http,https', 'max:2000'],
            'due_at' => ['nullable', 'date'],
            'max_points' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'publish' => ['boolean'],
            'files' => ['array', 'max:20'],
            'files.*' => Uploads::rules(),
            'links' => ['array', 'max:20'],
            'links.*' => ['url:http,https', 'max:2000'],
            'remove_attachments' => ['array'],
            'remove_attachments.*' => ['integer'],
            'also_save_to_library' => ['boolean'],
            // Folders belong to libraries and topics to classes, always of this same space.
            'folder_id' => $space->isCourse()
                ? ['prohibited']
                : ['nullable', 'integer', Rule::exists('folders', 'id')->where('space_id', $space->id)->whereNull('deleted_at')],
            'topic_id' => $space->isCourse()
                ? ['nullable', 'integer', Rule::exists('topics', 'id')->where('space_id', $space->id)]
                : ['prohibited'],
        ];
    }
}
