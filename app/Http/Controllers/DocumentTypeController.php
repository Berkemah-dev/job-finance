<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 80);
        $types = DocumentType::when($search, fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('master.document-types.index', compact('types', 'search'));
    }

    public function create()
    {
        return view('master.document-types.form', ['type' => new DocumentType, 'categories' => $this->categories()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:20|unique:document_types,code',
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:general,shipment,finance,compliance',
            'description' => 'nullable|string|max:500',
            'is_required' => 'boolean',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer|min:0|max:999',
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_required'] = $request->boolean('is_required');
        $data['is_active']   = $request->boolean('is_active', true);

        DocumentType::create($data);

        return redirect()->route('document-types.index')->with('success', 'Tipe dokumen berhasil ditambahkan.');
    }

    public function edit(DocumentType $documentType)
    {
        return view('master.document-types.form', ['type' => $documentType, 'categories' => $this->categories()]);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:20|unique:document_types,code,'.$documentType->id,
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:general,shipment,finance,compliance',
            'description' => 'nullable|string|max:500',
            'is_required' => 'boolean',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer|min:0|max:999',
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_required'] = $request->boolean('is_required');
        $data['is_active']   = $request->boolean('is_active');

        $documentType->update($data);

        return redirect()->route('document-types.index')->with('success', 'Tipe dokumen berhasil diperbarui.');
    }

    public function destroy(DocumentType $documentType)
    {
        if ($documentType->jobDocuments()->exists()) {
            return back()->with('error', 'Tipe dokumen ini sudah digunakan. Nonaktifkan saja jika tidak ingin digunakan lagi.');
        }

        $documentType->delete();

        return redirect()->route('document-types.index')->with('success', 'Tipe dokumen berhasil dihapus.');
    }

    private function categories(): array
    {
        return [
            'general'    => 'Umum',
            'shipment'   => 'Pengiriman',
            'finance'    => 'Keuangan',
            'compliance' => 'Kepatuhan',
        ];
    }
}
