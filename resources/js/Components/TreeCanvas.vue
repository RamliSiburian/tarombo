<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import * as d3 from 'd3';
import axios from 'axios';

const props = defineProps({
    tree: Object,
    searchHighlightId: { type: Number, default: null },
});

const emit = defineEmits(['nodeClick', 'export']);

const svgRef = ref(null);
const containerRef = ref(null);
const selectedNodeId = ref(null);
const highlightedIds = ref(new Set());
let svgElement = null;
let zoomBehavior = null;

const NODE_WIDTH = 180;
const NODE_HEIGHT = 70;
const H_GAP = 40;
const V_GAP = 90;

function flattenTree(node, parent = null, depth = 0) {
    if (!node) return [];
    const result = [{ ...node, _parent: parent, _depth: depth }];
    if (node.children_recursive && node.gender === 'male') {
        for (const child of node.children_recursive) {
            result.push(...flattenTree(child, node, depth + 1));
        }
    }
    return result;
}

async function onNodeClick(node) {
    selectedNodeId.value = node.id;

    try {
        const response = await axios.get(route('tree.ancestors', node.id));
        const ancestorIds = new Set(response.data.ancestor_ids);
        ancestorIds.add(node.id);
        highlightedIds.value = ancestorIds;
        emit('nodeClick', response.data.node);
        updateHighlights();
    } catch (e) {
        console.error('Failed to load ancestors', e);
    }
}

function getNodeColor(node, isHighlighted, isSelected) {
    if (isSelected) return '#f59e0b';
    if (isHighlighted) return '#a78bfa';
    if (node.gender === 'female') return '#ec4899';
    return '#6366f1';
}

function getLinkColor(source, target) {
    if (highlightedIds.value.has(source.data.id) && highlightedIds.value.has(target.data.id)) {
        return '#a78bfa';
    }
    return '#374151';
}

function getLinkWidth(source, target) {
    if (highlightedIds.value.has(source.data.id) && highlightedIds.value.has(target.data.id)) {
        return 2.5;
    }
    return 1.2;
}

function updateHighlights() {
    if (!svgElement) return;

    svgElement.selectAll('.tree-link')
        .transition().duration(300)
        .attr('stroke', d => getLinkColor(d.source, d.target))
        .attr('stroke-width', d => getLinkWidth(d.source, d.target))
        .attr('opacity', d => {
            if (highlightedIds.value.size === 0) return 0.6;
            const bothHighlighted = highlightedIds.value.has(d.source.data.id) && highlightedIds.value.has(d.target.data.id);
            return bothHighlighted ? 1 : 0.2;
        });

    svgElement.selectAll('.node-card')
        .transition().duration(300)
        .attr('stroke', d => {
            if (d.data.status === 'pending') return '#64748b';
            if (d.data.id === selectedNodeId.value) return '#f59e0b';
            if (highlightedIds.value.has(d.data.id)) return '#a78bfa';
            return d.data.gender === 'female' ? '#f472b6' : '#6366f1';
        })
        .attr('stroke-width', d => {
            if (d.data.status === 'pending') return 1.5;
            if (d.data.id === selectedNodeId.value || highlightedIds.value.has(d.data.id)) return 2;
            return 1;
        })
        .attr('fill', d => {
            if (d.data.status === 'pending') return '#1e293b';
            if (d.data.id === selectedNodeId.value) return '#451a03';
            if (highlightedIds.value.has(d.data.id)) return '#1e1b4b';
            return d.data.gender === 'female' ? '#1f0a16' : '#0f0f23';
        })
        .attr('opacity', d => {
            if (d.data.status === 'pending') return 0.6;
            if (highlightedIds.value.size === 0) return 1;
            return highlightedIds.value.has(d.data.id) ? 1 : 0.35;
        });
}

