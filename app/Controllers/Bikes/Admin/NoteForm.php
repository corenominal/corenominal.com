<?php

namespace App\Controllers\Bikes\Admin;

use App\Controllers\BaseController;
use App\Models\BikeModel;
use App\Models\BikeNoteMediaModel;
use App\Models\BikeNoteModel;

class NoteForm extends BaseController
{
    public function create(int $bikeId): mixed
    {
        $bike = (new BikeModel())->find($bikeId);

        if (! $bike) {
            return redirect()->to(site_url('admin/bikes'))->with('error', 'Bike not found.');
        }

        return view('bikes/admin/note_form', [
            'title'            => 'Add Note',
            'js'               => ['bikes/admin/note-form'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'action'           => 'create',
            'bike'             => $bike,
            'note'             => null,
            'media'            => [],
        ]);
    }

    public function edit(int $bikeId, int $noteId): mixed
    {
        $bike = (new BikeModel())->find($bikeId);

        if (! $bike) {
            return redirect()->to(site_url('admin/bikes'))->with('error', 'Bike not found.');
        }

        $note = (new BikeNoteModel())->where('bike_id', $bikeId)->find($noteId);

        if (! $note) {
            return redirect()->to(site_url('admin/bikes/' . $bikeId . '/edit'))->with('error', 'Note not found.');
        }

        $media = (new BikeNoteMediaModel())
            ->where('bike_note_id', $noteId)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        return view('bikes/admin/note_form', [
            'title'            => 'Edit Note',
            'js'               => ['bikes/admin/note-form'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'action'           => 'edit',
            'bike'             => $bike,
            'note'             => $note,
            'media'            => $media,
        ]);
    }
}
