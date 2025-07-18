"use client"

import { motion } from "framer-motion"

export function DataStreams() {
  return (
    <div className="absolute inset-0 overflow-hidden pointer-events-none">
      {/* Vertical data streams */}
      {Array.from({ length: 5 }).map((_, index) => (
        <motion.div
          key={`stream-${index}`}
          className="absolute w-px bg-gradient-to-b from-transparent via-blue-400/60 to-transparent"
          style={{
            left: `${20 + index * 15}%`,
            height: "100%",
          }}
          animate={{
            opacity: [0, 1, 0],
            scaleY: [0, 1, 0],
          }}
          transition={{
            duration: 3,
            repeat: Number.POSITIVE_INFINITY,
            delay: index * 0.6,
            ease: "easeInOut",
          }}
        />
      ))}

      {/* Horizontal data streams */}
      {Array.from({ length: 3 }).map((_, index) => (
        <motion.div
          key={`h-stream-${index}`}
          className="absolute h-px bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent"
          style={{
            top: `${30 + index * 20}%`,
            width: "100%",
          }}
          animate={{
            opacity: [0, 1, 0],
            scaleX: [0, 1, 0],
          }}
          transition={{
            duration: 4,
            repeat: Number.POSITIVE_INFINITY,
            delay: index * 0.8,
            ease: "easeInOut",
          }}
        />
      ))}

      {/* Data packets */}
      {Array.from({ length: 6 }).map((_, index) => (
        <motion.div
          key={`packet-${index}`}
          className="absolute w-2 h-2 bg-blue-400/80 rounded-full"
          style={{
            left: `${10 + index * 15}%`,
            top: `${20 + (index % 3) * 25}%`,
          }}
          animate={{
            x: [0, 100, 0],
            opacity: [0, 1, 0],
            scale: [0.5, 1, 0.5],
          }}
          transition={{
            duration: 5,
            repeat: Number.POSITIVE_INFINITY,
            delay: index * 0.4,
            ease: "easeInOut",
          }}
        />
      ))}
    </div>
  )
}
