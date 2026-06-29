<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Keyword;
use App\Models\Website;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
    public function index(Request $request)
    {
        $keywords = Keyword::with(['creator', 'latestRanking', 'website'])
            ->when($request->q, fn($q, $term) =>
                $q->where('keyword', 'like', "%{$term}%")
                  ->orWhere('target_url', 'like', "%{$term}%")
            )
            ->when($request->website_id, fn($q, $id) =>
                $q->where('website_id', $id)
            )
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $websites = Website::where('is_active', true)->get();

        return view('keywords.index', compact('keywords', 'websites'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'keyword'          => ['required', 'string', 'max:255'],
            'target_url'       => ['required', 'string', 'max:500'],
            'website_id'       => ['nullable', 'exists:websites,id'],
            'monthly_searches' => ['nullable', 'integer', 'min:0'],
            'semrush_volume'   => ['nullable', 'integer', 'min:0'],
            'kd'               => ['nullable', 'integer', 'min:0', 'max:100'],
            'competition'      => ['nullable', 'in:Low,Medium,High'],
            'intent'           => ['nullable', 'array'],
            'intent.*'         => ['in:I,T,N,C'],
            'currency'         => ['nullable', 'string', 'max:10'],
        ]);

        $data['intent']     = implode(',', array_filter($data['intent'] ?? [])) ?: null;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $keyword = Keyword::create($data);

        ActivityLog::record(
            userId:      auth()->id(),
            action:      'keyword.added',
            modelType:   'Keyword',
            modelId:     $keyword->id,
            description: "Added keyword: \"{$keyword->keyword}\"",
            newValues:   $keyword->only(['keyword', 'target_url', 'monthly_searches', 'kd'])
        );

        return back()->with('success', "Keyword \"{$keyword->keyword}\" added successfully.");
    }

    public function update(Request $request, Keyword $keyword)
    {
        $data = $request->validate([
            'keyword'          => ['sometimes', 'required', 'string', 'max:255'],
            'target_url'       => ['sometimes', 'required', 'string', 'max:500'],
            'monthly_searches' => ['nullable', 'integer', 'min:0'],
            'semrush_volume'   => ['nullable', 'integer', 'min:0'],
            'kd'               => ['nullable', 'integer', 'min:0', 'max:100'],
            'competition'      => ['nullable', 'in:Low,Medium,High'],
            'intent'           => ['nullable', 'array'],
            'intent.*'         => ['in:I,T,N,C'],
        ]);

        $data['intent'] = implode(',', array_filter($data['intent'] ?? [])) ?: null;

        $old = $keyword->only(array_keys($data));
        $data['updated_by'] = auth()->id();

        $keyword->update($data);

        // Detect if URL was changed specifically
        $action = isset($data['target_url']) && $old['target_url'] !== $data['target_url']
            ? 'url.updated'
            : 'keyword.updated';

        ActivityLog::record(
            userId:      auth()->id(),
            action:      $action,
            modelType:   'Keyword',
            modelId:     $keyword->id,
            description: "Updated keyword: \"{$keyword->keyword}\"",
            oldValues:   $old,
            newValues:   $keyword->fresh()->only(array_keys($data))
        );

        return back()->with('success', "Keyword \"{$keyword->keyword}\" updated.");
    }

    public function destroy(Keyword $keyword)
    {
        $name = $keyword->keyword;

        ActivityLog::record(
            userId:      auth()->id(),
            action:      'keyword.deleted',
            modelType:   'Keyword',
            modelId:     $keyword->id,
            description: "Deleted keyword: \"{$name}\"",
            oldValues:   $keyword->only(['keyword', 'target_url'])
        );

        $keyword->delete();

        return back()->with('success', "Keyword \"{$name}\" deleted.");
    }
}
