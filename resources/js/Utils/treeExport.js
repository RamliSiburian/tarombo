import * as d3 from 'd3';

/**
 * Utility for exporting genealogy tree in Visual (PNG/SVG) and Data Table (CSV/PDF) formats.
 */

function escapeXml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

/**
 * Prune a hierarchical tree object so that only nodes matching ancestorIdsSet are kept.
 */
export function pruneTreeToAncestors(node, ancestorIdsSet) {
    if (!node || !ancestorIdsSet || !ancestorIdsSet.has(node.id)) return null;

    const cloned = { ...node };
    if (node.children_recursive && Array.isArray(node.children_recursive)) {
        cloned.children_recursive = node.children_recursive
            .map(child => pruneTreeToAncestors(child, ancestorIdsSet))
            .filter(Boolean);
    } else {
        cloned.children_recursive = [];
    }
    return cloned;
}

/**
 * Flatten tree data for tabular export (CSV & Print PDF).
 */
export function flattenTreeData(node, parentName = '-', depth = 1) {
    if (!node) return [];

    const spousesStr = node.spouses && node.spouses.length > 0
        ? node.spouses.map(s => s.name).join(', ')
        : '-';

    const item = {
        id: node.id,
        level: (node.level !== undefined && node.level !== null) ? node.level + 1 : depth,
        name: node.name || '',
        marga: node.marga || '-',
        gender: node.gender === 'female' ? 'Perempuan' : 'Laki-laki',
        parent_name: parentName,
        spouses: spousesStr,
        asal_daerah: node.asal_daerah || '-',
        tahun_lahir: node.tahun_lahir || '-',
        tahun_wafat: node.tahun_wafat || '-',
    };

    let result = [item];

    if (node.children_recursive && Array.isArray(node.children_recursive)) {
        for (const child of node.children_recursive) {
            result.push(...flattenTreeData(child, node.name, depth + 1));
        }
    }

    return result;
}

/**
 * Generate a standalone, clean, and self-contained SVG string representing the tree.
 * When selectedNode & ancestorIdsSet are provided, it renders ONLY that lineage branch.
 */
