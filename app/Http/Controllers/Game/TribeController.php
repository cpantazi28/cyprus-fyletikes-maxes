<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Tribe;
use App\Models\TribeDiplomacy;
use App\Models\TribeForumPost;
use App\Models\TribeForumThread;
use App\Models\TribeMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TribeController extends Controller
{
    /**
     * Tribe Overview or Create / Search Tribe page.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $membership = $user->tribeMember;

        if (! $membership) {
            $topTribes = Tribe::orderBy('rank')->take(10)->get();

            return view('game.tribe.none', [
                'topTribes' => $topTribes,
            ]);
        }

        $tribe = $membership->tribe;
        $tribe->recalculatePoints();
        $tribe->load(['members.user.villages', 'allies.targetTribe', 'enemies.targetTribe', 'naps.targetTribe']);

        return view('game.tribe.index', [
            'tribe' => $tribe,
            'membership' => $membership,
            'isLeader' => $membership->isLeader(),
        ]);
    }

    /**
     * Create a new tribe.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user->tribeMember) {
            return back()->with('error', 'You are already in a tribe.');
        }

        $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'tag' => ['required', 'string', 'min:2', 'max:8', 'unique:tribes,tag'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $tribe = Tribe::create([
            'name' => $request->input('name'),
            'tag' => strtoupper($request->input('tag')),
            'description' => $request->input('description'),
            'founder_id' => $user->id,
            'points' => $user->total_points,
            'rank' => Tribe::count() + 1,
            'member_count' => 1,
        ]);

        TribeMember::create([
            'tribe_id' => $tribe->id,
            'user_id' => $user->id,
            'role' => 'founder',
            'joined_at' => now(),
        ]);

        return redirect()->route('game.tribe.index')->with('success', "Tribe [{$tribe->tag}] {$tribe->name} created!");
    }

    /**
     * Member roster.
     */
    public function members(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $membership = $user->tribeMember;
        if (! $membership) {
            return redirect()->route('game.tribe.index');
        }

        $tribe = $membership->tribe;
        $members = $tribe->members()->with(['user.villages'])->get();

        return view('game.tribe.members', [
            'tribe' => $tribe,
            'members' => $members,
            'membership' => $membership,
            'isLeader' => $membership->isLeader(),
        ]);
    }

    /**
     * Update member role.
     */
    public function updateRole(Request $request, int $memberId): RedirectResponse
    {
        $user = Auth::user();
        $membership = $user->tribeMember;
        if (! $membership || ! $membership->isLeader()) {
            return back()->with('error', 'Only tribe leaders can manage member roles.');
        }

        $member = TribeMember::where('id', $memberId)->where('tribe_id', $membership->tribe_id)->first();
        if (! $member) {
            return back()->with('error', 'Member not found.');
        }

        $role = $request->input('role');
        if (in_array($role, ['baron', 'diplomat', 'recruiter', 'member'])) {
            $member->role = $role;
            $member->save();
        }

        return back()->with('success', 'Member rank updated.');
    }

    /**
     * Tribe Diplomacy.
     */
    public function diplomacy(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $membership = $user->tribeMember;
        if (! $membership) {
            return redirect()->route('game.tribe.index');
        }

        $tribe = $membership->tribe;
        $diplomacies = $tribe->diplomacies()->with('targetTribe')->get();

        return view('game.tribe.diplomacy', [
            'tribe' => $tribe,
            'diplomacies' => $diplomacies,
            'isLeader' => $membership->isLeader(),
        ]);
    }

    /**
     * Add diplomatic relationship.
     */
    public function storeDiplomacy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $membership = $user->tribeMember;
        if (! $membership || ! $membership->isLeader()) {
            return back()->with('error', 'Only tribe leaders can manage diplomacy.');
        }

        $request->validate([
            'tag' => ['required', 'string'],
            'type' => ['required', 'in:ally,nap,enemy'],
        ]);

        $targetTribe = Tribe::where('tag', strtoupper($request->input('tag')))->first();
        if (! $targetTribe) {
            return back()->with('error', "Tribe with tag '{$request->input('tag')}' not found.");
        }

        if ($targetTribe->id === $membership->tribe_id) {
            return back()->with('error', 'Cannot set diplomacy with your own tribe.');
        }

        TribeDiplomacy::updateOrCreate(
            ['tribe_id' => $membership->tribe_id, 'target_tribe_id' => $targetTribe->id],
            ['type' => $request->input('type'), 'status' => 'accepted']
        );

        return back()->with('success', "Diplomatic status with [{$targetTribe->tag}] updated.");
    }

    /**
     * Tribe Forum index.
     */
    public function forum(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $membership = $user->tribeMember;
        if (! $membership) {
            return redirect()->route('game.tribe.index');
        }

        $tribe = $membership->tribe;
        $threads = $tribe->forumThreads()->with(['user', 'lastPost.user'])->withCount('posts')->paginate(15);

        return view('game.tribe.forum', [
            'tribe' => $tribe,
            'threads' => $threads,
        ]);
    }

    /**
     * View Forum Thread.
     */
    public function forumThread(Request $request, int $threadId): View|RedirectResponse
    {
        $user = Auth::user();
        $membership = $user->tribeMember;
        if (! $membership) {
            return redirect()->route('game.tribe.index');
        }

        $thread = TribeForumThread::where('id', $threadId)
            ->where('tribe_id', $membership->tribe_id)
            ->with(['user', 'posts.user'])
            ->first();

        if (! $thread) {
            return redirect()->route('game.tribe.forum')->with('error', 'Thread not found.');
        }

        $thread->increment('views');

        return view('game.tribe.thread', [
            'tribe' => $membership->tribe,
            'thread' => $thread,
        ]);
    }

    /**
     * Create forum thread.
     */
    public function storeThread(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $membership = $user->tribeMember;
        if (! $membership) {
            return redirect()->route('game.tribe.index');
        }

        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $thread = TribeForumThread::create([
            'tribe_id' => $membership->tribe_id,
            'user_id' => $user->id,
            'title' => $request->input('title'),
            'is_sticky' => $request->boolean('is_sticky') && $membership->isLeader(),
        ]);

        TribeForumPost::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => $request->input('body'),
        ]);

        return redirect()->route('game.tribe.thread', $thread->id)->with('success', 'Thread created.');
    }

    /**
     * Reply to thread.
     */
    public function storePost(Request $request, int $threadId): RedirectResponse
    {
        $user = Auth::user();
        $membership = $user->tribeMember;
        if (! $membership) {
            return redirect()->route('game.tribe.index');
        }

        $thread = TribeForumThread::where('id', $threadId)->where('tribe_id', $membership->tribe_id)->first();
        if (! $thread) {
            return back()->with('error', 'Thread not found.');
        }

        if ($thread->is_locked) {
            return back()->with('error', 'This thread is locked.');
        }

        $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        TribeForumPost::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => $request->input('body'),
        ]);

        $thread->touch();

        return back()->with('success', 'Reply posted.');
    }

    /**
     * Leave Tribe.
     */
    public function leave(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $membership = $user->tribeMember;
        if (! $membership) {
            return redirect()->route('game.tribe.index');
        }

        $tribe = $membership->tribe;
        $membership->delete();

        if ($tribe->members()->count() === 0) {
            $tribe->delete();
        } else {
            $tribe->recalculatePoints();
        }

        return redirect()->route('game.tribe.index')->with('success', 'You have left the tribe.');
    }
}
