/**
 * Utility for exporting genealogy tree in Visual (PNG/SVG) and Data Table (CSV/PDF) formats.
 */

function flattenTreeData(node, parentName = '-', depth = 1) {
    if (!node) return [];

    const spousesStr = node.spouses && node.spouses.length > 0
        ? node.spouses.map(s => s.name).join(', ')
        : '-';

    const item = {
        id: node.id,
        level: node.level || depth,
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

export function exportTreeAsPNG(svgElement, filename = 'tarombo-silsilah.png') {
    if (!svgElement) return;

    const clone = svgElement.cloneNode(true);
    const bbox = svgElement.getBBox();
    const padding = 60;

    const minX = bbox.x - padding;
    const minY = bbox.y - padding;
    const width = bbox.width + (padding * 2);
    const height = bbox.height + (padding * 2);

    clone.setAttribute('width', width);
    clone.setAttribute('height', height);
    clone.setAttribute('viewBox', `${minX} ${minY} ${width} ${height}`);

    const g = clone.querySelector('g.tree-root');
    if (g) {
        g.removeAttribute('transform');
    }

    const bgRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    bgRect.setAttribute('x', minX);
    bgRect.setAttribute('y', minY);
    bgRect.setAttribute('width', width);
    bgRect.setAttribute('height', height);
    bgRect.setAttribute('fill', '#0f172a');
    clone.insertBefore(bgRect, clone.firstChild);

    const serializer = new XMLSerializer();
    let svgString = serializer.serializeToString(clone);

    if (!svgString.match(/^<svg[^>]+xmlns="http\:\/\/www\.w3\.org\/2000\/svg"/)) {
        svgString = svgString.replace(/^<svg/, '<svg xmlns="http://www.w3.org/2000/svg"');
    }

    const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(svgBlob);

    const img = new Image();
    img.onload = () => {
        const canvas = document.createElement('canvas');
        const scale = 2; // High resolution
        canvas.width = Math.max(width * scale, 800);
        canvas.height = Math.max(height * scale, 600);
        const ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);
        ctx.drawImage(img, 0, 0);

        URL.revokeObjectURL(url);

        const a = document.createElement('a');
        a.download = filename;
        a.href = canvas.toDataURL('image/png');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    };
    img.src = url;
}

export function exportTreeAsSVG(svgElement, filename = 'tarombo-silsilah.svg') {
    if (!svgElement) return;

    const clone = svgElement.cloneNode(true);
    const bbox = svgElement.getBBox();
    const padding = 60;

    const minX = bbox.x - padding;
    const minY = bbox.y - padding;
    const width = bbox.width + (padding * 2);
    const height = bbox.height + (padding * 2);

    clone.setAttribute('width', width);
    clone.setAttribute('height', height);
    clone.setAttribute('viewBox', `${minX} ${minY} ${width} ${height}`);

    const g = clone.querySelector('g.tree-root');
    if (g) {
        g.removeAttribute('transform');
    }

    const bgRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    bgRect.setAttribute('x', minX);
    bgRect.setAttribute('y', minY);
    bgRect.setAttribute('width', width);
    bgRect.setAttribute('height', height);
    bgRect.setAttribute('fill', '#0f172a');
    clone.insertBefore(bgRect, clone.firstChild);

    const serializer = new XMLSerializer();
    let svgString = serializer.serializeToString(clone);

    if (!svgString.match(/^<svg[^>]+xmlns="http\:\/\/www\.w3\.org\/2000\/svg"/)) {
        svgString = svgString.replace(/^<svg/, '<svg xmlns="http://www.w3.org/2000/svg"');
    }

    const blob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

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
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

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
            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600;">${item.name}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${item.marga}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: center;">${item.gender}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${item.parent_name}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${item.spouses}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${item.asal_daerah}</td>
        </tr>
    `).join('');

    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>${title}</title>
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
                    <h1>🌳 ${title}</h1>
                    <div class="subtitle">${subtitleText} • Tanggal Dicetak: ${new Date().toLocaleDateString('id-ID')}</div>
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