function buildTree() {
    if (!svgRef.value || !props.tree) return;

    const container = containerRef.value;
    const width = container.clientWidth || 1200;
    const height = container.clientHeight || 700;

    // Clear
    d3.select(svgRef.value).selectAll('*').remove();

    const svg = d3.select(svgRef.value)
        .attr('width', width)
        .attr('height', height);

    const g = svg.append('g').attr('class', 'tree-root');
    svgElement = g;

    // D3 hierarchy
    const root = d3.hierarchy(props.tree, d => {
        if (d.gender === 'female') return [];
        return d.children_recursive || [];
    });

    // Tree layout
    const treeLayout = d3.tree()
        .nodeSize([NODE_WIDTH + H_GAP, NODE_HEIGHT + V_GAP])
        .separation((a, b) => (a.parent === b.parent ? 1.1 : 1.4));

    treeLayout(root);

    // Center tree
    const nodes = root.descendants();
    const minX = d3.min(nodes, d => d.x);
    const maxX = d3.max(nodes, d => d.x);
    const treeWidth = maxX - minX;

    // Generation Levels (Depth grouping)
    const depthMap = new Map();
    nodes.forEach(d => {
        const depth = d.depth;
        if (!depthMap.has(depth)) {
            depthMap.set(depth, {
                depth: depth,
                levelNumber: (d.data.level !== undefined && d.data.level !== null) ? d.data.level + 1 : depth + 1,
                y: d.y,
            });
        }
    });
    const levels = Array.from(depthMap.values()).sort((a, b) => a.depth - b.depth);

    // Horizontal Guidelines inside moving canvas group (g)
    const genGroup = g.append('g').attr('class', 'generation-guides');
    genGroup.selectAll('.gen-line')
        .data(levels)
        .enter()
        .append('line')
        .attr('class', 'gen-line')
        .attr('x1', minX - 5000)
        .attr('x2', maxX + 5000)
        .attr('y1', d => d.y)
        .attr('y2', d => d.y)
        .attr('stroke', '#334155')
        .attr('stroke-width', 1)
        .attr('stroke-dasharray', '4 4')
        .attr('opacity', 0.25);

    // Sticky Generation Badges Group (pinned to left margin)
    const stickyGenGroup = svg.append('g').attr('class', 'sticky-gen-badges');

    const levelBadge = stickyGenGroup.selectAll('.gen-badge')
        .data(levels)
        .enter()
        .append('g')
        .attr('class', 'gen-badge')
        .style('cursor', 'default');

    // Badge circle background
    levelBadge.append('circle')
        .attr('r', 15)
        .attr('fill', '#090d16')
        .attr('stroke', '#6366f1')
        .attr('stroke-width', 1.5)
        .style('filter', 'drop-shadow(0 2px 8px rgba(0,0,0,0.6))');

    // Badge number text
    levelBadge.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '0.35em')
        .attr('fill', '#e0e7ff')
        .attr('font-size', '12px')
        .attr('font-weight', '700')
        .attr('font-family', 'Inter, sans-serif')
        .text(d => d.levelNumber);

    levelBadge.append('title')
        .text(d => `Generasi ${d.levelNumber}`);

    // Links
    const linkGroup = g.append('g').attr('class', 'links');
    linkGroup.selectAll('.tree-link')
        .data(root.links())
        .enter()
        .append('path')
        .attr('class', 'tree-link')
        .attr('fill', 'none')
        .attr('stroke', '#374151')
        .attr('stroke-width', 1.2)
        .attr('opacity', 0.6)
        .attr('d', d3.linkVertical()
            .x(d => d.x)
            .y(d => d.y));

    // Nodes
    const nodeGroup = g.append('g').attr('class', 'nodes');
    const nodeEnter = nodeGroup.selectAll('.tree-node')
        .data(nodes)
        .enter()
        .append('g')
        .attr('class', 'tree-node')
        .attr('transform', d => `translate(${d.x},${d.y})`)
        .style('cursor', d => d.data.status === 'pending' ? 'not-allowed' : 'pointer')
        .on('click', (event, d) => {
            if (d.data.status === 'pending') return;
            onNodeClick(d.data);
        });

    // Node card background
    nodeEnter.append('rect')
        .attr('class', 'node-card')
        .attr('x', -NODE_WIDTH / 2)
        .attr('y', -NODE_HEIGHT / 2)
        .attr('width', NODE_WIDTH)
        .attr('height', NODE_HEIGHT)
        .attr('rx', 10)
        .attr('ry', 10)
        .attr('fill', d => {
            if (d.data.status === 'pending') return '#1e293b';
            return d.data.gender === 'female' ? '#1f0a16' : '#0f0f23';
        })
        .attr('stroke', d => {
            if (d.data.status === 'pending') return '#64748b';
            return d.data.gender === 'female' ? '#f472b6' : '#6366f1';
        })
        .attr('stroke-width', d => d.data.status === 'pending' ? 1.5 : 1)
        .attr('stroke-dasharray', d => d.data.status === 'pending' ? '4 4' : 'none')
        .attr('opacity', d => d.data.status === 'pending' ? 0.6 : 1)
        .style('filter', d => d.data.status === 'pending' ? 'none' : 'drop-shadow(0 4px 12px rgba(0,0,0,0.5))');

    // Gender indicator dot
    nodeEnter.append('circle')
        .attr('cx', NODE_WIDTH / 2 - 12)
        .attr('cy', -NODE_HEIGHT / 2 + 10)
        .attr('r', 5)
        .attr('fill', d => {
            if (d.data.status === 'pending') return '#64748b';
            return d.data.gender === 'female' ? '#ec4899' : '#818cf8';
        });

    // Node name
    nodeEnter.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', d => d.data.marga ? '-8' : '5')
        .attr('fill', d => d.data.status === 'pending' ? '#94a3b8' : '#f1f5f9')
        .attr('font-size', '12px')
        .attr('font-weight', '600')
        .attr('font-family', 'Inter, sans-serif')
        .text(d => {
            const name = d.data.name;
            return name.length > 20 ? name.substring(0, 18) + '…' : name;
        });

    // Marga text
    nodeEnter.filter(d => d.data.marga)
        .append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '10')
        .attr('fill', d => d.data.status === 'pending' ? '#64748b' : '#94a3b8')
        .attr('font-size', '10px')
        .attr('font-family', 'Inter, sans-serif')
        .text(d => `Marga ${d.data.marga}`);

    // Pending status badge label
    nodeEnter.filter(d => d.data.status === 'pending')
        .append('text')
        .attr('text-anchor', 'middle')
        .attr('x', 0)
        .attr('y', NODE_HEIGHT / 2 - 6)
        .attr('fill', '#94a3b8')
        .attr('font-size', '9px')
        .attr('font-style', 'italic')
        .text('⏳ Belum Aktif');

    // Children count badge
    nodeEnter.filter(d => d.data.status !== 'pending' && d.data.gender === 'male' && d.data.children_recursive?.length > 0)
        .append('text')
        .attr('text-anchor', 'middle')
        .attr('x', 0)
        .attr('y', NODE_HEIGHT / 2 - 6)
        .attr('fill', '#64748b')
        .attr('font-size', '9px')
        .text(d => `${d.data.children_recursive.length} keturunan`);

    // Hover effect
    nodeEnter
        .on('mouseenter', function(event, d) {
            if (d.data.status === 'pending') return;
            d3.select(this).select('.node-card')
                .transition().duration(150)
                .attr('stroke-width', 2)
                .style('filter', 'drop-shadow(0 6px 20px rgba(99,102,241,0.4))');
        })
        .on('mouseleave', function(event, d) {
            if (d.data.status === 'pending') return;
            d3.select(this).select('.node-card')
                .transition().duration(150)
                .attr('stroke-width', highlightedIds.value.has(d.data.id) ? 2 : 1)
                .style('filter', 'drop-shadow(0 4px 12px rgba(0,0,0,0.5))');
        });

    // Zoom & Pan
    zoomBehavior = d3.zoom()
        .scaleExtent([0.1, 3])
        .on('zoom', (event) => {
            g.attr('transform', event.transform);
            stickyGenGroup.selectAll('.gen-badge')
                .attr('transform', d => `translate(28, ${event.transform.applyY(d.y)})`)
                .attr('opacity', d => {
                    const screenY = event.transform.applyY(d.y);
                    if (screenY < -20 || screenY > height + 20) return 0;
                    return 1;
                });
        });

    svg.call(zoomBehavior);

    // Initial center view
    const initialX = width / 2 - (minX + treeWidth / 2);
    const initialY = 60;
    svg.call(zoomBehavior.transform, d3.zoomIdentity.translate(initialX, initialY).scale(0.85));
}

