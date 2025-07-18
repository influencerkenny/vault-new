"use client"

import { motion } from "framer-motion"
import { useEffect, useState } from "react"

interface Node {
  id: number
  x: number
  y: number
  size: number
  connections: number[]
}

export function NetworkVisualization() {
  const [nodes, setNodes] = useState<Node[]>([])

  useEffect(() => {
    const newNodes: Node[] = [
      { id: 0, x: 20, y: 30, size: 8, connections: [1, 2] },
      { id: 1, x: 60, y: 20, size: 12, connections: [0, 2, 3] },
      { id: 2, x: 40, y: 60, size: 10, connections: [0, 1, 4] },
      { id: 3, x: 80, y: 50, size: 6, connections: [1, 4] },
      { id: 4, x: 70, y: 80, size: 8, connections: [2, 3] },
    ]
    setNodes(newNodes)
  }, [])

  return (
    <div className="absolute inset-0 opacity-30">
      <svg className="w-full h-full" viewBox="0 0 100 100">
        {/* Connections */}
        {nodes.map((node) =>
          node.connections.map((connectionId) => {
            const connectedNode = nodes.find((n) => n.id === connectionId)
            if (!connectedNode) return null
            return (
              <motion.line
                key={`${node.id}-${connectionId}`}
                x1={node.x}
                y1={node.y}
                x2={connectedNode.x}
                y2={connectedNode.y}
                stroke="url(#gradient)"
                strokeWidth="0.5"
                initial={{ pathLength: 0, opacity: 0 }}
                animate={{ pathLength: 1, opacity: 0.6 }}
                transition={{ duration: 2, delay: node.id * 0.3 }}
              />
            )
          }),
        )}

        {/* Nodes */}
        {nodes.map((node) => (
          <motion.circle
            key={node.id}
            cx={node.x}
            cy={node.y}
            r={node.size / 2}
            fill="url(#nodeGradient)"
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 0.8 }}
            transition={{ duration: 0.8, delay: node.id * 0.2 }}
          >
            <motion.animate
              attributeName="r"
              values={`${node.size / 2};${node.size / 2 + 2};${node.size / 2}`}
              dur="3s"
              repeatCount="indefinite"
            />
          </motion.circle>
        ))}

        {/* Gradients */}
        <defs>
          <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#3B82F6" stopOpacity="0.8" />
            <stop offset="100%" stopColor="#06B6D4" stopOpacity="0.2" />
          </linearGradient>
          <radialGradient id="nodeGradient">
            <stop offset="0%" stopColor="#06B6D4" stopOpacity="0.9" />
            <stop offset="100%" stopColor="#3B82F6" stopOpacity="0.6" />
          </radialGradient>
        </defs>
      </svg>
    </div>
  )
}
