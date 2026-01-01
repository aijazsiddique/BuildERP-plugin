/**
 * Visual Formula Builder
 * Node-based formula builder with drag and drop functionality
 */

(function ($) {
    'use strict';

    // Wait for document to be ready
    $(document).ready(function () {
        const $page = $('.berp-formula-builder');
        if (!$page.length) {
            return;
        }

        // Check if we're on the formula builder page
        const $visualBuilder = $('#berp-visual-formula-builder');
        const $textEditor = $('#berp-text-formula-editor');
        const $visualBuilderBtn = $('#berp-use-visual-builder');
        const $textEditorBtn = $('#berp-use-text-editor');
        const $formulaTextarea = $('textarea[name="berp_formula[formula]"]');
        const $finalFormulaDisplay = $('#berp-visual-builder-final-formula');

        // Toggle between visual and text editor
        $visualBuilderBtn.on('click', function() {
            $visualBuilder.show();
            $textEditor.hide();
            $visualBuilderBtn.addClass('button-primary').removeClass('button-secondary');
            $textEditorBtn.addClass('button-secondary').removeClass('button-primary');
        });

        $textEditorBtn.on('click', function() {
            $visualBuilder.hide();
            $textEditor.show();
            $textEditorBtn.addClass('button-primary').removeClass('button-secondary');
            $visualBuilderBtn.addClass('button-secondary').removeClass('button-primary');
        });

        // Initialize the visual builder
        initVisualFormulaBuilder();
    });

    /**
     * Initialize the visual formula builder
     */
    function initVisualFormulaBuilder() {
        const workspace = document.getElementById('berp-visual-builder-workspace');
        let svgLayer = document.getElementById('berp-visual-builder-connections');
        const outputDisplay = document.getElementById('berp-visual-builder-final-formula');
        const formulaTextarea = document.querySelector('textarea[name="berp_formula[formula]"]');

        let nodes = [];
        let connections = [];
        let nodeIdCounter = 0;

        // Canvas panning state
        let isPanning = false;
        let panStart = { x: 0, y: 0 };
        let panOffset = { x: 0, y: 0 };
        let zoomLevel = 1;

        // Create inner canvas container for panning
        const canvasInner = document.createElement('div');
        canvasInner.id = 'berp-canvas-inner';
        canvasInner.className = 'berp-canvas-inner';

        // Move existing children to inner container
        while (workspace.firstChild) {
            canvasInner.appendChild(workspace.firstChild);
        }
        workspace.appendChild(canvasInner);

        // Re-get SVG layer reference (it moved)
        svgLayer = document.getElementById('berp-visual-builder-connections');

        // Canvas panning functionality
        workspace.addEventListener('mousedown', function(e) {
            // Only pan if clicking directly on workspace or canvas inner (not on nodes)
            if (e.target === workspace || e.target === canvasInner || e.target.tagName === 'svg') {
                isPanning = true;
                workspace.classList.add('berp-panning');
                panStart.x = e.clientX - panOffset.x;
                panStart.y = e.clientY - panOffset.y;
                e.preventDefault();
            }
        });

        document.addEventListener('mousemove', function(e) {
            if (!isPanning) return;
            panOffset.x = e.clientX - panStart.x;
            panOffset.y = e.clientY - panStart.y;
            updateCanvasTransform();
        });

        document.addEventListener('mouseup', function() {
            if (isPanning) {
                isPanning = false;
                workspace.classList.remove('berp-panning');
            }
        });

        // Reset pan on double-click
        workspace.addEventListener('dblclick', function(e) {
            if (e.target === workspace || e.target === canvasInner || e.target.tagName === 'svg') {
                resetView();
            }
        });

        // Helper function to update canvas transform
        function updateCanvasTransform() {
            canvasInner.style.transform = `translate(${panOffset.x}px, ${panOffset.y}px) scale(${zoomLevel})`;
        }

        // Reset view function
        function resetView() {
            panOffset = { x: 0, y: 0 };
            zoomLevel = 1;
            updateCanvasTransform();
        }

        // Fit to canvas function
        function fitToCanvas() {
            const allNodes = canvasInner.querySelectorAll('.berp-node');
            if (allNodes.length === 0) return;

            // Get workspace dimensions
            const wsRect = workspace.getBoundingClientRect();
            const padding = 50;

            // Calculate bounding box of all nodes (in their original positions)
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

            allNodes.forEach(node => {
                const left = parseFloat(node.style.left) || 0;
                const top = parseFloat(node.style.top) || 0;
                const width = node.offsetWidth;
                const height = node.offsetHeight;

                minX = Math.min(minX, left);
                minY = Math.min(minY, top);
                maxX = Math.max(maxX, left + width);
                maxY = Math.max(maxY, top + height);
            });

            // Calculate content dimensions
            const contentWidth = maxX - minX;
            const contentHeight = maxY - minY;

            // Calculate available space (accounting for sidebar)
            const availableWidth = wsRect.width - (padding * 2);
            const availableHeight = wsRect.height - (padding * 2);

            // Calculate scale to fit
            const scaleX = availableWidth / contentWidth;
            const scaleY = availableHeight / contentHeight;
            zoomLevel = Math.min(scaleX, scaleY, 1); // Don't zoom in beyond 100%
            zoomLevel = Math.max(zoomLevel, 0.2); // Don't zoom out too much

            // Calculate pan offset to center content
            const scaledWidth = contentWidth * zoomLevel;
            const scaledHeight = contentHeight * zoomLevel;
            panOffset.x = (availableWidth - scaledWidth) / 2 + padding - (minX * zoomLevel);
            panOffset.y = (availableHeight - scaledHeight) / 2 + padding - (minY * zoomLevel);

            updateCanvasTransform();
            setTimeout(renderConnections, 10);
        }

        // Add canvas controls
        const canvasControls = document.createElement('div');
        canvasControls.className = 'berp-canvas-controls';
        canvasControls.innerHTML = `
            <button type="button" class="berp-canvas-btn berp-fit-view-btn" title="Fit All Nodes to View">
                <span class="dashicons dashicons-editor-expand"></span>
            </button>
            <button type="button" class="berp-canvas-btn berp-reset-view-btn" title="Reset View (100%)">
                <span class="dashicons dashicons-image-rotate"></span>
            </button>
            <span class="berp-zoom-indicator">100%</span>
            <span class="berp-canvas-hint">Drag to pan • Scroll to zoom</span>
        `;
        workspace.appendChild(canvasControls);

        const zoomIndicator = canvasControls.querySelector('.berp-zoom-indicator');

        // Update zoom indicator
        function updateZoomIndicator() {
            zoomIndicator.textContent = Math.round(zoomLevel * 100) + '%';
        }

        // Fit to canvas button handler
        canvasControls.querySelector('.berp-fit-view-btn').addEventListener('click', function() {
            fitToCanvas();
            updateZoomIndicator();
        });

        // Reset view button handler
        canvasControls.querySelector('.berp-reset-view-btn').addEventListener('click', function() {
            resetView();
            updateZoomIndicator();
        });

        // Mouse wheel zoom
        workspace.addEventListener('wheel', function(e) {
            if (e.target.closest('.berp-node')) return; // Don't zoom when scrolling inside nodes
            e.preventDefault();
            
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            const newZoom = Math.max(0.2, Math.min(2, zoomLevel + delta));
            
            // Zoom towards mouse position
            const rect = workspace.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;
            
            // Adjust pan to zoom towards cursor
            const zoomRatio = newZoom / zoomLevel;
            panOffset.x = mouseX - (mouseX - panOffset.x) * zoomRatio;
            panOffset.y = mouseY - (mouseY - panOffset.y) * zoomRatio;
            
            zoomLevel = newZoom;
            updateCanvasTransform();
            updateZoomIndicator();
            renderConnections();
        }, { passive: false });

        // Get predefined variables from the dropdown template
        const variableDropdownTemplate = document.getElementById('berp-variable-dropdown-template');
        const predefinedVariables = [];
        if (variableDropdownTemplate) {
            const options = variableDropdownTemplate.querySelectorAll('option');
            options.forEach(option => {
                if (option.value) {
                    predefinedVariables.push({
                        key: option.value,
                        label: option.textContent
                    });
                }
            });
        }

        // Node Types configuration
        const NODE_TYPES = {
            'RESULT':   { title: 'FINAL RESULT', class: 'result', in: ['Input'], out: [] },
            'VARIABLE': { title: 'Variable', class: 'var', in: [], out: ['Value'], hasInput: true, hasDropdown: true },
            'NUMBER':   { title: 'Number', class: 'var', in: [], out: ['Value'], hasInput: true, inputType: 'number' },
            'ADD':      { title: 'Add (+)', class: 'math', in: ['A', 'B'], out: ['Result'], dynamic: true },
            'MUL':      { title: 'Multiply (*)', class: 'math', in: ['A', 'B'], out: ['Result'], dynamic: true },
            'SUB':      { title: 'Subtract (-)', class: 'math', in: ['A', 'B'], out: ['Result'] },
            'DIV':      { title: 'Divide (/)', class: 'math', in: ['A', 'B'], out: ['Result'] },
            'IF':       { title: 'IF Statement', class: 'func', in: ['Condition', 'True', 'False'], out: ['Result'] },
            'MIN':      { title: 'Minimum', class: 'func', in: ['A', 'B'], out: ['Result'] },
            'MAX':      { title: 'Maximum', class: 'func', in: ['A', 'B'], out: ['Result'] },
            'GT':       { title: 'Greater Than', class: 'logic', in: ['A', 'B'], out: ['Bool'] },
            'LT':       { title: 'Less Than', class: 'logic', in: ['A', 'B'], out: ['Bool'] }
        };

        // Initialize with a RESULT node
        createNode('RESULT', 600, 200);

        // Drag & Drop setup
        document.querySelectorAll('.berp-node-btn').forEach(item => {
            item.addEventListener('dragstart', e => e.dataTransfer.setData('type', item.dataset.type));
        });

        workspace.addEventListener('dragover', e => e.preventDefault());
        workspace.addEventListener('drop', e => {
            e.preventDefault();
            const type = e.dataTransfer.getData('type');
            if (type) {
                const rect = workspace.getBoundingClientRect();
                // Account for pan offset when dropping
                createNode(type, e.clientX - rect.left - panOffset.x, e.clientY - rect.top - panOffset.y);
            }
        });

        // Create Node function
        function createNode(type, x, y) {
            const config = NODE_TYPES[type];
            const id = `node_${nodeIdCounter++}`;

            const el = document.createElement('div');
            el.className = `berp-node berp-node-${config.class}`;
            el.id = id;
            el.style.left = `${x}px`;
            el.style.top = `${y}px`;

            let inputHtml = '';
            if (config.hasInput) {
                if (config.hasDropdown) {
                    // Create dropdown for predefined variables
                    inputHtml = `
                        <select class="berp-node-input-field berp-variable-dropdown">
                            <option value="">Select a variable...</option>
                            ${predefinedVariables.map(v => `<option value="${v.key}">${v.label}</option>`).join('')}
                        </select>
                    `;
                } else {
                    const placeholder = type === 'VARIABLE' ? 'variable_name' : '0';
                    inputHtml = `<input type="${config.inputType || 'text'}" class="berp-node-input-field" placeholder="${placeholder}" oninput="updateFormula()">`;
                }
            }

            // Add dynamic button if needed
            let dynamicBtnHtml = '';
            if (config.dynamic) {
                dynamicBtnHtml = `<button class="berp-add-input-btn" onclick="addPort('${id}')">+ Add Input</button>`;
            }

            const deleteHtml = type !== 'RESULT' ? '<span class="berp-delete-btn">✕</span>' : '';

            el.innerHTML = `
                <div class="berp-node-header">
                    ${config.title} ${deleteHtml}
                </div>
                <div class="berp-node-body">
                    ${inputHtml}
                    <div class="berp-ports-container">
                        <div class="berp-port-group berp-port-inputs" id="${id}_inputs">
                            ${config.in.map((lbl, i) => createPortHtml(id, i, 'in', lbl)).join('')}
                        </div>
                        <div class="berp-port-group berp-port-outputs">
                            ${config.out.map((lbl, i) => createPortHtml(id, i, 'out', lbl)).join('')}
                        </div>
                    </div>
                    ${dynamicBtnHtml}
                </div>
            `;

            canvasInner.appendChild(el);
            nodes.push({ id, type });

            // Bind events
            el.querySelector('.berp-node-header').addEventListener('mousedown', startDragNode);
            if(type !== 'RESULT') el.querySelector('.berp-delete-btn').addEventListener('click', () => deleteNode(id));

            // Add event listener for dropdown changes
            const dropdown = el.querySelector('.berp-variable-dropdown');
            if (dropdown) {
                dropdown.addEventListener('change', updateFormula);
            }
        }

        function createPortHtml(nodeId, index, type, label) {
            return `
                <div class="berp-port ${type}" data-port="${type}-${index}" onmousedown="startWire(event, '${nodeId}', '${type}-${index}', '${type}')">
                    <span class="berp-port-label">${label}</span>
                </div>
            `;
        }

        // Dynamic Port Logic
        window.addPort = function(nodeId) {
            const inputContainer = document.getElementById(`${nodeId}_inputs`);
            const currentPorts = inputContainer.querySelectorAll('.berp-port.in').length;

            // Generate label (C, D, E...)
            const label = String.fromCharCode(65 + currentPorts); // 65 is 'A', +2 is 'C'

            const div = document.createElement('div');
            div.innerHTML = createPortHtml(nodeId, currentPorts, 'in', label);

            // Append the child directly
            inputContainer.appendChild(div.firstElementChild);

            // Refresh wires in case node height changed
            setTimeout(renderConnections, 0);
        };

        function deleteNode(id) {
            connections = connections.filter(c => c.from !== id && c.to !== id);
            nodes = nodes.filter(n => n.id !== id);
            document.getElementById(id).remove();
            renderConnections();
            updateFormula();
        }

        // Drag Node functionality
        let dragNode = null;
        let offset = { x: 0, y: 0 };

        function startDragNode(e) {
            e.stopPropagation(); // Prevent canvas panning when dragging nodes
            dragNode = e.target.closest('.berp-node');
            const rect = dragNode.getBoundingClientRect();
            offset.x = e.clientX - rect.left;
            offset.y = e.clientY - rect.top;
            document.addEventListener('mousemove', onDragNode);
            document.addEventListener('mouseup', stopDragNode);
        }

        function onDragNode(e) {
            if (!dragNode) return;
            const wsRect = workspace.getBoundingClientRect();
            // Account for pan offset when dragging nodes
            dragNode.style.left = `${e.clientX - wsRect.left - offset.x - panOffset.x}px`;
            dragNode.style.top = `${e.clientY - wsRect.top - offset.y - panOffset.y}px`;
            renderConnections();
        }

        function stopDragNode() {
            dragNode = null;
            document.removeEventListener('mousemove', onDragNode);
            document.removeEventListener('mouseup', stopDragNode);
        }

        // Wiring functionality
        let dragWire = null;
        let tempLine = null;

        window.startWire = function(e, nodeId, portId, type) {
            e.stopPropagation();
            e.preventDefault();
            if (type === 'in') return; // Drag from output only

            tempLine = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            tempLine.setAttribute('class', 'berp-drag-line');
            svgLayer.appendChild(tempLine);

            const rect = e.target.getBoundingClientRect();
            const wsRect = workspace.getBoundingClientRect();
            dragWire = {
                nodeId, portId,
                startX: ((rect.left + rect.width/2) - wsRect.left - panOffset.x) / zoomLevel,
                startY: ((rect.top + rect.height/2) - wsRect.top - panOffset.y) / zoomLevel
            };

            document.addEventListener('mousemove', onDragWire);
            document.addEventListener('mouseup', onDropWire);
        };

        function onDragWire(e) {
            const wsRect = workspace.getBoundingClientRect();
            const endX = (e.clientX - wsRect.left - panOffset.x) / zoomLevel;
            const endY = (e.clientY - wsRect.top - panOffset.y) / zoomLevel;
            const d = getBezierPath(dragWire.startX, dragWire.startY, endX, endY);
            tempLine.setAttribute('d', d);
        }

        function onDropWire(e) {
            document.removeEventListener('mousemove', onDragWire);
            document.removeEventListener('mouseup', onDropWire);
            if(tempLine) tempLine.remove();

            const target = document.elementFromPoint(e.clientX, e.clientY);
            if (target && target.classList.contains('in')) {
                const targetNode = target.closest('.berp-node');
                const targetPort = target.dataset.port;

                // Allow only one connection per Input port
                connections = connections.filter(c => !(c.to === targetNode.id && c.toPort === targetPort));

                connections.push({
                    from: dragWire.nodeId, fromPort: dragWire.portId,
                    to: targetNode.id, toPort: targetPort
                });
                renderConnections();
                updateFormula();
            }
        }

        function renderConnections() {
            svgLayer.innerHTML = '';
            const wsRect = workspace.getBoundingClientRect();

            connections.forEach(conn => {
                const fromEl = document.getElementById(conn.from)?.querySelector(`[data-port="${conn.fromPort}"]`);
                const toEl = document.getElementById(conn.to)?.querySelector(`[data-port="${conn.toPort}"]`);
                if(!fromEl || !toEl) return;

                const fRect = fromEl.getBoundingClientRect();
                const tRect = toEl.getBoundingClientRect();

                const d = getBezierPath(
                    ((fRect.left + fRect.width/2) - wsRect.left - panOffset.x) / zoomLevel,
                    ((fRect.top + fRect.height/2) - wsRect.top - panOffset.y) / zoomLevel,
                    ((tRect.left + tRect.width/2) - wsRect.left - panOffset.x) / zoomLevel,
                    ((tRect.top + tRect.height/2) - wsRect.top - panOffset.y) / zoomLevel
                );

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', d);
                path.setAttribute('class', 'berp-wire');
                path.onclick = () => {
                    connections = connections.filter(c => c !== conn);
                    renderConnections();
                    updateFormula();
                };
                svgLayer.appendChild(path);
            });
        }

        function getBezierPath(x1, y1, x2, y2) {
            const dist = Math.abs(x2 - x1) * 0.5;
            return `M ${x1} ${y1} C ${x1 + dist} ${y1}, ${x2 - dist} ${y2}, ${x2} ${y2}`;
        }

        // Formula Generation
        window.updateFormula = function() {
            const res = nodes.find(n => n.type === 'RESULT');
            if (!res) return;
            const formula = evaluateNode(res.id, 'in-0') || "Complete the connection...";

            // Update the visual display
            outputDisplay.textContent = formula;

            // Update the hidden textarea for form submission
            formulaTextarea.value = formula;
        };

        function evaluateNode(nodeId, inputPortId) {
            // Check for incoming connection
            const conn = connections.find(c => c.to === nodeId && c.toPort === inputPortId);

            // If not connected
            if (!conn) {
                const n = nodes.find(x => x.id === nodeId);
                // If it's a variable input node, get value
                if (n && (n.type === 'VARIABLE' || n.type === 'NUMBER')) {
                    const nodeEl = document.getElementById(nodeId);
                    if (n.type === 'VARIABLE') {
                        const dropdown = nodeEl.querySelector('.berp-variable-dropdown');
                        return dropdown ? dropdown.value : 'null';
                    } else {
                        const input = nodeEl.querySelector('.berp-node-input-field');
                        return input ? input.value : '0';
                    }
                }
                return null;
            }

            // Recursive evaluation
            const srcNode = nodes.find(n => n.id === conn.from);

            // Base cases
            if (srcNode.type === 'VARIABLE' || srcNode.type === 'NUMBER') {
                const nodeEl = document.getElementById(srcNode.id);
                if (srcNode.type === 'VARIABLE') {
                    const dropdown = nodeEl.querySelector('.berp-variable-dropdown');
                    return dropdown ? dropdown.value : 'null';
                } else {
                    const input = nodeEl.querySelector('.berp-node-input-field');
                    return input ? input.value : '0';
                }
            }

            // Logic based on Type
            if (srcNode.type === 'ADD' || srcNode.type === 'MUL') {
                // Collect ALL input ports
                const inputContainer = document.getElementById(srcNode.id).querySelector('.berp-port-inputs');
                const portCount = inputContainer.querySelectorAll('.berp-port').length;
                let parts = [];

                for(let i=0; i<portCount; i++) {
                    const v = evaluateNode(srcNode.id, `in-${i}`);
                    if (v) parts.push(v);
                }

                if (parts.length === 0) return '0';
                const op = srcNode.type === 'ADD' ? '+' : '*';
                return `(${parts.join(` ${op} `)})`;
            }

            if (['SUB','DIV','GT','LT','MIN','MAX'].includes(srcNode.type)) {
                const a = evaluateNode(srcNode.id, 'in-0') || '0';
                const b = evaluateNode(srcNode.id, 'in-1') || '0';

                if (srcNode.type === 'MIN') return `MIN(${a}, ${b})`;
                if (srcNode.type === 'MAX') return `MAX(${a}, ${b})`;

                const map = { 'SUB': '-', 'DIV': '/', 'GT': '>', 'LT': '<' };
                return `(${a} ${map[srcNode.type]} ${b})`;
            }

            if (srcNode.type === 'IF') {
                const c = evaluateNode(srcNode.id, 'in-0') || 'false';
                const t = evaluateNode(srcNode.id, 'in-1') || '0';
                const f = evaluateNode(srcNode.id, 'in-2') || '0';
                return `IF(${c}, ${t}, ${f})`;
            }

            return "error";
        }

        // Add CSS styles for the visual builder
        addVisualBuilderStyles();
    }

    /**
     * Add CSS styles for the visual builder
     */
    function addVisualBuilderStyles() {
        const style = document.createElement('style');
        style.id = 'berp-visual-builder-styles';
        style.textContent = `
            :root {
                --berp-bg-color: #1a1a1d;
                --berp-grid-color: #2b2b2f;
                --berp-node-bg: #2d2d30;
                --berp-text-color: #eee;
                --berp-accent-blue: #4dabf7;
                --berp-accent-green: #69db7c;
                --berp-accent-red: #ff8787;
                --berp-accent-orange: #ffa94d;
                --berp-port-size: 12px;
            }

            .berp-visual-builder-container {
                width: 100%;
                height: 600px;
                background-color: var(--berp-bg-color);
                background-image: radial-gradient(var(--berp-grid-color) 1px, transparent 1px);
                background-size: 20px 20px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: var(--berp-text-color);
                position: relative;
                border: 1px solid #333;
                border-radius: 4px;
                overflow: hidden;
                resize: vertical;
                min-height: 400px;
                max-height: 80vh;
            }

            .berp-visual-builder-wrapper {
                display: flex;
                height: 100%;
            }

            /* Sidebar */
            .berp-visual-builder-sidebar {
                width: 260px;
                background: #252526;
                border-right: 1px solid #3e3e42;
                display: flex;
                flex-direction: column;
                z-index: 10;
                box-shadow: 2px 0 10px rgba(0,0,0,0.3);
                user-select: none;
                overflow-y: auto;
            }

            .berp-visual-builder-header {
                padding: 15px;
                background: #333337;
                font-weight: bold;
                border-bottom: 1px solid #3e3e42;
            }

            .berp-visual-builder-category {
                padding: 10px 15px;
                font-size: 0.8rem;
                text-transform: uppercase;
                color: #888;
                margin-top: 10px;
            }

            .berp-node-btn {
                margin: 5px 15px;
                padding: 10px;
                background: #3e3e42;
                border: 1px solid #555;
                border-radius: 4px;
                cursor: grab;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: background 0.2s;
            }
            .berp-node-btn:hover { background: #505055; }
            .berp-node-btn::before {
                content: '';
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: #ccc;
            }
            .berp-node-var::before { background: var(--berp-accent-green); }
            .berp-node-math::before { background: var(--berp-accent-blue); }
            .berp-node-logic::before { background: var(--berp-accent-orange); }
            .berp-node-func::before { background: var(--berp-accent-red); }

            /* Workspace */
            .berp-visual-builder-workspace {
                flex: 1;
                position: relative;
                overflow: hidden;
                cursor: grab;
            }

            .berp-visual-builder-workspace.berp-panning {
                cursor: grabbing;
            }

            /* Canvas Inner - contains all nodes and connections, used for panning */
            .berp-canvas-inner {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                transform: translate(0px, 0px) scale(1);
                transform-origin: 0 0;
                will-change: transform;
                transition: transform 0.15s ease-out;
            }

            #berp-visual-builder-connections {
                position: absolute;
                top: 0;
                left: 0;
                width: 5000px;
                height: 5000px;
                pointer-events: none;
                z-index: 5;
            }

            /* Nodes */
            .berp-node {
                position: absolute;
                width: 180px;
                background: var(--berp-node-bg);
                border: 1px solid #454545;
                border-radius: 6px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                display: flex;
                flex-direction: column;
                z-index: 1;
                user-select: none;
                cursor: move;
            }

            .berp-node-header {
                padding: 8px 12px;
                border-radius: 6px 6px 0 0;
                font-size: 0.9rem;
                font-weight: bold;
                cursor: move;
                display: flex;
                justify-content: space-between;
            }

            .berp-node.berp-node-var .berp-node-header { background: rgba(105, 219, 124, 0.2); color: var(--berp-accent-green); }
            .berp-node.berp-node-math .berp-node-header { background: rgba(77, 171, 247, 0.2); color: var(--berp-accent-blue); }
            .berp-node.berp-node-logic .berp-node-header { background: rgba(255, 169, 77, 0.2); color: var(--berp-accent-orange); }
            .berp-node.berp-node-func .berp-node-header { background: rgba(255, 135, 135, 0.2); color: var(--berp-accent-red); }
            .berp-node.berp-node-result .berp-node-header { background: #fff; color: #000; }

            .berp-node-body {
                padding: 10px;
                position: relative;
            }

            .berp-node-input-field {
                width: 90%;
                background: #1e1e1e;
                border: 1px solid #555;
                color: white;
                padding: 5px;
                border-radius: 3px;
                margin-bottom: 10px;
            }

            .berp-variable-dropdown {
                width: 100%;
                background: #1e1e1e;
                border: 1px solid #555;
                color: white;
                padding: 5px;
                border-radius: 3px;
                margin-bottom: 10px;
            }

            .berp-delete-btn {
                cursor: pointer;
                font-size: 10px;
                opacity: 0.5;
            }
            .berp-delete-btn:hover { opacity: 1; color: red; }

            /* Ports */
            .berp-ports-container {
                display: flex;
                justify-content: space-between;
                margin-top: 5px;
            }

            .berp-port-group {
                display: flex;
                flex-direction: column;
                gap: 15px;
                width: 50%;
            }
            .berp-port-group.berp-port-inputs { align-items: flex-start; }
            .berp-port-group.berp-port-outputs { align-items: flex-end; }

            .berp-port {
                width: var(--berp-port-size);
                height: var(--berp-port-size);
                background: #777;
                border-radius: 50%;
                border: 2px solid var(--berp-node-bg);
                cursor: crosshair;
                position: relative;
                transition: background 0.2s;
            }
            .berp-port:hover { background: white; transform: scale(1.2); }

            .berp-port.in { margin-left: -17px; background: #555; }
            .berp-port.out { margin-right: -17px; background: #fff; }

            .berp-port-label {
                font-size: 0.7rem;
                color: #aaa;
                position: absolute;
                top: -2px;
                pointer-events: none;
                white-space: nowrap;
            }
            .berp-port.in .berp-port-label { left: 15px; }
            .berp-port.out .berp-port-label { right: 15px; }

            /* Dynamic Inputs */
            .berp-add-input-btn {
                display: block;
                margin: 10px auto 0;
                background: #3e3e42;
                color: #888;
                border: 1px dashed #666;
                border-radius: 4px;
                font-size: 10px;
                width: 100%;
                padding: 4px;
                cursor: pointer;
                text-align: center;
            }
            .berp-add-input-btn:hover { background: #555; color: white; }

            /* Wires */
            path.berp-wire {
                fill: none;
                stroke: #888;
                stroke-width: 2px;
                cursor: pointer;
                pointer-events: stroke;
            }
            path.berp-wire:hover { stroke: #ff6b6b; stroke-width: 4px; }
            path.berp-drag-line {
                fill: none;
                stroke: #4dabf7;
                stroke-width: 2px;
                stroke-dasharray: 5;
                pointer-events: none;
            }

            /* Bottom Bar */
            .berp-visual-builder-bottom-bar {
                position: absolute;
                bottom: 20px;
                left: 280px;
                right: 20px;
                background: #252526;
                border: 1px solid #3e3e42;
                padding: 15px;
                border-radius: 8px;
                display: flex;
                flex-direction: column;
                gap: 5px;
                z-index: 100;
            }
            .berp-visual-builder-formula-label {
                font-size: 0.8rem;
                color: #888;
            }
            .berp-visual-builder-formula-output {
                font-family: monospace;
                color: var(--berp-accent-green);
                font-size: 1.1rem;
                word-break: break-all;
            }

            /* Canvas Controls */
            .berp-canvas-controls {
                position: absolute;
                top: 10px;
                right: 10px;
                display: flex;
                align-items: center;
                gap: 10px;
                z-index: 100;
            }

            .berp-canvas-btn {
                width: 32px;
                height: 32px;
                background: #3e3e42;
                border: 1px solid #555;
                border-radius: 4px;
                color: #aaa;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }

            .berp-canvas-btn:hover {
                background: #505055;
                color: #fff;
            }

            .berp-canvas-btn .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }

            .berp-canvas-hint {
                font-size: 11px;
                color: #666;
                background: rgba(37, 37, 38, 0.9);
                padding: 4px 8px;
                border-radius: 3px;
            }

            .berp-zoom-indicator {
                font-size: 11px;
                color: #888;
                background: rgba(37, 37, 38, 0.9);
                padding: 4px 8px;
                border-radius: 3px;
                min-width: 45px;
                text-align: center;
            }
        `;

        document.head.appendChild(style);
    }

})(jQuery);
