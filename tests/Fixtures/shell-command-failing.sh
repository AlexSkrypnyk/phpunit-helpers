#!/bin/bash
echo "=== Starting Complex Operation ==="
echo "Step 1: Initializing components..."
echo "  - Component A: OK"
echo "  - Component B: OK"
echo "  - Component C: FAILED"
echo ""
echo "Step 2: Processing data..."
echo "----------------------------------------"
echo "| Item     | Status    | Progress      |"
echo "----------------------------------------"
echo "| File 1   | Complete  | [##########]  |"
echo "| File 2   | Error     | [####------]  |"
echo "| File 3   | Pending   | [----------]  |"
echo "----------------------------------------"
echo ""
echo "ERROR: Critical failure in Component C" >&2
echo "ERROR: Unable to process File 2" >&2
echo "ERROR: Operation aborted" >&2
echo "Some non-error output that should not be treated as an error"
echo ""
echo "=== Complex Operation Failed ===" >&2
exit 1
