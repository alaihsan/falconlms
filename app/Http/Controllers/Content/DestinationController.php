<?php

namespace App\Http\Controllers\Content;

use App\Enums\MemberRole;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DestinationController extends Controller
{
    /**
     * Places the user may save new content to, for the location picker of
     * "+ Baru": the folders of their own library and the topics of the
     * classes they teach.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $libraries = [];

        if ($user->hasRole(Role::Teacher)) {
            $library = Space::personalLibraryFor($user);
            $libraries[] = ['space' => $this->space($library), 'folders' => $this->folders($library)];
        }

        $courses = $user->courses()
            ->wherePivot('role', MemberRole::Teacher->value)
            ->with('topics')
            ->orderBy('name')
            ->get()
            ->map(fn (Space $course) => [
                'space' => $this->space($course),
                'topics' => $course->topics->map(fn (Topic $topic) => ['id' => $topic->id, 'name' => $topic->name])->values()->all(),
            ])
            ->values()
            ->all();

        return response()->json(['libraries' => $libraries, 'courses' => $courses]);
    }

    /**
     * @return array{id: int, kind: string, name: string}
     */
    private function space(Space $space): array
    {
        return ['id' => $space->id, 'kind' => $space->kind->value, 'name' => $space->displayName()];
    }

    /**
     * Every folder in tree order, with its full path as a label.
     *
     * @return list<array{id: int, name: string, label: string, depth: int}>
     */
    private function folders(Space $library): array
    {
        $children = Folder::query()
            ->where('space_id', $library->id)
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->groupBy(fn (Folder $folder) => $folder->parent_id ?? 0);

        $list = [];

        $walk = function (int $parentId, array $names) use (&$walk, &$list, $children): void {
            /** @var Collection<int, Folder> $folders */
            $folders = $children->get($parentId, collect());

            foreach ($folders as $folder) {
                $path = [...$names, $folder->name];
                $list[] = ['id' => $folder->id, 'name' => $folder->name, 'label' => implode(' › ', $path), 'depth' => count($names)];
                $walk($folder->id, $path);
            }
        };

        $walk(0, []);

        return $list;
    }
}
