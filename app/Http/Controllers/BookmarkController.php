<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $antiHabits = $request->user()
            ->bookmarkedAntiHabits()
            ->publiclyVisible()
            ->withAssociations()
            ->orderByDesc('bookmarks.created_at')
            ->paginate(AntiHabitController::PER_PAGE);

        return view('bookmarks.index', ['antiHabits' => $antiHabits]);
    }
}
