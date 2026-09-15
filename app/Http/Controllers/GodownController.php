<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGodownRequest;
use App\Http\Requests\UpdateGodownRequest;
use App\Models\Godown;
use Illuminate\Http\Request;

class GodownController extends Controller
{
    public function index()
    {
        $godowns = Godown::all();

        return view('godowns.index', compact('godowns'));
    }

    public function create()
    {
        return view('godowns.create');
    }

    public function store(StoreGodownRequest $request)
    {
        Godown::create($request->validated());
        return redirect()->route('godowns.index')->with('success', 'Godown created successfully.');
    }

    public function edit(Godown $godown)
    {
        return view('godowns.edit', compact('godown'));
    }

    public function update(UpdateGodownRequest $request, Godown $godown)
    {
        $godown->update($request->validated());
        return redirect()->route('godowns.index')->with('success', 'Godown updated successfully.');
    }
}
