<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\NodeSpouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    /**
     * Download CSV template.
     */
    public function downloadTemplate()
    {
        $headers = [
            'name',
            'gender',
            'marga',
            'parent_name',
            'asal_daerah',
            'tahun_lahir',
            'tahun_wafat',
            'deskripsi',
            'spouse_names',      // Pisahkan dengan | contoh: Boru Sihombing|Boru Tobing
            'spouse_margas',     // Pisahkan dengan | sesuai urutan spouse_names
        ];

        $example = [
            'Si Raja Batak',
            'male',
            '',
            '',                  // parent_name kosong = root
            'Pusuk Buhit, Samosir',
            '1200',
            '1280',
            'Leluhur marga Batak',
            'Boru Sitorus',
            '',
        ];

        $csv  = "\xEF\xBB\xBF";   // UTF-8 BOM di awal (wajib paling pertama)
        $csv .= "sep=,\n";          // Excel hint: pakai koma sebagai delimiter kolom
        $csv .= implode(',', $headers) . "\n";
        $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $example)) . "\n";
        // Contoh baris ke-2: tokoh dengan 2 istri
        $example2 = [
            'Guru Tatea Bulan', 'male', 'Sirait', 'Si Raja Batak',
            'Samosir', '1230', '1310', 'Anak sulung Raja Batak',
            'Boru Sihombing|Boru Tobing', 'Sihombing|Tobing',
        ];
        $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $example2)) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-import-tarombo.csv"',
        ]);
    }

    /**
     * Preview CSV or JSON import without saving.
     * Returns analysis: rows to be inserted, skipped, and errors.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,json|max:5120', // max 5MB
        ]);

        $file      = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $rows = $extension === 'json'
                ? $this->parseJson($file->getPathname())
                : $this->parseCsv($file->getPathname());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membaca file: ' . $e->getMessage()], 422);
        }

        if (empty($rows)) {
            return response()->json(['error' => 'File kosong atau tidak ada baris data yang valid.'], 422);
        }

        // Load all existing nodes (id, name, parent_id, level) for fast lookup
        $existingNodes = Node::select('id', 'name', 'parent_id', 'level')->get();
        $existingMap   = [];
        foreach ($existingNodes as $n) {
            $key                 = $this->nodeKey($n->parent_id, $n->name);
            $existingMap[$key]   = $n;
        }

        // Also build name -> id lookup for resolving parent_name
        $nameToId = [];
        foreach ($existingNodes as $n) {
            // Last one wins — ambiguous names will be noted
            $nameToId[mb_strtolower(trim($n->name))] = $n->id;
        }

        $results = [
            'to_insert' => [],
            'skipped'   => [],
            'errors'    => [],
            'total'     => count($rows),
        ];

        foreach ($rows as $i => $row) {
            $rowNum   = $i + 2; // +2 because row 1 = header
            $name     = trim($row['name'] ?? '');
            $gender   = strtolower(trim($row['gender'] ?? 'male'));
            $parentName = trim($row['parent_name'] ?? '');

            // Required field check
            if (empty($name)) {
                $results['errors'][] = [
                    'row'     => $rowNum,
                    'message' => 'Kolom "name" wajib diisi.',
                    'data'    => $row,
                ];
                continue;
            }

            if (!in_array($gender, ['male', 'female'])) {
                $results['errors'][] = [
                    'row'     => $rowNum,
                    'message' => "Gender \"$gender\" tidak valid. Gunakan \"male\" atau \"female\".",
                    'data'    => $row,
                ];
                continue;
            }

            // Resolve parent_id
            $parentId    = null;
            $parentLevel = -1;
            if (!empty($parentName)) {
                $parentKey = mb_strtolower(trim($parentName));
                if (isset($nameToId[$parentKey])) {
                    $parentId    = $nameToId[$parentKey];
                    $parentLevel = $existingMap[$this->nodeKey(
                        $existingNodes->firstWhere('id', $parentId)?->parent_id,
                        $parentName
                    )]?->level ?? ($existingNodes->firstWhere('id', $parentId)?->level ?? 0);
                } else {
                    $results['errors'][] = [
                        'row'     => $rowNum,
                        'message' => "Parent \"$parentName\" tidak ditemukan di database. Pastikan parent sudah ada atau diimport terlebih dahulu.",
                        'data'    => $row,
                    ];
                    continue;
                }
            }

            // Duplicate check: same name under same parent
            $key = $this->nodeKey($parentId, $name);
            if (isset($existingMap[$key])) {
                $results['skipped'][] = [
                    'row'    => $rowNum,
                    'name'   => $name,
                    'reason' => "Sudah ada node \"$name\" di bawah parent yang sama (akan dilewati).",
                    'data'   => $row,
                ];
                continue;
            }

            $level = $parentId !== null ? $parentLevel + 1 : 0;

            $results['to_insert'][] = [
                'row'          => $rowNum,
                'name'         => $name,
                'gender'       => $gender,
                'marga'        => trim($row['marga'] ?? '') ?: null,
                'parent_id'    => $parentId,
                'parent_name'  => $parentName ?: '— (Root)',
                'level'        => $level,
                'asal_daerah'  => trim($row['asal_daerah'] ?? '') ?: null,
                'tahun_lahir'  => trim($row['tahun_lahir'] ?? '') ?: null,
                'tahun_wafat'  => trim($row['tahun_wafat'] ?? '') ?: null,
                'deskripsi'    => trim($row['deskripsi'] ?? '') ?: null,
                'spouse_names' => trim($row['spouse_names'] ?? '') ?: null,
                'spouse_margas' => trim($row['spouse_margas'] ?? '') ?: null,
            ];
        }

        return response()->json($results);
    }

    /**
     * Confirm and execute the actual import.
     * Receives already-previewed row data from frontend.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'rows'   => 'required|array|min:1',
            'rows.*.name'         => 'required|string|max:255',
            'rows.*.gender'       => 'required|in:male,female',
            'rows.*.marga'        => 'nullable|string|max:100',
            'rows.*.parent_id'    => 'nullable|integer|exists:nodes,id',
            'rows.*.level'        => 'required|integer|min:0',
            'rows.*.asal_daerah'  => 'nullable|string|max:255',
            'rows.*.tahun_lahir'  => 'nullable|string|max:10',
            'rows.*.tahun_wafat'  => 'nullable|string|max:10',
            'rows.*.deskripsi'    => 'nullable|string|max:1000',
            'rows.*.spouse_names' => 'nullable|string',
            'rows.*.spouse_margas' => 'nullable|string',
        ]);

        $rows      = $request->input('rows');
        $inserted  = 0;
        $failed    = [];

        DB::transaction(function () use ($rows, &$inserted, &$failed) {
            foreach ($rows as $row) {
                try {
                    $siblingCount = Node::where('parent_id', $row['parent_id'] ?? null)->count();

                    $node = Node::create([
                        'parent_id'   => $row['parent_id'] ?? null,
                        'name'        => $row['name'],
                        'gender'      => $row['gender'],
                        'marga'       => $row['marga'] ?? null,
                        'asal_daerah' => $row['asal_daerah'] ?? null,
                        'tahun_lahir' => $row['tahun_lahir'] ?? null,
                        'tahun_wafat' => $row['tahun_wafat'] ?? null,
                        'deskripsi'   => $row['deskripsi'] ?? null,
                        'status'      => 'pending',
                        'level'       => $row['level'],
                        'sort_order'  => $siblingCount + 1,
                    ]);

                    // Insert spouses if male and provided
                    if ($node->gender === 'male' && !empty($row['spouse_names'])) {
                        $spouseNames  = array_filter(array_map('trim', explode('|', $row['spouse_names'])));
                        $spouseMargas = array_filter(array_map('trim', explode('|', $row['spouse_margas'] ?? '')));

                        foreach ($spouseNames as $idx => $spouseName) {
                            if (!empty($spouseName)) {
                                NodeSpouse::create([
                                    'node_id' => $node->id,
                                    'name'    => $spouseName,
                                    'marga'   => $spouseMargas[$idx] ?? null,
                                ]);
                            }
                        }
                    }

                    $inserted++;
                } catch (\Exception $e) {
                    $failed[] = [
                        'name'    => $row['name'],
                        'message' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'inserted' => $inserted,
            'failed'   => $failed,
            'message'  => "$inserted node berhasil diimport dengan status Pending (menunggu ACC admin).",
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function nodeKey(?int $parentId, string $name): string
    {
        return ($parentId ?? 'null') . '::' . mb_strtolower(trim($name));
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) throw new \Exception('Tidak bisa membaca file CSV.');

        // 1. Detect and strip UTF-8 BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // 2. Detect delimiter: peek the first non-empty line
        $peekPos = ftell($handle);
        $peekLine = fgets($handle);
        fseek($handle, $peekPos);

        // If the first line is Excel's sep= directive, skip it and re-peek
        if ($peekLine !== false && str_starts_with(trim($peekLine), 'sep=')) {
            fgets($handle); // consume sep= line
            $peekPos  = ftell($handle);
            $peekLine = fgets($handle);
            fseek($handle, $peekPos);
        }

        // Auto-detect: count commas vs semicolons in the header line
        $commaCount     = substr_count($peekLine ?? '', ',');
        $semicolonCount = substr_count($peekLine ?? '', ';');
        $delimiter      = $semicolonCount > $commaCount ? ';' : ',';

        $header = null;
        $rows   = [];

        while (($line = fgetcsv($handle, 4096, $delimiter)) !== false) {
            if ($header === null) {
                $header = array_map('trim', $line);
                continue;
            }
            if (count(array_filter($line)) === 0) continue; // skip empty rows

            // Pad if row has fewer columns than header
            while (count($line) < count($header)) {
                $line[] = '';
            }

            $row = array_combine($header, array_slice($line, 0, count($header)));
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function parseJson(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) throw new \Exception('Tidak bisa membaca file JSON.');

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Format JSON tidak valid: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new \Exception('JSON harus berupa array of objects ([ {...}, {...} ])');
        }

        // Accept flat array or array under key "nodes"
        if (isset($data['nodes']) && is_array($data['nodes'])) {
            $data = $data['nodes'];
        }

        return $data;
    }
}
