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

        $example2 = [
            'Guru Tatea Bulan',
            'male',
            'Sirait',
            'Si Raja Batak',
            'Samosir',
            '1230',
            '1310',
            'Anak sulung Raja Batak',
            'Boru Sihombing|Boru Tobing',
            'Sihombing|Tobing',
        ];

        // Semicolon (;) is the standard list delimiter for Excel in Indonesia & Europe
        // With UTF-8 BOM, it opens cleanly with separate columns A to J in Excel.
        $csv  = "\xEF\xBB\xBF";
        $csv .= implode(';', $headers) . "\n";
        $csv .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $example)) . "\n";
        $csv .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $example2)) . "\n";

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
            'file' => 'required|file|max:5120', // max 5MB
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
            $key               = $this->nodeKey($n->parent_id, $n->name);
            $existingMap[$key] = $n;
        }

        // Also build name -> node lookup for resolving parent_name from DB
        $nameToNode = [];
        foreach ($existingNodes as $n) {
            $nameToNode[mb_strtolower(trim($n->name))] = $n;
        }

        // In-batch lookup to support parent-child relationships defined within the same file
        $batchNodeMap = []; // name_lower => ['level' => int, 'row' => int]

        $results = [
            'to_insert' => [],
            'skipped'   => [],
            'errors'    => [],
            'total'     => count($rows),
        ];

        foreach ($rows as $i => $row) {
            $rowNum     = $i + 2; // +2 because row 1 = header
            $name       = trim($row['name'] ?? '');
            $rawGender  = trim($row['gender'] ?? 'male');
            $gender     = $this->normalizeGender($rawGender);
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
                    'message' => "Gender \"$rawGender\" tidak valid. Gunakan \"male\" atau \"female\".",
                    'data'    => $row,
                ];
                continue;
            }

            // Resolve parent
            $parentId    = null;
            $parentLevel = -1;
            $parentFound = false;

            if (empty($parentName)) {
                // Root node
                $parentFound = true;
                $parentLevel = -1;
            } else {
                $parentKey = mb_strtolower($parentName);

                // 1. Check in existing database nodes
                if (isset($nameToNode[$parentKey])) {
                    $pNode       = $nameToNode[$parentKey];
                    $parentId    = $pNode->id;
                    $parentLevel = $pNode->level ?? 0;
                    $parentFound = true;
                }
                // 2. Check in previously processed batch nodes in this same file
                elseif (isset($batchNodeMap[$parentKey])) {
                    $parentLevel = $batchNodeMap[$parentKey]['level'];
                    $parentFound = true;
                } else {
                    $results['errors'][] = [
                        'row'     => $rowNum,
                        'message' => "Parent \"$parentName\" tidak ditemukan di database maupun di baris sebelumnya dalam file.",
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

            $level = $parentLevel + 1;

            // Register in batch map for subsequent children in the same file
            $nameKey = mb_strtolower($name);
            $batchNodeMap[$nameKey] = [
                'level' => $level,
                'row'   => $rowNum,
            ];

            $results['to_insert'][] = [
                'row'           => $rowNum,
                'name'          => $name,
                'gender'        => $gender,
                'marga'         => trim($row['marga'] ?? '') ?: null,
                'parent_id'     => $parentId,
                'parent_name'   => $parentName ?: '— (Root)',
                'level'         => $level,
                'asal_daerah'   => trim($row['asal_daerah'] ?? '') ?: null,
                'tahun_lahir'   => trim($row['tahun_lahir'] ?? '') ?: null,
                'tahun_wafat'   => trim($row['tahun_wafat'] ?? '') ?: null,
                'deskripsi'     => trim($row['deskripsi'] ?? '') ?: null,
                'spouse_names'  => trim($row['spouse_names'] ?? '') ?: null,
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
            'rows'                 => 'required|array|min:1',
            'rows.*.name'          => 'required|string|max:255',
            'rows.*.gender'        => 'required|in:male,female',
            'rows.*.marga'         => 'nullable|string|max:100',
            'rows.*.parent_id'     => 'nullable|integer',
            'rows.*.parent_name'   => 'nullable|string',
            'rows.*.level'         => 'required|integer|min:0',
            'rows.*.asal_daerah'   => 'nullable|string|max:255',
            'rows.*.tahun_lahir'   => 'nullable|string|max:10',
            'rows.*.tahun_wafat'   => 'nullable|string|max:10',
            'rows.*.deskripsi'     => 'nullable|string|max:1000',
            'rows.*.spouse_names'  => 'nullable|string',
            'rows.*.spouse_margas' => 'nullable|string',
        ]);

        $rows     = $request->input('rows');
        $inserted = 0;
        $failed   = [];

        DB::transaction(function () use ($rows, &$inserted, &$failed) {
            // Keep track of newly created nodes in this transaction: name_lower => id
            $createdMap = [];

            foreach ($rows as $row) {
                try {
                    $parentId = $row['parent_id'] ?? null;
                    $pName    = trim($row['parent_name'] ?? '');

                    // If parent_id is missing/null but parent_name exists, try to resolve from newly created nodes
                    if (!$parentId && !empty($pName) && $pName !== '— (Root)') {
                        $pKey = mb_strtolower($pName);
                        if (isset($createdMap[$pKey])) {
                            $parentId = $createdMap[$pKey];
                        } else {
                            $dbParent = Node::whereRaw('LOWER(name) = ?', [$pKey])->first();
                            if ($dbParent) {
                                $parentId = $dbParent->id;
                            }
                        }
                    }

                    $siblingCount = Node::where('parent_id', $parentId)->count();

                    $node = Node::create([
                        'parent_id'   => $parentId,
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

                    // Store in createdMap for child nodes following in this batch
                    $createdMap[mb_strtolower(trim($node->name))] = $node->id;

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

    private function normalizeGender(string $gender): string
    {
        $g = strtolower(trim($gender));
        if (in_array($g, ['male', 'm', 'l', 'laki-laki', 'pria', 'laki'])) {
            return 'male';
        }
        if (in_array($g, ['female', 'f', 'p', 'perempuan', 'wanita'])) {
            return 'female';
        }
        return $g;
    }

    private function sanitizeHeaderKey(string $key): string
    {
        // 1. Remove UTF-8 BOM, ANSI BOM representations, and non-printable chars
        $clean = str_replace(["\xEF\xBB\xBF", "ï»¿", "\u{FEFF}"], '', $key);
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);
        $clean = strtolower(trim($clean));

        // 2. Map common aliases
        $aliases = [
            'nama'            => 'name',
            'tokoh'           => 'name',
            'jenis_kelamin'   => 'gender',
            'kelamin'         => 'gender',
            'jk'              => 'gender',
            'sex'             => 'gender',
            'parent'          => 'parent_name',
            'ayah'            => 'parent_name',
            'orang_tua'       => 'parent_name',
            'orangtua'        => 'parent_name',
            'nama_parent'     => 'parent_name',
            'asal'            => 'asal_daerah',
            'daerah'          => 'asal_daerah',
            'lokasi'          => 'asal_daerah',
            'lahir'           => 'tahun_lahir',
            'birth_year'      => 'tahun_lahir',
            'tahun_kelahiran' => 'tahun_lahir',
            'wafat'           => 'tahun_wafat',
            'meninggal'       => 'tahun_wafat',
            'death_year'      => 'tahun_wafat',
            'tahun_kematian'  => 'tahun_wafat',
            'keterangan'      => 'deskripsi',
            'catatan'         => 'deskripsi',
            'description'     => 'deskripsi',
            'spouse_name'     => 'spouse_names',
            'istri'           => 'spouse_names',
            'pasangan'        => 'spouse_names',
            'nama_istri'      => 'spouse_names',
            'spouse_marga'    => 'spouse_margas',
            'marga_istri'     => 'spouse_margas',
            'marga_pasangan'  => 'spouse_margas',
        ];

        return $aliases[$clean] ?? $clean;
    }

    private function parseCsv(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) throw new \Exception('Tidak bisa membaca file CSV.');

        // 1. Strip BOM at beginning of file if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = preg_replace('/^ï»¿/', '', $content);

        // 2. Normalize line breaks
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
        if (empty($lines)) return [];

        // 3. Skip sep= directive if present on first line
        if (isset($lines[0]) && str_starts_with(trim($lines[0]), 'sep=')) {
            $sepLine   = array_shift($lines);
            $forcedSep = trim(substr($sepLine, 4));
        }

        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
        if (empty($lines)) return [];

        // 4. Auto-detect delimiter
        if (!empty($forcedSep)) {
            $delimiter = $forcedSep;
        } else {
            $sample         = implode("\n", array_slice($lines, 0, 3));
            $commaCount     = substr_count($sample, ',');
            $semicolonCount = substr_count($sample, ';');
            $tabCount       = substr_count($sample, "\t");

            if ($semicolonCount >= $commaCount && $semicolonCount > $tabCount) {
                $delimiter = ';';
            } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
                $delimiter = "\t";
            } else {
                $delimiter = ',';
            }
        }

        // 5. Parse CSV rows
        $rawRows = array_map(fn($l) => str_getcsv($l, $delimiter), $lines);
        if (empty($rawRows)) return [];

        $rawHeader = array_shift($rawRows);

        // Fallback: If rawHeader has only 1 column, but contains commas/semicolons (single-column Excel anomaly)
        if (count($rawHeader) === 1 && !empty($rawHeader[0])) {
            $singleHeader = $rawHeader[0];
            $innerDelim   = substr_count($singleHeader, ';') > substr_count($singleHeader, ',') ? ';' : ',';
            if (substr_count($singleHeader, $innerDelim) > 0) {
                $rawHeader = str_getcsv($singleHeader, $innerDelim);
                $newRows   = [];
                foreach ($lines as $idx => $line) {
                    if ($idx === 0) continue; // skip header line
                    $cleanLine = trim($line);
                    if (str_starts_with($cleanLine, '"') && str_ends_with($cleanLine, '"')) {
                        $cleanLine = substr($cleanLine, 1, -1);
                        $cleanLine = str_replace('""', '"', $cleanLine);
                    }
                    $newRows[] = str_getcsv($cleanLine, $innerDelim);
                }
                $rawRows = $newRows;
            }
        }

        $header = array_map(fn($col) => $this->sanitizeHeaderKey($col), $rawHeader);

        $rows = [];
        foreach ($rawRows as $line) {
            if (empty($line) || count(array_filter($line)) === 0) continue;

            // Pad row if it has fewer columns than header
            while (count($line) < count($header)) {
                $line[] = '';
            }

            $row    = array_combine($header, array_slice($line, 0, count($header)));
            $rows[] = $row;
        }

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

        // Normalize keys in JSON objects as well
        $normalized = [];
        foreach ($data as $item) {
            if (!is_array($item)) continue;
            $cleanItem = [];
            foreach ($item as $k => $v) {
                $cleanKey = $this->sanitizeHeaderKey((string)$k);
                $cleanItem[$cleanKey] = is_string($v) ? trim($v) : $v;
            }
            $normalized[] = $cleanItem;
        }

        return $normalized;
    }
}
