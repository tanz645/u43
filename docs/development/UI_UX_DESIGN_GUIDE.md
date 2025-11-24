# UI/UX Design Guide: Visual Workflow Builder

## Design Philosophy

Inspired by n8n, Zapier, and modern workflow tools, our visual workflow builder prioritizes:
- **Clarity**: Easy to understand at a glance
- **Efficiency**: Fast workflow creation
- **Flexibility**: Support complex workflows
- **Modern**: Contemporary design language

---

## Design System

### Color Palette

```css
/* Primary Colors */
--primary-50: #eff6ff;
--primary-100: #dbeafe;
--primary-500: #3b82f6;  /* Main brand color */
--primary-600: #2563eb;
--primary-700: #1d4ed8;

/* Node Type Colors */
--trigger-color: #10b981;    /* Green */
--action-color: #3b82f6;      /* Blue */
--agent-color: #8b5cf6;       /* Purple */
--condition-color: #f59e0b;  /* Orange */
--data-color: #6b7280;        /* Gray */

/* Status Colors */
--success: #10b981;
--error: #ef4444;
--warning: #f59e0b;
--info: #3b82f6;

/* Neutral Colors */
--gray-50: #f9fafb;
--gray-100: #f3f4f6;
--gray-200: #e5e7eb;
--gray-300: #d1d5db;
--gray-500: #6b7280;
--gray-700: #4b5563;
--gray-900: #111827;

/* Background */
--bg-primary: #ffffff;
--bg-secondary: #f9fafb;
--bg-canvas: #fafafa;
```

### Typography

```css
/* Font Family */
--font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
--font-mono: 'JetBrains Mono', 'Fira Code', monospace;

/* Font Sizes */
--text-xs: 0.75rem;    /* 12px */
--text-sm: 0.875rem;   /* 14px */
--text-base: 1rem;     /* 16px */
--text-lg: 1.125rem;   /* 18px */
--text-xl: 1.25rem;    /* 20px */
--text-2xl: 1.5rem;    /* 24px */
--text-3xl: 1.875rem;  /* 30px */

/* Font Weights */
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

### Spacing Scale

```css
--space-1: 0.25rem;   /* 4px */
--space-2: 0.5rem;    /* 8px */
--space-3: 0.75rem;   /* 12px */
--space-4: 1rem;      /* 16px */
--space-6: 1.5rem;    /* 24px */
--space-8: 2rem;      /* 32px */
--space-12: 3rem;     /* 48px */
--space-16: 4rem;     /* 64px */
```

### Shadows

```css
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
--shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
--shadow-md: 0 4px 6px -1px rgba(0, 0,0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
--shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
```

### Border Radius

```css
--radius-sm: 0.25rem;   /* 4px */
--radius: 0.375rem;     /* 6px */
--radius-md: 0.5rem;    /* 8px */
--radius-lg: 0.75rem;   /* 12px */
--radius-xl: 1rem;    /* 16px */
--radius-full: 9999px;
```

---

## Canvas Design

### Canvas Container

```css
.workflow-canvas {
  width: 100%;
  height: 100vh;
  background: var(--bg-canvas);
  background-image: 
    linear-gradient(rgba(0, 0, 0, 0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0, 0, 0, 0.02) 1px, transparent 1px);
  background-size: 20px 20px;
  position: relative;
  overflow: hidden;
}
```

### Viewport Controls

**Position**: Top-right corner, floating

```jsx
<div className="viewport-controls">
  <button className="zoom-in">+</button>
  <button className="zoom-out">-</button>
  <button className="fit-screen">Fit</button>
  <button className="reset-view">Reset</button>
</div>
```

**Styling**:
- Floating panel with shadow
- Icon buttons (24x24px)
- Hover effects
- Tooltips on hover

---

## Node Design

### Node Structure

```jsx
<div className="workflow-node node-trigger">
  {/* Node Header */}
  <div className="node-header">
    <div className="node-icon">
      <Icon name="trigger" />
    </div>
    <div className="node-title">Comment Posted</div>
    <div className="node-type-badge">Trigger</div>
  </div>
  
  {/* Node Body */}
  <div className="node-body">
    <div className="node-inputs">
      <div className="input-port" data-port-id="input-1">
        <div className="port-handle"></div>
        <div className="port-label">Data</div>
      </div>
    </div>
    
    <div className="node-content">
      {/* Node-specific content */}
    </div>
    
    <div className="node-outputs">
      <div className="output-port" data-port-id="output-1">
        <div className="port-handle"></div>
        <div className="port-label">Comment Data</div>
      </div>
    </div>
  </div>
  
  {/* Node Footer (optional) */}
  <div className="node-footer">
    <div className="node-status">Ready</div>
  </div>
</div>
```

### Node States

```css
/* Default State */
.workflow-node {
  background: white;
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  min-width: 200px;
  transition: all 0.2s;
}

/* Selected State */
.workflow-node.selected {
  border-color: var(--primary-500);
  box-shadow: var(--shadow-lg), 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Hover State */
.workflow-node:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}

/* Running State */
.workflow-node.running {
  border-color: var(--info);
  animation: pulse 2s infinite;
}

/* Success State */
.workflow-node.success {
  border-color: var(--success);
}

/* Error State */
.workflow-node.error {
  border-color: var(--error);
}
```

### Node Type Colors

```css
.node-trigger {
  border-left: 4px solid var(--trigger-color);
}

.node-action {
  border-left: 4px solid var(--action-color);
}

.node-agent {
  border-left: 4px solid var(--agent-color);
}

.node-condition {
  border-left: 4px solid var(--condition-color);
}
```

### Port Design

```css
.port-handle {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: white;
  border: 2px solid var(--gray-400);
  cursor: pointer;
  transition: all 0.2s;
}

.port-handle:hover {
  border-color: var(--primary-500);
  transform: scale(1.2);
}

.port-handle.connected {
  background: var(--primary-500);
  border-color: var(--primary-500);
}

.input-port .port-handle {
  left: -6px;
}

.output-port .port-handle {
  right: -6px;
}
```

---

## Connection/Edge Design

### Connection Style

```css
.workflow-edge {
  stroke: var(--gray-400);
  stroke-width: 2;
  fill: none;
  pointer-events: stroke;
  cursor: pointer;
}

.workflow-edge:hover {
  stroke: var(--primary-500);
  stroke-width: 3;
}

.workflow-edge.selected {
  stroke: var(--primary-600);
  stroke-width: 3;
}

/* Animated connection (data flow) */
.workflow-edge.animated {
  stroke-dasharray: 5, 5;
  animation: dash 1s linear infinite;
}
```

### Connection Path

- Use **Bezier curves** for smooth, professional look
- Control points for natural curves
- Avoid overlapping with nodes

---

## Node Palette

### Sidebar Design

```jsx
<div className="node-palette">
  <div className="palette-header">
    <h3>Nodes</h3>
    <input 
      type="search" 
      placeholder="Search nodes..." 
      className="palette-search"
    />
  </div>
  
  <div className="palette-categories">
    <Category name="Triggers" icon="bolt">
      <NodeItem id="comment-trigger" name="Comment Posted" />
      <NodeItem id="post-trigger" name="Post Published" />
    </Category>
    
    <Category name="Actions" icon="zap">
      <NodeItem id="approve-comment" name="Approve Comment" />
      <NodeItem id="send-email" name="Send Email" />
    </Category>
    
    <Category name="Agents" icon="brain">
      <NodeItem id="llm-agent" name="AI Decision Agent" />
    </Category>
  </div>
</div>
```

### Node Item in Palette

```css
.palette-node-item {
  display: flex;
  align-items: center;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius);
  cursor: grab;
  transition: background 0.2s;
}