export function generateTreeSVG(treeData, selectedNode = null, ancestorIdsSet = null) {
    if (!treeData) return '';

    // Determine target tree data (pruned if lineage scope, full if global)
    let targetTree = treeData;
    const isLineage = selectedNode && ancestorIdsSet && ancestorIdsSet.size > 0;
    
    if (isLineage) {
        const pruned = pruneTreeToAncestors(treeData, ancestorIdsSet);
        if (pruned) {
            targetTree = pruned;
        }
    }

    const NODE_WIDTH = 220;
    const NODE_HEIGHT = 82;
    const H_GAP = 50;
    const V_GAP = 95;

    // Build D3 Hierarchy
    const root = d3.hierarchy(targetTree, d => {
        if (d.gender === 'female') return [];
        return d.children_recursive || [];
    });

    const treeLayout = d3.tree()
        .nodeSize([NODE_WIDTH + H_GAP, NODE_HEIGHT + V_GAP])
        .separation((a, b) => (a.parent === b.parent ? 1.15 : 1.35));

    treeLayout(root);

    const nodes = root.descendants();
    const links = root.links();

    // Compute bounding box
    let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
    nodes.forEach(d => {
        if (d.x < minX) minX = d.x;
        if (d.x > maxX) maxX = d.x;
        if (d.y < minY) minY = d.y;
        if (d.y > maxY) maxY = d.y;
    });

    if (!isFinite(minX)) {
        minX = 0; maxX = 0; minY = 0; maxY = 0;
    }

    const treeWidth = maxX - minX;
    const paddingX = 90;
    const paddingTop = 160; // For Header Banner
    const paddingBottom = 90;

    const totalWidth = Math.max(Math.round(treeWidth + NODE_WIDTH + paddingX * 2), 650);
    const totalHeight = Math.max(Math.round((maxY - minY) + NODE_HEIGHT + paddingTop + paddingBottom), 450);

    const viewBoxX = Math.round(minX - NODE_WIDTH / 2 - paddingX);
    const viewBoxY = Math.round(minY - paddingTop);

    // Group nodes by depth for generation guidelines
    const depthMap = new Map();
    nodes.forEach(d => {
        const depth = d.depth;
        if (!depthMap.has(depth)) {
            const levelNum = (d.data.level !== undefined && d.data.level !== null) ? d.data.level + 1 : depth + 1;
            depthMap.set(depth, { depth, levelNum, y: d.y });
        }
    });
    const depthList = Array.from(depthMap.values()).sort((a, b) => a.depth - b.depth);

    // Header strings
    const titleText = 'TAROMBO BATAK';
    const subtitleText = isLineage
        ? `Garis Silsilah Leluhur: ${selectedNode.name} (${nodes.length} Tingkat Generasi)`
        : `Pohon Silsilah Keluarga (${nodes.length} Tokoh Terdaftar)`;
    const dateText = `Diekspor pada: ${new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`;

    // Build Guidelines SVG
    let guidelinesSvg = '';
    depthList.forEach(item => {
        const lineX1 = viewBoxX + 30;
        const lineX2 = viewBoxX + totalWidth - 30;
        guidelinesSvg += `
            <g class="generation-guide">
                <line x1="${lineX1}" y1="${item.y}" x2="${lineX2}" y2="${item.y}" stroke="#334155" stroke-width="1" stroke-dasharray="4 4" opacity="0.4" />
                <rect x="${lineX1}" y="${item.y - 12}" width="68" height="24" rx="12" fill="#0f172a" stroke="#6366f1" stroke-width="1.2" />
                <text x="${lineX1 + 34}" y="${item.y + 4}" text-anchor="middle" fill="#c7d2fe" font-size="10px" font-weight="700">Gen ${item.levelNum}</text>
            </g>
        `;
    });

    // Build Links SVG
    let linksSvg = '';
    links.forEach(link => {
        const s = link.source;
        const t = link.target;
        const sy = s.y + NODE_HEIGHT / 2;
        const ty = t.y - NODE_HEIGHT / 2;
        const pathData = `M ${s.x} ${sy} C ${s.x} ${(sy + ty) / 2}, ${t.x} ${(sy + ty) / 2}, ${t.x} ${ty}`;
        const strokeColor = isLineage ? '#a78bfa' : '#475569';
        const strokeWidth = isLineage ? '2.5' : '1.5';
        linksSvg += `<path d="${pathData}" fill="none" stroke="${strokeColor}" stroke-width="${strokeWidth}" stroke-linecap="round" opacity="0.85" />`;
    });

    // Build Nodes SVG
    let nodesSvg = '';
    nodes.forEach(d => {
        const isTargetNode = selectedNode && d.data.id === selectedNode.id;
        const isRoot = !d.parent;
        const nodeX = d.x - NODE_WIDTH / 2;
        const nodeY = d.y - NODE_HEIGHT / 2;

        let cardFill = '#0f172a';
        let cardStroke = '#6366f1';
        let strokeWidth = '1.5';
        let strokeDash = 'none';

        if (d.data.status === 'pending') {
            cardFill = '#1e293b';
            cardStroke = '#64748b';
            strokeDash = '4 4';
        } else if (isTargetNode) {
            cardFill = '#2e1065';
            cardStroke = '#f59e0b';
            strokeWidth = '2.5';
        } else if (isRoot) {
            cardFill = '#1e1b4b';
            cardStroke = '#fbbf24';
            strokeWidth = '2';
        } else if (d.data.gender === 'female') {
            cardFill = '#2a0a18';
            cardStroke = '#f472b6';
        }

        const nameSafe = escapeXml(d.data.name || 'Tanpa Nama');
        const margaSafe = d.data.marga ? `Marga ${escapeXml(d.data.marga)}` : '';
        
        let spouseText = '';
        if (d.data.spouses && d.data.spouses.length > 0) {
            const firstSpouse = d.data.spouses[0]?.name || '';
            spouseText = `💍 ${escapeXml(firstSpouse)}${d.data.spouses.length > 1 ? ` (+${d.data.spouses.length - 1})` : ''}`;
        }

        const genderDotColor = d.data.gender === 'female' ? '#ec4899' : (isTargetNode ? '#f59e0b' : '#818cf8');

        nodesSvg += `
            <g class="tree-node" transform="translate(${d.x}, ${d.y})">
                <!-- Card Background -->
                <rect x="${-NODE_WIDTH / 2}" y="${-NODE_HEIGHT / 2}" width="${NODE_WIDTH}" height="${NODE_HEIGHT}" rx="12" ry="12"
                      fill="${cardFill}" stroke="${cardStroke}" stroke-width="${strokeWidth}" stroke-dasharray="${strokeDash}"
                      filter="url(#cardShadow)" />
                
                <!-- Gender Dot -->
                <circle cx="${NODE_WIDTH / 2 - 16}" cy="${-NODE_HEIGHT / 2 + 16}" r="5" fill="${genderDotColor}" />

                <!-- Node Name -->
                <text x="0" y="${margaSafe ? -8 : 4}" text-anchor="middle" fill="#ffffff" font-size="13px" font-weight="700">
                    ${nameSafe}
                </text>

                <!-- Marga Text -->
                ${margaSafe ? `
                <text x="0" y="10" text-anchor="middle" fill="${isTargetNode ? '#fde047' : '#94a3b8'}" font-size="11px" font-weight="600">
                    ${margaSafe}
                </text>` : ''}

                <!-- Spouse or Region Subtitle -->
                ${spouseText ? `
                <text x="0" y="26" text-anchor="middle" fill="#cbd5e1" font-size="9.5px" font-style="italic">
                    ${spouseText}
                </text>` : (d.data.asal_daerah ? `
                <text x="0" y="26" text-anchor="middle" fill="#64748b" font-size="9.5px">
                    📍 ${escapeXml(d.data.asal_daerah)}
                </text>` : '')}
            </g>
        `;
    });

    // Construct full SVG
    return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${totalWidth}" height="${totalHeight}" viewBox="${viewBoxX} ${viewBoxY} ${totalWidth} ${totalHeight}">
    <defs>
        <filter id="cardShadow" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#000000" flood-opacity="0.6"/>
        </filter>
        <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#090d16" />
            <stop offset="50%" stop-color="#0f172a" />
            <stop offset="100%" stop-color="#090d16" />
        </linearGradient>
    </defs>
    <style>
        text {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            user-select: none;
        }
    </style>

    <!-- Main Background -->
    <rect x="${viewBoxX}" y="${viewBoxY}" width="${totalWidth}" height="${totalHeight}" fill="url(#bgGrad)" />

    <!-- Ambient Decorative Light -->
    <circle cx="${viewBoxX + 100}" cy="${viewBoxY + 100}" r="250" fill="#f59e0b" opacity="0.04" />
    <circle cx="${viewBoxX + totalWidth - 100}" cy="${viewBoxY + totalHeight - 100}" r="300" fill="#6366f1" opacity="0.05" />

    <!-- Header Section -->
    <g class="header" transform="translate(${viewBoxX + totalWidth / 2}, ${viewBoxY + 50})">
        <text x="0" y="0" text-anchor="middle" fill="#fbbf24" font-size="20px" font-weight="900" letter-spacing="2">
            🏛️ ${escapeXml(titleText)}
        </text>
        <text x="0" y="24" text-anchor="middle" fill="#e2e8f0" font-size="13px" font-weight="600">
            ${escapeXml(subtitleText)}
        </text>
        <text x="0" y="44" text-anchor="middle" fill="#64748b" font-size="11px">
            ${escapeXml(dateText)}
        </text>
        <line x1="${-Math.min(totalWidth / 2 - 60, 320)}" y1="58" x2="${Math.min(totalWidth / 2 - 60, 320)}" y2="58" stroke="#334155" stroke-width="1.5" stroke-linecap="round" />
    </g>

    <!-- Generation Guidelines -->
    ${guidelinesSvg}

    <!-- Links & Nodes -->
    ${linksSvg}
    ${nodesSvg}

    <!-- Footer -->
    <g class="footer" transform="translate(${viewBoxX + totalWidth / 2}, ${viewBoxY + totalHeight - 30})">
        <text x="0" y="0" text-anchor="middle" fill="#475569" font-size="10px">
            Tarombo Digital • Aplikasi Silsilah Batak Interaktif
        </text>
    </g>
</svg>`;
}

function downloadUrl(url, filename) {
    const a = document.createElement('a');
    a.download = filename;
    a.href = url;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

/**
 * Export tree as PNG Image.
 * Accurately parses SVG and draws to HTML5 Canvas safely clamped to browser limits.
 */
export async function exportTreeAsPNG(treeData, selectedNode = null, ancestorIdsSet = null, filename = 'tarombo-silsilah.png') {
    const svgString = generateTreeSVG(treeData, selectedNode, ancestorIdsSet);
    if (!svgString) return;

    return new Promise((resolve, reject) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(svgString, 'image/svg+xml');
        const svgEl = doc.documentElement;

        const width = parseFloat(svgEl.getAttribute('width')) || 800;
        const height = parseFloat(svgEl.getAttribute('height')) || 600;

        // Scale factor for crisp retina resolution (clamped to max 8192px dimension)
        const maxDim = Math.max(width, height);
        let scale = 2;
        if (maxDim * scale > 8192) {
            scale = Math.max(1, 8192 / maxDim);
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(width * scale);
        canvas.height = Math.round(height * scale);
        const ctx = canvas.getContext('2d');

        const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(svgBlob);
        const img = new Image();

        img.onload = () => {
            try {
                ctx.scale(scale, scale);
                ctx.drawImage(img, 0, 0);
                URL.revokeObjectURL(url);

                if (canvas.toBlob) {
                    canvas.toBlob((blob) => {
                        if (!blob) {
                            const dataUrl = canvas.toDataURL('image/png');
                            downloadUrl(dataUrl, filename);
                            resolve();
                            return;
                        }
                        const blobUrl = URL.createObjectURL(blob);
                        downloadUrl(blobUrl, filename);
                        setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
                        resolve();
                    }, 'image/png');
                } else {
                    const dataUrl = canvas.toDataURL('image/png');
                    downloadUrl(dataUrl, filename);
                    resolve();
                }
            } catch (err) {
                console.error('PNG conversion error:', err);
                URL.revokeObjectURL(url);
                reject(err);
            }
        };

        img.onerror = (err) => {
            URL.revokeObjectURL(url);
            console.error('Failed to load SVG into Image for PNG conversion', err);
            reject(new Error('Failed to load SVG into Image'));
        };

        img.src = url;
    });
}

/**
 * Export tree as standalone SVG file.
 */
export function exportTreeAsSVG(treeData, selectedNode = null, ancestorIdsSet = null, filename = 'tarombo-silsilah.svg') {
    const svgString = generateTreeSVG(treeData, selectedNode, ancestorIdsSet);
    if (!svgString) return;

    const blob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
    const blobUrl = URL.createObjectURL(blob);
    downloadUrl(blobUrl, filename);
    setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
}

/**
 * Export tree as CSV spreadsheet.
 */
export function exportTreeAsCSV(treeData, ancestorIdsSet = null, filename = 'tarombo-silsilah.csv') {
    let list = flattenTreeData(treeData);

    if (ancestorIdsSet && ancestorIdsSet.size > 0) {
        list = list.filter(node => ancestorIdsSet.has(node.id));
    }

    list.sort((a, b) => (a.level || 0) - (b.level || 0));

    const headers = [
        'No',
        'Generasi (Level)',
        'Nama',
        'Marga',
        'Jenis Kelamin',
        'Orang Tua (Ayah)',
        'Pasangan',
        'Asal Daerah',
        'Tahun Lahir',
        'Tahun Wafat'
    ];

    const rows = list.map((item, idx) => [
        idx + 1,
        `Generasi ${item.level}`,
        `"${(item.name || '').replace(/"/g, '""')}"`,
        `"${(item.marga || '-').replace(/"/g, '""')}"`,
        item.gender,
        `"${(item.parent_name || '-').replace(/"/g, '""')}"`,
        `"${(item.spouses || '-').replace(/"/g, '""')}"`,
        `"${(item.asal_daerah || '-').replace(/"/g, '""')}"`,
        item.tahun_lahir,
        item.tahun_wafat
    ]);

    const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const blobUrl = URL.createObjectURL(blob);
    downloadUrl(blobUrl, filename);
    setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
}

