<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use Illuminate\Http\Request;

class ClassRoomController extends Controller
{
    public function index()
    {
        $classes = ClassRoom::orderBy('name')->paginate(20);
        return view('classes.index', compact('classes'));
    }

    public function create()
    {
        return view('classes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        ClassRoom::create($data);
        return redirect()->route('classes.index')->with('status', 'Class added.');
    }

    public function edit(ClassRoom $class)
    {
        return view('classes.edit', compact('class'));
    }

    public function update(Request $request, ClassRoom $class)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $class->update($data);
        return redirect()->route('classes.index')->with('status', 'Class updated.');
    }

    public function destroy(ClassRoom $class)
    {
        $class->delete();
        return back()->with('status', 'Class deleted.');
    }
}
