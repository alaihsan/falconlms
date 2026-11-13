<?php

namespace App\Http\Controllers;

use App\Http\Requests\VotePollRequest;
use App\Models\LearningItem;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class PollController extends Controller
{
    /**
     * Cast or change the current student's vote on a poll.
     */
    public function vote(VotePollRequest $request, LearningItem $learningItem): RedirectResponse
    {
        if ($learningItem->type !== 'poll') {
            abort(Response::HTTP_NOT_FOUND, 'Konten ini bukan polling.');
        }

        /** @var User $user */
        $user = $request->user();

        PollVote::updateOrCreate(
            ['learning_item_id' => $learningItem->id, 'user_id' => $user->id],
            ['poll_option_id' => $request->validated('poll_option_id')],
        );

        return back()->with('success', "Jawaban Anda untuk \"{$learningItem->title}\" tersimpan.");
    }
}
