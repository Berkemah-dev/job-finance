<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Http\Requests\MappingRequest;
use App\Http\Requests\VersionRequest;
use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use App\Services\MasterDataService;
use App\Services\FinancialReportService;
use App\Services\XlsxTableReader;
use App\Models\Port;
use App\Models\ChargeType;
use App\Models\ContainerUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $type = $request->input('type');
        $archived = $request->input('archived') === '1';

        $query = ChartOfAccount::query()
            ->withCount('children')
            ->when($archived, fn ($q) => $q->onlyTrashed())
            ->when(array_key_exists((string) $type, config('accounting.types')), fn ($q) => $q->where('type', $type))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')));

        if ($search || $type || $archived) {
            $accounts = $query->orderBy('code')->paginate(10)->withQueryString();
            $isTree = false;
        } else {
            $allAccounts = ChartOfAccount::withCount('children')->orderBy('code')->get();
            $accounts = $this->buildTreeList($allAccounts);
            $isTree = true;
        }

        return view('accounts.index', compact('accounts', 'search', 'isTree'));
    }

    private function buildTreeList(Collection $all): Collection
    {
        $grouped = $all->groupBy(fn ($a) => (string) ($a->parent_id ?? 'root'));
        $result = collect();

        $traverse = function ($parentId, $currentLevel) use (&$traverse, $grouped, &$result) {
            $children = $grouped->get((string) $parentId, collect());
            foreach ($children as $child) {
                $child->tree_level = $currentLevel;
                $result->push($child);
                $traverse($child->id, $currentLevel + 1);
            }
        };

        // Root accounts have parent_id null
        $traverse('root', 1);

        // Append any accounts whose parent wasn't found in tree
        $pushedIds = $result->pluck('id')->all();
        $orphans = $all->reject(fn ($a) => in_array($a->id, $pushedIds));
        foreach ($orphans as $orphan) {
            $orphan->tree_level = $orphan->level ?? 1;
            $result->push($orphan);
        }

        return $result;
    }

    public function create(Request $request)
    {
        $account = new ChartOfAccount;
        $parent = null;

        if ($request->filled('parent_id')) {
            $parent = ChartOfAccount::find($request->input('parent_id'));
            if ($parent) {
                $account->parent_id = $parent->id;
                $account->type = $parent->type;
            }
        }

        $parents = ChartOfAccount::orderBy('code')->get();

        return view('accounts.form', compact('account', 'parent', 'parents'));
    }

    public function store(AccountRequest $request, MasterDataService $service)
    {
        $service->save(new ChartOfAccount, $request->validated(), $request->user());

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(ChartOfAccount $account)
    {
        $parent = $account->parent;
        $parents = ChartOfAccount::where('id', '!=', $account->id)->orderBy('code')->get();

        return view('accounts.form', compact('account', 'parent', 'parents'));
    }

    public function update(AccountRequest $request, ChartOfAccount $account, MasterDataService $service)
    {
        $service->save($account, $request->validated(), $request->user());

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(VersionRequest $request, ChartOfAccount $account, MasterDataService $service)
    {
        $service->archive($account, $request->validated(), $request->user());

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil diarsipkan.');
    }

    public function mappings()
    {
        return view('accounts.mappings', [
            'accounts' => ChartOfAccount::orderBy('code')->get(),
            'mappings' => AccountMapping::pluck('chart_of_account_id', 'key'),
        ]);
    }

    public function updateMappings(MappingRequest $request, MasterDataService $service)
    {
        $service->mappings($request->validated('mappings'), $request->user());

        return redirect()->route('accounts.mappings')->with('success', 'Mapping akun berhasil disimpan.');
    }

    public function template()
    {
        $sheet = $this->workbook('Template Import COA');
        $sheet->fromArray([
            ['Kode Akun', 'Nama Akun', 'Tipe', 'Kode Induk', 'Kepala Laporan (Referensi)'],
            ['61010', 'Biaya Contoh', 'expense', '61000', '61 - Biaya Perlengkapan Kantor'],
            ['61011', 'Biaya Contoh Anak', 'expense', '61010', '61 - Biaya Perlengkapan Kantor'],
        ], null, 'A1');
        $this->styleWorkbook($sheet, 3);

        $guide = $sheet->getParent()->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->fromArray([
            ['PETUNJUK IMPORT CHART OF ACCOUNTS'],
            ['1. Isi atau ubah data pada sheet "Template Import COA".'],
            ['2. Kolom wajib: Kode Akun, Nama Akun, dan Tipe.'],
            ['3. Tipe yang diperbolehkan: asset, liability, equity, revenue, cogs, expense.'],
            ['4. Kode Induk dikosongkan untuk akun utama. Isi dengan kode akun induk untuk sub akun.'],
            ['5. Kode akun yang sudah ada akan diperbarui. Kode baru akan ditambahkan.'],
            ['6. Import tidak menghapus akun yang tidak tercantum dalam file.'],
        ], null, 'A1');
        $guide->mergeCells('A1:E1');
        $guide->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $guide->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF102A56');
        $guide->getColumnDimension('A')->setWidth(105);
        $guide->getStyle('A1:A7')->getAlignment()->setWrapText(true);

        $reference = $sheet->getParent()->createSheet();
        $reference->setTitle('Referensi Kepala Akun');
        $reference->fromArray([['Kepala', 'Nama Kelompok Laporan', 'Kode Induk untuk Sub Akun']], null, 'A1');
        $row = 2;
        foreach ($this->reportDefinitions() as $definition) {
            $reference->fromArray([[
                $definition['acc_no'],
                $definition['name'],
                implode(', ', $definition['codes']),
            ]], null, 'A'.$row++);
        }
        $this->styleWorkbook($reference, max(2, $row - 1));

        return $this->downloadWorkbook($sheet->getParent(), 'Template-Import-COA.xlsx');
    }

    public function export()
    {
        $accounts = ChartOfAccount::withTrashed()->orderBy('code')->get();
        $accountsById = $accounts->keyBy('id');
        $sheet = $this->workbook('Daftar COA');
        $sheet->fromArray([['Kode Akun', 'Nama Akun', 'Tipe', 'Kode Induk', 'Kepala Laporan', 'Level', 'Status']], null, 'A1');
        foreach ($accounts as $index => $account) {
            $row = $index + 2;
            $sheet->fromArray([[
                $account->code,
                $account->name,
                $account->type,
                $accountsById->get($account->parent_id)?->code ?? '',
                $this->reportHeadFor($account, $accountsById),
                $account->level,
                $account->trashed() ? 'Diarsipkan' : 'Aktif',
            ]], null, 'A'.$row);
        }
        $this->styleWorkbook($sheet, max(2, $accounts->count() + 1));

        return $this->downloadWorkbook($sheet->getParent(), 'Chart-of-Accounts-'.now()->format('Ymd-His').'.xlsx');
    }

    public function import(Request $request, XlsxTableReader $reader, MasterDataService $service)
    {
        Gate::authorize('coa.manage');
        $request->validate(['file' => ['required', 'file', 'max:5120']]);
        $file = $request->file('file');
        if (! in_array(strtolower($file->getClientOriginalExtension()), ['xls', 'xlsx', 'csv'], true)) {
            throw ValidationException::withMessages(['file' => 'Gunakan file .xls, .xlsx, atau .csv.']);
        }

        $rows = $reader->read($file);
        $headerRow = collect($rows)->search(fn (array $row) => in_array('kode akun', array_map(fn ($cell) => $this->header((string) $cell), $row), true));
        if ($headerRow === false) {
            throw ValidationException::withMessages(['file' => 'Header "Kode Akun" tidak ditemukan. Unduh template Excel terlebih dahulu.']);
        }
        $columns = $this->columns($rows[$headerRow]);
        foreach (['code', 'name', 'type'] as $required) {
            if (! array_key_exists($required, $columns)) {
                throw ValidationException::withMessages(['file' => 'Kolom '.$this->label($required).' wajib tersedia.']);
            }
        }

        $incoming = [];
        foreach (array_slice($rows, $headerRow + 1) as $offset => $row) {
            $line = $headerRow + $offset + 2;
            $code = strtoupper(trim((string) $this->cell($row, $columns, 'code')));
            if ($code === '') continue;
            $name = trim((string) $this->cell($row, $columns, 'name'));
            $type = strtolower(trim((string) $this->cell($row, $columns, 'type')));
            $parentCode = strtoupper(trim((string) $this->cell($row, $columns, 'parent_code')));
            if (! preg_match('/^[0-9A-Z.-]{1,20}$/', $code) || $name === '' || mb_strlen($name) > 255 || ! array_key_exists($type, config('accounting.types'))) {
                throw ValidationException::withMessages(['file' => "Baris $line tidak valid. Periksa kode, nama akun, dan tipe."]);
            }
            if (isset($incoming[$code])) {
                throw ValidationException::withMessages(['file' => "Kode akun $code tercantum lebih dari satu kali."]);
            }
            if ($parentCode === $code) {
                throw ValidationException::withMessages(['file' => "Kode induk pada baris $line tidak boleh sama dengan kode akun."]);
            }
            $incoming[$code] = compact('code', 'name', 'type', 'parentCode');
        }
        if ($incoming === []) {
            throw ValidationException::withMessages(['file' => 'Tidak ada data COA yang dapat diimpor.']);
        }

        $created = 0;
        $updated = 0;
        DB::transaction(function () use ($incoming, $request, $service, &$created, &$updated) {
            $accounts = ChartOfAccount::withTrashed()->whereIn('code', array_keys($incoming))->get()->keyBy('code');
            foreach ($incoming as $data) {
                $account = $accounts->get($data['code']);
                if ($account && $account->mappings()->exists() && $account->type !== $data['type']) {
                    throw ValidationException::withMessages(['file' => 'Tipe akun mapping '.$data['code'].' tidak dapat diubah melalui import.']);
                }
                if (! $account) {
                    $account = new ChartOfAccount(['code' => $data['code']]);
                    $account->created_by = $request->user()->id;
                    $account->lock_version = 0;
                    $created++;
                } else {
                    if ($account->trashed()) $account->restore();
                    $account->lock_version++;
                    $updated++;
                }
                $account->name = $data['name'];
                $account->type = $data['type'];
                $account->updated_by = $request->user()->id;
                $account->save();
                $accounts->put($account->code, $account);
            }

            $parents = ChartOfAccount::withTrashed()->get()->keyBy('code');
            foreach ($incoming as $data) {
                $account = $accounts->get($data['code']);
                $parent = $data['parentCode'] !== '' ? $parents->get($data['parentCode']) : null;
                if ($data['parentCode'] !== '' && ! $parent) {
                    throw ValidationException::withMessages(['file' => 'Akun induk '.$data['parentCode'].' untuk '.$data['code'].' tidak ditemukan.']);
                }
                if ($parent && $parent->type !== $account->type) {
                    throw ValidationException::withMessages(['file' => 'Tipe akun '.$data['code'].' harus sama dengan akun induk '.$parent->code.'.']);
                }
                $account->parent_id = $parent?->id;
                $account->level = $parent ? $parent->level + 1 : 1;
                $account->updated_by = $request->user()->id;
                $account->save();
            }
            $service->log($request->user(), 'account.imported', "Import COA: $created baru, $updated diperbarui", ['module' => 'account']);
        }, 3);

        return redirect()->route('accounts.index')->with('success', "Import COA selesai: $created akun baru dan $updated akun diperbarui.");
    }

    private function workbook(string $title): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);

        return $sheet;
    }

    private function styleWorkbook(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $lastRow): void
    {
        $lastColumn = $sheet->getHighestColumn();
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastColumn.$lastRow);
        $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:'.$lastColumn.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF102A56');
        $sheet->getStyle('A1:'.$lastColumn.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach (range('A', $lastColumn) as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    private function downloadWorkbook(Spreadsheet $spreadsheet, string $filename)
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function header(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower(str_replace(['_', '-', '/'], ' ', $value))));
    }

    private function columns(array $headers): array
    {
        $columns = [];
        foreach ($headers as $index => $header) {
            $key = match ($this->header((string) $header)) {
                'kode akun', 'kode' => 'code',
                'nama akun', 'nama' => 'name',
                'tipe', 'type' => 'type',
                'kode induk', 'akun induk', 'parent code' => 'parent_code',
                default => null,
            };
            if ($key) $columns[$key] = $index;
        }
        return $columns;
    }

    private function cell(array $row, array $columns, string $key): string
    {
        return isset($columns[$key]) ? (string) ($row[$columns[$key]] ?? '') : '';
    }

    /** @return array<int, array{acc_no:string, name:string, codes:array}> */
    private function reportDefinitions(): array
    {
        $definitions = [];
        foreach (app(FinancialReportService::class)->radixDefinitions() as $groups) {
            foreach ($groups as $definition) $definitions[] = $definition;
        }

        return $definitions;
    }

    private function reportHeadFor(ChartOfAccount $account, Collection $accountsById): string
    {
        $ancestorCodes = [];
        $current = $account;
        while ($current) {
            $ancestorCodes[] = (string) $current->code;
            $current = $current->parent_id ? $accountsById->get($current->parent_id) : null;
        }
        foreach ($this->reportDefinitions() as $definition) {
            if (array_intersect($ancestorCodes, $definition['codes'])) {
                return $definition['acc_no'].' - '.$definition['name'];
            }
        }

        return '—';
    }

    private function label(string $key): string
    {
        return ['code' => 'Kode Akun', 'name' => 'Nama Akun', 'type' => 'Tipe'][$key] ?? $key;
    }
}