/**
 * Export tree as Printable PDF document.
 */
export function exportTreeAsPrintPDF(treeData, selectedNode = null, ancestorIdsSet = null, title = 'Tarombo Silsilah Batak') {
    let list = flattenTreeData(treeData);

    if (ancestorIdsSet && ancestorIdsSet.size > 0) {
        list = list.filter(node => ancestorIdsSet.has(node.id));
    }

    list.sort((a, b) => (a.level || 0) - (b.level || 0));

    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const subtitleText = selectedNode
        ? `Garis Silsilah Leluhur: ${selectedNode.name} (${list.length} Generasi ke atas)`
        : `Laporan Seluruh Data Silsilah (${list.length} Node)`;

    const tableRows = list.map((item, idx) => `
        <tr>
            <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: center;">${idx + 1}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; text-align: center;">Gen ${item.level}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600;">${escapeXml(item.name)}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${escapeXml(item.marga)}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: center;">${escapeXml(item.gender)}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${escapeXml(item.parent_name)}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${escapeXml(item.spouses)}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${escapeXml(item.asal_daerah)}</td>
        </tr>
    `).join('');

    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>${escapeXml(title)}</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 24px; color: #1e293b; background: #fff; }
                h1 { margin: 0 0 6px 0; color: #0f172a; font-size: 24px; }
                .subtitle { color: #64748b; font-size: 14px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
                th { background-color: #f1f5f9; color: #334155; padding: 10px 8px; border: 1px solid #cbd5e1; text-align: left; font-weight: 600; }
                tr:nth-child(even) { background-color: #f8fafc; }
                .btn-print { padding: 8px 16px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; }
                @media print {
                    body { padding: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                <div>
                    <h1>🌳 ${escapeXml(title)}</h1>
                    <div class="subtitle">${escapeXml(subtitleText)} • Tanggal Dicetak: ${new Date().toLocaleDateString('id-ID')}</div>
                </div>
                <button class="btn-print no-print" onclick="window.print()">Cetak / Simpan PDF</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center; width: 40px;">No</th>
                        <th style="text-align: center; width: 90px;">Generasi</th>
                        <th>Nama</th>
                        <th>Marga</th>
                        <th style="text-align: center;">Jenis Kelamin</th>
                        <th>Orang Tua</th>
                        <th>Pasangan</th>
                        <th>Asal Daerah</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
            <script>
                window.onload = function() {
                    setTimeout(function() { window.print(); }, 500);
                }
            </script>
        </body>
        </html>
    `;

    printWindow.document.write(html);
    printWindow.document.close();
}