function zoomIn() {
    if (!svgRef.value || !zoomBehavior) return;
    d3.select(svgRef.value).transition().duration(300).call(zoomBehavior.scaleBy, 1.3);
}

function zoomOut() {
    if (!svgRef.value || !zoomBehavior) return;
    d3.select(svgRef.value).transition().duration(300).call(zoomBehavior.scaleBy, 0.77);
}

function resetView() {
    if (!svgRef.value || !zoomBehavior) return;
    const width = containerRef.value.clientWidth;
    d3.select(svgRef.value).transition().duration(500).call(
        zoomBehavior.transform,
        d3.zoomIdentity.translate(width / 2, 60).scale(0.85)
    );
    selectedNodeId.value = null;
    highlightedIds.value = new Set();
    updateHighlights();
    emit('nodeClick', null);
}

function centerOnNode(nodeId) {
    if (!svgElement || !svgRef.value || !zoomBehavior) return;
    const nodeEl = svgElement.selectAll('.tree-node')
        .filter(d => d.data.id === nodeId);

    if (nodeEl.empty()) return;
    const d = nodeEl.datum();
    const width = containerRef.value.clientWidth;
    const height = containerRef.value.clientHeight;
    const scale = 1.2;

    d3.select(svgRef.value).transition().duration(600).call(
        zoomBehavior.transform,
        d3.zoomIdentity
            .translate(width / 2 - d.x * scale, height / 2 - d.y * scale)
            .scale(scale)
    );
}

