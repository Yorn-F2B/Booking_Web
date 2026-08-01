<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannedWord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannedWordController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.banned-words.index', [
            'words' => BannedWord::query()->orderBy('word')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.banned-words.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'words' => ['required', 'string', 'max:5000'],
        ], ['words.required' => 'Vui lòng nhập ít nhất một từ cấm.']);

        $words = preg_split('/[\r\n,;]+/u', $data['words']);
        $added = 0;
        foreach ($words as $word) {
            $word = trim(mb_strtolower($word, 'UTF-8'));
            if ($word === '') continue;
            BannedWord::firstOrCreate(['word' => $word], ['created_by' => auth()->id()]);
            $added++;
        }

        return redirect()->route('admin.banned-words.index')
            ->with('success', 'Đã lưu danh sách từ cấm.');
    }

    public function destroy(BannedWord $bannedWord): RedirectResponse
    {
        $bannedWord->delete();
        return back()->with('success', 'Đã xóa từ cấm ngay lập tức.');
    }
}