.palette-node-item:hover {
  background: var(--gray-100);
}

.palette-node-item:active {
  cursor: grabbing;
}

.palette-node-item .icon {
  width: 20px;
  height: 20px;
  margin-right: var(--space-2);
  color: var(--gray-500);
}
```

---

## Node Configuration Panel

### Panel Layout

```jsx
<div className="config-panel">
  <div className="config-header">
    <h3>Configure Node</h3>
    <button className="close-btn">×</button>
  </div>
  
  <div className="config-body">
    <FormField label="Node Title" required>
      <Input value={node.title} onChange={...} />
    </FormField>
    
    <FormField label="Description">
      <Textarea value={node.description} />
    </FormField>
    
    {/* Dynamic fields based on node type */}
    {renderNodeConfig(node)}
  </div>
  
  <div className="config-footer">
    <button className="btn-secondary">Cancel</button>
    <button className="btn-primary">Save</button>
  </div>
</div>
```

### Form Components

```css
.form-field {
  margin-bottom: var(--space-4);
}

.form-label {
  display: block;
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--gray-700);
  margin-bottom: var(--space-1);
}

.form-input {
  width: 100%;
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--gray-300);
  border-radius: var(--radius);
  font-size: var(--text-sm);
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
```

---

## Workflow List View

### Table Design

```jsx
<table className="workflow-table">
  <thead>
    <tr>
      <th>Title</th>
      <th>Status</th>
      <th>Last Execution</th>
      <th>Success Rate</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    {workflows.map(workflow => (
      <tr key={workflow.id}>
        <td>{workflow.title}</td>
        <td>
          <StatusBadge status={workflow.status} />
        </td>
        <td>{formatDate(workflow.last_execution)}</td>
        <td>
          <SuccessRate value={workflow.success_rate} />
        </td>
        <td>
          <ActionMenu workflow={workflow} />
        </td>
      </tr>
    ))}
  </tbody>
