<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Stream;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    public function index()
    {
        $streams = Stream::with('classRoom')->orderBy('class_id')->orderBy('name')->paginate(20);
        return view('streams.index', compact('streams'));
    }

    public function create()
    {
        $classes = ClassRoom::orderBy('name')->get();
        return view('streams.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:50'],
        ]);
        Stream::create($data);
        return redirect()->route('streams.index')->with('status', 'Stream added.');
    }

    public function edit(Stream $stream)
    {
        $classes = ClassRoom::orderBy('name')->get();
        return view('streams.edit', compact('stream', 'classes'));
    }

    public function update(Request $request, Stream $stream)
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:50'],
        ]);
        $stream->update($data);
        return redirect()->route('streams.index')->with('status', 'Stream updated.');
    }

    public function destroy(Stream $stream)
    {
        $stream->delete();
        return back()->with('status', 'Stream deleted.');
    }
}