watch(() => props.searchHighlightId, async (newId) => {
    if (!newId) return;
    await onNodeClick({ id: newId });
    centerOnNode(newId);
});

watch(() => props.tree, () => {
    buildTree();
}, { deep: true });

onMounted(() => {
    buildTree();
    window.addEventListener('resize', buildTree);
});

onUnmounted(() => {
    window.removeEventListener('resize', buildTree);
});

function getSvgElement() {
    return svgRef.value;
}

function getHighlightedIds() {
    return highlightedIds.value;
}

defineExpose({ zoomIn, zoomOut, resetView, centerOnNode, getSvgElement, getHighlightedIds });
</script>

<template>
    <div ref="containerRef" class="relative w-full h-full">
        <!-- Controls (Zoom & Export - Nomor 2) -->
        <div class="absolute top-4 right-4 z-10 flex flex-col gap-2">
            <button @click="zoomIn" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 border border-white/10 rounded-xl text-white flex items-center justify-center transition-colors shadow-lg" title="Perbesar (Zoom In)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </button>
            <button @click="zoomOut" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 border border-white/10 rounded-xl text-white flex items-center justify-center transition-colors shadow-lg" title="Perkecil (Zoom Out)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            </button>
            <button @click="resetView" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 border border-white/10 rounded-xl text-white flex items-center justify-center transition-colors shadow-lg" title="Reset Tampilan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 0 18 0A9 9 0 0 0 3 12z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/></svg>
            </button>
            <div class="w-full h-px bg-white/10 my-0.5"></div>
            <!-- Export Icon Button with Tooltip (Nomor 2) -->
            <button @click="emit('export')" class="w-9 h-9 bg-indigo-600/50 hover:bg-indigo-600 border border-indigo-500/40 rounded-xl text-indigo-100 hover:text-white flex items-center justify-center transition-all shadow-lg hover:scale-105" title="Export Silsilah">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </button>
        </div>

        <!-- Legend & Instructions -->
        <div class="absolute bottom-4 left-4 z-10 bg-gray-900/85 backdrop-blur-md border border-white/10 rounded-xl p-3 text-xs space-y-1.5 shadow-xl max-w-xs">
            <div class="flex items-center justify-between gap-3 mb-2 pb-1.5 border-b border-white/5">
                <span class="text-[11px] text-amber-400/90 font-medium">💡 Klik node untuk detail</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-slate-500 border border-dashed border-slate-300"></div>
                <span class="text-gray-300">Belum Aktif (Menunggu ACC)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                <span class="text-gray-300">Laki-laki (ada keturunan)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-pink-500"></div>
                <span class="text-gray-300">Perempuan (silsilah berhenti)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-violet-400"></div>
                <span class="text-gray-300">Jalur silsilah terpilih</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                <span class="text-gray-300">Node terpilih</span>
            </div>
        </div>

        <svg ref="svgRef" class="w-full h-full"></svg>
    </div>
</template>
