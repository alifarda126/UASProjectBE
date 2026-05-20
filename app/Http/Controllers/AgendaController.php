<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AnggotaOrganisasi;
use App\Models\Organisasi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * AgendaController — CRUD agenda/jadwal kegiatan organisasi.
 */
class AgendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user         = $request->user();
        $organisasiId = $request->get('organisasi_id');
        $organisasi   = $this->getOrganisasi($user, $organisasiId);

        if (!$organisasi) return response()->json(['data' => []]);

        $query = $organisasi->agendas()->with('creator:id,name');

        if ($request->has('status')) $query->where('status', $request->status);
        if ($request->has('type'))   $query->where('type', $request->type);

        $agendas = $query->orderBy('start_at')->get()->map(fn($a) => $this->format($a));
        return response()->json(['data' => $agendas]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organisasi_id' => 'required|exists:organisasi,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'location'      => 'nullable|string|max:255',
            'start_at'      => 'required|date',
            'end_at'        => 'nullable|date|after:start_at',
            'type'          => 'required|in:rapat,workshop,gathering,seminar,lainnya',
        ]);

        $agenda = Agenda::create([...$validated, 'user_id' => $request->user()->id, 'status' => 'upcoming']);
        return response()->json(['message' => 'Agenda berhasil dibuat', 'data' => $this->format($agenda)], 201);
    }

    public function show(Agenda $agenda): JsonResponse
    {
        return response()->json(['data' => $this->format($agenda->load('creator:id,name'))]);
    }

    public function update(Request $request, Agenda $agenda): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location'    => 'nullable|string|max:255',
            'start_at'    => 'sometimes|date',
            'end_at'      => 'nullable|date',
            'type'        => 'sometimes|in:rapat,workshop,gathering,seminar,lainnya',
            'status'      => 'sometimes|in:upcoming,ongoing,done,cancelled',
        ]);

        $agenda->update($validated);
        return response()->json(['message' => 'Agenda berhasil diupdate', 'data' => $this->format($agenda)]);
    }

    public function destroy(Agenda $agenda): JsonResponse
    {
        $agenda->delete();
        return response()->json(['message' => 'Agenda berhasil dihapus']);
    }

    private function format(Agenda $a): array
    {
        return [
            'id'          => $a->id,
            'title'       => $a->title,
            'description' => $a->description,
            'location'    => $a->location,
            'start_at'    => $a->start_at?->toISOString(),
            'end_at'      => $a->end_at?->toISOString(),
            'type'        => $a->type,
            'status'      => $a->status,
            'creator'     => $a->creator ? ['id' => $a->creator->id, 'name' => $a->creator->name] : null,
        ];
    }

    private function getOrganisasi($user, ?int $id): ?Organisasi
    {
        if ($user->isAdmin() && $id) return Organisasi::find($id);
        if ($id) return $user->organisasi()->find($id);
        return $user->isAdmin() ? Organisasi::first() : $user->organisasi()->first();
    }
}
