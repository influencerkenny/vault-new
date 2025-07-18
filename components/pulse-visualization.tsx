"use client"

import { motion } from "framer-motion"

export function PulseVisualization() {
  return (
    <div className="absolute inset-0 flex items-center justify-center">
      {/* Central pulse */}
      <motion.div
        className="absolute w-4 h-4 bg-gradient-to-r from-blue-400 to-cyan-400 rounded-full"
        animate={{
          scale: [1, 2, 1],
          opacity: [1, 0.3, 1],
        }}
        transition={{
          duration: 2,
          repeat: Number.POSITIVE_INFINITY,
          ease: "easeInOut",
        }}
      />

      {/* Pulse rings */}
      {Array.from({ length: 4 }).map((_, index) => (
        <motion.div
          key={index}
          className="absolute border border-blue-400/30 rounded-full"
          style={{
            width: `${(index + 1) * 60}px`,
            height: `${(index + 1) * 60}px`,
          }}
          animate={{
            scale: [1, 1.2, 1],
            opacity: [0.5, 0.1, 0.5],
          }}
          transition={{
            duration: 3,
            repeat: Number.POSITIVE_INFINITY,
            delay: index * 0.5,
            ease: "easeInOut",
          }}
        />
      ))}

      {/* Connecting lines */}
      {Array.from({ length: 6 }).map((_, index) => (
        <motion.div
          key={index}
          className="absolute w-px h-20 bg-gradient-to-t from-transparent via-blue-400/40 to-transparent"
          style={{
            transform: `rotate(${index * 60}deg)`,
            transformOrigin: "bottom center",
          }}
          animate={{
            opacity: [0.2, 0.8, 0.2],
            scaleY: [0.5, 1, 0.5],
          }}
          transition={{
            duration: 2,
            repeat: Number.POSITIVE_INFINITY,
            delay: index * 0.3,
            ease: "easeInOut",
          }}
        />
      ))}
    </div>
  )
}
