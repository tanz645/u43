import { BaseEdge, EdgeLabelRenderer, getSmoothStepPath } from 'reactflow';

/**
 * Custom Workflow Edge Component with Labels
 * 
 * Uses React Flow's recommended EdgeLabelRenderer pattern for HTML-based labels.
 * This is necessary because:
 * 1. Built-in `label` prop only supports SVG text (limited styling)
 * 2. EdgeLabelRenderer allows HTML/div labels with full CSS styling control
 * 3. We need conditional rendering (only show when multiple outputs exist)
 * 
 * Reference: https://reactflow.dev/examples/edges/edge-label-renderer
 */
export default function WorkflowEdge({
  id,
  sourceX,
  sourceY,
  targetX,
  targetY,
  sourcePosition,
  targetPosition,
  style = {},
  markerEnd,
  data,
  selected,
}) {
  const [edgePath, labelX, labelY] = getSmoothStepPath({
    sourceX,
    sourceY,
    sourcePosition,
    targetX,
    targetY,
    targetPosition,
  });

  // Get label from edge data
  const label = data?.label || '';
  const showLabel = label && label.trim() !== '';

  return (
    <>
      <BaseEdge
        path={edgePath}
        markerEnd={markerEnd}
        style={{
          ...style,
          stroke: selected ? '#3b82f6' : '#9ca3af',
          strokeWidth: selected ? 3 : 2,
        }}
      />
      {showLabel && (
        <EdgeLabelRenderer>
          <div
            style={{
              position: 'absolute',
              transform: `translate(-50%, -50%) translate(${labelX}px,${labelY}px)`,
              fontSize: 11,
              fontWeight: 500,
              pointerEvents: 'all',
            }}
            className="nodrag nopan"
          >
            <div
              style={{
                background: 'white',
                padding: '2px 6px',
                borderRadius: '4px',
                border: `1px solid ${selected ? '#3b82f6' : '#d1d5db'}`,
                boxShadow: '0 1px 2px rgba(0, 0, 0, 0.1)',
                color: selected ? '#3b82f6' : '#374151',
                whiteSpace: 'nowrap',
              }}
            >
              {label}
            </div>
          </div>
        </EdgeLabelRenderer>
      )}
    </>
  );
}

