"use client"

import { motion } from "framer-motion"

export function GeometricShapes() {
  return (
    <div className="absolute inset-0 overflow-hidden pointer-events-none">
      {/* Floating hexagons */}
      {Array.from({ length: 6 }).map((_, index) => (
        <motion.div
          key={`hex-${index}`}
          className="absolute"
          style={{
            left: `${20 + index * 15}%`,
            top: `${10 + (index % 3) * 30}%`,
          }}
          animate={{
            rotate: [0, 360],
            y: [0, -20, 0],
            opacity: [0.2, 0.6, 0.2],
          }}
          transition={{
            duration: 8 + index * 2,
            repeat: Number.POSITIVE_INFINITY,
            delay: index * 0.5,
            ease: "easeInOut",
          }}
        >
          <svg width="24" height="24" viewBox="0 0 24 24">
            <polygon
              points="12,2 22,8.5 22,15.5 12,22 2,15.5 2,8.5"
              fill="none"
              stroke="url(#hexGradient)"
              strokeWidth="1"
              opacity="0.6"
            />
            <defs>
              <linearGradient id="hexGradient">
                <stop offset="0%" stopColor="#3B82F6" />
                <stop offset="100%" stopColor="#06B6D4" />
              </linearGradient>
            </defs>
          </svg>
        </motion.div>
      ))}

      {/* Floating triangles */}
      {Array.from({ length: 4 }).map((_, index) => (
        <motion.div
          key={`tri-${index}`}
          className="absolute"
          style={{
            right: `${10 + index * 20}%`,
            top: `${20 + index * 20}%`,
          }}
          animate={{
            rotate: [0, -360],
            scale: [1, 1.3, 1],
            opacity: [0.3, 0.7, 0.3],
          }}
          transition={{
            duration: 6 + index,
            repeat: Number.POSITIVE_INFINITY,
            delay: index * 0.8,
            ease: "easeInOut",
          }}
        >
          <svg width="16" height="16" viewBox="0 0 16 16">
            <polygon points="8,2 14,14 2,14" fill="none" stroke="url(#triGradient)" strokeWidth="1" opacity="0.5" />
            <defs>
              <linearGradient id="triGradient">
                <stop offset="0%" stopColor="#06B6D4" />
                <stop offset="100%" stopColor="#8B5CF6" />
              </linearGradient>
            </defs>
          </svg>
        </motion.div>
      ))}

      {/* Floating circles */}
      {Array.from({ length: 8 }).map((_, index) => (
        <motion.div
          key={`circle-${index}`}
          className="absolute w-3 h-3 rounded-full border border-blue-400/40"
          style={{
            left: `${Math.random() * 80 + 10}%`,
            top: `${Math.random() * 80 + 10}%`,
          }}
          animate={{
            scale: [1, 1.5, 1],
            opacity: [0.2, 0.8, 0.2],
            y: [0, -40, 0],
          }}
          transition={{
            duration: 4 + index,
            repeat: Number.POSITIVE_INFINITY,
            delay: index * 0.3,
            ease: "easeInOut",
          }}
        />
      ))}
    </div>
  )
}