</table>
```

### Status Badge

```css
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: var(--space-1) var(--space-2);
  border-radius: var(--radius-full);
  font-size: var(--text-xs);
  font-weight: var(--font-medium);
}

.status-badge.published {
  background: var(--success);
  color: white;
}

.status-badge.draft {
  background: var(--gray-200);
  color: var(--gray-700);
}

.status-badge.paused {
  background: var(--warning);
  color: white;
}
```

---

## Execution View

### Execution Logs

```jsx
<div className="execution-view">
  <div className="execution-header">
    <h3>Execution #1234</h3>
    <StatusBadge status="success" />
  </div>
  
  <div className="execution-timeline">
    {nodes.map(node => (
      <ExecutionNode 
        key={node.id}
        node={node}
        status={node.execution_status}
        duration={node.duration}
        data={node.data}
      />
    ))}
  </div>
</div>
```

### Execution Node

```css
.execution-node {
  display: flex;
  align-items: center;
  padding: var(--space-3);
  border-left: 3px solid var(--gray-300);
  margin-bottom: var(--space-2);
}

.execution-node.success {
  border-color: var(--success);
}

.execution-node.error {
  border-color: var(--error);
}

.execution-node.running {
  border-color: var(--info);
  animation: pulse 1s infinite;
}
```

---

## Responsive Design

### Breakpoints

```css
/* Mobile */
@media (max-width: 640px) {
  .node-palette {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 50vh;
    transform: translateY(100%);
    transition: transform 0.3s;
  }
  
  .node-palette.open {
    transform: translateY(0);
  }
}

/* Tablet */
@media (min-width: 641px) and (max-width: 1024px) {
  .node-palette {
    width: 250px;
  }
}

/* Desktop */
@media (min-width: 1025px) {
  .node-palette {
    width: 300px;
  }
}
```

---

## Animations

### Node Animations

```css
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.node-enter {
  animation: slideIn 0.3s ease-out;
}
```

### Connection Animations

```css
@keyframes dash {
  to {
    stroke-dashoffset: -10;
  }
}

.connection-animated {
  stroke-dasharray: 5, 5;
  animation: dash 1s linear infinite;
}
```

---

## Accessibility

### Keyboard Navigation

- **Tab**: Navigate between nodes
- **Enter**: Open node configuration
- **Delete**: Delete selected node/connection
- **Arrow Keys**: Move selected node
- **Space**: Toggle node selection
- **Escape**: Close panels, deselect

### Screen Reader Support

```jsx
<div 
  className="workflow-node"
  role="button"
  aria-label="Comment Posted trigger node"
  tabIndex={0}
>
  {/* Node content */}
</div>
```

### Focus Indicators

```css
.workflow-node:focus {
  outline: 2px solid var(--primary-500);
  outline-offset: 2px;
}
```

---

## Dark Mode (Future)

```css
@media (prefers-color-scheme: dark) {
  :root {
    --bg-primary: #1f2937;
    --bg-secondary: #111827;
    --bg-canvas: #0f172a;
    --text-primary: #f9fafb;
    --text-secondary: #d1d5db;
  }
}
```

---

## Component Library Recommendations

### React
- **React Flow**: Canvas library
- **Tailwind CSS**: Styling
- **Headless UI**: Accessible components
- **React Hook Form**: Form management
- **Zustand**: State management

### Vue
- **Vue Flow**: Canvas library
- **Tailwind CSS**: Styling
- **Headless UI Vue**: Accessible components
- **VeeValidate**: Form validation
- **Pinia**: State management

---

## Implementation Checklist

- [ ] Set up design system (colors, typography, spacing)
- [ ] Create canvas component with zoom/pan
- [ ] Implement node component with all states
- [ ] Create connection/edge rendering
- [ ] Build node palette sidebar
- [ ] Create node configuration panel
- [ ] Add node dragging and positioning
- [ ] Implement connection creation
- [ ] Add node selection and multi-select
- [ ] Create execution view
- [ ] Add animations and transitions
- [ ] Implement keyboard shortcuts
- [ ] Add accessibility features
- [ ] Test responsive design
- [ ] Polish and refine

---

## References

- **n8n**: https://n8n.io (workflow automation)
- **Zapier**: https://zapier.com (workflow builder)
- **Retool**: https://retool.com (internal tools)
- **React Flow**: https://reactflow.dev (canvas library)
- **Tailwind CSS**: https://tailwindcss.com (styling)


